# Work Stream 1: Penalty Calculation Fix

**Date:** 2026-04-29  
**Approach:** B — Full Consolidation + Retroactive Fix  
**Status:** Approved for implementation

---

## Problem

Two competing demurrage systems exist in the codebase:

| | Old system | New system |
|---|---|---|
| Model | `Penalty` | `AppliedPenalty` |
| Formula | `excess_hours × weight_mt × rate_per_mt_hour` | `ceil(excess_hours) × rate` |
| Time window | `placement_time → completion` OR `loading_start → loading_end` | `loading_start → loading_end` |
| Per-wagon | No | No |
| Progressive rate | No | No |
| Used by charts | Yes | No |

Dashboard charts (`BuildPenaltyChartDataAction`) read from `Penalty`. Demurrage is written to `AppliedPenalty`. Result: charts show incomplete/wrong data. Both formulas are wrong per Indian Railways rules.

---

## Correct Formula (Indian Railways Standard)

```
window         = rake.placement_time → rake.loading_end_time
total_minutes  = diff in minutes
excess_minutes = max(0, total_minutes - SectionTimer['loading'].free_minutes)
excess_hours   = ceil(excess_minutes / 60)
rate           = progressive_rate(excess_hours, PenaltyType['DEM'].default_rate)
amount         = rate × rake.wagon_count
```

**Progressive multiplier (Indian Railways Board circular, May 2022):**

| Excess hours | Multiplier |
|---|---|
| ≤ 6 hrs | 1× base |
| > 6 – ≤ 12 hrs | 2× base |
| > 12 – ≤ 24 hrs | 3× base |
| > 24 – ≤ 48 hrs | 4× base |
| > 48 hrs | 6× base |

Base rate stored in `PenaltyType['DEM'].default_rate` (currently ₹225/wagon/hour per Railway Board).  
Multiplier tiers hardcoded in action — Railway Board law, not per-siding config.

**Meta snapshot per penalty record:**
```json
{
  "source": "demurrage",
  "placement_time": "ISO8601",
  "loading_end_time": "ISO8601",
  "total_minutes": 320,
  "free_minutes": 180,
  "excess_minutes": 140,
  "excess_hours": 3,
  "wagon_count": 59,
  "base_rate": 225,
  "rate_multiplier": 1,
  "recalculated_at": null,
  "correction_reason": null
}
```

---

## Data Consolidation

### Model changes

**`AppliedPenalty`** — single store for all demurrage going forward. No changes to schema.

**`Penalty` table** — add two nullable columns via migration:
- `migrated_at` (datetime, nullable)
- `migration_note` (string, nullable)

Old records are preserved with `migration_note = 'superseded_by_applied_penalties'`. Never deleted.

### Code deletions

- `PenaltyService::calculateDemurrage()` — deleted (wrong formula, wrong model)
- `PenaltyService::createDemurragePenalty()` — deleted (writes to wrong model)
- `PenaltyService::createManualPenalty()` — migrated: manual penalties now write to `AppliedPenalty` with `meta.source = 'manual'`
- `PenaltyService::getTotalPenalties()` — deleted (unused, reads wrong model)
- `PenaltyService::getPenaltySummary()` — deleted (unused, reads wrong model)

`PenaltyService` is unused outside its own file — delete the entire class after migrating `createManualPenalty`.

### `ApplyDemurragePenaltyAction` changes

- Time window: `placement_time → loading_end_time` (was `loading_start_time → loading_end_time`)
- Add `wagon_count` multiplier
- Add progressive rate via new private method `progressiveRate(int $excessHours, float $baseRate): float`
- Expand meta snapshot (see above)

### `BuildPenaltyChartDataAction` changes

Switch from `Penalty` model to `AppliedPenalty`:

| Old | New |
|---|---|
| `penalty_amount` | `amount` |
| `penalty_type` | join `penalty_types` on `penalty_type_id`, use `code` |
| `penalty_date` | `created_at` |
| siding join | `applied_penalties → rakes → sidings` |

Output shape unchanged — no frontend changes required.

---

## New Artisan Commands

### `penalties:recalculate`

**Options:**
- `--dry-run` — calculate and output diff CSV, write nothing to DB
- `--from=YYYY-MM-DD` — limit to rakes placed on or after date
- `--rake=ID` — single rake for testing

**Behaviour:**
1. Query all `Rake` records where `placement_time` and `loading_end_time` are not null
2. Recalculate demurrage with correct formula
3. Find existing `AppliedPenalty` with `meta->source = 'demurrage'` for that rake
4. **Dry run:** output CSV row: `rake_id, rake_number, siding, old_amount, new_amount, delta`
5. **Apply:** `updateOrCreate` the `AppliedPenalty` record, set `meta.recalculated_at = now()`, `meta.correction_reason = 'formula_fix_2026-04-29'`
6. Recalculate `RakeCharge` total after each update

**Review step:** always run `--dry-run` on staging, verify the diff report, then apply to production.

---

## Tests (Pest)

### 1. Unit — `ApplyDemurragePenaltyAction`
- Rake within free time → no penalty applied, returns `applied: false`
- Rake 2.5 hrs over (tier 1) → `ceil(2.5) = 3 hrs × 1× base × wagon_count`
- Rake 8 hrs over (tier 2) → `8 hrs × 2× base × wagon_count`
- Rake 15 hrs over (tier 3) → `15 hrs × 3× base × wagon_count`
- Missing `placement_time` → removes existing penalty, returns null
- Missing `loading_end_time` → removes existing penalty, returns null

### 2. Feature — `penalties:recalculate --dry-run`
- Seed 3 rakes with known old `AppliedPenalty` amounts
- Run command with `--dry-run`
- Assert CSV output contains correct old/new/delta values
- Assert no DB writes occurred

### 3. Feature — `BuildPenaltyChartDataAction`
- Seed `AppliedPenalty` records across sidings and months
- Assert `byType`, `bySiding`, `monthlyTrend` return correct aggregates
- Assert `Penalty` model records are NOT included in totals

---

## Implementation Order

1. Migration — add `migrated_at`, `migration_note` to `penalties` table
2. Fix `ApplyDemurragePenaltyAction` (formula, time window, wagon_count, progressive rate)
3. Update `BuildPenaltyChartDataAction` (switch to `AppliedPenalty`)
4. Migrate manual penalties path away from `PenaltyService`
5. Delete `PenaltyService::calculateDemurrage()` and `createDemurragePenalty()`
6. Create `penalties:recalculate` command
7. Write Pest tests
8. Run `penalties:recalculate --dry-run` on staging, review diff
9. Apply to production
