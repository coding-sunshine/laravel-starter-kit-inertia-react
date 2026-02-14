# SyncSubscriptionSeatsAction

## Purpose

Syncs subscription quantity with organization member count for per-seat plans when seat-based billing is enabled. Updates the `quantity` column and optionally the payment gateway.

## Location

`app/Actions/Billing/SyncSubscriptionSeatsAction.php`

## Method Signature

```php
public function handle(Organization $organization): void
```

## Dependencies

- `BillingSettings` – to check `enable_seat_based_billing`
- `PaymentGatewayManager` – to call `updateSubscriptionQuantity` when the subscription has a `gateway_subscription_id`

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$organization` | `Organization` | The organization whose subscription quantity should be synced |

## Return Value

Void. Returns early if seat billing is disabled, or if the organization has no active per-seat subscription.

## Usage Examples

### From Listener (automatic)

```php
// Invoked by SyncSubscriptionSeatsOnMemberChange on OrganizationMemberAdded / OrganizationMemberRemoved
$this->syncSeats->handle($event->organization);
```

### Manual invocation

```php
app(SyncSubscriptionSeatsAction::class)->handle($organization);
```

## Related Components

- **Listener**: `SyncSubscriptionSeatsOnMemberChange` (listens to `OrganizationMemberAdded`, `OrganizationMemberRemoved`)
- **Model**: `Organization`, `App\Models\Billing\Subscription`, `App\Models\Billing\Plan`
- **Settings**: `BillingSettings` (`enable_seat_based_billing`)

## Notes

- Only runs when `BillingSettings::enable_seat_based_billing` is true.
- Requires an active subscription whose plan has `is_per_seat` true.
- Quantity is set to `max(1, memberCount)`.
- If the subscription has `gateway_subscription_id`, the payment gateway's `updateSubscriptionQuantity` is called.
