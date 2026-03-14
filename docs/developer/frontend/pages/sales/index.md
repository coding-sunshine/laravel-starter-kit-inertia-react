# sales/index

## Purpose

Displays the Sales DataTable — a paginated, searchable list of property sales with commission data.

## Location

`resources/js/pages/sales/index.tsx`

## Route Information

- **URL**: `/sales`
- **Route Name**: `sales.index`
- **HTTP Method**: GET
- **Middleware**: `auth`, `verified`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `tableData` | `DataTableResponse<SalesTableRow>` | Paginated sale rows with meta |
| `searchableColumns` | `string[]` | List of columns that support search |

## User Flow

1. User navigates to `/sales`
2. DataTable renders with sales list (client, agent, lot, status, commissions)
3. User can search and filter sales records
