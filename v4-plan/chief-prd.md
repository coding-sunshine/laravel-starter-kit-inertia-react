# FusionCRM v4 Rebuild

## Overview

FusionCRM v4 is a ground-up rebuild of FusionCRM v3 (Laravel/MySQL/Nova) onto a modern stack. The v3 app is a property CRM used by PIAB (the platform operator) and subscriber organizations (real estate agents/agencies). v4 replaces v3 with a multi-tenant architecture where each v3 subscriber becomes a v4 Organization.

**Key concepts:**
- **Two contact types**: `saas_product` (platform leads managed by PIAB superadmin) and `property` (buyer/renter leads managed by subscriber orgs)
- **Project visibility**: PIAB org projects are shared marketplace listings visible to all orgs. Subscriber org projects are scoped to that org only.
- **Bidirectional sync**: v3 (MySQL) and v4 (PostgreSQL) run in parallel during transition. Event-driven sync for core entities, batch sync for secondary entities. v4 owns all sync code; v3 is nearly untouched.
- **Commission flow**: Platform earns commission on every sale. Commission tiers based on subscriber's trailing sales volume.
- **Builder Portal (PHASE 1 — SHIP FIRST)**: Competing with Kenekt ($799-990/mo). Builder Portal launches first, subscriber migration later. One codebase, phased launch. Stock auto-pushes to v3 as projects+lots. See `v4/specs/2026-03-25-builder-portal-fast-track-design.md`.
- **Human-in-the-loop**: Every story requires human approval before proceeding. No auto-commits.

**CRITICAL: Never touch `sites/default/sqlconf.php`.**

## Technical Context

**Stack**: Laravel 13, PostgreSQL 16 (with pgvector, cube, earthdistance extensions), Inertia.js + React, Filament 5, Spatie suite (model-states, schemaless-attributes, laravel-phone, etc.), laravel/ai (wraps prism-php/prism + relay)

**Starter kit**: `/Users/apple/Code/cogneiss/Products/laravel-starter-kit-inertia-react` -- provides ~153 migrations, 10 modules, billing, feature flags, MCP server, AI agents, chat UI, credits, dashboards, reports, workflows, DataTable system, and more.

**Module structure**: All CRM code lives in `modules/module-crm/` with namespace `Cogneiss\ModuleCrm`. Models at `modules/module-crm/src/Models/`, migrations at `modules/module-crm/database/migrations/`, controllers at `modules/module-crm/src/Http/Controllers/`.

**Multi-tenancy**: `organization_id` on every CRM table. `BelongsToOrganization` trait on every CRM model. OrganizationScope global scope. Import commands bypass scope and explicitly set organization_id.

**UI pattern**: Every CRM list page uses `AbstractDataTable` (machour/laravel-data-table) + `AppSidebarLayout` + Inertia `useForm()`. Reference: `/users` page in starter kit. DataTable classes define `tableColumns()`, `inertiaProps()`, `showProps()`. Frontend pages at `resources/js/pages/crm/{entity}/`. Wayfinder generates typed route helpers.

**Sync architecture**: Bidirectional for 8 entities (Contact, Company, Project, Lot, Sale, Reservation, Email, Phone) via model observers + poller. One-way batch (30min) for Users, Commissions, Notes, Project Updates. Last-write-wins conflict resolution. All sync code in `modules/module-crm/src/Sync/`. See `docs/superpowers/specs/2026-03-23-plan-rewrite-and-sync-design.md` section 4 for full design.

**Key companion docs** (all relative to project root):
- `newdb.md` -- full DDL for all CRM tables
- `newdb_simple.md` -- readable column-level quick reference
- `docs/superpowers/specs/2026-03-23-plan-rewrite-and-sync-design.md` -- sync architecture, business model, decisions
- `plan.md` -- detailed build steps (source of truth)
- `dbissues.md` -- v3 DB issues to avoid

**Key rules:**
1. One migration per table, all columns + FKs + indexes in one file
2. `foreignId()->constrained()->onDelete()` on every FK. organization_id = CASCADE, references = SET NULL
3. `DB::transaction()` on every multi-step operation
4. `DECIMAL(12,2)` + `akaunting/laravel-money` for money fields
5. `spatie/laravel-model-states` for status/stage columns
6. `askedio/laravel-soft-cascade` for parent-child soft deletes
7. Import commands must be idempotent (safe to re-run)
8. All syncable models implement `SyncableContract` + use `Syncable` trait
9. No `DB::raw()` -- use Eloquent (sync depends on observers)
10. AI SDK: `laravel/ai` (not prism-php/prism directly)
11. Namespace: `Cogneiss\ModuleCrm` (not `Modules\Crm`)
12. No auto-commits without human approval

## User Stories

### US-001: CRM Module Setup

**Status:** todo
**Priority:** 1
**Description:** Set up the CRM module foundation within the existing `modules/module-crm/` scaffold. The scaffold already exists in the starter kit but contains generic CRM tables (crm_contacts, crm_deals, crm_pipelines, crm_activities) that must be deleted. FusionCRM has a property/real-estate domain model that is fundamentally different.

Create PostgreSQL extensions (vector, cube, earthdistance). Register module-crm in `config/modules.php`. Verify `composer.json` autoload has `Cogneiss\ModuleCrm` namespace. Configure Filament Admin panel to discover module-crm resources. Delete scaffold migrations and models. Set up the directory structure (keeping existing dirs, adding missing ones): `database/migrations/`, `database/seeders/`, `database/factories/`, `src/Providers/`, `src/Features/`, `src/Models/Concerns/`, `src/Http/Controllers/`, `src/Http/Requests/`, `src/Filament/Resources/`, `src/Console/Commands/`, `src/Observers/`, `src/Sync/`, `src/Traits/`, `src/Actions/`, `src/Policies/`.

Add CRM morph map entries in `app/Providers/AppServiceProvider.php` boot() for: contact, project, lot, sale, note, property_enquiry, property_search, property_reservation. Enable `Model::preventLazyLoading(!app()->isProduction())`. Add `contact_origin` enum CHECK constraint design. Create `CrmFeature` Pennant feature flag at `src/Features/CrmFeature.php`.

See `plan.md` Step 0 items 1-5 for full details.

