# FusionCRM v4 — Sync Architecture Reference

> **Version**: 1.0
> **Date**: 2026-03-23
> **Status**: Reference document for implementation
> **Companion docs**: `newdb.md` (schema), `newdb_simple.md` (quick ref), `plan.md` (build steps)

---

## 1. Overview

FusionCRM v3 (MySQL) remains live during v4 (PostgreSQL) development. Both databases sync bidirectionally until a permanent cutover to v4.

**Core principles:**

- All sync code lives in v4 only, inside `modules/module-crm/src/Sync/`
- v4 connects to v3 via a `mysql_legacy` database connection
- v3 is untouched except for `updated_at` indexes on polled tables (see §10)
- Sync is designed to be cleanly disabled at cutover — observers, jobs, and commands are removed; no schema changes needed
- All sync writes use `upsert` keyed on `legacy_*_id` columns for idempotency

**Sync mechanisms:**

| Mechanism | Direction | Trigger | Entities |
|-----------|-----------|---------|----------|
| Observer-driven | v4 → v3 | Model save event | 8 bidirectional |
| Polled (60s) | v3 → v4 | Scheduled command | 8 bidirectional |
| Batch (30min) | v3 → v4 | Scheduled command | 5 one-way |

---

## 2. Entity Sync Matrix

| Entity | Direction | Mechanism | v3 Table | v4 Table | ID Mapping | Sync Order | Org Derivation |
|--------|-----------|-----------|----------|----------|------------|------------|----------------|
| Company | ⇄ | Event + Poll | `companies` | `companies` | `legacy_company_id` | 1 | PIAB org (shared data) |
| Contact | ⇄ | Event + Poll | `leads` | `contacts` | `legacy_lead_id` | 2 | `subscriber_id` via relationships → Lead → User → Org; else PIAB org |
| Project | ⇄ | Event + Poll | `projects` | `projects` | `legacy_project_id` | 3 | PIAB org (shared data) |
| Lot | ⇄ | Event + Poll | `lots` | `lots` | `legacy_lot_id` | 4 | Inherited from `projects.organization_id` |
| Sale | ⇄ | Event + Poll | `sales` | `sales` | `legacy_sale_id` | 5 | `subscriber_id` → Lead → User → Org |
| Reservation | ⇄ | Event + Poll | `property_reservations` | `property_reservations` | `legacy_reservation_id` | 6 | `loggedin_agent_id` → Lead → User → Org |
| Email | ⇄ | Event + Poll | `contacts` (polymorphic) | `contact_emails` | `legacy_email_id` | 7 | Inherited from parent contact |
| Phone | ⇄ | Event + Poll | `contacts` (polymorphic) | `contact_phones` | `legacy_phone_id` | 8 | Inherited from parent contact |
| User | → | Batch (30min) | `users` | `users` | `legacy_user_id` | — | Via `lead_id` → Lead → Org |
| Commission | → | Batch (30min) | `commissions` (polymorphic) | `commissions` | `legacy_commission_id` | — | Inherited from parent sale |
| Note | → | Batch (30min) | `notes` | `notes` | `legacy_note_id` | — | Via `author_id` → User → Org |
| Activity | — | SKIP | `activity_log` (spatie, 464K rows) | `activity_log` | — | — | Start fresh in v4. Too noisy to migrate. |
| Project Update | → | Batch (30min) | `project_updates` | `project_updates` | — | — | Inherited from parent project |

**Key corrections from v3 snapshot:**
- `lead_emails` and `lead_phones` tables **do not exist** in v3. Emails and phones are stored in the polymorphic `contacts` table (model_type, model_id, type, value).
- `payouts` table **does not exist** in v3. Removed from sync scope.
- `lead_activities` **does not exist**. Activities are in `activity_log` (spatie, 464K rows). **Skipped** — too noisy to migrate, start fresh in v4.

---

## 3. Sync Flows

### 3.1 Observer-Driven (v4 → v3)

```
v4 Model Saved (create/update/soft-delete)
  → SyncObserver fires (e.g. ContactSyncObserver)
    → Check: $model->syncing === true?
      → YES: skip (this write came FROM sync — prevent loop)
      → NO: dispatch SyncToLegacyJob to `sync` queue
        → Field mapper transforms v4 fields → v3 columns
        → Upsert to mysql_legacy using legacy_*_id
        → Log to sync_logs table
```

Each bidirectional entity has a dedicated observer registered in `CrmServiceProvider`. The observer listens to `created`, `updated`, and `deleted` (soft-delete) events.

### 3.2 Polled (v3 → v4)

```
SyncPollCommand runs every 60 seconds (Laravel scheduler)
  → For each entity IN SYNC ORDER (1–8, sequentially):
    → Query mysql_legacy WHERE updated_at > last_poll_time
    → For each changed row:
      → Field mapper transforms v3 columns → v4 fields
      → Compare updated_at: if v3 is newer → proceed
      → Set $model->syncing = true (prevent observer loop)
      → Upsert to v4 PostgreSQL
      → Log to sync_logs table
    → Update last_poll_time for this entity
```

The 8 entity polls run **sequentially** within a single command invocation to avoid overwhelming v3's MySQL. Load impact: 8 indexed queries per minute against v3 (~10K–50K rows per table).

### 3.3 Batch (one-way, v3 → v4)

```
SyncBatchCommand runs every 30 minutes (Laravel scheduler)
  → For each one-way entity (User, Commission, Note, Activity, Project Update):
    → Query mysql_legacy WHERE updated_at > last_sync_time
    → Process in chunks of 500 records per transaction
    → Batch upsert to v4 PostgreSQL
    → Update last_sync_time for this entity
    → Log to sync_logs table
```

---

## 4. Loop Prevention

Every sync write sets a runtime flag on the model instance:

```php
$model->syncing = true;
```

This flag is **not persisted** to the database. It exists only on the in-memory model instance during the current request/job lifecycle.

**Prevention mechanism:**
1. v4 model saved → Observer fires
2. Observer checks `$model->syncing` — if `true`, skips dispatch
3. If `false`, dispatches `SyncToLegacyJob`

This prevents the infinite loop:
```
v4 save → sync to v3 → poller detects v3 change → sync back to v4 → observer fires → ∞
```

**Edge cases:**
- Queue worker crash mid-sync: Safe because all sync writes use `upsert` (idempotent). The job retries and writes the same data again.
- Concurrent poller + observer: The `updated_at` comparison ensures last-write-wins. If both fire simultaneously, the newer timestamp wins on the next poll cycle.

---

## 5. Field Mappers

Each bidirectional entity has a dedicated field mapper class in `modules/module-crm/src/Sync/FieldMappers/`. The mapper handles both directions: `toV3(Model $v4Model): array` and `toV4(array $v3Row): array`.

