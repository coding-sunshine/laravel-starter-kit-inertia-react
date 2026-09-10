# FusionCRM v4 — Plan Rewrite & Bidirectional Sync Design

> **Date**: 2026-03-23
> **Status**: Approved (brainstorming complete, spec reviewed)
> **Scope**: Full rewrite of `newdb.md`, `newdb_simple.md`, `plan.md` + new `sync-architecture.md`
> **Review**: Spec reviewed — all critical/high/medium issues addressed (see §8)

---

## 1. Problem Statement

The planning docs (`newdb.md`, `newdb_simple.md`, `plan.md`) were written against an earlier version of the starter kit. The starter kit has since changed significantly:

| Planning Docs Assume | Starter Kit Reality |
|---|---|
| Laravel 12 | Laravel 13 |
| ~135 starter kit tables | 153 migrations |
| No module system | 10 modules including `module-crm` scaffold |
| CRM code in `modules/crm/` | Actually `modules/module-crm/` |
| `prism-php/prism` for AI | `laravel/ai` (with Prism + Relay) |
| No wizard/onboarding | Web wizard for project creation (Phase 3b) |
| No HR module | `module-hr` exists as reference |

Additionally, the rebuild requires a **bidirectional sync** between v3 (MySQL) and v4 (PostgreSQL) during a parallel-run period — not previously designed.

---

## 2. Decision Log

