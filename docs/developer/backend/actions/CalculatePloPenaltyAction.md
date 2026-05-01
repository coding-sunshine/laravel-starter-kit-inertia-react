# CalculatePloPenaltyAction

## Purpose

Provisional Penal Loading Overcharge (PLO) calculator. Pure compute — no DB writes — returning a `PloPenaltyResult` DTO.

> **Provisional formula.** Per umbrella spec §5.2 the formula is expected to be rewritten once the calibration corpus confirms the actual IR mechanism. The Action's input/output contract stays stable so callers (notably `ApplyPloPenaltyAction`) do not need to change.

## Location

`app/Actions/CalculatePloPenaltyAction.php`

## Method Signature

```php
public function handle(Rake $rake, RakeWeighment $weighment): PloPenaltyResult
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$rake` | `App\Models\Rake` | Rake under evaluation. `commodity_grade` is read for threshold lookup. |
| `$weighment` | `App\Models\RakeWeighment` | Weighment record. Loads `rakeWagonWeighments.wagon` for per-wagon CC capacity and net weight. |

## Calculation

```
chargeable_weight_mt = Σ (rake_wagon_weighments.cc_capacity_mt × utilisation_threshold)
shortfall_mt         = max(0, chargeable_weight_mt − Σ rake_wagon_weighments.net_weight_mt)
amount               = shortfall_mt × PenaltyType['PLO'].default_rate
```

`utilisation_threshold` is sourced from `commodity_utilisation_thresholds` matched on `commodity_grade`, falling back to **0.95** when no row matches.

## Return Value

`App\Actions\PloPenaltyResult` DTO with `rakeId`, `chargeableWeightMt`, `totalLoadedWeightMt`, `shortfallMt`, `rate`, and `amount`. Helper `isApplicable()` returns `true` when `amount > 0`.

## Side Effects

None. Pure function.

## Tests

`tests/Unit/Actions/CalculatePloPenaltyActionTest.php` — 3 cases (shortfall present, no shortfall, fallback threshold when commodity grade is missing).

## Related Models

- `App\Models\Rake`
- `App\Models\RakeWeighment`
- `App\Models\CommodityUtilisationThreshold`
- `App\Models\PenaltyType`

## Source Spec

`docs/superpowers/specs/2026-05-01-penalty-savings-program-design.md` §5.2.
