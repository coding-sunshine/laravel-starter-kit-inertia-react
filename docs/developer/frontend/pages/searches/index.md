# searches/index

## Purpose

Displays the Property Searches DataTable — a paginated, searchable list of property search records.

## Location

`resources/js/pages/searches/index.tsx`

## Route Information

- **URL**: `/searches`
- **Route Name**: `searches.index`
- **HTTP Method**: GET
- **Middleware**: `auth`, `verified`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `tableData` | `DataTableResponse<PropertySearchesTableRow>` | Paginated search rows with meta |
| `searchableColumns` | `string[]` | List of columns that support search |

## User Flow

1. User navigates to `/searches`
2. DataTable renders with property search criteria list
3. User can search and filter search records
