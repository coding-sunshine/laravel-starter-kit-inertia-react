# Penalty Savings Program — Umbrella Design

**Date:** 2026-05-01
**Status:** Draft — pending user review
**Approach:** Three-stage delivery, no feature flags, calibration-gated cut-over
**Builds on:** `2026-04-29-penalty-fix-design.md`, `2026-04-30-loadrite-api-integration-design.md`

---

## 1. Context

Railway penalties are the largest controllable cost leak in the rake operation. Historical RR-doc backfill (`rr_penalty_snapshots`, 731 records) shows a `~₹1.34 Cr` actual penalty pool already billed by Indian Railways across the corpus loaded into the system today. Distribution by head:

| Code | Count | Total ₹ | % of pool | Predictively tracked today? |
|---|---|---|---|---|
| **DEM** demurrage | 728 | ₹1,14,42,523 | **85%** | Action exists, near-zero output today (1/17,280 rakes had `dispatch_time`); penalty-fix spec corrects formula |
| **PLO** penal loading overcharge | 145 | ₹20,20,000 | 15% | Penalty type defined, **no calculator implemented** |
| ENHC engine hire | 10 | ₹2,04,960 | 1.5% | Defined, not implemented |
| POLA aggregate overload | 29 | ₹69,034 | 0.5% | Implemented (`ApplyWeighmentPenaltiesAction`) |
| POL1 individual overload | 53 | ₹53,612 | 0.4% | Implemented |

Loading-time distribution across 9,082 rakes that have both `placement_time` and `loading_end_time`:

| Bucket | Rakes |
|---|---|
| 0–3h (within free time) | 8,752 (96%) |
| 3h+ (penalty zone) | 330 (3.6%) |

Per siding:

| Siding | Rakes | With placement_time | With loading_end_time | Over-free-time count | Avg load (min) on overruns |
|---|---|---|---|---|---|
| Pakur | 7,758 | **0** | 76 | — | — |
| Dumka | 7,412 | 7,165 | 7,170 | 319 | 948 (~16h) |
| Kurwa | 2,110 | 1,942 | 1,944 | 11 | 1,567 |

**Loadrite hardware:** `loadrite_settings` row exists for Dumka (`siding_id=2`) with valid access token through Apr 2027. Pakur and Kurwa not configured.

## 2. Diagnosis

1. **POL1/POLA gets most code attention; DEM and PLO carry 99% of the rupee impact.** Predictive coverage is inverted from real-world cost weight.
2. **`ApplyDemurragePenaltyAction` is being fixed under the prior penalty-fix spec.** Once that ships, demurrage prediction becomes meaningful. This program then *consumes* those predictions for reconciliation and dispute, and adds the missing PLO calculator.
3. **Pakur is a 7,758-rake data hole.** No `placement_time` captured → no demurrage prediction possible at the source siding regardless of formula correctness.
4. **`rr_penalty_snapshots` (billed) and `applied_penalties` (predicted) sit in parallel with no reconciliation layer.** The variance signal is the foundation of the dispute case and of operational accountability — currently invisible.
5. **Loadrite ingestion is designed (prior spec) but not yet wired to penalty/dispute outputs.** Once payloads flow, they become first-class evidence for both real-time alerts (Stage 2) and post-fact disputes (Stage 3).

## 3. Goal & Success Metrics

**Program goal:** reduce total penalty bleed (avoidance + recovery) by ≥30% in Y1 against the ₹1.34 Cr historical baseline.

**Per-stage success metrics:**

| Stage | Metric | Target |
|---|---|---|
| 1 | Reconciliation coverage | ≥ 95% of rakes with billed RR have a reconciliation row per head |
| 1 | PLO predictive coverage | PLO predicted for 100% of rakes with completed wagon weighment |
| 1 | Pakur placement capture rate | ≥ 80% of new Pakur rakes have `placement_time` within 24h of placement |
| 1 | DEM prediction accuracy | predicted DEM amount within ±10% of billed DEM amount across the calibration corpus |
| 2 | Real-time alert coverage | ≥ 80% of POL1/POLA-billed wagons at Loadrite-active sidings had a Yellow/Orange/Red alert before final weighment |
| 2 | Loadrite uptime | ≥ 99% successful poll cycles per active siding per week |
| 3 | Dispute file rate | ≥ 60% of `dispute_candidate=true` reconciliations filed within 30 days |
| 3 | Recovery rate | ≥ 25% by ₹ recovered within 6 months of filing |