FK resolution: Field mappers resolve foreign keys by looking up the v4 record via `legacy_*_id`. If the parent record doesn't exist yet (race condition), the sync job is **re-queued with a 30-second delay** (max 3 retries, then logged as error).

### 5.1 Contact ↔ Lead (`ContactFieldMapper`)

v3 table: `leads` (23 columns) | v4 table: `contacts` (27 columns)

| v4 Column | v3 Column | Transform | Notes |
|-----------|-----------|-----------|-------|
| `id` | — | — | v4 auto-increment (BIGSERIAL) |
| `organization_id` | — | Computed | Derived from v3 subscriber relationship (see §9). Not written back to v3. |
| `legacy_lead_id` | `id` | Direct | v3 PK stored as mapping key |
| `contact_origin` | — | Computed | `'property'` for property leads; `'saas_product'` for platform leads. Not written back to v3. |
| `first_name` | `first_name` | Direct | — |
| `last_name` | `last_name` | Direct | — |
| `job_title` | `job_title` | Direct | — |
| `type` | — | Computed | Derived from v3 roles/relationships pivot. Default `'lead'`. Not written back to v3. |
| `stage` | `stage` | Direct | v3 stores free-text; v4 uses spatie/model-states. Map known values. |
| `source_id` | `source_id` | FK remap | v4 `sources.id` looked up via legacy source mapping |
| `company_id` | `company_id` | FK remap | v4 `companies.id` looked up via `legacy_company_id` |
| `lead_score` | — | Default 0 | New in v4. Not written back to v3. |
| `last_contacted_at` | — | — | New in v4. Not written back to v3. |
| `next_followup_at` | `next_followup_at` | Direct | MySQL datetime → PostgreSQL TIMESTAMPTZ |
| `last_followup_at` | `last_followup_at` | Direct | MySQL datetime → PostgreSQL TIMESTAMPTZ |
| `address_line1` | `extra_attributes->address` | JSONB extract | v3 stores address in extra_attributes JSON or polymorphic `addresses` table. Flattened in v4. |
| `address_line2` | — | — | Extracted from address if available |
| `city` | `extra_attributes->city` | JSONB extract | — |
| `state` | `extra_attributes->state` | JSONB extract | — |
| `postcode` | `extra_attributes->postcode` | JSONB extract | — |
| `country` | — | Default `'AU'` | — |
| `extra_attributes` | `extra_attributes` | JSONB merge | v4 JSONB. Merge remaining v3 JSON fields not extracted to flat columns. Includes `summary_note`, `important_note`. |
| `synced_at` | — | Set on sync | Current timestamp. Not written to v3. |
| `sync_source` | — | Set on sync | `'v3'` or `'v4'`. Not written to v3. |
| `created_by` | `created_by` | FK remap | v3 references `users.id`. v4 also references `users.id`. Remap via `legacy_user_id`. |
| `updated_by` | `modified_by` | FK remap | v3 column is `modified_by` → v4 `updated_by`. Remap via `legacy_user_id`. |
| `created_at` | `created_at` | Direct | MySQL timestamp → PostgreSQL TIMESTAMPTZ |
| `updated_at` | `updated_at` | Direct | — |
| `deleted_at` | `deleted_at` | Direct | Soft deletes synced bidirectionally |

**v4 → v3 reverse mapping (columns written back to v3 `leads`):**

| v3 Column | v4 Column | Transform |
|-----------|-----------|-----------|
| `first_name` | `first_name` | Direct |
| `last_name` | `last_name` | Direct |
| `job_title` | `job_title` | Direct |
| `stage` | `stage` | Direct |
| `source_id` | `source_id` | Reverse FK remap via legacy source mapping |
| `company_id` | `company_id` | Reverse FK remap via `legacy_company_id` |
| `next_followup_at` | `next_followup_at` | Direct |
| `last_followup_at` | `last_followup_at` | Direct |
| `extra_attributes` | `extra_attributes` + address fields | JSONB merge: re-inject address fields into JSON |
| `modified_by` | `updated_by` | Reverse FK remap |
| `updated_at` | `updated_at` | Direct |
| `deleted_at` | `deleted_at` | Direct |

**Skipped v3 columns (not synced to v4):**

| v3 Column | Reason |
|-----------|--------|
| `is_new` | Replaced by `stage` in v4 |
| `is_partner` | Replaced by `type = 'partner'` in v4 |
| `old_table_id` | Legacy migration artifact |
| `primary_color` | UI preference, not CRM data |
| `secondary_color` | UI preference |
| `primary_text_color` | UI preference |

### 5.2 Company ↔ Company (`CompanyFieldMapper`)

v3 table: `companies` (8 columns) | v4 table: `companies` (12 columns)

| v4 Column | v3 Column | Transform | Notes |
|-----------|-----------|-----------|-------|
| `id` | — | — | v4 auto-increment |
| `organization_id` | — | Computed | PIAB org (companies are shared data). Not written to v3. |
| `legacy_company_id` | `id` | Direct | v3 PK stored as mapping key |
| `name` | `name` | Direct | — |
| `slogan` | `slogan` | Direct | — |
| `bk_name` | `bk_name` | Direct | — |
| `extra_attributes` | `extra_company_info` | Rename | v3 `extra_company_info` (JSON) → v4 `extra_attributes` (JSONB) |
| `synced_at` | — | Set on sync | — |
| `sync_source` | — | Set on sync | — |
| `created_at` | `created_at` | Direct | — |
| `updated_at` | `updated_at` | Direct | — |
| `deleted_at` | `deleted_at` | Direct | — |

**v4 → v3 reverse mapping:**

| v3 Column | v4 Column | Transform |
|-----------|-----------|-----------|
| `name` | `name` | Direct |
| `bk_name` | `bk_name` | Direct |
| `slogan` | `slogan` | Direct |
| `extra_company_info` | `extra_attributes` | Rename (JSONB → JSON) |
| `updated_at` | `updated_at` | Direct |
| `deleted_at` | `deleted_at` | Direct |

### 5.3 Project ↔ Project (`ProjectFieldMapper`)

v3 table: `projects` (63 columns) | v4 table: `projects` (19 columns) + 4 child tables

v4 splits the v3 `projects` table into 5 tables (`projects`, `project_specs`, `project_investment`, `project_features`, `project_pricing`). **Sync only touches the main `projects` table for bidirectional sync.** The child tables are populated during initial migration and managed in v4 only.

