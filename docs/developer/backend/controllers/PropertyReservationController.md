# PropertyReservationController

## Purpose

Serves the property reservations DataTable page via Inertia.

## Location

`app/Http/Controllers/PropertyReservationController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| `index` | GET | `/reservations` | Render the reservations DataTable Inertia page |

## Routes

- `reservations.index`: `GET /reservations` - Reservations DataTable page

## Actions Used

None — delegates entirely to `PropertyReservationDataTable::inertiaProps()`.

## Related Components

- **Pages**: `reservations/index` (rendered by this controller)
- **DataTable**: `PropertyReservationDataTable` (provides table data)
