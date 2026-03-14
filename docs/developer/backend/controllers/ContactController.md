# ContactController

## Purpose

Serves the contacts DataTable page via Inertia, returning paginated/filtered contact data.

## Location

`app/Http/Controllers/ContactController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| `index` | GET | `/contacts` | Render the contacts DataTable Inertia page |

## Routes

- `contacts.index`: `GET /contacts` - Contacts DataTable page

## Actions Used

None — delegates entirely to `ContactDataTable::inertiaProps()`.

## Validation

None — query parameters are validated by `ContactDataTable` via `spatie/laravel-query-builder`.

## Related Components

- **Pages**: `contacts/index` (rendered by this controller)
- **DataTable**: `ContactDataTable` (provides table data)
- **Routes**: `contacts.index` (defined in routes/web.php)
