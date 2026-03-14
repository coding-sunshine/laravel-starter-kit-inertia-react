# commissions/index

## Purpose

Displays the Commissions DataTable — a paginated, searchable list of commission records linked to sales.

## Location

`resources/js/pages/commissions/index.tsx`

## Route Information

- **URL**: `/commissions`
- **Route Name**: `commissions.index`
- **HTTP Method**: GET
- **Middleware**: `auth`, `verified`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `tableData` | `DataTableResponse<CommissionsTableRow>` | Paginated commission rows with meta |
| `searchableColumns` | `string[]` | List of columns that support search |

## User Flow

1. User navigates to `/commissions`
2. DataTable renders with commission list (type, amount, rate, sale, agent)
3. User can search and filter commission records
