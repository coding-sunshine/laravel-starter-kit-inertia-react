# GenerateEmailCampaignAction

Personalises an email campaign for a specific recipient using AI, returning subject, preview text, HTML body, and plain text.

## Location

`app/Actions/GenerateEmailCampaignAction.php`

## Usage

```php
use App\Actions\GenerateEmailCampaignAction;
use App\Models\EmailCampaign;

$action = app(GenerateEmailCampaignAction::class);
$personalised = $action->handle($campaign, 'John Smith');
// Returns array{subject, preview_text, html_body, plain_text}
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$campaign` | `EmailCampaign` | The campaign to personalise |
| `$recipientName` | `string` | Recipient name (default: 'Valued Client') |

## Behaviour

- Checks AI credits via `AiCreditService::canUse()`.
- Falls back to the campaign's stored content if AI unavailable.
- Deducts credits with action key `generate_email_campaign`.

## Related

- `app/Models/EmailCampaign.php`
- `app/Http/Controllers/EmailCampaignController.php`
