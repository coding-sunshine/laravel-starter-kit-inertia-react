# GenerateBrochureV2Action

Generates an AI-enhanced PDF brochure (v2) for a Flyer using a BrochureLayout and Spatie Laravel PDF.

## Location

`app/Actions/GenerateBrochureV2Action.php`

## Usage

```php
use App\Actions\GenerateBrochureV2Action;
use App\Models\Flyer;

$action = app(GenerateBrochureV2Action::class);
$path = $action->handle($flyer, $layout);
// Returns absolute path to generated PDF
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$flyer` | `Flyer` | The flyer to generate the brochure for |
| `$layout` | `BrochureLayout|null` | Optional brochure layout; null uses defaults |

## Behaviour

- Calls Prism to generate tagline, description, key features, and CTA.
- Renders `resources/views/brochures/v2.blade.php` to A4 landscape PDF.
- Saves to `storage/app/brochures/brochure_{id}_v2.pdf`.
- Falls back to static content if AI unavailable.
- Deducts credits with action key `generate_brochure_v2`.

## Related

- `app/Models/Flyer.php`
- `app/Models/BrochureLayout.php`
- `app/Http/Controllers/BrochureLayoutController.php`
- `resources/views/brochures/v2.blade.php`