## 4. Architectural Principles

1. **One umbrella program, three independently shippable stages.** Each stage can deliver standalone value and is released sequentially. This document is the umbrella; each stage gets a focused implementation plan via the writing-plans skill.
2. **Activation via data presence, not feature flags.** No Pennant flags. Loadrite is enabled by inserting a row into `loadrite_settings`. Demurrage rules — handled per the penalty-fix spec — apply universally once that ships. Dispute workflow is gated by per-user `DisputePolicy`, not by org-level flags.
3. **Direct cut-over, calibration-gated.** Stage-1 cannot merge until the calibration corpus passes ±10% accuracy on real RR-doc samples. Rollback path is `git revert` + redeploy; all migrations stay additive so rollback is data-safe.
4. **Compute-then-apply split everywhere.** Every penalty calculator and dispute drafter exposes a `calculate()` returning a result struct, separate from `apply()` that persists. Enables dry-run via artisan and end-to-end testing without DB writes.
5. **All AI calls via Prism with structured output.** No free-form AI strings reach the database or legal output without human review.
6. **External I/O never crashes domain actions.** Loadrite, weather, Prism wrapped in try/catch; failures degrade gracefully.
7. **Idempotent ingestion.** Unique keys on every external-data table (`loadrite_payloads`, `penalty_reconciliations`, `disputes`).

---

## 5. Stage 1 — Plug the 85% Leak (4–6 weeks)

**Goal:** make the predicted-vs-billed gap visible per head, complete the missing PLO calculator, and close the Pakur data hole so the demurrage fix from the prior spec actually has data to work on.

**Depends on:** the penalty-fix spec (`2026-04-29-penalty-fix-design.md`) being merged.

### 5.1 Predicted-vs-Billed reconciliation

**New table `penalty_reconciliations`:**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `rake_id` | bigint FK | indexed |
| `penalty_code` | varchar(16) | DEM / PLO / POL1 / POLA / ENHC |
| `predicted_amount` | decimal(12,2) | nullable |
| `billed_amount` | decimal(12,2) | nullable |
| `variance` | decimal(12,2) | generated/derived |
| `variance_pct` | decimal(6,2) | generated/derived |
| `dispute_candidate` | boolean | default false |
| `notes` | json | freeform |
| `reconciled_at` | datetime | |
| `created_at` / `updated_at` | timestamps | |

Unique index: `(rake_id, penalty_code)`.

**`App\Actions\ReconcilePenaltyHeadsAction`** — input: `Rake`. Output: array of `PenaltyReconciliation`. Idempotent via `updateOrCreate` on the unique key.

**Trigger points:**
- `RrPenaltySnapshotsImported` event (after RR-doc ingestion completes for a rake) → reconcile.
- `AppliedPenaltyPersisted` event (new — fired by the post-penalty-fix `ApplyDemurragePenaltyAction` and by `ApplyWeighmentPenaltiesAction` and `CalculatePloPenaltyAction`) → reconcile.

**`dispute_candidate` rule:**
- `billed_amount > 0 AND predicted_amount IS NULL` (penalty we never saw coming) → candidate
- `billed_amount > predicted_amount × 1.15` (>15% billing surprise) → candidate
- `billed_amount > 0 AND ApplyDemurragePenaltyAction's meta.excess_minutes <= 0` (railway charged, our clock says no excess) → candidate

**Filament admin page `Penalty Reconciliation`** — list + drill-down per rake. Inertia siding-manager view shows the reconciliation row inline on the rake show page.

### 5.2 PLO calculator

**New action `App\Actions\CalculatePloPenaltyAction`** — pure calculation, returns a result struct. Triggered by the same `RakeWeighmentFinalised` event that drives `ApplyWeighmentPenaltiesAction`.

**Provisional rule pending calibration:** PLO appears to be charged when total loaded weight is below the chargeable weight. Historical bills (145 records, avg ₹13,931, range ₹5,000–75,000) suggest a banded structure rather than pure freight differential. The actual IR mechanism is one of: (a) shortfall MT × fixed rate, (b) freight charged on booked weight when actual is below tolerance, or (c) per-wagon flat penalty above a threshold. **The calibration step must reverse-engineer the rule from `rr_penalty_snapshots` PLO bills before this calculator is finalised.**

