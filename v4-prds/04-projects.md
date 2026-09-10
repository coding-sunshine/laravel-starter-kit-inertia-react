# PRD 04: Projects and Lots — Models, Import, and UI

## Overview

Create the projects and lots domain: migrations for projects + 4 child tables + lots + favorites + updates, Eloquent models with sync integration, data import from v3, and CRM UI pages. **Prerequisites:** PRD 02 (contacts + users imported, organizations created) and PRD 03 (sync infrastructure built — observers, field mappers, jobs all exist) must be complete.

**CRITICAL: Never touch `sites/default/sqlconf.php`. Never commit without human approval.**

## Technical Context

- **Module path:** `modules/module-crm/` — namespace `Cogneiss\ModuleCrm`
- **Companion docs:** `newdb.md` (projects/lots DDL), `newdb_simple.md` (quick ref), `plan.md` (Step 3), `sync-architecture.md` (sections 5.3-5.4 for field mappers)
- **Key packages:** `spatie/laravel-model-states` (lot stage), `akaunting/laravel-money` (price fields), `askedio/laravel-soft-cascade`, `machour/laravel-data-table`
- **Visibility rules:** PIAB org projects = shared marketplace (visible to ALL orgs); subscriber org projects = scoped to that org only
- **Sync:** Project and Lot are bidirectional entities. ProjectSyncObserver and LotSyncObserver must be registered. Field mappers already created in PRD 03.
- **v3 project decomposition:** v3 has 63 columns in one `projects` table. v4 splits into 5 tables: `projects` (core), `project_specs`, `project_investment`, `project_features`, `project_pricing`. Only main `projects` table syncs bidirectionally.

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `DB_CONNECTION=pgsql` — PostgreSQL connection (set in preflight)
- `DB_LEGACY_HOST`, `DB_LEGACY_PORT`, `DB_LEGACY_DATABASE`, `DB_LEGACY_USERNAME`, `DB_LEGACY_PASSWORD` — Legacy MySQL connection for project/lot import

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Project and Lot Migrations and Models

**Status:** todo
**Priority:** 1
**Description:** Create migrations and models for projects, 4 child tables, lots, project_favorites, and project_updates. All in `modules/module-crm/`.

**Lot Stage (spatie/model-states):** Define states for `Lot.stage`: `available`, `reserved`, `under_contract`, `settled`, `sold`, `withdrawn`. Create state classes in `modules/module-crm/src/Models/States/LotStage/`. Allowed transitions: available→reserved, reserved→under_contract|available, under_contract→settled|available, settled→sold. Use `lockForUpdate()` on stage transitions to prevent race conditions.

**Migrations** (all with `organization_id` FK CASCADE, see `newdb_simple.md` for all columns):

1. `create_projects_table.php`: `organization_id` FK CASCADE, `legacy_project_id` BIGINT UNIQUE, `title`, `description`, `estate`, `stage` (VARCHAR, model-state), `developer_id` FK SET NULL, `projecttype_id` FK SET NULL, address columns (flattened: address_line1, address_line2, city, state, postcode, latitude, longitude), `is_featured` BOOLEAN, `is_hot_property` BOOLEAN, `extra_attributes` JSONB + GIN index, `synced_at` TIMESTAMPTZ, `sync_source` VARCHAR(10), `created_by`/`updated_by` FK SET NULL, timestamps, softDeletes.

2. `create_project_specs_table.php`: `project_id` FK CASCADE (UNIQUE), `organization_id` FK CASCADE. Columns: min_land_area, max_land_area, min_living_area, max_living_area, min_bedrooms, max_bedrooms, min_bathrooms, max_bathrooms, storeys, frontage, depth, floor_area, land_info.

3. `create_project_investment_table.php`: `project_id` FK CASCADE (UNIQUE), `organization_id` FK CASCADE. Columns: min_rent, max_rent, avg_rent, rent_yield, avg_rent_yield, rent_to_sell_yield, vacancy_rate, capital_growth, median_price.

