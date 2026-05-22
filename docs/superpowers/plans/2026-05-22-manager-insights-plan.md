# Manager Insights Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a single Inertia page `/manager-brief` that surfaces an AI-ranked daily action list plus live widgets (Rs-at-stake, operator scoreboard, pending queue, trend strip), reusing existing Prism, LiveMonitor, and SidingPerformance infrastructure.

**Architecture:** Backend signal collectors and aggregate services emit typed DTOs; an orchestrator (`BuildManagerBrief`) feeds the ranked top-15 to a Prism agent that returns 5 ranked action cards; the result is cached per siding for 90 minutes and regenerated hourly by `manager-brief:refresh`; the controller serves cache + live widgets to an Inertia page that polls every 15 seconds.

**Tech Stack:** Laravel 13, Inertia v3, React 19, Tailwind v4, Filament v5, Pest v4, Prism (OpenRouter via `app/Services/PrismService.php`), Horizon, Postgres (live) / sqlite in-memory (test), Pan analytics.

**Spec reference:** `docs/superpowers/specs/2026-05-22-manager-insights-design.md`

---

## File Structure

### New backend files
- `app/DataTransferObjects/ManagerBrief/Signal.php` — single immutable DTO with `type`, `severity`, `rs_at_stake`, `recency_minutes`, `actionability`, `payload` (array). One DTO type for all signal kinds; `type` field discriminates downstream.
- `app/DataTransferObjects/ManagerBrief/ActionCard.php` — DTO for AI output: `severity`, `title`, `why`, `rs_at_stake`, `deep_link`, `deadline`.
- `app/DataTransferObjects/ManagerBrief/Payload.php` — full cache payload: `actions[]`, `generated_at`, `siding_id`, `model_used`, `ai_status`, `failed_reason`.
- `app/Actions/ManagerBrief/CollectSignals.php` — `final readonly`, single `handle(int $sidingId): array` returning `Signal[]`.
- `app/Actions/ManagerBrief/RankSignals.php` — pure function `handle(array $signals): array` returning top 15.
- `app/Actions/BuildManagerBrief.php` — orchestrator. `handle(int $sidingId): Payload`.
- `app/Services/ManagerBrief/LiveExposureCalculator.php`
- `app/Services/ManagerBrief/OperatorScoreboard.php`
- `app/Services/ManagerBrief/PendingQueue.php`
- `app/Services/ManagerBrief/TrendStrip.php`
- `app/Services/ForceMajeure/DowntimePenaltyMatcher.php` — extracted from `DisputesStitchForceMajeureCommand`. Shared between command + `CollectSignals`.
- `app/Ai/Agents/ManagerBriefAgent.php` — Prism agent. `synthesise(array $signals, array $context): ?array` returns `ActionCard[]` or `null` on failure.
- `app/Console/Commands/RefreshManagerBriefCommand.php` — `manager-brief:refresh --siding=?`.
- `app/Http/Controllers/ManagerBriefController.php` — `index(Request): Response`.
- `database/seeders/permissions/ManagerBriefPermissionsSeeder.php` — registers the two section permissions and grants them to existing roles.

### Modified backend files
- `routes/web.php` — register the new GET route under the existing auth middleware group with the section-permission middleware.
- `routes/console.php` — add hourly schedule for `manager-brief:refresh`, guarded by `Schema::hasTable('sidings')`.
- `app/Providers/AppServiceProvider.php` — add new Pan analytics names to the `configurePan()` allowlist (`nav-manager-brief`, `manager-brief-action-card`, `manager-brief-deeplink-*`, `manager-brief-widget-failed`, `manager-brief-refresh-now`).
- `app/Console/Commands/DisputesStitchForceMajeureCommand.php` — replace inline matching with calls into `DowntimePenaltyMatcher`. No behaviour change.
- `database/seeders/DatabaseSeeder.php` (or whatever calls permission seeders) — add `ManagerBriefPermissionsSeeder`.

### New frontend files
- `resources/js/pages/manager-brief/index.tsx`
- `resources/js/components/manager-brief/ActionCardStack.tsx`
- `resources/js/components/manager-brief/LiveExposureTicker.tsx`
- `resources/js/components/manager-brief/OperatorScoreboard.tsx`
- `resources/js/components/manager-brief/PendingQueue.tsx`
- `resources/js/components/manager-brief/TrendStrip.tsx`
- `resources/js/components/manager-brief/StalenessBanner.tsx`
- `resources/js/components/manager-brief/RefreshNowButton.tsx`

### Modified frontend files
- `resources/js/components/app-sidebar.tsx` — add "Manager Brief" nav entry near top, with `data-pan="nav-manager-brief"`.

