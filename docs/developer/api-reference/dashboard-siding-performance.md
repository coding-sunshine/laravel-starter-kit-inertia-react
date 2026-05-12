# Dashboard: siding overview + siding performance metrics (filters & responses)

This document describes:

- **Web JSON**: `GET /dashboard/siding-performance-metrics`
- **Mobile API**: `GET /api/v1/dashboard/siding-overview`

It also applies to the **mobile API** endpoint that returns the **same JSON as the web metrics endpoint**:

- **Mobile API (split charts)**: `GET /api/v1/dashboard/siding-performance-metrics`

> Note: There is no separate web JSON endpoint at `/dashboard/siding-overview`. The web “Siding overview” data is rendered as part of the Inertia dashboard page (`GET /dashboard`). Mobile uses `GET /api/v1/dashboard/siding-overview`.

## Authentication & authorization

### Web: `GET /dashboard/siding-performance-metrics`

- **Auth**: web session (`web`, `auth`, `verified`)
- **Authorization**: requires both permissions (unless user can `bypass-permissions`):
  - `sections.dashboard.view`
  - `dashboard.widgets.siding_overview_performance`

### Mobile API: `GET /api/v1/dashboard/siding-overview` and `GET /api/v1/dashboard/siding-performance-metrics`

- **Auth**: `auth:sanctum`
- **Feature flag**: `feature:api_access` (otherwise the route is not available)
- **Authorization**:
  - `siding-overview`: does not re-check widget permissions in the controller; it returns data for whatever the authenticated user is allowed to scope via `DashboardFilterResolver`.
  - `siding-performance-metrics`: delegates to the web controller method, so it enforces the same permissions as the web metrics endpoint (see above).

## Common filters (query parameters)

Both endpoints reuse the **dashboard filter resolver** (`App\Support\Dashboard\DashboardFilterResolver`) for scoping.

### Date range

#### `period`

- **Type**: string
- **Default**: `yesterday`
- **Allowed values**:
  - `yesterday`
  - `today`
  - `week`
  - `last_week`
  - `month`
  - `last_month`
  - `custom`

#### `from`, `to`

- **Used only when**: `period=custom`
- **Type**: date string
- **Format**: `YYYY-MM-DD` (example: `2026-05-11`)
- **Notes**:
  - If `period != custom`, `from`/`to` are ignored.
  - If omitted while `period=custom`, the backend falls back to “start of current month” → “today” (inclusive).

### Siding scope

#### `siding_ids` / `siding_id`

- **Type**:
  - `siding_ids`: comma-separated list of integers (example: `1,2,3`)
  - `siding_id`: single integer (example: `2`)
- **Behavior**: both forms are accepted; the resolver intersects requested ids with the user’s accessible sidings.
- **Default**: all accessible sidings

### Executive-style scope filters

These are optional and default to “no filter”.

#### `power_plant`

- **Type**: string
- **Value source**: dynamic (power plant names/stations). For mobile apps, fetch the allowed options from:
  - `GET /api/v1/dashboard/filter-options?section=siding-overview` (or `executive-overview`)

#### `rake_number`

- **Type**: string
- **Behavior**: partial match (LIKE) in relevant queries

#### `loader_id`

- **Type**: integer
- **Value source**: dynamic. For mobile apps, fetch allowed options from:
  - `GET /api/v1/dashboard/filter-options?section=loader-overload` (or relevant section)

#### `loader_operator`

- **Type**: string
- **Behavior**: trimmed; empty string becomes null
- **Value source**: dynamic per loader; exposed via `ExecutiveDashboardController::buildFilterOptions()` as `loaderOperatorsByLoader` on the web dashboard

#### `shift`

- **Type**: string
- **Allowed values**: `'1'`, `'2'`, `'3'`
- **Labels**: Shift 1, Shift 2, Shift 3

#### `penalty_type`

- **Type**: integer
- **Value source**: dynamic. For mobile apps, fetch options from:
  - `GET /api/v1/dashboard/filter-options?section=siding-overview` (penalty types are included there)

#### `rake_penalty_scope`

