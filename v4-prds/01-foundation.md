# PRD 01: Foundation — Module Setup, Config, Seeders, and Roles

## Overview

Set up the CRM module foundation within the existing `modules/module-crm/` scaffold, create PostgreSQL extensions, configure sync, seed lookup tables, and establish roles. This is the first code-level PRD; the codebase must have completed the Pre-Flight Checklist (`prds/00-preflight-checklist.md`) — the app should be running at `localhost:8000`, migrations complete, and `mysql_legacy` connection verified.

**CRITICAL: Never touch `sites/default/sqlconf.php`. Never commit without human approval.**

## Technical Context

- **Stack:** Laravel 12, PostgreSQL 16, Filament 5, Spatie suite
- **Module path:** `modules/module-crm/` — namespace `Cogneiss\ModuleCrm`
- **Companion docs:** `plan.md` (Step 0a + Step 0b), `newdb.md`, `newdb_simple.md`
- **Key patterns:** Module extends `App\Support\ModuleServiceProvider`; `BelongsToOrganization` trait from `app/Models/Concerns/`; Spatie Permission for roles; Pennant for feature flags
- **Existing modules (DO NOT TOUCH):** announcements, billing, blog, changelog, contact, dashboards, gamification, help, page-builder, reports, workflows

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `DB_CONNECTION=pgsql` — PostgreSQL connection (set in preflight)
- `DB_LEGACY_HOST`, `DB_LEGACY_PORT`, `DB_LEGACY_DATABASE`, `DB_LEGACY_USERNAME`, `DB_LEGACY_PASSWORD` — Legacy MySQL connection for v3 data import
- `SYNC_QUEUE=sync`, `SYNC_PIAB_ORG_ID`, `SYNC_TIEBREAKER=v3` — Sync configuration
- `SENTRY_DSN` — Error tracking (optional for dev, required for prod)

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: CRM Module Setup

**Status:** todo
**Priority:** 1
**Description:** Configure PostgreSQL extensions, register the CRM module, delete generic scaffold, set up directory structure, register morph map, and enable lazy loading prevention.

**PostgreSQL Extensions** — Run in migration or tinker:
- `CREATE EXTENSION IF NOT EXISTS vector`
- `CREATE EXTENSION IF NOT EXISTS cube`
- `CREATE EXTENSION IF NOT EXISTS earthdistance`

**Module Registration:**
- Set `'module-crm' => true` in `config/modules.php`
- Verify `composer.json` autoload has `"Cogneiss\\ModuleCrm\\": "modules/module-crm/src/"`
- Configure Filament Admin panel in `app/Providers/Filament/AdminPanelProvider.php`:
  ```
  ->discoverResources(
      in: base_path('modules/module-crm/src/Filament/Resources'),
      for: 'Cogneiss\\ModuleCrm\\Filament\\Resources'
  )
  ```

**Delete Scaffold** — Remove existing generic CRM migrations and models (crm_contacts, crm_deals, crm_pipelines, crm_activities). FusionCRM has a property-specific domain model.

**Directory Structure** — Keep existing dirs, add missing ones under `modules/module-crm/`:
```
database/migrations/
database/seeders/
database/factories/
src/Providers/CrmModuleServiceProvider.php  (extends App\Support\ModuleServiceProvider)
src/Features/CrmFeature.php                (Pennant feature flag)
src/Models/Concerns/
src/Http/Controllers/
src/Http/Requests/
src/Filament/Resources/
src/Console/Commands/
src/Observers/
src/Sync/
src/Traits/
src/Actions/
src/Policies/
src/DataTables/
src/Ai/Tools/
```

**Morph Map** — Add to `app/Providers/AppServiceProvider.php` `boot()`:
```php
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
```

**Lazy Loading Prevention:**
```php
Model::preventLazyLoading(!app()->isProduction());
```

**CrmFeature** — Create `src/Features/CrmFeature.php` as a Pennant feature flag class. Also create `AiToolsFeature.php` and `PropertyAccessFeature.php` in the same directory. These extend `Laravel\Pennant\Feature`. Register all feature flags in `CrmModuleServiceProvider`. `CrmFeature` controls whether the CRM module is active per organization.