### New test files
- `tests/Unit/Actions/ManagerBrief/RankSignalsTest.php`
- `tests/Unit/Ai/Agents/ManagerBriefAgentTest.php`
- `tests/Unit/Services/ManagerBrief/LiveExposureCalculatorTest.php`
- `tests/Unit/Services/ManagerBrief/OperatorScoreboardTest.php`
- `tests/Unit/Services/ManagerBrief/PendingQueueTest.php`
- `tests/Unit/Services/ManagerBrief/TrendStripTest.php`
- `tests/Unit/Services/ForceMajeure/DowntimePenaltyMatcherTest.php`
- `tests/Feature/ManagerBrief/ManagerBriefControllerTest.php`
- `tests/Feature/ManagerBrief/RefreshManagerBriefCommandTest.php`
- `tests/Feature/ManagerBrief/CollectSignalsTest.php`
- `tests/Feature/ManagerBrief/ScheduleRegistrationTest.php`
- `tests/Helpers/SeedsManagerBriefFixture.php` — trait, `seedManagerBriefFixture(int $sidingId): void` — creates 3 loading rakes (one over-PCC), 1 pending override, 1 force-majeure candidate, 1 silent scale row.

### New documentation
- `docs/developer/backend/actions/manager-brief.md` — Action doc per CLAUDE.md mandatory pattern.
- `docs/developer/backend/services/manager-brief.md` — Service doc.
- `docs/developer/frontend/pages/manager-brief.md` — Page doc.
- `docs/.manifest.json` — entries for above.

---

## Commit 1 — Backend Foundations

Goal: Every signal collector, every aggregate service, the force-majeure matcher, and all their tests, with **no AI and no routes**.

### Task 1: DTO scaffolding

**Files:**
- Create: `app/DataTransferObjects/ManagerBrief/Signal.php`
- Create: `app/DataTransferObjects/ManagerBrief/ActionCard.php`
- Create: `app/DataTransferObjects/ManagerBrief/Payload.php`

- [ ] **Step 1.1:** Generate the Action stubs (`php artisan make:class ...`) or write the three files directly. Use `final readonly class` with promoted constructor properties. Document each field with PHPDoc; include array shapes for `payload`/`actions`.
- [ ] **Step 1.2:** Add static `fromArray()` factory on `Payload` (used to deserialise from cache).
- [ ] **Step 1.3:** Add `toArray()` on all three (used to serialise to cache + Inertia).
- [ ] **Step 1.4:** Run `vendor/bin/pint --dirty --format agent`.
- [ ] **Step 1.5:** No tests yet — pure data classes; coverage comes via consumer tests in later tasks.

### Task 2: Force-majeure matcher extraction

**Files:**
- Create: `app/Services/ForceMajeure/DowntimePenaltyMatcher.php`
- Modify: `app/Console/Commands/DisputesStitchForceMajeureCommand.php`
- Test: `tests/Unit/Services/ForceMajeure/DowntimePenaltyMatcherTest.php`

- [ ] **Step 2.1:** Read `DisputesStitchForceMajeureCommand` end-to-end. Identify the section that matches `LoadriteDowntimeEvent` rows to `AppliedPenalty` rows (overlap of windows, penalty type ↔ downtime cause, not yet in `PenaltyReconciliation`).
- [ ] **Step 2.2:** Write failing test `it_returns_candidates_for_unreconciled_downtime_overlapping_penalties`. Seed one downtime window, one matching applied penalty, no reconciliation row → expect one candidate. Run: `vendor/bin/pest tests/Unit/Services/ForceMajeure/DowntimePenaltyMatcherTest.php`. Expected: FAIL (class does not exist).
- [ ] **Step 2.3:** Create `DowntimePenaltyMatcher` with method `candidates(int $sidingId, int $lookbackDays = 30): array`. Move the matching logic into it verbatim. Re-run the test; expected: PASS.
- [ ] **Step 2.4:** Add three more tests: `it_excludes_already_reconciled_penalties`, `it_requires_30_min_minimum_downtime`, `it_filters_by_siding`. Each in TDD order — write failing, watch fail, make pass.
- [ ] **Step 2.5:** Update `DisputesStitchForceMajeureCommand` to call `DowntimePenaltyMatcher::candidates(...)` instead of the inline logic. Run the command's existing test suite if any (`vendor/bin/pest tests/Feature/Disputes` or grep for the test file first).
- [ ] **Step 2.6:** Run `vendor/bin/pint --dirty --format agent`.
- [ ] **Step 2.7:** No commit yet — saved for the end of Commit 1.

### Task 3: Live exposure calculator

**Files:**
- Create: `app/Services/ManagerBrief/LiveExposureCalculator.php`
- Test: `tests/Unit/Services/ManagerBrief/LiveExposureCalculatorTest.php`

