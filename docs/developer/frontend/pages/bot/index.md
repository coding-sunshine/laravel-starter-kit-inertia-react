# Bot In A Box v2 — Chat Page

Simple chat interface for the unified CRM assistant.

## Location

`resources/js/pages/bot/index.tsx`

## Route

`GET /ai/bot` → `ai.bot.index`

## Features

- Message bubbles (user/assistant)
- Conversation ID tracking (persists across messages)
- Quick suggestion prompts on empty state
- Textarea with Enter-to-send
- Loading indicator (animated dots)

## API

Sends `POST /ai/bot/chat` with `{ message, conversation_id }`.

## Pan Analytics

`data-pan="ai-bot-tab"` on root container.

## Related

- `app/Http/Controllers/BotV2Controller.php`
- `app/AI/Agents/BotV2Agent.php`
