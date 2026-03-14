# PuckTemplateController

## Purpose

Manages the Puck template library — reusable page templates for campaign sites and flyers.

## Location

`app/Http/Controllers/PuckTemplateController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| index | GET | `/puck-templates` | List templates |
| store | POST | `/puck-templates` | Create template |
| edit | GET | `/puck-templates/{template}/edit` | Edit template |

## Routes

- `puck-templates.index`: `GET /puck-templates`
- `puck-templates.store`: `POST /puck-templates`
- `puck-templates.edit`: `GET /puck-templates/{template}/edit`
