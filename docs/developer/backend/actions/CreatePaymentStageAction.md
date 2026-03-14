# CreatePaymentStageAction

**Location**: `app/Actions/CreatePaymentStageAction.php`
**Last Updated**: 2026-03-15

## Purpose

Creates a `PaymentStage` record associated with a given `Sale`. Automatically inherits `organization_id` from the sale.

## Signature

```php
public function handle(Sale $sale, array $data): PaymentStage
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$sale` | `Sale` | The sale to attach the payment stage to |
| `$data` | `array` | Fields: `stage` (required), `amount`, `due_date`, `paid_at`, `notes` |

## Supported Stage Values

- `eoi` — Expression of Interest
- `deposit` — Deposit payment
- `commission` — Commission payment
- `payout` — Final payout

## Returns

`PaymentStage` — the newly created record.

## Usage

```php
$action = new CreatePaymentStageAction();
$stage = $action->handle($sale, [
    'stage' => 'eoi',
    'amount' => 5000.00,
    'due_date' => '2026-04-01',
]);
```

## Related

- Model: `App\Models\PaymentStage`
- Controller: `App\Http\Controllers\PaymentStageController`
- Routes: `payment-stages.store`