**Provisional calculation (subject to calibration outcome):**
```
chargeable_weight_mt    = sum(wagon.cc_mt × utilisation_threshold)  -- threshold per commodity from a new lookup
total_loaded_weight_mt  = sum(rake_wagon_weighments.net_weight_mt)
shortfall_mt            = max(0, chargeable_weight_mt - total_loaded_weight_mt)
plo_amount              = shortfall_mt × PenaltyType['PLO'].default_rate
```

If calibration shows the rule is freight-differential or per-wagon-flat, the formula is rewritten before the calculator is merged. The Action interface (`calculate(Rake): PloPenaltyResult`) and the persistence path stay the same regardless of which formula calibration confirms.

**`App\Actions\ApplyPloPenaltyAction`** — persists to `applied_penalties` with `meta.source = 'plo'`, breakdown captured (chargeable_weight_mt, total_loaded_weight_mt, shortfall_mt, rate). Hooks `RakeCharge` recalculation same as existing weighment penalty path.

**Commodity utilisation thresholds** (lookup): a new `commodity_utilisation_thresholds` table seeded from current PLO billing patterns. Reverse-engineer the ratio from the 145 historical PLO bills if possible; otherwise default to 0.95 and let ops adjust via Filament resource.

### 5.3 Pakur data-capture

**Problem:** 7,758 Pakur rakes / zero `placement_time`. The penalty-fix spec correctly computes demurrage from `placement_time → loading_end_time`, but Pakur supervisors aren't entering placement.

**Solution (default — confirm before build):** mobile-friendly Inertia React route `/sidings/{siding}/quick-placement`.

- Single-screen form: rake number autocomplete (active-rakes endpoint) + "Placed" / "Released" buttons, server stamps `now()` (with optional manual override + reason).
- Authentication: existing siding-user roles. Optional offline submission queue if network drops (uses Inertia v3 deferred-prop pattern).
- After-the-fact backfill artisan command `pakur:backfill-placement` accepting CSV (rake_no, placement_at, source) for any historical Pakur rakes the supervisor can recover from logbooks.

**Stretch (Stage 2):** WhatsApp Cloud API bot lets supervisors text `RAKE 12345 PLACED` to a number; same write path. Out of Stage 1 unless capture rate stays below target.

### 5.4 Cross-cutting (Stage 1)

- **Events:** `AppliedPenaltyPersisted`, `RrPenaltySnapshotsImported` are introduced if not already present.
- **Jobs:** `ReconcilePenaltyHeadsJob` on Horizon `penalties` queue.
- **Filament:** new resources `PenaltyReconciliationResource`, `CommodityUtilisationThresholdResource`. Read-only widget on existing `Penalty Recovery Dashboard` showing top dispute candidates.
- **Tests (Pest):**
  - Unit `CalculatePloPenaltyActionTest` — under-loaded / on-target / over-loaded / mixed wagons cases.
  - Unit `ReconcilePenaltyHeadsActionTest` — match / predicted-only / billed-only / variance-tier cases.
  - Feature `PakurQuickPlacementTest` — siding-user can submit placement, server stamps timestamp, rake updated.
  - Calibration `RrReconciliationCalibrationTest` — fixture corpus `tests/Fixtures/RailwayBills/*.json` of real RR docs; predicted vs billed within ±10%; CI gate for merge.
- **Observability:** structured logs on every reconciliation write; Pail-friendly tags `penalty.reconciled`, `pakur.placement.captured`.
- **Documentation:** action docs under `docs/developer/backend/actions/`; user-guide entry for the Pakur quick-placement flow under `docs/user-guide/`. Manifest updated; `php artisan docs:sync --check` passes.

### 5.5 Open inputs for Stage 1

- **Pakur capture channel** — confirm Inertia mobile page is acceptable (default), and whether WhatsApp follow-up is wanted in Stage 2.
- **Calibration corpus** — need 5–10 representative RR PDFs covering DEM and PLO bills. Engineer can pull from `rr_documents` table; user to flag any specific cases that should be in the calibration set.
- **PLO base rate** — confirm `penalty_types.PLO.default_rate = ₹100/MT` matches the latest IR circular. If shifted, seed update in same migration.
- **Commodity utilisation thresholds** — confirm whether a single 0.95 default is acceptable across coal grades or whether a per-grade table is needed at launch.

