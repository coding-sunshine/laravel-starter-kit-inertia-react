# enquiries/index

## Purpose

Displays the Property Enquiries DataTable — a paginated, searchable list of property enquiries.

## Location

`resources/js/pages/enquiries/index.tsx`

## Route Information

- **URL**: `/enquiries`
- **Route Name**: `enquiries.index`
- **HTTP Method**: GET
- **Middleware**: `auth`, `verified`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `tableData` | `DataTableResponse<PropertyEnquiriesTableRow>` | Paginated enquiry rows with meta |
| `searchableColumns` | `string[]` | List of columns that support search |

## User Flow

1. User navigates to `/enquiries`
2. DataTable renders with enquiry list (client contact, agent contact, property, status)
3. User can search and filter enquiries
