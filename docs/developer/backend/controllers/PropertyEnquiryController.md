# PropertyEnquiryController

## Purpose

Serves the property enquiries DataTable page via Inertia.

## Location

`app/Http/Controllers/PropertyEnquiryController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| `index` | GET | `/enquiries` | Render the enquiries DataTable Inertia page |

## Routes

- `enquiries.index`: `GET /enquiries` - Enquiries DataTable page

## Actions Used

None — delegates entirely to `PropertyEnquiryDataTable::inertiaProps()`.

## Related Components

- **Pages**: `enquiries/index` (rendered by this controller)
- **DataTable**: `PropertyEnquiryDataTable` (provides table data)
