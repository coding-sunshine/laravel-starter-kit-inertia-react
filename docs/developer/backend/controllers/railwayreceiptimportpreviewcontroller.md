# RailwayReceiptImportPreviewController

## Purpose

Sanctum API endpoint that returns JSON preview data for a Railway Receipt PDF (FNR, matched rake, siding metadata) before the client calls `POST api/v1/railway-receipts/upload`.

## Location

`app/Http/Controllers/Api/V1/RailwayReceiptImportPreviewController.php`

## Methods

| Method | HTTP Method | Route | Purpose |
|--------|-------------|-------|---------|
| store | POST | `api/v1/railway-receipts/import-preview` | Validate multipart PDF and return preview payload |

## Routes

- `api.v1.railway-receipts.import-preview`: `POST api/v1/railway-receipts/import-preview` — requires `sections.railway_receipts.upload`, `auth:sanctum`, active `feature:api_access`.

## Actions Used

- `PreviewRailwayReceiptImport` — parse PDF, resolve rake by FNR, enforce siding access, build response array.

## Validation

- `StoreApiRailwayReceiptImportPreviewRequest` — validates `pdf` (required file, PDF mime, max 10 MB).

## Related Components

- **Web parity**: `RrUploadController::importPreview` (`railway-receipts/import-preview`)
- **Action**: `App\Actions\PreviewRailwayReceiptImport`, `App\Actions\ResolveRakeForRrImportPreview`
- **Routes**: `routes/api.php` (v1, `auth:sanctum`)
