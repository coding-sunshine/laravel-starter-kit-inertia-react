# PRD 17: Xero Accounting Integration

> **Phase 2** — Runs after subscriber migration. Requires sales and commission data.

## Overview

Implement multi-tenant OAuth2 Xero connection, bidirectional contact sync, invoice sync (EOI, training, service, commission), invoice status tracking, commission reconciliation, expense mapping, audit trail, finance dashboards, payment-triggered deal advancement, and rate-limited queued sync jobs. Builds on Phase 1 + PRDs 09-16.

**Prerequisites:** PRDs 00-16 complete (API v2 working, webhook infrastructure in place, deal tracker with payment stages).

## Technical Context

- **HTTP Client:** `saloonphp/saloon` for Xero API integration
- **Webhooks:** `spatie/laravel-webhook-client` for receiving Xero webhooks
- **Invoices:** `laraveldaily/laravel-invoices` for invoice PDF generation
- **Queue:** Laravel queue with rate limiting for Xero API (60 requests/minute)
- **Feature flag:** `XeroIntegrationFeature` (Pennant) — available on Growth and Enterprise plans
- **Module:** `modules/module-crm/`, namespace `Cogneiss\ModuleCrm`

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `XERO_CLIENT_ID` — Required for Xero OAuth2 connection
- `XERO_CLIENT_SECRET` — Required for Xero OAuth2 connection
- `XERO_REDIRECT_URI` — Callback URL for Xero OAuth2 (defaults to `${APP_URL}/xero/callback`)

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Multi-Tenant Xero OAuth2 Connection

**Status:** todo
**Priority:** 1
**Description:** Enable each organization to connect their own Xero tenant with secure token management.

- [ ] Create migration `create_xero_connections_table`: id, organization_id (FK UNIQUE), xero_tenant_id (string), access_token (text encrypted), refresh_token (text encrypted), token_expires_at (timestamp), scopes (jsonb), connected_by (FK users), connected_at (timestamp), timestamps
- [ ] Create `Cogneiss\ModuleCrm\Models\XeroConnection` with `BelongsToOrganization`
- [ ] Create `XeroConnector` in `app/Http/Integrations/Xero/` using Saloon with OAuth2 authenticator
- [ ] OAuth2 flow: `/crm/settings/xero/connect` → redirect to Xero → callback stores tokens
- [ ] Token refresh: automatic on 401 response via Saloon's token refresh middleware
- [ ] Disconnect action: revoke tokens, delete connection record
- [ ] Inertia settings page at `/crm/settings/integrations/xero` showing connection status per org
- [ ] Env: `XERO_CLIENT_ID`, `XERO_CLIENT_SECRET`, `XERO_REDIRECT_URI`
- [ ] Feature-flagged behind `XeroIntegrationFeature`
- [ ] Verify: org admin connects to Xero; tokens stored encrypted; refresh works; disconnect clears tokens

### US-002: Contact Sync to Xero

**Status:** todo
**Priority:** 2
**Description:** Sync CRM contacts to Xero contacts with ID mapping.

- [ ] Create migration: add `xero_contact_id` (string nullable) to `contacts` table
- [ ] Create `SyncContactToXeroAction`: maps Contact fields (name, email, phone, address) to Xero Contact schema
- [ ] Sync triggers: on contact create/update (if org has Xero connection), dispatches `SyncContactToXeroJob`
- [ ] Job uses XeroConnector to POST/PUT contact to Xero API
- [ ] Map response `ContactID` back to `contacts.xero_contact_id`
- [ ] Rate limiting: max 60 Xero API calls per minute (use `spatie/laravel-rate-limited-job-middleware`)
- [ ] Verify: creating a CRM contact syncs to Xero; `xero_contact_id` populated

### US-003: Invoice Sync Engine

**Status:** todo
**Priority:** 1
**Description:** Push EOI, training, service, and commission invoices to Xero.

- [ ] Create migration `create_invoices_table`: id, organization_id (FK), sale_id (FK sales nullable), invoice_type (enum: eoi/training/service/commission), xero_invoice_id (string nullable), status (enum: draft/submitted/authorised/paid/voided), amount (decimal 12,2), tax_amount (decimal 12,2 default 0), due_date (date), contact_id (FK contacts — the billed party), line_items (jsonb), synced_at (timestamp nullable), created_by (FK users), timestamps
- [ ] Create `Cogneiss\ModuleCrm\Models\Invoice` with `BelongsToOrganization`
- [ ] Create `SyncInvoiceToXeroAction`: maps Invoice to Xero Invoice schema, POSTs via XeroConnector
- [ ] Map to correct Xero account code based on invoice_type (configurable per org in settings)
- [ ] Generate invoice PDF via `laraveldaily/laravel-invoices` for local storage
- [ ] Dispatch `SyncInvoiceToXeroJob` on invoice creation
- [ ] Verify: creating a commission invoice syncs to Xero; xero_invoice_id stored