---

## 6. Stage 2 — Real-Time Prevention (+3 weeks)

**Goal:** turn on the Loadrite ingestion path designed in `2026-04-30-loadrite-api-integration-design.md` and concretise the channels left as TBD in that spec.

**Depends on:** Stage 1 merged + the Loadrite-API-integration spec implemented.

### 6.1 What the prior Loadrite spec already covers (do not duplicate)

- `LoadriteConnector` Saloon stack and `loadrite_settings` schema.
- `PollLoadriteJob` self-scheduling at 30s with Redis lock + cursor.
- `SyncLoadriteWeightJob` writing to `WagonLoading.loadrite_weight_mt` with the precedence rules.
- `EvaluateOverloadAlertJob` with 90% warning, 100%+ critical, 5-min Redis debounce.
- `loadrite:start-polling` watchdog, `loadrite:store-token` interactive command.
- Three-channel notification (`Reverb`, database, SMS/WhatsApp/push) — channels other than Reverb and database listed as "to be wired up".

### 6.2 What this stage adds on top

1. **WhatsApp Cloud API channel** — concrete delivery for the SMS/WhatsApp/push slot in `LoadriteOverloadNotification::via()`.
   - New Saloon integration `App\Http\Integrations\WhatsAppCloud\WhatsAppConnector` and `SendTemplateMessageRequest`.
   - Per-supervisor opt-in via a new `users.whatsapp_opt_in` boolean and a verified `whatsapp_number` column.
   - Templates registered in Meta Cloud API console; template names stored in `config/penalty-alerts.php`.
   - Failure path logs and falls back to in-app notification only — never blocks the overload alert dispatch.
2. **Reconciliation evidence feed** — when an `EvaluateOverloadAlertJob` fires Yellow/Orange/Red, a record is written to a new `loadrite_alert_events` table linking `(loadrite_payload_id, wagon_id, rake_id, level, fired_at)`. The Stage-3 evidence-pack generator includes these events as proof for any subsequent POL1/POLA dispute on the same wagon.
3. **Pakur/Kurwa onboarding** — pure data-onboarding; no code changes. Insert a `loadrite_settings` row with the site's tokens, run `loadrite:store-token`, watchdog picks up automatically per the prior spec's design.
4. **Per-organisation isolation check** — confirm the polling watchdog respects multi-tenant boundaries (every `loadrite_settings` row lives under a siding which lives under an organisation; ensure jobs scope by tenant when emitting notifications).

### 6.3 Tests (Stage 2 additions)

- Unit `WhatsAppCloudConnectorTest` with Saloon `MockClient`.
- Feature `LoadriteOverloadNotificationTest` covering the WhatsApp channel for opted-in vs opted-out users.
- Feature `LoadriteAlertEventEvidenceTest` asserting alerts are persisted for later evidence-pack consumption.

### 6.4 Open inputs for Stage 2

- **WhatsApp gateway provider** — Meta Cloud API direct (default, cheapest) vs an existing aggregator the org already uses.
- **Pakur Loadrite hardware status** — installed, partially installed, or not. Determines whether onboarding is a row insert or a hardware procurement decision out of scope for this program.
- **Alert recipient list** — siding manager only, or also CHC/control-room shifts. Per-siding `alert_recipients` config likely needed; not in prior spec.

---

## 7. Stage 3 — AI Dispute Factory (+3–4 weeks)

**Goal:** turn `dispute_candidate=true` reconciliations into filed disputes with strong evidence packs and AI-drafted letters; track outcomes; compound the corpus over time.

**Depends on:** Stages 1 and 2 merged.

### 7.1 Domain model