- [ ] PostgreSQL extensions created: vector, cube, earthdistance (`SELECT * FROM pg_extension` shows all three)
- [ ] `module-crm` set to `true` in `config/modules.php`
- [ ] `composer.json` autoload has `Cogneiss\\ModuleCrm\\` mapped to `modules/module-crm/src/`
- [ ] Filament Admin panel discovers `Cogneiss\ModuleCrm\Filament\Resources`
- [ ] Scaffold CRM tables removed (no `crm_contacts`, `crm_deals`, `crm_pipelines`, `crm_activities` migrations)
- [ ] All required directories exist under `modules/module-crm/`
- [ ] Morph map registered in `AppServiceProvider::boot()` with all 8 CRM aliases
- [ ] `Model::preventLazyLoading(!app()->isProduction())` configured
- [ ] `CrmFeature` Pennant feature flag exists
- [ ] `CrmModuleServiceProvider` extends `App\Support\ModuleServiceProvider`
- [ ] Existing starter kit modules untouched (announcements, billing, blog, changelog, contact, dashboards, gamification, help, page-builder, reports, workflows)

### US-002: Config, Seeders, and Roles

**Status:** todo
**Priority:** 2
**Description:** Create the sync config, PIAB organization seed, lookup table seeders, Spatie Permission roles, and Stripe stub configuration.

Create `config/sync.php` with keys: `piab_organization_id` (from env), `tiebreaker_source` (default 'v3'), `batch_chunk_size` (default 500), `poll_interval` (default 60), `batch_interval` (default 1800).

Create database seeder to create the PIAB organization: `Organization::create(['name' => 'PIAB', 'slug' => 'piab'])`. Store its ID via `SYNC_PIAB_ORG_ID` env var.

Configure Spatie Permission roles: superadmin, admin, subscriber, agent, bdm, affiliate. See `archive/rebuild-plan/00-roles-permissions.md` for role definitions.

Create seeders for lookup tables imported from `mysql_legacy`: sources (17 rows), companies (976), developers (336), projecttypes (12), states (8), suburbs (15,299). All must have `organization_id` set to PIAB org for shared data. All org_id FKs use ON DELETE CASCADE.

Stripe is already in composer.json -- configure but stub (return success without charging). No real payment credentials yet.

See `plan.md` Step 0 items 7-11 for full details.

- [ ] `config/sync.php` exists and returns correct values (`php artisan tinker` -> `config('sync.piab_organization_id')`)
- [ ] `php artisan migrate:fresh --seed` runs clean without errors
- [ ] PIAB organization exists in database with known ID
- [ ] App loads at `localhost:8000`
- [ ] Filament admin panel loads at `/admin`
- [ ] Filament system panel loads at `/system` (superadmin only)
- [ ] Roles seeded via Shield: superadmin, admin, subscriber, agent, bdm, affiliate
- [ ] Lookup tables populated: sources (17), companies (976), developers (336), projecttypes (12), states (8), suburbs (15,299)
- [ ] All lookup records have `organization_id` set
- [ ] Pulse accessible at `/pulse`
- [ ] `php artisan health:check` passes
- [ ] `DB::connection('mysql_legacy')->table('leads')->count()` returns 9735
- [ ] Stripe configured in stub mode

### US-003: Contact Migrations and Models

**Status:** todo
**Priority:** 3
**Description:** Create the database migrations and Eloquent models for contacts, contact_emails, and contact_phones. All migrations go in `modules/module-crm/database/migrations/`.

**Migrations** (see `newdb_simple.md` "contacts" section for all columns):
1. `create_contacts_table.php` -- Required columns: `organization_id` (FK CASCADE), `legacy_lead_id`, `contact_origin` (CHECK IN 'property','saas_product'), `synced_at`, `sync_source`. Include partial index on `(organization_id, stage)`, GIN index on `extra_attributes`. All reference FKs (source_id, company_id, created_by, etc.) ON DELETE SET NULL.
2. `create_contact_emails_table.php` -- Required: `organization_id`, `legacy_email_id`, `synced_at`, `sync_source`. `contact_id` FK CASCADE.
3. `create_contact_phones_table.php` -- Required: `organization_id`, `legacy_phone_id`, `synced_at`, `sync_source`. `contact_id` FK CASCADE.

**Models** (all in `modules/module-crm/src/Models/`, namespace `Cogneiss\ModuleCrm\Models`):
- `Contact.php`: Uses traits `BelongsToOrganization`, `Syncable`, `HasFactory`, `SoftDeletes`, `LogsActivity`, soft-cascade `['emails', 'phones']`, `spatie/model-states` for 'stage', `spatie/schemaless-attributes` for 'extra_attributes', `wildside/userstamps` for created_by/updated_by. Relationship traits in `src/Traits/`: `HasCommunications`, `HasPropertyInteractions`, `HasSalesActivity`.
- `ContactEmail.php`: Uses `BelongsToOrganization`, `Syncable`.
- `ContactPhone.php`: Uses `BelongsToOrganization`, `Syncable`. Phone validation via `propaganistas/laravel-phone`.

See `newdb.md` for full DDL. See `plan.md` Step 1 items 1-5 for details.

- [ ] `contacts` table exists with all columns from `newdb_simple.md`
- [ ] `contact_emails` table exists with correct schema
- [ ] `contact_phones` table exists with correct schema
- [ ] `organization_id` FK with ON DELETE CASCADE on all three tables
- [ ] `legacy_lead_id`, `legacy_email_id`, `legacy_phone_id` columns exist
- [ ] Partial index on `(organization_id, stage)` on contacts
- [ ] GIN index on `extra_attributes` on contacts
- [ ] CHECK constraint: `contact_origin IN ('property', 'saas_product')`
- [ ] Contact model uses all required traits (BelongsToOrganization, Syncable, SoftDeletes, LogsActivity, model-states, schemaless-attributes, userstamps)
- [ ] Soft cascade configured: deleting contact deletes emails + phones
- [ ] ContactEmail and ContactPhone models use BelongsToOrganization + Syncable
- [ ] `php artisan migrate` succeeds with new tables

### US-004: Contact Import

