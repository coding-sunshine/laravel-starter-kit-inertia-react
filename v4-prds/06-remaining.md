# PRD 06: Remaining Entities — Notes, Comments, Partners, Tasks, Relationships, Media, and Lookups

## Overview

Import all remaining CRM entities: notes, comments, partners, tasks, relationships, media (morph updates), and lookup table enhancements (companies with sync columns). This is the "everything else" step that completes the data migration. **Prerequisites:** PRD 05 must be complete — sales exist (needed as morph targets for notes), contacts + projects exist for relationship remapping.

**CRITICAL: Never touch `sites/default/sqlconf.php`. Never commit without human approval.**

## Technical Context

- **Module path:** `modules/module-crm/` — namespace `Cogneiss\ModuleCrm`
- **Companion docs:** `plan.md` (Step 5), `newdb.md`, `newdb_simple.md`
- **Morph map:** Already registered in PRD 01 (contact, project, lot, sale, note, property_enquiry, property_search, property_reservation). All morph type references must use aliases, not class names.
- **Sync:** CompanySyncObserver must be registered for companies.
- **Key rules:** All imported records must have `organization_id` set. All commands must be idempotent. Use `DB::transaction()` for multi-step operations.

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `DB_CONNECTION=pgsql` — PostgreSQL connection (set in preflight)
- `DB_LEGACY_HOST`, `DB_LEGACY_PORT`, `DB_LEGACY_DATABASE`, `DB_LEGACY_USERNAME`, `DB_LEGACY_PASSWORD` — Legacy MySQL connection for remaining entity imports

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

**SKIP list (do not import):**
- `activity_log` (461K rows) -> start fresh in v4
- `login_histories` (42K) -> starter kit `login_events`
- `column_management` (4.5K) -> starter kit `data_table_saved_views`
- `user_api_tokens/ips` -> Sanctum handles this
- WordPress tables -> not moving
- `services/company_service` -> 5 years stale

## User Stories

### US-001: Import Notes, Comments, Partners, Tasks, and Relationships

**Status:** todo
**Priority:** 1
**Description:** Import secondary CRM entities with morph type updates, FK remaps, and org_id assignment. All commands in `modules/module-crm/src/Console/Commands/`.

**UI Note:** No DataTable pages needed for notes/comments/partners/tasks — these are sub-components shown on parent entity pages (contact show, project show, sale show). Notes are polymorphic, shown as a timeline on the parent entity's show page. Tasks are a simple list/create on the contact show page. Partners/Relationships are shown as tabs on the contact show page.

**ImportNotes.php** (22,681 rows):
- Update morph types: `App\Models\Lead` -> `'contact'` (use morph map alias)
- Remap `noteable_id` for Lead notes using `legacy_lead_id` MAP
- Set `organization_id` via `author_id` -> User -> Org
- Idempotent: use `legacy_note_id` or upsert key

**ImportComments.php** (4,259 rows):
- Import to starter kit `crm_comments` table
- Update morph types to aliases
- Direct column copy + `organization_id`

**ImportPartners.php** (299 rows):
- Column renames: `lead_id` -> `contact_id`, `parent_id` -> `parent_partner_id`, `relationship` -> `partner_type`
- Remap `lead_id` via `legacy_lead_id` MAP
- Set `organization_id`

**ImportRelationships.php** (7,378 rows):
- Column renames: `account_id` -> `account_contact_id`, `relation_id` -> `relation_contact_id`
- Remap both FKs via `legacy_lead_id` MAP
- Set `organization_id`

**ImportTasks.php** (57 rows):
- Column renames: `assigned_id` -> `assigned_to_user_id`, `attached_id` -> `attached_contact_id` (REMAP via legacy MAP), `start_at` -> `due_at`, `end_at` -> DROP
- Set `organization_id`

Create corresponding models in `modules/module-crm/src/Models/` if they don't exist: `Note`, `Partner`, `Relationship`, `Task`. All use `BelongsToOrganization`.

- [ ] `Note::count()` = 22,681
- [ ] `Note::where('noteable_type','App\Models\Lead')->count()` = 0 (all morph types updated to aliases)
- [ ] `DB::table('crm_comments')->count()` = 4,259
- [ ] `Relationship::count()` = 7,378
- [ ] `Relationship::whereNull('account_contact_id')->count()` = 0 (both FKs remapped)
- [ ] `Partner::count()` = 299
- [ ] `Task::count()` = 57
- [ ] All records have `organization_id` set: `Note::whereNull('organization_id')->count()` = 0, `Partner::whereNull('organization_id')->count()` = 0
- [ ] Contact detail page at `/crm/contacts/1` shows notes, tasks, relationships (200)
- [ ] Contact show page displays notes timeline
- [ ] Contact show page displays tasks list
- [ ] Contact show page displays relationships tab

### US-002: Import Media, Lookups, and Resources

**Status:** todo
**Priority:** 2
**Description:** Update media morph types, enhance company lookups with sync columns, and import resource tables.

**Media Update** (108,510 rows):
- Update `model_type` column to use morph map aliases (not full class names)
- `App\Models\Lead` -> `'contact'`, `App\Models\Project` -> `'project'`, etc.
- No column structure changes needed — just morph type values

**Company Lookup Enhancement:**
- Add columns to `companies` table: `legacy_company_id` BIGINT UNIQUE, `synced_at` TIMESTAMPTZ, `sync_source` VARCHAR(10)
- Rename `extra_company_info` -> `extra_attributes` (JSONB)
- Register `CompanySyncObserver` in `CrmModuleServiceProvider`:
  ```php
  Company::observe(CompanySyncObserver::class);
  ```

**Other Lookup Updates:**
- `sources`: verify `label` renamed to `name` (should be done in seeder from PRD 01)
- `developers`: rename `modified_by` -> `updated_by`, DROP `extra_attributes` (0% fill rate)
- `projecttypes`: no changes needed beyond what was seeded
- `states`: no org_id needed (shared reference data)
- `suburbs`: DROP `au_town_id` column

**Resources Import:**
- `resources` (138 rows): add `organization_id`, rename `modified_by` -> `updated_by`
- `resource_categories` (5 rows): direct copy
- `resource_groups` (9 rows): add `organization_id`

- [ ] `DB::table('media')->count()` = 108,510
- [ ] `DB::table('media')->where('model_type','LIKE','App\Models\%')->count()` = 0 (all morph types use aliases)
- [ ] `Company::whereNotNull('legacy_company_id')->count()` = 976 (all companies have legacy ID)
- [ ] `grep -c 'CompanySyncObserver' modules/module-crm/src/Providers/CrmModuleServiceProvider.php` >= 1
- [ ] `DB::table('resources')->count()` = 138
- [ ] `DB::table('resource_categories')->count()` = 5
- [ ] `DB::table('resource_groups')->count()` = 9
- [ ] All resource records have `organization_id` set
- [ ] Companies have `synced_at` and `sync_source` columns
- [ ] Companies have `extra_attributes` column (renamed from `extra_company_info`)
- [ ] `php artisan tinker --execute="echo \Cogneiss\ModuleCrm\Models\Note::count()"` → 22861
- [ ] `php artisan tinker --execute="echo DB::table('media')->where('model_type','LIKE','App\\\Models\\\%')->count()"` → 0 (morph types updated)
