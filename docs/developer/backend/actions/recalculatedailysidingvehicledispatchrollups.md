# RecalculateDailySidingVehicleDispatchRollups

## Purpose

Deletes and rebuilds rows in **`daily_siding_vehicle_dispatch_rollups`** from **`siding_vehicle_dispatches`** using SQL aggregates (`GROUP BY` calendar **`issued_on`** as **`(issued_on)::date`** — same idea as the Vehicle Dispatch register filters — plus **`siding_id`** and numeric **`shift_number`** from **`1st`/`2nd`/`3rd`** labels).

Used by **`rollup:backfill-daily-siding-vehicle-dispatches`** (`--dry-run` calls **`preview()`** only; no delete/insert). Later admin UI “Recalculate” may call **`handle()`**.

## Location

`app/Actions/RecalculateDailySidingVehicleDispatchRollups.php`

## Method signatures

```php
public function preview(string $fromDate, string $toDate, ?int $sidingId = null): Collection

public function handle(string $fromDate, string $toDate, ?int $sidingId = null): int

public static function assertDatesWithinIndianFiscalYear(CarbonInterface $anchor, string $fromDate, string $toDate): void
```

## Dependencies

None.

## Related components

- Command: **`rollup:backfill-daily-siding-vehicle-dispatches`** (`App\Console\Commands\BackfillDailySidingVehicleDispatchRollupsCommand`)
- SQL snippets: **`App\Support\Rollups\SidingVehicleDispatchRollupSql`**
- Models: **`DailySidingVehicleDispatchRollup`**, **`SidingVehicleDispatch`**
