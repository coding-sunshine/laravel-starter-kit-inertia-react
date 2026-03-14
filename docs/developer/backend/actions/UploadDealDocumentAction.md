# UploadDealDocumentAction

**Location**: `app/Actions/UploadDealDocumentAction.php`
**Last Updated**: 2026-03-15

## Purpose

Stores an uploaded file to the `private` disk under `deal-docs/{dealType}/{dealId}/` and creates a `DealDocument` record with metadata including MIME type, file size, and uploader.

## Signature

```php
public function handle(
    string $dealType,
    int $dealId,
    UploadedFile $file,
    string $documentType,
    string $title,
    ?int $organizationId = null,
): DealDocument
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$dealType` | `string` | `reservation` or `sale` |
| `$dealId` | `int` | The ID of the reservation or sale |
| `$file` | `UploadedFile` | The uploaded file (max 20MB enforced at controller level) |
| `$documentType` | `string` | `contract`, `invoice`, `id_doc`, `email`, or `other` |
| `$title` | `string` | Human-readable document title |
| `$organizationId` | `int\|null` | Organization ID for scoping |

## Returns

`DealDocument` — the newly created record.

## Storage

Files are stored on the `private` disk. The path pattern is:
```
deal-docs/{dealType}/{dealId}/{uuid}.{ext}
```

## Related

- Model: `App\Models\DealDocument`
- Controller: `App\Http\Controllers\DealDocumentController`
- Routes: `deal-documents.store`
