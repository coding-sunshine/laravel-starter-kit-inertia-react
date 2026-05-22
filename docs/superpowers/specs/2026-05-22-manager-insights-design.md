# Manager Insights — Design Spec

**Date:** 2026-05-22
**Author:** Brainstorming session (Hardik Shah + Claude)
**Status:** Draft for review
**Related work to reuse:** `GeneratePenaltyInsightsAction`, `GenerateDailyBriefingAction`, `PenaltyPredictionAgent`, `SidingRiskScoringAgent`, `PrismService`, `LoaderOverloadMetricsService`, `LiveMonitorDataBuilder`, `LoadingOverride`, `LoadriteDowntimeEvent`, `SidingPerformance`, existing `ExecutiveDashboardController` (8-section `/dashboard`).

---

## 1. Problem and Goal

### Problem
The existing `/dashboard` already exposes operational data across 8 sections (executive overview, siding overview, rake operations, penalty control, rake analytics, loader overload, power plant, penalties daily). In practice, managers do not open eight tabs every morning, and important signals — operator anomalies, rakes drifting toward penalty exposure, force-majeure recovery opportunities, silent Loadrite scales — get lost in the noise. Managers ask for "which loader caused overloading," "which rake might attract a penalty today," but the answers require cross-referencing several sections manually.

### Goal
A single page — **Manager Brief** — that synthesises existing data into a short, ranked list of action items a manager can review in under five minutes per morning, plus a small set of live supporting widgets (Rs exposure, operator scoreboard, pending queue, trend strip). AI ranks and narrates; deterministic aggregates back it up.

### Non-goals
- No new data sources. Uses what is already ingested (Loadrite events + system tables).
- No replacement for `/dashboard` or Filament admin. Both remain unchanged.
- No action-taking from the Brief in phase 1 — cards deep-link into existing pages where the manager can act (file dispute, ping supervisor, open rake).
- No mobile-specific layout in phase 1 — responsive, but tested only on desktop.

---

## 2. Scope and Surface

- **Route:** `Route::get('manager-brief', [ManagerBriefController::class, 'index'])` in `routes/web.php`, behind the standard auth + section-permission middleware.
- **Inertia page:** `resources/js/pages/manager-brief/index.tsx`. Single page, no sub-tabs.
- **Permission:** `sections.manager_brief.view` (read), `sections.manager_brief.refresh` (force regen — granted to super-admin + ops-lead roles). Registered via the seeders that already populate sections-permissions. The existing daily `permission:sync-routes` schedule only runs when `config('permission.route_based_enforcement')` is true, so do not rely on it — register the permissions explicitly in a seeder.
- **Sidebar nav:** new "Manager Brief" entry near the top of `resources/js/components/app-sidebar.tsx`. `data-pan="nav-manager-brief"`.
- **Siding scope:** respects current `SidingContext`. Super-admin without a siding selected sees an aggregate across all sidings the user can access; siding-scoped user sees only their siding.
- **Refresh model:**
  - AI-generated action cards regenerate every hour via the scheduled command `manager-brief:refresh`, cached per siding for 90 minutes (TTL deliberately wider than the regen interval so a stale-cache fallback exists).
  - Live widgets (Rs-at-stake ticker, operator scoreboard, pending queue) re-query on each page load and auto-refresh every 15 seconds via Inertia polling, matching the Control Room pattern.
  - Trend strip uses a 6-hour cache (slow-moving aggregates).

---

## 3. Components

### Backend

