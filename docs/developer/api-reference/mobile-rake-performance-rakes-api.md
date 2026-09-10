# Mobile API: rake performance list and detail

This document covers **only** these two Sanctum endpoints under the versioned API base (`/api/v1`):

| # | Method | Path | Laravel route name |
|---|--------|------|---------------------|
| 1 | `GET` | `/api/v1/dashboard/rake-performance/rakes` | `api.v1.dashboard.rake-performance.rakes.index` |
| 2 | `GET` | `/api/v1/dashboard/rake-performance/rakes/{rake}` | `api.v1.dashboard.rake-performance.rakes.show` |

**Base URL:** `{APP_URL}/api/v1` (example: `https://your-app.test/api/v1`).

**Not covered here:** legacy `GET /api/v1/dashboard/rake-performance`, web session routes under `/dashboard/rake-performance/...`, or other dashboard APIs.

---

## Shared: authentication, feature flag, middleware, authorization

### Middleware (order matters on the route group)

From [`routes/api.php`](../../../routes/api.php): `api`, `throttle:60,1`, `auth:sanctum`, `feature:api_access`.

| Concern | Behavior |
|---------|----------|
| **Rate limit** | `throttle:60,1` — 60 requests per minute per key (typical Laravel throttle). **429 Too Many Requests** when exceeded (standard Laravel JSON/throttle response). |
| **Auth** | **`auth:sanctum`** — send `Authorization: Bearer {token}` (or session cookie if your client uses cookies). **401 Unauthorized** if missing/invalid token. |
| **API access feature** | **`feature:api_access`** — Pennant feature `App\Features\ApiAccessFeature`. If inactive for the authenticated user, the route is not satisfied and Laravel responds **404 Not Found** (same pattern as other `/api/v1/dashboard/*` routes in tests). |

### Authorization (both endpoints)

Enforced in `ExecutiveDashboardController` (same rules for list and detail):

| Check | HTTP if failed |
|-------|----------------|
| Authenticated user present | **403** (controller `abort_unless($user !== null, 403)` — normally Sanctum returns 401 first for guests) |
| `bypass-permissions` **or** `sections.dashboard.view` | **403 Forbidden** |
| `bypass-permissions` **or** `dashboard.widgets.rake_performance` (rake-performance section) | **403 Forbidden** |

---

## 1. List: `GET /api/v1/dashboard/rake-performance/rakes`

**Controller:** `MobileDashboardController::rakePerformanceRakesIndex` → `ExecutiveDashboardController::rakePerformanceList`.

### Purpose

Paginated **summary** rows for rake-wise performance (no `loading_minutes`, no `wagon_overloads` per row). Same server logic and **list** query contract as web `GET /dashboard/rake-performance/rakes`.

### Query parameters

All parameters are **query string** (`?key=value&...`). Unless noted, omission uses the default.

#### Date range (drives `rakes.loading_date` filter)

| Name | Type | Required | Default | Format / values |
|------|------|----------|---------|-----------------|
| `period` | string | no | `yesterday` | One of: `yesterday`, `today`, `week`, `last_week`, `month`, `last_month`, `custom`. Unknown values fall back to **`yesterday`**-style bounds (see `DashboardFilterResolver::boundsForPeriod`). |
| `from` | date string | only if `period=custom` | (resolver default) | **`YYYY-MM-DD`**. Used with `period=custom`. If omitted under `custom`, bounds default to **start of current month → end of today** (inclusive), app timezone. |
| `to` | date string | only if `period=custom` | (resolver default) | **`YYYY-MM-DD`**. Same as `from` for custom range. |

**Note:** When `period` is **not** `custom`, the resolver **clears** `from` / `to` from the request and derives bounds from `period` only.

#### Siding scope

| Name | Type | Required | Default | Format / values |
|------|------|----------|---------|-----------------|
| `siding_ids` | string or array | no | all sidings the user may access | **String:** comma-separated integers, optional spaces: `1,2,3`. **Array:** repeated or array form as sent by HTTP client. Parsed in [`DashboardFilterResolver::parseRequestedSidingIds`](../../../app/Support/Dashboard/DashboardFilterResolver.php): only positive integers; intersected with user’s accessible siding ids. |
| `siding_id` | integer | no | (none) | **List-only** extra filter: when set, list is restricted to that siding **and** it must be in the resolved `filteredSidingIds`. |

#### Rake-wise filters (applied inside `rakePerformanceBaseQuery` for the list)