**Status:** todo
**Priority:** 4
**Description:** Create import and verify commands to migrate 9,735 leads from v3 MySQL to v4 PostgreSQL contacts. Commands go in `modules/module-crm/src/Console/Commands/`.

**ImportContacts.php** (register in CrmModuleServiceProvider):
- Must bypass `OrganizationScope` using `Contact::withoutGlobalScope(OrganizationScope::class)`
- Explicitly set `organization_id` on every created record
- Read `mysql_legacy.leads` (9,735 rows)
- For each lead in `DB::transaction()`:
  - Create contact, store `lead.id` as `legacy_lead_id`
  - Set `contact_origin`: 'property' (default) or 'saas_product' (platform leads)
  - Set `organization_id`: resolve via subscriber relationship chain or default to PIAB org
  - Flatten addresses from `mysql_legacy.addresses WHERE model_type='App\Models\Lead'` (street->address_line1, unit->address_line2, suburb->city)
  - Stage: use `leads.stage`, fallback to LATEST statusable entry
  - Map `is_partner` -> `type='partner'`
  - Move `important_note`, `summary_note` -> `extra_attributes` JSONB
- Split v3 polymorphic `contacts` table: `type='email_1'` -> contact_emails (is_primary=true), `type='email_2'` -> contact_emails (is_primary=false), `type='phone'` -> contact_phones (is_primary=true), website/facebook/linkedin -> `contacts.extra_attributes`, googleplus -> DROP
- Build MAP: `legacy_lead_id -> new contact_id` (cache to file/Redis for use in later steps)
- Must be IDEMPOTENT: skip if `legacy_lead_id` already exists

**VerifyContacts.php**: Count validation (v3 leads = v4 contacts = 9,735), emails (9,730), phones (9,601). FK integrity checks, stages populated, addresses filled, all contacts have organization_id and contact_origin.

See `plan.md` Step 1 IMPORT section for full field mappings and edge cases.

- [ ] `php artisan crm:import-contacts` completes without errors
- [ ] 9,735 contacts exist in database
- [ ] 9,730 contact_emails exist
- [ ] 9,601 contact_phones exist
- [ ] Social links stored in `extra_attributes` JSONB
- [ ] Addresses flattened from v3 addresses table into contact columns
- [ ] All contacts have `stage` populated
- [ ] `legacy_lead_id` -> `contact_id` MAP built and persisted
- [ ] Every contact has `organization_id` set (no NULL)
- [ ] Every contact has `contact_origin` set (no NULL)
- [ ] Re-running import produces same result (idempotent)
- [ ] `php artisan crm:verify-contacts` passes all checks

### US-005: Contact UI Pages

**Status:** todo
**Priority:** 5
**Description:** Create the CRM UI for contacts following the starter kit's DataTable pattern. Reference implementation: `/users` page -> `UsersTableController` -> `UserDataTable` -> `user-table.tsx`.

**Backend:**
- `modules/module-crm/src/DataTables/ContactDataTable.php`: Extends `AbstractDataTable`, uses `HasAi`, `HasExport`, `HasImport`, `HasInlineEdit`, `HasSelectAll`. Columns: first_name, last_name, email (from primary email), phone, company, contact_origin, stage (option type with model-state values), lead_score, created_at. Filterable: stage, contact_origin, company_id, source_id. Searchable: first_name, last_name, email.
- `modules/module-crm/src/Http/Controllers/ContactController.php`: `index()` calls `ContactDataTable::inertiaProps($request)` -> `Inertia::render('crm/contacts/index')`. `show($contact)` eager loads emails, phones, notes, sales, reservations, enquiries. Standard create/store/edit/update. Bulk soft-delete and batch-update endpoints.
- `modules/module-crm/routes/web.php`: Resource routes under `/crm` prefix. Include bulk-soft-delete and batch-update routes.

**Frontend** (at `resources/js/pages/crm/contacts/`):
- `index.tsx`: `<AppSidebarLayout>` + `<DataTable<ContactRow>>` with `tableName="contacts"`
- `show.tsx`: Contact detail with tabs (overview, emails/phones, notes, sales history, activity)
- `create.tsx` / `edit.tsx`: Form with `useForm()`, fields for identity + address + contact_origin
- `resources/js/types/crm/contact.ts`: `ContactRow`, `ContactDetail` TypeScript interfaces

**Filament admin** (lookups only): `SourceResource`, `CompanyResource`, `DeveloperResource`, `ProjecttypeResource` in `modules/module-crm/src/Filament/Resources/`.

Run `php artisan wayfinder:generate` after creating routes.

- [ ] CRM contacts list page loads at `/crm/contacts` with DataTable
- [ ] DataTable shows columns: first_name, last_name, email, phone, company, contact_origin, stage, lead_score, created_at
- [ ] Filtering works: by stage, contact_origin, company_id, source_id
- [ ] Search works: by first_name, last_name, email
- [ ] Contact show page loads at `/crm/contacts/{id}` with tabs
- [ ] Contact create form works at `/crm/contacts/create`
- [ ] Contact edit form works at `/crm/contacts/{id}/edit`
- [ ] Bulk soft-delete works from DataTable
- [ ] Soft cascade: deleting contact removes emails + phones
- [ ] Filament admin: SourceResource, CompanyResource, DeveloperResource, ProjecttypeResource accessible at `/admin`
- [ ] Wayfinder route helpers generated

### US-006: Sync Infrastructure - Contracts, Traits, and Field Mappers

**Status:** todo
**Priority:** 6
**Description:** Create the core sync infrastructure: contracts, traits, and field mappers. All code lives in `modules/module-crm/src/Sync/`. Full design in `docs/superpowers/specs/2026-03-23-plan-rewrite-and-sync-design.md` section 4.

**Create:**
1. `src/Sync/Contracts/SyncableContract.php` -- Interface for syncable models. Methods: `getLegacyIdColumn()`, `getSyncObserver()`, `getFieldMapper()`.
2. `src/Sync/Traits/Syncable.php` -- Shared sync behavior. Provides: `$syncing` runtime flag (not persisted), legacy ID accessor, sync logging helper.
3. Verify `config/sync.php` exists (from US-002).
4. Create `sync_logs` migration at `modules/module-crm/database/migrations/create_sync_logs_table.php`:
   ```sql
   id BIGSERIAL, entity_type VARCHAR(50), entity_id BIGINT, direction VARCHAR(10) ('v3_to_v4'|'v4_to_v3'),
   action VARCHAR(10) ('created'|'updated'|'skipped'|'conflict'|'failed'|'orphaned'), legacy_id BIGINT,
   payload JSONB, synced_at TIMESTAMPTZ DEFAULT NOW(), error TEXT
   ```
   Indexes: `(entity_type, entity_id)`, `(synced_at)`.