| v4 Column | v3 Column | Transform | Notes |
|-----------|-----------|-----------|-------|
| `id` | — | — | v4 auto-increment |
| `organization_id` | — | Computed | PIAB org (projects are shared data). Not written to v3. |
| `legacy_project_id` | `id` | Direct | v3 PK |
| `title` | `title` | Direct | — |
| `description` | `description` | Direct | — |
| `stage` | `stage` | Direct | v3 free-text; v4 constrained. Map known values. |
| `estate` | `estate` | Direct | — |
| `developer_id` | `developer_id` | FK remap | Remap via legacy developer mapping |
| `projecttype_id` | `projecttype_id` | FK remap | Remap via legacy projecttype mapping |
| `address_line1` | — | From `addresses` | v3 stores project addresses in polymorphic `addresses` table. Flattened in v4. |
| `address_line2` | — | From `addresses` | — |
| `city` | — | From `addresses` | — |
| `state` | — | From `addresses` | — |
| `postcode` | — | From `addresses` | — |
| `latitude` | — | From `addresses` | — |
| `longitude` | — | From `addresses` | — |
| `extra_attributes` | multiple cols | JSONB merge | Merge: `is_featured`, `is_hot_property`, `is_archived`, `is_hidden`, `is_smsf`, `is_firb`, `is_ndis`, `is_cashflow_positive`, `build_time`, `historical_growth`, `land_info`, `sub_agent_comms`, `uuid` |
| `synced_at` | — | Set on sync | — |
| `sync_source` | — | Set on sync | — |
| `created_by` | `created_by` | FK remap | Remap via `legacy_user_id` |
| `updated_by` | `modified_by` | FK remap | v3 `modified_by` → v4 `updated_by` |
| `created_at` | `created_at` | Direct | — |
| `updated_at` | `updated_at` | Direct | — |
| `deleted_at` | `deleted_at` | Direct | — |

**v4 → v3 reverse mapping:**

| v3 Column | v4 Column | Transform |
|-----------|-----------|-----------|
| `title` | `title` | Direct |
| `description` | `description` | Direct |
| `stage` | `stage` | Direct |
| `estate` | `estate` | Direct |
| `developer_id` | `developer_id` | Reverse FK remap |
| `projecttype_id` | `projecttype_id` | Reverse FK remap |
| `is_featured` | `extra_attributes->is_featured` | JSONB extract → boolean column |
| `is_hot_property` | `extra_attributes->is_hot_property` | JSONB extract |
| `is_archived` | `extra_attributes->is_archived` | JSONB extract |
| `is_hidden` | `extra_attributes->is_hidden` | JSONB extract |
| `is_smsf` | `extra_attributes->is_smsf` | JSONB extract |
| `is_firb` | `extra_attributes->is_firb` | JSONB extract |
| `is_ndis` | `extra_attributes->is_ndis` | JSONB extract |
| `is_cashflow_positive` | `extra_attributes->is_cashflow_positive` | JSONB extract |
| `build_time` | `extra_attributes->build_time` | JSONB extract |
| `historical_growth` | `extra_attributes->historical_growth` | JSONB extract |
| `land_info` | `extra_attributes->land_info` | JSONB extract |
| `sub_agent_comms` | `extra_attributes->sub_agent_comms` | JSONB extract |
| `modified_by` | `updated_by` | Reverse FK remap |
| `updated_at` | `updated_at` | Direct |
| `deleted_at` | `deleted_at` | Direct |

**Skipped v3 columns (not synced bidirectionally — managed in child tables or dropped):**

| v3 Column | Reason |
|-----------|--------|
| `total_lots` | Computed from lots count in v4 |
| `storeys` | Moved to `project_specs` |
| `min_landsize`, `max_landsize` | Moved to `project_specs` (renamed `min_land_area`, `max_land_area`) |
| `min_living_area`, `max_living_area` | Moved to `project_specs` |
| `bedrooms`, `bathrooms` | Dropped at project level (0.7% fill); kept at lot level |
| `min_bedrooms`, `max_bedrooms` | Moved to `project_specs` |
| `min_bathrooms`, `max_bathrooms` | Moved to `project_specs` |
| `garage` | Dropped at project level (0% fill) |
| `min_rent` through `avg_rent_yield` | Moved to `project_investment` |
| `rent_to_sell_yield` | Moved to `project_investment` |
| `min_price`, `max_price`, `avg_price` | Moved to `project_pricing` |
| `body_corporate_fees`, `min_body_corporate_fees`, `max_body_corporate_fees` | Moved to `project_pricing` |
| `rates_fees`, `min_rates_fees`, `max_rates_fees` | Moved to `project_pricing` |
| `start_at`, `end_at` | Moved to `extra_attributes` or dropped |
| `old_table_id` | Legacy migration artifact |
| `love_reactant_id` | v3-only (reactable system not migrated) |
| `uuid` | Merged into `extra_attributes` |

### 5.4 Lot ↔ Lot (`LotFieldMapper`)

v3 table: `lots` (48 columns) | v4 table: `lots` (43 columns)

