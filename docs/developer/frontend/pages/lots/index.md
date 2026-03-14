# lots/index

## Purpose

Displays the Lots DataTable — a paginated, searchable, filterable inventory of property lots with quick views (Available, Reserved, Sold, All).

## Location

`resources/js/pages/lots/index.tsx`

## Route Information

- **URL**: `/lots`
- **Route Name**: `lots.table`
- **HTTP Method**: GET
- **Middleware**: `auth`, `verified`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `tableData` | `DataTableResponse<LotDataTable>` | Paginated lot rows with meta |
| `searchableColumns` | `string[]` | List of columns that support search |

## User Flow

1. User navigates to `/lots`
2. DataTable renders with lot inventory (title, project, level, bed/bath/car, size, price, status)
3. User can filter by status (available/reserved/sold), bedrooms, bathrooms
4. Quick views pre-filter by lot status

## Related Components

- **Controller**: `LotsTableController@index`
- **DataTable**: `LotDataTable` (PHP)
- **Route**: `lots.table`

## Implementation Details

Uses the shared `<DataTable>` React component from `@/components/data-table/data-table`. Supports column visibility, ordering, resizing, pinning, quick views, exports, and filters.