| File | Role |
|---|---|
| `app/Http/Controllers/ManagerBriefController.php` | `index()` resolves siding context, assembles AI brief (from cache) + live widget payload, renders Inertia page. |
| `app/Actions/BuildManagerBrief.php` | Orchestrator. Calls `CollectSignals`, `RankSignals`, `ManagerBriefAgent`. Returns the cacheable payload. |
| `app/Actions/ManagerBrief/CollectSignals.php` | Pulls raw signals. One method per signal type, each returning a typed DTO array. Knows only DB models — no AI imports. |
| `app/Actions/ManagerBrief/RankSignals.php` | Pre-AI numeric scoring (Rs impact × recency × actionability). Returns top 15 candidates. Pure function over DTOs. |
| `app/Ai/Agents/ManagerBriefAgent.php` | Prism agent. Input: top 15 signal DTOs + recent context. Output: 5 ranked action cards `{ severity, title, why, rs_at_stake, deep_link, deadline }`. Knows only DTOs + prompt — no DB queries. |
| `app/Services/ManagerBrief/LiveExposureCalculator.php` | Sums projected Rs across currently-loading rakes. Reuses `LiveMonitorDataBuilder` to identify active rakes. |
| `app/Services/ManagerBrief/OperatorScoreboard.php` | Top 5 + bottom 5 operators over a 7-day window — accuracy %, Rs caused. Reuses `LoaderOverloadMetricsService`. |
| `app/Services/ManagerBrief/PendingQueue.php` | Counts overrides awaiting supervisor review + disputes ready to file (force-majeure candidates not yet reconciled). |
| `app/Services/ManagerBrief/TrendStrip.php` | Penalty Rs week-over-week, throughput MT week-over-week, on-time dispatch %. Reuses `SidingPerformance`. |
| `app/Console/Commands/RefreshManagerBriefCommand.php` | `manager-brief:refresh --siding=?`. Hourly per-siding regen across **all active sidings** (not Loadrite-only — penalty/demurrage/override signals apply to manual sidings too). Writes to cache key `manager-brief:{siding_id}:v1`. `withoutOverlapping()` + `onOneServer()`. |
| `routes/console.php` | Hourly schedule for `manager-brief:refresh`. Guarded by `Schema::hasTable('sidings')` (no crash on fresh DB / test in-memory SQLite before migrations). |
| `routes/web.php` | `Route::get('manager-brief', ...)` with section permission middleware. |

### Frontend

| File | Role |
|---|---|
| `resources/js/pages/manager-brief/index.tsx` | Page shell. Inertia polling every 15 seconds for live widgets. |
| `resources/js/components/manager-brief/ActionCardStack.tsx` | Top-of-page 5 ranked action cards. Severity-coloured (red / orange / yellow / green). Each card has a deep-link CTA. |
| `resources/js/components/manager-brief/LiveExposureTicker.tsx` | Big Rs number with animated count-up. |
| `resources/js/components/manager-brief/OperatorScoreboard.tsx` | Top 5 / bottom 5 table — operator name, wagons, accuracy %, Rs caused. |
| `resources/js/components/manager-brief/PendingQueue.tsx` | Two tiles: overrides awaiting + disputes ready. Each links to the existing page. |
| `resources/js/components/manager-brief/TrendStrip.tsx` | Three sparklines: penalty Rs, throughput, on-time dispatch. |
| `resources/js/components/app-sidebar.tsx` | Add "Manager Brief" nav entry. |

### Boundaries
- `CollectSignals` knows only DB models. Testable as pure DB queries.
- `ManagerBriefAgent` knows only the signal DTO + prompt. Testable with a mocked Prism client.
- `BuildManagerBrief` is the only orchestrator that imports both.
- Live widgets never call AI. Pure aggregates. Cheap. Fast.
- Cache layer isolates AI cost — the page never hits the LLM on a user request; it serves cached or schedules an async refresh.

### Reuse
- `GeneratePenaltyInsightsAction` provides the Prism setup pattern + prompt-construction helper. `ManagerBriefAgent` reuses it.
- `LoaderOverloadMetricsService` already computes operator overload metrics. `OperatorScoreboard` calls it directly.
- `LiveMonitorDataBuilder` already knows currently-loading rakes. `LiveExposureCalculator` calls it.

---

## 4. Data Flow

### Hourly AI refresh (background)

```
schedule:run (hourly)
  └→ RefreshManagerBriefCommand
        ├→ foreach configured siding
        │     └→ BuildManagerBrief::handle(sidingId)
        │           ├→ CollectSignals → SignalDTO[] (raw, ~50-100 items)
        │           ├→ RankSignals    → top 15 by Rs × recency × actionability
        │           ├→ ManagerBriefAgent::synthesise(top15)
        │           │     └→ Prism → OpenRouter LLM → 5 ranked action cards
        │           └→ ManagerBriefPayload { actions, generated_at, siding_id, model_used, ai_status }
        └→ Cache::put("manager-brief:{sidingId}:v1", payload, 90 min)
```

### Page load (user request)

```
GET /manager-brief
  └→ ManagerBriefController::index
        ├→ resolve current siding via SidingContext
        ├→ AI brief
        │     ├→ Cache::get("manager-brief:{sidingId}:v1")
        │     ├→ if miss → dispatch RefreshManagerBriefCommand asynchronously, serve placeholder
        │     └→ if stale (>60 min) → dispatch async regen, serve current cache
        ├→ Live widgets (always fresh)
        │     ├→ LiveExposureCalculator → Rs at stake now
        │     ├→ OperatorScoreboard → top 5 + bottom 5 (7-day window)
        │     ├→ PendingQueue → counts + deep-links
        │     └→ TrendStrip → from SidingPerformance (6h cache)
        └→ Inertia::render('manager-brief/index', payload)
```

### Auto-refresh on open page

