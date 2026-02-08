# PersonalDataExportController

## Purpose

Queues a personal data export job for the authenticated user. The user receives an email with a download link when the export is ready (GDPR).

## Location

`app/Http/Controllers/PersonalDataExportController.php`

## Methods

| Method | HTTP | Route | Purpose |
|--------|------|-------|---------|
| `__invoke` | POST | `personal-data-export.store` | Dispatch CreatePersonalDataExportJob, redirect back |

## Routes

- `personal-data-export.store`: POST `settings/personal-data-export` — Request export (throttle 3/min)

## Related Components

- **Routes**: `personal-data-export.store`, `personal-data-export.edit`
- **Package**: `spatie/laravel-personal-data-export`
- **Page**: `settings/personal-data-export`
