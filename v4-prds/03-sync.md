# PRD 03: Sync — Bidirectional Sync Infrastructure

## Overview

Build the complete bidirectional sync infrastructure between v4 (PostgreSQL) and v3 (MySQL). This includes contracts, traits, field mappers, observers, jobs, commands, scheduler entries, and a Filament dashboard widget. **Prerequisites:** PRD 02 must be complete — contacts imported (needed for sync testing), users imported, organizations created.

**CRITICAL: Never touch `sites/default/sqlconf.php`. Never commit without human approval.**

## Technical Context

- **All sync code lives in:** `modules/module-crm/src/Sync/`
- **Companion docs:** `sync-architecture.md` (full design — sections 1-10), `plan.md` (Step 1.5), `newdb.md` (sync_logs schema)
- **Sync mechanisms:** Observer-driven (v4->v3), Polled 60s (v3->v4), Batch 30min (v3->v4 one-way)
- **8 bidirectional entities:** Company, Contact, Project, Lot, Sale, Reservation, Email, Phone
- **5 one-way entities:** User, Commission, Note, Activity (SKIP), Project Update
- **Loop prevention:** `$model->syncing` runtime flag (not persisted)
- **Conflict resolution:** Last-write-wins based on `updated_at`
- **Queue:** All sync jobs on `sync` queue. Retry 3x with exponential backoff (30s, 120s, 480s).
- **Key rule:** All sync writes use `upsert` keyed on `legacy_*_id` columns for idempotency

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `DB_CONNECTION=pgsql` — PostgreSQL connection (set in preflight)
- `DB_LEGACY_HOST`, `DB_LEGACY_PORT`, `DB_LEGACY_DATABASE`, `DB_LEGACY_USERNAME`, `DB_LEGACY_PASSWORD` — Legacy MySQL connection for bidirectional sync
- `SYNC_QUEUE=sync`, `SYNC_PIAB_ORG_ID`, `SYNC_TIEBREAKER=v3`, `SYNC_BATCH_CHUNK`, `SYNC_POLL_INTERVAL`, `SYNC_BATCH_INTERVAL` — Sync configuration

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Sync Contracts, Traits, and Field Mappers

**Status:** todo
**Priority:** 1
**Description:** Create the core sync infrastructure: contracts, traits, sync_logs migration, and 8 field mappers. Reference `sync-architecture.md` section 5 for exact column mappings per entity.

**Create:**

1. `modules/module-crm/src/Sync/Contracts/SyncableContract.php` — Interface for syncable models. Methods: `getLegacyIdColumn(): string`, `getSyncObserver(): string`, `getFieldMapper(): string`.

2. `modules/module-crm/src/Sync/Traits/Syncable.php` — Shared sync behavior. Provides: `$syncing` runtime flag (not persisted to DB, exists only on in-memory model instance), legacy ID accessor, sync logging helper.

3. Verify `config/sync.php` exists (created in PRD 01 US-002).

4. `modules/module-crm/database/migrations/create_sync_logs_table.php`:
   - `id` BIGSERIAL PRIMARY KEY
   - `entity_type` VARCHAR(50) NOT NULL
   - `entity_id` BIGINT NOT NULL
   - `direction` VARCHAR(10) NOT NULL — `'v3_to_v4'` | `'v4_to_v3'`
   - `action` VARCHAR(10) NOT NULL — `'created'` | `'updated'` | `'skipped'` | `'conflict'` | `'failed'` | `'orphaned'`
   - `legacy_id` BIGINT
   - `payload` JSONB
   - `synced_at` TIMESTAMPTZ NOT NULL DEFAULT NOW()
   - `error` TEXT
   - Indexes: `(entity_type, entity_id)`, `(synced_at)`

