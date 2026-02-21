# AggregateSidingPerformance

## Purpose

Aggregates daily performance metrics per siding into the `siding_performance` table. Runs nightly via `rrmcs:aggregate-performance`.

## Location

`app/Actions/AggregateSidingPerformance.php`

## Method Signature

```php
public function handle(?CarbonImmutable $date = null): int
public function aggregateForSiding(int $sidingId, CarbonImmutable $date): SidingPerformance
```

## Dependencies

None (no constructor dependencies).

## Parameters

### `handle()`

| Parameter | Type | Description |
|-----------|------|-------------|
| `$date` | `?CarbonImmutable` | Date to aggregate (defaults to yesterday) |

### `aggregateForSiding()`

| Parameter | Type | Description |
|-----------|------|-------------|
| `$sidingId` | `int` | Siding ID to aggregate |
| `$date` | `CarbonImmutable` | Date to aggregate |

## Return Value

- `handle()` — number of siding records upserted
- `aggregateForSiding()` — the upserted `SidingPerformance` model

## Metrics Collected

For each siding on the given date:

- **Rakes processed** — rakes that completed loading
- **Total penalty amount** — sum of penalty amounts
- **Penalty incidents** — count of penalties
- **Average demurrage hours** — mean demurrage for rakes with demurrage > 0
- **Overload incidents** — wagons flagged as overloaded
- **Closing stock** — coal stock closing balance in MT

## Usage Examples

### From Scheduled Command

```bash
php artisan rrmcs:aggregate-performance
php artisan rrmcs:aggregate-performance --from=2025-01-01 --to=2025-01-31
```

## Related Components

- **Command**: `AggregatePerformanceCommand` (`rrmcs:aggregate-performance`)
- **Model**: `SidingPerformance`, `Rake`, `Penalty`, `Wagon`, `CoalStock`, `Siding`
