# MobileDashboardController

## Purpose

JSON endpoints for the **mobile** management dashboard (`/api/v1/dashboard/*`). Uses `auth:sanctum` and `feature:api_access` (see [API routes](../../api-reference/routes.md)).

## Location

`app/Http/Controllers/Api/Dashboard/MobileDashboardController.php`

## `sidingPerformanceMetrics`

- **Route**: `api.v1.dashboard.siding-performance-metrics` — `GET /api/v1/dashboard/siding-performance-metrics`
- **Behavior**: Delegates to `ExecutiveDashboardController::sidingPerformanceMetrics` — same query parameters (`period`, `from`, `to`, `siding_ids`, … plus optional `sp_rakes_*` / `sp_penalty_*`) and same JSON body `{ "rakes", "penalties" }`.
- **Authorization**: Same as web (`sections.dashboard.view` and `dashboard.widgets.siding_overview_performance`, or bypass), enforced inside `ExecutiveDashboardController`.

## `rakePerformanceRakesIndex`

- **Route**: `api.v1.dashboard.rake-performance.rakes.index` — `GET /api/v1/dashboard/rake-performance/rakes`
- **Behavior**: Delegates to `ExecutiveDashboardController::rakePerformanceList` — same query parameters and JSON as web `GET /dashboard/rake-performance/rakes` (paginated `data` + `meta`, summary rows only).
- **Authorization**: Same as web (`sections.dashboard.view` and `dashboard.widgets.rake_performance`, or bypass), enforced inside `ExecutiveDashboardController`.

## `rakePerformanceRakeShow`

- **Route**: `api.v1.dashboard.rake-performance.rakes.show` — `GET /api/v1/dashboard/rake-performance/rakes/{rake}`
- **Behavior**: Delegates to `ExecutiveDashboardController::rakePerformanceDetailForApi` — same **`data`** shape as web detail (`wagon_overloads`, loader metadata); **does not** re-apply list date or `rake_number` / `power_plant` / `rake_penalty_scope` (rake id + siding access + weighment eligibility only). **`filters`**: `{ "rake_id": <int> }`.
- **Authorization**: Same as web (`sections.dashboard.view` and `dashboard.widgets.rake_performance`, or bypass), enforced inside `ExecutiveDashboardController`.

## `rakePerformance` (deprecated)

- **Route**: `api.v1.dashboard.rake-performance` — `GET /api/v1/dashboard/rake-performance`
- **Behavior**: Returns `data.rakePerformance` from `ExecutiveDashboardController::buildRakePerformance()` for all matching rakes in one response (heavy). Prefer `rakePerformanceRakesIndex` + `rakePerformanceRakeShow` for mobile.

## Related

- [ExecutiveDashboardController](./executivedashboardcontroller.md) — `sidingPerformanceMetrics`, `rakePerformanceList`, `rakePerformanceDetail`, `rakePerformanceDetailForApi`, `buildRakePerformance`
- [Dashboard rake performance API](../../api-reference/dashboard-rake-performance.md) — filters, responses, legacy note
- [Mobile rake performance rakes API](../../api-reference/mobile-rake-performance-rakes-api.md) — list + detail only, full parameters and errors
