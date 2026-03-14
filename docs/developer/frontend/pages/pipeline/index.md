# Pipeline Index Page

## Purpose

Displays the sales pipeline in either a Kanban board view or a list view, with sales grouped by status.

## Location

`resources/js/pages/pipeline/index.tsx`

## Route Information

- **URL**: `/pipeline`
- **Route Name**: `pipeline.index`
- **HTTP Method**: GET
- **Middleware**: `auth`, `verified`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `grouped` | `Record<string, SalePipelineItem[]>` | Sales grouped by status key |
| `statuses` | `string[]` | Unique list of status values present |
| `total` | `number` | Total count of all sales |

## User Flow

1. User navigates to `/pipeline`
2. Kanban view is shown by default with columns for each status
3. User can toggle to list view using the List button
4. Each sale card shows ID, status badge, lot reference, comms-in total and created date

## Related Components

- **Controller**: `PipelineController@index`
- **Route**: `pipeline.index`

## Implementation Details

- View toggle state is managed locally via `useState`
- Kanban columns are horizontally scrollable for many statuses
- Status badge colors are mapped from a static `STATUS_COLORS` lookup
