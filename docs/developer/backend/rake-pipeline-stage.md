# Rake pipeline stage (derived)

## Purpose

`App\Models\Rake::pipelineStage(): RakeLifecycleStage` returns the operational pipeline stage of a rake. Stage is **derived from process timestamps** rather than the persisted `state` column, because `state` carries values from two competing vocabularies (see [ADR-003](../../architecture/ADRs/ADR-003-canonical-rake-state.md)) and is unreliable for live ops.

Use this accessor anywhere you need to ask "what stage is this rake at?" for dashboards, alerts, queue-depth analytics, or operational UI. Do not query `state` directly for this purpose.

## Stages

`App\Enums\RakeLifecycleStage`:

| Case | Value | Meaning |
|---|---|---|
| `AwaitingPlacement` | `awaiting_placement` | Indented; rake not yet placed at siding (or just placed, loading not begun). |
| `Loading` | `loading` | Loading in progress (`loading_start_time` set, `loading_end_time` null). |
| `AwaitingDispatch` | `awaiting_dispatch` | Loading complete; awaiting clearance / weighment / RR / dispatch. |
| `Dispatched` | `dispatched` | Departed siding; no RR yet. |
| `InTransit` | `in_transit` | Used only for legacy state-string mapping; modern flow goes Dispatched → Delivered once `rr_actual_date` is set. |
| `Delivered` | `delivered` | Arrived destination (RR issued or legacy `completed`/`closed`). |

## Resolution priority

1. `dispatch_time` set + `rr_actual_date` set → `Delivered`
2. `dispatch_time` set → `Dispatched`
3. `loading_end_time` set → `AwaitingDispatch`
4. `loading_start_time` set → `Loading`
5. `placement_time` set → `AwaitingPlacement`
6. Else → fall back to legacy mapping of the `state` string (`completed`/`closed`/`delivered` → `Delivered`, `in_transit` → `InTransit`, `departed`/`dispatched` → `Dispatched`, `staged`/`loading_completed`/`ready_for_dispatch`/`guard_approved`/`weighment_completed`/`rr_generated` → `AwaitingDispatch`, `loading` → `Loading`, anything else → `AwaitingPlacement`).

Timestamps always take precedence over the legacy state string when both are present.

## Consumers

- `App\Http\Controllers\Dashboard\ExecutiveDashboardController::buildActiveRakePipeline` — buckets rakes into the four-column "Active rake pipeline" widget.

When adding a new consumer, prefer the enum over raw strings:

```php
if ($rake->pipelineStage() === RakeLifecycleStage::AwaitingDispatch) {
    // …
}
```

## Tests

- `tests/Unit/Models/RakePipelineStageTest.php` — every timestamp combination + every legacy state mapping.
- `tests/Feature/Dashboard/ActiveRakePipelineTest.php` — end-to-end bucket grouping via `buildActiveRakePipeline`.
