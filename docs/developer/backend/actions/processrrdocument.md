# ProcessRrDocument

## Purpose

Extracts structured data from an uploaded Railway Receipt (RR) PDF/image via the Laravel AI SDK, then creates an `RrDocument` record from the extracted fields. Also exposes a standalone extraction method used to pre-fill the upload form before a document is saved.

## Location

`app/Actions/ProcessRrDocument.php`

## Method Signature

```php
public function handle(array $data, int $userId): RrDocument
```

```php
public function extractFromUpload(UploadedFile $file): ?array
```

## Dependencies

None (no constructor dependencies).

## Parameters

### `handle`

| Parameter | Type                                                              | Description                                                                             |
| --------- | ----------------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| `$data`   | `array{rake_id: int, document: UploadedFile, rr_number?: string}` | Rake to attach the document to, the uploaded file, and an optional pre-filled RR number |
| `$userId` | `int`                                                             | User id recorded as `created_by`                                                        |

### `extractFromUpload`

| Parameter | Type           | Description                                                      |
| --------- | -------------- | ---------------------------------------------------------------- |
| `$file`   | `UploadedFile` | RR PDF/image to extract from, without persisting an `RrDocument` |

## Return Value

- `handle()`: the created `RrDocument`, wrapped in `DB::transaction()`. On extraction/creation failure the stored file is deleted and an `InvalidArgumentException` is thrown.
- `extractFromUpload()`: extracted fields (`rr_number`, `rr_weight_mt`, `fnr`, `from_station_code`, `to_station_code`, `freight_total`, `charges`, `wagons`, `rr_details`, optionally `rr_received_date`), or `null` if AI is not configured or extraction fails.

## AI extraction

`extractRrData()` sends the file as a base64 attachment to `agent()->prompt()` with an instruction prompt that asks for every charge code printed on the RR (not a fixed enum), e.g. `POL, POL1, POL2, POLA, PCLA, DCLA, FAUC, FAOC, ENHC, DEM, OTC, GST — include any other code you see`. The response JSON is parsed with brace-depth matching to isolate the object, and `charges` is normalized generically: each key is uppercased/trimmed and kept only if its value is a positive number (`mb_strtoupper(mb_trim($code))`, `(float) $amount > 0`). No fixed whitelist of charge codes is applied at this layer — normalization/classification into penalty vs. non-penalty happens downstream in `RrImportService`.

## Usage Examples

### From Controller

```php
$rrDocument = app(ProcessRrDocument::class)->handle($data, $request->user()->id);
```

### Pre-fill on upload

```php
$extracted = app(ProcessRrDocument::class)->extractFromUpload($file);
```

## Related Components

- **Model**: `RrDocument`, `Rake`
- **Service**: `App\Services\Railway\RrImportService` (classifies extracted `charges` into `rr_charges` / penalty snapshots on import)
- **Service**: `App\Services\Railway\RrParserService` (separate PDF-text-based parser fallback used by `rr:reparse-charges`)

## Notes

- `isAiConfigured()` checks `config('ai.default')` and the corresponding provider key; extraction is skipped (returns `null`) when unset.
- `getMediaType()` maps MIME type to the AI attachment media type; unknown types default to `image/jpeg`.
- `getPendingRrDocuments()` / `getDocumentsWithDiscrepancies()` are read helpers scoped by siding, unrelated to extraction.
