# ReconcileXeroPaymentAction

## Purpose

Parses incoming Xero payment webhook payloads and creates `XeroReconciliation` records for any invoices that match locally stored Xero invoices.

## Location

`app/Actions/ReconcileXeroPaymentAction.php`

## Method Signature

```php
public function handle(array $payload): void
```

## Dependencies

None (no constructor injection required)

## Parameters

| Parameter  | Type               | Description                                          |
|------------|--------------------|------------------------------------------------------|
| `$payload` | `array<string, mixed>` | Xero webhook payload containing payment data     |

## Return Value

`void` — Creates or updates `XeroReconciliation` records. Logs warnings for unmatched invoices.

## Usage Examples

### From Controller (webhook handler)

```php
app(ReconcileXeroPaymentAction::class)->handle($request->all());
```

## Notes

- Supports both `payments[]` array or single payment payload shapes
- Matches on `Invoice.InvoiceID` to find local `XeroInvoice` records
- Uses `updateOrCreate` on `xero_payment_id` to avoid duplicates
- Called by `XeroController::webhook()` after signature validation
