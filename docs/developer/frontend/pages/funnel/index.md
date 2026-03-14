# Funnel Index Page

## Purpose

Displays a visual conversion funnel showing counts at each stage: leads, prospects, reservations, and sales.

## Location

`resources/js/pages/funnel/index.tsx`

## Route Information

- **URL**: `/funnel`
- **Route Name**: `funnel.index`
- **HTTP Method**: GET
- **Middleware**: `auth`, `verified`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `stages` | `FunnelStage[]` | Array of stages with `label`, `count`, and `key` |

## User Flow

1. User navigates to `/funnel`
2. Horizontal bar chart shows each pipeline stage
3. Bars are sized proportionally relative to the maximum stage count
4. Conversion rates between stages are shown as percentage labels

## Related Components

- **Controller**: `FunnelController@index`
- **Route**: `funnel.index`

## Implementation Details

- Bar widths are calculated as a percentage of the maximum stage count
- Minimum bar width of 10% ensures all stages are visible
- Conversion rate is calculated between adjacent stages
- Empty state is shown when all stage counts are zero
