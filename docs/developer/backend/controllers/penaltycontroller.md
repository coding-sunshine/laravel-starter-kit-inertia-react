# PenaltyController

## Purpose

Serves the penalties list/analytics pages. Reads from `rr_penalty_snapshots` (RR-parsed penalty pipeline) joined to `rr_documents`/`rakes`/`sidings`/`penalty_types`, scoped to the requesting user's accessible sidings and honoring `filter[penalty_date]`.

## Location

`app/Http/Controllers/RailwayReceipts/PenaltyController.php`

## Methods

| Method                  | HTTP Method | Route                                        | Purpose                                                                               |
| ----------------------- | ----------- | -------------------------------------------- | ------------------------------------------------------------------------------------- |
| `index`                 | GET         | `penalties`                                  | Renders `penalties/index` with the penalty table and chart data                       |
| `update`                | PATCH       | `penalties/{penalty}`                        | Updates a `Penalty` record (dispute/status workflow fields)                           |
| `analytics`             | GET         | `penalties/analytics`                        | Renders `penalties/analytics` with summary cards, breakdowns, trends, and AI insights |
| `disputeRecommendation` | GET         | `penalties/{penalty}/dispute-recommendation` | Returns an AI-generated dispute recommendation as JSON                                |

## Routes

- `penalties.index`: `GET /penalties` - penalty register table + charts
- `penalties.analytics`: `GET /penalties/analytics` - analytics dashboard
- `penalties.update`: `PATCH /penalties/{penalty}` - update penalty fields
- `penalties.dispute-recommendation`: `GET /penalties/{penalty}/dispute-recommendation` - AI dispute recommendation

## Actions Used

- `BuildPenaltyChartDataAction` - chart aggregates (by type, by siding, monthly trend) for `index`
- `GeneratePenaltyInsightsAction` - deferred AI insights on `analytics`
- `RecommendDisputeAction` - AI dispute recommendation for `disputeRecommendation`

## Validation

- `UpdatePenaltyRequest` - validates fields accepted by `update`

## `filter[penalty_date]` and the `rr_penalty_snapshots` base query

`penaltySnapshotQuery()` (private) is the shared base for all `analytics` breakdowns:

```php
DB::table('rr_penalty_snapshots')
    ->leftJoin('rr_documents', 'rr_penalty_snapshots.rr_document_id', '=', 'rr_documents.id')
    ->join('rakes', 'rr_penalty_snapshots.rake_id', '=', 'rakes.id')
    ->join('sidings', ...)
    ->leftJoin('penalty_types', 'rr_penalty_snapshots.penalty_code', '=', 'penalty_types.code')
```

scoped by `whereIn('rakes.siding_id', $sidingIds)`, then either `PenaltyDateFilter::apply()` when `filter[penalty_date]` is present, or a default 12-month window (`PenaltyDateFilter::DATE_EXPR >= now()->subMonths(12)`).

Several older analytics sub-builders (`buildDisputeAnalysis`, `buildResponsiblePartyDetail`, `buildTopOffenders`, `buildWeekdayHeatmap`, `buildRootCauseBreakdown`, `buildCostSavingOpportunities`, `buildByOperator`/`buildByShift`) still query the legacy `Penalty` model's `penalty_date`/`penalty_status`/`responsible_party`/`disputed_at` columns, which have no equivalent on `rr_penalty_snapshots`:

- **Dispute/status workflow** (`by_status`, `disputed_count`, `waived_count`, `dispute_success_rate` in `buildAnalyticsSummary`) is reported as empty/zero rather than fabricated, since `rr_penalty_snapshots` carries no dispute state.
- **`responsible_party`** breakdowns (`buildByResponsibleParty`, `buildResponsiblePartyDetail`) report empty for the same reason — no equivalent column on `rr_penalty_snapshots`.

## Related Components

- **Pages**: `resources/js/pages/penalties/index.tsx`, `resources/js/pages/penalties/analytics.tsx`
- **Actions**: `BuildPenaltyChartDataAction`, `GeneratePenaltyInsightsAction`, `RecommendDisputeAction`
- **Support**: `App\Support\PenaltyDateFilter`
- **DataTable**: `PenaltyDataTable` (used by `index`, also reads `rr_penalty_snapshots`)
- **Models**: `Penalty`, `Siding`, `RrPenaltySnapshot` (via raw query), `PenaltyType` (via raw query)
- **Routes**: `penalties.index`, `penalties.analytics`, `penalties.update`, `penalties.dispute-recommendation` (defined in `routes/web.php`)