### US-004: Invoice Status Tracking

**Status:** todo
**Priority:** 2
**Description:** Keep invoice status synchronized with Xero via webhooks and polling.

- [ ] Configure `spatie/laravel-webhook-client` to receive Xero webhooks at `/api/webhooks/xero`
- [ ] Handle Xero webhook events: INVOICE.UPDATE → update local invoice status
- [ ] Fallback: scheduled poll job every 15 minutes for invoices with status != paid/voided
- [ ] Display invoice status on deal/sale view: badge showing Draft/Submitted/Paid/Overdue
- [ ] Invoice list page at `/crm/invoices` with DataTable: invoice_type, amount, status, due_date, sale, contact
- [ ] Verify: Xero invoice marked as Paid triggers webhook → local status updates to paid

### US-005: Commission Reconciliation

**Status:** todo
**Priority:** 2
**Description:** Reconcile commission payouts with Xero journal entries.

- [ ] Create `ReconcileCommissionsAction`: compares CRM commissions table with Xero payment records
- [ ] Flag discrepancies: missing in Xero, amount mismatch, unreconciled
- [ ] Reconciliation report page at `/crm/finance/reconciliation`
- [ ] Show: commission_id, CRM amount, Xero amount, status (matched/mismatch/missing)
- [ ] Verify: reconciliation report shows matched and mismatched commissions

### US-006: Expense Mapping

**Status:** todo
**Priority:** 3
**Description:** Map CRM fees to correct Xero chart of accounts.

- [ ] Create settings page for Xero account mapping: invoice_type → Xero account code
- [ ] Store mappings in org settings JSON: `xero_account_map: { eoi: "200", commission: "400", ... }`
- [ ] SyncInvoiceToXeroAction uses these mappings when creating Xero invoices
- [ ] Fetch Xero chart of accounts for dropdown selection in settings
- [ ] Verify: changing account mapping for commissions uses new account code on next sync

### US-007: Finance Audit Trail

**Status:** todo
**Priority:** 3
**Description:** Log all Xero sync actions for audit compliance.

- [ ] Create migration `create_xero_sync_logs_table`: id, organization_id (FK), entity_type (string), entity_id (bigint), action (string: created/updated/synced/failed), xero_request_id (string nullable), response_summary (text nullable), user_id (FK users nullable), created_at
- [ ] Log every Xero API call (create, update, sync) with request/response summary
- [ ] Audit log viewable at `/crm/finance/audit-log` with DataTable
- [ ] Filterable by: entity type, action, date range
- [ ] Verify: syncing an invoice creates an audit log entry viewable in the log page

### US-008: Finance Dashboards

**Status:** todo
**Priority:** 3
**Description:** Build finance dashboard widgets showing cashflow, invoice aging, and agent earnings.

- [ ] Dashboard widgets (Inertia components on `/crm/finance`):
  - Outstanding invoices total and count
  - Invoice aging chart (current, 30d, 60d, 90d+)
  - Cashflow: received this month vs expected
  - Agent earnings: commission totals by agent (from commissions table)
- [ ] Data from local invoices table + commissions table (not live Xero API calls)
- [ ] Date range filter
- [ ] Verify: finance dashboard loads with invoice aging and agent earnings data

### US-009: Payment-Triggered Deal Advancement

**Status:** todo
**Priority:** 2
**Description:** Automatically advance deal stage when an invoice is paid.

- [ ] When invoice status changes to "paid" (via webhook or poll):
  - If invoice_type = "eoi" → update payment_stages record (PRD 14) to paid
  - If invoice_type = "deposit" → advance sale stage to next stage (if automation rule exists)
  - If invoice_type = "commission" → mark commission as received
- [ ] Use automation rules engine (PRD 13) for configurable triggers
- [ ] Log stage advancement in activity_log
- [ ] Verify: Xero invoice marked paid → payment stage updated → sale stage advanced (if rule configured)