| v4 Column | v3 Column | Transform | Notes |
|-----------|-----------|-----------|-------|
| `id` | — | — | v4 auto-increment |
| `organization_id` | — | Computed | Inherited from `projects.organization_id` via trigger. Not written to v3. |
| `project_id` | `project_id` | FK remap | Remap via `legacy_project_id` |
| `legacy_lot_id` | `id` | Direct | v3 PK |
| `lot_number` | — | — | New in v4. Extracted from `title` if available. |
| `title` | `title` | Direct | — |
| `price` | `price` | Direct | — |
| `land_price` | `land_price` | Direct | — |
| `build_price` | `build_price` | Direct | — |
| `stage` | `stage` | Direct | v3 free-text → v4 CHECK constraint. Map known values. |
| `bedrooms` | `bedrooms` | Type cast | v3 `varchar(40)` → v4 `SMALLINT`. Parse integer from string. |
| `bathrooms` | `bathrooms` | Type cast | v3 `varchar(40)` → v4 `SMALLINT`. Parse integer. |
| `car_spaces` | `car` | Rename + cast | v3 `car` (int unsigned) → v4 `car_spaces` (SMALLINT) |
| `storeys` | `storyes` | Rename | v3 typo `storyes` → v4 `storeys` |
| `internal_area` | `internal` | Rename | v3 `internal` → v4 `internal_area` |
| `external_area` | `external` | Rename | v3 `external` → v4 `external_area` |
| `land_area` | `land_size` | Rename + cast | v3 `land_size` (varchar) → v4 `land_area` (NUMERIC). Parse number. |
| `rent_weekly` | `weekly_rent` | Rename | v3 `weekly_rent` → v4 `rent_weekly` |
| `rent_yield` | `rent_yield` | Direct | — |
| `rent_to_sell_yield` | `rent_to_sell_yield` | Direct | — |
| `title_status` | `title_status` | Direct | — |
| `title_date` | — | — | New in v4 |
| `completion_date` | `completion` | Type cast | v3 `completion` (varchar) → v4 `completion_date` (DATE). Parse date. |
| `level` | `level` | Direct | — |
| `aspect` | `aspect` | Direct | — |
| `view` | `view` | Direct | — |
| `building_name` | `building` | Rename | v3 `building` → v4 `building_name` |
| `balcony_area` | `balcony` | Rename + cast | v3 `balcony` (int unsigned) → v4 `balcony_area` (NUMERIC) |
| `living_area` | `living_area` | Direct | — |
| `garage` | `garage` | Type cast | v3 `varchar(50)` → v4 `SMALLINT`. Parse integer. |
| `body_corporate` | `body_corporation` | Rename | v3 `body_corporation` → v4 `body_corporate` |
| `rates` | `rates` | Direct | — |
| `mpr` | `mpr` | Direct | v3 `int unsigned` → v4 `NUMERIC(10,2)` |
| `floorplan_url` | `floorplan` | Rename | v3 `floorplan` (varchar) → v4 `floorplan_url` |
| `uuid` | `uuid` | Direct | v3 varchar → v4 UUID type |
| `total_price` | `total` | Rename + cast | v3 `total` (int unsigned) → v4 `total_price` (NUMERIC) |
| `is_study` | `study` | Boolean cast | v3 `study` (int unsigned) → v4 `is_study` (boolean). `> 0` → `true` |
| `is_storage` | `storage` | Boolean cast | v3 `storage` (int) → v4 `is_storage` (boolean). `> 0` → `true` |
| `is_powder_room` | `powder_room` | Boolean cast | v3 `powder_room` (int unsigned) → v4 `is_powder_room`. `> 0` → `true` |
| `is_nras` | `is_nras` | Boolean cast | v3 `tinyint(1)` → v4 `BOOLEAN` |
| `is_cashflow_positive` | `is_cashflow_positive` | Boolean cast | Same |
| `is_smsf` | `is_smsf` | Boolean cast | Same |
| `synced_at` | — | Set on sync | — |
| `sync_source` | — | Set on sync | — |
| `created_by` | `created_by` | FK remap | Remap via `legacy_user_id` |
| `updated_by` | `modified_by` | FK remap | v3 `modified_by` → v4 `updated_by` |
| `created_at` | `created_at` | Direct | — |
| `updated_at` | `updated_at` | Direct | — |
| `deleted_at` | `deleted_at` | Direct | — |

**v4 → v3 reverse mapping:**

| v3 Column | v4 Column | Transform |
|-----------|-----------|-----------|
| `project_id` | `project_id` | Reverse FK remap via `legacy_project_id` |
| `title` | `title` | Direct |
| `price` | `price` | Direct |
| `land_price` | `land_price` | Direct |
| `build_price` | `build_price` | Direct |
| `stage` | `stage` | Direct |
| `bedrooms` | `bedrooms` | Cast SMALLINT → varchar |
| `bathrooms` | `bathrooms` | Cast SMALLINT → varchar |
| `car` | `car_spaces` | Rename |
| `storyes` | `storeys` | Rename (maintain v3 typo) |
| `internal` | `internal_area` | Rename |
| `external` | `external_area` | Rename |
| `land_size` | `land_area` | Rename + cast NUMERIC → varchar |
| `weekly_rent` | `rent_weekly` | Rename |
| `rent_yield` | `rent_yield` | Direct |
| `rent_to_sell_yield` | `rent_to_sell_yield` | Direct |
| `title_status` | `title_status` | Direct |
| `completion` | `completion_date` | Cast DATE → varchar |
| `level` | `level` | Direct |
| `aspect` | `aspect` | Direct |
| `view` | `view` | Direct |
| `building` | `building_name` | Rename |
| `balcony` | `balcony_area` | Rename + cast |
| `living_area` | `living_area` | Direct |
| `garage` | `garage` | Cast SMALLINT → varchar |
| `body_corporation` | `body_corporate` | Rename |
| `rates` | `rates` | Direct |
| `mpr` | `mpr` | Direct |
| `floorplan` | `floorplan_url` | Rename |
| `total` | `total_price` | Rename + cast |
| `study` | `is_study` | Boolean → int |
| `storage` | `is_storage` | Boolean → int |
| `powder_room` | `is_powder_room` | Boolean → int |
| `is_nras` | `is_nras` | Boolean → tinyint |
| `is_cashflow_positive` | `is_cashflow_positive` | Boolean → tinyint |
| `is_smsf` | `is_smsf` | Boolean → tinyint |
| `modified_by` | `updated_by` | Reverse FK remap |
| `updated_at` | `updated_at` | Direct |
| `deleted_at` | `deleted_at` | Direct |

**Skipped v3 columns:**

| v3 Column | Reason |
|-----------|--------|
| `five_percent_share_available` | 0% fill rate (28/121K). Dropped in v4. |
| `five_percent_share_price` | Same |
| `sub_agent_comms` | 0% fill rate. Dropped in v4. |
| `is_archived` | Replaced by `deleted_at` (soft deletes) in v4 |
| `old_table_id` | Legacy migration artifact |

### 5.5 Sale ↔ Sale (`SaleFieldMapper`)

v3 table: `sales` (40 columns) | v4 table: `sales` (20 columns) + `commissions` (normalized)

**CRITICAL**: In v3, `Sale.created_by` and `Sale.updated_by` reference `leads.id` (NOT `users.id`). The field mapper must perform an extra-hop resolution: `leads.id` → find matching `users` record via `users.lead_id` → use that `users.id` for v4's `created_by`/`updated_by` (which reference `users.id`).

Commission columns in v3 are flat on the `sales` table (e.g., `piab_comm`, `subscriber_comm`, `affiliate_comm`). In v4, these are normalized into separate rows in the `commissions` table. The sale sync handles the main sale record; commission sync is one-way batch.