- **Type**: string
- **Default**: `all`
- **Allowed values**:
  - `all`
  - `with_penalties`

#### `underload_threshold`

- **Type**: number
- **Range**: `0` to `100` (clamped)
- **Default**: `1.0`

### Single-day filters used by other dashboard sections (still accepted)

These are included in the shared resolver output and can appear in requests, though they only affect some widgets.

#### `daily_rake_date`, `coal_transport_date`

- **Type**: date string
- **Format**: `YYYY-MM-DD`
- **Default**: yesterday (in app timezone)

## Split-chart overrides (only for siding performance metrics)

Applies to:

- Web: `GET /dashboard/siding-performance-metrics`
- Mobile API: `GET /api/v1/dashboard/siding-performance-metrics`

You can override the date range **independently** for the two series:

- **rakes series** uses prefix `sp_rakes`
- **penalties series** uses prefix `sp_penalty`

### `{prefix}_period`

- **Type**: string
- **Key**: `sp_rakes_period` or `sp_penalty_period`
- **Default**: `main` (uses resolved `from`/`to` from the main dashboard period)
- **Allowed values**:
  - `main`
  - `yesterday`
  - `today`
  - `month`
  - `last_month`
  - `custom`

### `{prefix}_from`, `{prefix}_to` (only when `{prefix}_period=custom`)

- **Type**: date string
- **Format**: `YYYY-MM-DD`
- **Required when**: `{prefix}_period=custom`
- **Constraints**:
  - `from <= to` (inclusive)
  - Max range length: **731 days** (inclusive)

## Endpoints

## 1) Web JSON: `GET /dashboard/siding-performance-metrics`

### Success response (200)

Returns a plain JSON body:

```json
{
  "rakes": [
    { "name": "Siding A", "rakes": 12 },
    { "name": "Siding B", "rakes": 3 }
  ],
  "penalties": [
    { "name": "Siding A", "penalty_amount": 12000.5 },
    { "name": "Siding B", "penalty_amount": 0 }
  ]
}
```

### Failure responses

Behavior depends on whether the request expects JSON (`Accept: application/json`):

- **401 (unauthenticated)**:
  - Web routes often redirect to login when not requesting JSON.
- **403 (forbidden)**:
  - Missing `sections.dashboard.view` or `dashboard.widgets.siding_overview_performance` (unless bypass).
- **422 (validation / bad input)**:
  - Examples:
    - `Invalid sp_rakes_period.`
    - `Custom range requires sp_rakes_from and sp_rakes_to.`
    - `Custom range from must be before or equal to to.`
    - `Custom range may not exceed 731 days.`

When JSON is expected, the app uses `application/problem+json` (see `docs/developer/api-reference/README.md`) for error payloads.

## 2) Mobile API: `GET /api/v1/dashboard/siding-overview`

### Success response (200)

Returns:

- `filters`: the resolved filters (dates are `YYYY-MM-DD`)
- `data`: dashboard widget payloads for the siding overview section

#### Example success body

Below is an example response (values are illustrative; shapes/keys match the API):

