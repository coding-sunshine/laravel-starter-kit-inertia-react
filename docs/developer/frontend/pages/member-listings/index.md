# Member Listings Index Page

## Purpose

Displays both projects and lots in separate DataTable sections on a single page for member-facing listing browsing.

## Location

`resources/js/pages/member-listings/index.tsx`

## Route Information

- **URL**: `/member-listings`
- **Route Name**: `member-listings.index`
- **HTTP Method**: GET
- **Middleware**: `auth`, `verified`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `lotsTableData` | `DataTableResponse<LotTableRow> \| undefined` | Paginated lot data |
| `lotsSearchableColumns` | `string[]` | Searchable columns for lots table |
| `projectsTableData` | `DataTableResponse<ProjectTableRow> \| undefined` | Paginated project data |
| `projectsSearchableColumns` | `string[]` | Searchable columns for projects table |

## User Flow

1. User navigates to `/member-listings`
2. Page renders two DataTable sections: Projects and Lots
3. Each table has independent search, filtering, and pagination
4. Partial reloads use `projectsTableData` and `lotsTableData` keys respectively

## Related Components

- **Controller**: `MemberListingsController@index`
- **DataTables**: `LotDataTable`, `ProjectDataTable`
- **Route**: `member-listings.index`

## Implementation Details

- Two independent DataTable instances on the same page use different `tableName` and `partialReloadKey` values to avoid conflicts
- Export and filter features are enabled for both tables