5. Create 8 field mappers at `src/Sync/FieldMappers/`:
   - `ContactFieldMapper.php` (Contact <-> Lead): first_name=first_name, last_name=last_name, stage<->status (enum remap), extra_attributes<->[address,city,state,postcode]
   - `CompanyFieldMapper.php` (Company <-> Company)
   - `ProjectFieldMapper.php` (Project <-> Project)
   - `LotFieldMapper.php` (Lot <-> Lot)
   - `SaleFieldMapper.php` (Sale <-> Sale): Note sale.created_by in v3 = leads.id (not users.id), resolve via leads.id -> users.lead_id -> users.id
   - `ReservationFieldMapper.php` (Reservation <-> PropertyReservation)
   - `EmailFieldMapper.php` (ContactEmail <-> v3 contacts polymorphic)
   - `PhoneFieldMapper.php` (ContactPhone <-> v3 contacts polymorphic)
   Each mapper implements: `toV3(Model): array`, `toV4(array): array`.

See sync-architecture spec section 4.4 for field mapping examples. See section 8.9 for FK remapping and sync order.

- [ ] `SyncableContract` interface exists at `modules/module-crm/src/Sync/Contracts/SyncableContract.php`
- [ ] `Syncable` trait exists at `modules/module-crm/src/Sync/Traits/Syncable.php`
- [ ] `sync_logs` table migration exists and runs successfully
- [ ] `sync_logs` table has indexes on `(entity_type, entity_id)` and `(synced_at)`
- [ ] All 8 field mappers exist in `modules/module-crm/src/Sync/FieldMappers/`
- [ ] Each field mapper has `toV3()` and `toV4()` methods
- [ ] ContactFieldMapper correctly maps stage <-> status with enum remapping
- [ ] SaleFieldMapper handles created_by resolution (v3 leads.id -> v4 users.id)
- [ ] Unit tests pass for all 8 field mappers (round-trip: toV4(toV3(model)) preserves data)

### US-007: Sync Infrastructure - Observers, Jobs, and Commands

**Status:** todo
**Priority:** 7
**Description:** Create the sync observers, jobs, and artisan commands that power bidirectional sync. All code in `modules/module-crm/src/Sync/` and `modules/module-crm/src/Console/Commands/`. Full design in sync architecture spec section 4.

**Observers** (at `src/Sync/Observers/`): Create 8 sync observers: `ContactSyncObserver`, `CompanySyncObserver`, `ProjectSyncObserver`, `LotSyncObserver`, `SaleSyncObserver`, `ReservationSyncObserver`, `EmailSyncObserver`, `PhoneSyncObserver`. Each listens to: created, updated, deleted (soft-delete). Each checks `$model->syncing` flag -- skip if true (prevents infinite loop), else dispatch `SyncToLegacyJob`.

**Jobs** (at `src/Sync/Jobs/`):
- `SyncToLegacyJob.php` -- dispatched by observers (v4->v3). Uses field mapper to transform, upserts to `mysql_legacy` using `legacy_*_id`, logs to `sync_logs`.
- `SyncFromLegacyJob.php` -- dispatched by poller (v3->v4). Transforms v3 columns -> v4 fields, compares `updated_at` (last write wins), sets `$model->syncing = true`, saves, logs.
- `SyncBatchJob.php` -- scheduled batch for one-way entities (users, commissions, notes, project_updates). Chunks of 500 per transaction.
- All jobs: queue = 'sync', retry 3x with exponential backoff (30s, 120s, 480s). Failed after 3 retries -> logged with `action='failed'` + superadmin notification.

**Commands** (at `src/Console/Commands/`):
- `SyncPollCommand.php` -- polls v3 for changes every 60s. Processes 8 entities sequentially in dependency order: Companies, Contacts, Projects, Lots, Sales, Reservations, Emails, Phones.
- `SyncBatchCommand.php` -- batch sync one-way entities every 30min.
- `SyncFullCommand.php` -- full sync with `--direction=v3_to_v4|v4_to_v3|both` (default: both).
- `SyncStatusCommand.php` -- reports sync health: lag per entity, error rate, row counts.

Register all 8 observers in `CrmModuleServiceProvider::boot()`. Add scheduler entries in `routes/console.php`: `sync:poll` every minute, `sync:batch` every 30 minutes.

Provide the v3 MySQL index SQL (7 `ALTER TABLE` statements) as a migration note or documentation for manual execution on v3.

- [ ] All 8 sync observers exist in `modules/module-crm/src/Sync/Observers/`
- [ ] All 8 observers registered in `CrmModuleServiceProvider::boot()`
- [ ] `SyncToLegacyJob`, `SyncFromLegacyJob`, `SyncBatchJob` exist with correct queue config
- [ ] Jobs retry 3x with exponential backoff (30s, 120s, 480s)
- [ ] `SyncPollCommand` processes entities in correct dependency order
- [ ] `SyncBatchCommand` handles one-way entities (users, commissions, notes, project_updates)
- [ ] `SyncFullCommand` accepts `--direction` flag
- [ ] `SyncStatusCommand` outputs health report
- [ ] Scheduler entries added: `sync:poll` every minute, `sync:batch` every 30 minutes
- [ ] v3 MySQL index SQL documented (7 ALTER TABLE statements)
- [ ] Loop prevention works: v4 save -> syncs to v3 -> poller picks up -> does NOT re-sync back

### US-008: Sync Testing and Dashboard

**Status:** todo
**Priority:** 8
**Description:** Create the Filament sync dashboard widget and perform end-to-end sync testing. The dashboard is a Filament widget in the System panel (superadmin only).

**SyncDashboard widget** (Filament, System panel):
- Shows: sync lag per entity, error rate (last 24h), conflict count, last sync times, entity-level row count comparison (v3 vs v4)
- Sync errors with severity 'error' trigger a Laravel notification (email to superadmin)

