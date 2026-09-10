# Mobile API: dashboard RR upload coverage

Full reference for **`GET /api/v1/dashboard/rr-upload-coverage`**: Sanctum-authenticated JSON used by native clients for the **same RR rake coverage aggregate** as the web dashboard siding overview widget (“RR Upload Coverage”). Server logic is [`ExecutiveDashboardController::buildPenaltyControlRrCoverage`](../../../app/Http/Controllers/Dashboard/ExecutiveDashboardController.php); entrypoint is [`MobileDashboardController::rrUploadCoverage`](../../../app/Http/Controllers/Api/Dashboard/MobileDashboardController.php).

| Method | Path | Route name |
|--------|------|------------|
| `GET` | `/api/v1/dashboard/rr-upload-coverage` | `api.v1.dashboard.rr-upload-coverage` |

**Base URL:** `{APP_URL}/api/v1/dashboard/rr-upload-coverage`

---

## Table of contents

1. [Purpose and data semantics](#1-purpose-and-data-semantics)
2. [Transport, auth, and middleware](#2-transport-auth-and-middleware)
3. [Authorization](#3-authorization)
4. [Query parameters (all dashboard filters)](#4-query-parameters-all-dashboard-filters)
5. [Which inputs affect RR coverage vs response echo only](#5-which-inputs-affect-rr-coverage-vs-response-echo-only)
6. [Success response (200)](#6-success-response-200)
7. [Edge cases and empty results](#7-edge-cases-and-empty-results)
8. [Errors and HTTP status matrix](#8-errors-and-http-status-matrix)
9. [Example payloads](#9-example-payloads)
10. [Related endpoints](#10-related-endpoints)

---

## 1. Purpose and data semantics

The endpoint returns:

- **`filters`**: Resolved dashboard scope as JSON-safe scalars/dates (echo of what [`DashboardFilterResolver::resolve`](../../../app/Support/Dashboard/DashboardFilterResolver.php) computed).
- **`data.penaltyControlRrCoverage`**: Counts of rakes **with a loading date in the resolved `from`–`to` range** (inclusive, date-only comparison per DB driver), scoped to **`filteredSidingIds`**, split into totals and **per siding**.

Rakes included:

- `siding_id` in the filtered siding list.
- `loading_date` **not null**.
- `loading_date` **between** resolved `from` and `to` (as dates).

“With RR” means the rake has an associated RR document (`whereHas('rrDocument')` on the rake query). Exact SQL uses [`buildPenaltyControlRrCoverage`](../../../app/Http/Controllers/Dashboard/ExecutiveDashboardController.php); see PHPDoc there for the returned array shape.

TypeScript equivalents (frontend): [`PenaltyControlRrCoverage`](../../../resources/js/pages/dashboard/types.ts), [`PenaltyControlRrCoverageSidingRow`](../../../resources/js/pages/dashboard/types.ts).

---

## 2. Transport, auth, and middleware

Defined in [`routes/api.php`](../../../routes/api.php) under the versioned API group:

| Layer | Value |
|-------|--------|
| Prefix | `/api/v1` |
| HTTP method | **GET** only (no request body) |
| Middleware (typical order) | `api`, `throttle:60,1`, `auth:sanctum`, `feature:api_access` |

### 2.1 Recommended headers

| Header | Required | Notes |
|--------|----------|--------|
| `Authorization` | Yes | `Bearer {personal_access_token}` (Laravel Sanctum). |
| `Accept` | Recommended | `application/json` for consistent JSON error bodies where applicable. |

### 2.2 Rate limiting

| HTTP | When |
|------|------|
| **429 Too Many Requests** | More than **60** requests per minute per throttle key (`throttle:60,1`). |

---

## 3. Authorization

Checks run inside [`MobileDashboardController::rrUploadCoverage`](../../../app/Http/Controllers/Api/Dashboard/MobileDashboardController.php) **after** Sanctum and **`feature:api_access`**.

| Order | Rule | HTTP if failed |
|-------|------|----------------|
| 1 | Authenticated user | **403** from `abort_unless($user !== null, 403)` — in practice guests usually receive **401** from Sanctum first. |
| 2 | `bypass-permissions` **or** `sections.dashboard.view` | **403 Forbidden** |
| 3 | `bypass-permissions` **or** `dashboard.widgets.siding_overview_rr_rake_coverage` | **403 Forbidden** |

Users with **`bypass-permissions`** satisfy both permission checks.

---

## 4. Query parameters (all dashboard filters)

All of the following are read by [`DashboardFilterResolver::resolve`](../../../app/Support/Dashboard/DashboardFilterResolver.php). Parameters may be supplied as **query string** or request input (Laravel merges them for `$request->input()`).

### 4.1 Date range

| Name | Type | Required | Default | Notes |
|------|------|----------|---------|--------|
| `period` | string | No | **`month`** | See [§4.1.1 Presets](#411-period-presets). |
| `from` | date string | Only when `period=custom` | If omitted under `custom`: **start of current month** | Format **`YYYY-MM-DD`**, interpreted in `config('app.timezone')`. When **`period` ≠ `custom`**, the resolver **strips** `from` / `to` from the request and ignores them. |
| `to` | date string | Only when `period=custom` | If omitted under `custom`: **end of today** | Same as `from`. |

#### 4.1.1 `period` presets

Handled by [`boundsForPeriod`](../../../app/Support/Dashboard/DashboardFilterResolver.php) (app timezone):

| `period` | Inclusive bounds (conceptual) |
|----------|----------------------------------|
| `yesterday` | Previous calendar day (start → end). |
| `today` | Today (start of day → end of day). |
| `week` | Start of **current** week → **end of today** (not end of week). |
| `last_week` | Previous week (week start → week end). |
| `month` | Start of **current** month → **end of today**. |
| `last_month` | Full previous calendar month. |
| `custom` | Uses `from` / `to` as described above. |
| **Any other string** | Falls through to the **`default`** branch: same bounds as **`yesterday`**. |

### 4.2 Siding scope

| Name | Type | Required | Default | Notes |
|------|------|----------|---------|--------|
| `siding_ids` | string or array | No | All sidings the user may access | **String:** comma-separated positive integers (`1,2,3`). **Array:** acceptable Laravel array input. Parsed and **intersected** with accessible sidings (super-admin → all sidings; otherwise the user’s scoped sidings via [`accessibleSidings()`](../../../app/Models/Concerns/HasRRMCSAuthorization.php) and related rules). |
| `siding_id` | integer | No | — | Alternative single-siding filter; same intersection rules. |

### 4.3 Operational / chart filters (passed through resolver)

These populate `filterContext` and appear in the serialized **`filters`** response. They **do not** change [`buildPenaltyControlRrCoverage`](../../../app/Http/Controllers/Dashboard/ExecutiveDashboardController.php) (see [§5](#5-which-inputs-affect-rr-coverage-vs-response-echo-only)).

| Name | Type | Default | Notes |
|------|------|---------|--------|
| `power_plant` | string | `null` if omitted | Non-empty string filter value when provided. |
| `rake_number` | string | `null` | Partial match semantics apply on **other** dashboard widgets; not used inside RR coverage query. |
| `loader_id` | integer | `null` | Positive integer when provided. |
| `loader_operator` | string | `null` | Trimmed; blank becomes `null`. |
| `shift` | string | `null` | Typical values `'1'`, `'2'`, `'3'`. |
| `penalty_type` | integer | `null` | Penalty type id when provided. |
| `rake_penalty_scope` | string | `all` | **`all`** or **`with_penalties`**; invalid values → **`all`**. **Not** included in the top-level `filters` JSON object (only affects internal context); see [`serializeFilters`](../../../app/Http/Controllers/Api/Dashboard/MobileDashboardController.php). |
| `underload_threshold` | number | `1.0` | Clamped to **0–100**. |

### 4.4 Extra dates (resolver only)

Resolved for dashboard consistency; included in **`filters`** response; **not** used by `buildPenaltyControlRrCoverage`.

| Name | Type | Default if omitted | Notes |
|------|------|-------------------|--------|
| `daily_rake_date` | `YYYY-MM-DD` | Yesterday (start of day) | Parsed in app timezone. |
| `coal_transport_date` | `YYYY-MM-DD` | Yesterday (start of day) | Parsed in app timezone. |

### 4.5 Active dashboard section (resolver only)

| Name | Type | Default | Notes |
|------|------|---------|--------|
| `section` | string | First **permitted** section id, or `executive-overview` | Must be both **allowed** and **permitted** for the user (see [`DashboardWidgetPermissions`](../../../app/Support/Dashboard/DashboardWidgetPermissions.php)). Affects **`filters.section`** echo only; **does not** affect RR coverage SQL. |

Allowed section ids (must match user permissions): `executive-overview`, `siding-overview`, `operations`, `penalty-control`, `rake-performance`, `loader-overload`, `power-plant`.

---

## 5. Which inputs affect RR coverage vs response echo only

| Inputs | Affect `penaltyControlRrCoverage`? |
|--------|-----------------------------------|
| `period`, `from`, `to` | **Yes** (resolved date bounds). |
| `siding_ids`, `siding_id` | **Yes** (after intersection with accessible sidings). |
| `power_plant`, `rake_number`, `loader_id`, `loader_operator`, `shift`, `penalty_type`, `rake_penalty_scope`, `underload_threshold` | **No** (echoed only indirectly via `filterContext` / behavior of other endpoints; RR coverage method does not receive `filterContext`). |
| `daily_rake_date`, `coal_transport_date`, `section` | **No** (appear in `filters` JSON for client parity). |

---

## 6. Success response (200)

### 6.1 Envelope

This route returns a **plain JSON object** (not the `essa/api-toolkit` `{ status, message, data }` wrapper used by some other API resources):

```json
{
  "filters": { ... },
  "data": {
    "penaltyControlRrCoverage": { ... }
  }
}
```

### 6.2 `filters` object

Shape from [`MobileDashboardController::serializeFilters`](../../../app/Http/Controllers/Api/Dashboard/MobileDashboardController.php):

| Field | Type | Description |
|-------|------|-------------|
| `period` | string | Resolved period key. |
| `from` | string | `YYYY-MM-DD` (Carbon `toDateString()`). |
| `to` | string | `YYYY-MM-DD` end bound’s calendar date string. |
| `siding_ids` | array of int | Resolved **filtered** siding ids. |
| `power_plant` | string or null | Echo. |
| `rake_number` | string or null | Echo. |
| `loader_id` | int or null | Echo. |
| `loader_operator` | string or null | Echo. |
| `underload_threshold` | number | Echo (clamped). |
| `shift` | string or null | Echo. |
| `penalty_type` | int or null | Echo (penalty type id). |
| `daily_rake_date` | string | `YYYY-MM-DD`. |
| `coal_transport_date` | string | `YYYY-MM-DD`. |
| `section` | string | Resolved section id. |

### 6.3 `data.penaltyControlRrCoverage`

| Field | Type | Description |
|-------|------|-------------|
| `total_rakes` | int | All eligible rakes in range and siding scope. |
| `rakes_with_rr` | int | Subset with RR document. |
| `rakes_without_rr` | int | `max(0, total_rakes - rakes_with_rr)` (global). |
| `by_siding` | array | One row per siding in **`filteredSidingIds`** (even if zero rakes), sorted by **`siding_name`**. |

Each `by_siding[]` row:

| Field | Type |
|-------|------|
| `siding_id` | int |
| `siding_name` | string |
| `total_rakes` | int |
| `rakes_with_rr` | int |
| `rakes_without_rr` | int |

If **`filteredSidingIds`** is empty, the payload is all zeros and **`by_siding`** is `[]` (see implementation).

---

## 7. Edge cases and empty results

| Scenario | Behavior |
|----------|----------|
| User has access to sidings but passes **only** out-of-scope `siding_ids` | Intersection → **`filteredSidingIds` = []** → coverage zeros, **`by_siding` = []**. |
| User has **no** accessible sidings | Same as above. |
| Valid scope but **no** rakes with `loading_date` in range | `total_rakes = 0`, etc.; **`by_siding`** still lists sidings with zeros if ids non-empty. |
| Custom period with **invalid** `from`/`to` strings | Carbon may mis-parse; clients should send valid **`YYYY-MM-DD`**. |

---

## 8. Errors and HTTP status matrix

| HTTP | When | Typical client action |
|------|------|------------------------|
| **401 Unauthorized** | Missing/invalid bearer token. | Obtain or refresh Sanctum token. |
| **403 Forbidden** | Authenticated but fails dashboard view or RR widget permission (unless bypass). | Grant `sections.dashboard.view` and `dashboard.widgets.siding_overview_rr_rake_coverage`, or use a bypass-capable role (e.g. super-admin pattern). |
| **404 Not Found** | Often **`feature:api_access`** inactive for the user ([`EnsureFeatureActive`](../../../app/Http/Middleware/EnsureFeatureActive.php) aborts 404). Can also mean wrong URL or stale **`route:cache`** on the server. | Enable API access for the user; call **`GET /api/v1/dashboard/rr-upload-coverage`** (with `/api` prefix); run **`php artisan route:clear`** (or rebuild route cache) on deploy. |
| **429 Too Many Requests** | Exceeded throttle. | Back off; respect `Retry-After` if present. |
| **500** | Server/database failure. | Retry; check logs. |

**Note:** Laravel’s **404** from `abort(404)` and a true **“route not found”** can look similar in clients. Confirm the path includes **`/api`**, the route is deployed, and route cache is current.

---

## 9. Example payloads

### 9.1 Success (200) — illustrative

```json
{
  "filters": {
    "period": "month",
    "from": "2026-05-01",
    "to": "2026-05-14",
    "siding_ids": [1, 2],
    "power_plant": null,
    "rake_number": null,
    "loader_id": null,
    "loader_operator": null,
    "underload_threshold": 1,
    "shift": null,
    "penalty_type": null,
    "daily_rake_date": "2026-05-13",
    "coal_transport_date": "2026-05-13",
    "section": "siding-overview"
  },
  "data": {
    "penaltyControlRrCoverage": {
      "total_rakes": 120,
      "rakes_with_rr": 95,
      "rakes_without_rr": 25,
      "by_siding": [
        {
          "siding_id": 1,
          "siding_name": "Alpha Siding",
          "total_rakes": 70,
          "rakes_with_rr": 60,
          "rakes_without_rr": 10
        },
        {
          "siding_id": 2,
          "siding_name": "Beta Siding",
          "total_rakes": 50,
          "rakes_with_rr": 35,
          "rakes_without_rr": 15
        }
      ]
    }
  }
}
```

### 9.2 Forbidden (403) — default Laravel JSON

```json
{
  "message": "This action is unauthorized."
}
```

Exact keys may vary if exception rendering is customized.

### 9.3 Unauthorized (401)

Depends on Sanctum / `auth:sanctum` configuration; often a JSON **problem** payload when `Accept: application/json` is set.

---

## 10. Related endpoints

| Endpoint | Use |
|----------|-----|
| `GET /api/v1/dashboard/filter-options?section=siding-overview` | Power plants, penalty types, and other pickers aligned with siding overview filters. |
| `GET /api/v1/dashboard/siding-overview` | Larger aggregate payload (includes other widgets); prefer this dedicated route for RR-only traffic. |
| Web `GET /dashboard` + siding overview Inertia props | Same `penaltyControlRrCoverage` concept loaded as a deferred section prop on the browser dashboard. |

---

## Source files

- Route: [`routes/api.php`](../../../routes/api.php) (`rr-upload-coverage`).
- Controller: [`MobileDashboardController::rrUploadCoverage`](../../../app/Http/Controllers/Api/Dashboard/MobileDashboardController.php).
- Filters: [`DashboardFilterResolver`](../../../app/Support/Dashboard/DashboardFilterResolver.php).
- Aggregation: [`ExecutiveDashboardController::buildPenaltyControlRrCoverage`](../../../app/Http/Controllers/Dashboard/ExecutiveDashboardController.php).