**New table `disputes`:**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `penalty_reconciliation_id` | bigint FK | |
| `rake_id` | bigint FK | |
| `penalty_code` | varchar(16) | |
| `disputed_amount` | decimal(12,2) | |
| `status` | enum | `draft` / `under_review` / `filed` / `responded` / `won` / `partially_won` / `lost` / `withdrawn` |
| `dispute_angle` | varchar(64) | per-head angle taxonomy |
| `filed_at` | datetime | nullable |
| `response_at` | datetime | nullable |
| `resolved_at` | datetime | nullable |
| `recovered_amount` | decimal(12,2) | nullable |
| `evidence_pack_path` | varchar(512) | nullable |
| `letter_path` | varchar(512) | nullable |
| `meta` | json | drafted_by, reviewer_id, prompt_version, model |
| `created_by` / `updated_by` | userstamps | per project convention |
| `created_at` / `updated_at` | timestamps | |

Existing `RecommendDisputeAction` is extended to write a `disputes` row (`status = draft`) for high-confidence reconciliations rather than producing only a recommendation string.

### 7.2 Evidence-pack generator

**`App\Actions\BuildPenaltyEvidencePackAction`** — input: `PenaltyReconciliation`. Output: structured bundle + PDF attachment stored in `storage/app/private/disputes/{rake_id}/{penalty_code}/evidence.pdf`.

**Sections assembled (each is a separate sub-action so unit tests can pin one section at a time):**
- Rake timeline (placement, loading start, loading end, weighment, dispatch, RR issuance) from existing models.
- Weighment proofs — `RakeWeighment` and `RakeWagonWeighment` rows with calibration certificate reference if present.
- Loadrite payload trail (Stage-2 `loadrite_payloads` + `loadrite_alert_events`) when the rake's siding had Loadrite active during loading.
- Source RR document — extracted text and PDF reference from `rr_documents`.
- Section-timer actuals vs the free-time rule applied (`section_timers` snapshot at the time of computation).
- Weather window — new Saloon connector `App\Http\Integrations\Weather\WeatherConnector` with provider TBD (default IMD if available, OpenWeather fallback). Cached in a new `weather_observations` table keyed by `(siding_id, observed_at)` to avoid repeated calls.

**PDF rendering** — reuse the project's existing PDF mechanism. If none is present, add `barryvdh/laravel-dompdf` in this stage's first PR (single, reviewable dependency add per project convention; flagged in the open inputs).

### 7.3 AI-drafted dispute letters

**`App\Actions\DraftDisputeLetterAction`** — Prism with a structured-output schema. Per-head prompt templates in `resources/prompts/dispute/{code}.txt`.

**Schema fields:**
- `letter_subject`, `letter_body` (markdown), `dispute_angle` (enum), `legal_refs` (array of strings), `evidence_refs` (array of evidence-section identifiers), `confidence_score` (0–1), `risk_flags` (array).

**Per-head angle taxonomy (initial seed; expanded as outcomes feedback in):**
- **DEM** — clock-disputed, force-majeure, double-counting, station-side detention, free-time mis-applied.
- **PLO** — commodity mis-classification, declared-weight correctness, density/moisture adjustment.
- **POL1 / POLA** — calibration certificate currency, in-motion vs static reading variance, drift correction not applied, Loadrite payload trail contradicts railway weighbridge.
- **ENHC** — shunting requested by railway, signal failure, locomotive substitution.

**Output rendered to letter PDF (same renderer as evidence pack).** Letter starts in `status = draft`, requires manual `under_review → filed` transition by an authorised user (policy-gated).

### 7.4 Lifecycle workflow

- **Filament `DisputeResource`** — kanban view (columns by status) + table view + detail page with tabbed evidence preview.
- **State transitions** enforced via guarded Action methods (`FileDisputeAction`, `RecordDisputeResponseAction`, `ResolveDisputeAction`). State machine rules encoded in those actions, not in the model — keeps validation reusable from artisan/tests.
- **Notifications:** `DisputeFiledNotification`, `DisputeResponseReceivedNotification`, `DisputeResolvedNotification` to ops + finance.
- **Authorisation** — new `App\Policies\DisputePolicy` with abilities `view`, `draft`, `review`, `file`, `resolve`. Only finance/legal-tagged users can transition to `filed` or `resolved`.

### 7.5 Recovery analytics

- New Filament dashboard cluster `Penalty Recovery`:
  - Disputes by status (funnel)
  - Win rate by head, by siding, by dispute_angle
  - ₹ recovered MTD/YTD vs ₹ filed
  - Average cycle time per state
  - Outstanding response queue with SLA timer