| # | Question | Decision |
|---|----------|----------|
| 1 | Sync scenario | **Parallel run** — v3 stays live during v4 dev, both sync, then permanent cutover |
| 2 | Sync direction | **Bidirectional** — admins manage projects/lots in v4, rest in v3 |
| 3 | Conflict resolution | **Last write wins** — compare `updated_at` timestamps |
| 4 | Entity scope | Must-sync (⇄): Contacts, Projects, Lots, Sales, Property Reservations, Companies, Emails, Phones. One-way (→): Users, Commissions, Notes, Project Updates. Skip: Payouts (doesn't exist in v3), Activities (464K noise, start fresh), Nova logs, Telescope, statusables |
| 5 | Sync mechanism | **Hybrid** — event-driven (model observers) for must-sync entities, scheduled batch (30min) for one-way entities |
| 6 | Doc update approach | **Full rewrite** — regenerate all three docs from scratch |
| 7 | Sync code location | **v4 only** — v4 connects to v3 via `mysql_legacy` connection. v3 untouched |
| 8 | Multi-tenancy | **True multi-tenant from day one** — each v3 subscriber → v4 Organization |
| 9 | v3 subscriber scoping | v3 has `subscriber_id` on only 3 tables (sales, website_contacts, clients). Most data is global. Ownership derived from `subscriber_id` where available, `created_by` elsewhere |
| 10 | Contact types | **Two types** via `contact_origin`: `saas_product` (platform leads, superadmin-managed) and `property` (property buyer/renter leads, org-scoped) |
| 11 | Project/Lot visibility | PIAB org projects = shared (visible to all). Subscriber org projects = scoped to that org's team |
| 12 | Launch vs deferred features | Launch: shared listings, performance dashboard, commission tiers. Deferred: lead routing, AI follow-ups, co-selling |

---

## 3. Business & Multi-Tenancy Model

### 3.1 Platform Architecture

```
FusionCRM Platform (PIAB = superadmin org)
│
├── Platform Contacts (contact_origin = 'saas_product')
│   └── Managed by superadmin
│   └── Convert to Subscribers (Organizations)
│
└── Subscriber Organizations
    ├── Own Property Contacts (contact_origin = 'property')
    │   └── Buyers/renters interested in properties
    ├── Can sell PIAB shared properties (projects/lots)
    ├── Can create their OWN properties (org-scoped)
    ├── Team members with role-based permissions
    └── Platform earns commission on EVERY sale
```

### 3.2 v3 Subscriber → v4 Organization Mapping

| v3 Concept | v4 Concept | Migration Logic |
|---|---|---|
| Lead with `is_subscriber = true` (or equivalent) | Organization + owner User | Create org per subscriber Lead. Lead's User becomes org owner. |
| Lead without subscriber status | Platform Contact (`saas_product`) | Migrated to contacts under PIAB org |
| `sales.subscriber_id` → Lead | `sales.organization_id` → Organization | Resolve via Lead → User → Organization chain |
| `website_contacts.subscriber_id` → Lead | `contacts.organization_id` → Organization | Same resolution chain |
| `clients.subscriber_id` → User | `contacts.organization_id` → Organization | Resolve via User → Organization |
| Unscoped data (projects, lots) | `organization_id = PIAB org` | All existing v3 projects/lots become PIAB-owned shared listings |

### 3.3 Project/Lot Visibility Rules

```
IF project.organization_id = PIAB org:
    → Visible to ALL organizations (shared marketplace listing)
    → Any subscriber can sell lots from this project
    → PIAB admins manage project details

IF project.organization_id = subscriber org:
    → Visible ONLY to that org's team members
    → Team permissions control who can view/edit/manage
    → Subscriber manages their own project details
    → NOT visible to other subscribers (unless co-selling enabled — deferred)
```

### 3.4 Contact Scoping

```
contact_origin = 'saas_product':
    → Platform-level lead (interested in FusionCRM product)
    → Managed by superadmin only
    → organization_id = PIAB org
    → Goal: convert to subscriber (new Organization)

contact_origin = 'property':
    → Property buyer/renter lead
    → organization_id = subscriber org (who captured the lead)
    → Managed by that org's team members
    → Goal: sell/rent a property → Sale → platform commission
```

### 3.5 Commission Flow

```
Sale Created
  → sale.organization_id = selling subscriber's org
  → sale.lot_id → lot.project_id → project.organization_id
  │
  ├── IF project owned by PIAB:
  │     Subscriber commission = configured rate
  │     Platform commission = configured rate
  │
  └── IF project owned by subscriber:
        Subscriber keeps full sale amount
        Platform commission = configured platform rate
```

### 3.6 Launch Features (v4 Core)

1. **Shared Listings Marketplace**
   - PIAB projects visible in every subscriber's dashboard
   - Any subscriber can sell lots from shared projects
   - Visibility controlled by `project.organization_id`
   - Simple query: `WHERE organization_id = PIAB_ORG OR organization_id = current_org`

2. **Performance Dashboard**
   - Per-subscriber: sales count, conversion rate, commission earned, active leads
   - Filament widget in subscriber's admin panel
   - Basic aggregation queries on sales + contacts

3. **Commission Tiers**
   - `commission_tiers` config table: volume threshold → commission rate
   - Calculated on sale creation based on subscriber's trailing sales count
   - Simple lookup + percentage calculation

### 3.7 Deferred Features (Build Later)

4. **Lead Routing** — Auto-assign incoming property leads to best-fit subscriber based on geo, capacity, performance. Needs scoring algorithm + assignment engine.

5. **Automated Follow-ups** — AI-driven email/SMS sequences for property leads using `laravel/ai`. Needs workflow engine + template system + scheduling.

6. **Co-selling (Subscriber Property Promotion)** — Subscribers opt-in to share their properties with other subscribers. Needs cross-org permission model, referral tracking, split commission calculation.

---

## 4. Entity Sync Matrix

| Entity | Direction | Mechanism | v3 Table | v4 Table | ID Mapping |
|---|---|---|---|---|---|
| Contact | ⇄ | Event-driven | `leads` | `contacts` | `legacy_lead_id` |
| Company | ⇄ | Event-driven | `companies` | `companies` | `legacy_company_id` |
| Project | ⇄ | Event-driven | `projects` | `projects` | `legacy_project_id` |
| Lot | ⇄ | Event-driven | `lots` | `lots` | `legacy_lot_id` |
| Sale | ⇄ | Event-driven | `sales` | `sales` | `legacy_sale_id` |
| Reservation | ⇄ | Event-driven | `property_reservations` | `property_reservations` | `legacy_reservation_id` |
| Email | ⇄ | Event-driven | `contacts` (polymorphic, type LIKE 'email%') | `contact_emails` | `legacy_email_id` |
| Phone | ⇄ | Event-driven | `contacts` (polymorphic, type='phone') | `contact_phones` | `legacy_phone_id` |
| User | → | Batch (30min) | `users` | `users` | `legacy_user_id` |
| Commission | → | Batch (30min) | `commissions` (polymorphic) | `commissions` | `legacy_commission_id` |
| Note | → | Batch (30min) | `notes` | `notes` | `legacy_note_id` |
| Project Update | → | Batch (30min) | `project_updates` | `project_updates` | — |
| ~~Payout~~ | — | REMOVED | Does not exist in v3 | — | — |
| ~~Activity~~ | — | SKIP | `activity_log` (464K, noise) | Start fresh | — |

---

## 4. Sync Architecture

### 4.1 Event-Driven Flow (Bidirectional Entities)

**v4 → v3**:
```
v4 Model Saved
  → Observer fires (e.g. ContactSyncObserver)
    → Check: $model->syncing flag set?
      → YES: skip (this is a sync write, prevent loop)
      → NO: dispatch SyncToLegacyJob
        → Field mapper transforms v4 fields → v3 columns
        → Upsert to mysql_legacy using legacy_*_id
        → Log to sync_logs table
```

**v3 → v4**:
```
Poller runs every 60 seconds (for event-driven entities)
  → Query mysql_legacy WHERE updated_at > last_poll_time
    → For each changed row:
      → Field mapper transforms v3 columns → v4 fields
      → Compare updated_at: if v3 is newer → upsert to v4
      → Set $model->syncing = true (prevent observer loop)
      → Save model
      → Log to sync_logs table
```

### 4.2 Batch Flow (One-Way Entities)

```
Laravel Scheduler (every 30 minutes)
  → SyncBatchJob for each one-way entity
    → Query mysql_legacy WHERE updated_at > last_sync_time
    → Batch upsert to v4 PostgreSQL
    → Update last_sync_time
    → Log to sync_logs table
```

### 4.3 Loop Prevention

Every sync write sets a runtime flag `$model->syncing = true` (not persisted to DB). The model observer checks this flag and skips dispatch if true. This prevents:

```
v4 save → sync to v3 → poller detects v3 change → sync back to v4 → ∞
```

### 4.4 Field Mapping Example (Contact ↔ Lead)

```php
// ContactFieldMapper
'first_name'       => 'first_name',         // same
'last_name'        => 'last_name',          // same
'stage'            => 'status',             // renamed + enum remapped
'contact_origin'   => null,                 // NEW in v4, no v3 equivalent
'organization_id'  => null,                 // derived from v3 user context
'source_id'        => 'source_id',          // FK remapped
'company_id'       => 'company_id',         // FK remapped
'extra_attributes' => ['address','city','state','postcode'], // JSONB merge
```

### 4.5 Sync Log Table

```sql
CREATE TABLE sync_logs (
    id              BIGSERIAL PRIMARY KEY,
    entity_type     VARCHAR(50) NOT NULL,   -- 'contact', 'project', etc.
    entity_id       BIGINT NOT NULL,
    direction       VARCHAR(10) NOT NULL,   -- 'v3_to_v4' | 'v4_to_v3'
    action          VARCHAR(10) NOT NULL,   -- 'created' | 'updated' | 'skipped' | 'conflict'
    legacy_id       BIGINT,
    payload         JSONB,
    synced_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    error           TEXT
);

CREATE INDEX idx_sync_logs_entity ON sync_logs (entity_type, entity_id);
CREATE INDEX idx_sync_logs_synced_at ON sync_logs (synced_at);
```

### 4.6 Sync Infrastructure (Code Structure)

All sync code lives in v4's CRM module:

```
modules/module-crm/src/
  Sync/
    Contracts/
      SyncableContract.php          -- interface for syncable models
    Traits/
      Syncable.php                  -- shared sync behavior
    FieldMappers/
      ContactFieldMapper.php        -- Contact ↔ Lead mapping
      ProjectFieldMapper.php
      LotFieldMapper.php
      SaleFieldMapper.php
      ReservationFieldMapper.php
      CompanyFieldMapper.php
      EmailFieldMapper.php
      PhoneFieldMapper.php
    Observers/
      ContactSyncObserver.php       -- event-driven v4→v3
      ProjectSyncObserver.php
      LotSyncObserver.php
      SaleSyncObserver.php
      ReservationSyncObserver.php
      CompanySyncObserver.php
      EmailSyncObserver.php
      PhoneSyncObserver.php
    Jobs/
      SyncToLegacyJob.php           -- dispatched by observers
      SyncFromLegacyJob.php         -- dispatched by poller
      SyncBatchJob.php              -- scheduled batch for one-way
    Commands/
      SyncPollCommand.php           -- polls v3 for changes (every 60s)
      SyncBatchCommand.php          -- batch sync one-way entities (every 30min)
      SyncFullCommand.php           -- full sync (manual, for cutover)
      SyncStatusCommand.php         -- reports sync health
```

### 4.7 Cutover Plan

1. Announce cutover date to all users
2. Run `php artisan sync:full` — final complete sync of all entities
3. Verify row counts match between v3 and v4
4. Disable all sync observers in `CrmServiceProvider`
5. Remove scheduled sync commands from `console.php`
6. Drop `mysql_legacy` connection from `config/database.php`
7. v4 is now standalone — v3 can be archived

### 4.8 Rollback Plan

If v4 has critical issues post-cutover:
1. Re-enable `mysql_legacy` connection
2. Run `php artisan sync:full --direction=v4_to_v3` — push all v4 data back to v3
3. Point DNS/users back to v3
4. Investigate and fix v4 issues
5. Re-attempt cutover when ready

---

## 5. Document Rewrite Scope

### `newdb.md` — Full Schema & Migration Map
- Update starter kit table count to actual (from delta report)
- Fix Laravel version: 12 → 13
- Fix module path: `modules/crm/` → `modules/module-crm/`
- Update package references: `prism-php/prism` → `laravel/ai`
- Add sync columns: `legacy_*_id`, `synced_at`, `sync_source` on bidirectional entities
- Add sync-related indexes (composite on `legacy_*_id` + `updated_at`)
- Update model namespaces: `Cogneiss\ModuleCrm\Models\Contact`
- Update relationship diagrams for module-based namespaces

### `newdb_simple.md` — Readable Quick Reference
- Regenerated from new `newdb.md`
- Add sync direction markers: `⇄` (bidirectional), `→` (v3→v4 only), `—` (skip)

### `plan.md` — Build Plan
- Pre-flight updated for Laravel 13 + existing module system
- Step 0: use existing `module-crm` scaffold (not `mkdir`)
- New Step 1.5: Sync Infrastructure (observers, field mappers, jobs, commands)
- All model paths → `modules/module-crm/src/Models/`
- All Filament paths → module-based discovery
- Each step notes which sync observers to register
- AI SDK references → `laravel/ai`
- Step 25 replaced with reference to built-in sync from Step 1.5

### New: `sync-architecture.md`
- Standalone doc with everything from Section 4 above
- Architecture diagrams, entity matrix, field mappers, cutover/rollback

---

## 6. Execution Order

### Phase 1: Analysis

| Step | Action | Output |
|---|---|---|
| A1 | Deep-scan starter kit (migrations, models, modules, config, packages) | Raw data |
| A2 | Snapshot v3 live database (tables, row counts, FKs) | Raw data |
| A3 | Generate delta report | `analysis/starter-kit-delta-report.md` |
| A4 | **HUMAN GATE** — review delta report | Approval |

### Phase 2: Document Rewrites (sequential)

| Step | Action | Output |
|---|---|---|
| B1 | Rewrite `newdb.md` | Updated schema doc |
| B2 | **HUMAN GATE** — review | Approval |
| B3 | Rewrite `newdb_simple.md` | Updated quick reference |
| B4 | **HUMAN GATE** — review | Approval |
| B5 | Write `sync-architecture.md` | New sync doc |
| B6 | **HUMAN GATE** — review | Approval |
| B7 | Rewrite `plan.md` | Updated build plan |
| B8 | **HUMAN GATE** — review | Approval |

### Phase 3: Validation

| Step | Action | Output |
|---|---|---|
| C1 | Cross-reference all four docs for consistency | Validation report |
| C2 | **HUMAN GATE** — final review | Approval to proceed |

---

## 7. Constraints & Rules

- **No auto-commits** — human approval at every gate
- **v3 nearly untouched** — all sync code lives in v4; only exception: `updated_at` indexes on v3 poll targets (see §8.5)
- **Last write wins** — `updated_at` comparison for conflicts, with equal-timestamp tiebreaker (see §8.4)
- **No data loss** — v3 stays live and operational throughout
- **Module-first** — all CRM code in `modules/module-crm/`
- **Sync is removable** — designed to be cleanly disabled at cutover

---

## 8. Spec Review — Issues Addressed

Issues found during spec review, with resolutions:

### 8.1 CRITICAL — CRM Table Prefix Decision

**Issue**: The starter kit scaffold may use `crm_` prefixed tables (e.g. `crm_contacts`). The planning docs use unprefixed names (`contacts`).

**Resolution**: During Phase 1 (Analysis), Step A1 will verify the exact table names in the starter kit scaffold. The decision:
- **If scaffold has `crm_` prefix**: We **drop and replace** the scaffold migrations with our own FusionCRM migrations. The scaffold is a generic CRM — FusionCRM has a property/real-estate domain model that is fundamentally different.
- **If scaffold has no tables yet** (just empty directories): We create our own migrations from scratch.
- Either way, v4 CRM tables will use **unprefixed names** (`contacts`, `projects`, `lots`) to match the planning docs and keep SQL readable. The `module-crm` module namespace provides sufficient scoping.
- This decision will be confirmed in the delta report (Step A3) before any rewriting begins.

### 8.2 CRITICAL — Starter Kit Schema Conflicts

**Issue**: The scaffold's schema (if any) differs fundamentally from FusionCRM's property/real-estate domain.

**Resolution**: We **replace** the scaffold schema entirely. The `module-crm` directory structure (Models/, Providers/, etc.) is reused, but all migrations, models, and Filament resources are written from scratch for the FusionCRM domain. The delta report will catalog exactly what exists in the scaffold so nothing is accidentally lost.

### 8.3 HIGH — v3→v4 Polling Terminology

**Issue**: The v3→v4 direction is polling (every 60s), not event-driven. Naming is misleading.

**Resolution**: Corrected terminology throughout. The sync mechanism is now described as:
- **v4→v3**: Observer-driven (true event-driven, fires on model save)
- **v3→v4**: Polled (60-second interval, queries `updated_at` changes)
- The 8 entity polls run **sequentially** within a single scheduled command to avoid overwhelming v3's MySQL.
- Load impact: 8 indexed queries per minute against v3. Acceptable for the data volumes involved (~10K-50K rows per table).

### 8.4 HIGH — Race Condition / Clock Skew

**Issue**: v3 (MySQL) and v4 (PostgreSQL) may have different clocks. MySQL `updated_at` has second precision; PostgreSQL has microsecond precision.

**Resolution**:
- **NTP required**: Both servers must run NTP. This is a pre-flight check in `plan.md`.
- **Equal timestamp tiebreaker**: When `updated_at` values are equal (within 1-second tolerance), **v3 wins during Phase A** (v3 is source of truth for most entities), **v4 wins during Phase B** (after admin features move to v4). The active tiebreaker is a config value: `sync.tiebreaker_source = 'v3' | 'v4'`.
- **Tolerance window**: Writes within 1 second of each other are treated as "equal" for tiebreaker purposes.

### 8.5 HIGH — Deletes Not Addressed

**Issue**: Sync only covers creates and updates. Deletes (soft and hard) are not handled.

**Resolution**:
- **Soft deletes**: Synced as updates. The `deleted_at` column is included in field mappers. If v3 doesn't have `deleted_at` on a table, soft-delete in v4 sets a `is_archived` flag (or equivalent existing column) in v3.
- **Hard deletes**: Not synced. Hard deletes in v3 are detected by the poller via a separate `sync_tombstones` table in v4 — during full sync, if a `legacy_*_id` exists in v4 but the corresponding v3 row is gone, it's flagged in `sync_logs` with action `'orphaned'` for manual review. No automatic hard-delete propagation.
- **v4→v3 deletes**: Observers fire on soft-delete events. Hard deletes in v4 are blocked during parallel run (admin policy — use soft-delete only).

### 8.6 HIGH — Missing `updated_at` Indexes on v3

**Issue**: Poller queries `WHERE updated_at > last_poll_time` on v3 tables. Without indexes, these are full table scans.

**Resolution**: **Narrow exception to "v3 untouched" rule.** We add `updated_at` indexes to the 8 polled v3 tables. These are non-destructive, read-only performance improvements:
```sql
-- One-time v3 index additions (run manually before sync goes live)
ALTER TABLE leads ADD INDEX idx_leads_updated_at (updated_at);
ALTER TABLE companies ADD INDEX idx_companies_updated_at (updated_at);
ALTER TABLE projects ADD INDEX idx_projects_updated_at (updated_at);
ALTER TABLE lots ADD INDEX idx_lots_updated_at (updated_at);
ALTER TABLE sales ADD INDEX idx_sales_updated_at (updated_at);
ALTER TABLE property_reservations ADD INDEX idx_reservations_updated_at (updated_at);
ALTER TABLE lead_emails ADD INDEX idx_lead_emails_updated_at (updated_at);
ALTER TABLE lead_phones ADD INDEX idx_lead_phones_updated_at (updated_at);
```
These indexes are added in Step 1.5 (Sync Infrastructure) of `plan.md`, with a human approval gate.

### 8.7 MEDIUM — `sync_source` Column Definition

**Issue**: `sync_source` mentioned but not defined.

**Resolution**: `sync_source` is a `VARCHAR(10)` column on all bidirectional entities. Values: `'v3'`, `'v4'`, `'initial'` (from first migration). Set by the sync process on every write. Used for debugging and audit — not for conflict resolution (that uses `updated_at`).

### 8.8 MEDIUM — Sync Monitoring Dashboard

**Issue**: Only a CLI command for sync status. No Filament dashboard.

**Resolution**: Add to `plan.md` Step 1.5:
- `SyncStatusCommand` for CLI monitoring
- Filament `SyncDashboard` widget in the System panel (superadmin only) showing: sync lag per entity, error rate (last 24h), conflict count, last sync times, entity-level row count comparison
- Sync errors with severity `error` trigger a Laravel notification (email to superadmin)

### 8.9 MEDIUM — FK Remapping & Sync Order

**Issue**: When syncing Contact from v3→v4, `company_id` must be resolved via `legacy_company_id` lookup. Entities have ordering dependencies.

**Resolution**: Sync order is explicit (both for initial full sync and poller):
1. Companies (no FK dependencies)
2. Contacts (depends on companies)
3. Projects (no CRM FK dependencies)
4. Lots (depends on projects)
5. Sales (depends on contacts + lots)
6. Reservations (depends on contacts + lots)
7. Emails (depends on contacts)
8. Phones (depends on contacts)

FK resolution: Field mappers resolve FKs by looking up the v4 record via `legacy_*_id`. If the parent record doesn't exist yet (race condition), the sync job is **re-queued with a 30-second delay** (max 3 retries, then logged as error).

### 8.10 MEDIUM — Organization ID Derivation

**Issue**: v3 has limited scoping (`subscriber_id` on 3 tables only). v4 is fully multi-tenant. How is `organization_id` assigned?

**Resolution**: True multi-tenant from day one. Each v3 subscriber Lead becomes a v4 Organization:
- **Tables with `subscriber_id`** (sales, website_contacts, clients): Resolve via `subscriber_id` → Lead → User → Organization
- **Tables with `created_by`** (most others): Resolve via `created_by` → User → Organization
- **Unscoped shared data** (projects, lots): Assigned to PIAB org (`sync.piab_organization_id` config). These become shared marketplace listings.
- **Platform leads** (v3 leads that aren't subscribers): Assigned to PIAB org with `contact_origin = 'saas_product'`
- **Sync ongoing**: New v3 records derive `organization_id` from same resolution chains. Config value `sync.piab_organization_id` used for shared/global data.

### 8.11 MEDIUM — Batch Size & Transactions

**Issue**: Batch upserts don't specify chunk sizes.

**Resolution**: All batch operations use chunked processing:
- **Chunk size**: 500 records per transaction
- **One-way batch**: Process in chunks of 500, each chunk in its own DB transaction
- **Full sync**: Same chunking, with progress logging per chunk
- Configurable via `sync.batch_chunk_size` (default 500)

### 8.12 LOW — Idempotency

**Resolution**: All sync jobs use `upsert` (not `insert`), keyed on `legacy_*_id`. Re-running a sync job for the same record is safe. `sync_logs` entries are append-only (duplicates are acceptable for audit trail).

### 8.13 LOW — `SyncFullCommand` Direction Flag

**Resolution**: `SyncFullCommand` accepts `--direction=v3_to_v4|v4_to_v3|both` (default: `both`). Documented in code structure.

### 8.14 Missing Items Added

- **Data type mapping**: MySQL→PostgreSQL type mapping table will be included in `newdb.md` (e.g., `TINYINT(1)` → `BOOLEAN`, `ENUM` → `VARCHAR + CHECK`)
- **Enum value mapping**: Full enum value mapping tables per entity will be in `sync-architecture.md` field mapper sections
- **Queue config**: Sync jobs run on a dedicated `sync` queue (`SYNC_QUEUE=sync`). Separate worker process to avoid competing with app jobs.
- **Error handling**: Failed sync jobs retry 3 times with exponential backoff (30s, 120s, 480s). After 3 failures → logged to `sync_logs` with `action='failed'` + notification to superadmin. Dead jobs visible in Filament sync dashboard.
- **Testing strategy**: Added to `plan.md` Step 1.5: unit tests for each field mapper (round-trip mapping), integration tests for observer→job→write flow, staging environment test with v3 data copy before going live.
