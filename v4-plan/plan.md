# FusionCRM v4 - Build Plan

> Starter kit: `/Users/apple/Code/cogneiss/Products/laravel-starter-kit-inertia-react`
> Schema: `newdb_simple.md` (quick ref) | `newdb.md` (full DDL)
> Sync: `sync-architecture.md`
> Issues to avoid: `dbissues.md`
> Rule: **Human approval at end of every step. No auto-commits.**
>
> ## Launch Strategy (2026-03-25)
>
> **Phase 1 — Builder Portal (SHIP FAST):** Build v4, but launch Builder Portal first.
> Builders self-signup, create stock, agents browse portal. Stock auto-pushes to v3
> as enriched projects + lots so existing subscribers see it. Zero v3 code changes.
>
> **Phase 2 — Subscriber Migration:** Import v3 data (contacts, users, sales).
> Full bidirectional sync. Subscribers switch from v3 to v4.
>
> **Phase 3 — Advanced Features:** AI, analytics, marketing, Xero, R&D.
>
> **Design doc:** `v4/specs/2026-03-25-builder-portal-fast-track-design.md`
> **Competitive spec:** `v4/specs/kenekt-competitive-feature-spec.md`
>
> ### Phase 1 Build Order
> ```
> Pre-Flight → Step 0a (full) → Step 0b (full) → Step 2-slim (models+auth)
> → Step 3-slim (models only) → Step 4-slim (models only)
> → Step 1.5-slim (v3 push only) → PRD 12 Tier 1 → LAUNCH
> ```

---

## Pre-Flight
> Depends on: Nothing (first step)

```bash
# 1. Clone starter kit
cp -r /Users/apple/Code/cogneiss/Products/laravel-starter-kit-inertia-react /path/to/fusioncrm-v4
cd /path/to/fusioncrm-v4

# 2. Install
composer install && bun install && bun run build

# 3. Configure .env
cp .env.example .env
# Set:
#   DB_CONNECTION=pgsql
#   DB_HOST=127.0.0.1
#   DB_PORT=5432
#   DB_DATABASE=fusioncrm_v4
#   DB_USERNAME=root
#   DB_PASSWORD=
#   TYPESENSE_API_KEY=your_key
#   TYPESENSE_HOST=localhost
#   SYNC_QUEUE=sync
#   SYNC_PIAB_ORG_ID=1
#   SYNC_TIEBREAKER=v3

# 4. Verify NTP (required for sync clock alignment)
ntpstat || timedatectl show-ntp

# 5. Run starter kit migrations (~125 tables)
php artisan key:generate
php artisan migrate
php artisan serve  # verify app loads at localhost:8000

# 6. Add legacy MySQL connection to config/database.php 'connections' array:
#   'mysql_legacy' => [
#       'driver' => 'mysql',
#       'host' => '127.0.0.1',
#       'port' => '3306',
#       'database' => 'fv3',
#       'username' => 'root',
#       'password' => '',
#       'charset' => 'utf8mb4',
#       'collation' => 'utf8mb4_unicode_ci',
#   ],

# 7. Test legacy connection
php artisan tinker
>>> DB::connection('mysql_legacy')->table('leads')->count()
# Expected: 9735
```

VERIFY:
```
[ ] php artisan serve → browser at localhost:8000 returns 200
[ ] php artisan tinker --execute="echo DB::connection('mysql_legacy')->table('leads')->count()" → 9735
```

⏸ HUMAN GATE: Review and approve before proceeding.

---

## UI Pattern Reference

> Follow the starter kit's **DataTable pattern** (not the simple CRM pattern).
> Reference implementation: `/users` page → `UsersTableController` → `UserDataTable` → `user-table.tsx`

**Every CRM list page uses this stack:**

```
Backend:
  DataTable class  → modules/module-crm/src/DataTables/{Entity}DataTable.php
                     extends AbstractDataTable (machour/laravel-data-table)
                     defines: tableColumns(), inertiaProps(), showProps()
  Controller       → modules/module-crm/src/Http/Controllers/{Entity}Controller.php
                     index() calls {Entity}DataTable::inertiaProps($request)
                     returns Inertia::render('crm/{entity}/index', $props)
  Routes           → modules/module-crm/routes/web.php
                     Route::get('/crm/{entity}', [Controller, 'index'])
                     Route::get('/crm/{entity}/{id}', [Controller, 'show'])
                     Route::get('/crm/{entity}/create', [Controller, 'create'])
                     Route::post('/crm/{entity}', [Controller, 'store'])
                     Route::get('/crm/{entity}/{id}/edit', [Controller, 'edit'])
                     Route::put('/crm/{entity}/{id}', [Controller, 'update'])
                     Route::post('/crm/{entity}/bulk-soft-delete', ...)

Frontend:
  Index page       → resources/js/pages/crm/{entity}/index.tsx
                     <AppSidebarLayout breadcrumbs={[...]}>
                       <DataTable<EntityRow> tableData={tableData} tableName="{entity}" />
                     </AppSidebarLayout>
  Show page        → resources/js/pages/crm/{entity}/show.tsx
  Create/Edit      → resources/js/pages/crm/{entity}/create.tsx, edit.tsx
                     Use Inertia useForm() for form handling
  TypeScript types → resources/js/types/crm/{entity}.ts
```

**DataTable column definition** (in DataTable class):
```php
 ColumnBuilder::make('first_name', 'First Name')->searchable()->sortable()->type('text')->build(),
 ColumnBuilder::make('stage', 'Stage')->type('option')->options([...])->filterable()->build(),
 ColumnBuilder::make('email', 'Email')->searchable()->type('email')->build(),
```