- Metrics computed by `App\Actions\BuildDisputeMetricsAction`, cached 5 minutes.

### 7.6 Compounding loop

- Won/lost outcomes labelled in the corpus → quarterly human-curated prompt update.
- Angles that the railway has rejected ≥3 times get demoted automatically in the next draft selection (recorded in a `dispute_angle_outcomes` aggregation table refreshed nightly).

### 7.7 Tests (Stage 3)

- Unit `BuildPenaltyEvidencePackActionTest` — fixture-driven; asserts every required section is present and gracefully handles missing optional sections (no Loadrite, no weather).
- Unit `DraftDisputeLetterActionTest` — Prism `fake()` driver; asserts schema enforcement and per-head template selection.
- Feature `DisputeLifecycleTest` — end-to-end reconciliation → draft → under_review → filed → responded → resolved with metric assertions.
- Policy `DisputePolicyTest` — only finance/legal seats can advance to `filed`.

### 7.8 Open inputs for Stage 3

- **PDF renderer** — does the project already have one for dispatch reports / RR previews? If yes, reuse; if no, add `barryvdh/laravel-dompdf` in PR 1 of this stage.
- **Weather data source** — IMD, OpenWeather, or omit force-majeure angle entirely. Trade-off: API cost vs missing strong DEM dispute lever.
- **Authorised dispute signers** — provide the user/role list to seed `DisputePolicy`.
- **Existing dispute history** — if past filed disputes and outcomes exist outside the system, ingesting them into `disputes` on day one accelerates corpus quality. Format and source TBD.

---

## 8. Cross-Cutting (Program-Wide)

### 8.1 Rollout strategy (no feature flags)

- **Calibration-gated merge** for Stage 1 — `RrReconciliationCalibrationTest` is a hard CI gate.
- **Activation via data presence** — Loadrite settings row + `is_active=true` (Stage 2); `disputes` available program-wide on Stage 3 merge with per-user `DisputePolicy`.
- **Watch-window protocol** — for the 7 days after each stage merges, a daily reconciliation/alert-effectiveness diff is emailed to super-admins. Variance beyond calibration threshold triggers a hot-fix branch, not a flag flip.
- **Rollback path** — `git revert` + redeploy. All migrations stay additive. New tables sit empty after revert; no data is lost.
- **Per-organisation isolation** — verified at every entry point: jobs, notifications, dashboards. Multi-tenant boundaries respected throughout.

### 8.2 Error handling principles

- External I/O (Loadrite, weather, Prism, WhatsApp) wrapped in try/catch; failures degrade gracefully without crashing domain actions.
- Idempotency on all ingestion via unique keys.
- Compute-then-apply split — every calculator returns a result struct; persistence is a separate Action.
- Validation at the boundary only (form requests, command validation). Internal code trusts framework guarantees.
- No silent swallow — every catch logs structured context and surfaces to the existing `Alert` model or super-admin notification path.

### 8.3 Testing strategy

- Pest, feature-heavy. Unit tests for value objects and pure calculators; feature tests for full action flows with factories.
- Calibration corpus `tests/Fixtures/RailwayBills/*.json` is the merge gate for Stage 1 and the regression guard for all later stages.
- Saloon `MockClient` for Loadrite, WhatsApp, Weather — captured fixtures from real responses where possible.
- Prism `fake()` for AI calls — assertion on prompt structure and schema, never on free-form text.
- No mocking the database in integration tests.
- Coverage gates: `App\Actions\*Penalty*` ≥ 85% line coverage; new Domain code ≥ 90%.

### 8.4 Observability

- Structured logs on every penalty compute, reconciliation write, alert dispatch, dispute state change. Pail-friendly tags: `penalty.computed`, `penalty.reconciled`, `loadrite.alert.fired`, `dispute.state.changed`.
- Filament admin dashboards covering exposure ₹ by head/siding/month, prediction-vs-billing variance %, dispute funnel, alert effectiveness.
- Audit trail on every dispute state transition (project default activity log).
- Prism call telemetry (token cost, latency, model) into the AI cost dashboard.

### 8.5 Security & privacy

- Loadrite tokens encrypted at rest (already in place per prior spec).
- Dispute letters and evidence packs in `storage/app/private/`; access via Filament policy-gated download URLs only.
- AI prompts redact org-identifying internal IDs where not legally required.
- WhatsApp opt-in flag per supervisor; no default-on broadcast.
- Userstamps on every new model table.