**Testing:**
1. Start sync queue worker: `php artisan queue:work --queue=sync`
2. Test v4->v3: Create a Contact in v4 -> verify row appears in `mysql_legacy.leads`
3. Test v3->v4: Update a Lead in v3 -> run `sync:poll` -> verify change appears in v4
4. Test loop prevention: v4 save -> syncs to v3 -> poller detects change -> does NOT re-sync back to v4
5. Test conflict resolution: simultaneous edits -> last write wins based on `updated_at`

- [ ] SyncDashboard widget visible in Filament System panel (superadmin only)
- [ ] Dashboard shows sync lag per entity
- [ ] Dashboard shows error rate for last 24 hours
- [ ] Dashboard shows row count comparison (v3 vs v4)
- [ ] `SyncPollCommand` runs without errors
- [ ] `SyncBatchCommand` runs without errors
- [ ] `SyncStatusCommand` shows healthy status
- [ ] v4->v3 test: new Contact appears in v3 `leads` table
- [ ] v3->v4 test: updated v3 Lead appears in v4 `contacts` table
- [ ] Loop prevention verified: no infinite sync cycles
- [ ] Sync queue worker processes jobs from `sync` queue
- [ ] v3 `updated_at` indexes created on 7 tables

### US-009: Users Import and Organization Setup

**Status:** todo
**Priority:** 9
**Description:** Import 1,502 users from v3 and create Organizations for subscriber users. Each v3 subscriber becomes a v4 Organization with the subscriber's user as org owner.

**Migration:** Add `contact_id` FK to users table: `$table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete()`.

**ImportUsers.php** (at `modules/module-crm/src/Console/Commands/`):
- Read `mysql_legacy.users` (1,502 rows)
- Import: name, email, password (keep hashed), email_verified_at, timestamps
- Set `users.contact_id` using `legacy_lead_id` MAP built in US-004
- Read `mysql_legacy.model_has_roles` (11,627 entries) -> map v3 role names to Spatie Permission roles
- Create Organization per subscriber-role user (org owner). Store legacy user->org mapping for use in later steps.
- Assign other users to organizations via `organization_user` pivot table

See `docs/superpowers/specs/2026-03-23-plan-rewrite-and-sync-design.md` section 3.2 for subscriber -> org mapping logic.

- [ ] 1,502 users imported
- [ ] Users have correct Spatie Permission roles assigned
- [ ] `contact_id` linked to corresponding contact where applicable
- [ ] One Organization created per subscriber-role user
- [ ] Users can log in with their v3 passwords (password hashes preserved)
- [ ] Impersonation works: superadmin can impersonate any user
- [ ] User->Organization mapping persisted for use in subsequent import steps
- [ ] Non-subscriber users assigned to appropriate organizations

### US-010: Project and Lot Migrations and Models

**Status:** todo
**Priority:** 10
**Description:** Create migrations and models for projects, project decomposition tables (specs, investment, features, pricing), lots, project_favorites, and project_updates. All in `modules/module-crm/`.

**Migrations** (see `newdb_simple.md` for all columns, all with `organization_id` FK CASCADE):
- `create_projects_table.php`: Core columns + address + `legacy_project_id` + `synced_at` + `sync_source` + `extra_attributes` JSONB with GIN index
- `create_project_specs_table.php`: UNIQUE `project_id`
- `create_project_investment_table.php`: UNIQUE `project_id`
- `create_project_features_table.php`: UNIQUE `project_id`
- `create_project_pricing_table.php`: UNIQUE `project_id`
- `create_lots_table.php`: 49 columns + `legacy_lot_id` + `synced_at` + `sync_source` (see `newdb_simple.md`)
- `create_project_favorites_table.php`: Composite PK (`user_id` + `project_id`)
- `create_project_updates_table.php`

**Models** (all `Cogneiss\ModuleCrm\Models`):
- `Project`: `BelongsToOrganization`, `Syncable`, `hasOne(specs, investment, features, pricing)`, `hasMany(lots)`. Visibility: PIAB org projects = shared (visible to all), subscriber org = scoped.
- `Lot`: `BelongsToOrganization`, `Syncable`, `spatie/model-states` for stage, `akaunting/money` for prices, `SoftDeletes`. Stage transitions: available -> reserved -> sold (with `lockForUpdate()`).
- `ProjectSpec`, `ProjectInvestment`, `ProjectFeature`, `ProjectPricing`: `BelongsToOrganization`.

Register sync observers in `CrmModuleServiceProvider`: `Project::observe(ProjectSyncObserver::class)`, `Lot::observe(LotSyncObserver::class)`.

- [ ] `projects` table exists with all columns including `legacy_project_id`, `synced_at`, `sync_source`
- [ ] `project_specs`, `project_investment`, `project_features`, `project_pricing` tables exist with UNIQUE `project_id`
- [ ] `lots` table exists with 49+ columns including `legacy_lot_id`, `synced_at`, `sync_source`
- [ ] `project_favorites` table with composite PK
- [ ] `project_updates` table exists
- [ ] All tables have `organization_id` FK with ON DELETE CASCADE
- [ ] GIN index on `projects.extra_attributes`
- [ ] Lot model uses `spatie/model-states` for stage and `akaunting/money` for prices
- [ ] Lot stage transitions use `lockForUpdate()` in transaction
- [ ] Project model has `hasOne` relationships to specs, investment, features, pricing
- [ ] `ProjectSyncObserver` and `LotSyncObserver` registered in service provider
- [ ] `php artisan migrate` succeeds

### US-011: Projects and Lots Import

**Status:** todo
**Priority:** 11
**Description:** Import projects (15,481), lots (121,360), project_updates (155,273), and favorites (2,172) from v3. Commands in `modules/module-crm/src/Console/Commands/`.

**ImportProjects.php:**
- Read `mysql_legacy.projects` (15,481 rows)
- Per project in `DB::transaction()`: create projects row, flatten address from `mysql_legacy.addresses`, set `organization_id` = PIAB org (all v3 projects are shared), store `legacy_project_id`. Create `project_specs`, `project_investment`, `project_features`, `project_pricing`. Move `is_featured`, `is_hot_property`, `land_info` -> `extra_attributes`. Stage from `projects.stage` + statusables fallback.