| v4 Column | v3 Column | Transform | Notes |
|-----------|-----------|-----------|-------|
| `id` | — | — | v4 auto-increment |
| `organization_id` | `subscriber_id` | FK resolution | `subscriber_id` → Lead → User → Org. For 2 sales without `subscriber_id`: `created_by` → Lead → User → Org. |
| `legacy_sale_id` | `id` | Direct | v3 PK |
| `client_contact_id` | `client_id` | FK remap | v3 `client_id` → `leads.id`. Remap via `legacy_lead_id` → `contacts.id`. |
| `lot_id` | `lot_id` | FK remap | Remap via `legacy_lot_id` |
| `project_id` | `project_id` | FK remap | Remap via `legacy_project_id` |
| `developer_id` | `developer_id` | FK remap | Remap via legacy developer mapping |
| `sales_agent_contact_id` | `sales_agent_id` | FK remap | v3 `sales_agent_id` → `leads.id`. Remap via `legacy_lead_id` → `contacts.id`. |
| `subscriber_contact_id` | `subscriber_id` | FK remap | v3 `subscriber_id` → `leads.id`. Remap via `legacy_lead_id` → `contacts.id`. |
| `bdm_contact_id` | `bdm_id` | FK remap | v3 `bdm_id` → `leads.id`. Remap via `legacy_lead_id` → `contacts.id`. |
| `referral_partner_contact_id` | `referral_partner_id` | FK remap | Same pattern |
| `affiliate_contact_id` | `affiliate_id` | FK remap | Same pattern |
| `agent_contact_id` | `agent_id` | FK remap | Same pattern |
| `status` | — | From statusables | v3 uses polymorphic `statusables` table. Resolve most recent status name. |
| `purchase_price` | — | — | New in v4. May be derived from lot price. |
| `finance_due_date` | `finance_due_date` | Direct | — |
| `settlement_date` | — | — | New in v4 |
| `contract_date` | — | — | New in v4 |
| `extra_attributes` | multiple cols | JSONB merge | Merge: `expected_commissions`, `divide_percent`, `custom_attributes`, `comments`, `summary_note`, `comm_in_notes`, `comm_out_notes` |
| `synced_at` | — | Set on sync | — |
| `sync_source` | — | Set on sync | — |
| `created_by` | `created_by` | FK remap (extra hop) | v3 `created_by` → `leads.id` → `users.lead_id` match → v4 `users.id`. See CRITICAL note above. |
| `updated_by` | `updated_by` | FK remap (extra hop) | Same extra hop: `leads.id` → find `users` row → `users.id` |
| `created_at` | `created_at` | Direct | — |
| `updated_at` | `updated_at` | Direct | — |
| `deleted_at` | `deleted_at` | Direct | — |

**v4 → v3 reverse mapping:**

| v3 Column | v4 Column | Transform |
|-----------|-----------|-----------|
| `client_id` | `client_contact_id` | Reverse FK: `contacts.id` → `legacy_lead_id` |
| `lot_id` | `lot_id` | Reverse FK via `legacy_lot_id` |
| `project_id` | `project_id` | Reverse FK via `legacy_project_id` |
| `developer_id` | `developer_id` | Reverse FK |
| `sales_agent_id` | `sales_agent_contact_id` | Reverse FK: `contacts.id` → `legacy_lead_id` |
| `subscriber_id` | `subscriber_contact_id` | Reverse FK: `contacts.id` → `legacy_lead_id` |
| `bdm_id` | `bdm_contact_id` | Same pattern |
| `referral_partner_id` | `referral_partner_contact_id` | Same pattern |
| `affiliate_id` | `affiliate_contact_id` | Same pattern |
| `agent_id` | `agent_contact_id` | Same pattern |
| `finance_due_date` | `finance_due_date` | Direct |
| `expected_commissions` | `extra_attributes->expected_commissions` | JSONB extract |
| `divide_percent` | `extra_attributes->divide_percent` | JSONB extract |
| `custom_attributes` | `extra_attributes->custom_attributes` | JSONB extract |
| `comments` | `extra_attributes->comments` | JSONB extract |
| `summary_note` | `extra_attributes->summary_note` | JSONB extract |
| `updated_by` | `updated_by` | Reverse FK (extra hop): v4 `users.id` → `users.lead_id` → `leads.id` |
| `updated_at` | `updated_at` | Direct |
| `deleted_at` | `deleted_at` | Direct |

**Skipped v3 columns (dropped in v4 or moved to commissions):**

| v3 Column | Reason |
|-----------|--------|
| `comms_in_total` | Computed from `commissions` table in v4 |
| `comms_out_total` | Same |
| `piab_comm` | Normalized to `commissions` row with `commission_type = 'piab'` |
| `affiliate_comm` | Normalized to `commissions` row |
| `subscriber_comm` | Normalized to `commissions` row |
| `sales_agent_comm` | Normalized to `commissions` row |
| `bdm_comm` | Normalized to `commissions` row |
| `agent_comm` | Normalized to `commissions` row |
| `referral_partner_comm` | Normalized to `commissions` row |
| `payment_terms` | 0% fill rate. Dropped. |
| `is_comments_enabled` | UI preference. Dropped. |
| `is_sas_enabled` | 0% fill rate (never launched). Dropped. |
| `is_sas_max` | Same |
| `sas_percent` | Same |
| `sas_fee` | Same |
| `status_updated_at` | 0% fill rate. Dropped. |
| `comm_in_notes` | Merged into `extra_attributes` |
| `comm_out_notes` | Merged into `extra_attributes` |

### 5.6 Reservation ↔ PropertyReservation (`ReservationFieldMapper`)

v3 table: `property_reservations` (29 columns) | v4 table: `property_reservations` (28 columns)

| v4 Column | v3 Column | Transform | Notes |
|-----------|-----------|-----------|-------|
| `id` | — | — | v4 auto-increment |
| `organization_id` | `loggedin_agent_id` | FK resolution | `loggedin_agent_id` → Lead → User → Org |
| `legacy_reservation_id` | `id` | Direct | v3 PK |
| `lot_id` | `lot_id` | FK remap | Via `legacy_lot_id` |
| `project_id` | `property_id` | FK remap + rename | v3 `property_id` → `projects.id`. Remap via `legacy_project_id`. v4 renames to `project_id`. |
| `agent_contact_id` | `agent_id` | FK remap | v3 `agent_id` → `leads.id`. Remap via `legacy_lead_id` → `contacts.id`. |
| `primary_contact_id` | `purchaser1_id` | FK remap + rename | v3 `purchaser1_id` → `leads.id`. Remap → `contacts.id`. |
| `secondary_contact_id` | `purchaser2_id` | FK remap + rename | Same pattern |
| `logged_in_user_id` | `loggedin_agent_id` | FK remap | v3 `loggedin_agent_id` → `leads.id` → find `users.lead_id` match → `users.id` |
| `purchase_price` | `purchase_price` | Type cast | v3 `varchar(191)` → v4 `NUMERIC(12,2)`. Parse number. |
| `deposit_amount` | `deposit` | Rename + cast | v3 `deposit` (varchar) → v4 `deposit_amount` (NUMERIC). Parse number. |
| `status` | — | From statusables | Resolve from polymorphic `statusables` table. Default `'pending'`. |
| `purchaser_type` | `purchaser_type` | Type change | v3 `varchar(191)` → v4 `JSONB` |
| `trustee_name` | `trustee_name` | Direct | — |
| `finance_pre_approved` | `finance_preapproval` | Rename + cast | v3 `finance_preapproval` (varchar) → v4 `finance_pre_approved` (boolean) |
| `finance_days_required` | `finance_days_req` | Rename + cast | v3 `finance_days_req` (varchar) → v4 `finance_days_required` (SMALLINT) |
| `abn_acn` | `abn_acn` | Direct | — |
| `is_family_trust` | `family_trust` | JSONB extract + boolean | v3 `family_trust` (JSON) → v4 `is_family_trust` (boolean). Check if JSON indicates trust setup. |
| `is_bare_trust` | `bare_trust_setup` | JSONB extract + boolean | v3 `bare_trust_setup` (JSON) → v4 boolean |
| `is_smsf_trust` | `SMSF_trust_setup` | JSONB extract + boolean | v3 `SMSF_trust_setup` (JSON) → v4 boolean |
| `is_funds_rollover` | `funds_rollover` | JSONB extract + boolean | v3 `funds_rollover` (JSON) → v4 boolean |
| `broker_name` | `broker` | JSONB extract | v3 `broker` (JSON) → extract name field → v4 `broker_name` (varchar) |
| `firm_name` | `firm` | JSONB extract | v3 `firm` (JSON) → extract name field → v4 `firm_name` (varchar) |
| `is_agreed` | `agree` | Type cast | v3 `agree` (varchar) → v4 `is_agreed` (boolean) |
| `is_lawlab_agreed` | `agree_lawlab` | JSONB extract + boolean | v3 `agree_lawlab` (JSON) → v4 boolean |
| `contract_sent_at` | `contract_send` | JSONB extract + timestamp | v3 `contract_send` (JSON) → extract date → v4 `contract_sent_at` (TIMESTAMPTZ) |
| `synced_at` | — | Set on sync | — |
| `sync_source` | — | Set on sync | — |
| `created_by` | `loggedin_agent_id` | FK remap | `loggedin_agent_id` → Lead → find User → v4 `users.id` |
| `updated_by` | — | — | Set on sync updates only |
| `created_at` | `created_at` | Direct | — |
| `updated_at` | `updated_at` | Direct | — |
| `deleted_at` | `deleted_at` | Direct | — |

