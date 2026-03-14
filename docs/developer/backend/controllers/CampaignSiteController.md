# CampaignSiteController

## Purpose

Manages campaign site listing and the Puck visual page builder editor for campaign websites.

## Location

`app/Http/Controllers/CampaignSiteController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| index | GET | `/campaign-sites` | List all campaign sites for current org |
| editPuck | GET | `/campaign-sites/{campaign}/edit-puck` | Open Puck editor for a campaign site |
| savePuck | POST | `/campaign-sites/{campaign}/puck-save` | Save Puck content and optionally publish |

## Routes

- `campaign-sites.index`: `GET /campaign-sites` - List campaign sites
- `campaign-sites.edit-puck`: `GET /campaign-sites/{campaign}/edit-puck` - Puck editor
- `campaign-sites.puck-save`: `POST /campaign-sites/{campaign}/puck-save` - Save Puck JSON
