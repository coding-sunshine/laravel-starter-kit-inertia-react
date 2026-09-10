# PRD 09: Full-Text Search & Bulk Operations

## Overview

> **Phase 2** — This PRD runs after Phase 1 (Builder Portal) launches and Phase 2 subscriber migration completes.

Add full-text search via Laravel Scout + Typesense across Contact, Project, and Lot models. Implement bulk operations (update, delete, export) integrated with the DataTable pattern. This is the first Phase 2 step, building on top of the complete Phase 1 (Steps 0-7).

**Prerequisites:** PRDs 00-08 complete (all Phase 1 data migrated, dashboards working, sync active).

## Technical Context

- **Search:** `laravel/scout` + `typesense/typesense-php` (both in starter kit composer.json)
- **Module:** `modules/module-crm/`, namespace `Cogneiss\ModuleCrm`
- **DataTable:** `machour/laravel-data-table` AbstractDataTable pattern with `HasExport`, `HasSelectAll`
- **Layout:** `AppSidebarLayout` + `<DataTable<T>>` for all list pages
- **Models:** All have `organization_id` via `BelongsToOrganization` trait
- **Env:** `TYPESENSE_API_KEY`, `TYPESENSE_HOST` (set in Pre-Flight)
- **Real-time:** `laravel/reverb` WebSockets — when a contact/project/lot is updated, push update to open DataTable pages

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `TYPESENSE_API_KEY` — Required for full-text search via Scout + Typesense
- `TYPESENSE_HOST=localhost`, `TYPESENSE_PORT=8108`, `TYPESENSE_PROTOCOL=http` — Typesense connection
- `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` — Optional: real-time DataTable updates via WebSockets

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Configure Scout + Typesense for CRM Models

**Status:** todo
**Priority:** 1
**Description:** Make Contact, Project, and Lot models searchable via Scout with Typesense driver.

- [ ] Add `Searchable` trait to `Cogneiss\ModuleCrm\Models\Contact`, `Project`, and `Lot`
- [ ] Define `toSearchableArray()` on each model: Contact (first_name, last_name, email, phone, company, stage, suburb), Project (title, suburb, state, developer, project_type), Lot (lot_number, address, price, status, project title)
- [ ] Configure `config/scout.php` with Typesense driver and collection settings
- [ ] Scope search results by `organization_id` using Scout's `search()->where('organization_id', ...)` or a global query callback
- [ ] PIAB shared projects visible to all orgs in search results (WHERE org_id = PIAB OR org_id = current)
- [ ] Create `php artisan scout:import` command run for all three models
- [ ] Verify: `Contact::search('john')->get()` returns matching contacts scoped to org
- [ ] Verify: Typesense index contains 9,735 contacts, all projects, all lots
- [ ] Real-time DataTable updates via Reverb when records change
- [ ] WebSocket connection configured (REVERB_* env vars)

### US-002: Integrate Search with DataTable UI

**Status:** todo
**Priority:** 2
**Description:** Wire Typesense search into the existing DataTable global search so users get instant full-text results.

- [ ] Update `ContactDataTable`, `ProjectDataTable`, `LotDataTable` to use Scout search when `globalSearch` query parameter is present
- [ ] Search results respect existing DataTable filters (stage, contact_origin, etc.) applied as Typesense filter_by
- [ ] Search input in DataTable header triggers Typesense search with debounce (300ms)
- [ ] Results maintain pagination, sorting, and column filters
- [ ] Search highlights or ranks results by relevance
- [ ] Verify: typing in global search box on `/crm/contacts` returns relevant results within 200ms

### US-003: Lead Source Attribution

**Status:** todo
**Priority:** 2
**Description:** Add attribution tracking so each contact can be attributed to a campaign, ad, or agent.

- [ ] Create migration `create_contact_attributions_table` in `modules/module-crm/database/migrations/`: id, organization_id (FK), contact_id (FK contacts CASCADE), campaign_name (string nullable), ad_name (string nullable), attributed_agent_contact_id (FK contacts SET NULL nullable), source (string nullable), attributed_at (timestamp), timestamps
- [ ] Create `Cogneiss\ModuleCrm\Models\ContactAttribution` model with `BelongsToOrganization`
- [ ] Add `attributions()` hasMany relationship on Contact model
- [ ] Show attributions on contact show page (overview tab)
- [ ] Add `campaign_name` and `attributed_agent` as filterable columns in ContactDataTable
- [ ] Backfill attribution from existing `source_id` where possible (one-off artisan command)
- [ ] Verify: contact profile shows attribution; filtering by campaign works in DataTable

