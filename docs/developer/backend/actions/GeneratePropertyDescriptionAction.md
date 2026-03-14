# GeneratePropertyDescriptionAction

Generates an AI-powered marketing property description for a Lot model using Prism.

## Location

`app/Actions/GeneratePropertyDescriptionAction.php`

## Usage

```php
use App\Actions\GeneratePropertyDescriptionAction;
use App\Models\Lot;

$action = app(GeneratePropertyDescriptionAction::class);
$description = $action->handle($lot, 'exciting');
```

## Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$lot` | `Lot` | — | The lot to describe |
| `$tone` | `'professional'\|'exciting'\|'casual'` | `'professional'` | Writing tone |

## Returns

`string` — Marketing description (2-3 paragraphs with CTA), or fallback string if AI unavailable.

## Related

- `app/Models/Lot.php`
- `app/Services/PrismService.php`
