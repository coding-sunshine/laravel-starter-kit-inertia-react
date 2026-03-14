# AiSummaryController

Generates and retrieves AI summaries for CRM models via REST endpoints.

## Location

`app/Http/Controllers/AiSummaryController.php`

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/ai/summaries/{type}/{id}` | `ai.summaries.show` | Get latest summary |
| POST | `/ai/summaries/{type}/{id}` | `ai.summaries.generate` | Generate new summary |

## Supported Types

`contact`, `sale`, `reservation`, `lot`, `project`

## Response Format

```json
{
  "summary": {
    "id": 1,
    "content": "John Smith is a high-priority lead...",
    "model": "gpt-4o-mini",
    "created_at": "2026-03-15T10:00:00+00:00"
  }
}
```

## Related

- `app/Actions/GenerateAiSummaryAction.php`
- `app/Models/AiSummary.php`
