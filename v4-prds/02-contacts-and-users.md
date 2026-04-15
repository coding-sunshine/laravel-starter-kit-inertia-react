# PRD 02: Contacts and Users — Models, Import, UI, and User Setup

## Overview

Create the contacts domain (migrations, models, import, UI) and import users with organization setup. This PRD builds the core person entity that everything else references. **Prerequisites:** PRD 01 must be complete — module registered, extensions installed, lookups seeded, PIAB org created, Shield roles configured.

**CRITICAL: Never touch `sites/default/sqlconf.php`. Never commit without human approval.**

## Technical Context

- **Module path:** `modules/module-crm/` — namespace `Cogneiss\ModuleCrm`
- **Companion docs:** `newdb.md` (sections 3.1-3.3 for contacts schema), `newdb_simple.md` (contacts quick ref), `plan.md` (Step 1 + Step 2), `dbissues.md`
- **UI pattern:** DataTable pattern from starter kit. Reference: `/users` page -> `UsersTableController` -> `UserDataTable` -> `user-table.tsx`
- **Key packages:** `spatie/laravel-model-states` (stage), `spatie/laravel-schemaless-attributes` (extra_attributes), `askedio/laravel-soft-cascade`, `wildside/userstamps`, `propaganistas/laravel-phone`, `machour/laravel-data-table`
- **Multi-tenancy:** `organization_id` on every CRM table, `BelongsToOrganization` trait, `OrganizationScope` global scope. Import commands bypass scope.

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `DB_CONNECTION=pgsql` — PostgreSQL connection (set in preflight)
- `DB_LEGACY_HOST`, `DB_LEGACY_PORT`, `DB_LEGACY_DATABASE`, `DB_LEGACY_USERNAME`, `DB_LEGACY_PASSWORD` — Legacy MySQL connection for contact/user import

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Contact Migrations and Models

**Status:** todo
**Priority:** 1
**Description:** Create database migrations and Eloquent models for contacts, contact_emails, and contact_phones. All migrations in `modules/module-crm/database/migrations/`.

**Contact Stage (spatie/model-states):** Define states for `Contact.stage`: `new`, `contacted`, `qualified`, `proposal`, `negotiation`, `won`, `lost`, `archived`. Create state classes in `modules/module-crm/src/Models/States/ContactStage/`. Allowed transitions: new→contacted, contacted→qualified, qualified→proposal, proposal→negotiation, negotiation→won|lost, any→archived.

**Migrations** (see `newdb_simple.md` "contacts" section and `newdb.md` section 3.1-3.3 for full DDL):

1. `create_contacts_table.php`:
   - `organization_id` FK CASCADE (NOT NULL)
   - `legacy_lead_id` BIGINT UNIQUE (nullable — set during import)
   - `contact_origin` VARCHAR with CHECK constraint: `IN ('property', 'saas_product')`
   - `first_name`, `last_name`, `job_title`, `type`, `stage` (VARCHAR, model-state)
   - `source_id`, `company_id`, `created_by`, `updated_by` — all FK SET NULL
   - `lead_score` INTEGER DEFAULT 0
   - `address_line1`, `address_line2`, `city`, `state`, `postcode`, `country`
   - `next_followup_at`, `last_followup_at`, `last_contacted_at` TIMESTAMPTZ
   - `extra_attributes` JSONB with GIN index
   - `synced_at` TIMESTAMPTZ, `sync_source` VARCHAR(10)
   - Partial index on `(organization_id, stage) WHERE deleted_at IS NULL`
   - `timestamps()`, `softDeletes()`

2. `create_contact_emails_table.php`:
   - `contact_id` FK CASCADE, `organization_id` FK CASCADE
   - `legacy_email_id` BIGINT UNIQUE
   - `email` VARCHAR NOT NULL, `type` VARCHAR, `is_primary` BOOLEAN DEFAULT false
   - `order_column` INTEGER
   - `synced_at`, `sync_source`, `timestamps()`, `softDeletes()`

