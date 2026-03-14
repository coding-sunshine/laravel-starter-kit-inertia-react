# InventoryApiController

## Purpose

Inventory import controller — accepts JSON or CSV uploads to bulk-import lots or projects.

## Location

`app/Http/Controllers/InventoryApiController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| index | GET | `/inventory` | Show import page |
| import | POST | `/inventory/import` | Process file upload |
| template | GET | `/inventory/template/{type}` | Download CSV template |

## Routes

- `inventory.index`: `GET /inventory`
- `inventory.import`: `POST /inventory/import`
- `inventory.template`: `GET /inventory/template/{type}`

## Actions Used

- `ImportInventoryAction` — parses rows and upserts lots/projects
