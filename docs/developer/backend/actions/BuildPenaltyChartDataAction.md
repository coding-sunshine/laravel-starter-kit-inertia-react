# BuildPenaltyChartDataAction

## Purpose

Builds chart aggregates (by type, by siding, monthly trend) for the penalties index page. Aggregates directly from `rr_penalty_snapshots` (the RR-parsed penalty pipeline), not the legacy `Penalty`/`AppliedPenalty` models.

## Location

`app/Actions/BuildPenaltyChartDataAction.php`

## Method Signature

```php
public function handle(Request $request): array
```

## Dependencies

None (no constructor dependencies)

## Parameters

| Parameter  | Type                      | Description                                                                            |
| ---------- | ------------------------- | -------------------------------------------------------------------------------------- |
| `$request` | `Illuminate\Http\Request` | Current request; `filter[penalty_date]` is applied via `App\Support\PenaltyDateFilter` |

## Return Value

Array with three keys:

- **byType**: `array<int, array{name: string, value: float, count: int}>` — penalty code, summed amount, count
- **bySiding**: `array<int, array{name: string, total: float}>` — top 10 sidings by amount
- **monthlyTrend**: `array<int, array{month: string, total: float, count: int}>` — monthly totals (12 months when no date filter, or the filtered range)

## Query

`baseQuery()` builds `rr_penalty_snapshots` left-joined to `rr_documents` (for `rr_received_date`), inner-joined to `rakes` → `sidings`, and left-joined to `penalty_types` (for the code lookup):

```php
DB::table('rr_penalty_snapshots')
    ->leftJoin('rr_documents', 'rr_penalty_snapshots.rr_document_id', '=', 'rr_documents.id')
    ->join('rakes', 'rr_penalty_snapshots.rake_id', '=', 'rakes.id')
    ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
    ->leftJoin('penalty_types', 'rr_penalty_snapshots.penalty_code', '=', 'penalty_types.code');
```

- With no `filter[penalty_date]`: constrained to `PenaltyDateFilter::DATE_EXPR >= now()->startOfMonth()->subMonthsNoOverflow(11)` (rolling 12-month window, calendar-month aligned).
- With `filter[penalty_date]`: delegates to `PenaltyDateFilter::apply()` (day-granular; see that class for operator syntax: `eq`, `gte`, `lte`, `between`, `before`, `after`).

## Usage Examples

### From Controller

```php
$chartData = resolve(BuildPenaltyChartDataAction::class)->handle($request);

return Inertia::render('penalties/index', [
    'tableData' => PenaltyDataTable::makeTable($request),
    'chartData' => $chartData,
    // ...
]);
```

## Related Components

- **Controller**: `PenaltyController@index` (`App\Http\Controllers\RailwayReceipts\PenaltyController`)
- **Route**: `penalties.index` (GET /penalties)
- **Page**: `resources/js/pages/penalties/index.tsx` (PieChart, BarChart, AreaChart)
- **DataTable**: `PenaltyDataTable` (also reads `rr_penalty_snapshots`, computes `penalty_date = COALESCE(rr_received_date, loading_date, created_at)`)
- **Support**: `App\Support\PenaltyDateFilter` (shared `filter[penalty_date]` handling — also used by `PenaltyDataTable` and `PenaltyController`)
- **Models**: `RrPenaltySnapshot`, `RrDocument`, `Rake`, `Siding`, `PenaltyType`

## Notes

- Aggregations use `Illuminate\Database\Query\Builder` (raw query builder), not Eloquent — no model hydration.
- Monthly trend supports MySQL, PostgreSQL, and SQLite for date extraction, matching `PenaltyDateFilter::DATE_EXPR`.
- The legacy `Penalty` model / `PenaltyDataTable`'s old QueryBuilder-based filters (`penalty_type`, `penalty_status`, etc.) are no longer read here.