```
client (every 15 s)
  └→ Inertia poll → ManagerBriefController::index
        (returns fresh live widgets + same cached AI brief)
```

### Signal sources (CollectSignals breakdown)

| Signal | Source | Computed |
|---|---|---|
| Live overload exposure per loading rake | `wagon_loading` + `wagons.pcc_weight_mt` + `loadrite_events` | `SUM(loaded_quantity_mt - pcc_weight_mt) WHERE > 0 AND rake.state IN (loading, placed)` |
| Operator anomaly (overload-rate spike) | `loadrite_events` JOIN `wagon_loading` → `loader_operator_name` | this-week overload rate ÷ trailing 30-day baseline; flag if ≥ 2× |
| Force-majeure recovery candidates | `LoadriteDowntimeEvent` ↔ `AppliedPenalty` ↔ `PenaltyReconciliation` | Reuses the same matching logic as the existing `DisputesStitchForceMajeureCommand` — extract that logic into a shared service so both the command and `CollectSignals` consume it; do not duplicate. |
| Scale silence | `loadrite_events.scale_id` last event time | if an active rake exists at the siding AND the scale's last event is > 2 hours ago |
| Pending overrides | `LoadingOverride.supervisor_review_at IS NULL` | count + oldest |
| Underloading trend | `SidingPerformance` weekly aggregate | actual MT/rake vs target MT/rake |
| At-risk demurrage rake | `rakes.placement_time` + SLA config | hours-to-deadline below threshold |

---

## 5. Error Handling and Observability

### AI failures (Prism / OpenRouter)
- Wrap `ManagerBriefAgent::synthesise` in try/catch.
- On exception: log `Log::warning('manager-brief: AI synthesis failed', [exception, siding_id])`, return null.
- `BuildManagerBrief` if AI returns null still writes a payload with `actions=[]`, `ai_status='failed'`, `failed_reason=...`. Page banner: "AI insights temporarily unavailable — showing live data only".
- No retry loop in the command — next hour's run is the retry.

### Rate-limit / quota
- `PrismService` already has provider rotation. Reuse.
- Hourly command + per-siding cache implies roughly 24 LLM calls per day per siding. Tiny.
- If OpenRouter returns 429 → caught, logged, fallback as above.

### Stale cache surfacing
- Always include `generated_at` in payload.
- Frontend shows relative time: "Updated 47 min ago" — gray; "> 2 h ago" → amber chip.
- "Refresh now" button visible only to users with `sections.manager_brief.refresh`. Dispatches `RefreshManagerBriefCommand` asynchronously.

### Live widget failures
- Each widget wrapped in try/catch in the controller. On error the tile shows "—" + `data-pan="manager-brief-widget-failed"` so failures are visible in Pan analytics.
- Errors logged at `warning` level (not `error`) to avoid log-volume regressions.

### Data correctness guardrails
- Live exposure tile: skip rakes where `loaded_quantity_mt = 0` AND no Loadrite events (avoids ghost rakes).
- Operator scoreboard: only include operators with ≥ 10 wagons in the window (statistical noise filter).
- Force-majeure candidates: only if Loadrite downtime ≥ 30 minutes AND the penalty type matches the downtime cause.

### Observability hooks
- `data-pan="manager-brief-action-card"` per AI card (track which severity gets clicked).
- `data-pan="manager-brief-deeplink-{slug}"` per CTA — which actions drive engagement.
- All new Pan names added to `AppServiceProvider::configurePan()` allowlist.
- Activity log entry whenever a user with `sections.manager_brief.refresh` force-refreshes.

---

## 6. Testing

### Unit (Pest, no DB)

| Test file | Asserts |
|---|---|
| `tests/Unit/Actions/ManagerBrief/RankSignalsTest.php` | Numeric scoring formula correctly orders signals by `Rs × recency × actionability`. Empty list returns empty. Tied scores stable-sort by recency. |
| `tests/Unit/Ai/Agents/ManagerBriefAgentTest.php` | Prism client mocked. Given a fixed signal DTO array → asserts prompt contains all signals + agent returns 5 typed cards. AI exception → returns null + logs warning. |
| `tests/Unit/Services/ManagerBrief/LiveExposureCalculatorTest.php` | Sum logic — ignores `loaded_quantity_mt=0` ghost rakes; excludes `weight_source=weighbridge`. |
| `tests/Unit/Services/ManagerBrief/OperatorScoreboardTest.php` | Operators with < 10 wagons excluded. Top/bottom ranked by accuracy %. Empty input safe. |

### Feature (Pest, `RefreshDatabase` + factories)

