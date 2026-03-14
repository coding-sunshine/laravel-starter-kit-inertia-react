# Step 4: Property Reservations, Sales, and Commissions

## Goal

Add **property_reservations**, **property_enquiries**, **property_searches**, **sales**, and **commissions** with **contact_id**-based FKs only (no lead_id). Import from MySQL using the lead_id→contact_id map.

**Reservation/deposit payments:** Legacy uses **eWAY** for reservation fees. Integrate **eWAY via Saloon** (see [00-payment-gateway-decision.md](./00-payment-gateway-decision.md)) for deposit/one-off charges; store gateway transaction refs on reservation/sale rows for Xero reconciliation (Step 22). Subscriptions remain Stripe via kit billing (Step 23).

## Suggested implementation order (use kit generators first)

1. **Generate models** with `make:model:full` for each entity below; then add plan-specific columns (all *_contact_id FKs, organization_id, userstamps) to migrations and models.
2. **Run** `make:filament-resource` for PropertyReservation, PropertyEnquiry, PropertySearch, Sale; optional DataTables.
3. **Implement** `fusion:import-reservations-sales` and `fusion:verify-import-reservations-sales`.

**Models to generate:** PropertyReservation, PropertyEnquiry, PropertySearch, Sale, Commission. Example:
```bash
for m in PropertyReservation PropertyEnquiry PropertySearch Sale Commission; do php artisan make:model:full $m --migration --factory --seed --category=development; done
```
Then edit migrations for full column lists and contact_id FKs per Deliverables.

## Starter Kit References

- **Database**: `docs/developer/backend/database/`, userstamps, activity log
- **DataTable**: `docs/developer/backend/data-table.md`
- **Actions/Controllers**: Single-action classes and CRUD controllers
- **Filament**: `docs/developer/backend/filament.md`

## Deliverables

1. **Migrations**
   - **property_reservations**: id, organization_id, agent_contact_id (FK → contacts), primary_contact_id (FK → contacts), secondary_contact_id (nullable, FK → contacts), logged_in_user_id (nullable, FK → users), property_id, lot_id (string or FK), purchase_price, purchaser_type (json), trustee_name, abn_acn, SMSF_trust_setup, bare_trust_setup, funds_rollover, agree_lawlab, firm, broker, finance_condition, finance_days, deposit, deposit_bal, build_deposit, payment_duedate, contract_send, agree, agree_date, timestamps, soft_deletes, created_by, updated_by. **Status**: use **spatie/laravel-model-states** for reservation status; **filament-statefusion** for transition UI.
   - **property_enquiries**: id, organization_id, client_contact_id, agent_contact_id, logged_in_user_id (nullable), property refs, … (existing enquiry fields), timestamps, created_by, updated_by.
   - **property_searches**: id, organization_id, client_contact_id, agent_contact_id, logged_in_user_id (nullable), … (existing search fields), timestamps, created_by, updated_by.
   - **sales**: id, organization_id, **legacy_id** (bigint, nullable, indexed — MySQL sales.id), client_contact_id (FK → contacts), lot_id (FK → lots), project_id (FK → projects), developer_id (nullable), comm_in_notes, comm_out_notes, payment_terms, expected_commissions (json), finance_due_date, comms_in_total, comms_out_total, piab_comm, affiliate_contact_id, affiliate_comm, subscriber_contact_id, subscriber_comm, sales_agent_contact_id, sales_agent_comm, bdm_contact_id, bdm_comm, referral_partner_contact_id, referral_partner_comm, agent_contact_id, agent_comm, divide_percent (json), is_comments_enabled, comments, is_sas_enabled, is_sas_max, sas_percent, sas_fee, summary_note, status_updated_at, timestamps, soft_deletes, created_by, updated_by. **Status**: use **spatie/laravel-model-states** for sale status; **filament-statefusion** for transition UI. **Scout**: add Searchable to Sale. **Money**: use **akaunting/laravel-money** for commission/price columns.
   - **commissions**: id, sale_id (FK → sales), **commission_type** (enum: `piab` | `subscriber` | `affiliate` | `sales_agent` | `referral_partner` | `bdm` | `sub_agent` — 7 types confirmed on live site with 18 commission-related columns), agent_user_id (FK → users, nullable — null for platform-level piab type), rate_percentage (decimal 5,2, nullable), amount (decimal 12,2), override_amount (boolean, default false — when true, manual entry bypasses auto-calculation), notes (text, nullable), timestamps, soft_deletes. **Note**: replaces the legacy polymorphic `commissions` table with a typed, sale-scoped approach. All 7 commission types are tracked per sale. Index: sale_id, commission_type, agent_user_id.

   **Commission types explained**:
   | Type | Description | agent_user_id |
   |---|---|---|
   | `piab` | Platform fee (% of sale to PIAB) | null |
   | `subscriber` | Subscriber org commission | subscriber user |
   | `affiliate` | Affiliate referral commission | affiliate user |
   | `sales_agent` | Primary sales agent | sales agent user |
   | `referral_partner` | External referral partner | referral partner user |
   | `bdm` | Business Development Manager | BDM user |
   | `sub_agent` | Sub-agent or co-agent | sub-agent user |
   - **Email (laravel-database-mail)**: Create **ReservationCreatedEvent**; register in `config/database-mail.php` so confirmation is sent to primary contact. See 00-kit-package-alignment.md.

