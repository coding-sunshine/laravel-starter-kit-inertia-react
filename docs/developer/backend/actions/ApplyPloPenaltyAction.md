# ApplyPloPenaltyAction

## Purpose

Persists a Penal Loading Overcharge (PLO) `AppliedPenalty` row for a rake/weighment pair and emits `AppliedPenaltyPersisted` so downstream reconciliation can pick it up. Wraps `CalculatePloPenaltyAction` for the underlying compute.

## Location

`app/Actions/ApplyPloPenaltyAction.php`

## Method Signature

```php
public function handle(Rake $rake, RakeWeighment $weighment): ?AppliedPenalty
```

Returns the persisted `AppliedPenalty` model, or `null` when there is no shortfall (in which case any prior PLO row is removed — see Idempotency).

## Dependencies

| Dependency | Type | Purpose |
|------------|------|---------|
| `$calculator` | `App\Actions\CalculatePloPenaltyAction` | Computes PLO shortfall and amount. |

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$rake` | `App\Models\Rake` | Rake the penalty applies to. |
| `$weighment` | `App\Models\RakeWeighment` | Weighment that triggered the calculation. |

## Trigger Points

Invoked from `App\Importers\RakeWeighmentPdfImporter::importForRake()` immediately after `ApplyWeighmentPenaltiesAction`. Each fresh weighment import re-evaluates PLO.

## Idempotency

`updateOrCreate` keyed by `(rake_id, penalty_type_id, meta->source = 'plo')`. Re-running for the same rake/weighment overwrites the prior PLO row in place. When the recomputed shortfall is zero (or otherwise non-applicable), any existing PLO row for the rake is removed so stale predictions don't linger.

## Side Effects

- Writes to `applied_penalties` (PLO row).
- Recalculates the parent `RakeCharge` (`charge_type=PENALTY`, `is_actual_charges=false`) total to reflect the new PLO amount.
- Emits `AppliedPenaltyPersisted` after the DB transaction commits, which feeds `ReconcilePenaltyHeadsJob` via `ReconcileOnAppliedPenalty`.
- All writes wrapped in `DB::transaction()`.

## Tests

`tests/Feature/Actions/ApplyPloPenaltyActionTest.php` — 4 cases (creates a PLO row when shortfall exists, updates the row on re-run, removes the row when shortfall drops to zero, emits `AppliedPenaltyPersisted` on success).

## Related Components

- **Action**: `CalculatePloPenaltyAction`
- **Event**: `App\Events\AppliedPenaltyPersisted`
- **Models**: `AppliedPenalty`, `PenaltyType`, `RakeCharge`, `Rake`, `RakeWeighment`
- **Importer**: `App\Importers\RakeWeighmentPdfImporter`

## Source Spec

`docs/superpowers/specs/2026-05-01-penalty-savings-program-design.md` §5.2.
