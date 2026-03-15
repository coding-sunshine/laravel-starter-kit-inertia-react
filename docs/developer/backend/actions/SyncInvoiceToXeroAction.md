# SyncInvoiceToXeroAction

## Purpose

Syncs a local Sale to Xero as an accounts-receivable invoice (ACCREC). If Xero is not configured (missing `XERO_CLIENT_ID`), logs a deferred warning and returns early.

## Location

`app/Actions/SyncInvoiceToXeroAction.php`

## Method Signature

```php
public function handle(Sale $sale, XeroConnection $connection): void
```

## Dependencies

None (no constructor injection required)

## Parameters

| Parameter    | Type              | Description                            |
|--------------|-------------------|----------------------------------------|
| `$sale`      | `Sale`            | The local sale/deal to sync as invoice |
| `$connection`| `XeroConnection`  | The active Xero connection/tenant      |

## Return Value

`void` — Creates or updates a `XeroInvoice` record. Logs `xero.deferred` if not configured.

## Usage Examples

### From Job

```php
app(SyncInvoiceToXeroAction::class)->handle($sale, $connection);
```

### Dispatched via SyncInvoiceToXeroJob

```php
SyncInvoiceToXeroJob::dispatch($sale, $connection);
```

## Notes

- Guarded by `XeroConnector::isConfigured()` — safe to call even without credentials
- Defaults to `invoice_type = ACCREC` (accounts receivable)
- Related job: `app/Jobs/SyncInvoiceToXeroJob.php`