- [ ] **Step 3.1:** Failing test `it_sums_overload_rs_across_active_rakes`. Seed one loading rake with 3 wagons, one over PCC by 2 MT (assume Rs/MT from penalty config or hardcoded test rate). Expected calculator output: that rake's overload exposure.
- [ ] **Step 3.2:** Failing test `it_ignores_ghost_rakes_with_zero_loaded_and_no_loadrite_events`.
- [ ] **Step 3.3:** Failing test `it_excludes_weighbridge_weight_source_rows`.
- [ ] **Step 3.4:** Failing test `it_returns_zero_when_no_active_rakes`.
- [ ] **Step 3.5:** Implement `LiveExposureCalculator::handle(int $sidingId): array` returning `['total_rs' => float, 'breakdown' => array]`. Reuse `LiveMonitorDataBuilder` to find active rakes. Run all four tests until green.
- [ ] **Step 3.6:** Pint.

### Task 4: Operator scoreboard

**Files:**
- Create: `app/Services/ManagerBrief/OperatorScoreboard.php`
- Test: `tests/Unit/Services/ManagerBrief/OperatorScoreboardTest.php`

- [ ] **Step 4.1:** Failing test `it_returns_top_5_and_bottom_5_by_accuracy_in_7_day_window`.
- [ ] **Step 4.2:** Failing test `it_excludes_operators_with_fewer_than_10_wagons` (noise filter).
- [ ] **Step 4.3:** Failing test `it_returns_empty_when_no_data`.
- [ ] **Step 4.4:** Failing test `it_computes_rs_caused_per_operator` (overload × penalty rate).
- [ ] **Step 4.5:** Implement `OperatorScoreboard::handle(int $sidingId, int $windowDays = 7): array` returning `['top' => Operator[], 'bottom' => Operator[]]`. Reuse `LoaderOverloadMetricsService::applyLoaderOperatorNameFilterToQuery()` — do not duplicate the join logic.
- [ ] **Step 4.6:** Pint.

### Task 5: Pending queue

**Files:**
- Create: `app/Services/ManagerBrief/PendingQueue.php`
- Test: `tests/Unit/Services/ManagerBrief/PendingQueueTest.php`

- [ ] **Step 5.1:** Failing test `it_counts_overrides_awaiting_supervisor_review`.
- [ ] **Step 5.2:** Failing test `it_counts_disputes_ready_to_file_via_DowntimePenaltyMatcher`.
- [ ] **Step 5.3:** Failing test `it_returns_zero_counts_when_nothing_pending`.
- [ ] **Step 5.4:** Failing test `it_returns_oldest_pending_override_age_in_minutes`.
- [ ] **Step 5.5:** Implement `PendingQueue::handle(int $sidingId): array`. Calls `DowntimePenaltyMatcher::candidates(...)`; queries `LoadingOverride` directly for pending count.
- [ ] **Step 5.6:** Pint.

### Task 6: Trend strip

**Files:**
- Create: `app/Services/ManagerBrief/TrendStrip.php`
- Test: `tests/Unit/Services/ManagerBrief/TrendStripTest.php`

- [ ] **Step 6.1:** Failing test `it_returns_penalty_rs_week_over_week`.
- [ ] **Step 6.2:** Failing test `it_returns_throughput_mt_week_over_week`.
- [ ] **Step 6.3:** Failing test `it_returns_on_time_dispatch_percentage`.
- [ ] **Step 6.4:** Failing test `it_uses_cache_with_6_hour_ttl` (verify `Cache::remember` is called with 21600 seconds; mock the cache facade).
- [ ] **Step 6.5:** Implement `TrendStrip::handle(int $sidingId): array`. Query `SidingPerformance` for both the current and prior week, compute deltas.
- [ ] **Step 6.6:** Pint.

### Task 7: Collect signals

**Files:**
- Create: `app/Actions/ManagerBrief/CollectSignals.php`
- Create: `tests/Helpers/SeedsManagerBriefFixture.php` (trait)
- Test: `tests/Feature/ManagerBrief/CollectSignalsTest.php`

- [ ] **Step 7.1:** Implement the helper trait `SeedsManagerBriefFixture`. Method `seedManagerBriefFixture(int $sidingId): array` returning a map `['rake_active' => Rake, 'rake_overload' => Rake, 'override_pending' => LoadingOverride, 'downtime_event' => LoadriteDowntimeEvent, 'silent_scale_id' => string]`. Use existing factories — do not invent new ones.
- [ ] **Step 7.2:** Failing test `it_emits_live_overload_exposure_signal` (one signal per loading rake with overload > 0).
- [ ] **Step 7.3:** Failing test `it_emits_operator_anomaly_signal_when_overload_rate_doubles_baseline`. Seed 30 days of baseline events + a recent spike.
- [ ] **Step 7.4:** Failing test `it_emits_force_majeure_candidate_signal_via_matcher`. Use the matcher from Task 2.
- [ ] **Step 7.5:** Failing test `it_emits_scale_silence_signal_when_active_rake_and_scale_idle_2h`.
- [ ] **Step 7.6:** Failing test `it_emits_pending_override_signal_when_oldest_exceeds_threshold`.
- [ ] **Step 7.7:** Failing test `it_emits_underloading_trend_signal_from_SidingPerformance`.
- [ ] **Step 7.8:** Failing test `it_emits_at_risk_demurrage_signal_when_hours_to_deadline_below_threshold`.
- [ ] **Step 7.9:** Failing test `it_returns_empty_array_when_siding_has_no_data`.
- [ ] **Step 7.10:** Implement `CollectSignals::handle(int $sidingId): array`. One private method per signal type, each returning `Signal[]`. Public `handle` aggregates.
- [ ] **Step 7.11:** Run the full test class until green: `vendor/bin/pest tests/Feature/ManagerBrief/CollectSignalsTest.php`.
- [ ] **Step 7.12:** Pint.

