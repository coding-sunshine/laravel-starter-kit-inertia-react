# Step 22 (Step 16): Phase 2 — Xero Integration (Financial Core)

## Goal

Implement **multi-tenant OAuth2** for Xero, **contact sync** (Fusion → Xero), **invoice sync** (EOI, training, service, commission), **invoice status tracking**, **commission reconciliation**, **expense mapping**, **audit trail**, **finance dashboards**, **payment triggers** (advance deal on payment), and **Laravel queued jobs** for rate-limited sync. Builds on Steps 0–21.

## Starter Kit References

- **Laravel Queue**: Jobs, rate limiting, retries
- **OAuth2**: Xero OAuth2 provider (e.g. league/oauth2-client or package)
- **Filament**: Finance dashboard widgets; settings for Xero mapping

## Deliverables

1. **Multi-tenant OAuth2**: Each org connected to own Xero tenant; store tokens per org; refresh flow.
2. **Contact sync**: Fusion contacts (or subset) → Xero contacts; map contact_id to Xero contact id; sync on create/update or scheduled.
3. **Invoice sync engine**: Push EOI, training, service, and commission invoices to Xero; map to correct Xero account/contact. Use **laraveldaily/laravel-invoices** for invoice PDF generation. See 00-kit-package-alignment.md.
4. **Invoice status tracking**: Live sync of Paid, Draft, Overdue from Xero; display on deal or invoice list.
5. **Commission reconciliation**: Payouts/journals logged and synced to Xero.
6. **Expense mapping**: Fees → correct Xero chart of accounts.
7. **Audit trail**: Log all sync actions and user actions for finance.
8. **Finance dashboards**: Live cashflow, invoice aging, agent earnings (from Fusion + Xero data).
9. **Payment triggers**: Advance deal stage when invoice paid (webhook or poll).
10. **Laravel queued jobs**: Rate-limited, scalable sync jobs; failure handling and retries.

## DB Design (this step)

- **xero_connections** (organization_id, tenant_id, tokens json, refreshed_at); **invoices** (sale_id or deal_id, xero_invoice_id, status, synced_at); **sync_logs** (entity, action, request_id, response_summary).

## Data Import

- **None.** Sync is ongoing.

## AI Enhancements

- None required.

## Verification (verifiable results)

- Connect org to Xero; sync contacts; create and push invoice; confirm status in Xero; reconcile commission; view finance dashboard; trigger stage on payment.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 23 until the human has completed the checklist below.**

Human must:
- [ ] Confirm Xero OAuth2 connects per org and tokens refresh.
- [ ] Confirm contact sync and invoice sync run successfully.
- [ ] Confirm invoice status tracks from Xero.
- [ ] Confirm commission reconciliation and expense mapping work.
- [ ] Confirm finance dashboards display correct data.
- [ ] Confirm payment trigger advances deal stage.
- [ ] Approve proceeding to Step 23 (Phase 2: Auto signup & onboarding).

## Acceptance Criteria

- [ ] Multi-tenant Xero OAuth2, contact sync, invoice sync and status, commission reconciliation, expense mapping, audit trail, finance dashboards, payment triggers, and queued sync jobs delivered per scope.
