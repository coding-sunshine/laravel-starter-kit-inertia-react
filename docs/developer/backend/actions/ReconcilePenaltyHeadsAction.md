# ReconcilePenaltyHeadsAction

## Purpose

Joins predicted (`applied_penalties`) and billed (`rr_penalty_snapshots`) per `penalty_code` for a single rake and writes the result into one or more `penalty_reconciliations` rows. Idempotent — safe to run multiple times for the same rake. Returns a `ReconciliationOutcome` DTO summarising created, updated, and dispute-candidate rows.

## Location

`app/Actions/ReconcilePenaltyHeadsAction.php`

## Method Signature

```php
public function handle(Rake $rake): ReconciliationOutcome
```

## Dependencies

None (no constructor; pulls data via Eloquent queries).

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$rake` | `App\Models\Rake` | Rake whose predicted and billed penalty heads should be reconciled. |

## Return Value

`App\Actions\ReconciliationOutcome` DTO containing:

- `createdCodes: array<string>` — penalty codes inserted into `penalty_reconciliations`.
- `updatedCodes: array<string>` — penalty codes whose reconciliation row was updated.
- `disputeCandidateCodes: array<string>` — codes flagged for review (see Dispute rule below).

## Trigger Points

The Action is invoked indirectly through `ReconcilePenaltyHeadsJob` (Horizon `penalties` queue, dispatched with `WithoutOverlapping` middleware keyed by `rake_id` so concurrent dispatches for the same rake serialise).

Two events fan into this job:

| Event | Listener | Source |
|-------|----------|--------|
| `AppliedPenaltyPersisted` | `App\Listeners\ReconcileOnAppliedPenalty` | Emitted by Actions that write to `applied_penalties` (e.g. `ApplyWeighmentPenaltiesAction`, `ApplyPloPenaltyAction`). |
| `RrPenaltySnapshotsImported` | `App\Listeners\ReconcileOnRrImport` | Emitted after RR PDF import refreshes `rr_penalty_snapshots`. |

## Side Effects

- Writes/updates rows in `penalty_reconciliations` (one per `(rake_id, penalty_code)`).
- Sets `dispute_candidate = true` per umbrella spec §5.1 rule:
  - `billed_amount > 0 AND predicted_amount IS NULL`, **OR**
  - `billed_amount > predicted_amount × 1.15` (15% overshoot).
- Stamps `reconciled_at = now()` on every touched row.
- Wraps work in `DB::transaction()`.

## Idempotency

Uses `updateOrCreate` keyed by `(rake_id, penalty_code)`. Re-running for the same rake recomputes amounts and rewrites only changed rows.

## Tests

`tests/Unit/Actions/ReconcilePenaltyHeadsActionTest.php` — 6 cases covering predicted-only, billed-only, matched within tolerance, billed-without-predicted dispute, >15% overshoot dispute, and idempotency on re-run.

## Related Models

- `App\Models\PenaltyReconciliation`
- `App\Models\AppliedPenalty`
- `App\Models\RrPenaltySnapshot`
- `App\Models\Rake`

## Source Spec

`docs/superpowers/specs/2026-05-01-penalty-savings-program-design.md` §5.1.
