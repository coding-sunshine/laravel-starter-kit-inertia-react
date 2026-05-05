# ResolveRakeForRrImportPreview

## Purpose

Resolves the `Indent` (e-demand) and linked `Rake` for a normalized FNR string so the Railway Receipt upload flow can show a confirmation dialog before importing the PDF.

## Location

`app/Actions/ResolveRakeForRrImportPreview.php`

## Method Signature

```php
public function handle(string $normalizedFnr): array
```

## Dependencies

None

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| normalizedFnr | string | FNR from the RR PDF after trim; must match `indents.fnr_number` |

## Return Value

`array{indent: Indent, rake: Rake}` with `rake.siding` eager-loadable from the indent query.

## Usage Examples

### From controller

```php
$resolved = app(ResolveRakeForRrImportPreview::class)->handle($normalizedFnr);
$rake = $resolved['rake'];
```

## Related Components

- **Controller**: `App\Http\Controllers\RR\RrUploadController::importPreview`
- **Route**: `railway-receipts.import-preview` (POST `railway-receipts/import-preview`)
- **Models**: `Indent`, `Rake`

## Notes

- Throws `InvalidArgumentException` when FNR is empty, no indent matches, or the indent has no linked rake.
- User-facing copy for “no indent” is finalized in the controller response (`No e-demand found for this RR FNR.`).
- **Authorization**: `RrUploadController::importPreview` requires `sections.railway_receipts.upload` (same as `railway-receipts.import`). The route name is listed in `config/permission.php` `route_skip_patterns` so `permission:sync-routes` does not create a separate Spatie permission for it.
