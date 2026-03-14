# TaskController

## Purpose

Handles task listing for the CRM tasks view, using the TaskDataTable for server-side rendering.

## Location

`app/Http/Controllers/TaskController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| index | GET | `/tasks` | Display the tasks data table page |

## Routes

- `tasks.index`: `GET /tasks` - Renders the tasks index page with paginated DataTable data

## Actions Used

None

## Validation

None

## Related Components

- **Pages**: `tasks/index` (rendered by this controller)
- **DataTables**: `TaskDataTable` (provides table data)
- **Routes**: `tasks.index` (defined in routes/web.php)