**ImportLots.php:**
- 121,360 rows. Batch 1000. `organization_id` inherited from project.
- Store `legacy_lot_id`. Column renames: car->car_spaces, storyes->storeys, weekly_rent->rent_weekly, etc.
- Stage from `lots.stage` + statusables fallback. Money cast all price columns.

**ImportProjectUpdates.php:** 155,273 rows. Direct copy with batching.

**ImportFavorites.php:** `love_reactions` (2,172) -> `project_favorites` (user_id, project_id).

All imports must be idempotent.

- [ ] 15,481 projects imported (each has associated specs + investment + features + pricing rows)
- [ ] All projects have `organization_id` set (PIAB org for all v3 data)
- [ ] `legacy_project_id` populated on all projects
- [ ] 121,360 lots imported with stages and soft delete support
- [ ] All lots have `organization_id` + `legacy_lot_id`
- [ ] 155,273 project_updates imported
- [ ] 2,172 project_favorites imported
- [ ] Money fields (prices) display correctly via `akaunting/money`
- [ ] Lot stage transitions work: available -> reserved -> sold (with lockForUpdate)
- [ ] Column renames applied correctly (car->car_spaces, storyes->storeys, etc.)
- [ ] All imports are idempotent (re-run produces same result)
- [ ] Sync test: create project in v4 -> appears in v3 via ProjectSyncObserver

### US-012: Project and Lot UI Pages

**Status:** todo
**Priority:** 12
**Description:** Create CRM UI pages for projects and lots following the DataTable pattern.

**Backend:**
- `src/DataTables/ProjectDataTable.php`: Columns: title, estate, stage, developer, min_price, max_price, total_lots, is_featured. Filterable: stage, developer_id, projecttype_id, organization_id (shared vs own).
- `src/DataTables/LotDataTable.php`: Columns: title, project (relation), stage, price, bedrooms, bathrooms, land_size. Filterable: stage, project_id, price range, bedrooms.
- `src/Http/Controllers/ProjectController.php`: Show page with tabs (overview, specs, investment, features, pricing, lots list, updates). Edit specs/investment/features/pricing separately.
- `src/Http/Controllers/LotController.php`: Show page with detail + reservation history + sales history.
- Routes at `modules/module-crm/routes/web.php`: `/crm/projects` (CRUD + bulk), `/crm/lots` (CRUD + bulk).

**Frontend** (at `resources/js/pages/crm/`):
- `projects/index.tsx`, `show.tsx`, `create.tsx`, `edit.tsx`
- `lots/index.tsx`, `show.tsx`, `create.tsx`, `edit.tsx`
- Shared listings: index page shows PIAB + own org projects together with org badge

Run `php artisan wayfinder:generate` after creating routes.

- [ ] CRM project list page loads at `/crm/projects` with DataTable
- [ ] Project DataTable shows: title, estate, stage, developer, min_price, max_price, total_lots, is_featured
- [ ] Project show page has tabs: overview, specs, investment, features, pricing, lots list, updates
- [ ] Project create and edit forms work
- [ ] CRM lot list page loads at `/crm/lots` with DataTable
- [ ] Lot DataTable shows: title, project, stage, price, bedrooms, bathrooms, land_size
- [ ] Lot show page displays detail + reservation history + sales history
- [ ] Lot create and edit forms work
- [ ] Shared listings: PIAB org projects visible alongside subscriber projects with org badge
- [ ] Filtering works on both DataTables
- [ ] Wayfinder route helpers generated

### US-013: Sales and Transaction Migrations and Models

**Status:** todo
**Priority:** 13
**Description:** Create migrations and models for sales, commissions, commission_tiers, property_reservations, property_enquiries, and property_searches. All in `modules/module-crm/`.

**Migrations** (see `newdb_simple.md` for exact columns):
- `create_sales_table.php`: 7 contact FK columns (all ON DELETE SET NULL), `organization_id` FK CASCADE, `legacy_sale_id`, `synced_at`, `sync_source`, `extra_attributes` JSONB.
- `create_commissions_table.php`: `sale_id` FK CASCADE, UNIQUE constraint, CHECK on `type` column.
- `create_commission_tiers_table.php`: `organization_id`, `volume_threshold`, `commission_rate`.
- `create_property_reservations_table.php`: All trust/finance fields, `legacy_reservation_id`, `synced_at`, `sync_source`.
- `create_property_enquiries_table.php`: `extra_attributes` JSONB for detail fields.
- `create_property_searches_table.php`: `search_criteria` JSONB.

**Models** (`Cogneiss\ModuleCrm\Models`): Sale, Commission, CommissionTier, PropertyReservation, PropertyEnquiry, PropertySearch. All use `BelongsToOrganization`. Sale and PropertyReservation also use `Syncable`. All status/stage columns use `spatie/model-states`. Sale operations use `DB::transaction()` + `lockForUpdate()` for lot stage changes.

**IMPORTANT**: `Sale.created_by` in v3 = `leads.id` (not `users.id`). Field mapper resolves via `leads.id` -> `users.lead_id` -> `users.id`.

**Commission flow** (launch feature): `commission_tiers` table holds volume_threshold -> rate per org. Calculated on sale creation based on subscriber's trailing sales count.

Register sync observers: `Sale::observe(SaleSyncObserver::class)`, `PropertyReservation::observe(ReservationSyncObserver::class)`.

- [ ] `sales` table exists with 7 contact FK columns (all ON DELETE SET NULL)
- [ ] `commissions` table with UNIQUE constraint and CHECK on type
- [ ] `commission_tiers` table exists
- [ ] `property_reservations` table with `legacy_reservation_id`, `synced_at`, `sync_source`
- [ ] `property_enquiries` table with `extra_attributes` JSONB
- [ ] `property_searches` table with `search_criteria` JSONB
- [ ] All tables have `organization_id` FK with ON DELETE CASCADE
- [ ] Sale and PropertyReservation models use `Syncable` trait
- [ ] All status/stage columns use `spatie/model-states`
- [ ] `SaleSyncObserver` and `ReservationSyncObserver` registered
- [ ] `php artisan migrate` succeeds

