# PreviewRailwayReceiptImport

## Purpose

Parses an uploaded Railway Receipt PDF, resolves the matching rake via FNR and e-demand (`ResolveRakeForRrImportPreview`), enforces the caller’s siding access, ensures the **default** RR upload slot is still free (same rules as `importSnapshotOnly` without `diverrt_destination_id`, via `RrImportService::assertDefaultUploadSlotAvailableForPreview`), and returns the preview payload (no persistence).

## Location

`app/Actions/PreviewRailwayReceiptImport.php`

## Method Signature

```php
public function handle(User $user, UploadedFile $pdf): array
```

## Dependencies

- `RrParserService` — extracts structured data (including FNR) from the PDF
- `ResolveRakeForRrImportPreview` — maps normalized FNR to `Indent` / `Rake`
- `RrImportService` — slot check for preview (e.g. `"Railway Receipt has already been uploaded for this rake."` when applicable)

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| user | `User` | Current user; used for super-admin vs `accessibleSidings()` siding checks |
| pdf | `UploadedFile` | Multipart RR PDF |

## Return Value

Associative array with keys: `fnr_from_rr`, `fnr_from_indent`, `to_station_code`, `rake_destination_code`, `rake_destination`, `siding_code`, `siding_name`, `rake_id`, `rake_number`, `rake_serial_number`.

## Usage Examples

### From controller

```php
$payload = app(PreviewRailwayReceiptImport::class)->handle($request->user(), $request->file('pdf'));

return response()->json($payload);
```

## Related Components

- **Controllers**: `App\Http\Controllers\RR\RrUploadController::importPreview`, `App\Http\Controllers\Api\V1\RailwayReceiptImportPreviewController::store`
- **Routes**: `railway-receipts.import-preview` (web), `api.v1.railway-receipts.import-preview` (API)
- **Service**: `App\Services\Railway\RrImportService::assertDefaultUploadSlotAvailableForPreview`
- **Action**: `App\Actions\ResolveRakeForRrImportPreview`
- **Models**: `Indent`, `Rake`, `Siding`

## Notes

- Throws `InvalidArgumentException` with the same messages as `ResolveRakeForRrImportPreview` and the PDF parser when FNR/indents/rakes are invalid, and the same slot messages as RR import when the default slot is taken (non-diverted rake, diverted primary slot, etc.).
- Calls `abort(403)` when the resolved rake’s siding is not in the user’s allowed siding set (parity with prior web-only preview behavior).
- **Authorization**: Callers must still enforce `sections.railway_receipts.upload` before invoking this action.
