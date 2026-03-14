# LotsTableController

## Purpose

Serves the lots DataTable page via Inertia, returning paginated/filtered lot inventory data.

## Location

`app/Http/Controllers/LotsTableController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| `index` | GET | `/lots` | Render the lots DataTable Inertia page |

## Routes

- `lots.table`: `GET /lots` - Lots DataTable page

## Actions Used

None — delegates entirely to `LotDataTable::inertiaProps()`.

## Validation

None — query parameters are validated by `LotDataTable` via `spatie/laravel-query-builder`.

## Related Components

- **Pages**: `lots/index` (rendered by this controller)
- **DataTable**: `LotDataTable` (provides table data)
- **Routes**: `lots.table` (defined in routes/web.php)
