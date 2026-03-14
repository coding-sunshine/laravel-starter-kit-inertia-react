# GenerateAiSummaryAction

Generates an AI-powered 2-3 sentence summary for any Eloquent model using the configured Prism provider.

## Location

`app/Actions/GenerateAiSummaryAction.php`

## Usage

```php
use App\Actions\GenerateAiSummaryAction;
use App\Models\Contact;

$action = app(GenerateAiSummaryAction::class);
$summary = $action->handle($contact, "Contact: John Smith, Stage: hot, Lead Score: 85");
// Returns AiSummary model
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$model` | `Model` | The Eloquent model to summarize (Contact, Lot, Sale, etc.) |
| `$context` | `string` | Text context to pass to the AI model |

## Returns

`AiSummary` — a new summary record with `content`, `model`, `summarizable_type`, `summarizable_id`.

## Behaviour

- Uses `PrismService` with the default configured provider.
- Falls back to a truncated context snippet if AI is unavailable or throws.
- Stores result in `ai_summaries` via morph relationship.

## Related

- `app/Models/AiSummary.php`
- `app/Http/Controllers/AiSummaryController.php`
- `app/Services/PrismService.php`