4. `create_project_features_table.php`: `project_id` FK CASCADE (UNIQUE), `organization_id` FK CASCADE. Feature boolean flags, amenities JSONB.

5. `create_project_pricing_table.php`: `project_id` FK CASCADE (UNIQUE), `organization_id` FK CASCADE. Columns: min_price DECIMAL(12,2), max_price DECIMAL(12,2), avg_price, body_corporate_fees, min_body_corporate_fees, max_body_corporate_fees, rates_fees, min_rates_fees, max_rates_fees, price_range_text.

6. `create_lots_table.php`: `project_id` FK CASCADE, `organization_id` FK CASCADE, `legacy_lot_id` BIGINT UNIQUE, `title`, `lot_number`, `stage` (VARCHAR, model-state), `price` DECIMAL(12,2), `land_price`, `build_price`, `bedrooms` SMALLINT, `bathrooms` SMALLINT, `car_spaces` SMALLINT, `storeys` SMALLINT, `internal_area` NUMERIC, `external_area` NUMERIC, `land_area` NUMERIC, `rent_weekly` DECIMAL(12,2), `rent_yield`, `rent_to_sell_yield`, `title_status`, `title_date`, `completion_date` DATE, `level`, `aspect`, `view`, `building_name`, `balcony_area` NUMERIC, `living_area`, `garage` SMALLINT, `body_corporate`, `rates`, `mpr` NUMERIC(10,2), `floorplan_url`, `uuid` UUID, `total_price` NUMERIC, `is_study` BOOLEAN, `is_storage` BOOLEAN, `is_powder_room` BOOLEAN, `is_nras` BOOLEAN, `is_cashflow_positive` BOOLEAN, `is_smsf` BOOLEAN, `synced_at`, `sync_source`, `created_by`/`updated_by` FK SET NULL, timestamps, softDeletes. (~49 columns total)

7. `create_project_favorites_table.php`: Composite PK (`user_id` + `project_id`). `user_id` FK CASCADE, `project_id` FK CASCADE.

8. `create_project_updates_table.php`: `project_id` FK CASCADE, `organization_id` FK CASCADE, `title`, `body` TEXT, `published_at` TIMESTAMPTZ, timestamps.

**Models** (all `Cogneiss\ModuleCrm\Models`):
- `Project`: `BelongsToOrganization`, `Syncable`, `HasFactory`, `SoftDeletes`, `LogsActivity`. `hasOne(ProjectSpec)`, `hasOne(ProjectInvestment)`, `hasOne(ProjectFeature)`, `hasOne(ProjectPricing)`. `hasMany(Lot)`, `hasMany(ProjectUpdate)`. `spatie/model-states` for stage. `spatie/schemaless-attributes` for extra_attributes. `wildside/userstamps`.
- `Lot`: `BelongsToOrganization`, `Syncable`, `HasFactory`, `SoftDeletes`. `spatie/model-states` for stage (transitions: available -> reserved -> sold, use `lockForUpdate()`). `akaunting/money` for price columns. `belongsTo(Project)`.
- `ProjectSpec`, `ProjectInvestment`, `ProjectFeature`, `ProjectPricing`: `BelongsToOrganization`. `belongsTo(Project)`.
- `ProjectUpdate`: `BelongsToOrganization`. `belongsTo(Project)`.

Register sync observers in `CrmModuleServiceProvider`: `Project::observe(ProjectSyncObserver::class)`, `Lot::observe(LotSyncObserver::class)`.

**Developer Organizations:** v3 `developers` table rows (336) should each become an Organization with `type = 'developer'`. Create `ImportDevelopers.php` command: read `mysql_legacy.developers` -> create Organization with `type='developer'` for each. Map `developer_id` on projects to the new developer `organization_id`. The old `developers` lookup table is still created for backward compatibility, but the real entity is now the developer Organization. Each developer org should have an `organization_domain` record for subdomain access (e.g., metricon.fusioncrm.com).

