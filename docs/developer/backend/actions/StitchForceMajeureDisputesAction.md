# StitchForceMajeureDisputesAction

**Path:** `app/Actions/StitchForceMajeureDisputesAction.php`

**Purpose:** Cross-reference Loadrite `loadrite_downtime_events` with DEM reconciliations in `penalty_reconciliations` and flag `dispute_candidate = true` when total overlap with a rake's loading window exceeds 15 minutes.

## Invocation

| Caller | Frequency |
|---|---|
| `disputes:stitch-force-majeure` artisan command | Nightly via scheduler (03:00) |
| Manual replay | Engineer-triggered when downtime corpus is back-filled |

## Inputs

`handle(int $lookbackDays = 30): ForceMajeureStitchOutcome`

## Outputs

`ForceMajeureStitchOutcome` with `candidates`, `rakesScanned`, `downtimeEventsConsidered`.

## Side effects

For each flagged reconciliation:
- `dispute_candidate = true`
- `notes->force_majeure` JSON populated with `overlap_minutes` and `reason` (the asserted contract).
- `notes->force_majeure_detail` JSON populated with `reasons_all`, `event_ids`, `stitched_at` (extended metadata).

## Idempotency

Reconciliations already carrying `notes->force_majeure` are skipped via `whereNull('notes->force_majeure')`.

## Telemetry

Structured log at level `info`, tag `penalty.force_majeure.stitched`. Fields: `rakes_scanned`, `events_considered`, `candidates_flagged`.

## Related

- Job: `App\Jobs\FetchLoadriteDowntimeJob` (populates the downtime cache)
- Command: `disputes:stitch-force-majeure` (triggers this action)
- Source spec: `docs/superpowers/specs/2026-04-30-loadrite-api-integration-design.md`
