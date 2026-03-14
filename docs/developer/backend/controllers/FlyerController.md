# FlyerController

## Purpose

Manages Puck-based flyer editing and PDF export.

## Location

`app/Http/Controllers/FlyerController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|------------|-------|---------|
| editPuck | GET | `/flyers/{flyer}/edit-puck` | Open Puck flyer editor |
| savePuck | POST | `/flyers/{flyer}/puck-save` | Save Puck content |
| exportPdf | POST | `/flyers/{flyer}/export-pdf` | Export flyer as PDF |

## Routes

- `flyers.edit-puck`: `GET /flyers/{flyer}/edit-puck`
- `flyers.puck-save`: `POST /flyers/{flyer}/puck-save`
- `flyers.export-pdf`: `POST /flyers/{flyer}/export-pdf`

## Jobs Dispatched

- `ExportFlyerPdfJob` — generates PDF using spatie/laravel-pdf