2. **Models**
   - PropertyReservation, PropertyEnquiry, PropertySearch: relationships to Contact (agent, client, primary/secondary), User (logged_in_user), Organization.
   - Sale: relationships to Contact (client, sales_agent, subscriber, bdm, referral_partner, affiliate, agent), Lot, Project, Organization; commissions() morphMany.
   - Commission: morphTo commissionable (Sale).

3. **Import command**
   - `php artisan fusion:import-reservations-sales`. Source: MySQL property_reservations, property_enquiries, property_searches, sales, commissions. Target: new PostgreSQL tables.
   - **Mapping** (use lead_id→contact_id from Step 1):
     - property_reservations: loggedin_agent_id → agent_contact_id (or logged_in_user_id if you store user), agent_id → agent_contact_id, purchaser1_id → primary_contact_id, purchaser2_id → secondary_contact_id.
     - property_enquiries: loggedin_agent_id → logged_in_user_id or agent_contact_id, agent_id → agent_contact_id, client_id → client_contact_id.
     - property_searches: same as enquiries.
     - sales: client_id → client_contact_id, subscriber_id → subscriber_contact_id, sales_agent_id → sales_agent_contact_id, bdm_id → bdm_contact_id, referral_partner_id → referral_partner_contact_id, affiliate_id → affiliate_contact_id, agent_id → agent_contact_id.
   - Preserve lot_id, project_id, developer_id (map if IDs changed). Commissions: commissionable_type = Sale, commissionable_id = new sale id.

4. **Filament / DataTables / Inertia**
   - Filament resources for PropertyReservation, PropertyEnquiry, PropertySearch, Sale. **DataTables:** Add **HasAi** and **HasAuditLog** to SaleDataTable and any PropertyReservation DataTable (compliance logging for inline edits; see 00-kit-package-alignment.md). Use **spatie/laravel-data** for DTOs in import commands and API responses (kit convention). Optional Inertia list/detail pages.

## DB Design (this step)

- New tables: property_reservations, property_enquiries, property_searches, sales, commissions. All person FKs are contact_id or named *_contact_id. See `00-database-design.md` § 4.2.

## Data Import

- **Source**: MySQL tables above.
- **Target**: New PostgreSQL tables with contact_id columns.
- **Mapping**: Every old lead_id (and client_id, agent_id, purchaser1_id, etc.) → contact_id via Step 1 map. Store old sale id → new sale id for commissions. Run after Steps 1 and 3 (contacts and projects/lots exist).

## UI Specification

### Reservation Record Layout (full-page, milestone-focused)

```
┌───────────────────────────────────────────────────┐
│  Reservation #1234  [stage badge]  [···actions]   │
├──────────────┬────────────────────────────────────┤
│  Milestone   │  Details panel                     │
│  Timeline    │  Contact: [name] → record link     │
│  (vertical,  │  Lot: [lot#, project] → links      │
│  left 40px)  │  Assigned agent + commission %     │
│              │  Deposit: $X,XXX (eWAY status)     │
│              │  Settlement date                   │
│              │  Notes (inline editable)           │
│              │  Activity log                      │
└──────────────┴────────────────────────────────────┘
```

