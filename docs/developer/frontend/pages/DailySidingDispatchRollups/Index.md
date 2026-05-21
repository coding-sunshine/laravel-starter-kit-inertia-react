# DailySidingDispatchRollups/Index

## Purpose

Super-admin-only maintenance UI (**bookmark `GET /daily-siding-dispatch-rollups`**, no sidebar):

1. **Date range** filter → **day-wise table**: each calendar date shows rollup aggregates + **Source data** (whether dispatches exist) + **Recalculate** + **View buckets**.
2. **Bucket detail** (when **`filters.detail_date`** set): full **`daily_siding_vehicle_dispatch_rollups`** columns for that date, paginated (**`detail_page`**).
3. **Recalculate any calendar date**: separate section for late / off-range uploads; server validates dispatch rows exist before rebuild.

Shows **`flash.success`** and validation errors on **`date`** (Inertia **`errors.date`**).

## Location

`resources/js/pages/DailySidingDispatchRollups/Index.tsx`

## Routes

- **`daily-siding-dispatch-rollups.index`** — `date_from`, `date_to`, optional `detail_date`, `page`, `detail_page`
- **`daily-siding-dispatch-rollups.recalculate`** — POST **`date`** plus optional **`date_from`**, **`date_to`**, **`detail_date`** to preserve filters on redirect

Wayfinder: `@/routes/daily-siding-dispatch-rollups`

## Props

| Prop | Type | Notes |
|------|------|--------|
| `days` | Laravel paginator | **`DaySummaryRow[]`**, **50 days** per **`page`** |
| `detailRollups` | paginator \| `null` | Full bucket rows when **`detail_date`** active |
| `filters` | `{ date_from, date_to, detail_date }` | |
| `flash.success` | `string \| null` | After successful recalculation |

## Related

- **Controller**: **`DailySidingVehicleDispatchRollupAdminController`**
- **Action**: **`RecalculateDailySidingVehicleDispatchRollups`**
