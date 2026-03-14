# Call Logs Page

Displays Vapi call logs with filtering by sentiment and outcome.

## Location

`resources/js/pages/call-logs/index.tsx`

## Route

`GET /ai/calls` → `ai.calls.index`

## Props

| Prop | Type | Description |
|------|------|-------------|
| `call_logs` | paginated | CallLog records with contact |
| `vapi_configured` | boolean | Whether VAPI_API_KEY is set |

## Features

- Table: contact name, direction icon, duration, sentiment badge, outcome badge, timestamp
- Client-side filters for sentiment and outcome
- Vapi configuration warning banner
- Empty state when no calls yet

## Pan Analytics

`data-pan="call-logs-tab"` on root container.

## Related

- `app/Http/Controllers/VapiController.php`
- `app/Models/CallLog.php`