### 8.6 Documentation deliverables (per project rules)

- One developer doc per new Action under `docs/developer/backend/actions/`.
- One user-guide doc for the dispute kanban under `docs/user-guide/penalties/disputes.md`.
- One user-guide doc for the Pakur quick-placement flow.
- ADR `docs/architecture/ADRs/0XX-penalty-savings-program.md` recording the three-stage decomposition and the demurrage-first prioritisation.
- `docs/.manifest.json` updated; `php artisan docs:sync --check` is part of the pre-merge checklist.

---

## 9. Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| No flag-based rollback | Medium | High if a stage regresses | Calibration corpus as merge gate; watch-window protocol; additive migrations; compute-then-apply enables dry-run |
| Calibration corpus too small | Medium | High | Require ≥10 RR-doc samples covering DEM and PLO before Stage-1 merge |
| Pakur capture rate stays low | Medium | Medium (Stage-1 metric miss) | WhatsApp follow-up channel ready in Stage 2; backfill artisan command for historical recovery |
| Loadrite API contract changes | Low | Medium | Saloon abstraction localises change; tests pinned to fixtures; alert path still works without Loadrite |
| Prism prompt drift across model versions | Medium | Medium | Pin model in `config/prism.php`; quarterly human curation of corpus; structured-output schema is the contract |
| Weather API costs balloon | Low | Low | Cache `weather_observations` table; opt-out path documented |
| Dispute letters create legal liability | Low | High | Manual `under_review → filed` gate with `DisputePolicy`; never auto-file; legal review of templates before launch |

## 10. Out of Scope (recorded explicitly)

- IoT/RFID at siding entries (capex, future)
- Automatic fine-tuning of Prism models (manual quarterly curation only)
- Locomotive and signal-log ingestion (depends on railway data feed availability)
- Migration of POL1/POLA prediction to a Loadrite-only path (weighment stays authoritative; Loadrite is corroborating evidence)
- Penalty heads that don't appear in the historical corpus (wharfage, sick wagon, BPC, RR amendment, late cancellation, environmental) — defer until they actually show up on bills

## 11. Open Inputs (consolidated, awaiting user response)

**Stage 1**
- Confirm Pakur capture channel = mobile Inertia page (default).
- Confirm calibration corpus path: pull 5–10 RR PDFs from `rr_documents`, or supply a curated set.
- Confirm `penalty_types.PLO.default_rate` against latest IR circular.
- Confirm commodity utilisation thresholds — single 0.95 default acceptable, or per-grade table required at launch.

**Stage 2**
- WhatsApp gateway choice — Meta Cloud API direct (default) or aggregator?
- Pakur Loadrite hardware status — installed / partial / not / unknown.
- Per-siding alert recipient list.

**Stage 3**
- PDF renderer reuse vs add `barryvdh/laravel-dompdf`.
- Weather data source — IMD / OpenWeather / omit.
- Seed list for `DisputePolicy` authorised signers (finance/legal seats).
- Existing dispute history outside the system — yes/no, and ingest format if yes.

## 12. Implementation Order Summary

| Stage | PR sequence (high level) |
|---|---|
| 1 | (a) `penalty_reconciliations` migration + Action + Filament resource → (b) PLO calculator + Apply action + tests → (c) Pakur quick-placement Inertia route + backfill command → (d) calibration corpus + CI gate |
| 2 | (a) WhatsApp Saloon connector + `users.whatsapp_opt_in` migration → (b) `loadrite_alert_events` table + listener → (c) Pakur/Kurwa Loadrite onboarding (data only) |
| 3 | (a) `disputes` migration + extend `RecommendDisputeAction` → (b) evidence-pack generator + Weather connector + PDF renderer decision → (c) `DraftDisputeLetterAction` + per-head templates → (d) Filament `DisputeResource` + lifecycle actions → (e) recovery analytics dashboard + compounding-loop aggregation |

Each PR is targeted to be reviewable independently. The writing-plans skill produces a granular plan per stage when each stage starts.

---

**End of umbrella design.** Stage-1 plan to be drafted via the writing-plans skill once user approves this spec.
