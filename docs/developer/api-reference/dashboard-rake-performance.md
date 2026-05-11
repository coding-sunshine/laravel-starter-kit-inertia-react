# Dashboard: rake-wise performance (list, detail, legacy mobile bundle)

This document describes **rake-wise performance** JSON: the same contract for **web session** and **mobile Sanctum** list/detail endpoints, plus the **deprecated** monolithic mobile response.

**Mobile-only deep dive** (the two `rake-performance/rakes` APIs: every filter, success and error responses): [Mobile rake performance rakes API](./mobile-rake-performance-rakes-api.md).

## Endpoints

| Environment | Method | Path | Route name (Laravel) |
|-------------|--------|------|----------------------|
| Web | GET | `/dashboard/rake-performance/rakes` | `dashboard.rake-performance.rakes.index` |
| Web | GET | `/dashboard/rake-performance/rakes/{rake}` | `dashboard.rake-performance.rakes.show` |
| Mobile API | GET | `/api/v1/dashboard/rake-performance/rakes` | `api.v1.dashboard.rake-performance.rakes.index` |
| Mobile API | GET | `/api/v1/dashboard/rake-performance/rakes/{rake}` | `api.v1.dashboard.rake-performance.rakes.show` |
| Mobile API (deprecated) | GET | `/api/v1/dashboard/rake-performance` | `api.v1.dashboard.rake-performance` |

**Mobile list** delegates to `ExecutiveDashboardController::rakePerformanceList` — same query parameters and JSON as web `GET /dashboard/rake-performance/rakes`.

**Mobile show** (`GET /api/v1/dashboard/rake-performance/rakes/{rake}`) uses `ExecutiveDashboardController::rakePerformanceDetailForApi`: same **`data`** payload shape as web detail (including `wagon_overloads`), but **does not** re-apply list date range or `rake_number` / `power_plant` / `rake_penalty_scope`; only **rake id** (plus auth/siding access and weighment eligibility) matters. **`filters`** in the response is `{ "rake_id": <int> }` only.

**Web show** (`GET /dashboard/rake-performance/rakes/{rake}`) still uses `rakePerformanceDetail` — list filters and date range must match the row you open.

**Legacy** `GET /api/v1/dashboard/rake-performance` returns `{ "filters", "data": { "rakePerformance": [...] } }` where `rakePerformance` is built with `buildRakePerformance()` (all matching rakes, each including `wagon_overloads` and `loading_minutes`). Prefer the **paginated list** + **per-rake detail** endpoints for native apps to avoid large payloads and timeouts.

## Authentication and authorization

### Web (`/dashboard/rake-performance/rakes`, `/dashboard/rake-performance/rakes/{rake}`)

- **Auth**: web session (`web`, `auth`, `verified`).
- **Authorization** (unless `bypass-permissions`):
  - `sections.dashboard.view`
  - `dashboard.widgets.rake_performance` (see `DashboardWidgetPermissions::userCanSeeDashboardSection` for `rake-performance`).

### Mobile API (`/api/v1/dashboard/rake-performance/rakes`, `.../rakes/{rake}`, legacy `.../rake-performance`)

- **Auth**: `auth:sanctum`
- **Feature flag**: `feature:api_access` (route not registered for the request lifecycle when inactive — same as other `api/v1/dashboard/*` routes).
- **Authorization**: enforced inside `ExecutiveDashboardController` for list/detail (same as web).

## Common filters (query parameters)

List and detail reuse **`DashboardFilterResolver`** (`App\Support\Dashboard\DashboardFilterResolver`) — same rules as other dashboard JSON.

### Date range

- **`period`**: string; default `yesterday`. Allowed: `yesterday`, `today`, `week`, `last_week`, `month`, `last_month`, `custom`.
- **`from`**, **`to`**: `YYYY-MM-DD`; used when `period=custom`.

### Siding scope

- **`siding_ids`**: comma-separated integers (intersected with accessible sidings).
- **`siding_id`** (list only): single integer; restricts the list to one siding within scope. Invalid or out-of-scope siding returns **422** from the list action.

### Rake-wise filters

- **`power_plant`**: string; optional.
- **`rake_number`**: string; partial match (LIKE).
- **`rake_penalty_scope`**: `all` (default) or `with_penalties` — limits to rakes with predicted or RR penalty rows.

Filter pickers for mobile: `GET /api/v1/dashboard/filter-options?section=rake-performance` (`rake_number`, `power_plant`, `rake_penalty_scope`).

### List pagination (index only)

- **`page`**: integer, default `1`.
- **`per_page`**: integer, default `100`, clamped **1–100**. Mobile apps typically use **`per_page=20`** and increment `page` for infinite scroll.

### Other resolver keys

Optional keys such as `section`, `loader_id`, `shift`, etc. may appear on the query string when reusing dashboard URL builders; **list** behavior is driven by the resolver and `rakePerformanceBaseQuery`. **Mobile show** ignores those for inclusion (only siding access + weighment eligibility apply).

## Response shapes

### `GET .../rake-performance/rakes` (index)

`application/json`:

- **`filters`**: `period`, `from`, `to`, `siding_ids`, `power_plant`, `rake_number`, `rake_penalty_scope` (serialized resolved scope).
- **`data`**: array of **summary** rows (no `loading_minutes`, no `wagon_overloads`). Each row includes at least: `id`, `siding_id`, `rake_number`, `rake_serial_number`, `siding`, `dispatch_date`, `wagon_count`, `net_weight`, `over_load`, `under_load`, predicted/actual penalty amount and count fields.
- **`meta`**: `current_page`, `last_page`, `per_page`, `total`.

### `GET .../rake-performance/rakes/{rake}` (show)

`application/json`:

- **`filters`**: same shape as index.
- **`data`**: single object — summary fields plus **`loading_minutes`** and **`wagon_overloads`** (wagon weighment rows with `loader_id`, `loader_name`, `loader_operator_name`, `over_load_mt`, `under_load_mt`, `cc_capacity_mt`, `net_weight_mt`, `wagon_number`).

**404**: when the rake is not found, not in scope, or does not satisfy the rake-performance query (e.g. missing weighment data per `rakePerformanceBaseQuery`).

## Related

- [ExecutiveDashboardController](../backend/controllers/executivedashboardcontroller.md) — `rakePerformanceList`, `rakePerformanceDetail`, `rakePerformanceDetailForApi`, `buildRakePerformance`
- [MobileDashboardController](../backend/controllers/mobiledashboardcontroller.md)
