# PRD 14: Deal Tracker Enhancements

> **Phase 2** — Runs after subscriber migration.

## Overview

Add a Kanban pipeline board for reservations/sales at `/deal-tracker`, a per-deal document vault, a payment stage tracker (EOI to Payout), and an important/pinned notes panel. Builds on Phase 1 + PRDs 09-13.

**Prerequisites:** PRDs 00-13 complete (sales pipeline list/Kanban from PRD 09, custom fields, automation rules working).

## Technical Context

- **DataTable Kanban:** Use DataTable built-in Kanban layout (`layout: 'kanban'`) — do NOT build a custom Kanban component
- **Media:** `spatie/laravel-media-library` for document vault per sale
- **Inertia:** Payment stage widgets, pinned notes component (React components)
- **Module:** `modules/module-crm/`, namespace `Cogneiss\ModuleCrm`
- **Existing tables:** `property_reservations`, `sales` (from Phase 1 Steps 3-4)

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `DB_CONNECTION=pgsql` — PostgreSQL connection (set in preflight)

No special API keys required for this PRD.

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Deal Tracker Kanban Board

**Status:** todo
**Priority:** 1
**Description:** Build the `/deal-tracker` page showing reservations and sales as a drag-and-drop Kanban board.

- [ ] Create Inertia page at `/crm/deal-tracker`
- [ ] Create `DealTrackerDataTable` in `modules/module-crm/src/DataTables/` extending AbstractDataTable with Kanban layout
- [ ] Kanban columns: Enquiry, Qualified, Reservation, Contract, Unconditional, Settled (mapped from sale/reservation stages)
- [ ] Card display: contact name, project + lot, purchase price, days in current stage, agent avatar
- [ ] Dragging a card between columns triggers stage update via `UpdateSaleStageAction` (existing from PRD 09)
- [ ] Cards populated from both `property_reservations` and `sales` tables (union or polymorphic query)
- [ ] Role-aware: users only see deals they have permission for (org-scoped)
- [ ] Filter bar: agent, project, date range, price range
- [ ] Verify: `/crm/deal-tracker` loads with stage columns; dragging a card from Reservation to Contract updates the DB stage

### US-002: Document Vault

**Status:** todo
**Priority:** 2
**Description:** Per-deal storage for PDFs, emails, contracts using Spatie Media Library.

- [ ] Add media collection 'documents' to `Sale` model via `registerMediaCollections()`
- [ ] Add media collection 'documents' to `PropertyReservation` model
- [ ] Create "Documents" tab on sale show page and reservation show page
- [ ] File upload: drag-and-drop zone for PDFs, images, emails (.eml)
- [ ] Document list: name, type, size, uploaded_by (user), uploaded_at, download link, delete action
- [ ] Categorize documents: contract, EOI, correspondence, other (via custom property on media)
- [ ] Permission: upload requires edit permission on the deal; view requires read permission
- [ ] Verify: uploading a PDF to a sale shows in document vault; file downloadable; categorized correctly

### US-003: Payment Stage Tracker

**Status:** todo
**Priority:** 2
**Description:** Track payment milestones per deal from EOI through to Payout.

- [ ] Create migration `create_payment_stages_table`: id, sale_id (FK sales CASCADE), organization_id (FK), stage (enum: eoi/deposit/commission_invoice/commission_paid/payout), amount (decimal 12,2 nullable), due_date (date nullable), paid_date (date nullable), notes (text nullable), created_by (FK users nullable), timestamps
- [ ] Create `Cogneiss\ModuleCrm\Models\PaymentStage` with `BelongsToOrganization`
- [ ] Add `paymentStages()` hasMany relationship on Sale model
- [ ] Display payment stages as a horizontal stepper/timeline on sale detail page
- [ ] Each stage: status indicator (pending/due/paid/overdue), amount, dates
- [ ] Quick-edit: mark stage as paid with date (inline form)
- [ ] Automation hook: when payment stage changes, evaluate automation rules (PRD 13 US-004)
- [ ] Verify: sale shows payment stages timeline; marking deposit as paid updates the record

### US-004: Important Notes Panel (Pinned Notes)

**Status:** todo
**Priority:** 3
**Description:** Allow pinning critical notes visible as alerts on dashboard and deal views.

- [ ] Add `is_pinned` (boolean default false) and `pinned_visibility` (jsonb — array of role slugs, e.g., ["admin", "agent", "bdm"]) columns to existing `notes` table via migration
- [ ] Update Note model with `scopePinned()` and `scopeVisibleToRole($role)` query scopes
- [ ] "Pin Note" action on note list and note detail (toggle is_pinned + set visibility roles)
- [ ] Show pinned notes as alert cards at top of: dashboard, deal/sale show page, contact show page
- [ ] Pinned notes styled as warning/info banners (not inline with regular notes)
- [ ] Only users with matching role see the pinned note
- [ ] Unpin action to remove from alerts
- [ ] Verify: pinning a note with visibility ["admin"] shows it on admin's dashboard; agent does not see it
