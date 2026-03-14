# SaleController

## Purpose

Serves the sales DataTable page via Inertia.

## Location

`app/Http/Controllers/SaleController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| `index` | GET | `/sales` | Render the sales DataTable Inertia page |

## Routes

- `sales.index`: `GET /sales` - Sales DataTable page

## Actions Used

None — delegates entirely to `SaleDataTable::inertiaProps()`.

## Related Components

- **Pages**: `sales/index` (rendered by this controller)
- **DataTable**: `SaleDataTable` (provides table data)