3. `create_contact_phones_table.php`:
   - `contact_id` FK CASCADE, `organization_id` FK CASCADE
   - `legacy_phone_id` BIGINT UNIQUE
   - `phone` VARCHAR NOT NULL, `type` VARCHAR, `is_primary` BOOLEAN DEFAULT false
   - `order_column` INTEGER
   - `synced_at`, `sync_source`, `timestamps()`, `softDeletes()`

**Models** (all in `modules/module-crm/src/Models/`, namespace `Cogneiss\ModuleCrm\Models`):

- `Contact.php`: Uses traits `BelongsToOrganization`, `Syncable`, `HasFactory`, `SoftDeletes`, `LogsActivity`. Soft cascade: `['emails', 'phones']` via `askedio/laravel-soft-cascade`. `spatie/model-states` for `stage`. `spatie/schemaless-attributes` for `extra_attributes`. `wildside/userstamps` for `created_by`/`updated_by`. Relationship traits in `src/Traits/`: `HasCommunications` (emails, phones), `HasPropertyInteractions` (enquiries, searches, reservations), `HasSalesActivity` (sales, commissions).

- `ContactEmail.php`: Uses `BelongsToOrganization`, `Syncable`, `SoftDeletes`. Belongs to Contact.

- `ContactPhone.php`: Uses `BelongsToOrganization`, `Syncable`, `SoftDeletes`. Phone validation via `propaganistas/laravel-phone`. Belongs to Contact.

- [ ] `contacts` table exists with all columns from `newdb_simple.md`
- [ ] `contact_emails` table exists with correct schema
- [ ] `contact_phones` table exists with correct schema
- [ ] `organization_id` FK with ON DELETE CASCADE on all three tables
- [ ] `legacy_lead_id`, `legacy_email_id`, `legacy_phone_id` columns exist (UNIQUE)
- [ ] Partial index on `(organization_id, stage)` on contacts WHERE `deleted_at IS NULL`
- [ ] GIN index on `extra_attributes` on contacts
- [ ] CHECK constraint: `contact_origin IN ('property', 'saas_product')`
- [ ] Contact model uses all required traits (BelongsToOrganization, Syncable, SoftDeletes, LogsActivity, model-states, schemaless-attributes, userstamps)
- [ ] Soft cascade configured: deleting contact soft-deletes emails + phones
- [ ] ContactEmail and ContactPhone models use BelongsToOrganization + Syncable
- [ ] `php artisan migrate` succeeds with new tables
- [ ] Contact stage uses spatie/model-states with defined transitions
- [ ] Invalid stage transition throws TransitionNotAllowed exception

### US-002: Contact Data Import

**Status:** todo
**Priority:** 2
**Description:** Create import and verify commands to migrate 9,735 leads from v3 MySQL to v4 PostgreSQL contacts. Commands in `modules/module-crm/src/Console/Commands/`. Register in `CrmModuleServiceProvider`.

**NOTE:** Create the base import class first (see CLAUDE.md "Import Command Pattern" section for the full base class with `--dry-run`, `--chunk`, `--since`, `--force` flags, chunked transactions, progress bar, and `mapRow()`/`upsertRow()` methods), then extend it for `ImportContacts`.

**ImportContacts.php:**
- Must bypass `OrganizationScope`: `Contact::withoutGlobalScope(OrganizationScope::class)`
- Explicitly set `organization_id` on every created record
- Read `mysql_legacy.leads` (9,735 rows)
- For each lead in `DB::transaction()`:
  - Create contact, store `lead.id` as `legacy_lead_id`
  - Set `contact_origin`: `'property'` (default) or `'saas_product'` (platform leads)
  - Set `organization_id`: resolve via subscriber relationship chain or default to PIAB org
  - Flatten addresses from `mysql_legacy.addresses WHERE model_type='App\Models\Lead'` (street->address_line1, unit->address_line2, suburb->city)
  - Stage: use `leads.stage`, fallback to LATEST `statusable` entry
  - Map `is_partner` -> `type='partner'`
  - Move `important_note`, `summary_note` -> `extra_attributes` JSONB