**Key components from starter kit:**
- `<DataTable<T>>` — server-side table with filter, sort, paginate, bulk actions, export, inline edit
- `<AppSidebarLayout>` — authenticated layout with sidebar + breadcrumbs + command palette
- `useForm()` from `@inertiajs/react` — form state + validation + submit
- Wayfinder generates typed route helpers in `@/actions/` (auto-generated, don't edit)

---

## Step 0a: Module Setup
> Depends on: Pre-Flight (app running, legacy DB connected)

```
1. PostgreSQL extensions
   php artisan tinker
   >>> DB::statement('CREATE EXTENSION IF NOT EXISTS vector')
   >>> DB::statement('CREATE EXTENSION IF NOT EXISTS cube')
   >>> DB::statement('CREATE EXTENSION IF NOT EXISTS earthdistance')

2. CRM module setup
   The starter kit has a STUB at `modules/crm/` with namespace `Modules\Crm`.
   We RENAME it to `modules/module-crm/` with namespace `Cogneiss\ModuleCrm`
   (matching the module-hr pattern).

   a. Rename module directory:
      mv modules/crm modules/module-crm

   b. Update composer.json autoload — change:
      FROM: "Modules\\Crm\\": "modules/crm/src/"
      TO:   "Cogneiss\\ModuleCrm\\": "modules/module-crm/src/"
      (also update Database namespace if present)

   c. Update all namespace declarations inside modules/module-crm/src/:
      Find/replace: `namespace Modules\Crm` → `namespace Cogneiss\ModuleCrm`
      Find/replace: `use Modules\Crm` → `use Cogneiss\ModuleCrm`

   d. Register module-crm in config/modules.php:
      'module-crm' => true,

   e. Configure Filament Admin panel to discover module-crm resources:
      In app/Providers/Filament/AdminPanelProvider.php:
        ->discoverResources(
            in: base_path('modules/module-crm/src/Filament/Resources'),
            for: 'Cogneiss\\ModuleCrm\\Filament\\Resources'
        )

   f. Delete scaffold migrations + models — the existing scaffold (crm_contacts,
      crm_deals, crm_pipelines, crm_activities) is generic CRM. Delete it.
      FusionCRM has property-specific tables built from scratch.

   g. Run: composer dump-autoload

   Structure (keep existing dirs, add what's missing):
     modules/module-crm/
       database/migrations/        <- ALL CRM table migrations
       database/seeders/
       database/factories/
       src/
         Providers/CrmModuleServiceProvider.php  <- extends App\Support\ModuleServiceProvider
         Features/CrmFeature.php    <- Pennant feature flag
         Models/
           Concerns/                <- BelongsToOrganization (from app/Models/Concerns/)
         Http/Controllers/          <- Inertia controllers
         Http/Requests/
         Filament/Resources/        <- Lookup admin ONLY
         Console/Commands/          <- Import + Verify commands
         Observers/
         Sync/                      <- All sync code (Step 1.5)
         Traits/
         Actions/
         Policies/

3. Morph map — add to app/Providers/AppServiceProvider.php boot()
   Relation::enforceMorphMap([
       'contact'              => \Cogneiss\ModuleCrm\Models\Contact::class,
       'project'              => \Cogneiss\ModuleCrm\Models\Project::class,
       'lot'                  => \Cogneiss\ModuleCrm\Models\Lot::class,
       'sale'                 => \Cogneiss\ModuleCrm\Models\Sale::class,
       'note'                 => \Cogneiss\ModuleCrm\Models\Note::class,
       'property_enquiry'     => \Cogneiss\ModuleCrm\Models\PropertyEnquiry::class,
       'property_search'      => \Cogneiss\ModuleCrm\Models\PropertySearch::class,
       'property_reservation' => \Cogneiss\ModuleCrm\Models\PropertyReservation::class,
   ]);

4. Prevent lazy loading
   Model::preventLazyLoading(!app()->isProduction());

5. Filament panels (no action needed, verify):
   - System panel (/system) = superadmin: settings, monitoring, orgs, permissions
   - Admin panel (/admin) = org admin: users, roles, mail templates, lookups
   CRM UI = Inertia + React (resources/js/pages/crm/*)
   Filament CRM resources (admin panel) = lookup management ONLY
```

VERIFY:
```
[ ] php artisan tinker --execute="DB::select(\"SELECT extname FROM pg_extension WHERE extname='vector'\")" → returns 1 row
[ ] php artisan tinker --execute="DB::select(\"SELECT extname FROM pg_extension WHERE extname='cube'\")" → returns 1 row
[ ] php artisan tinker --execute="DB::select(\"SELECT extname FROM pg_extension WHERE extname='earthdistance'\")" → returns 1 row
[ ] php artisan tinker --execute="echo config('modules.module-crm')" → 1
[ ] grep -c 'Cogneiss\\\\ModuleCrm' composer.json → ≥1
[ ] php artisan tinker --execute="echo class_exists(\Cogneiss\ModuleCrm\Providers\CrmModuleServiceProvider::class) ? 'yes' : 'no'" → yes
[ ] php artisan tinker --execute="echo count(\Illuminate\Database\Eloquent\Relations\Relation::morphMap())" → 8
[ ] curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/admin → 200 (after login)
[ ] curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/system → 200 (superadmin)
[ ] ls modules/module-crm/database/migrations/ → no scaffold files (crm_contacts, crm_deals, crm_pipelines, crm_activities removed)
```

⏸ HUMAN GATE: Review and approve before proceeding.

---

## Step 0b: Config & Seeders
> Depends on: Step 0a (module registered, extensions installed)

```
6. contact_origin enum
   Add CHECK constraint on contacts table: contact_origin IN ('property', 'saas_product')
   'saas_product' = platform leads (PIAB org), 'property' = buyer/renter leads (subscriber org)

7. Create PIAB organization (default org for shared/global data)
   Seed: Organization::create(['name' => 'PIAB', 'slug' => 'piab'])
   Store its ID → config/sync.php piab_organization_id / SYNC_PIAB_ORG_ID env

8. Sync config — create config/sync.php:
   return [
       'piab_organization_id' => env('SYNC_PIAB_ORG_ID'),
       'tiebreaker_source' => env('SYNC_TIEBREAKER', 'v3'),
       'batch_chunk_size' => env('SYNC_BATCH_CHUNK', 500),
       'poll_interval' => env('SYNC_POLL_INTERVAL', 60),
       'batch_interval' => env('SYNC_BATCH_INTERVAL', 1800),
   ];

9. Spatie Permission roles
   Configure: superadmin, admin, subscriber, agent, bdm, affiliate
   See: archive/rebuild-plan/00-roles-permissions.md

10. Lookup table seeders
    Create seeders for: sources (17), companies (976), developers (336),
    projecttypes (12), states (8), suburbs (15,299)
    Import from mysql_legacy. All must have organization_id (PIAB org for shared data).
    All org_id FKs: ON DELETE CASCADE

11. Stripe stub
    stripe/stripe-php already in composer.json
    Configure but stub (return success without charging)

12. Existing modules (don't touch):
    announcements, billing, blog, changelog, contact (form submissions),
    dashboards, gamification, help, page-builder, reports, workflows

13. Health + Pulse pre-configured (no action needed):
    Pulse at /pulse, Health at php artisan health:check
```

VERIFY:
```
[ ] php artisan migrate:fresh --seed → exits with code 0
[ ] curl -s -o /dev/null -w '%{http_code}' http://localhost:8000 → 200 (app loads)
[ ] curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/admin → 200
[ ] curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/system → 200 (superadmin only)
[ ] php artisan tinker --execute="echo \Spatie\Permission\Models\Role::count()" → 6 (superadmin, admin, subscriber, agent, bdm, affiliate)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Source::count()" → 17
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Company::count()" → 976
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Developer::count()" → 336
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Projecttype::count()" → 12
[ ] php artisan tinker --execute="echo \App\Models\State::count()" → 8
[ ] php artisan tinker --execute="echo \App\Models\Suburb::count()" → 15299
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Source::whereNull('organization_id')->count()" → 0 (all lookups have org_id)
[ ] php artisan tinker --execute="echo \App\Models\Organization::where('slug','piab')->exists() ? 'yes' : 'no'" → yes
[ ] php artisan tinker --execute="echo config('sync.piab_organization_id')" → matches PIAB org ID
[ ] curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/pulse → 200
[ ] php artisan health:check → exits with code 0
[ ] php artisan tinker --execute="echo DB::connection('mysql_legacy')->table('leads')->count()" → 9735
[ ] php artisan tinker --execute="echo config('modules.module-crm')" → 1
```

⏸ HUMAN GATE: Review and approve before proceeding.

---

## Step 1: Contacts
> Depends on: Step 0b (module exists, lookups seeded, PIAB org created)
>
> **Phase 1 (Builder Portal): SKIP ENTIRELY.** Contacts = subscriber CRM data (9,735 leads).
> Not needed for Builder Portal. Builders and agents are Users, not Contacts.
> Run this step in Phase 2 (Subscriber Migration).

```
CREATE (all in modules/module-crm/):

  1. Migration: database/migrations/create_contacts_table.php
     Schema: read newdb.md section 3.1 for full CREATE TABLE DDL
     Key columns: organization_id (FK CASCADE), legacy_lead_id (BIGINT UNIQUE),
     contact_origin (VARCHAR CHECK IN ('property','saas_product')),
     stage (VARCHAR, model-state), lead_score (INTEGER DEFAULT 0),
     synced_at (TIMESTAMPTZ), sync_source (VARCHAR(10)),
     extra_attributes (JSONB + GIN index),
     created_by (FK SET NULL), updated_by (FK SET NULL)
     Indexes: partial on (organization_id, stage) WHERE deleted_at IS NULL,
     GIN on extra_attributes, unique on legacy_lead_id

  2. Migration: create_contact_emails_table.php
     Schema: read newdb.md section 3.2
     Key columns: contact_id (FK CASCADE), organization_id (FK CASCADE),
     legacy_email_id (BIGINT UNIQUE), email (VARCHAR NOT NULL),
     is_primary (BOOLEAN DEFAULT false), synced_at, sync_source

  3. Migration: create_contact_phones_table.php
     Schema: read newdb.md section 3.3
     Key columns: contact_id (FK CASCADE), organization_id (FK CASCADE),
     legacy_phone_id (BIGINT UNIQUE), phone (VARCHAR NOT NULL),
     is_primary (BOOLEAN DEFAULT false), synced_at, sync_source

  4. Model: src/Models/Contact.php
     Namespace: Cogneiss\ModuleCrm\Models
     Traits:
       - App\Models\Concerns\BelongsToOrganization
       - Cogneiss\ModuleCrm\Sync\Traits\Syncable  <- implements SyncableContract
       - HasFactory, SoftDeletes, LogsActivity
       - askedio/laravel-soft-cascade -> softCascade = ['emails', 'phones']
       - spatie/laravel-model-states for 'stage'
       - spatie/laravel-schemaless-attributes for 'extra_attributes'
       - wildside/userstamps for created_by / updated_by
     Relationship traits in src/Traits/:
       HasCommunications (emails, phones)
       HasPropertyInteractions (enquiries, searches, reservations)
       HasSalesActivity (sales, commissions)

  5. Models: src/Models/ContactEmail.php, ContactPhone.php
     Both use: BelongsToOrganization, Syncable
     Phone validation: propaganistas/laravel-phone

  6. CRM UI (follow DataTable pattern from UI Pattern Reference above):
     a. DataTable: src/DataTables/ContactDataTable.php
        - extends AbstractDataTable, use HasAi, HasExport, HasImport, HasInlineEdit, HasSelectAll
        - tableColumns(): first_name, last_name, email (from primary email), phone, company,
          contact_origin, stage (option type with model-state values), lead_score, created_at
        - Filterable: stage, contact_origin, company_id, source_id
        - Searchable: first_name, last_name, email
     b. Controller: src/Http/Controllers/ContactController.php
        - index(): ContactDataTable::inertiaProps($request) → Inertia::render('crm/contacts/index')
        - show($contact): ContactDataTable::showProps($contact) → Inertia::render('crm/contacts/show')
          Include: emails, phones, notes, sales, reservations, enquiries (eager loaded)
        - create/store/edit/update: standard Inertia form handling
        - bulkSoftDelete, batchUpdate: DataTable bulk action endpoints
     c. Pages: resources/js/pages/crm/contacts/
        - index.tsx: <AppSidebarLayout> + <DataTable<ContactRow>> with tableName="contacts"
        - show.tsx: contact detail with tabs (overview, emails/phones, notes, sales history, activity)
        - create.tsx / edit.tsx: form with useForm(), fields for identity + address + contact_origin
     d. Types: resources/js/types/crm/contact.ts (ContactRow, ContactDetail interfaces)
     e. Routes: modules/module-crm/routes/web.php
        Route::prefix('crm')->group(fn() =>
          Route::resource('contacts', ContactController::class)
          Route::post('contacts/bulk-soft-delete', ...)
          Route::patch('contacts/batch-update', ...)
        )
     f. Run: php artisan wayfinder:generate (regenerate typed route helpers)

  7. Filament admin (lookups only):
     src/Filament/Resources/ -> SourceResource, CompanyResource,
     DeveloperResource, ProjecttypeResource

IMPORT (commands in src/Console/Commands/):
  Create: ImportContacts.php (register in CrmModuleServiceProvider)
  IMPORTANT: Import commands must bypass OrganizationScope.
  Use Contact::withoutGlobalScope(OrganizationScope::class) for reads,
  and explicitly set organization_id on every created record.

  a. Read mysql_legacy.leads (9,735 rows)
  b. For each lead, in DB::transaction():
     - Create contact (store lead.id as legacy_lead_id)
     - Set contact_origin: 'property' (default) or 'saas_product' (platform leads)
     - Set organization_id: resolve via subscriber relationship chain or PIAB org
     - Flatten addresses: mysql_legacy.addresses WHERE model_type='App\Models\Lead'
       street->address_line1, unit->address_line2, suburb->city, etc.
     - Stage: use leads.stage, fallback to LATEST statusable
     - Map is_partner -> type='partner'
     - Move important_note, summary_note -> extra_attributes JSONB
  c. Split contacts/detail (v3 polymorphic 'contacts' table):
     type='email_1' -> contact_emails (is_primary=true)
     type='email_2' -> contact_emails (is_primary=false)
     type='phone'   -> contact_phones (is_primary=true)
     type='website','facebook','linkedin',etc -> contacts.extra_attributes
     type='googleplus' -> DROP
  d. Build MAP: legacy_lead_id -> new contact_id (cache to file/Redis)
  e. IDEMPOTENT: skip if legacy_lead_id already exists

  Create: VerifyContacts.php
     Count: v3 leads = v4 contacts (9,735)
     Count: emails (9,730), phones (9,601)
     FK integrity, stages populated, addresses filled
     Verify: all contacts have organization_id
     Verify: all contacts have contact_origin
```

VERIFY:
```
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::count()" → 9735
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\ContactEmail::count()" → 9730
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\ContactPhone::count()" → 9601
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNotNull('extra_attributes')->where('extra_attributes','!=','{}')->count()" → >0 (social links stored)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNull('address_line1')->count()" → spot-check is low (addresses flattened)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNull('stage')->count()" → 0 (stages populated)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNull('legacy_lead_id')->count()" → 0 (MAP built)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNull('organization_id')->count()" → 0
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNull('contact_origin')->count()" → 0
[ ] CRM contact pages work: curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/crm/contacts → 200
[ ] Soft cascade test: php artisan tinker --execute="$c = \Cogneiss\ModuleCrm\Models\Contact::factory()->create(); $c->emails()->create(['email'=>'test@test.com','organization_id'=>$c->organization_id]); $c->delete(); echo \Cogneiss\ModuleCrm\Models\ContactEmail::withTrashed()->where('contact_id',$c->id)->whereNotNull('deleted_at')->count()" → 1
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::where('first_name','LIKE','%test%')->count()" → verifies search works (run after manual test)
```

⏸ HUMAN GATE: Review and approve before proceeding.

---

## Step 1.5: Sync Infrastructure
> Depends on: Step 1 (contacts exist for sync testing)
>
> **Phase 1 (Builder Portal): SLIM — only `mysql_legacy` connection + outbound push observers.**
> Skip bidirectional sync, pollers, field mappers, sync logs, conflict resolution.
> Only build: database connection config + `PackageV3PushObserver` that writes to v3 projects + lots.
> Full sync infrastructure runs in Phase 2 (Subscriber Migration).

> Full design: `sync-architecture.md`
> Built here, active from first deployment.

```
1. Verify mysql_legacy connection in config/database.php (from Pre-Flight)

2. Verify config/sync.php exists (from Step 0b)

3. Create sync_logs migration (in modules/module-crm/database/migrations/):
   Schema: read newdb.md section for sync_logs
   Key columns:
     id (BIGSERIAL PRIMARY KEY),
     entity_type (VARCHAR(50) NOT NULL),
     entity_id (BIGINT NOT NULL),
     direction (VARCHAR(10) NOT NULL — 'v3_to_v4' | 'v4_to_v3'),
     action (VARCHAR(10) NOT NULL — 'created'|'updated'|'skipped'|'conflict'|'failed'|'orphaned'),
     legacy_id (BIGINT),
     payload (JSONB),
     synced_at (TIMESTAMPTZ NOT NULL DEFAULT NOW()),
     error (TEXT)
   Indexes: (entity_type, entity_id), (synced_at)

4. Create SyncableContract interface + Syncable trait
   Path: src/Sync/Contracts/SyncableContract.php
   Path: src/Sync/Traits/Syncable.php
   Trait provides: $syncing flag, legacy ID accessor, sync logging

5. Create 8 field mappers (one per bidirectional entity):
   Path: src/Sync/FieldMappers/
     ContactFieldMapper.php      -- Contact ↔ Lead
     CompanyFieldMapper.php      -- Company ↔ Company
     ProjectFieldMapper.php      -- Project ↔ Project
     LotFieldMapper.php          -- Lot ↔ Lot
     SaleFieldMapper.php         -- Sale ↔ Sale
     ReservationFieldMapper.php  -- Reservation ↔ PropertyReservation
     EmailFieldMapper.php        -- ContactEmail ↔ contacts (polymorphic)
     PhoneFieldMapper.php        -- ContactPhone ↔ contacts (polymorphic)
   Each mapper: toV3(Model): array, toV4(array): array
   See sync-architecture.md §5 for exact column mappings.

6. Create 8 sync observers:
   Path: src/Sync/Observers/
     ContactSyncObserver, CompanySyncObserver, ProjectSyncObserver,
     LotSyncObserver, SaleSyncObserver, ReservationSyncObserver,
     EmailSyncObserver, PhoneSyncObserver
   Each listens to: created, updated, deleted (soft-delete)
   Each checks $model->syncing flag → skip if true → else dispatch SyncToLegacyJob

7. Create sync jobs:
   Path: src/Sync/Jobs/
     SyncToLegacyJob.php    -- dispatched by observers (v4→v3)
     SyncFromLegacyJob.php  -- dispatched by poller (v3→v4)
     SyncBatchJob.php       -- scheduled batch for one-way entities
   All jobs: queue = 'sync', retry 3x with backoff (30s, 120s, 480s)

8. Create sync commands:
   Path: src/Console/Commands/
     SyncPollCommand.php    -- polls v3 for changes (entities 1-8 sequentially)
     SyncBatchCommand.php   -- batch sync one-way entities (users, commissions, notes, etc.)
     SyncFullCommand.php    -- full sync (--direction=v3_to_v4|v4_to_v3|both)
     SyncStatusCommand.php  -- sync health report (lag, errors, row counts)

9. Register observers in CrmModuleServiceProvider::boot():
     Contact::observe(ContactSyncObserver::class);
     Company::observe(CompanySyncObserver::class);
     // ... all 8

10. Add scheduler entries in routes/console.php:
    Schedule::command('sync:poll')->everyMinute();
    Schedule::command('sync:batch')->everyThirtyMinutes();

11. Add v3 updated_at indexes (run manually on v3 MySQL):
    ALTER TABLE leads ADD INDEX idx_leads_updated_at (updated_at);
    ALTER TABLE companies ADD INDEX idx_companies_updated_at (updated_at);
    ALTER TABLE projects ADD INDEX idx_projects_updated_at (updated_at);
    ALTER TABLE lots ADD INDEX idx_lots_updated_at (updated_at);
    ALTER TABLE sales ADD INDEX idx_sales_updated_at (updated_at);
    ALTER TABLE property_reservations ADD INDEX idx_reservations_updated_at (updated_at);
    ALTER TABLE contacts ADD INDEX idx_contacts_updated_at (updated_at);
    (7 indexes — 'contacts' is v3 polymorphic table for emails+phones)

12. Start sync queue worker:
    php artisan queue:work --queue=sync

13. Create Filament SyncDashboard widget (System panel, superadmin only):
    Shows: sync lag per entity, error rate (24h), conflict count,
    last sync times, row count comparison v3 vs v4

14. Test v4→v3: create Contact in v4 → verify row appears in mysql_legacy.leads
15. Test v3→v4: update Lead in v3 → run sync:poll → verify change in v4
16. Test loop prevention: v4 save → syncs to v3 → poller picks up → does NOT re-sync back
```

VERIFY:
```
[ ] php artisan tinker --execute="echo \Illuminate\Support\Facades\Schema::hasTable('sync_logs') ? 'yes' : 'no'" → yes
[ ] php artisan tinker --execute="echo collect(\Illuminate\Support\Facades\Schema::getIndexes('sync_logs'))->count()" → ≥2 (entity_type+entity_id, synced_at)
[ ] php artisan test --filter=FieldMapper → 8 mapper tests pass (round-trip mapping)
[ ] php artisan tinker --execute="echo count(app(\Cogneiss\ModuleCrm\Providers\CrmModuleServiceProvider::class)->getObservers())" → verify all 8 observers registered (or grep CrmModuleServiceProvider for ::observe calls)
[ ] grep -c '::observe(' modules/module-crm/src/Providers/CrmModuleServiceProvider.php → ≥8
[ ] php artisan sync:poll → exits with code 0 (no errors)
[ ] php artisan sync:batch → exits with code 0 (no errors)
[ ] php artisan sync:status → shows healthy status, zero errors
[ ] v4→v3 test: php artisan tinker --execute="$c = \Cogneiss\ModuleCrm\Models\Contact::factory()->create(['first_name'=>'SyncTestV4']); echo $c->id" → then verify: php artisan tinker --execute="echo DB::connection('mysql_legacy')->table('leads')->where('first_name','SyncTestV4')->exists() ? 'yes' : 'no'" → yes
[ ] v3→v4 test: manually update a v3 lead, run php artisan sync:poll, verify change appears in v4
[ ] Loop prevention: php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\SyncLog::where('action','skipped')->count()" → >0 after bidirectional test (skipped = loop prevented)
[ ] php artisan queue:work --queue=sync --once → processes at least one job without error
[ ] SyncDashboard widget: curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/system → 200 (widget visible after login)
[ ] v3 indexes: mysql -e "SHOW INDEX FROM leads WHERE Key_name='idx_leads_updated_at'" fv3 → returns 1 row (repeat for all 7 tables)
```

⏸ HUMAN GATE: Review and approve before proceeding.

---

## Step 2: Users
> Depends on: Step 1 (contacts imported, legacy_lead_id MAP available)
>
> **Phase 1 (Builder Portal): SLIM — migrations + models + auth + self-signup only.**
> Skip v3 user import (1,502 users). Builders register fresh via self-signup.
> Self-signup creates User + Organization (type='developer') + subdomain.
> Full v3 user import runs in Phase 2.

```
DO:
  1. Migration: add contact_id FK to users table
     Schema: read newdb.md for users table additions
     Key columns: contact_id (FK SET NULL, nullable — links user to their contact record)
     $table->foreignId('contact_id')->nullable()
           ->constrained()->nullOnDelete();

IMPORT:
  Create: modules/module-crm/src/Console/Commands/ImportUsers.php

  a. Read mysql_legacy.users (1,502)
  b. Import: name, email, password, email_verified_at, timestamps
  c. Set users.contact_id using legacy_lead_id MAP
  d. Read mysql_legacy.model_has_roles (11,627)
     Map v3 role names -> Spatie Permission roles
  e. Create organization per subscriber-role user (org owner)
     Store legacy user→org mapping for org derivation in later steps
  f. Assign other users to org via organization_user
```

VERIFY:
```
[ ] php artisan tinker --execute="echo \App\Models\User::count()" → 1502
[ ] php artisan tinker --execute="echo \Spatie\Permission\Models\Role::all()->pluck('name')->sort()->implode(',')" → admin,affiliate,agent,bdm,subscriber,superadmin
[ ] php artisan tinker --execute="echo \App\Models\User::whereNotNull('contact_id')->count()" → >0 (contact_id linked where applicable)
[ ] php artisan tinker --execute="echo \App\Models\Organization::where('slug','!=','piab')->count()" → matches number of subscriber-role users (1 org per subscriber)
[ ] Login test: php artisan tinker --execute="echo \App\Models\User::first()->email" → then attempt login with that email + v3 password at /login
[ ] php artisan tinker --execute="echo \App\Models\User::role('superadmin')->count()" → ≥1 (impersonation source user exists)
```

⏸ HUMAN GATE: Review and approve before proceeding.

---

## Step 3: Projects & Lots
> Depends on: Step 2 (users exist, org per subscriber created, user→org mapping available)
>
> **Phase 1 (Builder Portal): SLIM — migrations + models only.**
> Skip v3 import (15K projects, 121K lots). Builders create new projects/lots in v4.
> Full v3 import runs in Phase 2. Import must handle merge with builder-created data.

```
CREATE (all in modules/module-crm/):

  Migrations (all with organization_id FK CASCADE, 1:1 FKs CASCADE):
    - projects
      Schema: read newdb.md section for projects CREATE TABLE DDL
      Key columns: organization_id (FK CASCADE), legacy_project_id (BIGINT UNIQUE),
      title, estate, stage (VARCHAR, model-state), developer_id (FK SET NULL),
      projecttype_id (FK SET NULL), synced_at (TIMESTAMPTZ), sync_source (VARCHAR(10)),
      extra_attributes (JSONB + GIN index), address columns (flattened),
      is_featured (BOOLEAN), is_hot_property (BOOLEAN)
    - project_specs (UNIQUE project_id FK CASCADE)
      Key columns: land_size, frontage, depth, land_info, floor_area
    - project_investment (UNIQUE project_id FK CASCADE)
      Key columns: rental_yield, vacancy_rate, capital_growth, median_price
    - project_features (UNIQUE project_id FK CASCADE)
      Key columns: feature flags (booleans), amenities (JSONB)
    - project_pricing (UNIQUE project_id FK CASCADE)
      Key columns: min_price (DECIMAL(12,2)), max_price (DECIMAL(12,2)), price_range_text
    - lots
      Schema: read newdb.md section for lots CREATE TABLE DDL
      Key columns: project_id (FK CASCADE), organization_id (FK CASCADE),
      legacy_lot_id (BIGINT UNIQUE), title, stage (VARCHAR, model-state),
      price (DECIMAL(12,2)), bedrooms (SMALLINT), bathrooms (SMALLINT),
      car_spaces (SMALLINT), land_size (DECIMAL(10,2)), storeys (SMALLINT),
      rent_weekly (DECIMAL(12,2)), synced_at, sync_source,
      49 columns total — see newdb_simple.md for full list
    - project_favorites (composite PK: user_id + project_id)
    - project_updates
      Key columns: project_id (FK CASCADE), title, body (TEXT), published_at
  See newdb_simple.md for exact columns per table.

  Models (all in src/Models/, namespace Cogneiss\ModuleCrm\Models):
    Project: BelongsToOrganization, Syncable, hasOne(specs, investment, features, pricing),
      hasMany(lots)
    Lot: BelongsToOrganization, Syncable, spatie/model-states for stage,
      akaunting/money for prices, SoftDeletes
    ProjectSpec, ProjectInvestment, ProjectFeature, ProjectPricing:
      BelongsToOrganization

  Visibility rules:
    - project.organization_id = PIAB org → visible to ALL orgs (shared marketplace)
    - project.organization_id = subscriber org → scoped to that org only

  CRM UI (follow DataTable pattern):
    a. DataTables: ProjectDataTable.php, LotDataTable.php
       - Project columns: title, estate, stage, developer, min_price, max_price, total_lots, is_featured
       - Lot columns: title, project (relation), stage, price, bedrooms, bathrooms, land_size
       - Project filterable: stage, developer_id, projecttype_id, organization_id (for shared vs own)
       - Lot filterable: stage, project_id, price range, bedrooms
    b. Controllers: ProjectController.php, LotController.php
       - Project show: tabs (overview, specs, investment, features, pricing, lots list, updates)
       - Lot show: detail + reservation history + sales history
    c. Pages: resources/js/pages/crm/projects/ and crm/lots/
       - projects/index.tsx, show.tsx, create.tsx, edit.tsx
       - lots/index.tsx, show.tsx, create.tsx, edit.tsx
       - Shared listings: index page shows PIAB + own org projects together with org badge
    d. Routes: /crm/projects (CRUD + bulk), /crm/lots (CRUD + bulk)

  Register sync observers in CrmModuleServiceProvider:
    Project::observe(ProjectSyncObserver::class);
    Lot::observe(LotSyncObserver::class);

IMPORT:
  Create: ImportProjects.php in src/Console/Commands/
    a. Read mysql_legacy.projects (15,481 rows)
    b. In DB::transaction() per project:
       - Create projects row (core cols + address flattened from mysql_legacy.addresses)
       - Set organization_id = PIAB org (all v3 projects are shared)
       - Store legacy_project_id
       - Create project_specs, project_investment, project_features, project_pricing
       - Move is_featured, is_hot_property, land_info, etc -> extra_attributes
       - Stage from projects.stage + statusables fallback

  Create: ImportLots.php
    121,360 rows. Batch 1000. organization_id inherited from project.
    Store legacy_lot_id.
    Renames: car->car_spaces, storyes->storeys, weekly_rent->rent_weekly, etc.
    Stage from lots.stage + statusables fallback. Money cast all prices.

  Create: ImportProjectUpdates.php
    155,273 rows. Direct copy. Batch.

  Create: ImportFavorites.php
    love_reactions (2,172) -> project_favorites (user_id, project_id)
```

VERIFY:
```
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Project::count()" → 15481
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Project::whereNull('organization_id')->count()" → 0
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Project::whereNull('legacy_project_id')->count()" → 0
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Project::has('specs')->count()" → 15481 (each has specs)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Project::has('investment')->count()" → 15481
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Project::has('features')->count()" → 15481
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Project::has('pricing')->count()" → 15481
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Lot::count()" → 121360
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Lot::whereNull('organization_id')->count()" → 0
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Lot::whereNull('legacy_lot_id')->count()" → 0
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Lot::whereNull('stage')->count()" → 0
[ ] php artisan tinker --execute="echo DB::table('project_updates')->count()" → 155273
[ ] php artisan tinker --execute="echo DB::table('project_favorites')->count()" → 2172
[ ] Project decomposition: php artisan tinker --execute="$p = \Cogneiss\ModuleCrm\Models\Project::first(); echo $p->specs()->exists() && $p->investment()->exists() && $p->features()->exists() && $p->pricing()->exists() ? 'yes' : 'no'" → yes
[ ] Lot stage transitions: php artisan tinker --execute="use Cogneiss\ModuleCrm\Models\Lot; $l = Lot::where('stage','available')->first(); echo $l ? 'available lot found' : 'none'" → available lot found
[ ] Money fields: php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Lot::whereNotNull('price')->where('price','>',0)->count()" → >0
[ ] grep -c 'SyncObserver' modules/module-crm/src/Providers/CrmModuleServiceProvider.php → includes ProjectSyncObserver + LotSyncObserver
[ ] Sync test: php artisan tinker --execute="$p = \Cogneiss\ModuleCrm\Models\Project::factory()->create(['title'=>'SyncTestProject']); echo $p->id" → then verify in v3
[ ] CRM project pages: curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/crm/projects → 200
[ ] CRM lot pages: curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/crm/lots → 200
[ ] Shared listings: php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Project::where('organization_id', config('sync.piab_organization_id'))->count()" → 15481 (all v3 projects are PIAB org)
```

⏸ HUMAN GATE: Review and approve before proceeding.

---

## Step 4: Sales & Transactions
> Depends on: Step 3 (projects + lots exist, lot stage transitions work)
>
> **Phase 1 (Builder Portal): SLIM — migrations + models only.**
> Skip v3 import (447 sales, 120 reservations, 721 enquiries, 316 searches).
> My Deals (PRD 12 US-007) uses these tables for new sales created in v4.
> Full v3 import runs in Phase 2.

```
CREATE (all in modules/module-crm/):

  Migrations (all with organization_id FK CASCADE, contact FKs SET NULL):
    - sales
      Schema: read newdb.md section for sales CREATE TABLE DDL
      Key columns: organization_id (FK CASCADE), lot_id (FK SET NULL),
      project_id (FK SET NULL), legacy_sale_id (BIGINT UNIQUE),
      7 contact FK columns (primary_contact_id, secondary_contact_id, solicitor_contact_id,
      accountant_contact_id, financial_planner_contact_id, referrer_contact_id, affiliate_contact_id — all FK SET NULL),
      status (VARCHAR, model-state), purchase_price (DECIMAL(12,2)),
      synced_at (TIMESTAMPTZ), sync_source (VARCHAR(10)),
      extra_attributes (JSONB + GIN index)
    - commissions
      Key columns: sale_id (FK CASCADE), type (VARCHAR CHECK), amount (DECIMAL(12,2)),
      UNIQUE constraint on (sale_id, type)
    - commission_tiers
      Key columns: organization_id (FK CASCADE), volume_threshold (INTEGER),
      commission_rate (DECIMAL(5,4))
    - property_reservations
      Schema: read newdb.md section for property_reservations
      Key columns: primary_contact_id (FK SET NULL), lot_id (FK SET NULL),
      legacy_reservation_id (BIGINT UNIQUE), all trust/finance fields,
      status (VARCHAR, model-state), synced_at, sync_source
    - property_enquiries
      Key columns: contact_id (FK SET NULL), project_id (FK SET NULL),
      extra_attributes (JSONB + GIN index — stores 10 detail fields)
    - property_searches
      Key columns: contact_id (FK SET NULL),
      search_criteria (JSONB + GIN index — stores 12 criteria fields)
  See newdb_simple.md for exact columns.

  Models (src/Models/): Sale, Commission, CommissionTier,
    PropertyReservation, PropertyEnquiry, PropertySearch
    All: BelongsToOrganization
    Sale, PropertyReservation: Syncable
  All status/stage columns: spatie/model-states
  Use DB::transaction() + lockForUpdate() for lot stage changes

  IMPORTANT: Sale.created_by in v3 → leads.id (NOT users.id).
  Field mapper resolves: leads.id → users.lead_id → users.id

  Register sync observers in CrmModuleServiceProvider:
    Sale::observe(SaleSyncObserver::class);
    PropertyReservation::observe(ReservationSyncObserver::class);

  Commission flow (launch feature):
    - commission_tiers: volume threshold → rate, per organization
    - Calculated on sale creation based on subscriber's trailing sales count

  CRM UI (follow DataTable pattern):
    a. DataTables: SaleDataTable.php, ReservationDataTable.php
       - Sale columns: client (contact), lot, project, status, purchase_price, commissions total, created_at
       - Reservation columns: primary_contact, lot, project, purchase_price, status, created_at
       - Sale filterable: status, project_id, lot_id, organization_id
    b. Controllers: SaleController.php, ReservationController.php
       - Sale show: tabs (overview, commissions breakdown, documents, notes)
       - Reservation show: purchaser details, trust/finance fields, contract status
    c. Pages: resources/js/pages/crm/sales/ and crm/reservations/
       - Sale create: select contact + lot → auto-fill project/developer, commission tier calc
       - Commission breakdown visible on sale detail
    d. Routes: /crm/sales (CRUD + bulk), /crm/reservations (CRUD + bulk)

IMPORT:
  Create: ImportSales.php (in src/Console/Commands/)
    a. Read mysql_legacy.sales (447)
    b. Set organization_id via subscriber_id → Lead → User → Org
    c. Store legacy_sale_id
    d. Remap 7 contact FKs using legacy_lead_id MAP
    e. Normalize commissions:
       For each of 7 commission columns (piab_comm, affiliate_comm, etc.):
         if value > 0: INSERT commissions (sale_id, type, amount)
    f. Move to extra_attributes: divide_percent, expected_commissions, etc.
    g. Status from statusables LATEST

  Create: ImportReservations.php
    120 rows. Store legacy_reservation_id.
    Remap: purchaser1_id -> primary_contact_id, etc.

  Create: ImportEnquiries.php
    721 rows. Move 10 detail fields -> extra_attributes JSONB

  Create: ImportSearches.php
    316 rows. Move 12 criteria fields -> search_criteria JSONB
```

VERIFY:
```
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Sale::count()" → 447
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Sale::whereNull('organization_id')->count()" → 0
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Sale::whereNull('legacy_sale_id')->count()" → 0
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Sale::whereDoesntHave('primaryContact')->count()" → spot-check low (7 contact FKs valid)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Commission::count()" → equals count of non-zero commission cols in v3
[ ] php artisan tinker --execute="echo DB::select(\"SELECT COUNT(*) as c FROM commissions c1 JOIN commissions c2 ON c1.sale_id=c2.sale_id AND c1.type=c2.type AND c1.id!=c2.id\")[0]->c" → 0 (UNIQUE prevents dupes)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\CommissionTier::count()" → >0 (default rates seeded)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\PropertyReservation::count()" → 120
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\PropertyEnquiry::count()" → 721
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\PropertySearch::count()" → 316
[ ] Spot-check 10 random: php artisan tinker --execute="\Cogneiss\ModuleCrm\Models\Sale::inRandomOrder()->take(10)->get()->each(fn(\$s) => print \$s->legacy_sale_id.' org:'.\$s->organization_id.' contacts:'.collect(['primary_contact_id','secondary_contact_id'])->map(fn(\$f)=>\$s->\$f)->filter()->count().PHP_EOL)" → all have org_id, most have ≥1 contact FK
[ ] Sale status transitions: php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Sale::first()?->status" → returns a valid state
[ ] grep -c 'SyncObserver' modules/module-crm/src/Providers/CrmModuleServiceProvider.php → includes SaleSyncObserver + ReservationSyncObserver
```

⏸ HUMAN GATE: Review and approve before proceeding.

---

## Step 5: Everything Else
> Depends on: Step 4 (sales exist for note/comment morph targets, contacts + projects for relationship remapping)
>
> **Phase 1 (Builder Portal): SKIP ENTIRELY.** Notes, partners, relationships, tasks, comments,
> media import — all subscriber CRM data. Run in Phase 2.

```
IMPORT (in order, all commands in modules/module-crm/src/Console/Commands/):

  1. Notes (22,681)
     Update morph: App\Models\Lead -> 'contact'
     Remap noteable_id for Lead notes using legacy MAP
     Set organization_id via author_id → User → Org

  2. Comments (4,259) -> starter kit crm_comments
     Update morph types. Direct column copy.
     Set organization_id.

  3. Partners (299)
     lead_id -> contact_id, parent_id -> parent_partner_id,
     relationship -> partner_type
     Set organization_id.

  4. Relationships (7,378)
     account_id -> account_contact_id, relation_id -> relation_contact_id
     Remap both via legacy MAP. Set organization_id.

  5. Tasks (57)
     assigned_id -> assigned_to_user_id,
     attached_id -> attached_contact_id (REMAP),
     start_at -> due_at, end_at -> DROP
     Set organization_id.

  6. Media (108,510)
     Update model_type to morph map aliases. No column changes.

  7. Lookups (all + org_id CASCADE + UNIQUE):
     sources: rename label -> name
     companies: rename extra_company_info -> extra_attributes,
       add legacy_company_id + synced_at + sync_source
     developers: rename modified_by -> updated_by, DROP extra_attributes (0%)
     projecttypes, states (no org_id), suburbs (DROP au_town_id)

     Register CompanySyncObserver in CrmModuleServiceProvider:
       Company::observe(CompanySyncObserver::class);

  8. Resources:
      resources (138): + org_id, rename modified_by -> updated_by
      resource_categories (5): direct
      resource_groups (9): + org_id

SKIP:
  activity_log (461K) -> fresh in v4
  login_histories (42K) -> starter kit login_events
  column_management (4.5K) -> starter kit data_table_saved_views
  user_api_tokens/ips -> Sanctum
  WordPress tables -> not moving
  services/company_service -> 5 years stale
```

VERIFY:
```
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Note::count()" → 22681
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Note::where('noteable_type','App\\\Models\\\Lead')->count()" → 0 (morph types updated, none still use old class)
[ ] php artisan tinker --execute="echo DB::table('crm_comments')->count()" → 4259
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Relationship::count()" → 7378
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Relationship::whereNull('account_contact_id')->count()" → 0 (both FKs remapped)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Partner::count()" → 299
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Task::count()" → 57
[ ] php artisan tinker --execute="echo DB::table('media')->count()" → 108510
[ ] php artisan tinker --execute="echo DB::table('media')->where('model_type','LIKE','App\\\Models\\\%')->count()" → 0 (morph types updated to aliases)
[ ] php artisan tinker --execute="echo DB::table('resources')->count()" → 138
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Company::whereNotNull('legacy_company_id')->count()" → 976 (all companies have legacy ID)
[ ] grep -c 'CompanySyncObserver' modules/module-crm/src/Providers/CrmModuleServiceProvider.php → ≥1
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Note::whereNull('organization_id')->count()" → 0 (all imported records have org_id)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Partner::whereNull('organization_id')->count()" → 0
[ ] Contact detail page: curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/crm/contacts/1 → 200 (shows notes, emails, phones, tasks, relationships)
```

⏸ HUMAN GATE: Review and approve before proceeding.

---

## Step 6: AI & Verification
> Depends on: Step 5 (all CRM data imported, all morph types updated, all relationships remapped)
>
> **Phase 1 (Builder Portal): SKIP ENTIRELY.** AI embeddings, credits, verification commands.
> Run in Phase 2 after subscriber data is imported.

```
DO:
  v3 "Bot in a Box" -> Built-in laravel/ai agents (v4)
  v3 had: external bot service (botinabox.online) + separate DB
  v4 has: everything built-in via laravel/ai (wraps prism-php/prism)

  Starter kit already provides:
    app/Ai/Agents/AssistantAgent.php   <- laravel/ai agent with tools + memory
    app/Ai/Tools/UsersIndex.php        <- example tool
    app/Mcp/Servers/ApiServer.php      <- MCP endpoint at /mcp/api
    app/Mcp/Tools/                     <- MCP tools for external agents
    resources/js/pages/chat/           <- React chat UI (already built)
    agent_conversations table          <- conversation history (pre-built)
    credits + credit_packs tables      <- AI usage tracking (pre-built)
    config/ai.php                      <- multi-provider config

  1. Add CRM-specific AI tools (modules/module-crm/src/Ai/Tools/):
     ContactsSearch.php   -> search contacts by name, email, stage, location
     ContactsShow.php     -> get contact with emails, phones, notes, tasks
     ProjectsSearch.php   -> search projects by location, type, price range
     LotsAvailable.php    -> find available lots for a project
     SalesPipeline.php    -> get sales pipeline status + totals
     CommissionCalc.php   -> calculate expected commissions for a sale

  2. Add CRM MCP tools (app/Mcp/Tools/) for external agent access:
     Same tools as above, exposed via MCP protocol at /mcp/api

  3. Convert v3 bot data (knowledge extraction, NO table migration):
     ai_bot_categories (11) -> agent persona categories in code
     ai_bot_boxes (46) -> review, convert relevant ones to agent system prompts
     ai_bot_prompt_commands (481) -> export useful prompts, convert to AI tools
     ELIMINATED: botmysql connection, SyncUsers event, external bot service

  4. pgvector embeddings
     model_embeddings table + HasEmbeddings trait (starter kit pre-built)
     Generate embeddings for 9,735 contacts
     Enables: "find contacts similar to X", semantic search

  5. AI credit tracking
     credits + credit_packs (starter kit pre-built)
     Rate limit with spatie/laravel-rate-limited-job-middleware
     Config via Filament: Settings > AI (per-org overridable)

  6. Durable workflows
     laravel-workflow already installed + waterline UI
     Import pipelines, AI-triggered automation

  7. Full verification (run verify commands for ALL steps)
```

VERIFY:
```
[ ] Chat UI test: curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/chat → 200
[ ] AI tool count: php artisan tinker --execute="echo count(glob(base_path('modules/module-crm/src/Ai/Tools/*.php')))" → 6
[ ] MCP endpoint: curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/mcp/api → 200
[ ] Embeddings: php artisan tinker --execute="echo DB::table('model_embeddings')->where('embeddable_type','contact')->count()" → 9735
[ ] Credits tracking: php artisan tinker --execute="echo \Illuminate\Support\Facades\Schema::hasTable('credits') && \Illuminate\Support\Facades\Schema::hasTable('credit_packs') ? 'yes' : 'no'" → yes
[ ] No external bot dependency: grep -r 'botinabox' modules/module-crm/ → 0 matches
[ ] php artisan tinker --execute="echo DB::connection('mysql_legacy')->getDatabaseName()" → should NOT contain 'botmysql' anywhere in config
[ ] ALL prior import verify commands pass (run VerifyContacts, etc.)
```

⏸ HUMAN GATE: Review and approve before proceeding.

---

## Step 7: Reporting & Dashboards
> Depends on: Step 6 (AI tools work, all CRM data verified, embeddings generated)
>
> **Phase 1 (Builder Portal): SKIP ENTIRELY.** Subscriber dashboards and reports.
> Run in Phase 2 after subscriber migration.

```
DO:
  1. Dashboards (starter kit modules/dashboards/ with Puck builder)
     DashboardBuilderController + DashboardDataSourceRegistry exist
     Add CRM data sources: contact pipeline, sales, lots, commissions
     Puck renders dashboard blocks

  2. Performance dashboard widgets (Filament, per-org):
     - Sales count (trailing 30/90/365 days)
     - Conversion rate (contacts → sales)
     - Commission earned (total + by tier)
     - Active leads count
     - Lot availability summary

  3. Reports (starter kit modules/reports/ with Puck builder)
     Add CRM report types
     Export: maatwebsite/excel (installed)

  4. Shared listings marketplace view
     PIAB org projects visible in every subscriber's dashboard
     Query: WHERE organization_id = PIAB_ORG OR organization_id = current_org
     Any subscriber can sell lots from shared projects

  5. Replace v3 raw SQL (dbissues.md Section 8)
     Geographic -> earthdistance extension
     Aggregates -> Eloquent with parameter binding
     Case-insensitive -> ILIKE
```

VERIFY:
```
[ ] Dashboard loads: curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/dashboard → 200
[ ] Widgets data: php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Sale::where('created_at','>=',now()->subDays(365))->count()" → >0 (data available for widgets)
[ ] Shared listings query: php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Project::where('organization_id', config('sync.piab_organization_id'))->orWhere('organization_id', \App\Models\Organization::where('slug','!=','piab')->first()?->id)->count()" → ≥15481
[ ] Reports page: curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/reports → 200
[ ] Export test: php artisan tinker --execute="echo class_exists(\Maatwebsite\Excel\Facades\Excel::class) ? 'yes' : 'no'" → yes
[ ] Zero raw SQL: grep -r 'DB::raw' modules/module-crm/src/ → 0 matches
[ ] Zero raw SQL (double check): grep -rn 'DB::raw\|DB::select.*raw\|selectRaw\|whereRaw\|orderByRaw' modules/module-crm/src/ → 0 matches
```

⏸ HUMAN GATE: Review and approve before proceeding.

---

## Step 8: Builder Portal & Agent Portal (PHASE 1 — SHIP FIRST)
> Depends on: Steps 0a, 0b (full) + Steps 2, 3, 4 (slim — models only, no v3 import) + Step 1.5-slim (v3 push)
> **Priority: #1 strategic initiative** — see `v4/specs/kenekt-competitive-feature-spec.md`
> **PRD:** `12-property-builder.md` (rewritten 2026-03-25 with 20 user stories, 3 tiers)
> **Design:** `v4/specs/2026-03-25-builder-portal-fast-track-design.md`

**Why first:** PIAB competes directly with Kenekt ($799-990/mo stock management). Builder Portal offered FREE to capture supply at the source. Speed > perfection. v3 subscribers see builder stock automatically via v3 push (enriched projects + lots). Zero v3 code changes.

**Self-signup:** Builders register at `/register` → creates User + Organization (type='developer') + subdomain. Starter kit auth, not custom code.

**v3 Push:** `PackageV3PushObserver` writes to v3 MySQL on package create/update/delete. Pushes as projects + lots with enriched descriptions (design name, bedrooms, facade, price breakdown). Subscribers see it as normal project/lot listings.

**Tier 1 (MUST SHIP — Kenekt parity):**
1. Construction Library — designs, facades, inclusions, commission templates (US-001)
2. Package creation engine — lot + library = sellable product (US-002)
3. Distribution & allocation control — open vs exclusive per agent (US-003)
4. Builder Workbench dashboard — stock management UI (US-004)
5. Bulk stock import — CSV/Excel/URL with validation (US-005)
6. Agent Portal — browse, search, filter, export, my lists, documents, enquiry (US-006)
7. Commission tracking by construction stage (US-007)
8. Document cascade + white-labelling (US-008)

**Tier 2 (SHOULD SHIP — differentiators):**
9. Dynamic brochure generation (US-009)
10. API / data syndication (US-010)
11. Property match intelligence (US-011)
12. Saved searches + email alerts (US-012)
13. Package newsletters (US-013)
14. Builder CRM pipeline (US-014)

**New tables (12):** construction_designs, construction_facades, construction_inclusions, construction_inclusion_items, commission_templates, packages, package_inclusions, package_distributions, agent_favorites, agent_saved_searches, sale_commission_payments, enquiries, stock_imports

⏸ HUMAN GATE: Review Tier 1 scope before proceeding. Tier 2 can follow immediately.

---

---

## Phase 2: Subscriber Migration (after Builder Portal launched)
> Depends on: Step 8 (Builder Portal live, builders onboarded)
> Run the FULL versions of Steps 1-7 (the parts skipped in Phase 1)

| Order | Step | What | Notes |
| --- | --- | --- | --- |
| 1 | Step 1 | Contacts — full import (9,735 contacts) | Polymorphic splits, stage mapping, address flattening |
| 2 | Step 1.5 | Full sync infrastructure | Bidirectional sync, pollers, field mappers, conflict resolution |
| 3 | Step 2-import | Users — import 1,502 v3 users | Map to orgs, contact_id linking |
| 4 | Step 3-import | Projects & Lots — import 15K projects + 121K lots | **Must merge with builder-created data** (skip duplicates) |
| 5 | Step 4-import | Sales — import 447 sales + 120 reservations | Commission normalization |
| 6 | Step 5 | Notes, partners, relationships, tasks, media | Full |
| 7 | Step 6 | AI embeddings, credits, verification | Full |
| 8 | Step 7 | Reporting, dashboards | Full |
| 9 | Cutover | Subscribers switch from v3 to v4 | v3 push observers removed, v3 decommissioned |

⏸ HUMAN GATE: Review Phase 2 scope before proceeding.

---

## Phase 3: Advanced Features (after subscribers migrated)
> Depends on: Phase 2 complete (all subscriber data in v4)

### Phase 3a: Builder Portal Tier 2-3

| Feature | PRD 12 Story | Priority |
| --- | --- | --- |
| Dynamic brochure generation | US-009 | High |
| API / data syndication | US-010 | High |
| Property match intelligence | US-011 | Medium |
| Saved searches + email alerts | US-012 | High |
| Package newsletters | US-013 | Medium |
| Builder CRM pipeline | US-014 | Medium |
| Auto-validation & MLS | US-015 | Low |
| Listing versioning | US-016 | Low |
| AI push suggestions | US-017 | Low |
| Compliance (FIRB/NDIS) | US-018 | Low |
| WordPress plugin | US-019 | Low |
| Advanced analytics | US-020 | Medium |

### Phase 3b: Platform Features

| Step | Feature | Key Package | Spec |
| --- | --- | --- | --- |
| 9 | Search + bulk ops | `laravel/scout` + `typesense` | `14-step-8-phase-1-parity.md` |
| 10 | AI lead gen | `laravel/ai` | `15-step-9-*.md` |
| 11 | AI core + voice | `laravel/ai` + `prism-php/relay` | `16-step-10-*.md` |
| 12 | CRM analytics | `panphp/pan` | `18-step-12-*.md` |
| 13 | Marketing | `thomasjohnkane/snooze`, email_campaigns | `19-step-13-*.md` |
| 14 | Deal tracker | deals, deal_documents | `20-step-14-*.md` |
| 15 | Websites API (NOT WordPress) | `dedoc/scramble` | `21-step-15-*.md` |
| 16 | Xero | `saloonphp/saloon`, `spatie/webhook-*` | `22-step-16-*.md` |
| 17 | Signup + onboarding | OnboardingServiceProvider, `stripe` | `23-step-17-*.md` |
| 18 | R&D features | custom_fields, automation_rules | `24-step-18-*.md` |

⏸ HUMAN GATE: Review Phase 3 scope before proceeding to any step.

---

## Sync Architecture

> **Phase 1:** Outbound push only (`PackageV3PushObserver` → v3 projects + lots).
> **Phase 2:** Full bidirectional sync (Step 1.5). See `sync-architecture.md`.
> Cutover plan: `sync-architecture.md` §10

---

## Deferred Features (Build Later)

1. **Flyers, Websites, and Marketing Email** — flyers, campaign_websites, websites, mail_lists, brochure_mail_jobs, email_settings and related tables will be rebuilt separately in v4 — not migrated from v3.
2. **Lead Routing** — auto-assign property leads to best-fit subscriber based on geo, capacity, performance
3. **Automated AI Follow-ups** — AI sequences using laravel/ai + workflows
4. **Co-selling** — cross-org property sharing with split commissions

See spec §3.7 for details.

---

## Verification Checklist (After EVERY Step)

```
=== DATA ===
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::count()" → matches expected count for step
[ ] php artisan tinker --execute="echo DB::select(\"SELECT COUNT(*) as c FROM contacts c LEFT JOIN organizations o ON c.organization_id=o.id WHERE o.id IS NULL\")[0]->c" → 0 (no orphaned FKs)
[ ] php artisan tinker --execute="echo collect(['contacts','sales','lots','projects'])->sum(fn(\$t) => DB::table(\$t)->whereNull('organization_id')->count())" → 0 (no NULL in NOT NULL org columns)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNull('legacy_lead_id')->count()" → 0 (legacy IDs populated)
[ ] php artisan tinker --execute="echo DB::table('media')->where('model_type','LIKE','App\\\Models\\\%')->count()" → 0 (morph types use aliases)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::onlyTrashed()->count()" → ≥0 (soft-deleted records preserved)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNull('organization_id')->count()" → 0

=== SCHEMA ===
[ ] php artisan tinker --execute="echo collect(DB::select(\"SELECT conname FROM pg_constraint WHERE contype='f'\"))->count()" → >0 (FKs exist)
[ ] php artisan tinker --execute="echo DB::select(\"SELECT COUNT(*) as c FROM information_schema.columns WHERE table_name='contacts' AND column_name='organization_id'\")[0]->c" → 1
[ ] php artisan tinker --execute="echo DB::select(\"SELECT COUNT(*) as c FROM pg_indexes WHERE tablename='contacts'\")[0]->c" → ≥3 (PK + org_id + legacy_lead_id + partial + GIN)
[ ] php artisan tinker --execute="echo DB::select(\"SELECT data_type FROM information_schema.columns WHERE table_name='sales' AND column_name='purchase_price'\")[0]->data_type" → numeric (DECIMAL)
[ ] php artisan tinker --execute="echo DB::select(\"SELECT COUNT(*) as c FROM pg_constraint WHERE contype='c' AND conname LIKE '%contact_origin%'\")[0]->c" → 1 (CHECK constraint)

=== CODE ===
[ ] grep -r 'DB::raw' modules/module-crm/src/ | wc -l → 0
[ ] CRM pages: curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/crm/contacts → 200
[ ] Filament admin: curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/admin → 200

=== SYNC ===
[ ] grep -c '::observe(' modules/module-crm/src/Providers/CrmModuleServiceProvider.php → ≥8 (for bidirectional entities)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNull('legacy_lead_id')->count()" → 0 (legacy IDs on syncable records)
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNotNull('synced_at')->count()" → >0 after sync runs
[ ] php artisan sync:status → shows healthy status
[ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\SyncLog::where('action','failed')->count()" → 0 (no failed syncs)

=== PER STEP ===

Step 0a:
  [ ] php artisan tinker --execute="echo count(DB::select(\"SELECT extname FROM pg_extension WHERE extname IN ('vector','cube','earthdistance')\"))" → 3
  [ ] php artisan tinker --execute="echo config('modules.module-crm')" → 1
  [ ] php artisan tinker --execute="echo count(\Illuminate\Database\Eloquent\Relations\Relation::morphMap())" → 8

Step 0b:
  [ ] php artisan migrate:fresh --seed → exits with code 0
  [ ] curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/admin → 200
  [ ] curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/system → 200
  [ ] curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/pulse → 200
  [ ] php artisan health:check → exits with code 0
  [ ] php artisan tinker --execute="echo DB::connection('mysql_legacy')->table('leads')->count()" → 9735
  [ ] php artisan tinker --execute="echo \App\Models\Organization::where('slug','piab')->exists() ? 'yes' : 'no'" → yes
  [ ] php artisan tinker --execute="echo config('modules.module-crm')" → 1

Step 1:
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::count()" → 9735
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\ContactEmail::count()" → 9730
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\ContactPhone::count()" → 9601
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNull('stage')->count()" → 0
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNull('contact_origin')->count()" → 0
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNotNull('extra_attributes')->count()" → >0 (social links)
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNull('legacy_lead_id')->count()" → 0 (MAP built)
  [ ] curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/crm/contacts → 200

Step 1.5:
  [ ] grep -c '::observe(' modules/module-crm/src/Providers/CrmModuleServiceProvider.php → ≥8
  [ ] php artisan test --filter=FieldMapper → all pass
  [ ] php artisan sync:status → healthy
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\SyncLog::where('action','failed')->count()" → 0

Step 2:
  [ ] php artisan tinker --execute="echo \App\Models\User::count()" → 1502
  [ ] php artisan tinker --execute="echo \App\Models\User::whereNotNull('contact_id')->count()" → >0
  [ ] php artisan tinker --execute="echo \App\Models\Organization::where('slug','!=','piab')->count()" → matches subscriber count

Step 3:
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Project::count()" → 15481
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Project::has('specs')->has('investment')->has('features')->has('pricing')->count()" → 15481
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Lot::count()" → 121360
  [ ] php artisan tinker --execute="echo DB::table('project_updates')->count()" → 155273
  [ ] php artisan tinker --execute="echo DB::table('project_favorites')->count()" → 2172
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Lot::whereNotNull('price')->where('price','>',0)->count()" → >0 (money displays correctly)

Step 4:
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Sale::count()" → 447
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Commission::count()" → >0
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\CommissionTier::count()" → >0
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\PropertyReservation::count()" → 120
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\PropertyEnquiry::count()" → 721
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\PropertySearch::count()" → 316

Step 5:
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Note::count()" → 22681
  [ ] php artisan tinker --execute="echo DB::table('crm_comments')->count()" → 4259
  [ ] php artisan tinker --execute="echo DB::table('media')->count()" → 108510
  [ ] php artisan tinker --execute="echo DB::table('media')->where('model_type','LIKE','App\\\Models\\\%')->count()" → 0 (morph updated)
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Relationship::count()" → 7378
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Partner::count()" → 299
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Task::count()" → 57
  [ ] grep -c 'CompanySyncObserver' modules/module-crm/src/Providers/CrmModuleServiceProvider.php → ≥1

Step 6:
  [ ] curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/chat → 200
  [ ] php artisan tinker --execute="echo DB::table('model_embeddings')->where('embeddable_type','contact')->count()" → 9735
  [ ] php artisan tinker --execute="echo \Illuminate\Support\Facades\Schema::hasTable('credits') ? 'yes' : 'no'" → yes
  [ ] php artisan crm:verify-all → all checks pass

Step 7:
  [ ] curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/dashboard → 200
  [ ] curl -s -o /dev/null -w '%{http_code}' http://localhost:8000/reports → 200
  [ ] grep -r 'DB::raw' modules/module-crm/src/ | wc -l → 0
  [ ] php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Project::where('organization_id', config('sync.piab_organization_id'))->count()" → 15481 (shared listings)

=== FINAL (after all steps) ===
  [ ] php artisan tinker --execute="echo implode(' ', ['contacts='.\Cogneiss\ModuleCrm\Models\Contact::count(), 'lots='.\Cogneiss\ModuleCrm\Models\Lot::count(), 'projects='.\Cogneiss\ModuleCrm\Models\Project::count(), 'sales='.\Cogneiss\ModuleCrm\Models\Sale::count(), 'media='.DB::table('media')->count()])" → contacts=9735 lots=121360 projects=15481 sales=447 media=108510
  [ ] php artisan tinker --execute="echo DB::select(\"SELECT COUNT(*) as c FROM contacts c LEFT JOIN organizations o ON c.organization_id=o.id WHERE o.id IS NULL\")[0]->c" → 0 (zero orphaned FKs)
  [ ] php artisan tinker --execute="echo DB::table('media')->where('model_type','LIKE','App\\\Models\\\%')->count()" → 0 (zero old morph types)
  [ ] All imports idempotent: php artisan crm:import-contacts && php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::count()" → still 9735
  [ ] grep -c '::observe(' modules/module-crm/src/Providers/CrmModuleServiceProvider.php → ≥8 (all sync observers registered)
  [ ] php artisan sync:status → healthy, zero errors
```

---

## Rules

```
1.  One migration per table. All columns + FKs + indexes in one file.
2.  foreignId()->constrained()->onDelete() on EVERY FK.
    organization_id: ON DELETE CASCADE
    Reference FKs: ON DELETE SET NULL
3.  DB::transaction() on every multi-step operation.
4.  Explicit ->with() eager loading everywhere.
5.  DECIMAL(12,2) + akaunting/laravel-money for money.
6.  spatie/laravel-model-states for status/stage.
7.  askedio/laravel-soft-cascade for parent-child.
8.  Observers for side effects. NEVER boot() cascades.
9.  propaganistas/laravel-phone for phone validation.
10. Import commands: IDEMPOTENT (safe to re-run).
11. Import commands: bypass OrganizationScope, explicitly set organization_id.
12. All CRM models: use BelongsToOrganization trait (from starter kit).
13. All CRM models: use wildside/userstamps for created_by/updated_by.
14. All CRM models MUST have organization_id.
15. All syncable models MUST implement SyncableContract + use Syncable trait.
16. No direct database queries — use Eloquent (sync depends on observers).
17. Namespace: Cogneiss\ModuleCrm (NOT Modules\Crm).
18. Module path: modules/module-crm/ (NOT modules/crm/).
19. AI SDK: laravel/ai (NOT prism-php/prism directly).
20. Verification checklist after every step.
21. Human approval before next step.
22. No auto-commits.
```