### US-014: Sales and Transaction Import

**Status:** todo
**Priority:** 14
**Description:** Import sales (447), reservations (120), enquiries (721), and searches (316) from v3. Commands in `modules/module-crm/src/Console/Commands/`.

**ImportSales.php:**
- Read `mysql_legacy.sales` (447 rows)
- Set `organization_id` via `subscriber_id` -> Lead -> User -> Organization chain
- Store `legacy_sale_id`
- Remap all 7 contact FK columns using `legacy_lead_id` MAP
- Normalize commissions: for each of 7 commission columns (piab_comm, affiliate_comm, etc.), if value > 0, INSERT into commissions table (sale_id, type, amount)
- Move `divide_percent`, `expected_commissions`, etc. -> `extra_attributes` JSONB
- Status from `statusables` LATEST entry

**ImportReservations.php:** 120 rows. Store `legacy_reservation_id`. Remap: `purchaser1_id` -> `primary_contact_id`, etc.

**ImportEnquiries.php:** 721 rows. Move 10 detail fields -> `extra_attributes` JSONB.

**ImportSearches.php:** 316 rows. Move 12 criteria fields -> `search_criteria` JSONB.

Seed `commission_tiers` with default rates.

- [ ] 447 sales imported, all 7 contact FKs valid (no broken references)
- [ ] All sales have `organization_id` + `legacy_sale_id`
- [ ] Commission rows created = count of non-zero commission columns in v3 sales
- [ ] UNIQUE constraint prevents duplicate commissions
- [ ] `commission_tiers` table seeded with default rates
- [ ] 120 reservations imported with `legacy_reservation_id`
- [ ] 721 enquiries imported with detail fields in `extra_attributes`
- [ ] 316 searches imported with criteria in `search_criteria` JSONB
- [ ] Spot-check 10 random sales records: FK remapping correct
- [ ] Sale status transitions work via model-states
- [ ] All imports are idempotent

### US-015: Sales and Transaction UI Pages

**Status:** todo
**Priority:** 15
**Description:** Create CRM UI pages for sales and reservations following the DataTable pattern.

**Backend:**
- `src/DataTables/SaleDataTable.php`: Columns: client (contact name), lot, project, status, purchase_price, commissions total, created_at. Filterable: status, project_id, lot_id, organization_id.
- `src/DataTables/ReservationDataTable.php`: Columns: primary_contact, lot, project, purchase_price, status, created_at.
- `src/Http/Controllers/SaleController.php`: Show page with tabs (overview, commissions breakdown, documents, notes). Create form: select contact + lot -> auto-fill project/developer, commission tier calculation.
- `src/Http/Controllers/ReservationController.php`: Show page with purchaser details, trust/finance fields, contract status.
- Routes: `/crm/sales` (CRUD + bulk), `/crm/reservations` (CRUD + bulk).

**Frontend:**
- `resources/js/pages/crm/sales/` -- index.tsx, show.tsx, create.tsx, edit.tsx. Commission breakdown visible on sale detail.
- `resources/js/pages/crm/reservations/` -- index.tsx, show.tsx, create.tsx, edit.tsx.

- [ ] CRM sales list page loads at `/crm/sales` with DataTable
- [ ] Sale DataTable shows: client, lot, project, status, purchase_price, commissions total, created_at
- [ ] Sale show page has tabs: overview, commissions breakdown, documents, notes
- [ ] Sale create form: select contact + lot auto-fills project/developer
- [ ] Commission tier calculation works on sale creation
- [ ] CRM reservations list page loads at `/crm/reservations` with DataTable
- [ ] Reservation show page displays purchaser details, trust/finance fields, contract status
- [ ] Filtering works on both DataTables
- [ ] Sale and reservation create/edit forms work

### US-016: Import Notes, Comments, Partners, Tasks, and Relationships

**Status:** todo
**Priority:** 16
**Description:** Import secondary entities from v3. All import commands in `modules/module-crm/src/Console/Commands/`.

**ImportNotes.php** (22,681 rows):
- Update morph type: `App\Models\Lead` -> `'contact'` (morph map alias)
- Remap `noteable_id` for Lead notes using `legacy_lead_id` MAP
- Set `organization_id` via `author_id` -> User -> Organization

**ImportComments.php** (4,259 rows):
- Import to starter kit `crm_comments` table
- Update morph types. Direct column copy.
- Set `organization_id`.

**ImportPartners.php** (299 rows):
- Column renames: `lead_id` -> `contact_id`, `parent_id` -> `parent_partner_id`, `relationship` -> `partner_type`
- Set `organization_id`.

**ImportRelationships.php** (7,378 rows):
- Column renames: `account_id` -> `account_contact_id`, `relation_id` -> `relation_contact_id`
- Remap both IDs via `legacy_lead_id` MAP. Set `organization_id`.

**ImportTasks.php** (57 rows):
- Column renames: `assigned_id` -> `assigned_to_user_id`, `attached_id` -> `attached_contact_id` (remap via MAP), `start_at` -> `due_at`, `end_at` -> DROP
- Set `organization_id`.

Create necessary migrations for partners, relationships, and tasks tables if they don't exist in the starter kit.

- [ ] 22,681 notes imported with morph types updated to aliases
- [ ] 4,259 comments imported
- [ ] 7,378 relationships imported with both FK columns remapped
- [ ] 299 partners imported with column renames applied
- [ ] 57 tasks imported with column renames applied
- [ ] All imported records have `organization_id` set (no NULL)
- [ ] Morph types use aliases (no `App\Models\*` strings)
- [ ] Contact show page displays associated notes, tasks, relationships

### US-017: Import Media, Lookups, and Resources

**Status:** todo
**Priority:** 17
**Description:** Import media, lookups with schema changes, and resource tables from v3. All commands in `modules/module-crm/src/Console/Commands/`.

**ImportMedia.php** (108,510 rows):
- Update `model_type` to morph map aliases. No column changes needed.

