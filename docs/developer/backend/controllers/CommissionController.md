# CommissionController

## Purpose

Serves the commissions DataTable page via Inertia.

## Location

`app/Http/Controllers/CommissionController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| `index` | GET | `/commissions` | Render the commissions DataTable Inertia page |

## Routes

- `commissions.index`: `GET /commissions` - Commissions DataTable page

## Actions Used

None — delegates entirely to `CommissionDataTable::inertiaProps()`.

## Related Components

- **Pages**: `commissions/index` (rendered by this controller)
- **DataTable**: `CommissionDataTable` (provides table data)
