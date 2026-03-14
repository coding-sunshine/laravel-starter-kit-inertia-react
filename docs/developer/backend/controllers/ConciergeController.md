# ConciergeController

Property concierge — AI-powered buyer-to-property matching via ConciergeAgent.

## Location

`app/Http/Controllers/ConciergeController.php`

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/ai/concierge` | `ai.concierge.index` | Render concierge UI |
| POST | `/ai/concierge/match` | `ai.concierge.match` | Match properties for buyer |

## Request (POST /ai/concierge/match)

```json
{
  "contact_id": 42,
  "message": "3 bedroom, budget $600k, Northside",
  "conversation_id": "uuid-optional"
}
```

## Agent

Uses `ConciergeAgent` (LotsFilterTool, PipelineSummaryTool, memory).

## Related

- `app/AI/Agents/ConciergeAgent.php`
- `resources/js/pages/concierge/index.tsx`