### Task 8: Rank signals

**Files:**
- Create: `app/Actions/ManagerBrief/RankSignals.php`
- Test: `tests/Unit/Actions/ManagerBrief/RankSignalsTest.php`

- [ ] **Step 8.1:** Failing test `it_orders_by_rs_recency_actionability_product`.
- [ ] **Step 8.2:** Failing test `it_returns_top_15_only_when_more_signals_supplied`.
- [ ] **Step 8.3:** Failing test `it_returns_empty_when_input_empty`.
- [ ] **Step 8.4:** Failing test `it_breaks_ties_by_recency` (stable sort).
- [ ] **Step 8.5:** Implement. Score = `rs_at_stake * recencyWeight(recency_minutes) * actionability`. `recencyWeight` decays linearly over a week. Document the formula in a PHPDoc block on the method.
- [ ] **Step 8.6:** Pint.

### Task 9: Commit 1

- [ ] **Step 9.1:** Run the full new-test set:
  ```
  vendor/bin/pest tests/Unit/Services/ForceMajeure tests/Unit/Services/ManagerBrief tests/Unit/Actions/ManagerBrief tests/Feature/ManagerBrief/CollectSignalsTest.php
  ```
  Expected: all PASS.
- [ ] **Step 9.2:** `vendor/bin/pint --dirty --format agent`. Expected: `{"tool":"pint","result":"passed"}`.
- [ ] **Step 9.3:** Larastan on changed files only. Goal: zero new errors over baseline (project is not phpstan-clean overall).
- [ ] **Step 9.4:** Stage and commit:
  ```
  git add app/DataTransferObjects/ManagerBrief app/Services/ManagerBrief app/Services/ForceMajeure app/Actions/ManagerBrief app/Console/Commands/DisputesStitchForceMajeureCommand.php tests/Unit/Services/ForceMajeure tests/Unit/Services/ManagerBrief tests/Unit/Actions/ManagerBrief tests/Feature/ManagerBrief/CollectSignalsTest.php tests/Helpers/SeedsManagerBriefFixture.php
  git commit -m "feat(manager-brief): backend signal collectors + aggregate services + force-majeure matcher extraction"
  ```

---

## Commit 2 — AI Layer + Caching

Goal: AI synthesis + caching + scheduled hourly refresh. Page still does not exist.

### Task 10: Manager brief agent

**Files:**
- Create: `app/Ai/Agents/ManagerBriefAgent.php`
- Test: `tests/Unit/Ai/Agents/ManagerBriefAgentTest.php`

- [ ] **Step 10.1:** Read `app/Actions/GeneratePenaltyInsightsAction.php` to learn the established Prism prompt-construction pattern, response-parsing pattern, and error handling.
- [ ] **Step 10.2:** Failing test `it_sends_all_signals_to_prism_and_returns_5_typed_action_cards`. Mock `PrismService` to return a fixed JSON response with 5 cards. Assert prompt contains every signal type by checking the prompt string for marker substrings.
- [ ] **Step 10.3:** Failing test `it_returns_null_and_logs_warning_when_prism_throws`. Mock to throw; assert `Log::shouldReceive('warning')`.
- [ ] **Step 10.4:** Failing test `it_drops_cards_failing_schema_validation` (missing `rs_at_stake` or `deep_link`).
- [ ] **Step 10.5:** Failing test `it_clamps_output_to_5_cards_even_if_model_returns_more`.
- [ ] **Step 10.6:** Implement `ManagerBriefAgent::synthesise(array $signals, array $context): ?array`. The agent builds the prompt, calls `PrismService->defaultModel()`, validates schema, returns `ActionCard[]` or `null`.
- [ ] **Step 10.7:** Pint.

### Task 11: Build orchestrator

**Files:**
- Create: `app/Actions/BuildManagerBrief.php`
- Test: `tests/Feature/ManagerBrief/BuildManagerBriefTest.php` (new — add to plan)

- [ ] **Step 11.1:** Failing test `it_collects_signals_ranks_them_calls_agent_and_returns_payload`. Mock the agent.
- [ ] **Step 11.2:** Failing test `it_writes_payload_with_ai_status_failed_when_agent_returns_null`.
- [ ] **Step 11.3:** Failing test `it_includes_generated_at_and_model_used_and_siding_id_in_payload`.
- [ ] **Step 11.4:** Implement `BuildManagerBrief::handle(int $sidingId): Payload`. Compose `CollectSignals` → `RankSignals` → `ManagerBriefAgent`. Build `Payload`. Does **not** write to cache itself — that is the command's job.
- [ ] **Step 11.5:** Pint.

