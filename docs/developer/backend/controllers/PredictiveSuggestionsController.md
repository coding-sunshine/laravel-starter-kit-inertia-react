# PredictiveSuggestionsController

Retrieves and generates AI-powered next-best-action suggestions for contacts.

## Location

`app/Http/Controllers/PredictiveSuggestionsController.php`

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/ai/suggestions/{contact}` | `ai.suggestions.show` | Get suggestions for contact |
| POST | `/ai/suggestions/{contact}/generate` | `ai.suggestions.generate` | Generate new suggestions |

## Response

```json
{
  "contact_id": 42,
  "suggestions": [
    {"action": "Call within 1 hour", "reason": "High lead score", "priority": "high"}
  ]
}
```

## Related

- `app/Actions/GeneratePredictiveSuggestionsAction.php`
- `app/Models/Contact.php`
