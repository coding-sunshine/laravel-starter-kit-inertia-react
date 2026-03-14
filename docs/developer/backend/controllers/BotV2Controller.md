# BotV2Controller

Bot In A Box v2 — unified CRM chat interface backed by `BotV2Agent`.

## Location

`app/Http/Controllers/BotV2Controller.php`

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/ai/bot` | `ai.bot.index` | Render chat UI |
| POST | `/ai/bot/chat` | `ai.bot.chat` | Send message, get reply |

## Request (POST /ai/bot/chat)

```json
{
  "message": "Show me hot leads",
  "conversation_id": "uuid-optional"
}
```

## Response

```json
{
  "success": true,
  "reply": "Here are your hot leads...",
  "conversation_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

## Agent

Uses `BotV2Agent` (all 4 tools: ContactSearchTool, TasksForUserTool, LotsFilterTool, PipelineSummaryTool).

## Related

- `app/AI/Agents/BotV2Agent.php`
- `resources/js/pages/bot/index.tsx`