**v4 → v3 reverse mapping:**

| v3 Column | v4 Column | Transform |
|-----------|-----------|-----------|
| `agent_id` | `agent_contact_id` | Reverse FK: `contacts.id` → `legacy_lead_id` |
| `property_id` | `project_id` | Reverse FK via `legacy_project_id` |
| `lot_id` | `lot_id` | Reverse FK via `legacy_lot_id` |
| `purchaser1_id` | `primary_contact_id` | Reverse FK: `contacts.id` → `legacy_lead_id` |
| `purchaser2_id` | `secondary_contact_id` | Same |
| `purchase_price` | `purchase_price` | Cast NUMERIC → varchar |
| `deposit` | `deposit_amount` | Rename + cast |
| `purchaser_type` | `purchaser_type` | JSONB → varchar (serialize) |
| `trustee_name` | `trustee_name` | Direct |
| `finance_preapproval` | `finance_pre_approved` | Boolean → varchar |
| `finance_days_req` | `finance_days_required` | Cast SMALLINT → varchar |
| `abn_acn` | `abn_acn` | Direct |
| `updated_at` | `updated_at` | Direct |
| `deleted_at` | `deleted_at` | Direct |

**Skipped v3 columns:**

| v3 Column | Reason |
|-----------|--------|
| `land_deposit` | 0% fill rate. Dropped. |
| `build_deposit` | 0% fill rate. Dropped. |
| `agree_date` | 0% fill rate. Dropped. |

### 5.7 Email ↔ Contacts (polymorphic) (`EmailFieldMapper`)

v3 source: `contacts` table WHERE `model_type = 'App\\Models\\Lead'` AND `type LIKE 'email%'`
v4 table: `contact_emails` (12 columns)

**Polymorphic extraction logic (v3 → v4):**
```sql
SELECT * FROM contacts
WHERE model_type = 'App\\Models\\Lead'
  AND type LIKE 'email%'
  AND updated_at > :last_poll_time
```

The v3 `contacts` table stores multiple contact methods for any model (leads, projects, etc.) using polymorphic `model_type`/`model_id` columns. Email records for leads have `type` values like `email_1`, `email_2`, etc. The `value` column contains the email address.

| v4 Column | v3 Column | Transform | Notes |
|-----------|-----------|-----------|-------|
| `id` | — | — | v4 auto-increment |
| `organization_id` | — | Computed | Inherited from parent contact's `organization_id`. Resolve via `model_id` → Lead → Contact → `organization_id`. |
| `contact_id` | `model_id` | FK remap | v3 `model_id` (= `leads.id` when `model_type = 'App\\Models\\Lead'`) → remap via `legacy_lead_id` → `contacts.id` |
| `legacy_email_id` | `id` | Direct | v3 `contacts.id` (polymorphic table PK) |
| `type` | `type` | Enum remap | v3: `email_1` → v4: `work`; `email_2` → `home`; other `email_*` → `other` |
| `email` | `value` | Rename | v3 `value` → v4 `email` |
| `is_primary` | `type` | Derived | `email_1` → `true`; others → `false` |
| `order_column` | `order_column` | Direct | — |
| `synced_at` | — | Set on sync | — |
| `sync_source` | — | Set on sync | — |
| `created_at` | `created_at` | Direct | — |
| `updated_at` | `updated_at` | Direct | — |

**v4 → v3 reverse mapping (writing to polymorphic `contacts` table):**

| v3 Column | v4 Column | Transform |
|-----------|-----------|-----------|
| `model_type` | — | Always `'App\\Models\\Lead'` |
| `model_id` | `contact_id` | Reverse FK: `contacts.id` → `legacy_lead_id` |
| `type` | `type` + `is_primary` | Reverse enum: if `is_primary` → `'email_1'`; `type='home'` → `'email_2'`; else `'email_3'` etc. |
| `value` | `email` | Rename |
| `order_column` | `order_column` | Direct |
| `custom_attributes` | — | Null (not used for emails) |
| `updated_at` | `updated_at` | Direct |

**Skipped v3 columns:**

| v3 Column | Reason |
|-----------|--------|
| `custom_attributes` | Not used for email records in practice |

### 5.8 Phone ↔ Contacts (polymorphic) (`PhoneFieldMapper`)

v3 source: `contacts` table WHERE `model_type = 'App\\Models\\Lead'` AND `type = 'phone'`
v4 table: `contact_phones` (12 columns)

**Polymorphic extraction logic (v3 → v4):**
```sql
SELECT * FROM contacts
WHERE model_type = 'App\\Models\\Lead'
  AND type = 'phone'
  AND updated_at > :last_poll_time
```

