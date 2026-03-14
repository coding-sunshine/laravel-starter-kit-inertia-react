# LeadCaptureController

## Purpose

Multi-channel lead capture endpoint. Accepts leads from web forms, chat widgets, SMS webhooks, and API calls. Normalizes to Contact and auto-routes.

## Location

`app/Http/Controllers/LeadCaptureController.php`

## Routes

| Method | Path | Name | Description |
|--------|------|------|-------------|
| POST | /lead-capture | lead-capture.store | Capture a single lead (web form / public) |
| POST | /lead-capture/bulk | lead-capture.bulk | Bulk capture from CSV/API (authenticated) |

## Related Components

- **Action**: `CaptureLeadAction`
- **Action**: `RouteLeadAction`
- **Action**: `EnrollInNurtureSequenceAction`
