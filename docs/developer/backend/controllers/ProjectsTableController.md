# ProjectsTableController

## Purpose

Serves the projects DataTable page via Inertia, returning paginated/filtered project data.

## Location

`app/Http/Controllers/ProjectsTableController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| `index` | GET | `/projects` | Render the projects DataTable Inertia page |

## Routes

- `projects.table`: `GET /projects` - Projects DataTable page

## Actions Used

None — delegates entirely to `ProjectDataTable::inertiaProps()`.

## Validation

None — query parameters are validated by `ProjectDataTable` via `spatie/laravel-query-builder`.

## Related Components

- **Pages**: `projects/index` (rendered by this controller)
- **DataTable**: `ProjectDataTable` (provides table data)
- **Routes**: `projects.table` (defined in routes/web.php)