| v4 Column | v3 Column | Transform | Notes |
|-----------|-----------|-----------|-------|
| `id` | — | — | v4 auto-increment |
| `organization_id` | — | Computed | Inherited from parent contact's `organization_id`. |
| `contact_id` | `model_id` | FK remap | v3 `model_id` → remap via `legacy_lead_id` → `contacts.id` |
| `legacy_phone_id` | `id` | Direct | v3 `contacts.id` (polymorphic table PK) |
| `type` | — | Default `'mobile'` | v3 `type = 'phone'` does not distinguish mobile/work/home. Default to `'mobile'`. |
| `phone` | `value` | Rename | v3 `value` → v4 `phone` |
| `is_primary` | `order_column` | Derived | `order_column = 1` or first phone record → `true`; others → `false` |
| `order_column` | `order_column` | Direct | — |
| `synced_at` | — | Set on sync | — |
| `sync_source` | — | Set on sync | — |
| `created_at` | `created_at` | Direct | — |
| `updated_at` | `updated_at` | Direct | — |

**v4 → v3 reverse mapping (writing to polymorphic `contacts` table):**

| v3 Column | v4 Column | Transform |
|-----------|-----------|-----------|
| `model_type` | — | Always `'App\\Models\\Lead'` |
| `model_id` | `contact_id` | Reverse FK: `contacts.id` → `legacy_lead_id` |
| `type` | — | Always `'phone'` |
| `value` | `phone` | Rename |
| `order_column` | `order_column` | Direct |
| `custom_attributes` | — | Null |
| `updated_at` | `updated_at` | Direct |

---

## 6. Conflict Resolution

### Last-Write-Wins Strategy

All bidirectional entities use `updated_at` timestamp comparison to resolve conflicts.

**Algorithm:**

```
1. Poller reads v3 row with updated_at = T_v3
2. Look up v4 record via legacy_*_id
3. Compare T_v3 with v4 record's updated_at = T_v4

IF T_v3 > T_v4 + 1 second:
    → v3 wins → upsert v3 data into v4

IF T_v4 > T_v3 + 1 second:
    → v4 wins → no action (v4 data is already newer)

IF |T_v3 - T_v4| <= 1 second (equal within tolerance):
    → Tiebreaker decides → check config('sync.tiebreaker_source')
    → Phase A: tiebreaker_source = 'v3' → v3 wins
    → Phase B: tiebreaker_source = 'v4' → v4 wins
```

**Prerequisites:**
- NTP required on both servers (pre-flight check in `plan.md`)
- MySQL `updated_at` has second precision; PostgreSQL has microsecond precision. The 1-second tolerance window accounts for this difference.
- `sync.tiebreaker_source` is a config value: `'v3'` during Phase A (v3 is primary), `'v4'` during Phase B (after admin features move to v4)

---

## 7. Delete Handling

### Soft Deletes (bidirectional)

Soft deletes are synced as regular updates. The `deleted_at` column is included in every field mapper.

- **v4 → v3**: Observer fires on model soft-delete event. `SyncToLegacyJob` writes `deleted_at` to the v3 record. If v3 doesn't have a `deleted_at` column on that table, the mapper sets `is_archived = 1` instead.
- **v3 → v4**: Poller detects `deleted_at` change via `updated_at` comparison. Upserts `deleted_at` into v4.

### Hard Deletes

- **Hard deletes in v3**: Detected during full sync (`sync:full`). If a `legacy_*_id` exists in v4 but the corresponding v3 row is gone, the record is logged in `sync_logs` with `action = 'orphaned'` for manual review. No automatic deletion in v4.
- **Hard deletes in v4**: **BLOCKED** during parallel run. Admin policy: soft-delete only while sync is active. Hard delete support is restored at cutover.

---

## 8. Infrastructure

### Code Structure

```
modules/module-crm/src/
  Sync/
    Contracts/
      SyncableContract.php              -- interface for syncable models
    Traits/
      Syncable.php                      -- shared sync behavior ($syncing flag, legacy ID accessor)
    FieldMappers/
      ContactFieldMapper.php            -- Contact ↔ Lead mapping
      CompanyFieldMapper.php            -- Company ↔ Company mapping
      ProjectFieldMapper.php            -- Project ↔ Project mapping
      LotFieldMapper.php                -- Lot ↔ Lot mapping
      SaleFieldMapper.php               -- Sale ↔ Sale mapping
      ReservationFieldMapper.php        -- Reservation ↔ PropertyReservation mapping
      EmailFieldMapper.php              -- Email ↔ Contacts (polymorphic) mapping
      PhoneFieldMapper.php              -- Phone ↔ Contacts (polymorphic) mapping
    Observers/
      ContactSyncObserver.php           -- event-driven v4→v3 for contacts
      CompanySyncObserver.php
      ProjectSyncObserver.php
      LotSyncObserver.php
      SaleSyncObserver.php
      ReservationSyncObserver.php
      EmailSyncObserver.php
      PhoneSyncObserver.php
    Jobs/
      SyncToLegacyJob.php               -- dispatched by observers (v4→v3)
      SyncFromLegacyJob.php             -- dispatched by poller (v3→v4)
      SyncBatchJob.php                  -- scheduled batch for one-way entities
    Commands/
      SyncPollCommand.php               -- polls v3 for changes (every 60s)
      SyncBatchCommand.php              -- batch sync one-way entities (every 30min)
      SyncFullCommand.php               -- full sync (manual, for cutover)
      SyncStatusCommand.php             -- reports sync health (CLI)
```

### Config (`config/sync.php`)

```php
return [
    // Organization ID for PIAB superadmin org (shared/unscoped data)
    'piab_organization_id' => env('SYNC_PIAB_ORG_ID'),

    // Conflict tiebreaker: 'v3' during Phase A, 'v4' during Phase B
    'tiebreaker_source' => env('SYNC_TIEBREAKER', 'v3'),

    // Batch processing chunk size (records per transaction)
    'batch_chunk_size' => env('SYNC_BATCH_CHUNK', 500),

    // Poll interval in seconds (for SyncPollCommand schedule)
    'poll_interval' => env('SYNC_POLL_INTERVAL', 60),

    // Batch interval in seconds (for SyncBatchCommand schedule)
    'batch_interval' => env('SYNC_BATCH_INTERVAL', 1800),
];
```

### Queue

- Dedicated `sync` queue: `SYNC_QUEUE=sync` in `.env`
- Separate worker process: `php artisan queue:work --queue=sync`
- Retry policy: 3 retries with exponential backoff
  - Retry 1: 30 seconds
  - Retry 2: 120 seconds
  - Retry 3: 480 seconds
- Failed jobs after 3 retries: logged to `sync_logs` with `action = 'failed'`, superadmin notified via Laravel notification (email)

### Monitoring

**CLI**: `php artisan sync:status` — shows per-entity sync lag, error count, last sync time, row count comparison.

**Filament Dashboard**: `SyncDashboard` widget in System panel (superadmin only). Displays:
- Sync lag per entity (time since last successful sync)
- Error rate (last 24 hours)
- Conflict count
- Last sync times per entity per direction
- Row count comparison: v3 total vs v4 total per entity

