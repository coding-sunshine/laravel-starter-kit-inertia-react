# Mobile API: loader overload (four endpoints)

Full reference for **`GET /api/v1/dashboard/loader-overload/loaders`**, **`GET .../loaders/{loader}`**, **`GET .../operators`**, and **`GET .../operators/show`**. Same server logic and JSON shapes as the web session routes under `/dashboard/loader-overload/*` ([`LoaderOverloadWebController`](../../../app/Http/Controllers/Dashboard/LoaderOverloadWebController.php), [`LoaderOverloadMetricsService`](../../../app/Services/Dashboard/LoaderOverloadMetricsService.php)).

| # | Method | Path | Route name |
|---|--------|------|--------------|
| 1 | `GET` | `/api/v1/dashboard/loader-overload/loaders` | `api.v1.dashboard.loader-overload.loaders.index` |
| 2 | `GET` | `/api/v1/dashboard/loader-overload/loaders/{loader}` | `api.v1.dashboard.loader-overload.loaders.show` |
| 3 | `GET` | `/api/v1/dashboard/loader-overload/operators` | `api.v1.dashboard.loader-overload.operators.index` |
| 4 | `GET` | `/api/v1/dashboard/loader-overload/operators/show` | `api.v1.dashboard.loader-overload.operators.show` |

**Base path:** `{APP_URL}/api/v1/dashboard/loader-overload/...`

**Out of scope here:** legacy `GET /api/v1/dashboard/loader-overload` (aggregate `loaderOverloadTrends` only).

---

## Table of contents