| Name | Type | Required | Default | Format / values |
|------|------|----------|---------|-----------------|
| `power_plant` | string | no | null | Exact match against `rake_weighments.to_station` subquery (rakes having such a weighment). |
| `rake_number` | string | no | null | Partial match: SQL `LIKE %value%` on `rakes.rake_number`. |
| `rake_penalty_scope` | string | no | `all` | **`all`** — no penalty filter. **`with_penalties`** — rake must have at least one `applied_penalties` or `rr_penalty_snapshots` row. Any other string is treated as **`all`**. |

#### Pagination (list only)

| Name | Type | Required | Default | Format / values |
|------|------|----------|---------|-----------------|
| `page` | integer | no | `1` | Minimum `1`. |
| `per_page` | integer | no | `100` | Clamped to **1–100** inclusive. |

#### Resolver-only / context (stored in `filterContext`; **not** used by `rakePerformanceBaseQuery` for listing)

These are still read by `DashboardFilterResolver::resolve()` and appear in internal `filterContext`; they **do not** change which rakes appear in the rake-performance **list** query (only `power_plant`, `rake_number`, `rake_penalty_scope` do, plus date/siding). Documented for parity with other dashboard URLs:

| Name | Type | Default | Notes |
|------|------|---------|--------|
| `loader_id` | integer | null | From request via `integer()` helper. |
| `loader_operator` | string | null | Trimmed; empty → null. |
| `shift` | string | null | |
| `penalty_type` | integer | null | |
| `underload_threshold` | number | `1.0` | Clamped **0–100** if numeric. |
| `daily_rake_date` | date | yesterday (resolver default) | Parsed if present. |
| `coal_transport_date` | date | yesterday (resolver default) | Parsed if present. |
| `section` | string | first permitted dashboard section | Must be an allowed **and** permitted section id, else fallback. |

#### List-specific validation

| Condition | HTTP | Message / body |
|-----------|------|------------------|
| `siding_id` set and **not** in current `filteredSidingIds` | **422 Unprocessable Entity** | Plain-text message: `The selected siding is not in the current filter scope.` |

### Success response — `200 OK`

**Content-Type:** `application/json`

```json
{
  "filters": {
    "period": "yesterday",
    "from": "2026-05-10",
    "to": "2026-05-10",
    "siding_ids": [1, 2],
    "power_plant": null,
    "rake_number": null,
    "rake_penalty_scope": "all"
  },
  "data": [
    {
      "id": 23894,
      "siding_id": 1,
      "rake_number": "90",
      "rake_serial_number": null,
      "siding": "Pakur Siding",
      "dispatch_date": "30 Apr 2026",
      "wagon_count": 60,
      "net_weight": 3600.0,
      "over_load": 1.39,
      "under_load": 126.73,
      "predicted_penalty_amount": 2100.0,
      "predicted_penalty_count": 2,
      "actual_penalty_amount": 0,
      "actual_penalty_count": 0
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 87
  }
}
```

- **`filters`:** Echo of resolved scope for list (subset of resolver output), from `serializeRakePerformanceListFilters`.
- **`data`:** Array of **summary** objects. **No** `loading_minutes`, **no** `wagon_overloads`.
- **`meta`:** Laravel-style pagination metadata.

#### Summary row field reference

| Field | Type | Notes |
|-------|------|--------|
| `id` | integer | Rake primary key. |
| `siding_id` | integer | |
| `rake_number` | string | |
| `rake_serial_number` | string\|null | |
| `siding` | string | Siding display name or `—`. |
| `dispatch_date` | string | From `loading_date` or `created_at`, formatted **`d M Y`** (e.g. `30 Apr 2026`), or `—`. |
| `wagon_count` | integer\|null | |
| `net_weight` | number\|null | From weighment aggregate, 2 decimal places. |
| `over_load` | number\|null | |
| `under_load` | number\|null | |
| `predicted_penalty_amount` | number | `0` if none. |
| `predicted_penalty_count` | integer | |
| `actual_penalty_amount` | number | |
| `actual_penalty_count` | integer | |

**Empty list:** Still **200** with `"data": []` and `meta.total` possibly `0` if no rakes match (not an error).

### Failure responses — list

| HTTP | When |
|------|------|
| **401** | No/invalid Sanctum token. |
| **403** | Logged in but missing `sections.dashboard.view` or `dashboard.widgets.rake_performance` (and no `bypass-permissions`). |
| **404** | `feature:api_access` inactive for user, or route not found. |
| **422** | Invalid `siding_id` for current scope (see above). |
| **429** | Throttle exceeded. |

---

## 2. Detail: `GET /api/v1/dashboard/rake-performance/rakes/{rake}`

