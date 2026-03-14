# PropertySearchController

## Purpose

Serves the property searches DataTable page via Inertia.

## Location

`app/Http/Controllers/PropertySearchController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| `index` | GET | `/searches` | Render the searches DataTable Inertia page |

## Routes

- `searches.index`: `GET /searches` - Searches DataTable page

## Actions Used

None — delegates entirely to `PropertySearchDataTable::inertiaProps()`.

## Related Components

- **Pages**: `searches/index` (rendered by this controller)
- **DataTable**: `PropertySearchDataTable` (provides table data)