**Milestone steps** (model-states, left vertical timeline):
Enquiry → Qualified → Reservation → Contract → Unconditional → Settled

Each step: circle icon (filled=completed, hollow=pending), date stamp when completed, actor avatar. Active step highlighted. Clicking a step → triggers state transition modal (filament-statefusion or Inertia modal).

### Create Reservation Form (slide-over, `max-w-2xl`)

```
Section 1 — Parties
  Contact (searchable) | Secondary Contact (optional)
  Assigned Agent (searchable user)

Section 2 — Property
  Project (searchable) | Lot (filtered by project, shows status)

Section 3 — Terms
  Purchase Price | Deposit Amount | Settlement Date
  Finance Condition (toggle) | Finance Days

Section 4 — eWAY Deposit
  Trigger eWAY payment? (toggle)
  → if yes: eWAY hosted payment redirect (store transaction_id + access_code on return)
  → Deposit Status badge: Pending / Paid / Failed + “Retry payment” if failed

Section 5 — Notes
  Notes (textarea)
```

### Commission Input UI (inline within Sale record)

**Inline commission table** — one row per commission type. All 7 types shown (some may be $0). Editable inline via `HasInlineEdit`.

| Party | Type | Agent (searchable) | Rate % | Amount ($) | Override | Notes |
|---|---|---|---|---|---|---|
| PIAB | piab | — | 2.5% | $X,XXX | ☐ | |
| Subscriber | subscriber | [user search] | 1.0% | $XXX | ☐ | |
| Sales Agent | sales_agent | [user search] | 1.5% | $XXX | ☐ | |
| BDM | bdm | [user search] | 0.5% | $XXX | ☐ | |
| Referral Partner | referral_partner | [user search] | 0.5% | $XXX | ☐ | |
| Affiliate | affiliate | [user search] | 0.5% | $XXX | ☐ | |
| Sub Agent | sub_agent | [user search] | 0.0% | $0 | ☐ | |
| **Total** | | | | **$X,XXX** | | |

- **Auto-calculate**: `amount = lot.price × (rate_percentage / 100)`. Triggers on `rate_percentage` change.
- **Override flag**: Check → amount field becomes freely editable; removes auto-calc lock. Badge shows “Overridden” on row.
- **”Recalculate All”** button: Resets all non-overridden rows to auto-calculated amounts.
- Totals row: sum of all `amount` values. PIAB amount shown separately as “Platform fee”.

**CommissionDataTable** (`/commissions` — standalone view):
- View all commissions across all sales. Filter by: commission_type, agent_user, date range (settled_at), project.
- Analytics: total by type (pie chart friendly), total by agent (bar chart friendly).
- `tableAiSystemContext()`: `'You are analyzing commission records for a real estate agency. 7 commission types: piab (platform), subscriber, affiliate, sales_agent, referral_partner, bdm, sub_agent. Help calculate totals by agent and type, compare month-over-month, and identify top performers.'`

### eWAY Payment Flow

NOT built from scratch — uses eWAY hosted payment page (redirect flow):
1. Backend generates `transaction_access_code` via eWAY Saloon integration
2. User is redirected to eWAY hosted page
3. On return, store `transaction_id` + `transaction_access_code` on reservation/sale row
4. Status badge: **Pending** (gray) / **Paid** (green) / **Failed** (red)
5. “Retry payment” button visible if status = Failed → re-initiates flow
6. eWAY status shown in Reservation RHS details panel and ReservationDataTable `deposit_status` column

### Create/Edit Sale Form (slide-over, `max-w-2xl`)

> **Note**: In v4, sales are typically promoted from Reservations via state transition, not created standalone. However, the form is needed for manual entry and corrections.

