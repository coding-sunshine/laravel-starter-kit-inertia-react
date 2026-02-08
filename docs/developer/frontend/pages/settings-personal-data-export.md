# settings/personal-data-export

## Purpose

Settings page for requesting a GDPR personal data export. User clicks "Request data export"; a job runs and an email with a download link is sent when ready.

## Location

`resources/js/pages/settings/personal-data-export.tsx`

## Route Information

- **URL**: `settings/personal-data-export`
- **Route Name**: `personal-data-export.edit` (GET), `personal-data-export.store` (POST)
- **HTTP Method**: GET (page), POST (request export)
- **Middleware**: `web`, `auth`

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| `flash.status` | `string` | Confirmation after requesting export |

## User Flow

1. User opens Settings → Data export.
2. Reads copy; clicks "Request data export".
3. Request is throttled (3/min). Job is queued; user sees status message. When ready, user receives email with download link.

## Related Components

- **Controller**: `PersonalDataExportController` (POST)
- **Route**: `personal-data-export.edit`, `personal-data-export.store`
- **Layout**: `AppLayout`, `SettingsLayout`
- **Package**: `spatie/laravel-personal-data-export`