### Task 12: Refresh command + cache wiring

**Files:**
- Create: `app/Console/Commands/RefreshManagerBriefCommand.php`
- Test: `tests/Feature/ManagerBrief/RefreshManagerBriefCommandTest.php`

- [ ] **Step 12.1:** Failing test `it_writes_payload_to_cache_per_siding`. Run command; assert `Cache::has("manager-brief:{sidingId}:v1")` and the cached payload matches what `BuildManagerBrief` produced.
- [ ] **Step 12.2:** Failing test `it_iterates_all_active_sidings_when_no_siding_flag_given`.
- [ ] **Step 12.3:** Failing test `it_processes_only_the_given_siding_when_flag_set`.
- [ ] **Step 12.4:** Failing test `it_logs_warning_and_continues_when_one_siding_fails`. Force `BuildManagerBrief` to throw for one of two sidings; assert the other still completes.
- [ ] **Step 12.5:** Implement command. Use `php artisan make:command ManagerBrief/RefreshManagerBriefCommand --no-interaction`. Signature: `manager-brief:refresh {--siding=}`. Cache TTL: 90 minutes (in seconds). All logging at WARNING level.
- [ ] **Step 12.6:** Pint.

### Task 13: Schedule registration

**Files:**
- Modify: `routes/console.php`
- Test: `tests/Feature/ManagerBrief/ScheduleRegistrationTest.php`

- [ ] **Step 13.1:** Failing test `manager_brief_refresh_is_scheduled_hourly`. Assert `Schedule::events()` contains an event whose `command` includes `manager-brief:refresh` and whose expression matches `0 * * * *`.
- [ ] **Step 13.2:** Failing test `manager_brief_refresh_is_guarded_by_schema_check_on_fresh_db`. Boot the test app on a fresh sqlite without running migrations; the file should load without throwing. Reuse the pattern from the `loadrite:catchup` guard already in `routes/console.php`.
- [ ] **Step 13.3:** Add the schedule block, guarded by `Schema::hasTable('sidings')`, with `->hourly()->withoutOverlapping()->onOneServer()->name('manager-brief-refresh')`.
- [ ] **Step 13.4:** Pint.

### Task 14: Commit 2

- [ ] **Step 14.1:** Run:
  ```
  vendor/bin/pest tests/Unit/Ai/Agents/ManagerBriefAgentTest.php tests/Feature/ManagerBrief/BuildManagerBriefTest.php tests/Feature/ManagerBrief/RefreshManagerBriefCommandTest.php tests/Feature/ManagerBrief/ScheduleRegistrationTest.php
  ```
  Expected: all PASS.
- [ ] **Step 14.2:** Pint, larastan-on-touched.
- [ ] **Step 14.3:** Commit:
  ```
  git add app/Ai/Agents/ManagerBriefAgent.php app/Actions/BuildManagerBrief.php app/Console/Commands/RefreshManagerBriefCommand.php routes/console.php tests/Unit/Ai/Agents/ManagerBriefAgentTest.php tests/Feature/ManagerBrief/BuildManagerBriefTest.php tests/Feature/ManagerBrief/RefreshManagerBriefCommandTest.php tests/Feature/ManagerBrief/ScheduleRegistrationTest.php
  git commit -m "feat(manager-brief): AI agent + orchestrator + hourly refresh command"
  ```

---

## Commit 3 — Controller, Inertia Page, React Components

Goal: User-facing page rendering AI brief + all five live widgets. Permission gate enforced.

### Task 15: Permission seeder

**Files:**
- Create: `database/seeders/permissions/ManagerBriefPermissionsSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` (or whichever seeder calls permission seeders — grep `permissions/.*Seeder` first)

- [ ] **Step 15.1:** Find how other section permissions are seeded (grep `sections.rake_loader.view` to locate the existing seeder pattern).
- [ ] **Step 15.2:** Add `sections.manager_brief.view` and `sections.manager_brief.refresh`. Grant `view` to manager + ops-lead + super-admin roles; grant `refresh` to ops-lead + super-admin only.
- [ ] **Step 15.3:** Run the seeder in test env: `php artisan db:seed --class=Database\\Seeders\\Permissions\\ManagerBriefPermissionsSeeder --no-interaction`. Verify with `php artisan tinker --execute='echo \Spatie\Permission\Models\Permission::where("name","sections.manager_brief.view")->exists() ? "ok" : "missing";'`.
- [ ] **Step 15.4:** Pint.

### Task 16: Controller + route

**Files:**
- Create: `app/Http/Controllers/ManagerBriefController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ManagerBrief/ManagerBriefControllerTest.php`