| Test file | Asserts |
|---|---|
| `tests/Feature/ManagerBrief/ManagerBriefControllerTest.php` | Permission gate (403 without `sections.manager_brief.view`); super-admin without siding → aggregate across allowed sidings; siding-scoped user → only their siding; cache hit returns cached payload (no Prism call); cache miss schedules async refresh + serves placeholder; response shape matches Inertia contract. |
| `tests/Feature/ManagerBrief/RefreshManagerBriefCommandTest.php` | Iterates configured sidings; `withoutOverlapping` honoured (two parallel invocations → one runs); cache write keyed `manager-brief:{sidingId}:v1`; Prism failure → payload written with `ai_status=failed` + empty actions. |
| `tests/Feature/ManagerBrief/CollectSignalsTest.php` | Seed fixture rakes/events/overrides → each signal method returns expected DTOs. Coverage per signal type. |
| `tests/Feature/ManagerBrief/ScheduleRegistrationTest.php` | `manager-brief:refresh` registered hourly. Guarded by `Schema::hasTable` (no crash on fresh DB). |

### Browser smoke (manual on live, no Playwright in CI)
- Page loads under 2 seconds with cached brief.
- All five widgets render even if AI brief is empty.
- Deep-link CTAs open the correct pages.
- "Refresh now" works for the permitted role only.

### Out-of-scope tests
- Real LLM output (non-deterministic + costs money).
- Pixel layout (covered by manual smoke + Pan).
- Performance of underlying services already covered by existing tests (`LoaderOverloadMetricsService`, etc.).

### Test-data approach
- Factories used: `Rake`, `Wagon`, `WagonLoading`, `LoadriteEvent`, `LoadingOverride`, `LoadriteDowntimeEvent`, `AppliedPenalty`, `SidingPerformance`, `LoaderOperator` — all exist already.
- Helper trait `Tests\Helpers\SeedsManagerBriefFixture` — one-call setup for "siding with 3 loading rakes, 1 pending override, 1 dispute candidate, 1 silent scale".

### CI gates
- Pint passes.
- Larastan max — no NEW errors (pre-existing not blocking).
- All new tests pass: `vendor/bin/pest tests/Unit/Actions/ManagerBrief tests/Unit/Ai/Agents/ManagerBriefAgentTest.php tests/Unit/Services/ManagerBrief tests/Feature/ManagerBrief`.

---

## 7. Build Sequence

Single PR, four reviewable commits:

1. **Backend foundations** — `CollectSignals`, `RankSignals`, `LiveExposureCalculator`, `OperatorScoreboard`, `PendingQueue`, `TrendStrip` + their unit/feature tests. No AI, no routes, no UI. Each service tested in isolation against seeded fixtures.
2. **AI layer + caching** — `ManagerBriefAgent`, `BuildManagerBrief`, `RefreshManagerBriefCommand`, schedule registration, cache wiring. Mocked-Prism tests. Live LLM smoke from CLI on staging.
3. **Controller + Inertia page** — `ManagerBriefController`, route, permission, page shell + all five React components. Full feature test of the controller.
4. **Polish** — sidebar nav, Pan analytics, banners (stale cache / AI failure), "refresh now" button, documentation updates per `docs/.manifest.json` workflow.

Phase 2 (separate spec) can add action-taking (file dispute from the card, ping supervisor) and mobile layout polish.

---

## 8. Open Risks and Mitigations

| Risk | Mitigation |
|---|---|
| AI produces low-quality or hallucinated cards | Pre-AI ranking ensures only the strongest 15 numeric signals are fed in. Card schema is strict — agent must fill `rs_at_stake` + `deep_link`; cards failing schema are dropped. |
| Hourly Prism cost grows with siding count | Per-siding cache + 90-min TTL; only sidings with active rakes are refreshed (skip-empty guard in command). |
| Page becomes "yet another dashboard" managers ignore | Pan analytics on every card and CTA — if engagement is low after 2 weeks, the design is wrong and revisited. Success metric: ≥ 60 % of manager logins open the page; ≥ 30 % of action cards get clicked. |
| Brief content drifts from operator vocabulary | Prompt uses operator names + rake serial numbers verbatim (no rephrasing) so cards match what operators say on the floor. |
| Recurring disk-full incidents (production infrastructure) | Out of scope for this feature — flagged separately to the host/sysadmin. |

---

## 9. Success Criteria

- A manager can identify the top action of the day in under 30 seconds.
- 80 % of severe overload events surface as a card within 60 minutes of detection.
- The page is the entry point for at least one penalty dispute filing per week (proves CTAs convert).
- No new disk-volume regression (logs/cache stay within budget — covered by infra hygiene, not this feature).
- Live widgets render correctly when the AI layer is fully unavailable.
