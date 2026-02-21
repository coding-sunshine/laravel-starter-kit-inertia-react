# BuildPenaltyChartDataAction

## Purpose

Builds chart aggregates (by type, by siding, monthly trend) from the same filtered query as the penalties DataTable. Powers the dynamic charts on the penalties index page that update when filters change.

## Location

`app/Actions/BuildPenaltyChartDataAction.php`

## Method Signature

```php
public function handle(Request $request): array
```

## Dependencies

None (no constructor dependencies)

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$request` | `Illuminate\Http\Request` | Current request; filters from `filter[penalty_type]`, `filter[penalty_status]`, etc. are applied |

## Return Value

Array with three keys:

- **byType**: `array<int, array{name: string, value: float, count: int}>` — penalty type, summed amount, count
- **bySiding**: `array<int, array{name: string, total: float}>` — top 10 sidings by amount
- **monthlyTrend**: `array<int, array{month: string, total: float, count: int}>` — monthly totals (12 months when no date filter, or filtered range)

When no date filter is applied, data is constrained to the last 12 months for consistency with the analytics page.

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

- **Controller**: `PenaltyController@index`
- **Route**: `penalties.index` (GET /penalties)
- **Page**: `resources/js/pages/penalties/index.tsx` (PieChart, BarChart, AreaChart)
- **DataTable**: `PenaltyDataTable` (shared base query and filters)
- **Model**: `Penalty`

## Notes

- Uses `QueryBuilder::for(PenaltyDataTable::tableBaseQuery())` with `tableAllowedFilters()` so charts reflect the same filters as the table
- Aggregations use `getQuery()->get()` to avoid Eloquent model hydration with partial selects
- Supports MySQL, PostgreSQL, and SQLite for date extraction in monthly trend
