# MobileDashboardController

## Purpose

JSON endpoints for the **mobile** management dashboard (`/api/v1/dashboard/*`). Uses `auth:sanctum` and `feature:api_access` (see [API routes](../../api-reference/routes.md)).

## Location

`app/Http/Controllers/Api/Dashboard/MobileDashboardController.php`

## `sidingPerformanceMetrics`

- **Route**: `api.v1.dashboard.siding-performance-metrics` — `GET /api/v1/dashboard/siding-performance-metrics`
- **Behavior**: Delegates to `ExecutiveDashboardController::sidingPerformanceMetrics` — same query parameters (`period`, `from`, `to`, `siding_ids`, … plus optional `sp_rakes_*` / `sp_penalty_*`) and same JSON body `{ "rakes", "penalties" }`.
- **Authorization**: Same as web (`sections.dashboard.view` and `dashboard.widgets.siding_overview_performance`, or bypass), enforced inside `ExecutiveDashboardController`.

## Related

- [ExecutiveDashboardController](./executivedashboardcontroller.md) — `sidingPerformanceMetrics` implementation and query-parameter table
