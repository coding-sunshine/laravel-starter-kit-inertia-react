# Property Concierge Page

AI-powered property matching interface for buyers.

## Location

`resources/js/pages/concierge/index.tsx`

## Route

`GET /ai/concierge` → `ai.concierge.index`

## Features

- Contact ID input (optional) to pre-fill buyer context
- Buyer requirements text area
- Match history showing queries and concierge responses
- Sends `POST /ai/concierge/match`

## Pan Analytics

`data-pan="ai-concierge-tab"` on root container.

## Related

- `app/Http/Controllers/ConciergeController.php`
- `app/AI/Agents/ConciergeAgent.php`