- Split v3 polymorphic `contacts` table:
  - `type='email_1'` -> `contact_emails` (is_primary=true)
  - `type='email_2'` -> `contact_emails` (is_primary=false)
  - `type='phone'` -> `contact_phones` (is_primary=true)
  - `website/facebook/linkedin` -> `contacts.extra_attributes`
  - `googleplus` -> DROP
- Build MAP: `legacy_lead_id -> new contact_id` (cache to file/Redis for later steps)
- Must be IDEMPOTENT: skip if `legacy_lead_id` already exists

**VerifyContacts.php:** Count validation, FK integrity checks, stage/address/org population checks.

- [ ] `php artisan crm:import-contacts` completes without errors
- [ ] 9,735 contacts exist in database
- [ ] 9,730 contact_emails exist
- [ ] 9,601 contact_phones exist
- [ ] Social links stored in `extra_attributes` JSONB
- [ ] Addresses flattened from v3 addresses table into contact columns
- [ ] All contacts have `stage` populated (zero NULL)
- [ ] `legacy_lead_id -> contact_id` MAP built and persisted
- [ ] Every contact has `organization_id` set (zero NULL)
- [ ] Every contact has `contact_origin` set (zero NULL)
- [ ] Re-running import produces same result (idempotent)
- [ ] `php artisan crm:verify-contacts` passes all checks
- [ ] `php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::count()"` → 9735
- [ ] `php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Contact::whereNull('organization_id')->count()"` → 0
- [ ] `php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\ContactEmail::count()"` → 9730

### US-003: Contact UI Pages

**Status:** todo
**Priority:** 3
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

- [ ] CRM contacts list page loads at `/crm/contacts` with DataTable (200)
- [ ] DataTable shows columns: first_name, last_name, email, phone, company, contact_origin, stage, lead_score, created_at
- [ ] Filtering works: by stage, contact_origin, company_id, source_id
- [ ] Search works: by first_name, last_name, email
- [ ] Contact show page loads at `/crm/contacts/{id}` with tabs
- [ ] Contact create form works at `/crm/contacts/create`
- [ ] Contact edit form works at `/crm/contacts/{id}/edit`
- [ ] Bulk soft-delete works from DataTable
- [ ] Soft cascade: deleting contact removes emails + phones
- [ ] Filament admin: SourceResource, CompanyResource, DeveloperResource, ProjecttypeResource accessible at `/admin`
- [ ] Wayfinder route helpers generated: `php artisan wayfinder:generate` exits 0

### US-004: Users Import and Organization Setup

**Status:** todo
**Priority:** 4
**Description:** Import 1,502 users from v3 and create Organizations for subscriber users. Each v3 subscriber becomes a v4 Organization. Depends on US-002 (contact import must be complete — `legacy_lead_id` MAP required).

**Migration:** Add `contact_id` FK to users table:
```php
$table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
```

**ImportUsers.php** (at `modules/module-crm/src/Console/Commands/`):
- Read `mysql_legacy.users` (1,502 rows)
- Import: name, email, password (keep hashed), email_verified_at, timestamps
- Set `users.contact_id` using `legacy_lead_id` MAP built in US-002
- Read `mysql_legacy.model_has_roles` (11,627 entries) -> map v3 role names to Spatie Permission roles
- Create Organization per subscriber-role user (org owner) with `type = 'agency'`. v3 subscribers become agency-type organizations in v4.
- Assign other users to organizations via `organization_user` pivot table

See `plan.md` Step 2 and sync architecture spec section 3.2 for subscriber -> org mapping logic.

- [ ] 1,502 users imported: `User::count()` = 1502
- [ ] Users have correct Spatie Permission roles assigned
- [ ] `contact_id` linked to corresponding contact where applicable: `User::whereNotNull('contact_id')->count()` > 0
- [ ] One Organization created per subscriber-role user: `Organization::where('slug','!=','piab')->count()` matches subscriber count
- [ ] Users can log in with their v3 passwords (password hashes preserved)
- [ ] Impersonation works: superadmin can impersonate any user
- [ ] User->Organization mapping persisted for use in subsequent import steps
- [ ] Non-subscriber users assigned to appropriate organizations
- [ ] All subscriber organizations have type='agency'
- [ ] Roles distribution: `User::role('superadmin')->count()` >= 1
