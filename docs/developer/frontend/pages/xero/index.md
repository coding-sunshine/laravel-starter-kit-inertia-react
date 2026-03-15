# Xero Integration Dashboard (`xero/index`)

## Purpose

Displays the Xero integration status, sync statistics, action buttons for contact and invoice sync, and a list of recent payment reconciliations.

## Location

`resources/js/pages/xero/index.tsx`

## Route

`GET /xero` → `xero.index`

## Props

| Prop                     | Type                     | Description                                   |
|--------------------------|--------------------------|-----------------------------------------------|
| `connection`             | `XeroConnection \| null` | Active Xero connection or null if disconnected |
| `xero_contacts_count`    | `number`                 | Number of synced contacts                      |
| `xero_invoices_count`    | `number`                 | Number of synced invoices                      |
| `reconciliations_count`  | `number`                 | Total reconciliation records                   |
| `recent_reconciliations` | `XeroReconciliation[]`   | Last 10 reconciliation records                 |
| `is_configured`          | `boolean`                | Whether XERO_CLIENT_ID is set                  |

## UI Sections

- **Connection status card**: green (connected), yellow (not configured/deferred), red (not connected)
- **Stats row**: Synced Contacts, Synced Invoices, Reconciliations
- **Action buttons**: Sync Contacts, Sync Invoices (disabled when not connected)
- **Recent reconciliations table**: Payment ID, Invoice number, amount, date

## Pan Analytics

- `xero-tab` — Connect/Disconnect button
- `xero-sync-contacts` — Sync Contacts button
- `xero-sync-invoices` — Sync Invoices button