- [ ] `projects` table exists with all columns including `legacy_project_id`, `synced_at`, `sync_source`
- [ ] `project_specs`, `project_investment`, `project_features`, `project_pricing` tables exist with UNIQUE `project_id`
- [ ] `lots` table exists with ~49 columns including `legacy_lot_id`, `synced_at`, `sync_source`
- [ ] `project_favorites` table with composite PK (user_id + project_id)
- [ ] `project_updates` table exists
- [ ] All tables have `organization_id` FK with ON DELETE CASCADE
- [ ] GIN index on `projects.extra_attributes`
- [ ] Lot model uses `spatie/model-states` for stage and `akaunting/money` for prices
- [ ] Lot stage transitions use `lockForUpdate()` in transaction
- [ ] Project model has `hasOne` relationships to specs, investment, features, pricing
- [ ] `ProjectSyncObserver` and `LotSyncObserver` registered in service provider
- [ ] `php artisan migrate` succeeds
- [ ] Lot stage uses spatie/model-states with defined transitions
- [ ] Lot stage transition uses lockForUpdate() to prevent race conditions

### US-002: Project and Lot Data Import

**Status:** todo
**Priority:** 2
**Description:** Import projects (15,481), lots (121,360), project_updates (155,273), and favorites (2,172) from v3. All commands in `modules/module-crm/src/Console/Commands/`.

**ImportDevelopers.php (run before ImportProjects):**
- Import developers as organizations first (336 rows -> 336 developer orgs with `type='developer'`)
- Each developer org gets an `organization_domain` for subdomain access
- Build developer_id -> organization_id map for use by ImportProjects

**ImportProjects.php:**
- Read `mysql_legacy.projects` (15,481 rows)
- Per project in `DB::transaction()`:
  - Create `projects` row (core columns + address flattened from `mysql_legacy.addresses` WHERE `model_type='App\Models\Project'`)
  - Set `organization_id` = PIAB org (all v3 projects are shared marketplace listings)
  - Set `developer_organization_id` pointing to the developer org (from ImportDevelopers map)
  - Store `legacy_project_id`
  - Create `project_specs`, `project_investment`, `project_features`, `project_pricing` child records
  - Move `is_featured`, `is_hot_property`, `land_info`, boolean flags -> `extra_attributes` JSONB
  - Stage from `projects.stage` + `statusables` fallback
- Idempotent: skip if `legacy_project_id` already exists

**ImportLots.php:**
- 121,360 rows. Batch in chunks of 1,000.
- `organization_id` inherited from parent project
- Store `legacy_lot_id`
- Column renames: `car`->`car_spaces`, `storyes`->`storeys`, `weekly_rent`->`rent_weekly`, `land_size`->`land_area`, `internal`->`internal_area`, `external`->`external_area`, `building`->`building_name`, `balcony`->`balcony_area`, `floorplan`->`floorplan_url`, `total`->`total_price`, `body_corporation`->`body_corporate`, `completion`->`completion_date`
- Type casts: `bedrooms`/`bathrooms` varchar->SMALLINT, `land_size` varchar->NUMERIC, `study`/`storage`/`powder_room` int->boolean
- Stage from `lots.stage` + `statusables` fallback
- Money cast all price columns
- Idempotent: skip if `legacy_lot_id` already exists

**ImportProjectUpdates.php:** 155,273 rows. Direct copy with batching (chunks of 1,000).

**ImportFavorites.php:** `love_reactions` (2,172 rows from v3 reactable system) -> `project_favorites` (user_id, project_id).