- [ ] PostgreSQL extensions created: `SELECT extname FROM pg_extension WHERE extname IN ('vector','cube','earthdistance')` returns 3 rows
- [ ] `config('modules.module-crm')` returns `true`
- [ ] `composer.json` autoload has `Cogneiss\\ModuleCrm\\` mapped to `modules/module-crm/src/`
- [ ] Filament Admin panel discovers `Cogneiss\ModuleCrm\Filament\Resources`
- [ ] Scaffold CRM tables removed: no `crm_contacts`, `crm_deals`, `crm_pipelines`, `crm_activities` migrations exist
- [ ] All required directories exist under `modules/module-crm/`
- [ ] Morph map registered in `AppServiceProvider::boot()` with all 8 CRM aliases: `count(Relation::morphMap())` >= 8
- [ ] `Model::preventLazyLoading(!app()->isProduction())` configured
- [ ] `CrmFeature` Pennant feature flag exists at `modules/module-crm/src/Features/CrmFeature.php`
- [ ] `CrmModuleServiceProvider` extends `App\Support\ModuleServiceProvider` and class exists
- [ ] Existing starter kit modules untouched (verify no changes to other module dirs)
- [ ] Filament admin panel loads at `/admin` (200 after login)
- [ ] Filament system panel loads at `/system` (200 for superadmin)
- [ ] `php artisan tinker --execute="echo count(DB::select(\"SELECT extname FROM pg_extension WHERE extname IN ('vector','cube','earthdistance')\"))"` → 3
- [ ] `php artisan tinker --execute="echo \App\Models\Organization::where('slug','piab')->count()"` → 1

### US-002: Config, Seeders, and Roles

**Status:** todo
**Priority:** 2
**Description:** Create sync config, seed PIAB organization, configure Shield roles, seed lookup tables from `mysql_legacy`, and configure Stripe stub.

**Sync Config** — Create `config/sync.php`:
```php
return [
    'piab_organization_id' => env('SYNC_PIAB_ORG_ID'),
    'tiebreaker_source' => env('SYNC_TIEBREAKER', 'v3'),
    'batch_chunk_size' => env('SYNC_BATCH_CHUNK', 500),
    'poll_interval' => env('SYNC_POLL_INTERVAL', 60),
    'batch_interval' => env('SYNC_BATCH_INTERVAL', 1800),
];
```

**Organizations `type` Column** — Add `type` column to organizations table:
```sql
ALTER TABLE organizations ADD COLUMN type VARCHAR(20) NOT NULL DEFAULT 'agency' CHECK (type IN ('platform', 'developer', 'agency'));
```
Create a migration in `modules/module-crm/database/migrations/` to add this column.

**PIAB Organization Seed** — Create database seeder:
```php
Organization::create(['name' => 'PIAB', 'slug' => 'piab', 'type' => 'platform']);
```
The org ID must match the `SYNC_PIAB_ORG_ID` env var. PIAB org must have `type = 'platform'`.

**Spatie Permission Roles** — Seed roles and permissions per the matrix below. See `archive/rebuild-plan/00-roles-permissions.md` for full details.

**Active User Roles (9 roles to seed):**

| Role | Slug | Scope |
|---|---|---|
| Super Admin | `superadmin` | Global (all orgs) — full system access |
| PIAB Admin | `piab_admin` | Global (all orgs) — full CRM, minus system config |
| Subscriber | `subscriber` | Org-scoped — paying CRM customer (org owner) |
| Agent | `agent` | Org-scoped — sales agent under subscriber |
| Sales Agent | `sales_agent` | Org-scoped — legacy alias for agent (identical perms) |
| BDM | `bdm` | Org-scoped — business development manager |
| Referral Partner | `referral_partner` | Org-scoped — external, limited to own contacts |
| Affiliate | `affiliate` | Org-scoped — can see subscribers + property portal |
| Property Manager | `property_manager` | Org-scoped — full property CRUD |

**Contact-type Roles (NOT login roles — `is_user_role = false`):**
lead, client, partner, affiliate_contact, finance_broker, conveyancer, accountant, developer_contact, insurance_agency, event_coordinator, saas_lead, other

**Permission Categories (seed all, assign per role):**
- Dashboard: `contacts.view.dashboard`
- Contacts: `contacts.view`, `contacts.create`, `contacts.edit`, `contacts.delete`, `contacts.view.own`, `contacts.edit.own`, `contacts.export`, `contacts.import`, `contacts.merge`
- Property Portal: `projects.view`, `projects.view.details`, `projects.create`, `projects.edit`, `projects.delete`, `lots.view`, `lots.view.details`, `lots.create`, `lots.edit`, `lots.delete`, `potential_properties.view`, `potential_properties.manage`, `potential_properties.map`
- Sales & Reservations: `reservations.view`, `reservations.create`, `reservations.edit`, `sales.view`, `sales.create`, `commissions.view`, `spr.view`, `spr.manage`
- Tasks: `tasks.view`, `tasks.view.own`, `tasks.create`, `tasks.edit`, `tasks.delete`
- Enquiries: `enquiries.view`, `enquiries.property_search.view`, `enquiries.reservation.view`, `enquiries.finance.view`
- Marketing: `marketing.view`, `flyers.view/create/edit/delete`, `campaign_websites.view/create/edit/delete`, `mail_lists.view`, `mail_jobs.view`, `websites.view`
- Reports: `reports.view`, `reports.network_activity`, `reports.notes_history`, `reports.login_history`, `reports.same_device`, `reports.website`, `reports.wp_website`, `reports.campaign`
- Resources: `resources.view/create/edit/delete`
- Admin: `users.view/create/edit/delete`, `roles.manage`, `api_keys.view/manage`, `ai_credits.view/manage`, `settings.system`, `orgs.view_all/manage`, `agents.view`, `co_living_projects.view`

