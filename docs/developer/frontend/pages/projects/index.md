# projects/index

## Purpose

Displays the Projects DataTable — a paginated, searchable, filterable list of property development projects with quick views (All, Available, Hot Properties, Pre-Launch).

## Location

`resources/js/pages/projects/index.tsx`

## Route Information

- **URL**: `/projects`
- **Route Name**: `projects.table`
- **HTTP Method**: GET
- **Middleware**: `auth`, `verified`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `tableData` | `DataTableResponse<ProjectsTableRow>` | Paginated project rows with meta |
| `searchableColumns` | `string[]` | List of columns that support search |

## User Flow

1. User navigates to `/projects`
2. DataTable renders with project list (title, stage, suburb, developer, price, lot counts)
3. User can search, filter by stage/suburb/state, switch quick views
4. "Add project" header action navigates to Filament admin create page

## Related Components

- **Controller**: `ProjectsTableController@index`
- **DataTable**: `ProjectDataTable` (PHP)
- **Route**: `projects.table`

## Implementation Details

Uses the shared `<DataTable>` React component from `@/components/data-table/data-table`. Supports column visibility, ordering, resizing, pinning, quick views, exports, and filters.
