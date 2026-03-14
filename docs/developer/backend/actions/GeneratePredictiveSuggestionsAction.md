# GeneratePredictiveSuggestionsAction

Generates 3-5 AI-powered next-best-action suggestions for a CRM contact.

## Location

`app/Actions/GeneratePredictiveSuggestionsAction.php`

## Usage

```php
use App\Actions\GeneratePredictiveSuggestionsAction;
use App\Models\Contact;

$action = app(GeneratePredictiveSuggestionsAction::class);
$suggestions = $action->handle($contact);
// Returns array of {action, reason, priority} objects
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$contact` | `Contact` | The contact to generate suggestions for |

## Returns

`array<int, array{action: string, reason: string, priority: string}>` — Empty array if AI unavailable or on error.

## Behaviour

- Builds context from contact stage, type, lead score, and follow-up dates.
- Returns empty array (never throws) on AI failure.
- Uses `PrismService` with structured JSON output instructions.

## Related

- `app/Http/Controllers/PredictiveSuggestionsController.php`
- `app/Services/PrismService.php`
