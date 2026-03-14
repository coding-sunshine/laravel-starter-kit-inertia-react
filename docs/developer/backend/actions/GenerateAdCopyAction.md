# GenerateAdCopyAction

Generates AI-powered ad copy (headline, body copy, CTA) for a specified channel, type, and tone using Prism.

## Location

`app/Actions/GenerateAdCopyAction.php`

## Usage

```php
use App\Actions\GenerateAdCopyAction;

$action = app(GenerateAdCopyAction::class);
$copy = $action->handle('facebook', 'ad', 'professional', '3-bed apartments from $650k');
// Returns array{headline: string, body_copy: string, cta_text: string}
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$channel` | `string` | Ad channel: facebook, instagram, twitter, linkedin, google |
| `$type` | `string` | Ad type: ad, social, carousel, story |
| `$tone` | `string` | Tone: professional, casual, urgent, friendly |
| `$context` | `string` | Context string passed to AI (e.g. project name, price range) |

## Behaviour

- Checks AI credits via `AiCreditService::canUse()` before calling Prism.
- Falls back to static copy if AI is unavailable or credits exhausted.
- Parses JSON from AI response; falls back on parse failure.
- Deducts credits with action key `generate_ad_copy`.

## Related

- `app/Models/AdTemplate.php`
- `app/Http/Controllers/AdTemplateController.php`
- `app/Services/AiCreditService.php`
- `app/Services/PrismService.php`
