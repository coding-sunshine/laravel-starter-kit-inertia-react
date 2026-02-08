# ProfileExportPdfController

## Purpose

Returns the authenticated user's profile as a PDF file download. Used from the dashboard via the "Export profile (PDF)" link (Inertia).

## Location

`app/Http/Controllers/ProfileExportPdfController.php`

## Methods

| Method   | HTTP | Route               | Purpose                    |
|----------|------|---------------------|----------------------------|
| `__invoke` | GET  | `profile.export-pdf` | Generate and download PDF |

## Routes

- `profile.export-pdf`: GET `profile/export-pdf` — Requires `auth` and `verified`. Returns `application/pdf` with `Content-Disposition: attachment`.

## Implementation

- Uses **spatie/laravel-pdf** (`pdf()` helper).
- Blade view: `resources/views/pdf/profile.blade.php` (user name, email, email_verified_at, generated date).
- Filename: `profile-{Y-m-d}.pdf`.

## Related

- **Page**: Dashboard (`dashboard`) — link to this route.
- **Package**: [Content & export](../content-export.md) (PDF, tags, Excel).