**Role -> Permission Assignments:**
- `superadmin`: ALL permissions
- `piab_admin`: ALL except `reports.same_device`, `roles.manage`, `settings.system`, `orgs.manage`
- `subscriber`: Dashboard, full contacts, projects/lots view, reservations create, sales/commissions view, own tasks, enquiries, marketing, reports (website/wp/campaign), resources, users view/create, API keys, AI credits view, SPR view, agents view
- `agent`/`sales_agent`: Dashboard, contacts (view/create/edit/delete + own), projects/lots view, reservations/sales/commissions view, own tasks, enquiries, resources, mail lists, co-living
- `bdm`: Dashboard, contacts (full), projects/lots view, reservations create, sales/commissions, own tasks, enquiries, resources, mail lists, users view/create (subscribers)
- `referral_partner`: Dashboard, own contacts only, own tasks, enquiries view, resources, commissions view, mail lists
- `affiliate`: Dashboard, contacts (full), projects/lots view, resources, commissions, own tasks, mail lists, users view/create (subscribers)
- `property_manager`: Dashboard, users (developers), projects/lots (full CRUD), potential properties (full), own tasks, enquiries, resources, mail lists

**Data Scoping:**
- superadmin/piab_admin: all orgs
- subscriber/agent/bdm/affiliate: `WHERE organization_id = user.organization_id`
- referral_partner: own created contacts only (`created_by = auth()->id()`)
- `contacts.view.own` further restricts to `assigned_agent_id = auth()->id()`

**Seeder:** Create `database/seeders/CrmRolesPermissionsSeeder.php` — call from `EssentialSeeder`. Runtime-editable via Filament -> Settings -> Roles & Permissions.

**Lookup Table Seeders** — Import from `mysql_legacy`. All must have `organization_id` set to PIAB org (shared data). All `organization_id` FKs use ON DELETE CASCADE.
- `sources` (17 rows) — rename `label` -> `name`
- `companies` (976 rows)
- `developers` (336 rows)
- `projecttypes` (12 rows)
- `states` (8 rows)
- `suburbs` (15,299 rows)

**Stripe Stub** — `stripe/stripe-php` is already in `composer.json`. Configure but stub (return success without charging). No real payment credentials yet.

- [ ] `config('sync.piab_organization_id')` returns expected PIAB org ID
- [ ] `config('sync.tiebreaker_source')` returns `'v3'`
- [ ] `php artisan migrate:fresh --seed` runs clean without errors (exit code 0)
- [ ] PIAB organization exists: `Organization::where('slug','piab')->exists()` returns `true`
- [ ] App loads at `localhost:8000` (200)
- [ ] Filament admin panel loads at `/admin` (200)
- [ ] Filament system panel loads at `/system` (200, superadmin only)
- [ ] Roles seeded: `Role::count()` = 6 (superadmin, admin, subscriber, agent, bdm, affiliate)
- [ ] `Source::count()` = 17
- [ ] `Company::count()` = 976
- [ ] `Developer::count()` = 336
- [ ] `Projecttype::count()` = 12
- [ ] `State::count()` = 8
- [ ] `Suburb::count()` = 15,299
- [ ] All lookup records have `organization_id` set: `Source::whereNull('organization_id')->count()` = 0
- [ ] Pulse accessible at `/pulse` (200)
- [ ] `php artisan health:check` passes (exit code 0)
- [ ] `DB::connection('mysql_legacy')->table('leads')->count()` = 9735
- [ ] PIAB organization has type='platform'
- [ ] organizations table has type column with CHECK constraint
- [ ] Stripe configured in stub mode (no real charges)
- [ ] Sentry configured (sentry/sentry-laravel already in starter kit, add SENTRY_DSN to .env)
- [ ] Backup configured (spatie/laravel-backup already in starter kit, verify `php artisan backup:run` works)
- [ ] CRM feature flag classes created: `CrmFeature`, `AiToolsFeature`, `PropertyAccessFeature` in `modules/module-crm/src/Features/`
- [ ] Feature flags registered in `CrmModuleServiceProvider`
- [ ] Impersonation verified (stechstudio/filament-impersonate already in starter kit, superadmin can impersonate)
- [ ] Impersonation works: superadmin at /system can impersonate any user via stechstudio/filament-impersonate
- [ ] CrmFeature flag class created and registered (Pennant)
