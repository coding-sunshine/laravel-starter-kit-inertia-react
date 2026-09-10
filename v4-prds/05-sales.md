# PRD 05: Sales and Transactions — Models, Import, and UI

## Overview

Create the sales and transactions domain: sales, commissions, commission_tiers, property_reservations, enquiries, and searches. Includes migrations, models, sync observer registration, data import, and CRM UI. **Prerequisites:** PRD 04 must be complete — projects and lots exist (sales reference lots/projects), lot stage transitions work.

**CRITICAL: Never touch `sites/default/sqlconf.php`. Never commit without human approval.**

## Technical Context

- **Module path:** `modules/module-crm/` — namespace `Cogneiss\ModuleCrm`
- **Companion docs:** `newdb.md` (sales/commissions/reservations/enquiries/searches DDL), `newdb_simple.md`, `plan.md` (Step 4), `sync-architecture.md` (section 5.5 for SaleFieldMapper, section 5.6 for ReservationFieldMapper)
- **Key packages:** `spatie/laravel-model-states` (status/stage), `akaunting/laravel-money` (money fields), `machour/laravel-data-table`
- **CRITICAL FK issue:** v3 `Sale.created_by` and `Sale.updated_by` reference `leads.id` (NOT `users.id`). Field mapper resolves via extra hop: `leads.id` -> `users.lead_id` -> `users.id`. See `sync-architecture.md` section 5.5.
- **Commission normalization:** v3 stores 7 flat commission columns on `sales` table. v4 normalizes to separate rows in `commissions` table.
- **Sync:** Sale and PropertyReservation are bidirectional entities. Observers must be registered.

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `DB_CONNECTION=pgsql` — PostgreSQL connection (set in preflight)
- `DB_LEGACY_HOST`, `DB_LEGACY_PORT`, `DB_LEGACY_DATABASE`, `DB_LEGACY_USERNAME`, `DB_LEGACY_PASSWORD` — Legacy MySQL connection for sales/reservation import
- `STRIPE_KEY`, `STRIPE_SECRET` — Stripe handles all payments including reservation deposits (stubbed OK for now, activate when credentials ready)

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Sales and Transaction Migrations and Models

**Status:** todo
**Priority:** 1
**Description:** Create migrations and models for sales, commissions, commission_tiers, property_reservations, property_enquiries, and property_searches. All in `modules/module-crm/`.

**Sale Status (spatie/model-states):** Define states for `Sale.status`: `pending`, `processing`, `settled`, `cancelled`, `refunded`. Create state classes in `modules/module-crm/src/Models/States/SaleStatus/`. Allowed transitions: pending→processing, processing→settled|cancelled, settled→refunded.

**Payment Gateway — Stripe Only:**

Stripe handles all payments: subscriptions (Stripe Billing), one-time credit purchases (Stripe Checkout), and reservation deposits (Stripe Payment Intents). Use `stripe/stripe-php` directly — no gateway abstraction needed. Store Stripe transaction/payment intent IDs on reservation/sale rows for audit and Xero reconciliation (PRD 17).

**Migrations** (all with `organization_id` FK CASCADE, see `newdb_simple.md` for complete columns):

1. `create_sales_table.php`:
   - `organization_id` FK CASCADE, `legacy_sale_id` BIGINT UNIQUE
   - `lot_id` FK SET NULL, `project_id` FK SET NULL, `developer_id` FK SET NULL
   - 7 contact FK columns (all FK SET NULL to contacts): `client_contact_id`, `sales_agent_contact_id`, `subscriber_contact_id`, `bdm_contact_id`, `referral_partner_contact_id`, `affiliate_contact_id`, `agent_contact_id`
   - `status` VARCHAR (model-state)
   - `purchase_price` DECIMAL(12,2), `finance_due_date` DATE, `settlement_date` DATE, `contract_date` DATE
   - `extra_attributes` JSONB + GIN index (stores: expected_commissions, divide_percent, custom_attributes, comments, summary_note, comm_in_notes, comm_out_notes)
   - `synced_at` TIMESTAMPTZ, `sync_source` VARCHAR(10)
   - `created_by`/`updated_by` FK SET NULL to users
   - timestamps, softDeletes

2. `create_commissions_table.php`:
   - `sale_id` FK CASCADE, `organization_id` FK CASCADE
   - `type` VARCHAR with CHECK constraint (piab, affiliate, subscriber, sales_agent, bdm, agent, referral_partner)
   - `amount` DECIMAL(12,2) NOT NULL
   - UNIQUE constraint on `(sale_id, type)`
   - `legacy_commission_id` BIGINT
   - timestamps

3. `create_commission_tiers_table.php`:
   - `organization_id` FK CASCADE
   - `volume_threshold` INTEGER NOT NULL
   - `commission_rate` DECIMAL(5,4) NOT NULL
   - timestamps

