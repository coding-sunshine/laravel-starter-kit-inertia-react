# ExecutiveDashboardController

## Purpose

Serves the main Inertia dashboard (`/dashboard`) and related JSON endpoints for dashboard widgets (executive data, rake performance lists, loader overload, **siding performance chart metrics**).

## Location

`app/Http/Controllers/Dashboard/ExecutiveDashboardController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| `__invoke` | GET | `dashboard` | Inertia dashboard page with filters and props |
| `executiveYesterdayData` | GET | `dashboard/executive-yesterday-data` | JSON for executive yesterday tab / custom ranges |
| `sidingPerformanceMetrics` | GET | `dashboard/siding-performance-metrics` | JSON for siding performance charts (independent `sp_rakes_*` / `sp_penalty_*` date slices) |
| `rakePerformanceList` | GET | `dashboard/rake-performance/rakes` | JSON list for rake performance |
| `rakePerformanceDetail` | GET | `dashboard/rake-performance/rakes/{rake}` | JSON detail for one rake |

## `sidingPerformanceMetrics` query parameters

Reuses the **same** query string as the dashboard for scope (`period`, `from`, `to`, `siding_ids`, `power_plant`, `rake_number`, etc.) via `DashboardFilterResolver::resolve()`.

Per-chart overrides (optional):

| Parameter | Values | Notes |
|-----------|--------|--------|
| `sp_rakes_period` | `main` (default), `yesterday`, `today`, `month`, `last_month`, `custom` | Rakes-dispatched series date range |
| `sp_rakes_from`, `sp_rakes_to` | `Y-m-d` | Required when `sp_rakes_period=custom` |
| `sp_penalty_period` | same as above | Penalty-by-siding series |
| `sp_penalty_from`, `sp_penalty_to` | `Y-m-d` | Required when `sp_penalty_period=custom` |

**Response** (`application/json`): `{ "rakes": [ { "name", "rakes" } ], "penalties": [ { "name", "penalty_amount" } ] }` — siding order follows filtered siding ids.

**Authorization**: `sections.dashboard.view` (or bypass) **and** `dashboard.widgets.siding_overview_performance` (or bypass).

## Routes

- `dashboard`: `GET /dashboard` — Inertia
- `dashboard.executive-yesterday-data`: `GET /dashboard/executive-yesterday-data` — JSON
- `dashboard.siding-performance-metrics`: `GET /dashboard/siding-performance-metrics` — JSON
- `dashboard.rake-performance.rakes.index`: `GET /dashboard/rake-performance/rakes` — JSON

## Related Components

- **Pages**: `dashboard` (`resources/js/pages/dashboard.tsx`)
- **Support**: `App\Support\Dashboard\DashboardFilterResolver`, `DashboardWidgetPermissions`