**Lookup migrations and imports:**
- `sources`: Rename `label` -> `name`. Add `organization_id` FK CASCADE + UNIQUE on `(organization_id, name)`.
- `companies`: Rename `extra_company_info` -> `extra_attributes`. Add `legacy_company_id` + `synced_at` + `sync_source`. Add `organization_id` FK CASCADE.
- `developers`: Rename `modified_by` -> `updated_by`. DROP `extra_attributes` (0% populated). Add `organization_id` FK CASCADE.
- `projecttypes`: Add `organization_id` FK CASCADE.
- `states`: No org_id (global reference).
- `suburbs`: DROP `au_town_id`. No org_id needed (global reference).

Register `CompanySyncObserver` in `CrmModuleServiceProvider`: `Company::observe(CompanySyncObserver::class)`.

**Resource tables:**
- `resources` (138 rows): Add org_id, rename `modified_by` -> `updated_by`.
- `resource_categories` (5 rows): Direct copy.
- `resource_groups` (9 rows): Add org_id.

**SKIP** (do NOT import): `activity_log` (461K, start fresh), `login_histories` (42K, use starter kit login_events), `column_management` (4.5K, use data_table_saved_views), `user_api_tokens/ips` (use Sanctum), WordPress tables, `services/company_service` (5 years stale).

- [ ] 108,510 media records with morph types updated to aliases
- [ ] Companies have `legacy_company_id` populated
- [ ] `CompanySyncObserver` registered in service provider
- [ ] Sources renamed: `label` -> `name` with UNIQUE on `(organization_id, name)`
- [ ] Developers renamed: `modified_by` -> `updated_by`
- [ ] Resources (138), resource_categories (5), resource_groups (9) imported
- [ ] All imported records with org_id have `organization_id` set
- [ ] Skipped tables NOT present in v4: activity_log, login_histories, column_management, WordPress tables
- [ ] Contact show page displays all related data: notes, emails, phones, tasks, relationships, media

### US-018: AI Tools and Verification

**Status:** todo
**Priority:** 18
**Description:** Add CRM-specific AI tools for the built-in `laravel/ai` agent system, generate contact embeddings, and run full verification across all imported data. The starter kit already provides the AI infrastructure: `AssistantAgent`, chat UI at `/chat`, MCP endpoint at `/mcp/api`, credits + credit_packs tables, `config/ai.php`.

**CRM AI Tools** (at `modules/module-crm/src/Ai/Tools/`):
- `ContactsSearch.php` -- search contacts by name, email, stage, location
- `ContactsShow.php` -- get contact with emails, phones, notes, tasks
- `ProjectsSearch.php` -- search projects by location, type, price range
- `LotsAvailable.php` -- find available lots for a project
- `SalesPipeline.php` -- get sales pipeline status + totals
- `CommissionCalc.php` -- calculate expected commissions for a sale

**CRM MCP Tools** (at `app/Mcp/Tools/`): Same tools exposed via MCP protocol at `/mcp/api`.

**v3 Bot Data** (knowledge extraction, NO table migration):
- `ai_bot_categories` (11) -> agent persona categories in code
- `ai_bot_boxes` (46) -> review, convert relevant ones to agent system prompts
- `ai_bot_prompt_commands` (481) -> export useful prompts, convert to AI tools
- Eliminated: botmysql connection, SyncUsers event, external bot service

**pgvector embeddings**: Generate embeddings for 9,735 contacts using starter kit's `HasEmbeddings` trait + `model_embeddings` table (polymorphic, org-scoped, pre-built). Use `SemanticSearchService` for similarity queries. Enables semantic search and "find contacts similar to X".

**AI credit tracking**: Configure credits + credit_packs. Rate limit with `spatie/laravel-rate-limited-job-middleware`. Config via Filament: Settings > AI (per-org overridable).

**Full verification**: Run ALL verify commands from Steps 1-5. Confirm all row counts, FK integrity, morph types, organization_ids.

- [ ] Chat UI at `/chat` responds with CRM-aware answers
- [ ] Agent can search contacts by natural language
- [ ] Agent can search projects by location, type, price range
- [ ] Agent can find available lots for a project
- [ ] Agent can calculate commissions when asked
- [ ] MCP endpoint at `/mcp/api` serves CRM tools
- [ ] Embeddings generated for 9,735 contacts
- [ ] Credits consumed and tracked per AI query
- [ ] No external bot service dependency (botmysql connection eliminated)
- [ ] ALL import verify commands pass (contacts=9735, lots=121360, projects=15481, sales=447, media=108510)
- [ ] Zero orphaned FKs across all tables
- [ ] Zero `App\Models\*` strings in morph columns
- [ ] Every CRM record has `organization_id` set

### US-019: Reporting and Dashboards

**Status:** todo
**Priority:** 19
**Description:** Create CRM dashboards, performance widgets, reports, shared listings marketplace view, and replace all v3 raw SQL patterns. The starter kit provides `modules/dashboards/` (Puck builder with `DashboardBuilderController` + `DashboardDataSourceRegistry`) and `modules/reports/` (Puck builder + `maatwebsite/excel` for export).

**Dashboards:**
- Register CRM data sources in `DashboardDataSourceRegistry`: contact pipeline, sales, lots, commissions
- Puck renders dashboard blocks

**Performance dashboard widgets** (Filament, per-org):
- Sales count (trailing 30/90/365 days)
- Conversion rate (contacts -> sales)
- Commission earned (total + by tier)
- Active leads count
- Lot availability summary

**Reports:**
- Add CRM report types to the reports module
- Export via `maatwebsite/excel` (installed in starter kit)

**Shared listings marketplace view:**
- PIAB org projects visible in every subscriber's dashboard
- Query: `WHERE organization_id = PIAB_ORG OR organization_id = current_org`
- Any subscriber can sell lots from shared projects

**Replace v3 raw SQL** (see `dbissues.md` Section 8):
- Geographic queries -> earthdistance extension
- Aggregate queries -> Eloquent with parameter binding
- Case-insensitive search -> ILIKE

- [ ] Dashboard loads with CRM data sources
- [ ] Performance widgets show correct per-org stats (sales count, conversion rate, commission earned, active leads, lot availability)
- [ ] Shared listings visible to all subscriber organizations
- [ ] Reports generate correctly with CRM data
- [ ] CSV/Excel export works via `maatwebsite/excel`
- [ ] Zero `DB::raw()` calls in entire CRM codebase
- [ ] Geographic queries use earthdistance extension
- [ ] Case-insensitive searches use ILIKE