```
Section 1 — Transaction
  Contact (searchable, read-only if promoted from Reservation)
  Lot (searchable, read-only if from Reservation) | Project (auto-filled)
  Sale Price* | Settlement Date

Section 2 — Commission (repeat rows, one per party)
  Party role (dropdown: PIAB / sales_agent / bdm / referral_partner / affiliate / subscriber)
  Contact (searchable) | Commission % | Dollar amount (auto-calc, override toggle)

Section 3 — Notes
  Comm In Notes | Comm Out Notes | Summary Note

Section 4 — Flags
  SMSF toggle | SAS toggle | SAS % | SAS Fee
  Comments enabled toggle
```

Save: close, flash toast, SaleDataTable refreshes.

### Sale Detail Page (full-page, tabbed)

Tabs: **Details** (main fields + commission table) | **Activity** (log) | **Documents** | **AI Insights** (C1 rendered commission forecast + next-best-action card).

### DataTable Configuration — ReservationDataTable

**Traits**: `HasExport, HasSelectAll, HasAuditLog, HasAi`

**Columns**:

| Column | Type | Options |
|---|---|---|
| `id` | `text` | label “#” |
| `contact.full_name` | `text` | searchable, linkTo contact |
| `lot.title` | `text` | searchable, linkTo lot |
| `project.title` | `text` | filterable |
| `stage` | `badge` | state machine stages, colorMap |
| `deposit_amount` | `money` | — |
| `deposit_status` | `badge` | pending=gray, paid=green, failed=red |
| `assigned_agent` | `avatar_text` | filterable |
| `settlement_date` | `date` | sortable |
| `created_at` | `date` | sortable |

**`tableQuickViews()`**:
```php
return [
    QuickView::make('active')->label('Active')->default()->filter(['stage' => ['not_in' => ['settled', 'cancelled']]]),
    QuickView::make('settling_soon')->label('Settling Soon')->filter(['settlement_date' => ['days_from_now_lte' => 30]]),
    QuickView::make('deposit_pending')->label('Deposit Pending')->filter(['deposit_status' => 'pending']),
    QuickView::make('settled')->label('Settled')->filter(['stage' => 'settled']),
];
```

**`tableAnalytics()`**:
```php
return [
    Analytic::make('active')->label('Active Reservations')->query(
        fn($q) => $q->whereNotIn('stage', ['settled', 'cancelled'])->count()
    ),
    Analytic::make('settling_month')->label('Settling This Month')->query(
        fn($q) => $q->whereMonth('settlement_date', now()->month)->count()
    ),
    Analytic::make('total_value')->label('Pipeline Value')->query(
        fn($q) => $q->join('lots', 'property_reservations.lot_id', '=', 'lots.id')
                    ->sum('lots.price'),
        format: 'money'
    ),
];
```

**`tableAiSystemContext()`**:
```php
return 'You are analyzing a property reservation pipeline for a real estate sales agency. Reservations
track buyers from initial enquiry to settlement. Stages: enquiry/qualified/reservation/contract/
unconditional/settled. Key fields: deposit_status (eWAY payment), settlement_date, assigned_agent.
Help identify at-risk deals, upcoming settlements, and unpaid deposits.';
```

### DataTable Configuration — SaleDataTable

**Traits**: `HasExport, HasSelectAll, HasAuditLog, HasAi`

**Columns**:

| Column | Type | Options |
|---|---|---|
| `contact.full_name` | `text` | searchable |
| `lot.title` | `text` | searchable |
| `project.title` | `text` | filterable |
| `sale_price` | `money` | sortable |
| `commission_total` | `money` | computed from commissions sum |
| `status` | `badge` | state machine, colorMap |
| `settled_at` | `date` | sortable |
| `assigned_agent` | `avatar_text` | filterable |

**`tableQuickViews()`**:
```php
return [
    QuickView::make('active')->label('Active')->default()->filter(['status' => ['not_in' => ['cancelled']]]]),
    QuickView::make('settling_soon')->label('Settling Soon')->filter(['settled_at' => ['days_from_now_lte' => 30]]),
    QuickView::make('this_month')->label('Settled This Month')->filter(['settled_at' => ['this_month' => true]]),
];
```

**`tableAiSystemContext()`**:
```php
return 'You are analyzing a property sales register for a real estate agency. Sales track completed or
in-progress property transactions. Key fields: sale_price, commission_total (sum of all agent commissions),
status (state machine), settled_at. Help calculate commission forecasts and identify pipeline value.';
```

