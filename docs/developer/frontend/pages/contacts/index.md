# contacts/index

## Purpose

Displays the Contacts DataTable — a paginated, searchable, filterable list of CRM contacts (leads, clients, partners) with quick views (All, Leads, Clients, Hot).

## Location

`resources/js/pages/contacts/index.tsx`

## Route Information

- **URL**: `/contacts`
- **Route Name**: `contacts.index`
- **HTTP Method**: GET
- **Middleware**: `auth`, `verified`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `tableData` | `DataTableResponse<ContactsTableRow>` | Paginated contact rows with meta |
| `searchableColumns` | `string[]` | List of columns that support search |

## User Flow

1. User navigates to `/contacts`
2. DataTable renders with contact list (name, type, stage, company, lead score, last contacted)
3. User can search, filter by type/stage/origin, switch quick views
4. "Add contact" header action navigates to Filament admin create page
