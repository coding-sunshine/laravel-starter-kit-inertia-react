# reservations/index

## Purpose

Displays the Property Reservations DataTable — a paginated, searchable list of property reservations.

## Location

`resources/js/pages/reservations/index.tsx`

## Route Information

- **URL**: `/reservations`
- **Route Name**: `reservations.index`
- **HTTP Method**: GET
- **Middleware**: `auth`, `verified`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `tableData` | `DataTableResponse<PropertyReservationsTableRow>` | Paginated reservation rows with meta |
| `searchableColumns` | `string[]` | List of columns that support search |

## User Flow

1. User navigates to `/reservations`
2. DataTable renders with reservation list (stage, deposit status, purchase price, lot, agent contact)
3. User can search and filter reservations
4. "Add reservation" header action navigates to Filament admin create page