- [ ] **Step 16.1:** Failing test `it_returns_403_without_view_permission`. Authenticate a user without the permission; expect 403.
- [ ] **Step 16.2:** Failing test `it_renders_inertia_page_with_cached_payload_when_cache_hit`. Pre-warm cache; assert response includes that exact payload and no Prism call is dispatched.
- [ ] **Step 16.3:** Failing test `it_dispatches_async_refresh_and_serves_placeholder_when_cache_miss`. `Bus::fake([RefreshManagerBriefCommand::class])` — wait, this is a console command, not a job. Instead use `Queue::fake()` and dispatch a queued job that wraps the command, OR call `Artisan::queue(...)` (which IS queueable). Pick one approach and stick with it; the cleanest is `Artisan::queue('manager-brief:refresh', ['--siding' => $sidingId])`.
- [ ] **Step 16.4:** Failing test `it_returns_aggregate_across_allowed_sidings_for_super_admin_without_siding_context`.
- [ ] **Step 16.5:** Failing test `it_serves_live_widgets_alongside_ai_brief`. Assert response has keys `actions`, `live_exposure`, `operator_scoreboard`, `pending_queue`, `trend_strip`, `generated_at`, `ai_status`.
- [ ] **Step 16.6:** Failing test `it_falls_back_to_dash_tile_when_one_widget_throws`. Stub one service to throw; expected that tile in the response is `null` and `widget_errors[]` includes its slug.
- [ ] **Step 16.7:** Implement `ManagerBriefController::index(Request $request): Response`. Resolve siding via `SidingContext`. Read cache. If miss/stale → `Artisan::queue('manager-brief:refresh', ['--siding' => $sidingId])` and serve placeholder. Assemble live widgets in try/catch per tile. Render `manager-brief/index` Inertia component.
- [ ] **Step 16.8:** Register the route in `routes/web.php` under the existing auth middleware group with the section-permission middleware: `Route::get('manager-brief', [ManagerBriefController::class, 'index'])->name('manager-brief.index')`.
- [ ] **Step 16.9:** Pint.

### Task 17: Page shell

**Files:**
- Create: `resources/js/pages/manager-brief/index.tsx`

- [ ] **Step 17.1:** Scaffold the page with `usePage()` props typed against the controller payload. Use the existing layout component (grep for what `/dashboard` uses — likely an `AppLayout` import).
- [ ] **Step 17.2:** Render `<ActionCardStack>` at top, then a 2×2 grid of `<LiveExposureTicker>`, `<OperatorScoreboard>`, `<PendingQueue>`, `<TrendStrip>`. `<StalenessBanner>` and `<RefreshNowButton>` at top above the stack.
- [ ] **Step 17.3:** Use Inertia v3 `router.reload` with the polling helper to refresh live widgets every 15 seconds. Reuse the polling pattern from `control-panel-v2/index.tsx` — do not invent a new one.
- [ ] **Step 17.4:** No tests for the page shell itself (covered by controller feature tests + smoke). One file, props rendered.

### Task 18: ActionCardStack

**Files:**
- Create: `resources/js/components/manager-brief/ActionCardStack.tsx`

- [ ] **Step 18.1:** Accepts `actions: ActionCard[]`, `aiStatus: 'ok'|'failed'`. Renders 5 cards or fewer.
- [ ] **Step 18.2:** Severity colour map: `critical` red, `high` orange, `medium` yellow, `low` green.
- [ ] **Step 18.3:** Each card is a button → calls `router.visit(action.deep_link)` on click. `data-pan="manager-brief-action-card"` with `data-pan-meta='{"severity":...}'`.
- [ ] **Step 18.4:** When `aiStatus === 'failed'` and `actions.length === 0`, render a single "AI insights unavailable" placeholder card.

### Task 19: LiveExposureTicker

**Files:**
- Create: `resources/js/components/manager-brief/LiveExposureTicker.tsx`

- [ ] **Step 19.1:** Accepts `totalRs: number | null`, `breakdown: { rake_number, rs }[]`.
- [ ] **Step 19.2:** Big number with `Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' })`. Animated count-up: use framer-motion's `useMotionValue` + `useTransform` (already in deps).
- [ ] **Step 19.3:** Tooltip / popover on hover shows breakdown by rake.
- [ ] **Step 19.4:** `totalRs === null` → render "—" + `data-pan="manager-brief-widget-failed"`.

### Task 20: OperatorScoreboard

**Files:**
- Create: `resources/js/components/manager-brief/OperatorScoreboard.tsx`

- [ ] **Step 20.1:** Accepts `top: Operator[]`, `bottom: Operator[]` where `Operator = { name, wagons, accuracy_pct, rs_caused }`.
- [ ] **Step 20.2:** Two columns: "Best this week" + "Needs coaching". Sorted by accuracy desc / asc.
- [ ] **Step 20.3:** Click on operator → navigate to `/dashboard#section=loader-overload&operator={name}` (use existing dashboard section deep-link convention; grep `?section=` in resources/js/pages/dashboard.tsx to confirm format).
- [ ] **Step 20.4:** Null prop → "—" + widget-failed pan tag.