- [ ] `Project::count()` = 15,481
- [ ] `Project::whereNull('organization_id')->count()` = 0
- [ ] `Project::whereNull('legacy_project_id')->count()` = 0
- [ ] `Project::has('specs')->count()` = 15,481 (every project has specs)
- [ ] `Project::has('investment')->count()` = 15,481
- [ ] `Project::has('features')->count()` = 15,481
- [ ] `Project::has('pricing')->count()` = 15,481
- [ ] `Lot::count()` = 121,360
- [ ] `Lot::whereNull('organization_id')->count()` = 0
- [ ] `Lot::whereNull('legacy_lot_id')->count()` = 0
- [ ] `Lot::whereNull('stage')->count()` = 0
- [ ] `DB::table('project_updates')->count()` = 155,273
- [ ] `DB::table('project_favorites')->count()` = 2,172
- [ ] Money fields populated: `Lot::whereNotNull('price')->where('price','>',0)->count()` > 0
- [ ] Lot stage transitions work: `Lot::where('stage','available')->first()` returns a lot
- [ ] Re-running import produces same result (idempotent)
- [ ] 336 developer organizations created with type='developer'
- [ ] Each developer org has organization_domain for subdomain
- [ ] Projects reference both organization_id (PIAB/owner) and developer_organization_id
- [ ] `php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Project::count()"` → 15566 (approximate, may vary)
- [ ] `php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Lot::count()"` → 121755 (approximate)

### US-003: Project and Lot UI Pages

**Status:** todo
**Priority:** 3
**Description:** Create CRM UI pages for projects and lots following the DataTable pattern.

**Backend:**
- `modules/module-crm/src/DataTables/ProjectDataTable.php`: Extends `AbstractDataTable`. Columns: title, estate, stage, developer, min_price, max_price, total_lots, is_featured. Filterable: stage, developer_id, projecttype_id, organization_id (shared vs own).
- `modules/module-crm/src/DataTables/LotDataTable.php`: Extends `AbstractDataTable`. Columns: title, project (relation), stage, price, bedrooms, bathrooms, land_size. Filterable: stage, project_id, price range, bedrooms.
- `modules/module-crm/src/Http/Controllers/ProjectController.php`: `show()` with tabs (overview, specs, investment, features, pricing, lots list, updates).
- `modules/module-crm/src/Http/Controllers/LotController.php`: `show()` with detail + reservation history + sales history.
- Routes at `modules/module-crm/routes/web.php`: `/crm/projects` (CRUD + bulk), `/crm/lots` (CRUD + bulk).

**Frontend:**
- `resources/js/pages/crm/projects/`: index.tsx, show.tsx, create.tsx, edit.tsx
- `resources/js/pages/crm/lots/`: index.tsx, show.tsx, create.tsx, edit.tsx
- Shared listings: index page shows PIAB + own org projects together with org badge distinguishing shared vs own
- TypeScript types in `resources/js/types/crm/project.ts` and `lot.ts`

**Stock/Portal Toggle in AppSidebar header:**
- Developer users see: Stock Management (manage their projects/lots) + Portal (what agents see)
- Agency users see: Portal (browse developer stock + own properties) + CRM (manage leads/sales)
- PIAB users see: Admin (everything)
- Implementation: Check `auth()->user()->organization->type` to determine available views
- Toggle component: `<ViewToggle>` in AppSidebar, switches route prefix between `/crm/stock/` and `/crm/portal/`

Run `php artisan wayfinder:generate` after creating routes.

- [ ] Project list page loads at `/crm/projects` (200)
- [ ] Project DataTable columns: title, estate, stage, developer, min_price, max_price, total_lots, is_featured
- [ ] Project filtering: stage, developer_id, projecttype_id, organization_id
- [ ] Lot list page loads at `/crm/lots` (200)
- [ ] Lot DataTable columns: title, project, stage, price, bedrooms, bathrooms, land_size
- [ ] Lot filtering: stage, project_id, price range, bedrooms
- [ ] Project show page with tabs (overview, specs, investment, features, pricing, lots, updates)
- [ ] Lot show page with detail + reservation history + sales history
- [ ] Shared listings visible: PIAB org projects shown with org badge
- [ ] Sync test: creating a project in v4 triggers sync to v3 `mysql_legacy.projects`
- [ ] Wayfinder route helpers generated