```json
{
  "filters": {
    "period": "last_month",
    "from": "2026-04-01",
    "to": "2026-04-30",
    "siding_ids": [1, 2, 3],
    "power_plant": null,
    "rake_number": null,
    "loader_id": null,
    "loader_operator": null,
    "underload_threshold": 1,
    "shift": null,
    "penalty_type": null,
    "daily_rake_date": "2026-05-10",
    "coal_transport_date": "2026-05-10",
    "section": "executive-overview"
  },
  "data": {
    "kpis": {
      "rakesDispatchedToday": 400,
      "coalDispatchedToday": 1461630.12,
      "totalPenaltyThisMonth": 316851.024,
      "predictedPenaltyRisk": 201625.2,
      "avgLoadingTimeMinutes": 111,
      "trucksReceivedToday": 96925
    },
    "sidingStocks": {
      "1": {
        "siding_id": 1,
        "opening_balance_mt": 34368.11,
        "closing_balance_mt": 30540.09,
        "total_rakes": 96,
        "received_mt": 331236.04,
        "dispatched_mt": 345534.56,
        "last_receipt_at": "2026-05-04T19:33:59+05:30",
        "last_dispatch_at": "2026-05-07T14:14:14+05:30"
      },
      "2": {
        "siding_id": 2,
        "opening_balance_mt": 19499.65,
        "closing_balance_mt": 19528.29,
        "total_rakes": 150,
        "received_mt": 555148.57,
        "dispatched_mt": 743853.62,
        "last_receipt_at": "2026-05-04T14:00:05+05:30",
        "last_dispatch_at": "2026-04-30T19:20:14+05:30"
      },
      "3": {
        "siding_id": 3,
        "opening_balance_mt": 547.12,
        "closing_balance_mt": 544.13,
        "total_rakes": 160,
        "received_mt": 659657.68,
        "dispatched_mt": 613769.72,
        "last_receipt_at": "2026-05-01T12:38:18+05:30",
        "last_dispatch_at": "2026-05-01T13:05:01+05:30"
      }
    },
    "sidingPerformance": [
      {
        "name": "Pakur Siding",
        "rakes": 85,
        "penalties": 19,
        "penalty_amount": 198500.64,
        "penalty_rate": 17.6
      },
      {
        "name": "Dumka Siding",
        "rakes": 148,
        "penalties": 21,
        "penalty_amount": 52562.8,
        "penalty_rate": 14.2
      },
      {
        "name": "Kurwa Siding",
        "rakes": 167,
        "penalties": 30,
        "penalty_amount": 50699.44,
        "penalty_rate": 18
      }
    ],
    "penaltyTrendDaily": {
      "series": [
        { "key": "siding_1", "label": "Pakur Siding", "siding_id": 1 },
        { "key": "siding_2", "label": "Dumka Siding", "siding_id": 2 },
        { "key": "siding_3", "label": "Kurwa Siding", "siding_id": 3 }
      ],
      "points": [
        {
          "date": "2026-04-01",
          "label": "01 Apr 2026",
          "siding_1": 0,
          "siding_2": 1694.7,
          "siding_3": 1723.68
        }
      ]
    },
    "powerPlantDispatch": [
      {
        "name": "Sagardighi TPP",
        "rakes": 159,
        "weight_mt": 572304.85,
        "sidings": {
          "Pakur Siding": { "rakes": 64, "weight_mt": 229004.68 },
          "Kurwa Siding": { "rakes": 93, "weight_mt": 336232.13 },
          "Dumka Siding": { "rakes": 2, "weight_mt": 7068.04 }
        }
      }
    ]
  }
}
```

#### `penaltyTrendDaily` notes (important)

- **`penaltyTrendDaily.series`**: list of sidings with stable keys (`siding_{id}`) used as property names in each point.
- **`penaltyTrendDaily.points[]`**:
  - `date`: `YYYY-MM-DD`
  - `label`: human-readable label (example: `01 Apr 2026`)
  - dynamic numeric fields keyed by `series[*].key` (example: `siding_1`, `siding_2`, …), representing the penalty amount for that day.

### Failure responses

- **401 (unauthenticated)**: missing/invalid Sanctum token
- **404 (not found)**: `feature:api_access` disabled for the user
- **422 (unprocessable)**: invalid inputs (date parsing / invalid values)
- **500 (server error)**: unexpected exception

When the request expects JSON, errors are formatted as `application/problem+json` (see `docs/developer/api-reference/README.md`).

## 3) Mobile API: `GET /api/v1/dashboard/siding-performance-metrics`

This is the mobile-friendly version of the split chart endpoint. It returns the **same response body** as the web endpoint.

### Success response (200)

Same as web `GET /dashboard/siding-performance-metrics`:

```json
{
  "rakes": [{ "name": "Siding A", "rakes": 12 }],
  "penalties": [{ "name": "Siding A", "penalty_amount": 12000.5 }]
}
```

### Failure responses

- **401**: missing/invalid Sanctum token
- **404**: `feature:api_access` disabled for the user
- **403**: missing dashboard permissions (delegates to web controller)
- **422**: invalid `sp_*` values (messages listed above)