## AI Enhancements

- **Sale/commission summary**: Use Prism to generate a human-readable commission breakdown from comm_in_notes, comm_out_notes, and commission fields. Rendered via Thesys C1 `CommissionTable` component on Sale detail page.
- **Commission forecast**: From current pipeline stage and historical settlement rates, Prism forecasts expected commissions for the month. Displayed on dashboard KPI widget as C1 chart.
- **Milestone auto-advance suggestion**: When all criteria for next milestone are met (e.g. contract signed + deposit paid), AI assistant suggests advancing the stage. C1 renders the suggestion as an action card with one-click confirm.
- **Contract anomaly detection**: Background job scans settled sale amount vs list price for large discrepancies (>10%), flags for review. Surfaced in SaleDataTable HasAi `insights` panel.
- **Next-best-action**: Suggest next step (e.g. “Send contract”, “Follow up finance”) from sale stage and dates. Prism call from Sale detail page → C1 renders as task suggestion card.
- **Email draft for client**: From sale + contact context, draft a short email (e.g. finance reminder). Prism → C1 `EmailCompose` component. Triggered from Sale detail or Filament action.
- *More ideas:* **13-ai-native-features-by-section.md** § Step 4.

## Verification (verifiable results, data fully imported)

- After import, verify: **property_reservations** = 119, **property_enquiries** = 701, **property_searches** = 312, **sales** = 443, **commissions** = 19,416; all contact FKs resolve to contacts. Implement `php artisan fusion:verify-import-reservations-sales`. See **11-verification-per-step.md** § Step 4 and **10-mysql-usage-and-skip-guide.md**.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 5 until the human has completed the checklist below.**

Human must:
- [ ] Confirm all migrations (property_reservations, property_enquiries, property_searches, sales, commissions) ran.
- [ ] Confirm `fusion:import-reservations-sales` completed; verification PASS (reservations 119, enquiries 701, searches 312, sales 443, commissions 19,416).
- [ ] Confirm all contact FKs on sales/reservations resolve (no orphan contact_id).
- [ ] Optionally spot-check a sale and its commissions in the UI.
- [ ] Approve proceeding to Step 5 (tasks, relationships, marketing).

## Acceptance Criteria

- [ ] All four (or five) migrations run.
- [ ] All legacy lead_id-style columns have a single contact_id (or named contact FK) in new schema.
- [ ] Import command runs and populates reservations, enquiries, searches, sales, commissions; every person reference resolved via contact_id map.
- [ ] Verification confirms expected row counts (reservations 119, enquiries 701, searches 312, sales 443, commissions 19,416).
- [ ] `commissions` table has `commission_type` enum column with all 7 values (`piab`, `subscriber`, `affiliate`, `sales_agent`, `referral_partner`, `bdm`, `sub_agent`); `override_amount` boolean column exists.
- [ ] For a sample sale: all 7 commission type rows exist in `commissions` table; auto-calculated amounts match `lot.price × rate_percentage / 100`; rows with `override_amount = true` retain manually entered amounts after recalculation.
- [ ] `CommissionDataTable` at `/commissions` loads; filter by `commission_type` and `agent_user_id` works; totals analytic row visible.
- [ ] `ReservationCreatedEvent` triggers a database-mail confirmation email to primary contact on reservation creation.
- [ ] Scout import run: `php artisan scout:import "App\Models\Sale"` succeeds; Typesense `sales` collection exists with `contact_name`, `lot_title`, `project_title`, `status`, `sale_price` fields indexed.

**UI acceptance criteria (verified by Visual QA protocol — Task 5 checks):**
- [ ] `/reservations` loads with ReservationDataTable; deposit_status badge visible
- [ ] `/reservations/{id}` shows 6-step vertical milestone timeline with completed/pending visual distinction
- [ ] Reservation create slide-over opens; all 5 sections render; eWAY toggle shows/hides payment fields
- [ ] `/sales` loads with SaleDataTable; commission_total money column visible
- [ ] Sale create/edit slide-over opens; commission rows auto-calculate dollar amounts on % change
- [ ] eWAY deposit status badge renders in correct color (Pending=gray, Paid=green, Failed=red)
