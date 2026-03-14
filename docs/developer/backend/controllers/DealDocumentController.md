# DealDocumentController

**Location**: `app/Http/Controllers/DealDocumentController.php`
**Last Updated**: 2026-03-15

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `deal-documents` | `deal-documents.index` | List documents filtered by deal_type + deal_id |
| POST | `deal-documents` | `deal-documents.store` | Upload a new document |
| DELETE | `deal-documents/{dealDocument}` | `deal-documents.destroy` | Delete a document and its file |

## Actions Used

- `UploadDealDocumentAction`

## File Upload

- Max size: 20MB (`max:20480` rule)
- Files stored on `private` disk under `deal-docs/{deal_type}/{deal_id}/`
- On delete, the physical file is removed from storage before the DB record is deleted

## Response

All responses are JSON. The `store` endpoint returns HTTP 201 with the document record including the `uploader` relation.

## Middleware

Requires `auth` + `verified`.