1. [Transport, auth, and middleware](#1-transport-auth-and-middleware)
2. [Shared query parameters (`DashboardFilterResolver`)](#2-shared-query-parameters-dashboardfilterresolver)
3. [Endpoint 1: Loaders index](#3-endpoint-1-loaders-index)
4. [Endpoint 2: Loader show](#4-endpoint-2-loader-show)
5. [Endpoint 3: Operators index](#5-endpoint-3-operators-index)
6. [Endpoint 4: Operator show](#6-endpoint-4-operator-show)
7. [Shared success: `filters` object](#7-shared-success-filters-object)
8. [Operator name matching (important)](#8-operator-name-matching-important)
9. [Example error response bodies](#9-example-error-response-bodies-laravel-defaults)
10. [Quick reference: HTTP status matrix](#quick-reference-http-status-matrix)

---

## 1. Transport, auth, and middleware

Defined in [`routes/api.php`](../../../routes/api.php) on the `api/v1` group:

| Layer | Value |
|-------|--------|
| Prefix | `/api/v1` |
| HTTP method | **GET** only (no body) |
| Middleware (order) | `api`, `throttle:60,1`, `auth:sanctum`, `feature:api_access` |

### 1.1 Authentication

| Header | Required | Notes |
|--------|----------|--------|
| `Authorization` | Yes | `Bearer {personal_access_token}` (Sanctum). |
| `Accept` | Recommended | `application/json` for consistent JSON errors. |

| HTTP | When |
|------|------|
| **401 Unauthorized** | Missing or invalid token (guest or bad bearer). |

### 1.2 API access feature (Pennant)

| HTTP | When |
|------|------|
| **404 Not Found** | `App\Features\ApiAccessFeature` is **inactive** for the authenticated user (`feature:api_access` middleware). Same pattern as other `/api/v1/dashboard/*` routes. |

### 1.3 Loader-overload authorization

After Sanctum, [`MobileDashboardController::assertCanAccessLoaderOverload`](../../../app/Http/Controllers/Api/Dashboard/MobileDashboardController.php) runs for all four endpoints:

| Order | Rule | HTTP if failed |
|-------|------|-----------------|
| 1 | User authenticated | **403** (`abort_unless($user !== null, 403)`) — in practice Sanctum usually returns **401** first for guests. |
| 2 | `bypass-permissions` **or** `sections.dashboard.view` | **403 Forbidden** |
| 3 | `bypass-permissions` **or** `dashboard.widgets.loader_overload_trends` (see [`DashboardWidgetPermissions::userCanSeeDashboardSection(..., 'loader-overload')`](../../../app/Support/Dashboard/DashboardWidgetPermissions.php)) | **403 Forbidden** |

**403 body:** Laravel default JSON (e.g. `{"message":"This action is unauthorized."}`) unless customized globally.

### 1.4 Rate limiting

| HTTP | When |
|------|------|
| **429 Too Many Requests** | More than **60** requests per minute per throttle key (`throttle:60,1`). Standard Laravel throttle JSON/throttle response. |

---

## 2. Shared query parameters (`DashboardFilterResolver`)

All four endpoints call [`DashboardFilterResolver::resolve($request)`](../../../app/Support/Dashboard/DashboardFilterResolver.php). Parameters may appear in the **query string** or request input; Laravel merges `query()` and `input()` where noted below.

### 2.1 Date range

| Name | Type | Required | Default | Valid values / notes |
|------|------|----------|---------|----------------------|
| `period` | string | No | `yesterday` | Presets: `yesterday`, `today`, `week`, `last_week`, `month`, `last_month`, **`custom`**, or any other string (unknown → same bounds as **`yesterday`** in [`boundsForPeriod`](../../../app/Support/Dashboard/DashboardFilterResolver.php)). |
| `from` | date string | Only for `custom` | If omitted under `custom`: **start of current month** | `YYYY-MM-DD`, app timezone. When **`period` ≠ `custom`**, the resolver **clears** `from` / `to` from the request and ignores them. |
| `to` | date string | Only for `custom` | If omitted under `custom`: **end of today** | `YYYY-MM-DD`, app timezone. |

**Validation / parsing:** Dates are parsed with [`Carbon::parse`](../../../app/Support/Dashboard/DashboardFilterResolver.php) in `config('app.timezone')`. Invalid date strings can throw or yield unexpected bounds depending on Carbon; clients should send valid `YYYY-MM-DD`.

### 2.2 Siding scope

| Name | Type | Required | Default | Notes |
|------|------|----------|---------|--------|
| `siding_ids` | string **or** array | No | All sidings the user may access | **String:** comma-separated positive integers (`1, 2, 3`). **Array:** repeated keys or PHP array form. Parsed in [`parseRequestedSidingIds`](../../../app/Support/Dashboard/DashboardFilterResolver.php); result is **intersected** with the user’s accessible siding ids (`isSuperAdmin()` → all sidings; else `accessibleSidings()` / `siding_id` rules). |
| `siding_id` | integer | No | — | Alternative to `siding_ids`: single value parsed like list input. |

If the intersection is empty (e.g. user requests sidings they cannot access), `filteredSidingIds` may be empty → list/detail queries return **empty data** (still **200**), not an error.

### 2.3 Dashboard filter context (stored in resolver + echoed in `filters`)

| Name | Type | Required | Default | Notes |
|------|------|----------|---------|--------|
| `power_plant` | string | No | `null` | Stored in `filterContext` and echoed in **`filters`**. **Not** applied by [`LoaderOverloadMetricsService`](../../../app/Services/Dashboard/LoaderOverloadMetricsService.php) for these four endpoints (no effect on counts). |
| `rake_number` | string | No | `null` | Same as `power_plant` — **resolver + echo only** for loader-overload metrics here. |
| `loader_id` | integer | No | `null` | From `$request->integer('loader_id')`; `0` → `null`. **Affects SQL** for **`paginateLoadersWithActivity`** and **`loaderDetail`** ([`baseWagonLoadingQuery`](../../../app/Services/Dashboard/LoaderOverloadMetricsService.php)). |
| `loader_operator` | string | No | `null` | Trimmed; empty → `null`. **Affects SQL** for loaders index + loader detail (**exact** `wagon_loading.loader_operator_name`). **Not** applied to operators index / operator detail lists. |
| `shift` | string | No | `null` | Stored in `filterContext`; **not** used in `LoaderOverloadMetricsService` for these four endpoints. |
| `penalty_type` | integer | No | `null` | Stored as `penalty_type_id` in `filterContext`; **not** used in `LoaderOverloadMetricsService` for these four endpoints. |
| `rake_penalty_scope` | string | No | `all` | **`all`** or **`with_penalties`** ([`resolveRakePenaltyScope`](../../../app/Support/Dashboard/DashboardFilterResolver.php)); echoed in **`filters`**; **not** used in `LoaderOverloadMetricsService` for these four endpoints. |
| `underload_threshold` | number | No | `1.0` | If present and numeric: clamped to **0.0–100.0**. Non-numeric ignored → default `1.0`. Stored as `underload_threshold_percent` in `filterContext`. Drives underload CASE in SQL for **`loaderDetail`** and **`operatorDetail`**. |
| `daily_rake_date` | date string | No | Resolver default (yesterday anchor) | Single day for resolver state; echoed in **legacy** `serializeFilters` only — **not** in the four endpoints’ `filters` (list uses `serializeLoaderOverloadListFilters`). Still **parsed** on every request. |
| `coal_transport_date` | date string | No | Same as above | Same as `daily_rake_date`. |
| `section` | string | No | First **permitted** dashboard section for user | Must be both **allowed** and **permitted** ([`DashboardWidgetPermissions`](../../../app/Support/Dashboard/DashboardWidgetPermissions.php)); else fallback. Affects resolver only; does not change loader-overload SQL directly. |

### 2.4 Pagination (loaders index + operators index only)

| Name | Type | Required | Default | Validation / clamping |
|------|------|----------|---------|------------------------|
| `page` | integer | No | `1` | `(int) query` then `max(1, …)` — non-numeric → `0` → **1**. |
| `per_page` | integer | No | `10` | `(int) query` then `min(50, max(1, …))` — max **50**, min **1**. |

### 2.5 Which shared parameters affect SQL (this service only)

| Endpoint | Affects counts / rows |
|----------|----------------------|
| **Loaders index** | `siding_ids` / `siding_id` → `filteredSidingIds`; `period` / `from` / `to`; **`loader_id`**; **`loader_operator`**. |
| **Loader show** | Same as loaders index for the underlying `wagon_loading` query, plus **`underload_threshold`** for overload/underload CASE; **`{loader}`** path forces that loader. |
| **Operators index** | `filteredSidingIds` + date range only (distinct operator names from `wagon_loading`). |
| **Operator show** | `filteredSidingIds` + date range + **`underload_threshold`** + **`siding_id`** + **`operator`** (exact `loader_operator_name`). |

All other resolver keys in [§2.3](#23-dashboard-filter-context-stored-in-resolver--echoed-in-filters) still appear in the **`filters`** JSON for a consistent dashboard contract.

---

## 3. Endpoint 1: Loaders index

**Full URL:** `GET {APP_URL}/api/v1/dashboard/loader-overload/loaders`

**Controller:** [`MobileDashboardController::loaderOverloadLoadersIndex`](../../../app/Http/Controllers/Api/Dashboard/MobileDashboardController.php)

**Service:** [`LoaderOverloadMetricsService::paginateLoadersWithActivity`](../../../app/Services/Dashboard/LoaderOverloadMetricsService.php)

### 3.1 Path parameters

None.

### 3.2 Query parameters

| Category | Parameters |
|----------|----------------|
| **Shared** | All of [§2 Shared query parameters](#2-shared-query-parameters-dashboardfilterresolver). |
| **Pagination** | `page`, `per_page` ([§2.4](#24-pagination-loaders-index--operators-index-only)). |

### 3.3 Validation and errors

| Condition | HTTP | Response notes |
|-----------|------|------------------|
| Missing/invalid token | **401** | Sanctum. |
| API feature off | **404** | `feature:api_access`. |
| Missing dashboard or widget permission | **403** | [§1.3](#13-loader-overload-authorization). |
| Throttle | **429** | [§1.4](#14-rate-limiting). |

There is **no** 422 validator for this route; invalid dates may still resolve or error depending on Carbon.

### 3.4 Success response — `200 OK`

**Content-Type:** `application/json`

**Top-level keys:** `filters`, `data`, `meta`.

```json
{
  "filters": {
    "period": "last_month",
    "from": "2026-04-01",
    "to": "2026-04-30",
    "siding_ids": [1],
    "power_plant": null,
    "rake_number": null,
    "loader_id": null,
    "loader_operator": null,
    "underload_threshold": 1,
    "shift": null,
    "penalty_type": null,
    "rake_penalty_scope": "all"
  },
  "data": [
    {
      "id": 4,
      "name": "3 Pakur-JCB No 3",
      "siding": "Pakur Siding"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 22
  }
}
```

#### `data[]` row (loaders index)

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | `loaders.id` |
| `name` | string | `loader_name` |
| `siding` | string | Siding display name or `—` |

**Empty list:** `data` may be `[]` with `meta.total` `0` when no loaders have `wagon_loading` activity in range (still **200**).

---

## 4. Endpoint 2: Loader show

**Full URL:** `GET {APP_URL}/api/v1/dashboard/loader-overload/loaders/{loader}`

**Controller:** [`MobileDashboardController::loaderOverloadLoaderShow`](../../../app/Http/Controllers/Api/Dashboard/MobileDashboardController.php)

**Service:** [`LoaderOverloadMetricsService::loaderDetail`](../../../app/Services/Dashboard/LoaderOverloadMetricsService.php)

### 4.1 Path parameters

| Name | Type | Required | Notes |
|------|------|----------|--------|
| `loader` | integer | Yes | **Implicit route model binding** to [`App\Models\Loader`](../../../app/Models/Loader.php). Missing or non-numeric id → **404** (model not found). **Soft-deleted** loaders are not resolved → **404** before controller logic. |

### 4.2 Query parameters

| Category | Parameters |
|----------|----------------|
| **Shared** | All of [§2](#2-shared-query-parameters-dashboardfilterresolver) **except** pagination (`page`, `per_page` are ignored). |

`loader_id` in query narrows the same loader’s detail query when combined with path `{loader}` via merged `filterContext` (redundant if equal).

### 4.3 Validation and errors

| Condition | HTTP | Response notes |
|-----------|------|------------------|
| Loader not found (binding) | **404** | Laravel JSON/HTML depending on `Accept`. |
| `loaderDetail` returns `null` (loader’s `siding_id` not in user’s `filteredSidingIds`, or `filteredSidingIds` empty) | **404** | `abort(404)` — default Laravel **404** body. |
| Auth / feature / permission / throttle | **401**, **404** (feature), **403**, **429** | Same as [§1](#1-transport-auth-and-middleware). |

### 4.4 Success response — `200 OK`

```json
{
  "filters": {
    "period": "last_month",
    "from": "2026-04-01",
    "to": "2026-04-30",
    "siding_ids": [1],
    "power_plant": null,
    "rake_number": null,
    "loader_id": null,
    "loader_operator": null,
    "underload_threshold": 1,
    "shift": null,
    "penalty_type": null,
    "rake_penalty_scope": "all"
  },
  "data": {
    "loader": {
      "id": 4,
      "name": "3 Pakur-JCB No 3",
      "siding": "Pakur Siding"
    },
    "operators": ["Pankaj", "Abdul"],
    "monthly": [
      {
        "month": "Apr 2026",
        "overload": 0,
        "underload": 175,
        "total": 175
      }
    ],
    "summary": {
      "total_wagons": 175,
      "overloaded_wagons": 0,
      "underloaded_wagons": 175,
      "overload_rate": 0,
      "underload_rate": 100,
      "overload_trend": 0,
      "underload_trend": 0
    }
  }
}
```

#### `data` object fields

| Field | Type | Description |
|-------|------|-------------|
| `loader` | object | `id` (int), `name` (string), `siding` (string). |
| `operators` | array of string | Distinct `loader_operator_name` values for this loader in range (may be empty). |
| `monthly` | array | One entry per calendar month in the result span; each row: |
| `monthly[].month` | string | Label, format **`M Y`** (e.g. `Apr 2026`). |
| `monthly[].overload` | integer | Count of overloaded wagons in that month. |
| `monthly[].underload` | integer | Count of underloaded wagons (threshold from `underload_threshold`). |
| `monthly[].total` | integer | Wagons counted in that month. |
| `summary` | object | Rolled up from `monthly`: |
| `summary.total_wagons` | integer | Sum of `monthly[].total`. |
| `summary.overloaded_wagons` | integer | Sum of `monthly[].overload`. |
| `summary.underloaded_wagons` | integer | Sum of `monthly[].underload`. |
| `summary.overload_rate` | float | Rounded 1 decimal; `0` if no wagons. |
| `summary.underload_rate` | float | Rounded 1 decimal; `0` if no wagons. |
| `summary.overload_trend` | integer | Difference last vs previous month overload counts (`0` if fewer than two months in `monthly`). |
| `summary.underload_trend` | integer | Same for underload. |

**Empty metrics:** `monthly` may be `[]` and summary zeros when there is no matching `wagon_loading` in range (still **200** if loader is in scope).

---

## 5. Endpoint 3: Operators index

**Full URL:** `GET {APP_URL}/api/v1/dashboard/loader-overload/operators`

**Controller:** [`MobileDashboardController::loaderOverloadOperatorsIndex`](../../../app/Http/Controllers/Api/Dashboard/MobileDashboardController.php)

**Service:** [`LoaderOverloadMetricsService::paginateOperatorsWithActivity`](../../../app/Services/Dashboard/LoaderOverloadMetricsService.php)

### 5.1 Path parameters

None.

### 5.2 Query parameters

| Category | Parameters |
|----------|----------------|
| **Shared** | All of [§2](#2-shared-query-parameters-dashboardfilterresolver). |
| **Pagination** | `page`, `per_page` ([§2.4](#24-pagination-loaders-index--operators-index-only)). |

### 5.3 Validation and errors

Same as loaders index: **401**, **404** (feature), **403**, **429**. No route-level **422**.

### 5.4 Success response — `200 OK`

```json
{
  "filters": {
    "period": "last_month",
    "from": "2026-04-01",
    "to": "2026-04-30",
    "siding_ids": [1],
    "power_plant": null,
    "rake_number": null,
    "loader_id": null,
    "loader_operator": null,
    "underload_threshold": 1,
    "shift": null,
    "penalty_type": null,
    "rake_penalty_scope": "all"
  },
  "data": [
    {
      "siding_id": 1,
      "siding": "Pakur Siding",
      "name": "Pankaj"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 10,
    "total": 15
  }
}
```

#### `data[]` row (operators index)

| Field | Type | Description |
|-------|------|-------------|
| `siding_id` | integer | Rake / wagon_loading siding. |
| `siding` | string | Siding name or `—`. |
| `name` | string | **`TRIM(loader_operator_name)`** from DB (use **exact** string for [operator show](#6-endpoint-4-operator-show), including case for DBs with case-sensitive collation). |

**Empty list:** `data: []`, `meta.total: 0` — still **200**.

---

## 6. Endpoint 4: Operator show

**Full URL:** `GET {APP_URL}/api/v1/dashboard/loader-overload/operators/show`

**Controller:** [`MobileDashboardController::loaderOverloadOperatorShow`](../../../app/Http/Controllers/Api/Dashboard/MobileDashboardController.php)

**Service:** [`LoaderOverloadMetricsService::operatorDetail`](../../../app/Services/Dashboard/LoaderOverloadMetricsService.php)

### 6.1 Path parameters

None.

### 6.2 Query parameters

| Name | Type | Required | Notes |
|------|------|----------|--------|
| `siding_id` | integer | **Yes** | Must be **&gt; 0** after cast. Must appear in **`filteredSidingIds`** after resolver intersection. |
| `operator` | string | **Yes** | Non-empty after `mb_trim`. Must match `wagon_loading.loader_operator_name` **exactly** (see [§8](#8-operator-name-matching-important)). URL-encode spaces and special characters in clients. |
| **Shared** | — | — | See [§2.3](#23-dashboard-filter-context-stored-in-resolver--echoed-in-filters): for **operator show**, only **date range**, **siding scope**, and **`underload_threshold`** change SQL inside [`operatorDetail`](../../../app/Services/Dashboard/LoaderOverloadMetricsService.php). |

### 6.3 Validation and errors

| Condition | HTTP | Message / behavior |
|-----------|------|--------------------|
| `siding_id` missing, `0`, negative, or non-numeric → `0`, **or** `operator` missing / whitespace-only | **422 Unprocessable Entity** | Plain-text / JSON `message`: **`siding_id and operator are required.`** (from `abort(422, '...')`). |
| `siding_id` **not** in `filteredSidingIds` | **422 Unprocessable Entity** | **`The selected siding is not in the current filter scope.`** |
| `operatorDetail` returns `null` (only when siding id not in user scope, operator blank after trim, or **no `sidings` row** for `siding_id`) | **404 Not Found** | Laravel default 404. **Wrong `operator` string** (casing/spelling) still returns **200** with empty `monthly` / zero summary — see [§8](#8-operator-name-matching-important). |
| Auth / feature / permission / throttle | **401**, **404** (feature), **403**, **429** | Same as [§1](#1-transport-auth-and-middleware). |

### 6.4 Success response — `200 OK`

```json
{
  "filters": {
    "period": "last_month",
    "from": "2026-04-01",
    "to": "2026-04-30",
    "siding_ids": [1],
    "power_plant": null,
    "rake_number": null,
    "loader_id": null,
    "loader_operator": null,
    "underload_threshold": 1,
    "shift": null,
    "penalty_type": null,
    "rake_penalty_scope": "all"
  },
  "data": {
    "operator": {
      "name": "Pankaj",
      "siding_id": 1,
      "siding": "Pakur Siding"
    },
    "loaders": [
      { "id": 4, "name": "3 Pakur-JCB No 3" },
      { "id": 6, "name": "6 Pakur-JCB No 6" }
    ],
    "monthly": [
      {
        "month": "Apr 2026",
        "overload": 0,
        "underload": 175,
        "total": 175
      }
    ],
    "summary": {
      "total_wagons": 175,
      "overloaded_wagons": 0,
      "underloaded_wagons": 175,
      "overload_rate": 0,
      "underload_rate": 100,
      "overload_trend": 0,
      "underload_trend": 0
    }
  }
}
```

#### `data` object fields

| Field | Type | Description |
|-------|------|-------------|
| `operator` | object | `name` (**trimmed request value**, not re-read from DB), `siding_id`, `siding` (name from `sidings` table). |
| `loaders` | array | Distinct loaders used by this operator on this siding in range: `{ "id", "name" }[]`, ordered by loader name. |
| `monthly` | array | Same shape as [loader show §4.4](#44-success-response--200-ok). |
| `summary` | object | Same shape as loader show summary. |

**200 with zeros:** If `operator` does not match any DB row (wrong case/spelling) but `siding_id` is valid, you can still get **200** with `loaders: []`, `monthly: []`, and all summary counts **0** — treat as “no data”; fix `operator` to match [operators index](#5-endpoint-3-operators-index) `name` exactly.

---

## 7. Shared success: `filters` object

For **all four** endpoints, `filters` is produced by [`serializeLoaderOverloadListFilters`](../../../app/Http/Controllers/Api/Dashboard/MobileDashboardController.php):

| JSON key | Type | Source |
|----------|------|--------|
| `period` | string | Resolved period key. |
| `from` | string (`Y-m-d`) | `$resolved['from']->toDateString()`. |
| `to` | string (`Y-m-d`) | `$resolved['to']->toDateString()`. |
| `siding_ids` | array of int | `array_values($resolved['filteredSidingIds'])`. |
| `power_plant` | string\|null | |
| `rake_number` | string\|null | |
| `loader_id` | int\|null | |
| `loader_operator` | string\|null | |
| `underload_threshold` | float | Echo of resolved percent (default `1`). |
| `shift` | string\|null | |
| `penalty_type` | int\|null | Echo of `penalty_type_id`. |
| `rake_penalty_scope` | string | `all` or `with_penalties`. |

**Not included** in these four responses (unlike legacy dashboard `serializeFilters`): `daily_rake_date`, `coal_transport_date`, `section`.

---

## 8. Operator name matching (important)

- **Operators index** returns `name` from **`TRIM(loader_operator_name)`** in SQL.
- **Operator show** filters with **`wl.loader_operator_name = <trimmed query parameter>`** (exact equality).

Therefore:

1. Copy **`operator`** from the **operators list** response (`data[].name`) character-for-character.
2. On **case-sensitive** databases (e.g. PostgreSQL default), **`Pankaj` ≠ `pankaj`** — wrong casing yields **empty** `monthly` / `loaders` with **200** and `operator.name` equal to whatever you sent.

---

## 9. Example error response bodies (Laravel defaults)

Exact JSON may vary with `Accept` and exception handler configuration. Typical shapes:

**422** (`abort(422, 'message')`):

```json
{
  "message": "siding_id and operator are required."
}
```

```json
{
  "message": "The selected siding is not in the current filter scope."
}
```

**403** (authorization):

```json
{
  "message": "This action is unauthorized."
}
```

**404** (missing `Loader` for `{loader}`, or `operatorDetail` null):

```json
{
  "message": "No query results for model [App\\Models\\Loader] {id}"
}
```

(or a generic “Not found” / empty body depending on handler.)

**401** (Sanctum): often `{"message":"Unauthenticated."}`.

---

## Quick reference: HTTP status matrix

| Status | Loaders index | Loader show | Operators index | Operator show |
|--------|---------------|-------------|-----------------|---------------|
| **200** | Yes | Yes | Yes | Yes |
| **401** | Yes | Yes | Yes | Yes |
| **403** | Yes | Yes | Yes | Yes |
| **404** | Feature only | Model not found; loader outside scope | Feature only | Feature; `operatorDetail` null (invalid scope / missing siding row) |
| **422** | No | No | No | **Yes** (missing/invalid `siding_id`/`operator`; siding out of scope) |
| **429** | Yes | Yes | Yes | Yes |

---

## Related code

| Piece | Path |
|-------|------|
| Routes | [`routes/api.php`](../../../routes/api.php) (`loader-overload/loaders`, …) |
| Controller | [`app/Http/Controllers/Api/Dashboard/MobileDashboardController.php`](../../../app/Http/Controllers/Api/Dashboard/MobileDashboardController.php) |
| Metrics | [`app/Services/Dashboard/LoaderOverloadMetricsService.php`](../../../app/Services/Dashboard/LoaderOverloadMetricsService.php) |
| Filters | [`app/Support/Dashboard/DashboardFilterResolver.php`](../../../app/Support/Dashboard/DashboardFilterResolver.php) |
| Web parity | [`app/Http/Controllers/Dashboard/LoaderOverloadWebController.php`](../../../app/Http/Controllers/Dashboard/LoaderOverloadWebController.php) |
| Route list (generated) | `php artisan route:list --path=api/v1/dashboard/loader-overload` |
