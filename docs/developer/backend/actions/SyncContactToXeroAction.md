# SyncContactToXeroAction

## Purpose

Syncs a local CRM Contact to Xero as a contact record via the Xero API. If Xero is not configured (missing `XERO_CLIENT_ID`), logs a deferred warning and returns early without error.

## Location

`app/Actions/SyncContactToXeroAction.php`

## Method Signature

```php
public function handle(Contact $contact, XeroConnection $connection): void
```

## Dependencies

None (no constructor injection required)

## Parameters

| Parameter    | Type              | Description                            |
|--------------|-------------------|----------------------------------------|
| `$contact`   | `Contact`         | The local CRM contact to sync          |
| `$connection`| `XeroConnection`  | The active Xero connection/tenant      |

## Return Value

`void` — Creates or updates a `XeroContact` record. Logs `xero.deferred` if not configured.

## Usage Examples

### From Job

```php
app(SyncContactToXeroAction::class)->handle($contact, $connection);
```

### Dispatched via SyncContactToXeroJob

```php
SyncContactToXeroJob::dispatch($contact, $connection);
```

## Notes

- Guarded by `XeroConnector::isConfigured()` — safe to call even without credentials
- Uses `updateOrCreate` to avoid duplicate records
- Related job: `app/Jobs/SyncContactToXeroJob.php`