### US-004: Sales Pipeline Kanban View

**Status:** todo
**Priority:** 2
**Description:** Build a Kanban board for sales grouped by stage with drag-and-drop stage changes.

- [ ] Create `/crm/sales/pipeline` Inertia page with Kanban layout
- [ ] Use DataTable built-in Kanban layout (`layout: 'kanban'`) on `SaleDataTable` if supported, else build Kanban React component
- [ ] Columns: Enquiry, Qualified, Reservation, Contract, Unconditional, Settled (from sale stages)
- [ ] Cards show: contact name, project + lot, purchase price, days in stage, agent avatar
- [ ] Drag card between columns triggers `UpdateSaleStageAction` via Inertia POST
- [ ] Role-aware: users only see sales they have permission for
- [ ] Verify: `/crm/sales/pipeline` loads with stage columns; dragging a card updates the stage in DB

### US-005: Sales Pipeline List View

**Status:** todo
**Priority:** 3
**Description:** Provide a flat DataTable list view for sales with filters and quick-edit.

- [ ] Ensure `SaleDataTable` has columns: contact, project, lot, stage (option type), purchase_price, agent, created_at
- [ ] Filterable by: stage, agent (user), date range
- [ ] Sortable by all visible columns
- [ ] `HasExport` trait enabled for CSV/Excel export
- [ ] Quick-edit stage via inline dropdown (uses `HasInlineEdit` or `HasToggle`)
- [ ] Verify: `/crm/sales` list loads with filters; changing stage inline persists

### US-006: Follow-Up Task Logic

**Status:** todo
**Priority:** 2
**Description:** Implement `last_contacted_at` auto-update and `next_followup_at` rules per the spec.

- [ ] Create `UpdateLastContactedAtAction` in `modules/module-crm/src/Actions/`: accepts Contact + reason string, updates `last_contacted_at`, logs to activity_log
- [ ] Hook action into: email sent (Send Email action), call logged (Log Call action), task completed (where type IN call/email/meeting/follow_up/visit), note added (interaction_type = 'contact'), stage changed manually
- [ ] Task observer: on `saved()` where status changes to completed and type is interaction type, call the action
- [ ] `next_followup_at` is NEVER auto-overwritten by system; only set when user explicitly sets follow-up date
- [ ] Default suggestion: `today + follow_up_interval_days` (configurable per org via `ContactSettings` in org settings JSON, default 7 for leads, 30 for clients)
- [ ] Stale contact logic: `last_contacted_at IS NULL AND created_at < now() - stale_threshold_days` OR `last_contacted_at < now() - stale_threshold_days` (default 30 days)
- [ ] Dashboard KPI card "Stale Contacts" uses stale query
- [ ] Overdue follow-up: `next_followup_at < now()` shows orange dot on contact list and dashboard
- [ ] Automated/bulk emails do NOT trigger `last_contacted_at` update
- [ ] Verify: completing a call task updates `last_contacted_at`; `next_followup_at` is not overwritten

### US-007: Reservation Form v2

**Status:** todo
**Priority:** 3
**Description:** Enhance the property reservation form with auto-fill, validation, and stakeholder mapping.

- [ ] Create/update Inertia form at `/crm/reservations/create`
- [ ] Auto-fill: primary contact from selected contact, secondary contact optional, agent from current user's contact_id, property/lot from URL params or selection
- [ ] Server-side validation: required fields (purchase_price, primary_contact_id, lot_id), date validation, clear error messages
- [ ] Submit creates `PropertyReservation` with correct: primary_contact_id, secondary_contact_id, agent_contact_id, logged_in_user_id (auth user)
- [ ] Use `useForm()` from `@inertiajs/react`
- [ ] Verify: reservation created has correct stakeholder IDs; validation errors display for missing required fields

### US-008: Status Quick-Edit

**Status:** todo
**Priority:** 3
**Description:** Add inline status/stage change controls on contact list, sale list, and task list.

- [ ] Contact list: stage dropdown inline (uses `HasInlineEdit` on ContactDataTable)
- [ ] Sale list: stage dropdown inline (uses `HasInlineEdit` on SaleDataTable)
- [ ] Task list: status toggle (uses existing `HasToggle` on TaskDataTable)
- [ ] Create `UpdateContactStageAction` and `UpdateSaleStageAction` single-action classes
- [ ] Permission check: only users with edit permission can quick-edit
- [ ] Optimistic UI or Inertia reload on change
- [ ] Verify: changing a contact stage via inline dropdown persists to DB