**Controller:** `MobileDashboardController::rakePerformanceRakeShow` → `ExecutiveDashboardController::rakePerformanceDetailForApi`.

### Purpose

One rake **detail** payload: same **`data`** field shape as the web detail modal (includes `loading_minutes` and `wagon_overloads` with loader/operator fields). **Unlike the web** `rakePerformanceDetail`, this endpoint **does not** require list date range or `rake_number` / `power_plant` / `rake_penalty_scope` to match — only **route model `{rake}`**, **user siding access**, and **operational + weighment eligibility** (see below).

### Path parameter

| Name | Type | Required | Notes |
|------|------|----------|--------|
| `rake` | integer (id) | yes | Implicit Eloquent binding to `App\Models\Rake`. **Soft-deleted** rakes are **not** resolved → **404** before controller body. |

### Query parameters

**None required.** Any query string keys are **ignored** for **which** rake is returned (inclusion is by id + siding access + eligibility only). You may still send a query string without breaking the request.

### Eligibility (when `data` is returned vs 404)

After auth, the rake must:

1. Have `siding_id` in the user’s accessible siding list (super-admin: all sidings; otherwise `accessibleSidings()`).
2. Satisfy the same **non-date** quality rules as list rows: `data_source` null or in `['system','manual']`, and exist related `rake_weighments` and `rake_wagon_weighments` via the latest weighment chain (see `rakePerformanceDetailByIdBaseQuery` in `ExecutiveDashboardController`).

If any check fails, the handler returns **404** (no JSON body shape is guaranteed beyond Laravel’s default exception handler).

### Success response — `200 OK`

**Content-Type:** `application/json`

```json
{
  "filters": {
    "rake_id": 23894
  },
  "data": {
    "id": 23894,
    "siding_id": 1,
    "rake_number": "90",
    "rake_serial_number": null,
    "siding": "Pakur Siding",
    "dispatch_date": "16 Apr 2026",
    "wagon_count": 60,
    "net_weight": 3900.0,
    "over_load": 0.5,
    "under_load": 120.0,
    "predicted_penalty_amount": 9900.0,
    "predicted_penalty_count": 3,
    "actual_penalty_amount": 0,
    "actual_penalty_count": 0,
    "loading_minutes": 0,
    "wagon_overloads": [
      {
        "wagon_number": "12345678",
        "over_load_mt": 0.5,
        "under_load_mt": 2.1,
        "cc_capacity_mt": 60.0,
        "net_weight_mt": 57.9,
        "loader_id": 5,
        "loader_name": "Loader A",
        "loader_operator_name": "Operator Name"
      }
    ]
  }
}
```

- **`filters`:** Always **`{ "rake_id": <int> }`** only (mobile API contract).
- **`data`:** Same keys as summary, plus:
  - **`loading_minutes`:** integer or `null` if start/end loading times missing.
  - **`wagon_overloads`:** array ordered by wagon sequence; each item may include `loader_id`, `loader_name`, `loader_operator_name` when `wagon_loading` data exists.

Numeric rounding: weighment-related numbers are rounded to **2** decimal places where applicable (see `mapRakeToPerformanceArray` / wagon row builder).

### Failure responses — detail

| HTTP | When |
|------|------|
| **401** | No/invalid Sanctum token. |
| **403** | Same permission rules as list. |
| **404** | `feature:api_access` off; **or** `{rake}` not found / soft-deleted; **or** rake not in accessible sidings; **or** rake fails operational/weighment eligibility. |
| **429** | Throttle exceeded. |

---

## Related code

- [`App\Http\Controllers\Api\Dashboard\MobileDashboardController`](../../../app/Http/Controllers/Api/Dashboard/MobileDashboardController.php) — `rakePerformanceRakesIndex`, `rakePerformanceRakeShow`
- [`App\Http\Controllers\Dashboard\ExecutiveDashboardController`](../../../app/Http/Controllers/Dashboard/ExecutiveDashboardController.php) — `rakePerformanceList`, `rakePerformanceDetailForApi`, `buildRakePerformanceDetailForApi`, `rakePerformanceDetailByIdBaseQuery`, `rakePerformanceBaseQuery`, `serializeRakePerformanceListFilters`
- [`App\Support\Dashboard\DashboardFilterResolver`](../../../app/Support/Dashboard/DashboardFilterResolver.php) — `resolve()`
- [`App\Support\Dashboard\DashboardWidgetPermissions`](../../../app/Support/Dashboard/DashboardWidgetPermissions.php) — `rake-performance` section

Broader context (web + legacy mobile): [Dashboard rake performance](./dashboard-rake-performance.md).
