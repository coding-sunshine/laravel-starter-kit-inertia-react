# VehicleDispatchController

**Class**: `App\Http\Controllers\VehicleDispatchController`

**Routes**: See [routes.md](../../api-reference/routes.md#vehicledispatchcontroller).

## `reconciliationReport`

`GET vehicle-dispatch/reconciliation-report`

JSON report for coal-site dispatch vs railway-siding receipt. Uses `App\Services\DispatchReconciliationReportService`.

**Query parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `siding_id` | int | PKUR / KURWA / DUMK siding (user must have access) |
| `from` | date `Y-m-d` | Range start |
| `to` | date `Y-m-d` | Range end (max 93 days) |

**Response**

- `days[]`: newest first; each has `date`, `shifts` (1–3), `day_total`
- Per shift: `dispatch_*` from `siding_vehicle_dispatches`, `received_*` from `daily_vehicle_entries` (`road_dispatch`), `in_transit_*` = dispatch − received (may be negative when received exceeds dispatch). Day and range totals sum shift values algebraically.
- `range_total.stock_updated_mt`: sum of `net_wt` where `status = completed`
- `range_total.in_progress_gross_mt`: sum of `gross_wt` where `status != completed`

**Authorization**: User must have **Access to all siding shift data** (`users.access_to_siding_shift_data`, same as road-dispatch shift report). Not gated by a separate route permission.