5. Create 8 field mappers at `modules/module-crm/src/Sync/FieldMappers/`:
   - `ContactFieldMapper.php` — Contact <-> Lead (see `sync-architecture.md` section 5.1). 27 columns mapped. FK remaps for source_id, company_id, created_by, updated_by. Address extraction from extra_attributes/addresses table.
   - `CompanyFieldMapper.php` — Company <-> Company (section 5.2). Rename extra_company_info -> extra_attributes.
   - `ProjectFieldMapper.php` — Project <-> Project (section 5.3). 19 v4 columns. Many v3 boolean columns merged into extra_attributes JSONB. Child tables (specs, investment, features, pricing) NOT synced bidirectionally.
   - `LotFieldMapper.php` — Lot <-> Lot (section 5.4). 43 v4 columns. Column renames: car->car_spaces, storyes->storeys, weekly_rent->rent_weekly, etc. Type casts: varchar->SMALLINT, varchar->NUMERIC, int->boolean.
   - `SaleFieldMapper.php` — Sale <-> Sale (section 5.5). **CRITICAL:** v3 `Sale.created_by` references `leads.id` (NOT `users.id`). Extra-hop resolution: `leads.id` -> find `users` via `users.lead_id` -> `users.id`. Commission columns NOT synced (one-way batch).
   - `ReservationFieldMapper.php` — Reservation <-> PropertyReservation (section 5.6). Renames: property_id->project_id, purchaser1_id->primary_contact_id, deposit->deposit_amount. JSONB extractions for trust/finance booleans.
   - `EmailFieldMapper.php` — ContactEmail <-> v3 `contacts` table (polymorphic, section 5.7). WHERE `model_type='App\Models\Lead' AND type LIKE 'email%'`. Type remap: email_1->work (is_primary=true), email_2->home.
   - `PhoneFieldMapper.php` — ContactPhone <-> v3 `contacts` table (polymorphic, section 5.8). WHERE `model_type='App\Models\Lead' AND type LIKE 'phone%'`.

   Each mapper implements: `toV3(Model $v4Model): array`, `toV4(array $v3Row): array`.
   FK resolution: Look up v4 record via `legacy_*_id`. If parent record doesn't exist (race condition), re-queue job with 30-second delay (max 3 retries, then log as error).

- [ ] `SyncableContract` interface exists at `modules/module-crm/src/Sync/Contracts/SyncableContract.php`
- [ ] `Syncable` trait exists at `modules/module-crm/src/Sync/Traits/Syncable.php`
- [ ] `sync_logs` table migration exists and `php artisan migrate` succeeds
- [ ] `sync_logs` table has indexes on `(entity_type, entity_id)` and `(synced_at)`
- [ ] All 8 field mappers exist in `modules/module-crm/src/Sync/FieldMappers/`
- [ ] Each field mapper has `toV3()` and `toV4()` methods
- [ ] ContactFieldMapper correctly maps stage with enum remapping
- [ ] SaleFieldMapper handles created_by extra-hop resolution (v3 leads.id -> v4 users.id)
- [ ] Unit tests pass for all 8 field mappers: `php artisan test --filter=FieldMapper` (round-trip: `toV4(toV3(model))` preserves data)

### US-002: Sync Observers, Jobs, and Commands

**Status:** todo
**Priority:** 2
**Description:** Create the sync observers, jobs, and artisan commands that power bidirectional sync. Register observers and scheduler entries.

**Observers** (at `modules/module-crm/src/Sync/Observers/`):
Create 8 observers: `ContactSyncObserver`, `CompanySyncObserver`, `ProjectSyncObserver`, `LotSyncObserver`, `SaleSyncObserver`, `ReservationSyncObserver`, `EmailSyncObserver`, `PhoneSyncObserver`.
- Each listens to: `created`, `updated`, `deleted` (soft-delete)
- Each checks `$model->syncing` flag — if `true`, skip (prevents infinite loop); else dispatch `SyncToLegacyJob`

**Jobs** (at `modules/module-crm/src/Sync/Jobs/`):
- `SyncToLegacyJob.php` — Dispatched by observers (v4->v3). Uses field mapper to transform, upserts to `mysql_legacy` using `legacy_*_id`, logs to `sync_logs`.
- `SyncFromLegacyJob.php` — Dispatched by poller (v3->v4). Transforms v3 columns -> v4 fields, compares `updated_at` (last write wins), sets `$model->syncing = true`, saves, logs.
- `SyncBatchJob.php` — Scheduled batch for one-way entities (users, commissions, notes, project_updates). Chunks of 500 per transaction.
- All jobs: queue = `sync`, retry 3x with exponential backoff (30s, 120s, 480s). Failed after 3 retries -> logged with `action='failed'` + superadmin notification.

**Commands** (at `modules/module-crm/src/Console/Commands/`):
- `SyncPollCommand.php` (`sync:poll`) — Polls v3 for changes every 60s. Processes 8 entities **sequentially in dependency order**: Companies (1), Contacts (2), Projects (3), Lots (4), Sales (5), Reservations (6), Emails (7), Phones (8). Queries `mysql_legacy WHERE updated_at > last_poll_time`.
- `SyncBatchCommand.php` (`sync:batch`) — Batch sync one-way entities every 30min.
- `SyncFullCommand.php` (`sync:full`) — Full sync with `--direction=v3_to_v4|v4_to_v3|both` (default: both).
- `SyncStatusCommand.php` (`sync:status`) — Reports sync health: lag per entity, error rate, row counts.