### Task 21: PendingQueue

**Files:**
- Create: `resources/js/components/manager-brief/PendingQueue.tsx`

- [ ] **Step 21.1:** Two tile rows: "Overrides awaiting supervisor" (count + oldest age) and "Disputes ready to file" (count + estimated Rs recovery).
- [ ] **Step 21.2:** Click on either tile → navigates to existing page (overrides → `/loading-overrides` or whatever the existing route is; grep for it. Disputes → `/disputes` or equivalent).
- [ ] **Step 21.3:** Empty state ("0") rendered in muted style; do not hide the tile.

### Task 22: TrendStrip

**Files:**
- Create: `resources/js/components/manager-brief/TrendStrip.tsx`

- [ ] **Step 22.1:** Three small sparklines: penalty Rs, throughput MT, on-time dispatch %. Use the chart lib already in deps — grep for `recharts` or similar in `resources/js/components/` (do not add a new dep).
- [ ] **Step 22.2:** Each sparkline shows current week vs prior week with a delta chip (`+12%` green, `-7%` red).
- [ ] **Step 22.3:** Null → "—".

### Task 23: StalenessBanner

**Files:**
- Create: `resources/js/components/manager-brief/StalenessBanner.tsx`

- [ ] **Step 23.1:** Accepts `generatedAt: string`. Renders nothing when fresh (<60 min), gray "Updated 47 min ago" 60–120 min, amber chip ">2h ago" beyond that.
- [ ] **Step 23.2:** Uses `date-fns` `formatDistanceToNow` (already in deps — grep `from "date-fns"` to confirm).

### Task 24: RefreshNowButton

**Files:**
- Create: `resources/js/components/manager-brief/RefreshNowButton.tsx`

- [ ] **Step 24.1:** Accepts `canRefresh: boolean`. Hidden when false.
- [ ] **Step 24.2:** On click → POST to a new endpoint `Route::post('manager-brief/refresh', ...)` that dispatches the command async and returns 202. Add this route + controller method (`refresh()`) gated by `sections.manager_brief.refresh` permission.
- [ ] **Step 24.3:** Add a test in `ManagerBriefControllerTest`: `it_dispatches_refresh_and_returns_202_when_user_has_refresh_permission` + `it_returns_403_without_refresh_permission`.
- [ ] **Step 24.4:** Disabled + spinner while in-flight. `data-pan="manager-brief-refresh-now"`.

### Task 25: Commit 3

- [ ] **Step 25.1:** Run:
  ```
  vendor/bin/pest tests/Feature/ManagerBrief/ManagerBriefControllerTest.php
  ```
  Expected: all PASS.
- [ ] **Step 25.2:** `npm run build` to confirm React side compiles cleanly.
- [ ] **Step 25.3:** Pint + larastan-on-touched.
- [ ] **Step 25.4:** Commit:
  ```
  git add app/Http/Controllers/ManagerBriefController.php routes/web.php database/seeders/permissions/ManagerBriefPermissionsSeeder.php database/seeders/DatabaseSeeder.php resources/js/pages/manager-brief resources/js/components/manager-brief tests/Feature/ManagerBrief/ManagerBriefControllerTest.php
  git commit -m "feat(manager-brief): controller + Inertia page + React components"
  ```

---

## Commit 4 — Polish

Goal: Nav entry, Pan analytics, banners, documentation, manifest. No new logic.

### Task 26: Sidebar nav

**Files:**
- Modify: `resources/js/components/app-sidebar.tsx`

- [ ] **Step 26.1:** Add a "Manager Brief" entry near the top of the nav (above or near "Control Room"). `href: '/manager-brief'`, `dataPan: 'nav-manager-brief'`. Match the exact prop shape used for other entries (grep an existing entry).
- [ ] **Step 26.2:** Add it to the by-title ordering block too if one exists (grep `byTitle\\(` to confirm).

### Task 27: Pan analytics whitelist

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] **Step 27.1:** Locate the `configurePan()` method. Add the new names to the allowlist:
  - `nav-manager-brief`
  - `manager-brief-action-card`
  - `manager-brief-widget-failed`
  - `manager-brief-refresh-now`
  - Plus any `manager-brief-deeplink-*` (use the wildcard syntax already used by the project; grep `configurePan` to see).
- [ ] **Step 27.2:** No test — Pan whitelist registration is a single config change covered by manual smoke.

### Task 28: Documentation per CLAUDE.md MANDATORY pattern

**Files:**
- Create: `docs/developer/backend/actions/manager-brief.md`
- Create: `docs/developer/backend/services/manager-brief.md`
- Create: `docs/developer/frontend/pages/manager-brief.md`
- Modify: `docs/developer/api-reference/routes.md`
- Modify: `docs/.manifest.json`