4. `create_property_reservations_table.php`:
   - `organization_id` FK CASCADE, `legacy_reservation_id` BIGINT UNIQUE
   - `lot_id` FK SET NULL, `project_id` FK SET NULL
   - `agent_contact_id` FK SET NULL, `primary_contact_id` FK SET NULL, `secondary_contact_id` FK SET NULL, `logged_in_user_id` FK SET NULL
   - `purchase_price` NUMERIC(12,2), `deposit_amount` NUMERIC(12,2)
   - `status` VARCHAR (model-state)
   - `purchaser_type` JSONB, `trustee_name`, `finance_pre_approved` BOOLEAN, `finance_days_required` SMALLINT
   - `abn_acn`, `is_family_trust` BOOLEAN, `is_bare_trust` BOOLEAN, `is_smsf_trust` BOOLEAN, `is_funds_rollover` BOOLEAN
   - `broker_name`, `firm_name`, `is_agreed` BOOLEAN, `is_lawlab_agreed` BOOLEAN, `contract_sent_at` TIMESTAMPTZ
   - `synced_at`, `sync_source`
   - `created_by`/`updated_by` FK SET NULL, timestamps, softDeletes

5. `create_property_enquiries_table.php`:
   - `contact_id` FK SET NULL, `project_id` FK SET NULL, `organization_id` FK CASCADE
   - `extra_attributes` JSONB + GIN index (stores 10 detail fields from v3)
   - timestamps, softDeletes

6. `create_property_searches_table.php`:
   - `contact_id` FK SET NULL, `organization_id` FK CASCADE
   - `search_criteria` JSONB + GIN index (stores 12 criteria fields from v3)
   - timestamps, softDeletes

**Models** (all `Cogneiss\ModuleCrm\Models`):
- `Sale`: `BelongsToOrganization`, `Syncable`, `SoftDeletes`, `LogsActivity`. `spatie/model-states` for status. `akaunting/money` for purchase_price. `hasMany(Commission)`. Use `DB::transaction()` + `lockForUpdate()` for lot stage changes on sale creation.
- `Commission`: `BelongsToOrganization`. `belongsTo(Sale)`. `akaunting/money` for amount.
- `CommissionTier`: `BelongsToOrganization`.
- `PropertyReservation`: `BelongsToOrganization`, `Syncable`, `SoftDeletes`. `spatie/model-states` for status.
- `PropertyEnquiry`: `BelongsToOrganization`, `SoftDeletes`.
- `PropertySearch`: `BelongsToOrganization`, `SoftDeletes`.

Register sync observers: `Sale::observe(SaleSyncObserver::class)`, `PropertyReservation::observe(ReservationSyncObserver::class)`.

- [ ] All 6 tables created with correct schema
- [ ] `organization_id` FK CASCADE on all tables
- [ ] Sales table has 7 contact FK columns (all SET NULL)
- [ ] UNIQUE constraint on `commissions(sale_id, type)` prevents duplicate commission types per sale
- [ ] Property reservations table has all trust/finance boolean fields
- [ ] Enquiries and searches have JSONB columns with GIN indexes
- [ ] All status/stage columns use `spatie/model-states`
- [ ] `SaleSyncObserver` and `ReservationSyncObserver` registered in service provider
- [ ] `php artisan migrate` succeeds
- [ ] Sale status uses spatie/model-states with defined transitions

> **Note (2026-03-25):** Commission tracking by construction stage (Unconditional, Frame, Lock-up, Completion) is defined in **PRD 12 US-007** (`sale_commission_payments` table). The `commissions` table here stores the total commission per type (piab, affiliate, subscriber, etc.). PRD 12's `sale_commission_payments` breaks each commission into stage-based installments using `commission_templates.stages` JSONB splits. These two tables work together: `commissions` = what is owed, `sale_commission_payments` = when it's paid.

### US-002: Sales and Transaction Data Import

**Status:** todo
**Priority:** 2
**Description:** Import 447 sales (with commission normalization), 120 reservations, 721 enquiries, and 316 searches from v3. Commands in `modules/module-crm/src/Console/Commands/`.