**Alerting**: Failed sync errors with severity `error` trigger a Laravel notification (email to superadmin).

### Sync Log Table

```sql
CREATE TABLE sync_logs (
    id              BIGSERIAL PRIMARY KEY,
    entity_type     VARCHAR(50) NOT NULL,   -- 'contact', 'project', 'lot', etc.
    entity_id       BIGINT NOT NULL,
    direction       VARCHAR(10) NOT NULL,   -- 'v3_to_v4' | 'v4_to_v3'
    action          VARCHAR(10) NOT NULL,   -- 'created' | 'updated' | 'skipped' | 'conflict' | 'failed' | 'orphaned'
    legacy_id       BIGINT,
    payload         JSONB,
    synced_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    error           TEXT
);

CREATE INDEX idx_sync_logs_entity ON sync_logs (entity_type, entity_id);
CREATE INDEX idx_sync_logs_synced_at ON sync_logs (synced_at);
```

Entries are append-only. Duplicate entries for the same record are acceptable (audit trail). The `payload` column stores the field values at sync time for debugging.

---

## 9. Organization Mapping

### Core Concept

Each v3 subscriber Lead becomes a v4 Organization. All v4 CRM tables have a mandatory `organization_id` column for multi-tenant scoping.

### Subscriber Identification

v3 identifies subscribers via the `relationships` pivot table:

```sql
-- Find subscriber leads in v3
SELECT r.account_id AS lead_id
FROM relationships r
INNER JOIN roles ON roles.id = r.relation_type
WHERE roles.name = 'subscriber'   -- or equivalent subscriber role name
```

Each subscriber Lead has a corresponding User (`users.lead_id = leads.id`). During migration, each subscriber Lead → User → new Organization (user becomes org owner).

### Per-Table Derivation Rules

| Table | Scoping Column in v3 | Resolution Chain | v4 `organization_id` |
|-------|----------------------|------------------|---------------------|
| `leads` → `contacts` | `relationships` pivot | Lead → check relationships for subscriber → Lead → User → Org | Subscriber's org, or PIAB org if no subscriber relationship |
| `companies` | None (shared) | — | PIAB org |
| `projects` | None (shared) | — | PIAB org (shared marketplace listings) |
| `lots` | None (inherited) | `lot.project_id` → Project → `organization_id` | Same as parent project (PIAB org for existing data) |
| `sales` | `subscriber_id` (447/449 rows) | `subscriber_id` → Lead → User → Org | Subscriber's org |
| `sales` (no subscriber_id) | `created_by` → `leads.id` | `created_by` → Lead → User → Org | Derived from creator lead |
| `property_reservations` | `loggedin_agent_id` → `leads.id` | `loggedin_agent_id` → Lead → User → Org | Agent's org |
| `contacts` (emails) | `model_id` → `leads.id` | model_id → Lead → Contact → `organization_id` | Same as parent contact |
| `contacts` (phones) | `model_id` → `leads.id` | Same | Same as parent contact |
| `users` | `lead_id` → `leads.id` | `lead_id` → Lead → Contact → org resolution | User's org (via subscriber check on lead) |
| `commissions` (polymorphic) | `commissionable_id` → project/lot | Via parent project/lot → `organization_id` | Same as parent entity |
| `notes` | `author_id` → `users.id` | `author_id` → User → Org | Author's org |
| `project_updates` | `project_id` | `project_id` → Project → `organization_id` | Same as parent project |
| `activity_log` | `causer_id` → `users.id` | `causer_id` → User → Org | Causer's org |

### Platform Leads

v3 leads that are NOT subscribers and NOT associated with any subscriber get:
- `organization_id` = PIAB org
- `contact_origin` = `'saas_product'`

These are platform-level leads managed by superadmin.

### Config

```php
// config/sync.php
'piab_organization_id' => env('SYNC_PIAB_ORG_ID'),
```

This value must be set before sync starts. It references the PIAB superadmin organization created during v4 seeding.

---

## 10. Cutover & Rollback

### Cutover Steps

1. **Announce** cutover date to all users
2. **Run full sync**: `php artisan sync:full`
3. **Verify** row counts match between v3 and v4 per entity: `php artisan sync:status`
4. **Disable** all sync observers in `CrmServiceProvider` (comment out observer registrations)
5. **Remove** scheduled sync commands from `routes/console.php`
6. **Drop** `mysql_legacy` connection from `config/database.php`
7. **v4 is standalone** — v3 can be archived

### Rollback Steps

If v4 has critical issues post-cutover:

1. **Re-enable** `mysql_legacy` connection in `config/database.php`
2. **Run reverse sync**: `php artisan sync:full --direction=v4_to_v3`
3. **Point** DNS/users back to v3
4. **Investigate** and fix v4 issues
5. **Re-attempt** cutover when ready

### `SyncFullCommand` Flags

```
php artisan sync:full                           # Both directions (default)
php artisan sync:full --direction=v3_to_v4      # v3 → v4 only
php artisan sync:full --direction=v4_to_v3      # v4 → v3 only (for rollback)
```

### v3 Index Additions

The following indexes must be added to v3 **before sync goes live**. These are the only changes to v3 — non-destructive, read-only performance improvements for the poller queries.

```sql
-- Run manually on v3 MySQL (one-time, requires human approval)
ALTER TABLE leads ADD INDEX idx_leads_updated_at (updated_at);
ALTER TABLE companies ADD INDEX idx_companies_updated_at (updated_at);
ALTER TABLE projects ADD INDEX idx_projects_updated_at (updated_at);
ALTER TABLE lots ADD INDEX idx_lots_updated_at (updated_at);
ALTER TABLE sales ADD INDEX idx_sales_updated_at (updated_at);
ALTER TABLE property_reservations ADD INDEX idx_reservations_updated_at (updated_at);
ALTER TABLE contacts ADD INDEX idx_contacts_updated_at (updated_at);
```

**Note on the last index**: `contacts` here is the v3 **polymorphic** contacts table (stores emails, phones, websites for leads and projects). This is NOT the v4 `contacts` table (which maps to v3 `leads`). The poller queries this table for email/phone sync:

```sql
-- Email poll query
SELECT * FROM contacts
WHERE model_type = 'App\\Models\\Lead'
  AND type LIKE 'email%'
  AND updated_at > :last_poll_time;

-- Phone poll query
SELECT * FROM contacts
WHERE model_type = 'App\\Models\\Lead'
  AND type = 'phone'
  AND updated_at > :last_poll_time;
```

The 7 indexes above replace the 8 originally specified in the spec (which incorrectly referenced `lead_emails` and `lead_phones` tables that do not exist). The single `contacts` table index covers both email and phone polling.
