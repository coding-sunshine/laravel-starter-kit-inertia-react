# ADR-003: Canonical Rake State Model — Deferred Refactor

## Status

Proposed (deferred). Sets direction; no implementation in this ADR's PR.

## Context

The `rakes.state` column is a free-text string with no enum/cast. As of 2026-05, two competing vocabularies are written to it by different parts of the codebase:

**Vocabulary A — operational lifecycle** (used by `App\Actions\CreateRake`, `App\Policies\RakePolicy`, dashboards, jobs, AI/MCP tools, several `where('state', …)` queries):

- `pending`
- `loading`
- `staged`
- `departed`
- `in_transit`
- `delivered`

**Vocabulary B — workflow-step states** (used by `App\Services\Rakes\RakeStateService` and the services it coordinates: `GuardInspectionService`, `WeighmentService`, `RrService`, `TxrService`, `WagonLoadingService`, `ProcessGuardInspection`):

- `pending`
- `txr_in_progress`, `txr_completed`
- `loading`
- `loading_completed`
- `guard_approved`, `guard_rejected`
- `weighment_completed`
- `rr_generated`
- `closed`

**Stragglers** also seen in code: `placed`, `completed`, `approved`, `rejected`, `ready_for_dispatch`, `unfit`. Excel imports force `delivered` or `completed` regardless of pipeline progress.

Live database (production snapshot 2026-05-02) holds: `pending` (410), `loading_completed` (1), `closed` (3), `completed` (16,901), plus `null`. None of the lifecycle values (`loading`, `staged`, `departed`, `in_transit`, `delivered`) appear at scale, despite being the values most readers compare against. The Executive dashboard's "Active rake pipeline" was bucketing on `str_contains` of `state` and silently fell through to a single column for everything in production — see ADR-003 sibling fix in `App\Models\Rake::pipelineStage()`.

Approximately 30 files write or read `rakes.state` across both vocabularies. Adding a backed-enum cast on `Rake::$state` today would throw `ValueError` on first encounter of any unmapped legacy value, with blast radius spanning guard inspection, RR generation, weighment, demurrage alerts, MCP tools, and AI chat. This is too risky to bundle with a dashboard fix.

## Decision

1. **Short term (this PR, ADR-003 sibling)**: introduce `App\Enums\RakeLifecycleStage` and `App\Models\Rake::pipelineStage()`. Stage is **derived** from process timestamps (`placement_time`, `loading_start_time`, `loading_end_time`, `dispatch_time`, `rr_actual_date`) with a fallback mapping for legacy `state` strings. The persisted `state` column is **not** touched, cast, or migrated. Dashboards and any other reader that needs the operational pipeline view consume `pipelineStage()`.

2. **Medium term (this ADR, deferred to its own dedicated PR)**: unify the two vocabularies into a single canonical state enum, with one writer (`RakeStateService`) and one source of truth. Phased migration:

   **Phase 1 — Define canonical model.** Adopt the workflow-step vocabulary (Vocabulary B) as the canonical persisted state, since it carries more operational signal and is already the active state machine. Lifecycle vocabulary (Vocabulary A) becomes a **derived view** computed by `pipelineStage()`. Rationale: workflow-step states map 1-to-1 to user-visible operational events; lifecycle states are coarser and can always be derived from the finer-grained timeline.

   **Phase 2 — Codify.** Introduce `App\Enums\RakeState` (backed enum, vocabulary B) and a `RakeStateMachine` service with explicit allowed transitions. Define a legacy-value mapping table for one-shot backfill: `delivered` → `closed`, `completed` → `closed`, `staged` → `loading_completed`, `departed` → `closed` (or a new `departed` case if the operational distinction matters).

   **Phase 3 — Audit writers.** Replace every `'state' => '...'` literal across the ~20 writers with `RakeState::Foo->value` (or `->value` via the enum). Replace every `where('state', '...')` and `whereIn('state', [...])` reader with the same. Excel/historical importers stay as-is at first, but flagged as a follow-up; their values become `RakeState::Closed`.

   **Phase 4 — Backfill migration.** Run the legacy-mapping migration on a copy first; verify counts of each canonical state match expectations. Add a release-time job that scans for any unmapped value and aborts the deploy.

   **Phase 5 — Add the cast.** Once production data is clean, add `protected $casts = ['state' => RakeState::class]`. From this point forward the cast guarantees the invariant.

   **Phase 6 — Reduce derived stage to a function of state alone, where possible.** Once the canonical state machine is the source of truth, `pipelineStage()` can collapse to `RakeState::pipelineStage()` — a pure mapping with no timestamp dependence. Timestamps remain for observability/SLA but stop being the source of truth.

3. **Tooling that must be settled before Phase 4 ships**:
   - Decide: do we keep `staged` / `departed` as distinct visible operational events, or is `loading_completed` + `dispatch_time` enough? (Answer drives whether we add new enum cases or fold them into `closed`.)
   - Decide: are Excel-imported historical rakes excluded from the canonical state machine entirely (frozen as `closed`)? Default: yes.
   - Decide: should `state` allow `null`, or default to `pending` at insert? Default: drop nullability and default to `pending` at the same time the cast is added.
   - Verify: every state-writing code path is covered by either a feature test or a dedicated unit test before Phase 5.

## Consequences

### Easier

- The dashboard's pipeline view becomes correct **today** without waiting on the canonical-state refactor (Phase 1 only).
- Every consumer that needs the operational pipeline can use `pipelineStage()` and stop touching the unreliable `state` column.
- Phases 2–6 can ship independently and incrementally; each phase is self-contained and reversible.
- Future state additions (e.g., new workflow steps) only require an enum case + transition rule, not a 30-file scavenger hunt.

### Harder

- For the duration of Phases 1–4, the codebase carries two ways of asking "what stage is this rake in" — the new `pipelineStage()` and the old `state` queries. Reviewers need to know which to use. Mitigation: PR template line item; eventually `state` queries get replaced one-by-one as Phase 3 progresses.
- The legacy-value mapping is opinionated. Anyone reviewing Phase 4 needs domain knowledge to confirm `delivered`/`completed`/`staged` map correctly. Mitigation: review with operations stakeholder before running the migration.

### Open questions to resolve before Phase 2

- Does Vocabulary A's `in_transit` add value over `closed` + a non-null `dispatch_time` and null `rr_actual_date`? If not, we can drop it from the canonical enum.
- Excel imports currently force a single value; do we need a separate "imported" provenance flag so the canonical state machine can ignore them safely?
