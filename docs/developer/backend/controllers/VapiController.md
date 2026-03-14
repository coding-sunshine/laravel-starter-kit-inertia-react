# VapiController

Handles Vapi voice AI webhook events and displays call logs.

## Location

`app/Http/Controllers/VapiController.php`

## Routes

| Method | URI | Name | Middleware | Description |
|--------|-----|------|------------|-------------|
| GET | `/ai/calls` | `ai.calls.index` | auth | Call logs Inertia page |
| POST | `/webhooks/vapi` | `webhooks.vapi` | none (no CSRF) | Vapi webhook handler |

## Webhook Events Handled

- `call-started` / `call.created` — creates `CallLog` record
- `call-ended` / `call.ended` — updates duration and outcome
- `transcript` — updates transcript text and infers sentiment

## Sentiment Inference

Simple keyword-based: negative words → `negative`, positive words → `positive`, otherwise `neutral`.

## Configuration

Requires `VAPI_API_KEY` in `.env`. Configured in `config/services.php` as `services.vapi.api_key`.

## Related

- `app/Models/CallLog.php`
- `app/Services/VapiService.php`
- `resources/js/pages/call-logs/index.tsx`