### US-009: Bulk Update Tools

**Status:** todo
**Priority:** 3
**Description:** Implement bulk tagging, bulk status change, and bulk group assignment.

- [ ] DataTable bulk actions toolbar appears when rows are selected (via `HasSelectAll`)
- [ ] Bulk tag: select contacts/sales/tasks → apply or remove tag (via `spatie/laravel-tags`)
- [ ] Bulk status change: select contacts → set stage; select sales → set stage
- [ ] Bulk add to mail list: select contacts → add to a mail list (via `mail_list_contacts` pivot)
- [ ] Bulk delete: select → soft delete (with confirmation modal)
- [ ] Bulk export: select → export selected rows as CSV (`HasExport`)
- [ ] Backend: action validates permissions, applies in DB transaction, returns count affected
- [ ] Verify: select 3 contacts, apply tag "VIP" → all 3 have tag; bulk delete soft-deletes selected

### US-010: Member-Uploaded Listings

**Status:** todo
**Priority:** 3
**Description:** Allow org users to create and edit projects/lots with media upload and validation.

- [ ] Project create/edit form at `/crm/projects/create` and `/crm/projects/{id}/edit`
- [ ] Lot create/edit form at `/crm/lots/create` and `/crm/lots/{id}/edit`
- [ ] Media upload: photos and floor plans via Spatie Media Library (media collections per project/lot)
- [ ] Validation: required fields (title, project_stage for project; lot_number, price for lot), price > 0, logical date constraints
- [ ] Optional `listing_status` (draft/published) on projects
- [ ] Scoped by `organization_id` — users only see/edit their org's listings
- [ ] Permission: only users with `create-projects` / `edit-projects` permission
- [ ] Verify: user creates project with photo, lot with price; validation blocks submit with missing title

### US-011: Multi-Channel Publishing

**Status:** todo
**Priority:** 4
**Description:** Push projects/lots to PHP fast sites and WordPress sites.

- [ ] Create `PushToPhpSiteAction`: syncs project/lot data to PHP site endpoint (uses `websites` table for config)
- [ ] Create `PushToWordPressAction`: syncs to WP site via WP REST API (uses `wordpress_websites` config)
- [ ] "Publish" button on project/lot show page triggers push to selected channel(s)
- [ ] Bulk publish: select multiple projects → push to channel
- [ ] Log push history in `activity_log` (or dedicated `push_history` entries): pushable_type, pushable_id, channel, pushed_at, user_id
- [ ] Show last pushed timestamp on project/lot detail
- [ ] Verify: push to PHP site executes without error; push history appears on project

### US-012: Conversion Funnel View

**Status:** todo
**Priority:** 4
**Description:** Build a report page showing the lead-to-commission funnel.

- [ ] Create Inertia page at `/crm/reports/funnel`
- [ ] Query: count contacts by stage, count property_reservations, count sales, count commissions
- [ ] Display as funnel chart (horizontal bars or stepped visualization)
- [ ] Filters: date range, organization, agent
- [ ] Use existing models: Contact (stages), PropertyReservation, Sale, Commission
- [ ] Verify: funnel page loads showing counts for at least two stages; filters work

### US-013: Shared Saved Searches

**Status:** todo
**Priority:** 5 (after existing stories)
**Description:** Extend the starter kit's `data_table_saved_views` to support team-shared and system-wide saved searches.

Currently: `data_table_saved_views` is per-user only.
Add columns:
- `organization_id BIGINT FK NULL` — if set, shared with org members
- `is_shared BOOLEAN DEFAULT false` — visible to all org members
- `is_system BOOLEAN DEFAULT false` — admin-created default views (e.g., "Available Lots", "Hot Properties")

Agents can:
- Save private searches (existing behavior)
- Share searches with their team (set is_shared=true)
- See system-wide saved searches from admins

- [ ] Verify starter kit migration applied: `data_table_saved_views` has `organization_id`, `is_shared`, `is_system` columns (already in starter kit migration `2026_03_25_032603`)
- [ ] Saved view dropdown shows: My Views | Team Views | System Views
- [ ] Creating a saved view has "Share with team" toggle
- [ ] System views are created by superadmin/developer admin
- [ ] Team views scoped to organization_id