**ImportSales.php:**
- Read `mysql_legacy.sales` (447 rows)
- Per sale in `DB::transaction()`:
  - Set `organization_id` via `subscriber_id` -> Lead -> User -> Org chain. For 2 sales without `subscriber_id`: use `created_by` -> Lead -> User -> Org.
  - Store `legacy_sale_id`
  - Remap 7 contact FKs using `legacy_lead_id` MAP: client_id, sales_agent_id, subscriber_id, bdm_id, referral_partner_id, affiliate_id, agent_id -> corresponding `*_contact_id` columns
  - **CRITICAL FK resolution for created_by/updated_by:** v3 `created_by` references `leads.id`. Extra hop: `leads.id` -> find `users` record via `users.lead_id` -> use that `users.id`
  - Normalize commissions: for each of 7 commission columns (piab_comm, affiliate_comm, subscriber_comm, sales_agent_comm, bdm_comm, agent_comm, referral_partner_comm): if value > 0, INSERT into `commissions(sale_id, type, amount)`
  - Move to `extra_attributes` JSONB: divide_percent, expected_commissions, custom_attributes, comments, summary_note, comm_in_notes, comm_out_notes
  - Status from `statusables` table (resolve most recent status name)
- Idempotent: skip if `legacy_sale_id` already exists

**ImportReservations.php:**
- 120 rows. Store `legacy_reservation_id`.
- Remap: `purchaser1_id` -> `primary_contact_id`, `purchaser2_id` -> `secondary_contact_id`, `agent_id` -> `agent_contact_id`, `property_id` -> `project_id` (via `legacy_project_id`)
- Type casts: `purchase_price` varchar->NUMERIC, `deposit` varchar->`deposit_amount` NUMERIC, `finance_preapproval` varchar->boolean, `finance_days_req` varchar->SMALLINT
- JSONB extractions: `family_trust`/`bare_trust_setup`/`SMSF_trust_setup`/`funds_rollover` JSON -> boolean flags
- `broker` JSON -> `broker_name` varchar, `firm` JSON -> `firm_name` varchar
- Status from `statusables` (default 'pending')

**ImportEnquiries.php:**
- 721 rows. Move 10 detail fields -> `extra_attributes` JSONB

**ImportSearches.php:**
- 316 rows. Move 12 criteria fields -> `search_criteria` JSONB

- [ ] `Sale::count()` = 447
- [ ] `Sale::whereNull('organization_id')->count()` = 0
- [ ] `Sale::whereNull('legacy_sale_id')->count()` = 0
- [ ] `Commission::count()` equals count of non-zero commission columns in v3
- [ ] No duplicate commission types per sale: query for duplicates returns 0
- [ ] `CommissionTier::count()` > 0 (default rates seeded)
- [ ] `PropertyReservation::count()` = 120
- [ ] `PropertyEnquiry::count()` = 721
- [ ] `PropertySearch::count()` = 316
- [ ] Spot-check: 10 random sales have org_id and at least 1 contact FK populated
- [ ] Sale status populated: `Sale::first()?->status` returns a valid state
- [ ] `created_by` correctly resolved (not referencing leads.id)
- [ ] Re-running imports is idempotent
- [ ] `php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Sale::count()"` → 449
- [ ] `php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Commission::count()"` → matches expected

### US-003: Sales and Transaction UI Pages

**Status:** todo
**Priority:** 3
**Description:** Create CRM UI pages for sales and reservations following the DataTable pattern.

**Backend:**
- `modules/module-crm/src/DataTables/SaleDataTable.php`: Columns: client (contact name), lot, project, status, purchase_price, commissions total (computed), created_at. Filterable: status, project_id, lot_id, organization_id.
- `modules/module-crm/src/DataTables/ReservationDataTable.php`: Columns: primary_contact, lot, project, purchase_price, status, created_at.
- `modules/module-crm/src/Http/Controllers/SaleController.php`: `show()` with tabs (overview, commissions breakdown, documents, notes). `create()`: select contact + lot -> auto-fill project/developer, commission tier calculation.
- `modules/module-crm/src/Http/Controllers/ReservationController.php`: `show()`: purchaser details, trust/finance fields, contract status.
- Routes at `modules/module-crm/routes/web.php`: `/crm/sales` (CRUD + bulk), `/crm/reservations` (CRUD + bulk).

**Frontend:**
- `resources/js/pages/crm/sales/`: index.tsx, show.tsx, create.tsx, edit.tsx
- `resources/js/pages/crm/reservations/`: index.tsx, show.tsx, create.tsx, edit.tsx
- Sale create: select contact + lot -> auto-fill project/developer, show commission tier calc
- Commission breakdown visible on sale detail page
- TypeScript types in `resources/js/types/crm/sale.ts` and `reservation.ts`

Run `php artisan wayfinder:generate` after creating routes.

- [ ] Sales list page loads at `/crm/sales` (200)
- [ ] Sale DataTable columns: client, lot, project, status, purchase_price, commissions total, created_at
- [ ] Sale filtering: status, project_id, lot_id, organization_id
- [ ] Reservation list page loads at `/crm/reservations` (200)
- [ ] Sale show page with commission breakdown tab
- [ ] Sale create auto-fills project/developer when lot selected
- [ ] Reservation show page with trust/finance details
- [ ] Wayfinder route helpers generated
