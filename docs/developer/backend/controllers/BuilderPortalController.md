# BuilderPortalController

## Purpose

Manages white-label builder portals with branding configuration per organization.

## Location

`app/Http/Controllers/BuilderPortalController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| index | GET | `/builder-portal` | List org portals |
| store | POST | `/builder-portal` | Create new portal |
| show | GET | `/builder-portal/{portal}` | View single portal |

## Routes

- `builder-portal.index`: `GET /builder-portal`
- `builder-portal.store`: `POST /builder-portal`
- `builder-portal.show`: `GET /builder-portal/{portal}`
