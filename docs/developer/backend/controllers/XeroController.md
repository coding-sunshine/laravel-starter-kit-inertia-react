# XeroController

## Purpose

Handles the Xero OAuth2 connection lifecycle, contact and invoice sync dispatch, and inbound Xero payment webhook reconciliation.

## Location

`app/Http/Controllers/XeroController.php`

## Routes

| Method | URI                   | Action          | Name                   |
|--------|-----------------------|-----------------|------------------------|
| GET    | `/xero`               | `index`         | `xero.index`           |
| GET    | `/xero/connect`       | `connect`       | `xero.connect`         |
| GET    | `/xero/callback`      | `callback`      | `xero.callback`        |
| POST   | `/xero/disconnect`    | `disconnect`    | `xero.disconnect`      |
| POST   | `/xero/sync-contacts` | `syncContacts`  | `xero.sync-contacts`   |
| POST   | `/xero/sync-invoices` | `syncInvoices`  | `xero.sync-invoices`   |
| POST   | `/xero/webhook`       | `webhook`       | `xero.webhook`         |

## Methods

### `index()`

Returns the Xero dashboard Inertia page with connection status, sync counts, and recent reconciliations.

### `connect()`

Initiates the Xero OAuth2 flow. If `XERO_CLIENT_ID` is not set, logs a deferred warning and redirects back with a flash message.

### `callback(Request $request)`

Handles the OAuth2 callback. Stores a pending `XeroConnection` record. Token exchange is deferred until credentials are fully configured.

### `disconnect(Request $request)`

Soft-deletes the active `XeroConnection` and sets `disconnected_at`.

### `webhook(Request $request)`

Validates the `x-xero-signature` HMAC-SHA256 header against `XERO_WEBHOOK_KEY`. On success, delegates to `ReconcileXeroPaymentAction`.

### `syncContacts(Request $request)`

Dispatches `SyncContactToXeroJob` for up to 50 recent contacts.

### `syncInvoices(Request $request)`

Dispatches `SyncInvoiceToXeroJob` for up to 50 recent sales.

## Notes

- Routes under `auth + verified` middleware except `/xero/webhook` which is public (CSRF exempt)
- OAuth deferred: XERO_CLIENT_ID / XERO_CLIENT_SECRET not required for app to boot
