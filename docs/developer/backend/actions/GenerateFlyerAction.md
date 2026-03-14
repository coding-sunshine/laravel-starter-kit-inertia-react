# GenerateFlyerAction

## Purpose

Generates a PDF flyer for a project/lot using `spatie/laravel-pdf` v2.

## Location

`app/Actions/GenerateFlyerAction.php`

## Method Signature

```php
public function handle(Flyer $flyer): PdfBuilder
```

## Dependencies

None (no constructor dependencies).

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$flyer` | `App\Models\Flyer` | The flyer model to generate a PDF for. Automatically loads `project`, `flyerTemplate`, and `lot` relationships. |

## Return Value

Returns a `Spatie\LaravelPdf\PdfBuilder` instance. Call one of the following to get output:
- `->download('filename.pdf')` — stream as download response
- `->inline()` — render inline in browser
- `->save('/path/to/file.pdf')` — save to disk

## Usage Examples

### From Controller

```php
$pdf = app(GenerateFlyerAction::class)->handle($flyer);
$filename = app(GenerateFlyerAction::class)->filename($flyer);
return $pdf->download($filename);
```

### Inline preview

```php
return app(GenerateFlyerAction::class)->handle($flyer)->inline();
```

## Template System

- **Default template**: `resources/views/flyers/pdf.blade.php` — rendered when no custom HTML or template HTML is set.
- **Flyer template HTML**: If `FlyerTemplate::html_content` is set, it is rendered with token substitution (`{project_title}`, `{suburb}`, etc.).
- **Custom HTML**: If `Flyer::is_custom` is `true` and `Flyer::custom_html` is set, custom HTML overrides the default template.

## Related Components

- **Model**: `App\Models\Flyer`, `App\Models\FlyerTemplate`, `App\Models\Project`, `App\Models\Lot`
- **View**: `resources/views/flyers/pdf.blade.php`
- **Package**: `spatie/laravel-pdf` v2

## Notes

- PDF format is A4 with no margins (margins are set via CSS in the Blade view).
- The `filename()` helper method returns a slugified filename based on the project title and lot ID.
- Import data from the legacy system is available via `Flyer::legacy_id`.