- [ ] **Step 28.1:** Use the templates in `docs/.templates/`. One Action doc covering `BuildManagerBrief`, `CollectSignals`, `RankSignals`. One service doc covering the four services + the matcher. One page doc.
- [ ] **Step 28.2:** Add the new route entry to `docs/developer/api-reference/routes.md`.
- [ ] **Step 28.3:** Run `php artisan docs:sync` to update the manifest automatically. Then `php artisan docs:sync --check` to verify nothing undocumented.
- [ ] **Step 28.4:** Commit only the docs changes if `docs:sync` produced manifest diffs.

### Task 29: Commit 4

- [ ] **Step 29.1:** Run all manager-brief tests:
  ```
  vendor/bin/pest tests/Unit/Services/ForceMajeure tests/Unit/Services/ManagerBrief tests/Unit/Actions/ManagerBrief tests/Unit/Ai/Agents/ManagerBriefAgentTest.php tests/Feature/ManagerBrief
  ```
  Expected: all PASS.
- [ ] **Step 29.2:** Pint, larastan-on-touched, `npm run build`.
- [ ] **Step 29.3:** Commit:
  ```
  git add resources/js/components/app-sidebar.tsx app/Providers/AppServiceProvider.php docs/developer/backend/actions/manager-brief.md docs/developer/backend/services/manager-brief.md docs/developer/frontend/pages/manager-brief.md docs/developer/api-reference/routes.md docs/.manifest.json
  git commit -m "chore(manager-brief): sidebar nav, Pan analytics whitelist, documentation, manifest sync"
  ```

---

## End-to-End Smoke Checklist (Live Deploy)

After the PR is merged and pulled on production:

- [ ] **S.1:** SSH to live, `cd /home/sharproj/shar-ereports`, `git pull`, `composer dump-autoload -o`, `php artisan migrate --force`, `php artisan db:seed --class=Database\\Seeders\\Permissions\\ManagerBriefPermissionsSeeder --no-interaction --force`, `php artisan optimize:clear && php artisan optimize`. Restart Horizon (`sudo systemctl restart horizon`).
- [ ] **S.2:** Disk + log sanity: `df -h /` shows ≥10G free; `ls -lh storage/logs/laravel-$(date +%F).log` is reasonable.
- [ ] **S.3:** Force-refresh once: `php artisan manager-brief:refresh --siding=2`. Expect command to complete in <60s without errors. Inspect cache: `php artisan tinker --execute='echo strlen(json_encode(Cache::get("manager-brief:2:v1")));'` — non-zero.
- [ ] **S.4:** Hit `/manager-brief` as super-admin with siding 2 selected. Page renders within 2s. Five action cards visible (or "no urgent actions" placeholder). All four widgets render. Staleness banner shows "Updated <1 min ago".
- [ ] **S.5:** Click one action card → navigates to the deep-linked page successfully.
- [ ] **S.6:** Click an operator in the scoreboard → opens the loader-overload section of `/dashboard` filtered to that operator.
- [ ] **S.7:** Click "Disputes ready to file" tile → opens the disputes page.
- [ ] **S.8:** Log in as a user with only `sections.manager_brief.view` (no `.refresh`). Confirm refresh button is hidden.
- [ ] **S.9:** Log in as a user without `sections.manager_brief.view`. Confirm 403 on `/manager-brief`.
- [ ] **S.10:** Verify Pan analytics fires: open `/admin/analytics/product`, confirm `nav-manager-brief` and `manager-brief-action-card` show non-zero impressions/clicks.
- [ ] **S.11:** Verify schedule registered: `php artisan schedule:list | grep manager-brief` shows the hourly entry.
- [ ] **S.12:** Confirm no new log spam: `grep -c manager-brief storage/logs/laravel-$(date +%F).log` returns a small number; no `ERROR` entries naming `ManagerBrief*`.
- [ ] **S.13:** Twenty-four-hour follow-up: confirm hourly refresh ran (cache `generated_at` advances each hour); disk usage stable.

---

## Self-Review Summary

- **Spec coverage:** Every section (§1 surface, §2 components, §3 data flow, §4 errors/observability, §5 testing, §7 build sequence) has at least one task. §6 (testing strategy) is implemented across Tasks 2–13 + 16 + their test files.
- **Placeholders:** None left. Every step names a concrete file path, test name, or command.
- **Type consistency:** Cache key `manager-brief:{sidingId}:v1` used consistently. Permission strings `sections.manager_brief.view` / `sections.manager_brief.refresh` consistent. Command signature `manager-brief:refresh --siding=` consistent.
- **Scope:** Single focused plan. No bundled subsystems.
- **Ambiguity:** "Refresh now" mechanism made explicit (POST endpoint dispatching `Artisan::queue`). Permission boundaries explicit. Cache TTL explicit (90 min) vs staleness banner threshold (60 min staleness → gray, 120 min → amber).
