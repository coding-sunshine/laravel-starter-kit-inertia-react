# TriggerWebhooksAction

## Purpose

Dispatches `TriggerWebhookJob` for all active `WebhookEndpoint` records subscribed to the given event within an organization. Called by `CaptureLeadAction` (on `contact.created`) and `UpdateSaleStageAction` (on `sale.updated`).

## Location

`app/Actions/TriggerWebhooksAction.php`

## Method Signature

```php
public function handle(string $event, array $payload, int $organizationId): void
```

## Dependencies

None (uses Eloquent + dispatches jobs).

## Return Value

Void — jobs are dispatched asynchronously.

## Related Components

- **Job**: `TriggerWebhookJob`
- **Model**: `WebhookEndpoint`
- **Callers**: `CaptureLeadAction`, `UpdateSaleStageAction`
- **API**: `Api\V2\WebhookController` (CRUD for endpoints)