**Registration:**
- Register all 8 observers in `CrmModuleServiceProvider::boot()`.
- Add scheduler entries in `routes/console.php`:
  ```php
  Schedule::command('sync:poll')->everyMinute();
  Schedule::command('sync:batch')->everyThirtyMinutes();
  ```

**v3 MySQL Indexes** — Document these 7 `ALTER TABLE` statements for manual execution on v3:
```sql
ALTER TABLE leads ADD INDEX idx_leads_updated_at (updated_at);
ALTER TABLE companies ADD INDEX idx_companies_updated_at (updated_at);
ALTER TABLE projects ADD INDEX idx_projects_updated_at (updated_at);
ALTER TABLE lots ADD INDEX idx_lots_updated_at (updated_at);
ALTER TABLE sales ADD INDEX idx_sales_updated_at (updated_at);
ALTER TABLE property_reservations ADD INDEX idx_reservations_updated_at (updated_at);
ALTER TABLE contacts ADD INDEX idx_contacts_updated_at (updated_at);
```

- [ ] All 8 sync observers exist in `modules/module-crm/src/Sync/Observers/`
- [ ] All 8 observers registered in `CrmModuleServiceProvider::boot()`: `grep -c '::observe(' modules/module-crm/src/Providers/CrmModuleServiceProvider.php` >= 8
- [ ] `SyncToLegacyJob`, `SyncFromLegacyJob`, `SyncBatchJob` exist with correct queue config (`sync`)
- [ ] Jobs retry 3x with exponential backoff (30s, 120s, 480s)
- [ ] `SyncPollCommand` processes entities in correct dependency order (1-8)
- [ ] `SyncBatchCommand` handles one-way entities (users, commissions, notes, project_updates)
- [ ] `SyncFullCommand` accepts `--direction` flag
- [ ] `SyncStatusCommand` outputs health report
- [ ] Scheduler entries added: `sync:poll` every minute, `sync:batch` every 30 minutes
- [ ] v3 MySQL index SQL documented (7 ALTER TABLE statements)
- [ ] `php artisan sync:poll` exits with code 0
- [ ] `php artisan sync:batch` exits with code 0
- [ ] `php artisan sync:status` shows healthy status

### US-003: Sync Testing and Dashboard

**Status:** todo
**Priority:** 3
**Description:** Create the Filament sync dashboard widget (System panel, superadmin only) and perform end-to-end sync testing.

**SyncDashboard Widget** (Filament widget in System panel):
- Shows: sync lag per entity, error rate (last 24h), conflict count, last sync times, entity-level row count comparison (v3 vs v4)
- Sync errors with severity `error` trigger a Laravel notification (email to superadmin)

**Testing Procedure:**
1. Start sync queue worker: `php artisan queue:work --queue=sync`
2. **v4->v3 test:** Create a Contact in v4 -> verify row appears in `mysql_legacy.leads`
3. **v3->v4 test:** Update a Lead in v3 -> run `sync:poll` -> verify change appears in v4
4. **Loop prevention test:** v4 save -> syncs to v3 -> poller detects change -> does NOT re-sync back to v4 (check `sync_logs` for `action='skipped'`)
5. **Conflict resolution test:** Simultaneous edits -> last write wins based on `updated_at`

- [ ] SyncDashboard widget visible in Filament System panel (superadmin only)
- [ ] Dashboard shows sync lag per entity
- [ ] Dashboard shows error rate for last 24 hours
- [ ] Dashboard shows row count comparison (v3 vs v4)
- [ ] `php artisan queue:work --queue=sync --once` processes at least one job without error
- [ ] v4->v3 test: `Contact::factory()->create(['first_name'=>'SyncTestV4'])` -> `DB::connection('mysql_legacy')->table('leads')->where('first_name','SyncTestV4')->exists()` returns `true`
- [ ] v3->v4 test: manually update v3 lead, run `sync:poll`, verify change in v4
- [ ] Loop prevention verified: `SyncLog::where('action','skipped')->count()` > 0 after bidirectional test
- [ ] v3 `updated_at` indexes created on 7 tables (documented for manual execution)
- [ ] `php artisan sync:status` shows healthy status with zero errors
- [ ] `php artisan sync:status` → shows healthy
- [ ] `php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\SyncLog::where('action','failed')->count()"` → 0
