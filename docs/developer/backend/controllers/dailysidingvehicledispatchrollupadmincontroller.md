# DailySidingVehicleDispatchRollupAdminController

## Purpose

Super-admin-only Inertia UI:

1. **Date range** (`date_from`, `date_to`, max **366** days): paginated **calendar-day list** (**50 days per page**, newest first). Each row shows rollup aggregates (`bucket_count`, `total_dispatches`, `total_qty_mineral_mt`) and whether **`siding_vehicle_dispatches`** has rows for that **`whereDate('issued_on', …)`**.
2. Optional **`detail_date`** (must fall in range): paginated **bucket-level** rollup rows (**50 per page**, query param **`detail_page`**).
3. **POST recalculate**: **`RecalculateDailySidingVehicleDispatchRollups::handle($date, $date)`** only if **`VehicleDispatch`** has at least one non-null **`issued_on`** row on that calendar date; otherwise **`ValidationException`** with **`No dispatch records have been uploaded for this date.`**

Not exposed in the sidebar; **`AutoPermissionMiddleware`** has no `sections.*` mapping — gate is **`User::isSuperAdmin()`** only.

## Location

`app/Http/Controllers/DailySidingVehicleDispatchRollupAdminController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|-------------|-------|---------|
| `index` | GET | `daily-siding-dispatch-rollups.index` | **`days`** paginator + optional **`detailRollups`** paginator |
| `recalculate` | POST | `daily-siding-dispatch-rollups.recalculate` | Single-day rebuild; optional **`date_from`**, **`date_to`**, **`detail_date`** echoed back on redirect |

## Routes

- `daily-siding-dispatch-rollups.index`: `GET daily-siding-dispatch-rollups`
- `daily-siding-dispatch-rollups.recalculate`: `POST daily-siding-dispatch-rollups/recalculate`

### Query parameters (`index`)

| Parameter | Notes |
|-----------|--------|
| `date_from`, `date_to` | Optional; default **today − 13 days** through **today** (app timezone) |
| `detail_date` | Optional; if outside range, ignored |
| `page` | Day-list page |
| `detail_page` | Bucket-detail page |

## Actions Used

- **`RecalculateDailySidingVehicleDispatchRollups`** — `handle($date, $date)`

## Validation

- **Index**: optional dates; range length ≤ **366** days.
- **Recalculate**: **`date`** required; rejects when no **`VehicleDispatch`** source rows exist for that calendar date.

## Related Components

- **Pages**: **`DailySidingDispatchRollups/Index`**
- **Models**: **`DailySidingVehicleDispatchRollup`**, **`VehicleDispatch`**, **`Siding`**
