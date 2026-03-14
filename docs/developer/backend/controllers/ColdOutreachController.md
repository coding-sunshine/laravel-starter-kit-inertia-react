# ColdOutreachController

## Purpose

Manages cold outreach templates and provides AI copy generation endpoint.

## Location

`app/Http/Controllers/ColdOutreachController.php`

## Routes

| Method | Path | Name | Description |
|--------|------|------|-------------|
| GET | /cold-outreach | cold-outreach.index | List all templates |
| POST | /cold-outreach/generate | cold-outreach.generate | Generate AI outreach copy |

## Related Components

- **Action**: `GenerateColdOutreachAction`
- **Page**: `cold-outreach/index`
