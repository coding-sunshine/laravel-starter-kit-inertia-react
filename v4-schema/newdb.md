# FusionCRM v4 - New Database Structure & Migration Map

> **Generated**: 2026-03-23 | **Stack**: Laravel 13, PostgreSQL 16, pgvector
> **Source**: v3 live data analysis (88 tables) + rebuild plan (27 files) + starter kit (~125 tables)
> **Starter Kit**: [laravel-starter-kit-inertia-react](https://github.com/coding-sunshine/laravel-starter-kit-inertia-react)
> **Scope**: Only tables that EXIST in v4 and their v3 mapping (if any)
>
> **Code structure**: All CRM code in `modules/module-crm/` (models, migrations, controllers, commands).
> All CRM models use namespace `Cogneiss\ModuleCrm\Models\*` and `BelongsToOrganization` trait for multi-tenant scoping.
> CRM UI = Inertia + React pages. Filament = admin lookup management only. See `plan.md` for details.
>
> **AI**: `laravel/ai` (wraps `prism-php/prism`) — primary SDK for AI agents and tools.
>
> **Multi-tenancy**: Single-tenant (v3) → Multi-tenant per org (v4). Each v3 subscriber → v4 Organization.
> **Sync**: No sync (v3) → Bidirectional v3↔v4 sync during parallel-run period. See `sync-architecture.md`.
>
> **PostgreSQL**: Target **PostgreSQL 16** (see [§9](#9-postgresql-best-practices--operations) for constraints, pooling, bulk load, monitoring). Generic Postgres topics align with [PlanetScale database skills](https://github.com/planetscale/database-skills/tree/main/skills/postgres/references) (schema design, indexing, query patterns, MVCC/VACUUM, backups, replication).

---

## Table of Contents

1. [v3 to v4 At a Glance](#1-v3-to-v4-at-a-glance)
2. [What We Are NOT Migrating](#2-what-we-are-not-migrating)
3. [v4 Tables - CRM Domain (Data Migrated from v3)](#3-v4-tables---crm-domain-data-migrated-from-v3)
4. [v4 Tables - Starter Kit (Pre-Built, No Migration)](#4-v4-tables---starter-kit-pre-built-no-migration)
5. [Column-Level Migration Maps](#5-column-level-migration-maps)
6. [Relationship Diagrams](#6-relationship-diagrams)
7. [Data Volume & Migration Order](#7-data-volume--migration-order)
8. [Index Strategy](#8-index-strategy)
9. [PostgreSQL Best Practices & Operations](#9-postgresql-best-practices--operations)
10. [MySQL→PostgreSQL Type Mapping](#10-mysqlpostgresql-type-mapping)

---

## 1. v3 to v4 At a Glance

```
    v3 (MySQL)                              v4 (PostgreSQL)
    ----------                              ---------------
    88 tables                    -->        ~170 total (45 v4 CRM tables + ~125 starter kit)
    821+ columns                 -->        ~400 CRM columns (est.)
    30% FK constraints           -->        100% FK constraints
    20% indexed FKs              -->        100% indexed FKs
    0 CHECK constraints          -->        CHECK on all enums
    0 transactions               -->        All multi-step wrapped
    0 eager loading              -->        preventLazyLoading()
    5 polymorphic tables         -->        2 (notes + comments)
    63-col projects              -->        5 focused tables
    7 commission flat cols       -->        Normalized commissions table
    148K statusables             -->        State column per model
    Lead (God Object, 25 rels)   -->        Contact (trait-based, focused)
    Aecor package                -->        Spatie ecosystem
    No morph map                 -->        enforceMorphMap()
    MySQL only                   -->        PostgreSQL + pgvector
    Nova (2 resources)           -->        Filament 5 (superadmin)
    WordPress creation           -->        NOT moving to v4
    Custom AI bots               -->        laravel/ai agents
    Single-tenant               -->        Multi-tenant per org
    No sync                     -->        Bidirectional v3↔v4 sync
```

### Migration Summary

```
    +-------------------------------------------------+
    |         v3 TABLE DISPOSITION (88 tables)         |
    +-------------------------------------------------+
    |  In Section 2 (accounted for):  65 tables        |
    |    23 = dead/unused (2A)                         |
    |     3 = WordPress not moving (2B)                |
    |     1 = reference data skipped (2B-2)            |
    |     3 = AI bots replaced by laravel/ai (2C)      |
    |     9 = replaced by starter kit (2D)             |
    |     5 = data absorbed into other v4 tables (2E)  |
    |     6 = framework/auth recreated fresh (2F)      |
    |     2 = stale (2G)                               |
    |     1 = love_reaction_types (2F)                 |
    |    12 = rebuild separately in v4 (2I)            |
    |  In Section 3 (migrated directly):  23 tables    |
    |                                                   |
    |  65 + 23 = 88 (all v3 tables accounted for)      |
    |  v4 CRM destination tables: 45                    |
    |    (33 original + 12 builder portal tables)      |
    +-------------------------------------------------+
```

---

## 2. What We Are NOT Migrating

### 2A. Dead/Unused Features (23 tables - 0 business value)

| Table | Rows | Why Skipping |
| --- | --- | --- |
| action_events | 0 | Nova audit log - Nova not used |
| default_fonts | 0 | Never populated |
| failed_jobs | 0 | Recreate fresh in v4 |
| model_has_permissions | 0 | Individual permissions never used |
| oauth_access_tokens | 0 | Passport never used |
| oauth_auth_codes | 0 | Passport never used |
| oauth_clients | 0 | Passport never used |
| oauth_personal_access_clients | 0 | Passport never used |
| oauth_refresh_tokens | 0 | Passport never used |
| personal_access_tokens | 0 | Sanctum tokens never used |
| settings | 0 | Never populated |
| taggables | 0 | Tags never used |
| tags | 0 | Tags never used |
| team_user | 0 | Teams never used |
| teams | 0 | Teams never used |
| widget_settings | 0 | Never used |
| campaign_website_templates | 1 | 1 seed row, 5 years stale |
| finance_assessments | 1 | Feature never launched |
| potential_properties | 1 | Feature never launched |
| flyer_templates | 3 | Seed data only |
| spr_requests | 9 | 9 records in 3.5 years |
| survey_questions | 7 | Seed data only |
| questionnaires | 31 | 31 records in 4 years - barely adopted |

### 2B. WordPress System (3 tables - NOT moving to v4)

| Table | Rows | Reason |
| --- | --- | --- |
| wordpress_websites | 207 | WordPress site creation not moving to v4 |
| wordpress_templates | 4 | Part of WP system |
| onlineform_contacts | 19 | WP form submissions - not migrated |

### 2B-2. Skipped Reference Data (1 table)

| Table | Rows | Reason |
| --- | --- | --- |
| au_towns | 15,299 | Geographic ref data with population/elevation - NOT the same as suburbs (which has postcode/lat/lng and IS migrated). Starter kit has `devrabiul/laravel-geo-genius` if population data needed later |

### 2C. AI Bot System (3 tables - replaced by laravel/ai)

| v3 Table | Rows | Replacement |
| --- | --- | --- |
| ai_bot_boxes | 46 | `laravel/ai` agents in `app/Ai/Agents/` |
| ai_bot_categories | 11 | Agent config files |
| ai_bot_prompt_commands | 481 | Export as AI agent tool config, then discard table |

### 2D. Replaced by Starter Kit Equivalents (9 tables - no data migration needed)

| v3 Table | Rows | Starter Kit Replacement |
| --- | --- | --- |
| column_management | 4,543 | `data_table_saved_views` - users will reconfigure |
| ad_managements | 11 | `advertisements` + `ad_templates` - rebuild fresh |
| login_histories | 42,414 | `login_events` - fresh audit trail |
| user_api_tokens | 1,503 | `personal_access_tokens` (Sanctum) |
| user_api_ips | 129 | Sanctum + middleware IP restriction |
| file_types | 21 | Spatie media library handles file types |
| activity_log | 464,767 | Fresh `spatie/activitylog` in v4 - too large to migrate, start fresh |
| developer_state | 356 | Rebuild as needed (developer <-> state relationship) |
| password_resets | 30 | `password_reset_tokens` (Laravel default, recreated fresh) |

### 2E. Polymorphic/Absorbed Tables (5 tables - data absorbed into other v4 tables)

| v3 Table | Rows | What Happens to Data |
| --- | --- | --- |
| statuses | 214 | Status definitions discarded - `spatie/laravel-model-states` replaces |
| statusables | 149,201 | LATEST status per record -> model column (contacts.stage, lots.stage, etc.) |
| addresses | 24,745 | Flattened into parent table columns (contacts + projects address fields) |
| contacts (polymorphic) | 20,190 | Split into contact_emails + contact_phones + contacts.extra_attributes. **Source**: polymorphic `contacts` table with `model_type='App\Models\Lead'` and `type LIKE 'email%'` or `type='phone'`. NOT from `lead_emails`/`lead_phones` (which do not exist in v3) |
| commissions (polymorphic) | 19,602 | Project/Lot commissions -> commission_templates; Sale commissions -> normalized commissions table |

**Note**: These tables' DATA is migrated into other v4 tables. The v3 tables themselves are not recreated in v4. The 5 `love_*` tables (love_reactants: 16,150; love_reacters: 1,511; love_reactions: 2,199; love_reactant_reaction_counters: 2,012; love_reactant_reaction_totals: 2,012) are absorbed into `project_favorites` pivot.

### 2F. Framework/Auth Tables (6 tables - recreated fresh or replaced)

| v3 Table | Rows | What Happens |
| --- | --- | --- |
| migrations | 156 | Framework table - recreated fresh in v4 |
| sessions | 56 | Framework table - recreated fresh in v4 |
| permissions | 113 | Replaced by Spatie Permission permissions |
| roles | 22 | Replaced by Spatie Permission roles |
| role_has_permissions | 396 | Replaced by Spatie Permission |
| love_reaction_types | 1 | Only 1 type ("Like") - not needed |

### 2G. Stale/Low-Value Tables (2 tables)

| Table | Rows | Last Updated | Why Skipping |
| --- | --- | --- | --- |
| services | 30 | 2021-07-27 | 5 years stale, not referenced |
| company_service | 44 | N/A | Pivot for stale services |

### 2H. Features Deferred (not built in v4 initially)

| Feature | Reason | When to Revisit |
| --- | --- | --- |
| Airtable sync | Explicitly deferred in rebuild plan | If requested |
| Payment processing | No credentials available | When credentials provided (`stripe/stripe-php` ready) |
| WordPress creation | Not moving to v4 | If WP integration needed later |

### 2I. Rebuild Separately in v4 (12 tables - not migrated from v3)

These features will be rebuilt from scratch in v4. Data is NOT migrated.

| Table | v3 Rows | Reason |
| --- | --- | --- |
| flyers | 10,648 | Rebuild with spatie/laravel-pdf in v4 |
| campaign_websites | 200 | Rebuild as part of v4 website builder |
| campaign_website_project | 328 | Pivot for campaign_websites |
| websites | 574 | Rebuild as part of v4 website builder (Step 15) |
| website_pages | 137 | Child of websites |
| website_elements | 705 | Child of website_pages |
| website_contacts | 1,239 | Child of websites |
| mail_lists | 74 | Rebuild as part of v4 marketing module (Step 13) |
| mail_list_contacts | NEW | Was to be split from mail_lists arrays |
| brochure_mail_jobs | 191 | Rebuild as part of v4 marketing module |
| mail_job_statuses | 184 | Child of brochure_mail_jobs |
| email_settings | 44 | Rebuild as part of v4 marketing module |

### Quick Reference: NOT in v4

```
    +-----------------------------------------------------------+
    |              FEATURES NOT CARRIED TO v4                    |
    +-----------------------------------------------------------+
    |                                                           |
    |  DEAD (23 tables):                                        |
    |    OAuth/Passport, Teams, Tags(empty), Nova, Permissions  |
    |    Finance assessments, Potential properties, Surveys      |
    |    Templates (campaign/flyer/WP), Fonts, Widgets, SPR     |
    |                                                           |
    |  NOT MOVING:                                              |
    |    WordPress websites + templates + online forms           |
    |    au_towns (population/elevation data - geo-genius)       |
    |    NOTE: suburbs (postcode/lat/lng) IS migrated           |
    |                                                           |
    |  REPLACED BY STARTER KIT:                                 |
    |    column_management -> data_table_saved_views             |
    |    ad_managements -> advertisements + ad_templates         |
    |    login_histories -> login_events                         |
    |    user_api_tokens -> Sanctum personal_access_tokens       |
    |    file_types -> Spatie media library                      |
    |                                                           |
    |  FLATTENED (data moves, table doesn't):                   |
    |    statuses + statusables -> model state columns           |
    |    addresses -> parent table address columns               |
    |    contacts (polymorphic) -> emails + phones + JSONB       |
    |    commissions (polymorphic) -> normalized table           |
    |                                                           |
    |  STALE: services + company_service (5 years untouched)    |
    |                                                           |
    |  DEFERRED: Airtable, Payments, WordPress                  |
    |                                                           |
    |  REBUILD SEPARATELY IN v4 (12 tables):                    |
    |    flyers, campaign_websites, campaign_website_project,    |
    |    websites, website_pages, website_elements,              |
    |    website_contacts, mail_lists, mail_list_contacts,       |
    |    brochure_mail_jobs, mail_job_statuses, email_settings   |
    |                                                           |
    |  FRAMEWORK/AUTH: migrations, sessions, permissions,        |
    |    roles, role_has_permissions, love_reaction_types        |
    |                                                           |
    |  AI BOTS: ai_bot_boxes, ai_bot_categories,                |
    |    ai_bot_prompt_commands (-> laravel/ai)                  |
    |                                                           |
    |  TOTAL: 65 v3 tables NOT migrated as own tables           |
    |  (5 of those have data absorbed into other v4 tables)     |
    |  (5 love_* tables absorbed into project_favorites)        |
    +-----------------------------------------------------------+
```

---

## 3. v4 Tables - CRM Domain (Data Migrated from v3)

> These tables have data imported from v3. Each shows the v3 source and mapping.
> All CRM table names are **unprefixed** (e.g. `contacts`, not `crm_contacts`). The `module-crm` module namespace provides sufficient scoping.
> The starter kit's generic scaffold tables (`crm_contacts`, `crm_deals`, `crm_pipelines`, `crm_activities`) are **dropped and replaced** by these property/real-estate domain tables.

### 3.1 Contacts (replaces leads)

```
v3 Source: leads (9,750 rows)
          + contacts/detail (20,190 rows -> split into emails/phones)
              NOTE: emails/phones come from polymorphic `contacts` table
              (model_type='App\Models\Lead', type LIKE 'email%' or type='phone')
              NOT from `lead_emails`/`lead_phones` tables (which do not exist in v3)
          + addresses (9,165 lead addresses -> flattened)
          + statusables (7,675 -> contacts.stage)
Sync: ⇄ Bidirectional
```

```sql
CREATE TABLE contacts (
    id                  BIGSERIAL PRIMARY KEY,
    organization_id     BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    legacy_lead_id      BIGINT UNIQUE,
    contact_origin      VARCHAR(20) NOT NULL DEFAULT 'property'
                        CHECK (contact_origin IN ('property', 'saas_product')),
    first_name          VARCHAR(255) NOT NULL,
    last_name           VARCHAR(255),
    job_title           VARCHAR(255),
    type                VARCHAR(30) NOT NULL DEFAULT 'lead'
                        CHECK (type IN ('lead','client','agent','partner',
                                       'subscriber','bdm','sub_agent')),
    stage               VARCHAR(30) NOT NULL DEFAULT 'new',   -- spatie/model-states
    source_id           BIGINT REFERENCES sources(id) ON DELETE SET NULL,
    company_id          BIGINT REFERENCES companies(id) ON DELETE SET NULL,
    lead_score          SMALLINT DEFAULT 0 CHECK (lead_score BETWEEN 0 AND 100),
    last_contacted_at   TIMESTAMPTZ,
    next_followup_at    TIMESTAMPTZ,
    last_followup_at    TIMESTAMPTZ,
    -- Address (flattened from v3 addresses table)
    address_line1       VARCHAR(255),
    address_line2       VARCHAR(255),
    city                VARCHAR(255),
    state               VARCHAR(50),
    postcode            VARCHAR(10),
    country             VARCHAR(50) DEFAULT 'AU',
    extra_attributes    JSONB DEFAULT '{}',
    -- Sync columns
    synced_at           TIMESTAMPTZ,
    sync_source         VARCHAR(10),   -- 'v3', 'v4', 'initial'
    created_by          BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by          BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at          TIMESTAMPTZ,
    updated_at          TIMESTAMPTZ,
    deleted_at          TIMESTAMPTZ
);

CREATE INDEX idx_contacts_org ON contacts(organization_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_contacts_org_stage ON contacts(organization_id, stage) WHERE deleted_at IS NULL;
CREATE INDEX idx_contacts_org_type ON contacts(organization_id, type) WHERE deleted_at IS NULL;
CREATE INDEX idx_contacts_legacy ON contacts(legacy_lead_id) WHERE legacy_lead_id IS NOT NULL;
CREATE INDEX idx_contacts_legacy_updated ON contacts(legacy_lead_id, updated_at) WHERE legacy_lead_id IS NOT NULL;
CREATE INDEX idx_contacts_source ON contacts(source_id);
CREATE INDEX idx_contacts_company ON contacts(company_id);
CREATE INDEX idx_contacts_next_followup ON contacts(next_followup_at) WHERE deleted_at IS NULL;
CREATE INDEX idx_contacts_extra ON contacts USING GIN (extra_attributes);
CREATE INDEX idx_contacts_origin ON contacts(contact_origin) WHERE deleted_at IS NULL;
```

**`organization_id` derivation**: For v3 leads with a `subscriber_id` (via `relationships` pivot), resolve `subscriber_id → Lead → User → Organization`. For leads without subscriber relationship, assign to PIAB org (`sync.piab_organization_id`). Platform leads (non-subscriber, non-property) get `contact_origin = 'saas_product'`.

### 3.2 Contact Emails

```
v3 Source: contacts (polymorphic) WHERE model_type='App\Models\Lead' AND type LIKE 'email%'
           (email_1: 9,163 rows, email_2: 567 rows)
           NOTE: NOT from `lead_emails` table (does not exist in v3)
Sync: ⇄ Bidirectional
```

```sql
CREATE TABLE contact_emails (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    contact_id      BIGINT NOT NULL REFERENCES contacts(id) ON DELETE CASCADE,
    legacy_email_id BIGINT UNIQUE,
    type            VARCHAR(20) NOT NULL DEFAULT 'work'
                    CHECK (type IN ('work', 'home', 'other')),
    email           VARCHAR(255) NOT NULL,
    is_primary      BOOLEAN DEFAULT false,
    order_column    SMALLINT DEFAULT 0,
    synced_at       TIMESTAMPTZ,
    sync_source     VARCHAR(10),
    created_at      TIMESTAMPTZ,
    updated_at      TIMESTAMPTZ
);
CREATE INDEX idx_contact_emails_org ON contact_emails(organization_id);
CREATE INDEX idx_contact_emails_contact ON contact_emails(contact_id);
CREATE INDEX idx_contact_emails_lower ON contact_emails (LOWER(email));
CREATE INDEX idx_contact_emails_legacy ON contact_emails(legacy_email_id) WHERE legacy_email_id IS NOT NULL;
CREATE INDEX idx_contact_emails_legacy_updated ON contact_emails(legacy_email_id, updated_at) WHERE legacy_email_id IS NOT NULL;
-- At most one primary email per contact (partial unique index)
CREATE UNIQUE INDEX idx_contact_emails_one_primary ON contact_emails (contact_id) WHERE is_primary = true;
```

### 3.3 Contact Phones

```
v3 Source: contacts (polymorphic) WHERE model_type='App\Models\Lead' AND type='phone'
           (9,601 rows)
           NOTE: NOT from `lead_phones` table (does not exist in v3)
Validation: propaganistas/laravel-phone
Sync: ⇄ Bidirectional
```

```sql
CREATE TABLE contact_phones (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    contact_id      BIGINT NOT NULL REFERENCES contacts(id) ON DELETE CASCADE,
    legacy_phone_id BIGINT UNIQUE,
    type            VARCHAR(20) NOT NULL DEFAULT 'mobile'
                    CHECK (type IN ('work', 'home', 'mobile', 'other')),
    phone           VARCHAR(50) NOT NULL,
    is_primary      BOOLEAN DEFAULT false,
    order_column    SMALLINT DEFAULT 0,
    synced_at       TIMESTAMPTZ,
    sync_source     VARCHAR(10),
    created_at      TIMESTAMPTZ,
    updated_at      TIMESTAMPTZ
);
CREATE INDEX idx_contact_phones_org ON contact_phones(organization_id);
CREATE INDEX idx_contact_phones_contact ON contact_phones(contact_id);
CREATE INDEX idx_contact_phones_legacy ON contact_phones(legacy_phone_id) WHERE legacy_phone_id IS NOT NULL;
CREATE INDEX idx_contact_phones_legacy_updated ON contact_phones(legacy_phone_id, updated_at) WHERE legacy_phone_id IS NOT NULL;
-- At most one primary phone per contact
CREATE UNIQUE INDEX idx_contact_phones_one_primary ON contact_phones (contact_id) WHERE is_primary = true;
```

### 3.4 Projects (decomposed from 63 cols)

```
v3 Source: projects (15,566 rows, 63 columns -> split into 5 tables)
          + addresses (15,480 project addresses -> flattened into projects)
          + statusables (16,065 -> projects.stage)
Sync: ⇄ Bidirectional
```

```sql
CREATE TABLE projects (
    id                  BIGSERIAL PRIMARY KEY,
    organization_id     BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    legacy_project_id   BIGINT UNIQUE,
    title               VARCHAR(255) NOT NULL,
    description         TEXT,
    stage               VARCHAR(30) NOT NULL DEFAULT 'active',
    estate              VARCHAR(255),
    developer_id        BIGINT REFERENCES developers(id) ON DELETE SET NULL,
    projecttype_id      BIGINT REFERENCES projecttypes(id) ON DELETE SET NULL,
    -- Address (flattened from v3 addresses)
    address_line1       VARCHAR(255),
    address_line2       VARCHAR(255),
    city                VARCHAR(255),
    state               VARCHAR(50),
    postcode            VARCHAR(10),
    latitude            NUMERIC(10,7),
    longitude           NUMERIC(10,7),
    extra_attributes    JSONB DEFAULT '{}',  -- is_featured, is_hot_property, land_info, property_conditions, trust_details, build_time
    -- Sync columns
    synced_at           TIMESTAMPTZ,
    sync_source         VARCHAR(10),
    created_by          BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by          BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at          TIMESTAMPTZ,
    updated_at          TIMESTAMPTZ,
    deleted_at          TIMESTAMPTZ
);
CREATE INDEX idx_projects_org ON projects(organization_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_projects_org_stage ON projects(organization_id, stage) WHERE deleted_at IS NULL;
CREATE INDEX idx_projects_legacy ON projects(legacy_project_id) WHERE legacy_project_id IS NOT NULL;
CREATE INDEX idx_projects_legacy_updated ON projects(legacy_project_id, updated_at) WHERE legacy_project_id IS NOT NULL;
CREATE INDEX idx_projects_developer ON projects(developer_id);
CREATE INDEX idx_projects_extra ON projects USING GIN (extra_attributes);
```

**`organization_id` derivation**: v3 projects are unscoped shared data. Set to PIAB org (`sync.piab_organization_id`). Subscriber-created projects in v4 get the subscriber's `organization_id`.

### 3.5 Project Specs (1:1)

```
v3 Source: projects (building spec columns)
```

```sql
CREATE TABLE project_specs (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    project_id      BIGINT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    -- bedrooms/bathrooms: use min/max only (single values are 0.7%/0.6% fill)
    min_bedrooms    SMALLINT,
    max_bedrooms    SMALLINT,
    min_bathrooms   SMALLINT,
    max_bathrooms   SMALLINT,
    -- DROPPED: bedrooms (0.7%), bathrooms (0.6%), car_spaces/garage (0%), storeys (0.1%)
    -- These exist at lot level where they're well-populated
    living_area     NUMERIC(8,2),
    min_living_area NUMERIC(8,2),
    max_living_area NUMERIC(8,2),
    internal_area   NUMERIC(8,2),
    external_area   NUMERIC(8,2),
    land_area       NUMERIC(10,2),
    min_land_area   NUMERIC(10,2),
    max_land_area   NUMERIC(10,2),
    frontage        NUMERIC(8,2),
    depth           NUMERIC(8,2),
    created_at      TIMESTAMPTZ,
    updated_at      TIMESTAMPTZ
);
CREATE UNIQUE INDEX idx_project_specs_project ON project_specs(project_id);
CREATE INDEX idx_project_specs_org ON project_specs(organization_id);
```

### 3.6 Project Investment (1:1)

```
v3 Source: projects (financial metric columns)
```

```sql
CREATE TABLE project_investment (
    id                  BIGSERIAL PRIMARY KEY,
    organization_id     BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    project_id          BIGINT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    min_rent            NUMERIC(10,2),
    max_rent            NUMERIC(10,2),
    avg_rent            NUMERIC(10,2),
    min_rent_yield      NUMERIC(5,2),
    max_rent_yield      NUMERIC(5,2),
    avg_rent_yield      NUMERIC(5,2),
    rent_to_sell_yield  NUMERIC(5,2),
    cap_rate            NUMERIC(5,2),
    avg_price           NUMERIC(12,2),
    historical_growth   NUMERIC(5,2),
    is_cashflow_positive BOOLEAN DEFAULT false,
    cashflow_weekly     NUMERIC(10,2),
    cashflow_monthly    NUMERIC(10,2),
    cashflow_annual     NUMERIC(12,2),
    depreciation_yr1    NUMERIC(12,2),
    depreciation_yr5    NUMERIC(12,2),
    created_at          TIMESTAMPTZ,
    updated_at          TIMESTAMPTZ
);
CREATE UNIQUE INDEX idx_project_investment_project ON project_investment(project_id);
CREATE INDEX idx_project_investment_org ON project_investment(organization_id);
```

### 3.7 Project Features (1:1)

```
v3 Source: projects (boolean flag columns)
```

```sql
CREATE TABLE project_features (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    project_id      BIGINT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    is_co_living    BOOLEAN DEFAULT false,
    is_ndis         BOOLEAN DEFAULT false,
    is_smsf         BOOLEAN DEFAULT false,
    -- DROPPED: is_flexi (0% filled)
    is_exclusive    BOOLEAN DEFAULT false,
    is_rent_to_sell BOOLEAN DEFAULT false,
    is_rooming      BOOLEAN DEFAULT false,
    is_firb         BOOLEAN DEFAULT false,
    is_house_land   BOOLEAN DEFAULT false,
    is_off_plan     BOOLEAN DEFAULT false,
    created_at      TIMESTAMPTZ,
    updated_at      TIMESTAMPTZ
);
CREATE UNIQUE INDEX idx_project_features_project ON project_features(project_id);
CREATE INDEX idx_project_features_org ON project_features(organization_id);
```

### 3.8 Project Pricing (1:1)

```
v3 Source: projects (pricing columns)
Money: akaunting/laravel-money
```

```sql
CREATE TABLE project_pricing (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    project_id      BIGINT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    min_price       NUMERIC(12,2),
    max_price       NUMERIC(12,2),
    min_land_price  NUMERIC(12,2),
    max_land_price  NUMERIC(12,2),
    deposit_amount  NUMERIC(12,2),
    deposit_percent NUMERIC(5,2),
    stamp_duty      NUMERIC(12,2),
    rates_fees      NUMERIC(10,2),
    min_rates_fees  NUMERIC(10,2),
    max_rates_fees  NUMERIC(10,2),
    body_corporate_fees NUMERIC(10,2),
    min_body_corporate_fees NUMERIC(10,2),
    max_body_corporate_fees NUMERIC(10,2),
    created_at      TIMESTAMPTZ,
    updated_at      TIMESTAMPTZ
);
CREATE UNIQUE INDEX idx_project_pricing_project ON project_pricing(project_id);
CREATE INDEX idx_project_pricing_org ON project_pricing(organization_id);
```

### 3.9 Lots

```
v3 Source: lots (121,755 rows)
          + statusables (123,534 -> lots.stage)
Money: akaunting/laravel-money
Sync: ⇄ Bidirectional
Note: v3 column `storyes` is a typo for `storeys` — fixed in v4
```

```sql
CREATE TABLE lots (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    project_id      BIGINT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    legacy_lot_id   BIGINT UNIQUE,
    lot_number      VARCHAR(50),
    title           VARCHAR(255),
    price           NUMERIC(12,2),
    land_price      NUMERIC(12,2),
    build_price     NUMERIC(12,2),
    stage           VARCHAR(30) DEFAULT 'available'
                    CHECK (stage IN ('available','reserved','under_contract',
                                    'settled','sold','withdrawn')),
    bedrooms        SMALLINT,
    bathrooms       SMALLINT,
    car_spaces      SMALLINT,
    storeys         SMALLINT,       -- v3: `storyes` (typo fixed)
    internal_area   NUMERIC(8,2),
    external_area   NUMERIC(8,2),
    land_area       NUMERIC(10,2),
    rent_weekly     NUMERIC(8,2),
    rent_yield      NUMERIC(5,2),
    rent_to_sell_yield NUMERIC(5,2),
    title_status    VARCHAR(50),
    title_date      DATE,
    completion_date DATE,
    level           VARCHAR(50),
    aspect          VARCHAR(100),
    view            VARCHAR(255),
    building_name   VARCHAR(255),
    balcony_area    NUMERIC(8,2),
    living_area     NUMERIC(8,2),
    garage          SMALLINT,
    body_corporate  NUMERIC(10,2),
    rates           NUMERIC(10,2),
    mpr             NUMERIC(10,2),
    floorplan_url   VARCHAR(500),
    uuid            UUID,
    total_price     NUMERIC(12,2),
    is_study        BOOLEAN DEFAULT false,
    is_storage      BOOLEAN DEFAULT false,
    is_powder_room  BOOLEAN DEFAULT false,
    is_nras         BOOLEAN DEFAULT false,
    is_cashflow_positive BOOLEAN DEFAULT false,
    is_smsf         BOOLEAN DEFAULT false,
    -- DROPPED: five_percent_share_* (28/121K = 0%), sub_agent_comms (0%)
    -- Sync columns
    synced_at       TIMESTAMPTZ,
    sync_source     VARCHAR(10),
    created_by      BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by      BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ,
    updated_at      TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ
);
CREATE INDEX idx_lots_project_stage ON lots(project_id, stage) WHERE deleted_at IS NULL;
CREATE INDEX idx_lots_org ON lots(organization_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_lots_legacy ON lots(legacy_lot_id) WHERE legacy_lot_id IS NOT NULL;
CREATE INDEX idx_lots_legacy_updated ON lots(legacy_lot_id, updated_at) WHERE legacy_lot_id IS NOT NULL;
CREATE INDEX idx_lots_list ON lots(project_id) INCLUDE (lot_number, price, stage);
```

**`lots.organization_id` invariant:** Denormalized for tenant-scoped queries and optional RLS. Keep it equal to `projects.organization_id` for the row's `project_id`. Recommended: a `BEFORE INSERT OR UPDATE` trigger on `lots` that sets `NEW.organization_id` from `projects.organization_id` (see [§9.2](#92-data-integrity--triggers)).

```sql
CREATE OR REPLACE FUNCTION trg_lots_sync_organization_id()
RETURNS TRIGGER AS $$
BEGIN
    SELECT organization_id INTO STRICT NEW.organization_id FROM projects WHERE id = NEW.project_id;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER lots_sync_organization_id
    BEFORE INSERT OR UPDATE ON lots
    FOR EACH ROW
    EXECUTE FUNCTION trg_lots_sync_organization_id();
```

### 3.10 Sales

```
v3 Source: sales (449 rows)
          + statusables (447 -> sales.status)
          Flat commission columns REMOVED -> normalized commissions table
Sync: ⇄ Bidirectional
NOTE: v3 `Sale.created_by` references `leads.id` (NOT `users.id`)
      Organization derivation: created_by → Lead → User → Organization
```

```sql
CREATE TABLE sales (
    id                              BIGSERIAL PRIMARY KEY,
    organization_id                 BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    legacy_sale_id                  BIGINT UNIQUE,
    client_contact_id               BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    lot_id                          BIGINT REFERENCES lots(id) ON DELETE SET NULL,
    project_id                      BIGINT REFERENCES projects(id) ON DELETE SET NULL,
    developer_id                    BIGINT REFERENCES developers(id) ON DELETE SET NULL,
    sales_agent_contact_id          BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    subscriber_contact_id           BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    bdm_contact_id                  BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    referral_partner_contact_id     BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    affiliate_contact_id            BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    agent_contact_id                BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    status                          VARCHAR(30) DEFAULT 'pending',
    purchase_price                  NUMERIC(12,2),
    finance_due_date                DATE,
    settlement_date                 DATE,
    contract_date                   DATE,
    extra_attributes                JSONB DEFAULT '{}',  -- divide_percent, expected_commissions, custom_attributes
    -- DROPPED: payment_terms (0%), sas_* (0% - never launched), status_updated_at (0%)
    -- DROPPED: comm_in_notes (0%), comm_out_notes (0%), is_comments_enabled (UI pref)
    -- Sync columns
    synced_at                       TIMESTAMPTZ,
    sync_source                     VARCHAR(10),
    created_by                      BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by                      BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at                      TIMESTAMPTZ,
    updated_at                      TIMESTAMPTZ,
    deleted_at                      TIMESTAMPTZ
);
CREATE INDEX idx_sales_org ON sales(organization_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_sales_org_status ON sales(organization_id, status) WHERE deleted_at IS NULL;
CREATE INDEX idx_sales_legacy ON sales(legacy_sale_id) WHERE legacy_sale_id IS NOT NULL;
CREATE INDEX idx_sales_legacy_updated ON sales(legacy_sale_id, updated_at) WHERE legacy_sale_id IS NOT NULL;
CREATE INDEX idx_sales_lot ON sales(lot_id);
CREATE INDEX idx_sales_project ON sales(project_id);
CREATE INDEX idx_sales_created ON sales USING BRIN (created_at);
```

**`organization_id` derivation for sales**: v3 sales have `subscriber_id` (447/449 rows). Resolve via `subscriber_id → Lead → User → Organization`. For the 2 sales without `subscriber_id`, derive via `created_by → Lead(!) → User → Organization` (note: `created_by` on sales references `leads.id`, NOT `users.id` — extra hop required).

### 3.11 Commissions (normalized)

```
v3 Source: sales flat columns (piab_comm, affiliate_comm, etc.) -> 1 row per type
          + commissions polymorphic (Project: 15,254 + Lot: 4,262) -> project/lot config
```

```sql
CREATE TABLE commissions (
    id                  BIGSERIAL PRIMARY KEY,
    organization_id     BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    sale_id             BIGINT NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
    legacy_commission_id BIGINT UNIQUE,
    commission_type     VARCHAR(30) NOT NULL
                        CHECK (commission_type IN ('piab','subscriber','affiliate',
                                                   'sales_agent','referral_partner',
                                                   'bdm','sub_agent')),
    agent_user_id       BIGINT REFERENCES users(id) ON DELETE SET NULL,
    rate_percentage     NUMERIC(5,4),
    amount              NUMERIC(12,2) NOT NULL DEFAULT 0,
    override_amount     NUMERIC(12,2),
    notes               TEXT,
    created_at          TIMESTAMPTZ,
    updated_at          TIMESTAMPTZ,
    deleted_at          TIMESTAMPTZ
);
-- Uniqueness for active rows only (soft delete); NULLS NOT DISTINCT so at most one row with NULL agent per type/sale
CREATE UNIQUE INDEX uq_commissions_sale_type_agent_active
    ON commissions (sale_id, commission_type, agent_user_id) NULLS NOT DISTINCT
    WHERE deleted_at IS NULL;
CREATE INDEX idx_commissions_org ON commissions(organization_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_commissions_sale ON commissions(sale_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_commissions_agent ON commissions(agent_user_id) WHERE agent_user_id IS NOT NULL AND deleted_at IS NULL;
CREATE INDEX idx_commissions_legacy ON commissions(legacy_commission_id) WHERE legacy_commission_id IS NOT NULL;
```

### 3.12 Commission Tiers (NEW - launch feature)

```
v3 Source: None (new v4 table)
Purpose: Volume-based commission rate lookup for subscribers
```

```sql
CREATE TABLE commission_tiers (
    id                  BIGSERIAL PRIMARY KEY,
    organization_id     BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name                VARCHAR(255) NOT NULL,
    min_sales_count     INTEGER NOT NULL DEFAULT 0,
    max_sales_count     INTEGER,
    commission_rate     NUMERIC(5,4) NOT NULL,
    is_active           BOOLEAN DEFAULT true,
    created_at          TIMESTAMPTZ,
    updated_at          TIMESTAMPTZ,
    deleted_at          TIMESTAMPTZ
);
CREATE INDEX idx_commission_tiers_org ON commission_tiers(organization_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_commission_tiers_lookup ON commission_tiers(organization_id, min_sales_count, max_sales_count) WHERE is_active = true AND deleted_at IS NULL;
```

### 3.13 Property Reservations

```
v3 Source: property_reservations (120 rows)
          + statusables (110 -> status)
          FK remap: lead_id -> contact_id
Sync: ⇄ Bidirectional
```

```sql
CREATE TABLE property_reservations (
    id                      BIGSERIAL PRIMARY KEY,
    organization_id         BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    legacy_reservation_id   BIGINT UNIQUE,
    lot_id                  BIGINT REFERENCES lots(id) ON DELETE SET NULL,
    project_id              BIGINT REFERENCES projects(id) ON DELETE SET NULL,
    agent_contact_id        BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    primary_contact_id      BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    secondary_contact_id    BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    logged_in_user_id       BIGINT REFERENCES users(id) ON DELETE SET NULL,
    purchase_price          NUMERIC(12,2),
    deposit_amount          NUMERIC(12,2),
    -- DROPPED: land_deposit (0%), build_deposit (0%), agree_date (0%)
    status                  VARCHAR(30) DEFAULT 'pending',
    purchaser_type          JSONB,
    trustee_name            VARCHAR(255),
    finance_pre_approved    BOOLEAN DEFAULT false,
    finance_days_required   SMALLINT,
    abn_acn                 VARCHAR(50),
    is_family_trust         BOOLEAN DEFAULT false,
    is_bare_trust           BOOLEAN DEFAULT false,
    is_smsf_trust           BOOLEAN DEFAULT false,
    is_funds_rollover       BOOLEAN DEFAULT false,
    broker_name             VARCHAR(255),
    firm_name               VARCHAR(255),
    is_agreed               BOOLEAN DEFAULT false,
    is_lawlab_agreed        BOOLEAN DEFAULT false,
    contract_sent_at        TIMESTAMPTZ,
    -- Sync columns
    synced_at               TIMESTAMPTZ,
    sync_source             VARCHAR(10),
    created_by              BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by              BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at              TIMESTAMPTZ,
    updated_at              TIMESTAMPTZ,
    deleted_at              TIMESTAMPTZ
);
CREATE INDEX idx_reservations_org ON property_reservations(organization_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_reservations_org_status ON property_reservations(organization_id, status) WHERE deleted_at IS NULL;
CREATE INDEX idx_reservations_lot ON property_reservations(lot_id);
CREATE INDEX idx_reservations_legacy ON property_reservations(legacy_reservation_id) WHERE legacy_reservation_id IS NOT NULL;
CREATE INDEX idx_reservations_legacy_updated ON property_reservations(legacy_reservation_id, updated_at) WHERE legacy_reservation_id IS NOT NULL;
```

### 3.14 Property Enquiries

```
v3 Source: property_enquiries (734 rows) + statusables (649 -> status)
```

```sql
CREATE TABLE property_enquiries (
    id                  BIGSERIAL PRIMARY KEY,
    organization_id     BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    client_contact_id   BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    agent_contact_id    BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    logged_in_user_id   BIGINT REFERENCES users(id) ON DELETE SET NULL,
    status              VARCHAR(30) DEFAULT 'new',
    extra_attributes    JSONB DEFAULT '{}',  -- property, location, inspection, purchaser details
    created_by          BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by          BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at          TIMESTAMPTZ,
    updated_at          TIMESTAMPTZ,
    deleted_at          TIMESTAMPTZ
);
CREATE INDEX idx_enquiries_org ON property_enquiries(organization_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_enquiries_org_status ON property_enquiries(organization_id, status) WHERE deleted_at IS NULL;
CREATE INDEX idx_enquiries_client ON property_enquiries(client_contact_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_enquiries_extra ON property_enquiries USING GIN (extra_attributes);
```

### 3.15 Property Searches

```
v3 Source: property_searches (318 rows) + statusables (199 -> status)
```

```sql
CREATE TABLE property_searches (
    id                  BIGSERIAL PRIMARY KEY,
    organization_id     BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    client_contact_id   BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    agent_contact_id    BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    logged_in_user_id   BIGINT REFERENCES users(id) ON DELETE SET NULL,
    status              VARCHAR(30) DEFAULT 'active',
    search_criteria     JSONB DEFAULT '{}',
    created_by          BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by          BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at          TIMESTAMPTZ,
    updated_at          TIMESTAMPTZ,
    deleted_at          TIMESTAMPTZ
);
CREATE INDEX idx_property_searches_org ON property_searches(organization_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_property_searches_criteria ON property_searches USING GIN (search_criteria);
CREATE INDEX idx_searches_org_status ON property_searches(organization_id, status) WHERE deleted_at IS NULL;
```

### 3.16 Tasks

```
v3 Source: tasks (62 rows) - FK remap
v3 renames: assigned_id -> assigned_to_user_id, attached_id -> attached_contact_id
v3 renames: start_at -> due_at, end_at -> DROP (use completed_at instead)
```

```sql
CREATE TABLE tasks (
    id                      BIGSERIAL PRIMARY KEY,
    organization_id         BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    title                   VARCHAR(255) NOT NULL,
    description             TEXT,
    type                    VARCHAR(30),
    assigned_to_user_id     BIGINT REFERENCES users(id) ON DELETE SET NULL,
    assigned_contact_id     BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    attached_contact_id     BIGINT REFERENCES contacts(id) ON DELETE SET NULL,
    is_complete             BOOLEAN DEFAULT false,
    due_at                  TIMESTAMPTZ,
    completed_at            TIMESTAMPTZ,
    created_by              BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by              BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at              TIMESTAMPTZ,
    updated_at              TIMESTAMPTZ,
    deleted_at              TIMESTAMPTZ
);
CREATE INDEX idx_tasks_org ON tasks(organization_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_tasks_assigned_user ON tasks(assigned_to_user_id, due_at) WHERE deleted_at IS NULL;
CREATE INDEX idx_tasks_org_open ON tasks(organization_id, due_at) WHERE deleted_at IS NULL AND is_complete = false;
```

### 3.17 Notes (only remaining polymorphic)

```
v3 Source: notes (22,861 rows) - morph map update (App\Models\Lead -> contact)
           Sale: 16,189 | Lead: 6,337 | Project: 155
```

```sql
CREATE TABLE notes (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    legacy_note_id  BIGINT UNIQUE,
    noteable_type   VARCHAR(50) NOT NULL,   -- morph map: 'contact', 'sale', 'project'
    noteable_id     BIGINT NOT NULL,
    author_id       BIGINT REFERENCES users(id) ON DELETE SET NULL,
    content         TEXT NOT NULL,
    type            VARCHAR(20) DEFAULT 'note'
                    CHECK (type IN ('note', 'email_draft', 'system')),
    is_pinned       BOOLEAN DEFAULT false,
    created_at      TIMESTAMPTZ,
    updated_at      TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ
);
CREATE INDEX idx_notes_org ON notes(organization_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_notes_noteable ON notes(noteable_type, noteable_id);
CREATE INDEX idx_notes_author_created ON notes(author_id, created_at DESC) WHERE deleted_at IS NULL;
CREATE INDEX idx_notes_legacy ON notes(legacy_note_id) WHERE legacy_note_id IS NOT NULL;
```

### 3.18 Comments

```
v3 Source: comments (4,300 rows) - direct copy
           PropertyEnquiry: 2,489 | PropertySearch: 1,502 | PropertyReservation: 268
```

Starter kit already has `crm_comments` table with columns: `id`, `commentable_type`, `commentable_id`, `user_id`, `comment`, `is_approved`, `created_at`, `updated_at`.

v3 -> v4 mapping:
- `commentable_type`: Update morph map (`App\Models\PropertyEnquiry` -> `property_enquiry`, etc.)
- `commentable_id`: Direct
- `user_id`: Direct
- `comment`: Direct
- `is_approved`: Direct
- No new columns needed - schema is compatible.

### 3.19 Partners

```
v3 Source: partners (300 rows) - FK remap lead_id -> contact_id
```

```sql
CREATE TABLE partners (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    contact_id      BIGINT NOT NULL REFERENCES contacts(id) ON DELETE CASCADE,
    parent_partner_id BIGINT REFERENCES partners(id) ON DELETE SET NULL,
    status          VARCHAR(30) DEFAULT 'active',
    partner_type    VARCHAR(50),
    commission_rate NUMERIC(5,2),
    territory       VARCHAR(255),
    created_by      BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by      BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ,
    updated_at      TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ
);
CREATE INDEX idx_partners_org ON partners(organization_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_partners_contact ON partners(contact_id);
CREATE INDEX idx_partners_org_status ON partners(organization_id, status) WHERE deleted_at IS NULL;
```

### 3.20 Relationships

```
v3 Source: relationships (7,392 rows) - FK remap to contact_ids
v3 renames: account_id -> account_contact_id, relation_id -> relation_contact_id
```

```sql
CREATE TABLE relationships (
    id                      BIGSERIAL PRIMARY KEY,
    organization_id         BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    account_contact_id      BIGINT NOT NULL REFERENCES contacts(id) ON DELETE CASCADE,
    relation_contact_id     BIGINT NOT NULL REFERENCES contacts(id) ON DELETE CASCADE,
    relation_type           VARCHAR(30)
                            CHECK (relation_type IN ('agent','partner','referral',
                                                    'colleague','family','spouse')),
    created_at              TIMESTAMPTZ,
    updated_at              TIMESTAMPTZ
);
CREATE INDEX idx_relationships_org ON relationships(organization_id);
CREATE INDEX idx_relationships_account ON relationships(account_contact_id);
CREATE INDEX idx_relationships_relation ON relationships(relation_contact_id);
```

### 3.21 Project Favorites (replaces 5 love_* tables)

```
v3 Source: love_reactions (2,199 rows) - simplified from 5 tables to 1
```

```sql
CREATE TABLE project_favorites (
    user_id     BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    project_id  BIGINT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    PRIMARY KEY (user_id, project_id)
);
```

### 3.22 Project Updates

```
v3 Source: project_updates (156,393 rows) - direct copy
```

```sql
CREATE TABLE project_updates (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    project_id      BIGINT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id         BIGINT REFERENCES users(id) ON DELETE SET NULL,
    type            VARCHAR(30),
    extra_attributes JSONB DEFAULT '{}',
    created_at      TIMESTAMPTZ,
    updated_at      TIMESTAMPTZ
);
CREATE INDEX idx_project_updates_org ON project_updates(organization_id);
CREATE INDEX idx_project_updates_project ON project_updates(project_id);
CREATE INDEX idx_project_updates_created ON project_updates USING BRIN (created_at);
```

### 3.23 Lookup Tables (direct copy + org_id)

```
v3 Source: Direct copy with added organization_id and UNIQUE constraints
```

| Table | v3 Rows | Changes for v4 |
| --- | --- | --- |
| sources | 17 | + organization_id, rename `label` -> `name`, + UNIQUE(org_id, name) |
| companies | 976 | + organization_id, + legacy_company_id, + synced_at, + sync_source, + UNIQUE(org_id, name). Sync: ⇄ |
| developers | 336 | + organization_id |
| projecttypes | 12 | + organization_id, + UNIQUE(org_id, name) |
| states | 8 | Keep as-is (shared geographic data) |
| suburbs | 15,299 | + `state_id` FK → `states` (backfill from v3 `state` string → `states.short_name` / `long_name`); DROP `au_town_id`; keep `state` VARCHAR optional as denormalized label until backfill complete, then drop or sync via trigger |

```sql
CREATE TABLE sources (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name            VARCHAR(255) NOT NULL,
    description     TEXT,
    created_at      TIMESTAMPTZ,
    updated_at      TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ,
    UNIQUE (organization_id, name)
);

CREATE TABLE companies (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    legacy_company_id BIGINT UNIQUE,
    name            VARCHAR(255) NOT NULL,
    slogan          VARCHAR(255),
    bk_name         VARCHAR(255),
    extra_attributes JSONB DEFAULT '{}',
    synced_at       TIMESTAMPTZ,
    sync_source     VARCHAR(10),
    created_at      TIMESTAMPTZ,
    updated_at      TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ,
    UNIQUE (organization_id, name)
);
CREATE INDEX idx_companies_org ON companies(organization_id) WHERE deleted_at IS NULL;
CREATE INDEX idx_companies_legacy ON companies(legacy_company_id) WHERE legacy_company_id IS NOT NULL;
CREATE INDEX idx_companies_legacy_updated ON companies(legacy_company_id, updated_at) WHERE legacy_company_id IS NOT NULL;

CREATE TABLE developers (
    id                      BIGSERIAL PRIMARY KEY,
    organization_id         BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    user_id                 BIGINT REFERENCES users(id) ON DELETE SET NULL,
    is_active               BOOLEAN DEFAULT true,
    is_onboard              BOOLEAN DEFAULT false,
    build_time              VARCHAR(255),
    commission_note         TEXT,
    information_delivery    TEXT,
    relationship_status     VARCHAR(255),
    login_info              TEXT,
    updated_by              BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at              TIMESTAMPTZ,
    updated_at              TIMESTAMPTZ,
    deleted_at              TIMESTAMPTZ
);
CREATE INDEX idx_developers_org ON developers(organization_id) WHERE deleted_at IS NULL;

CREATE TABLE projecttypes (
    id              BIGSERIAL PRIMARY KEY,
    organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    title           VARCHAR(255) NOT NULL,
    created_at      TIMESTAMPTZ,
    updated_at      TIMESTAMPTZ,
    deleted_at      TIMESTAMPTZ,
    UNIQUE (organization_id, title)
);
CREATE INDEX idx_projecttypes_org ON projecttypes(organization_id);

CREATE TABLE states (
    id              BIGSERIAL PRIMARY KEY,
    long_name       VARCHAR(255) NOT NULL,
    short_name      VARCHAR(10) NOT NULL,
    created_at      TIMESTAMPTZ,
    updated_at      TIMESTAMPTZ
);

CREATE TABLE suburbs (
    id              BIGSERIAL PRIMARY KEY,
    suburb          VARCHAR(255) NOT NULL,
    postcode        VARCHAR(10),
    state_id        BIGINT REFERENCES states(id) ON DELETE SET NULL,
    state           VARCHAR(50),
    latitude        NUMERIC(10,7),
    longitude       NUMERIC(10,7),
    created_at      TIMESTAMPTZ,
    updated_at      TIMESTAMPTZ
);
CREATE INDEX idx_suburbs_state_id ON suburbs(state_id);
CREATE INDEX idx_suburbs_postcode ON suburbs(postcode);
CREATE INDEX idx_suburbs_suburb_lower ON suburbs (LOWER(suburb));
```

**Suburbs migration:** Populate `state_id` from v3 text `state` (normalize case and abbreviations against `states`). Prefer queries and joins on `state_id`; drop `state` once backfill is verified if you want a single source of truth.

### ~~3.24-3.26 REMOVED — Rebuild Separately in v4~~

> **Flyers** (3.26), **Marketing & Email** (3.24: mail_lists, mail_list_contacts, brochure_mail_jobs, mail_job_statuses, email_settings, campaign_websites, campaign_website_project), and **Websites** (3.25: websites, website_pages, website_elements, website_contacts) have been moved to Section 2I. These 12 tables will be rebuilt from scratch in v4 — not migrated from v3.

### 3.27 Resources

```
v3 Source: Training/resource library - active
```

| Table | v3 Rows | v4 Changes |
| --- | --- | --- |
| resources | 138 | + organization_id |
| resource_categories | 5 | Direct copy |
| resource_groups | 9 | + organization_id |

```sql
CREATE TABLE resource_categories (
    id                  BIGSERIAL PRIMARY KEY,
    title               VARCHAR(255) NOT NULL,
    slug                VARCHAR(255),
    files_type_allowed  VARCHAR(255),
    created_by          BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by          BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at          TIMESTAMPTZ,
    updated_at          TIMESTAMPTZ
);

CREATE TABLE resource_groups (
    id                      BIGSERIAL PRIMARY KEY,
    organization_id         BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    resource_category_id    BIGINT NOT NULL REFERENCES resource_categories(id) ON DELETE CASCADE,
    title                   VARCHAR(255) NOT NULL,
    slug                    VARCHAR(255),
    created_by              BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by              BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at              TIMESTAMPTZ,
    updated_at              TIMESTAMPTZ
);
CREATE INDEX idx_resource_groups_org ON resource_groups(organization_id);

CREATE TABLE resources (
    id                  BIGSERIAL PRIMARY KEY,
    organization_id     BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    title               VARCHAR(255) NOT NULL,
    slug                VARCHAR(255),
    url                 VARCHAR(500),
    resource_group_id   BIGINT REFERENCES resource_groups(id) ON DELETE SET NULL,
    order_column        SMALLINT DEFAULT 0,
    created_by          BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_by          BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at          TIMESTAMPTZ,
    updated_at          TIMESTAMPTZ,
    deleted_at          TIMESTAMPTZ
);
CREATE INDEX idx_resources_org ON resources(organization_id) WHERE deleted_at IS NULL;
```

### 3.28 Media (Spatie)

```
v3 Source: media (109,514 rows) - update morph types to match morph map
           Project: 88K | Flyer: 11K | Sale: 4K | Lead: 2K | others
```

Spatie media library format is compatible. Update `model_type` values only.

### 3.29 Users

```
v3 Source: users (1,503 rows) + model_has_roles (11,643 rows -> Spatie Permission)
```

Starter kit already has users table. Migration steps:
- Import `name`, `email`, `password`, `email_verified_at`, `created_at`, `updated_at`, `deleted_at`
- Add `contact_id` FK (set after contacts are imported using legacy_lead_id map)
- Import `model_has_roles` (11,643 rows) -> map v3 roles to Spatie Permission roles
- DROP from v3 users: All 44 columns NOT listed above (CRM-specific fields moved to contacts or organization_settings)
- v3 `created_by`/`modified_by` on users -> not migrated (users don't need userstamps on themselves)

### 3.30 Sync Infrastructure Tables

```
v4 only - no v3 source
```

```sql
CREATE TABLE sync_logs (
    id              BIGSERIAL PRIMARY KEY,
    entity_type     VARCHAR(50) NOT NULL,   -- 'contact', 'project', etc.
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

### 3.31 Construction Designs (NEW — Builder Portal)

```
v4 only — no v3 source. Part of Construction Library ("recipe book").
```

```sql
CREATE TABLE construction_designs (
    id                  BIGSERIAL PRIMARY KEY,
    organization_id     BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name                VARCHAR(255) NOT NULL,
    slug                VARCHAR(255) NOT NULL,
    width_m             DECIMAL(6,2),
    depth_m             DECIMAL(6,2),
    floor_area_sqm      DECIMAL(8,2),
    bedrooms            SMALLINT DEFAULT 0,
    bathrooms           SMALLINT DEFAULT 0,
    garages             SMALLINT DEFAULT 0,
    living_areas        SMALLINT DEFAULT 0,
    storeys             SMALLINT DEFAULT 1,
    property_type       VARCHAR(30) CHECK (property_type IN ('house','duplex','townhouse','unit','villa','terrace','granny_flat','rooming_house','co_living','ndi_s')) DEFAULT 'house',
    build_price         DECIMAL(12,2),
    range_name          VARCHAR(100),
    brand               VARCHAR(100),
    description         TEXT,
    is_active           BOOLEAN DEFAULT TRUE,
    extra_attributes    JSONB DEFAULT '{}',
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ,
    UNIQUE (organization_id, slug)
);

CREATE INDEX idx_construction_designs_org ON construction_designs (organization_id);
CREATE INDEX idx_construction_designs_active ON construction_designs (organization_id, is_active) WHERE is_active = TRUE;
```

### 3.32 Construction Facades (NEW — Builder Portal)

```
v4 only — no v3 source.
```

```sql
CREATE TABLE construction_facades (
    id                  BIGSERIAL PRIMARY KEY,
    organization_id     BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name                VARCHAR(255) NOT NULL,
    slug                VARCHAR(255) NOT NULL,
    colour_scheme       VARCHAR(100),
    elevation           VARCHAR(100),
    storey_type         VARCHAR(20),
    facade_price        DECIMAL(12,2) DEFAULT 0,
    is_active           BOOLEAN DEFAULT TRUE,
    extra_attributes    JSONB DEFAULT '{}',
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ,
    UNIQUE (organization_id, slug)
);

CREATE INDEX idx_construction_facades_org ON construction_facades (organization_id);
```

### 3.33 Construction Inclusions (NEW — Builder Portal)

```
v4 only — no v3 source.
```

```sql
CREATE TABLE construction_inclusions (
    id                  BIGSERIAL PRIMARY KEY,
    organization_id     BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name                VARCHAR(255) NOT NULL,
    slug                VARCHAR(255) NOT NULL,
    tier                VARCHAR(20) DEFAULT 'standard',
    description         TEXT,
    is_active           BOOLEAN DEFAULT TRUE,
    extra_attributes    JSONB DEFAULT '{}',
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ,
    UNIQUE (organization_id, slug)
);

CREATE TABLE construction_inclusion_items (
    id                  BIGSERIAL PRIMARY KEY,
    inclusion_id        BIGINT NOT NULL REFERENCES construction_inclusions(id) ON DELETE CASCADE,
    category            VARCHAR(50) NOT NULL,
    item_name           VARCHAR(255) NOT NULL,
    description         TEXT,
    is_featured         BOOLEAN DEFAULT FALSE,
    sort_order          SMALLINT DEFAULT 0,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_inclusion_items_inclusion ON construction_inclusion_items (inclusion_id);
CREATE INDEX idx_inclusion_items_featured ON construction_inclusion_items (inclusion_id, is_featured) WHERE is_featured = TRUE;
```

### 3.34 Commission Templates (NEW — Builder Portal)

```
v4 only — no v3 source. Reusable commission structures with stage-based payment splits.
```

```sql
CREATE TABLE commission_templates (
    id                  BIGSERIAL PRIMARY KEY,
    organization_id     BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name                VARCHAR(255) NOT NULL,
    commission_type     VARCHAR(20) NOT NULL CHECK (commission_type IN ('percentage','flat','tiered')),
    rate                DECIMAL(8,4),
    flat_amount         DECIMAL(12,2),
    stages              JSONB DEFAULT '{"unconditional": 25, "frame": 25, "lockup": 25, "completion": 25}',
    is_active           BOOLEAN DEFAULT TRUE,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ
);

CREATE INDEX idx_commission_templates_org ON commission_templates (organization_id);
```

### 3.35 Packages (NEW — Builder Portal)

```
v4 only — no v3 source. A package = lot + design + facade + inclusions = sellable H&L product.
```

```sql
CREATE TABLE packages (
    id                          BIGSERIAL PRIMARY KEY,
    organization_id             BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    lot_id                      BIGINT REFERENCES lots(id) ON DELETE SET NULL,
    project_id                  BIGINT REFERENCES projects(id) ON DELETE SET NULL,
    construction_design_id      BIGINT REFERENCES construction_designs(id) ON DELETE SET NULL,
    construction_facade_id      BIGINT REFERENCES construction_facades(id) ON DELETE SET NULL,
    commission_template_id      BIGINT REFERENCES commission_templates(id) ON DELETE SET NULL,
    name                        VARCHAR(255),
    slug                        VARCHAR(255),
    land_price                  DECIMAL(12,2) DEFAULT 0,
    build_price                 DECIMAL(12,2) DEFAULT 0,
    facade_price                DECIMAL(12,2) DEFAULT 0,
    total_price                 DECIMAL(12,2) GENERATED ALWAYS AS (land_price + build_price + facade_price) STORED,
    weekly_rent_est             DECIMAL(8,2),
    gross_yield_pct             DECIMAL(5,2),
    status                      VARCHAR(20) NOT NULL CHECK (status IN ('draft','available','sold','archived')) DEFAULT 'draft',
    distribution_type           VARCHAR(20) NOT NULL CHECK (distribution_type IN ('none','open','exclusive')) DEFAULT 'none',
    publish_at                  TIMESTAMPTZ,
    published_at                TIMESTAMPTZ,
    extra_attributes            JSONB DEFAULT '{}',
    created_by                  BIGINT REFERENCES users(id) ON DELETE SET NULL,
    created_at                  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at                  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at                  TIMESTAMPTZ
);

CREATE INDEX idx_packages_org ON packages (organization_id);
CREATE INDEX idx_packages_lot ON packages (lot_id);
CREATE INDEX idx_packages_status ON packages (organization_id, status) WHERE status = 'available';
CREATE INDEX idx_packages_distribution ON packages (distribution_type) WHERE distribution_type != 'none';
```

### 3.36 Package Inclusions (pivot — NEW)

```sql
CREATE TABLE package_inclusions (
    id                          BIGSERIAL PRIMARY KEY,
    package_id                  BIGINT NOT NULL REFERENCES packages(id) ON DELETE CASCADE,
    construction_inclusion_id   BIGINT NOT NULL REFERENCES construction_inclusions(id) ON DELETE CASCADE,
    is_featured                 BOOLEAN DEFAULT FALSE,
    sort_order                  SMALLINT DEFAULT 0,
    UNIQUE (package_id, construction_inclusion_id)
);
```

### 3.37 Package Distributions (pivot — NEW)

```sql
CREATE TABLE package_distributions (
    id                  BIGSERIAL PRIMARY KEY,
    package_id          BIGINT NOT NULL REFERENCES packages(id) ON DELETE CASCADE,
    user_id             BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    allocated_by        BIGINT REFERENCES users(id) ON DELETE SET NULL,
    allocated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (package_id, user_id)
);

CREATE INDEX idx_package_distributions_user ON package_distributions (user_id);
```

### 3.38 Agent Favorites (NEW — Agent Portal)

```sql
CREATE TABLE agent_favorites (
    id                  BIGSERIAL PRIMARY KEY,
    user_id             BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    package_id          BIGINT NOT NULL REFERENCES packages(id) ON DELETE CASCADE,
    list_name           VARCHAR(100) DEFAULT 'Favorites',
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (user_id, package_id, list_name)
);

CREATE INDEX idx_agent_favorites_user ON agent_favorites (user_id);
```

### 3.39 Agent Saved Searches (NEW — Agent Portal)

```sql
CREATE TABLE agent_saved_searches (
    id                      BIGSERIAL PRIMARY KEY,
    user_id                 BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    organization_id         BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name                    VARCHAR(255) NOT NULL,
    search_criteria         JSONB NOT NULL DEFAULT '{}',
    email_alerts_enabled    BOOLEAN DEFAULT FALSE,
    alert_frequency         VARCHAR(10) CHECK (alert_frequency IN ('immediate','daily','weekly')) DEFAULT 'daily',
    last_alerted_at         TIMESTAMPTZ,
    created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_agent_saved_searches_user ON agent_saved_searches (user_id);
CREATE INDEX idx_agent_saved_searches_alerts ON agent_saved_searches (email_alerts_enabled) WHERE email_alerts_enabled = TRUE;
```

### 3.40 Sale Commission Payments (NEW — Stage-based tracking)

```
v4 only. Breaks commission into construction stage installments.
Links to commissions table (PRD 05) for the total amount, and tracks when each stage payment is made.
```

```sql
CREATE TABLE sale_commission_payments (
    id                  BIGSERIAL PRIMARY KEY,
    sale_id             BIGINT NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
    commission_id       BIGINT NOT NULL REFERENCES commissions(id) ON DELETE CASCADE,
    stage               VARCHAR(20) NOT NULL CHECK (stage IN ('unconditional','frame','lockup','completion')),
    amount              DECIMAL(12,2) NOT NULL,
    is_paid             BOOLEAN DEFAULT FALSE,
    paid_at             TIMESTAMPTZ,
    invoice_reference   VARCHAR(100),
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_commission_payments_sale ON sale_commission_payments (sale_id);
CREATE INDEX idx_commission_payments_unpaid ON sale_commission_payments (is_paid) WHERE is_paid = FALSE;
```

### 3.41 Enquiries (NEW — Agent Portal)

```
Structured enquiry form modelled after Kenekt's enquiry processing flow.
Two enquiry types: buyer_presentation (agent has a buyer ready) vs package_research (exploring options).
```

```sql
CREATE TABLE enquiries (
    id                      BIGSERIAL PRIMARY KEY,
    organization_id         BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    from_user_id            BIGINT REFERENCES users(id) ON DELETE SET NULL,
    to_organization_id      BIGINT REFERENCES organizations(id) ON DELETE SET NULL,
    package_id              BIGINT REFERENCES packages(id) ON DELETE SET NULL,
    enquiry_type            VARCHAR(30) NOT NULL CHECK (enquiry_type IN ('buyer_presentation','package_research','general')) DEFAULT 'general',
    presentation_date       DATE,
    buyer_has_preapproval   BOOLEAN,
    ownership_type          VARCHAR(20) CHECK (ownership_type IN ('investor','owner_occupier')),
    support_requested       JSONB DEFAULT '[]',
    subject                 VARCHAR(255),
    message                 TEXT NOT NULL,
    status                  VARCHAR(20) CHECK (status IN ('pending','responded','closed')) DEFAULT 'pending',
    responded_at            TIMESTAMPTZ,
    created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- support_requested example: ["confirming_availability", "request_epack", "commission_terms", "register_eoi"]

CREATE INDEX idx_enquiries_org ON enquiries (organization_id);
CREATE INDEX idx_enquiries_to_org ON enquiries (to_organization_id);
CREATE INDEX idx_enquiries_status ON enquiries (status) WHERE status = 'pending';
```

### 3.42 Stock Imports (NEW — Bulk Import Tracking)

```sql
CREATE TABLE stock_imports (
    id                  BIGSERIAL PRIMARY KEY,
    organization_id     BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    project_id          BIGINT REFERENCES projects(id) ON DELETE SET NULL,
    file_name           VARCHAR(255),
    file_path           VARCHAR(500),
    import_url          VARCHAR(500),
    status              VARCHAR(20) NOT NULL CHECK (status IN ('pending','parsing','completed','failed')) DEFAULT 'pending',
    total_rows          INTEGER DEFAULT 0,
    success_count       INTEGER DEFAULT 0,
    error_count         INTEGER DEFAULT 0,
    errors              JSONB DEFAULT '[]',
    imported_by         BIGINT REFERENCES users(id) ON DELETE SET NULL,
    completed_at        TIMESTAMPTZ,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_stock_imports_org ON stock_imports (organization_id);
```

---

## 4. v4 Tables - Starter Kit (Pre-Built, No Migration)

> These ~125 tables come from the starter kit and its packages (153 migration files creating ~115 core + ~10 module tables).
> NO data migration from v3. They start empty or with seed data.
>
> **Architecture notes** (updated 2026-03-23):
> - Telescope REMOVED, replaced by **Laravel Pulse** (5 tables) + **Spatie Health** (1 table)
> - Filament has TWO panels: **Admin** (app/Filament/Resources/) + **System** (app/Filament/System/) for superadmin
> - Module system: 13 modules including `module-crm` (`Cogneiss\ModuleCrm`) and `module-hr` (`Cogneiss\ModuleHr`)
> - Neither `module-crm` nor `module-hr` is registered in `config/modules.php` — must be added
> - CRM Filament resource discovery must be configured in panel providers
> - Use `php artisan make:model:full --panel=admin` to scaffold models
> - Spatie Onboard already integrated (OnboardingServiceProvider.php with 3 steps)
> - Dashboard + Report builders use Puck (modules/dashboards/, modules/reports/)

### By Domain

| Domain | Tables | Key Packages |
| --- | --- | --- |
| **Framework & Auth** (14) | users, organizations, org_user, org_invitations, org_domains, org_settings, password_reset_tokens, sessions, cache, cache_locks, jobs, job_batches, failed_jobs, personal_access_tokens | Laravel, Sanctum |
| **Monitoring** (6) | pulse_aggregates, pulse_entries, pulse_monitors, pulse_values, pulse_meta, health_check_result_history_items | laravel/pulse, spatie/laravel-health |
| **AI & Agents** (13) | ai_bots, ai_bot_runs, ai_bot_prompts, ai_bot_categories, ai_credit_pools, ai_credit_usage, ai_user_credit_limits, ai_user_byok, ai_summaries, agent_conversations, agent_conversation_messages, model_embeddings, embedding_demos | laravel/ai, prism-php/prism, pgvector |
| **Billing** (9) | credits, credit_packs, invoices, gateway_products, payment_gateways, payment_stages, billing_metrics, failed_payment_attempts, refund_requests | stripe/stripe-php (Stripe only — no eWAY, no Lemon Squeezy) |
| **Email & Marketing** (12) | email_campaigns, nurture_sequences, sequence_enrollments, sequence_steps, mail_templates, mail_exceptions, builder_email_logs, builder_document_*, builder_portals, cold_outreach_templates, landing_page_templates | martinpetricko/database-mail, backstage/laravel-mails |
| **Workflows** (8) | workflows, workflow_logs, workflow_exceptions, workflow_relationships, workflow_signals, workflow_timers, automation_rules, funnel_templates | laravel-workflow |
| **CRM Scaffold** (7) | crm_pipelines, crm_contacts, crm_deals, crm_activities (REPLACED by module-crm tables), hr_departments, hr_employees, hr_leave_requests | module-crm, module-hr |
| **Dashboards & Reports** (3) | dashboards, reports, report_outputs | modules/dashboards, modules/reports |
| **Custom Fields** (2) | custom_fields, custom_field_values | Custom |
| **Affiliates** (4) | affiliates, affiliate_commissions, affiliate_payouts, referrals | jijunair/laravel-referral |
| **Engagement** (9) | engagement_events, achievements, achievement_user, levels, streaks, streak_activities, streak_histories, announcements, experience_audits | cjmellor/level-up |
| **Content/CMS** (10) | pages, page_revisions, posts, puck_templates, help_articles, mentions, slug_redirects, listing_versions, terms_versions, user_terms_acceptances | Custom |
| **Spatie** (6) | activity_log, media, tags, taggables, permissions, model_has_roles + role_has_permissions | spatie/* |
| **Feature Flags** (3) | features, feature_segments, flags + model_flags | laravel/pennant |
| **Notifications** (4) | notifications, notification_preferences, push_history, push_schedules | thomasjohnkane/snooze |
| **Integrations** (8) | xero_connections, xero_contacts, xero_invoices, xero_reconciliations, webhook_endpoints, webhook_logs, social_accounts, login_events | saloonphp/saloon, spatie/laravel-webhook-* |
| **Property** (8) | house_designs, house_design_inclusion_package, inclusion_packages, inclusion_items, packages, ad_templates, brochure_layouts, commission_templates | Custom |
| **Analytics** (7) | pan_analytics, attributions, contact_attributions, retargeting_pixels, data_table_audit_log, data_table_saved_views, sync_log + sync_state | panphp/pan |
| **Misc** (5) | onboarding_progress, contact_submissions, exports, imports + failed_import_rows, shareables, visibility_demos | spatie/laravel-onboard, maatwebsite/excel |

### How CRM Tables Connect to Starter Kit

```
    STARTER KIT                           CRM TABLES
    -----------                           ----------
    organizations ----------------------> contacts.organization_id (ALL CRM tables)
    users ----------------------------->  contacts.created_by, sales.*_contact_id
    agent_conversations --------------->  AI chats about contacts/projects
    model_embeddings ------------------>  pgvector search over contacts (polymorphic)
    email_campaigns ------------------->  Send to contact_emails
    nurture_sequences ----------------->  Enroll contacts in drip sequences
    workflows ------------------------->  Automate: new contact -> assign -> notify
    custom_fields/values -------------->  Extend contacts/projects/sales dynamically
    deals ----------------------------->  Link to contacts + lots + projects
    affiliates ----------------------->   Link to contacts for referral tracking
    lead_scores ---------------------->   AI-computed scoring for contacts
    xero_contacts/invoices ------------>  Sync contacts + sales to Xero
    commission_templates -------------->  Template rules for CRM commissions
    house_designs/inclusion_packages -->  Link to projects/lots
    attributions --------------------->   Track how contacts were acquired
    activity_log --------------------->   Audit trail for all CRM entities
    media --------------------------->    Files for projects, contacts
    tags/taggables ------------------>    Tag contacts, projects
    notifications ------------------->    Notify about CRM events
```

---

## 5. Column-Level Migration Maps (Every Column)

> Every v3 column is mapped: MIGRATE, DROP, or MOVE. Nothing is left ambiguous.
>
> **Detailed column maps (5.1-5.16)**: For complex tables with column renames, splits, or JSONB moves.
> **Simple tables (3.16-3.29)**: Tasks, notes, relationships, lookups, resources - their Section 3 CREATE TABLE DDL IS the definitive column spec. v3 column names map directly to v4 unless the DDL has a different name (the mapping is obvious from the DDL comments and FK names). (Marketing, websites, and flyers removed — rebuild separately, see §2I.)
>
> **Organization ID derivation** (applies to ALL tables):
> - Tables with `subscriber_id`: Derived via `subscriber_id → Lead → User → Organization`
> - Tables with `created_by` pointing to `users.id`: Derived via `created_by → User → Organization`
> - Tables with `created_by` pointing to `leads.id` (e.g. sales): Derived via `created_by → Lead(!) → User → Organization` (extra hop)
> - Shared data (projects, lots): Set to PIAB org (`sync.piab_organization_id`)
> - Platform leads: Set to PIAB org with `contact_origin = 'saas_product'`

### 5.1 leads (23 cols) -> contacts

| v3 Column | v4 Destination | Action |
| --- | --- | --- |
| id | contacts.legacy_lead_id | Store as mapping key (UNIQUE) |
| first_name | contacts.first_name | Direct |
| last_name | contacts.last_name | Direct |
| job_title | contacts.job_title | Direct (was `title` in some refs) |
| source_id | contacts.source_id | Direct FK |
| company_id | contacts.company_id | Direct FK |
| stage | contacts.stage | Direct (v3 already has this + statusables) |
| extra_attributes | contacts.extra_attributes | MySQL JSON -> PostgreSQL JSONB |
| last_followup_at | contacts.last_followup_at | Keep column (0% in v3 but will be used in v4) |
| next_followup_at | contacts.next_followup_at | Keep column (0% in v3 but will be used in v4) |
| important_note | contacts.extra_attributes.important_note | Move to JSONB |
| summary_note | contacts.extra_attributes.summary_note | Move to JSONB |
| is_new | DROP | Derive from stage='new' instead |
| is_partner | contacts.type='partner' | Map to type enum |
| primary_color | DROP | UI preference, not contact data |
| primary_text_color | DROP | UI preference |
| secondary_color | DROP | UI preference |
| old_table_id | DROP | Legacy v2 reference |
| created_by | contacts.created_by | Direct (userstamp) |
| modified_by | contacts.updated_by | Rename to updated_by |
| created_at | contacts.created_at | Direct |
| updated_at | contacts.updated_at | Direct |
| deleted_at | contacts.deleted_at | Direct |
| - | contacts.id | NEW: BIGSERIAL PK |
| - | contacts.organization_id | NEW: multi-tenant. Derived via relationships pivot → subscriber Lead → User → Organization |
| - | contacts.legacy_lead_id | NEW: sync mapping (UNIQUE) |
| - | contacts.contact_origin | NEW: 'property' default, 'saas_product' for platform leads |
| - | contacts.type | NEW: expanded enum |
| - | contacts.lead_score | NEW: AI-computed |
| - | contacts.last_contacted_at | NEW |
| - | contacts.synced_at | NEW: sync timestamp |
| - | contacts.sync_source | NEW: 'v3', 'v4', 'initial' |
| - | contacts.address_line1..country | From addresses table (9,165 rows) |

### 5.2 v3 contacts/detail (20,190 rows) -> contact_emails + contact_phones

**Source**: Polymorphic `contacts` table (`model_type='App\Models\Lead'`), NOT from `lead_emails`/`lead_phones` (which do not exist in v3).

| v3 type | v3 Rows | v4 Table | Mapping |
| --- | --- | --- | --- |
| email_1 | 9,163 | contact_emails (is_primary=true) | value -> email |
| email_2 | 567 | contact_emails (is_primary=false) | value -> email |
| phone | 9,601 | contact_phones (is_primary=true) | value -> phone |
| website | 692 | contacts.extra_attributes.website | Move to JSONB |
| facebook | 59 | contacts.extra_attributes.facebook | Move to JSONB |
| linkedin | 32 | contacts.extra_attributes.linkedin | Move to JSONB |
| youtube | 15 | contacts.extra_attributes.youtube | Move to JSONB |
| skype | 12 | contacts.extra_attributes.skype | Move to JSONB |
| twitter | 10 | contacts.extra_attributes.twitter | Move to JSONB |
| googleplus | 5 | DROP | Dead platform |
| pinterest | 3 | contacts.extra_attributes.pinterest | Move to JSONB |

v3 columns: model_id -> contact_id via legacy_lead_id map. order_column -> order_column. custom_attributes -> DROP.
NEW sync columns: legacy_email_id / legacy_phone_id (v3 contacts.id), synced_at, sync_source.
NEW: organization_id derived from parent contact's organization_id.

### 5.3 addresses (24,745 rows) -> flatten into parent tables

| v3 model_type | Rows | v4 Destination | Column Mapping |
| --- | --- | --- | --- |
| App\Models\Project | 15,480 | projects.address_line1..longitude | street->address_line1, unit->address_line2, suburb->city, state->state, postcode->postcode, latitude->latitude, longitude->longitude |
| App\Models\Lead | 9,165 | contacts.address_line1..country | Same mapping as above |
| App\Models\PotentialProperty | 1 | DROP | Table dropped |

v3 columns NOT migrated: house_number (merge into address_line1), custom_attributes (DROP), order_column (DROP), type (DROP - only 1 address per entity).

### 5.4 projects (53 cols) -> 5 tables

| v3 Column | v4 Table.Column | Action |
| --- | --- | --- |
| **-> projects (core)** | | |
| id | projects.legacy_project_id | Store as mapping key (UNIQUE) |
| title | projects.title | Direct |
| description | projects.description | Direct |
| estate | projects.estate | Direct |
| developer_id | projects.developer_id | Direct FK |
| projecttype_id | projects.projecttype_id | Direct FK |
| stage | projects.stage | Direct (+ statusables LATEST) |
| start_at | DROP | 0% filled |
| end_at | DROP | 0% filled |
| total_lots | DROP | Compute from lots count |
| is_archived | DROP | Derive from stage |
| is_hidden | DROP | Derive from stage |
| is_featured | projects.extra_attributes.is_featured | Move to projects.extra_attributes JSONB |
| is_hot_property | projects.extra_attributes.is_hot_property | Move to projects.extra_attributes JSONB |
| love_reactant_id | DROP | Replaced by project_favorites |
| old_table_id | DROP | Legacy v2 |
| land_info | projects.extra_attributes.land_info | Move to projects.extra_attributes JSONB |
| property_conditions | projects.extra_attributes.property_conditions | Move to projects.extra_attributes JSONB |
| trust_details | projects.extra_attributes.trust_details | Move to projects.extra_attributes JSONB |
| sub_agent_comms | DROP | 0% filled |
| build_time | projects.extra_attributes.build_time | Move to projects.extra_attributes JSONB (3.2%) |
| uuid | DROP | Not needed in v4 (use id) |
| created_by | projects.created_by | Userstamp |
| modified_by | projects.updated_by | Rename |
| created_at | projects.created_at | Direct |
| updated_at | projects.updated_at | Direct |
| deleted_at | projects.deleted_at | Direct |
| - | projects.organization_id | NEW: Set to PIAB org (sync.piab_organization_id) |
| - | projects.legacy_project_id | NEW: sync mapping (UNIQUE) |
| - | projects.synced_at | NEW: sync timestamp |
| - | projects.sync_source | NEW: 'v3', 'v4', 'initial' |
| **-> project_specs** | | |
| bedrooms | DROP | 0.7% - use min/max instead |
| min_bedrooms | project_specs.min_bedrooms | Direct (3.3%) |
| max_bedrooms | project_specs.max_bedrooms | Direct |
| bathrooms | DROP | 0.6% - use min/max instead |
| min_bathrooms | project_specs.min_bathrooms | Direct (3.3%) |
| max_bathrooms | project_specs.max_bathrooms | Direct |
| garage | DROP | 0% at project level (71% at lot level) |
| storeys | DROP | 0.1% at project level (use lot level) |
| min_living_area | project_specs.min_living_area | Direct |
| max_living_area | project_specs.max_living_area | Direct |
| min_landsize | project_specs.min_land_area | Rename |
| max_landsize | project_specs.max_land_area | Rename |
| - | project_specs.living_area | NEW v4 column |
| - | project_specs.internal_area | NEW v4 column |
| - | project_specs.external_area | NEW v4 column |
| - | project_specs.frontage | NEW v4 column |
| - | project_specs.depth | NEW v4 column |
| **-> project_investment** | | |
| min_rent | project_investment.min_rent | Direct |
| max_rent | project_investment.max_rent | Direct |
| avg_rent | project_investment.avg_rent | Direct |
| min_rent_yield | project_investment.min_rent_yield | Direct |
| max_rent_yield | project_investment.max_rent_yield | Direct |
| avg_rent_yield | project_investment.avg_rent_yield | Direct |
| rent_to_sell_yield | project_investment.rent_to_sell_yield | Direct |
| historical_growth | project_investment.historical_growth | Direct |
| avg_price | project_investment.avg_price | Direct |
| is_cashflow_positive | project_investment.is_cashflow_positive | Direct |
| - | project_investment.cap_rate | NEW v4 column |
| - | project_investment.cashflow_weekly | NEW v4 column |
| - | project_investment.cashflow_monthly | NEW v4 column |
| - | project_investment.cashflow_annual | NEW v4 column |
| - | project_investment.depreciation_yr1 | NEW v4 column |
| - | project_investment.depreciation_yr5 | NEW v4 column |
| **-> project_features** | | |
| is_co_living | project_features.is_co_living | Direct |
| is_ndis | project_features.is_ndis | Direct |
| is_smsf | project_features.is_smsf | Direct |
| is_flexi | DROP | 0% filled (3/15481) |
| is_exclusive | project_features.is_exclusive | Direct |
| is_rent_to_sell | project_features.is_rent_to_sell | Direct |
| is_rooming | project_features.is_rooming | Direct |
| is_firb | project_features.is_firb | Direct |
| - | project_features.is_house_land | NEW v4 column |
| - | project_features.is_off_plan | NEW v4 column |
| **-> project_pricing** | | |
| min_price | project_pricing.min_price | Direct |
| max_price | project_pricing.max_price | Direct |
| rates_fees | project_pricing.rates_fees | Direct |
| min_rates_fees | project_pricing.min_rates_fees | Direct |
| max_rates_fees | project_pricing.max_rates_fees | Direct |
| body_corporate_fees | project_pricing.body_corporate_fees | Direct |
| min_body_corporate_fees | project_pricing.min_body_corporate_fees | Direct |
| max_body_corporate_fees | project_pricing.max_body_corporate_fees | Direct |
| - | project_pricing.min_land_price | NEW v4 column |
| - | project_pricing.max_land_price | NEW v4 column |
| - | project_pricing.deposit_amount | NEW v4 column |
| - | project_pricing.deposit_percent | NEW v4 column |
| - | project_pricing.stamp_duty | NEW v4 column |

### 5.5 lots (48 cols) -> lots

| v3 Column | v4 Column | Action |
| --- | --- | --- |
| id | lots.legacy_lot_id | Store as mapping key (UNIQUE) |
| project_id | lots.project_id | Direct FK |
| title | lots.title | Direct |
| lot_number | lots.lot_number | Direct |
| price | lots.price | Direct (Money cast) |
| land_price | lots.land_price | Direct (Money cast) |
| build_price | lots.build_price | Direct (Money cast) |
| total | lots.total_price | Rename (Money cast) |
| stage | lots.stage | Direct (+ statusables LATEST) |
| bedrooms | lots.bedrooms | Direct |
| bathrooms | lots.bathrooms | Direct |
| car | lots.car_spaces | Rename |
| garage | lots.garage | Direct |
| storyes | lots.storeys | Fix typo (`storyes` -> `storeys`) |
| internal | lots.internal_area | Rename |
| external | lots.external_area | Rename |
| land_size | lots.land_area | Rename |
| living_area | lots.living_area | Direct |
| weekly_rent | lots.rent_weekly | Rename |
| rent_yield | lots.rent_yield | Direct |
| rent_to_sell_yield | lots.rent_to_sell_yield | Direct |
| title_status | lots.title_status | Direct |
| title_date | lots.title_date | Direct |
| completion | lots.completion_date | Rename to DATE type |
| level | lots.level | Direct |
| aspect | lots.aspect | Direct |
| view | lots.view | Direct |
| building | lots.building_name | Rename |
| balcony | lots.balcony_area | Rename |
| study | lots.is_study | Rename to boolean |
| storage | lots.is_storage | Rename to boolean |
| powder_room | lots.is_powder_room | Rename to boolean |
| body_corporation | lots.body_corporate | Rename (Money cast) |
| rates | lots.rates | Direct (Money cast) |
| mpr | lots.mpr | Direct (what is this? investigate) |
| floorplan | lots.floorplan_url | Rename (or use media) |
| uuid | lots.uuid | Direct |
| five_percent_share_available | DROP | 0% (28/121K) |
| five_percent_share_price | DROP | 0% (29/121K) |
| is_archived | DROP | Derive from stage |
| is_nras | lots.is_nras | Direct |
| is_cashflow_positive | lots.is_cashflow_positive | Direct |
| is_smsf | lots.is_smsf | Direct |
| sub_agent_comms | DROP | 0% filled |
| old_table_id | DROP | Legacy v2 (56% filled but not needed in v4) |
| created_by | lots.created_by | Userstamp |
| modified_by | lots.updated_by | Rename |
| created_at | lots.created_at | Direct |
| updated_at | lots.updated_at | Direct |
| deleted_at | lots.deleted_at | Direct |
| - | lots.organization_id | NEW: Set to PIAB org (sync.piab_organization_id), synced via trigger from projects |
| - | lots.legacy_lot_id | NEW: sync mapping (UNIQUE) |
| - | lots.synced_at | NEW: sync timestamp |
| - | lots.sync_source | NEW: 'v3', 'v4', 'initial' |

**STATUS**: Section 3.9 lots CREATE TABLE is up-to-date with all these columns.

### 5.6 sales (40 cols) -> sales + commissions

| v3 Column | v4 Destination | Action |
| --- | --- | --- |
| id | sales.legacy_sale_id | Store as mapping key (UNIQUE) |
| client_id | sales.client_contact_id | Remap via legacy_lead_id |
| lot_id | sales.lot_id | Direct FK |
| project_id | sales.project_id | Direct FK |
| developer_id | sales.developer_id | Direct FK |
| sales_agent_id | sales.sales_agent_contact_id | Remap via legacy_lead_id |
| subscriber_id | sales.subscriber_contact_id | Remap. Also used for org_id derivation |
| bdm_id | sales.bdm_contact_id | Remap |
| referral_partner_id | sales.referral_partner_contact_id | Remap |
| affiliate_id | sales.affiliate_contact_id | Remap |
| agent_id | sales.agent_contact_id | Remap |
| purchase_price | sales.purchase_price | Direct (Money cast) |
| finance_due_date | sales.finance_due_date | Keep column (0% in v3, will be used in v4) |
| settlement_date | sales.settlement_date | Direct |
| contract_date | sales.contract_date | Direct |
| status | sales.status | From statusables (LATEST status per record) |
| piab_comm | commissions (type='piab') | Normalize |
| affiliate_comm | commissions (type='affiliate') | Normalize |
| subscriber_comm | commissions (type='subscriber') | Normalize |
| sales_agent_comm | commissions (type='sales_agent') | Normalize |
| bdm_comm | commissions (type='bdm') | Normalize |
| referral_partner_comm | commissions (type='referral_partner') | Normalize |
| agent_comm | commissions (type='sub_agent') | Normalize |
| comms_in_total | DROP | Compute from commissions SUM |
| comms_out_total | DROP | Compute from commissions SUM |
| divide_percent | sales.extra_attributes.divide_percent | Move to JSONB (100% filled) |
| expected_commissions | sales.extra_attributes.expected_commissions | Move to JSONB (75% filled) |
| payment_terms | DROP | 0% filled - never used |
| sas_fee | DROP | 0% - SAS feature never launched |
| sas_percent | DROP | 0% |
| is_sas_enabled | DROP | 0% |
| is_sas_max | DROP | 0% |
| comm_in_notes | DROP | 0.2% (1 row) |
| comm_out_notes | DROP | 0% |
| comments | DROP | 0.9% (4 rows) - use notes table instead |
| summary_note | sales.extra_attributes.summary_note | Move to JSONB |
| is_comments_enabled | DROP | UI preference |
| custom_attributes | sales.extra_attributes | Merge into JSONB |
| created_by | sales.created_by | **NOTE: v3 `created_by` references `leads.id` NOT `users.id`**. Must resolve Lead → User for v4 |
| updated_by | sales.updated_by | Direct |
| created_at | sales.created_at | Direct |
| updated_at | sales.updated_at | Direct |
| deleted_at | sales.deleted_at | Direct |
| - | sales.organization_id | NEW: Derived via `subscriber_id → Lead → User → Organization` (99.6%). For 2 rows without subscriber_id: `created_by → Lead(!) → User → Organization` |
| - | sales.legacy_sale_id | NEW: sync mapping (UNIQUE) |
| - | sales.synced_at | NEW: sync timestamp |
| - | sales.sync_source | NEW: 'v3', 'v4', 'initial' |

**STATUS**: Section 3.10 sales CREATE TABLE has `extra_attributes JSONB`. payment_terms and status_updated_at are DROPPED (0% filled).

### 5.7 property_reservations (29 cols)

| v3 Column | v4 Column | Action |
| --- | --- | --- |
| id | legacy_reservation_id | Store as mapping key (UNIQUE) |
| lot_id | lot_id | Direct FK |
| property_id | project_id | Rename FK |
| purchaser1_id | primary_contact_id | Remap via legacy_lead_id |
| purchaser2_id | secondary_contact_id | Remap |
| agent_id | agent_contact_id | Remap |
| loggedin_agent_id | logged_in_user_id | Remap to users |
| purchase_price | purchase_price | Direct (Money cast) |
| deposit | deposit_amount | Rename |
| land_deposit | DROP | 0% filled |
| build_deposit | DROP | 0% filled |
| purchaser_type | purchaser_type | Direct (JSONB) |
| trustee_name | trustee_name | Direct |
| finance_preapproval | finance_pre_approved | Rename boolean |
| finance_days_req | finance_days_required | Rename |
| abn_acn | abn_acn | Direct |
| family_trust | is_family_trust | Rename boolean |
| bare_trust_setup | is_bare_trust | Rename boolean |
| SMSF_trust_setup | is_smsf_trust | Rename |
| funds_rollover | is_funds_rollover | Rename boolean |
| broker | broker_name | Rename |
| firm | firm_name | Rename |
| agree | is_agreed | Rename boolean |
| agree_date | DROP | 0% filled |
| agree_lawlab | is_lawlab_agreed | Rename boolean |
| contract_send | contract_sent_at | Rename to TIMESTAMPTZ |
| created_at | created_at | Direct |
| updated_at | updated_at | Direct |
| deleted_at | deleted_at | Direct |
| - | organization_id | NEW: multi-tenant |
| - | legacy_reservation_id | NEW: sync mapping (UNIQUE) |
| - | synced_at | NEW: sync timestamp |
| - | sync_source | NEW: 'v3', 'v4', 'initial' |
| - | created_by | NEW: userstamp |
| - | updated_by | NEW: userstamp |

### 5.8 property_enquiries (18 cols)

| v3 Column | v4 Column | Action |
| --- | --- | --- |
| id | id | New PK |
| client_id | client_contact_id | Remap |
| agent_id | agent_contact_id | Remap |
| loggedin_agent_id | logged_in_user_id | Remap |
| property | extra_attributes.property | Move to JSONB |
| preferred_location | extra_attributes.preferred_location | Move to JSONB |
| purchaser_type | extra_attributes.purchaser_type | Move to JSONB |
| requesting_info | extra_attributes.requesting_info | Move to JSONB |
| max_capacity | extra_attributes.max_capacity | Move to JSONB |
| preapproval | extra_attributes.preapproval | Move to JSONB |
| cash_purchase | extra_attributes.cash_purchase | Move to JSONB |
| inspection_date | DROP | 0% filled |
| inspection_time | extra_attributes.inspection_time | Move to JSONB (7.6%) |
| inspection_person | extra_attributes.inspection_person | Move to JSONB |
| instructions | extra_attributes.instructions | Move to JSONB |
| created_at | created_at | Direct |
| updated_at | updated_at | Direct |
| deleted_at | deleted_at | Direct |
| - | organization_id | NEW: multi-tenant |
| - | created_by | NEW: userstamp |
| - | updated_by | NEW: userstamp |
| - | status | From statusables (LATEST status per record) |

### 5.9 property_searches (22 cols)

| v3 Column | v4 Column | Action |
| --- | --- | --- |
| id | id | New PK |
| client_id | client_contact_id | Remap |
| agent_id | agent_contact_id | Remap |
| loggedin_agent_id | logged_in_user_id | Remap |
| purchase_type | search_criteria.purchase_type | JSONB |
| property_type | search_criteria.property_type | JSONB |
| build_status | search_criteria.build_status | JSONB |
| purchaser_type | search_criteria.purchaser_type | JSONB |
| preferred_location | search_criteria.preferred_location | JSONB |
| no_of_bedrooms | search_criteria.bedrooms | JSONB |
| no_of_bathrooms | search_criteria.bathrooms | JSONB |
| no_of_carspaces | search_criteria.car_spaces | JSONB |
| max_capacity | search_criteria.max_capacity | JSONB |
| preapproval | search_criteria.preapproval | JSONB |
| finance | search_criteria.finance | JSONB |
| lender | search_criteria.lender | JSONB |
| lvr | search_criteria.lvr | JSONB |
| extra_instructions | search_criteria.extra_instructions | JSONB |
| property_config_other | search_criteria.property_config_other | JSONB |
| created_at | created_at | Direct |
| updated_at | updated_at | Direct |
| deleted_at | deleted_at | Direct |
| - | organization_id | NEW: multi-tenant |
| - | created_by | NEW: userstamp |
| - | updated_by | NEW: userstamp |
| - | status | From statusables (LATEST status per record) |

### 5.10 partners (7 cols)

| v3 Column | v4 Column | Action |
| --- | --- | --- |
| id | id | New PK |
| lead_id | contact_id | Remap via legacy_lead_id |
| parent_id | parent_partner_id | Rename (self-referencing FK) |
| relationship | partner_type | Rename |
| created_at | created_at | Direct |
| updated_at | updated_at | Direct |
| deleted_at | deleted_at | Direct |
| - | organization_id | NEW |
| - | commission_rate | NEW (was not in v3) |
| - | territory | NEW |
| - | status | NEW |

### 5.11 developers (14 cols)

| v3 Column | v4 Column | Action |
| --- | --- | --- |
| id | id | Direct |
| user_id | user_id | Direct FK |
| is_active | is_active | Direct |
| is_onboard | is_onboard | Direct |
| build_time | build_time | Direct |
| commission_note | commission_note | Direct |
| information_delivery | information_delivery | Direct |
| relationship_status | relationship_status | Direct |
| login_info | login_info | Direct |
| extra_attributes | DROP | 0% filled |
| modified_by | updated_by | Rename |
| created_at | created_at | Direct |
| updated_at | updated_at | Direct |
| deleted_at | deleted_at | Direct |
| - | organization_id | NEW |

### 5.12 companies (8 cols)

| v3 Column | v4 Column | Action |
| --- | --- | --- |
| id | legacy_company_id | Store as mapping key (UNIQUE) |
| name | name | Direct |
| slogan | slogan | Direct |
| bk_name | bk_name | Direct |
| extra_company_info | extra_attributes | Rename to JSONB |
| created_at | created_at | Direct |
| updated_at | updated_at | Direct |
| deleted_at | deleted_at | Direct |
| - | organization_id | NEW |
| - | legacy_company_id | NEW: sync mapping (UNIQUE) |
| - | synced_at | NEW: sync timestamp |
| - | sync_source | NEW: 'v3', 'v4', 'initial' |

### 5.12a suburbs (lookup + `state_id`)

| v3 Column | v4 Column | Action |
| --- | --- | --- |
| id | id | Direct |
| suburb | suburb | Direct |
| postcode | postcode | Direct |
| state | suburbs.state | Keep for migration/backfill source |
| - | state_id | NEW: resolve via `states.short_name` / `long_name` / normalized match on v3 `state` |
| latitude | latitude | Direct |
| longitude | longitude | Direct |
| au_town_id | DROP | Not migrated (see §2B-2) |
| created_at | created_at | Direct |
| updated_at | updated_at | Direct |

### ~~5.13 flyers~~ — REMOVED (rebuild separately in v4, see §2I)

### ~~5.14 campaign_websites~~ — REMOVED (rebuild separately in v4, see §2I)

### 5.15 statusables (149,201 rows) -> model columns

| v3 statusable_type | Rows | v4 column | On table |
| --- | --- | --- | --- |
| App\Models\Lot | 123,534 | lots.stage | lots |
| App\Models\Project | 16,065 | projects.stage | projects |
| App\Models\Lead | 7,675 | contacts.stage | contacts |
| App\Models\PropertyEnquiry | 649 | property_enquiries.status | property_enquiries |
| App\Models\Sale | 447 | sales.status | sales |
| App\Models\PropertySearch | 199 | property_searches.status | property_searches |
| App\Models\PropertyReservation | 110 | property_reservations.status | property_reservations |
| App\Models\FinanceAssessment | 1 | DROP | Table dropped |

Rule: For each record, find LATEST statusable and set as column value.
Note: leads/projects/lots already have `stage` column in v3 - use that as primary, fallback to statusables.

### 5.16 v3 polymorphic commissions (19,602 rows)

| v3 commissionable_type | Rows | v4 Handling |
| --- | --- | --- |
| App\Models\Project | 15,254 | -> commission_templates (starter kit) or project_pricing |
| App\Models\Lot | 4,262 | -> lots commission rate fields or commission_templates |

v3 commission columns: commission_in, commission_out, commission_percent_in, commission_percent_out, commission_percent_profit, commission_profit, extra_attributes.

v3 sales flat columns -> v4 commissions (normalized):
- piab_comm -> sale_id + type='piab' + amount
- affiliate_comm -> sale_id + type='affiliate' + amount
- subscriber_comm -> sale_id + type='subscriber' + amount
- sales_agent_comm -> sale_id + type='sales_agent' + amount
- bdm_comm -> sale_id + type='bdm' + amount
- referral_partner_comm -> sale_id + type='referral_partner' + amount
- agent_comm -> sale_id + type='sub_agent' + amount

---

## 6. Relationship Diagrams

### 6.1 Organization Hub (Multi-Tenancy)

```
                        +------------------+
                        |  organizations   |
                        +--------+---------+
                                 |
         +-----------------------+------------------------+
         |                       |                        |
    +----+-----+          +------+------+          +------+------+
    | contacts |          |  projects   |          |   sales     |
    +----------+          +-------------+          +-------------+
    | companies|          |    lots     |          | commissions |
    | tasks    |          |             |          | comm_tiers  |
    | partners |          | proj_specs  |          | reservations|
    | rels     |          | proj_invest |          | enquiries   |
    | emails   |          | proj_feat   |          | searches    |
    | phones   |          | proj_price  |          | notes       |
    | notes    |          | proj_updates|          | comments    |
    +----------+          +-------------+          +-------------+

    EVERY CRM table has organization_id -> organizations(id)

    organizations.type VARCHAR(20) CHECK (type IN ('platform', 'developer', 'agency')) NOT NULL DEFAULT 'agency'
      - 'platform' = PIAB org (1 org, superadmin)
      - 'developer' = Property developers (imported from v3 developers table — each becomes an org)
      - 'agency' = Subscribers/agents (each v3 subscriber becomes an agency org)
```

### 6.2 Contact Hub

```
                            +---------------+
                    +-------+   contacts    +-------+
                    |       |               |       |
                    |       | org_id (FK)   |       |
                    |       | source_id     |       |
                    |       | company_id    |       |
                    |       | stage (state) |       |
                    |       | lead_score    |       |
                    |       | contact_origin|       |
                    |       +-------+-------+       |
                    |               |               |
         +----------+---------+----+----+---------+---------+
         v          v         v         v         v         v
    +---------+ +---------+ +------+ +------+ +--------+ +------+
    | contact | | contact | | tasks| | notes| |partners| | rels |
    | _emails | | _phones | |      | |(morph)| |       | |      |
    +---------+ +---------+ +------+ +------+ +--------+ +------+

    Also referenced by (as FK):
    +-- sales (7 contact role FKs)
    +-- property_reservations (3 contact FKs)
    +-- property_enquiries (2 contact FKs)
    +-- property_searches (2 contact FKs)
    +-- users (contact_id)
```

### 6.3 Contact Origin Split

```
    contacts
    ├── contact_origin = 'saas_product'
    │     └── Platform leads (interested in FusionCRM product)
    │     └── organization_id = PIAB org
    │     └── Managed by superadmin
    │     └── Goal: convert to subscriber → new Organization
    │
    └── contact_origin = 'property'
          └── Property buyer/renter leads
          └── organization_id = subscriber org
          └── Managed by org team members
          └── Goal: sell/rent a property → Sale → commission
```

### 6.4 Sales Flow

```
    contacts --> sales --> commissions
       |            |         |
       |            |         +-- commission_tiers (rate lookup)
       |            +-- lot_id --> lots --> projects
       |            +-- project_id --> projects
       |            +-- notes (morph)
       |
       +-- client_contact_id
       +-- sales_agent_contact_id
       +-- subscriber_contact_id
       +-- bdm_contact_id
       +-- referral_partner_contact_id
       +-- affiliate_contact_id
       +-- agent_contact_id

    Sale has up to 7 commission records:
    +--------------------------------------------+
    |  commissions                               |
    |  +-- type: piab           amount: $X       |
    |  +-- type: subscriber     amount: $X       |
    |  +-- type: affiliate      amount: $X       |
    |  +-- type: sales_agent    amount: $X       |
    |  +-- type: bdm            amount: $X       |
    |  +-- type: referral_partner amount: $X     |
    |  +-- type: sub_agent      amount: $X       |
    +--------------------------------------------+

    NOTE: v3 Sale.created_by references leads.id (NOT users.id)
```

### 6.5 Project Decomposition

```
                    +---------------+
                    |   projects    | (core: ~15 cols)
                    | Cogneiss\ModuleCrm\Models\Project
                    +-------+-------+
                            |
         +------------------+------------------+
         |                  |                  |
    +----+-----+    +-------+------+    +------+-----+
    | project  |    |  project     |    |  project   |
    |  _specs  |    | _investment  |    | _features  |
    |  (1:1)   |    |   (1:1)      |    |   (1:1)    |
    +----------+    +--------------+    +------------+
         |
    +----+------+
    |  project  |
    |  _pricing |
    |   (1:1)   |
    +-----------+
         |
    +----+-------+
    |    lots    | (1:many)
    | Cogneiss\ModuleCrm\Models\Lot
    |  stage     |
    +-----+------+
          |
    +-----+----------+
    | property_      |
    | reservations   |
    | Cogneiss\ModuleCrm\Models\PropertyReservation
    +----------------+
```

### 6.6 Sync Mapping

```
    v3 (MySQL)                    v4 (PostgreSQL)
    ----------                    ---------------
    leads.id        ---------->   contacts.legacy_lead_id (UNIQUE)
    companies.id    ---------->   companies.legacy_company_id (UNIQUE)
    projects.id     ---------->   projects.legacy_project_id (UNIQUE)
    lots.id         ---------->   lots.legacy_lot_id (UNIQUE)
    sales.id        ---------->   sales.legacy_sale_id (UNIQUE)
    property_reservations.id -->  property_reservations.legacy_reservation_id (UNIQUE)
    contacts.id (email) ------>   contact_emails.legacy_email_id (UNIQUE)
    contacts.id (phone) ------>   contact_phones.legacy_phone_id (UNIQUE)

    Bidirectional entities have synced_at + sync_source columns
    UPSERT via: ON CONFLICT (legacy_*_id) DO UPDATE
```

---

## 7. Data Volume & Migration Order

### Execution Sequence (Sync Dependency Order)

```
Step 0: Bootstrap
+-- PostgreSQL extensions (pgvector, cube, earthdistance) — confirm allow-list on host (§9.1)
+-- Create Organizations from v3 subscriber leads
+-- Seed lookups (sources, developers, projecttypes, states, suburbs)
+-- Configure mysql_legacy connection
+-- Morph map, preventLazyLoading, Spatie Permission roles
+-- Bulk import: prefer COPY / batched inserts; after large loads run ANALYZE; align sequences with setval (§9.4)
+-- Use a separate migration DB role or elevated session only for cutover if policy allows (§9.5)

Step 1: Companies (976 rows) — no FK dependencies
+-- Import companies -> companies (store legacy_company_id)
+-- Set organization_id (PIAB org for shared data)

Step 2: Contacts (9,750 leads -> contacts)
+-- Import leads -> contacts (store legacy_lead_id)
+-- Split v3 polymorphic contacts -> contact_emails (9,163+567) + contact_phones (9,601)
+-- Flatten addresses (9,165 lead) -> contact address columns
+-- Flatten statusables (7,675) -> contacts.stage
+-- Build legacy_lead_id -> contact_id MAP

Step 3: Users (1,503)
+-- Import users + set contact_id from map
+-- Import roles -> Spatie Permission

Step 4: Projects & Lots
+-- Import projects (15,566) -> decompose into 5 tables (store legacy_project_id)
+-- Flatten addresses (15,480) -> project address columns
+-- Flatten statusables (16,065) -> projects.stage
+-- Import lots (121,755) + flatten statusables -> lots.stage (store legacy_lot_id)
+-- Import project_updates (156,393)
+-- Migrate love_reactions (2,199) -> project_favorites

Step 5: Sales & Commissions
+-- Import sales (449) - remap 7 contact FKs + store legacy_sale_id
+-- Normalize flat commission cols -> commissions table
+-- Import property_reservations (120) - remap FKs + store legacy_reservation_id
+-- Import property_enquiries (734) - remap FKs
+-- Import property_searches (318) - remap FKs
+-- Flatten statusables for all transaction models
+-- Seed commission_tiers (new)

Step 6: Supporting Data
+-- Import notes (22,861) - update morph map (store legacy_note_id)
+-- Import comments (4,300) - direct
+-- Import partners (300) - remap FK
+-- Import relationships (7,392) - remap FKs
+-- Import tasks (62) - remap FKs
+-- Import media (109,514) - update morph types
+-- Import resources (138) + categories + groups

Step 7: AI & Verification
+-- Configure AI agents from v3 prompt_commands
+-- Generate pgvector embeddings
+-- Full verification suite
```

### Volume Heat Map (actual v3 row counts)

```
ROWS        TABLE                    COMPLEXITY
----------- ------------------------ ---------------
156,393     project_updates          LOW  (direct copy)
149,201     statusables              N/A  (absorbed into model columns)
121,755     lots                     MED  (clean + stage + sync cols)
109,514     media                    MED  (update morph types)
 24,745     addresses                N/A  (flattened into parents)
 22,861     notes                    MED  (update morph map)
 20,190     contacts (polymorphic)   MED  (split to emails + phones)
 19,602     commissions (poly)       MED  (normalize to sale rows)
 15,566     projects                 HIGH (decompose 53 cols into 5 tables)
 15,566     project_specs            LOW  (derived from projects, 1:1)
 15,566     project_investment       LOW  (derived from projects, 1:1)
 15,566     project_features         LOW  (derived from projects, 1:1)
 15,566     project_pricing          LOW  (derived from projects, 1:1)
 15,299     suburbs                  MED  (+ state_id backfill, DROP au_town_id)
  9,750     leads -> contacts        HIGH (central mapping + sync cols)
  9,601     polymorphic -> phones    MED  (split from polymorphic contacts)
  9,163     polymorphic -> emails    MED  (split from polymorphic contacts)
  7,392     relationships            MED  (FK remap)
  4,300     comments                 LOW  (direct copy)
  2,199     love_reactions           LOW  (-> favorites)
  1,503     users                    MED  (+ contact_id link)
    976     companies                LOW  (+ org_id + sync cols)
    734     property_enquiries       MED  (FK remap)
    449     sales                    HIGH (FK remap + denorm + sync cols)
    336     developers               LOW  (+ org_id)
    318     property_searches        MED  (FK remap)
    300     partners                 MED  (FK remap)
    138     resources                LOW  (+ org_id)
    120     property_reservations    MED  (FK remap + sync cols)
     62     tasks                    LOW  (FK remap)
    NEW     commission_tiers         LOW  (seed data)
    NEW     sync_logs                LOW  (infrastructure)
     17     sources                  LOW  (+ org_id)
     12     projecttypes             LOW  (+ org_id)
      9     resource_groups          LOW  (+ org_id)
      8     states                   LOW  (direct copy)
      5     resource_categories      LOW  (direct copy)
```

---

## 8. Index Strategy

**Canonical definitions:** List, partial, GIN, BRIN, and expression indexes are defined **on each table in §3** where they apply. This section records **principles** and **optional tuning** so we do not duplicate indexes (e.g. one partial index per list pattern: `organization_id` + `stage`/`status` + `deleted_at IS NULL`).

### 8.1 Principles (see [indexing](https://raw.githubusercontent.com/planetscale/database-skills/main/skills/postgres/references/indexing.md), [query patterns](https://raw.githubusercontent.com/planetscale/database-skills/main/skills/postgres/references/query-patterns.md))

| Practice | Application in v4 CRM |
| --- | --- |
| Index every FK used in JOINs/ON DELETE | Covered beside each `REFERENCES` in §3 |
| Tenant + filter + soft delete | Partial composites: `(organization_id, …)` `WHERE deleted_at IS NULL` |
| Avoid redundant indexes | Same column list + same predicate = one index only |
| Covering / INCLUDE | `idx_lots_list` for lot grid queries without heap lookups |
| Expression indexes | `LOWER(email)`, `LOWER(suburb)` for case-insensitive search |
| JSONB | Default GIN on `jsonb` supports `@>`, `?`, keys. If **only** containment on paths matters, consider `USING GIN (col jsonb_path_ops)` for a smaller/faster index (narrower operator set) |

### 8.2 Sync-Specific Indexes

| Index | Table(s) | Purpose |
| --- | --- | --- |
| `legacy_*_id UNIQUE` | All bidirectional entities | UPSERT target for `ON CONFLICT (legacy_*_id) DO UPDATE` |
| `(legacy_*_id, updated_at)` | contacts, companies, projects, lots, sales, property_reservations, contact_emails, contact_phones | Composite for efficient change detection during poll cycles |
| `organization_id` | **Every CRM table** | Tenant scoping and RLS support |
| `contact_origin` | contacts | Filter platform vs property contacts |

### 8.3 BRIN (time-series / append-heavy)

Defined in §3 on `project_updates(created_at)` and `sales(created_at)`. For very large tables, tune `pages_per_range` at create time, e.g.:

```sql
CREATE INDEX idx_project_updates_created_brin ON project_updates
    USING BRIN (created_at) WITH (pages_per_range = 128);
```

Re-evaluate BRIN vs btree if the workload is **random updates** on old rows (BRIN is weaker there).

### 8.4 Post-cutover index audit

After launch, use `pg_stat_user_indexes` / `idx_scan` and the ideas in [index optimization](https://raw.githubusercontent.com/planetscale/database-skills/main/skills/postgres/references/index-optimization.md) to drop unused indexes and merge duplicates. New indexes on large tables prefer **`CREATE INDEX CONCURRENTLY`** outside peak traffic.

### 8.5 Extensions used by this design

```sql
CREATE EXTENSION IF NOT EXISTS vector;       -- pgvector (starter kit / embeddings)
CREATE EXTENSION IF NOT EXISTS cube;
CREATE EXTENSION IF NOT EXISTS earthdistance;
```

Confirm each extension is available on the **target** Postgres (managed providers publish an allow-list). If `earthdistance` is unavailable, keep lat/lng in application logic or adopt **PostGIS** if permitted.

### 8.6 Index summary (approximate)

| Index Type | Count | Purpose |
| --- | --- | --- |
| Primary keys | ~45 | CRM tables |
| FK-supporting / list / partial | ~70+ | As defined in §3 |
| GIN (JSONB) | 4+ | `extra_attributes`, `search_criteria`, enquiries |
| BRIN | 2 | Time-ordered fact tables |
| Expression | 3+ | `LOWER(email)`, `LOWER(suburb)` |
| Partial unique | 3+ | Primary email/phone per contact; active commissions |
| INCLUDE (covering) | 1 | Lot lists |
| Sync (legacy_*_id) | 8 UNIQUE + 8 composite | Bidirectional sync entities |

---

## 9. PostgreSQL Best Practices & Operations

Cross-cutting items aligned with generic Postgres and [PlanetScale Postgres skills](https://github.com/planetscale/database-skills/tree/main/skills/postgres) (schema design, MVCC/VACUUM, monitoring, backups, pooling). Laravel remains the source of truth for business rules unless you adopt RLS.

### 9.1 Extensions & collation

- Enable only extensions the host supports ([extensions / compatibility](https://raw.githubusercontent.com/planetscale/database-skills/main/skills/postgres/references/ps-extensions.md) for PlanetScale; otherwise your provider's list).
- Use one database encoding (UTF-8) and consistent locale; avoid mixing collations across comparable text columns without a documented reason.

### 9.2 Data integrity & triggers

- **`lots.organization_id`:** Keep synchronized with `projects.organization_id` via the trigger in §3.9 (or equivalent application transaction).
- **Commissions:** Partial unique index on active rows (`WHERE deleted_at IS NULL`) + `NULLS NOT DISTINCT` avoids duplicate "open" rows and allows re-creating a commission after a soft delete.
- **Suburbs:** Prefer `state_id` as the relational key; backfill from legacy `state` text during migration.

### 9.3 Schema design references

- Primary keys, FKs, types: [schema design](https://raw.githubusercontent.com/planetscale/database-skills/main/skills/postgres/references/schema-design.md).
- Large tables / retention later: [partitioning](https://raw.githubusercontent.com/planetscale/database-skills/main/skills/postgres/references/partitioning.md) (not required for initial row counts; revisit for `project_updates`, `media`, `activity_log` growth).

### 9.4 Bulk migration & MVCC

- Prefer **`COPY`** (or Laravel batch inserts with chunking) for large tables; avoid single multi-hour transactions holding back xmin ([MVCC / VACUUM](https://raw.githubusercontent.com/planetscale/database-skills/main/skills/postgres/references/mvcc-vacuum.md)).
- After bulk load: **`ANALYZE`** affected tables; **`SELECT setval(...)`** for sequences/`GENERATED` columns if IDs were inserted explicitly.
- Optional: drop non-essential indexes before massive load, rebuild with **`CONCURRENTLY`** after (trade complexity vs peak insert speed).

### 9.5 Security & roles

- Separate **migration** vs **application** DB users; least privilege on the app role (DML only on needed tables).
- When email_settings is rebuilt in v4 (see §2I), treat `password` / `secret_key` as secrets (encryption at rest + KMS/vault patterns where policy requires); never log connection strings or credentials.

### 9.6 Multi-tenancy

- **Application-level:** Global scopes on `organization_id` (current Laravel plan).
- **Database-level RLS:** Optional hardening on CRM tables keyed by `organization_id`; if adopted, policies must match how the starter kit resolves the current org ([process / connection](https://raw.githubusercontent.com/planetscale/database-skills/main/skills/postgres/references/process-architecture.md) — typically `SET app.current_organization_id` per request via middleware).

### 9.7 Connection pooling

- Use a pooler in production ([connection pooling](https://raw.githubusercontent.com/planetscale/database-skills/main/skills/postgres/references/ps-connection-pooling.md), [PgBouncer configuration](https://raw.githubusercontent.com/planetscale/database-skills/main/skills/postgres/references/pgbouncer-configuration.md)). Be aware of **transaction vs session** pooling vs prepared statements / advisory locks.

### 9.8 Monitoring & insights

- Enable **`pg_stat_statements`** where the provider allows it; pair with slow-query logging ([monitoring](https://raw.githubusercontent.com/planetscale/database-skills/main/skills/postgres/references/monitoring.md)).
- PlanetScale: [Insights / CLI](https://raw.githubusercontent.com/planetscale/database-skills/main/skills/postgres/references/ps-insights.md) for slow queries and schema review.

### 9.9 Backups & recovery

- Define RPO/RTO before cutover ([backup and recovery](https://raw.githubusercontent.com/planetscale/database-skills/main/skills/postgres/references/backup-recovery.md)). Managed Postgres usually handles base backups; application still needs **runbook** for logical export (`pg_dump` of CRM schema) before risky migrations.

### 9.10 Optional: full-text search

- If you search long text in `notes.content` or similar, add a **`tsvector`** column maintained by trigger or generated column, plus **GIN** — preferable to `ILIKE '%…%'` at scale ([query patterns](https://raw.githubusercontent.com/planetscale/database-skills/main/skills/postgres/references/query-patterns.md)).

### 9.11 NTP & Clock Alignment (Sync Requirement)

- **Both v3 and v4 servers MUST run NTP** for reliable `updated_at` comparison during bidirectional sync.
- MySQL `TIMESTAMP`/`DATETIME` has **second** precision. PostgreSQL `TIMESTAMPTZ` has **microsecond** precision. The sync poller truncates PostgreSQL timestamps to second precision for comparison.
- Equal-timestamp tiebreaker (within 1-second tolerance): configurable via `sync.tiebreaker_source` (`'v3'` during Phase A, `'v4'` during Phase B).

### 9.12 UPSERT Pattern for Sync

All sync writes use PostgreSQL's `ON CONFLICT ... DO UPDATE` for idempotent upserts:

```sql
INSERT INTO contacts (legacy_lead_id, first_name, last_name, organization_id, ...)
VALUES ($1, $2, $3, $4, ...)
ON CONFLICT (legacy_lead_id) DO UPDATE SET
    first_name = EXCLUDED.first_name,
    last_name = EXCLUDED.last_name,
    synced_at = NOW(),
    sync_source = 'v3',
    updated_at = EXCLUDED.updated_at
WHERE contacts.updated_at < EXCLUDED.updated_at;  -- last write wins
```

This pattern is used for all 8 bidirectional entities. The `WHERE` clause implements last-write-wins conflict resolution.

---

## 10. MySQL→PostgreSQL Type Mapping

Reference table for converting v3 MySQL column types to v4 PostgreSQL equivalents.

| MySQL Type | PostgreSQL Type | Notes |
| --- | --- | --- |
| `TINYINT(1)` | `BOOLEAN` | MySQL uses 0/1, PostgreSQL uses true/false |
| `INT UNSIGNED` | `BIGINT` | No unsigned in PostgreSQL. Use `CHECK (col >= 0)` if non-negative required |
| `BIGINT UNSIGNED` | `BIGINT` | No unsigned in PostgreSQL. Use `CHECK (col >= 0)` if non-negative required |
| `ENUM('a','b')` | `VARCHAR(20) + CHECK` | `CHECK (col IN ('a','b'))`. More flexible than ENUM type |
| `TEXT` | `TEXT` | Same |
| `DATETIME` | `TIMESTAMPTZ` | Always timezone-aware in v4. MySQL DATETIME has no timezone |
| `TIMESTAMP` | `TIMESTAMPTZ` | Always timezone-aware. MySQL TIMESTAMP auto-converts to UTC |
| `JSON` | `JSONB` | Binary JSON, indexable with GIN. Supports `@>`, `?`, `?|`, `?&` operators |
| `DECIMAL(10,2)` | `NUMERIC(10,2)` | Same semantics. `DECIMAL` is alias for `NUMERIC` in PostgreSQL |
| `DECIMAL(8,2) UNSIGNED` | `NUMERIC(8,2)` | Drop unsigned. Add `CHECK (col >= 0)` if non-negative required |
| `VARCHAR(191)` | `VARCHAR(191)` | Same. Or use `TEXT` if no length constraint needed (same perf in PostgreSQL) |
| `INT` | `INTEGER` | Same |
| `DATE` | `DATE` | Same |
| `UUID` (stored as VARCHAR) | `UUID` | Native UUID type in PostgreSQL. More efficient storage and indexing |
| `MEDIUMTEXT` / `LONGTEXT` | `TEXT` | PostgreSQL TEXT has no size limit (up to 1GB) |

**Key differences to watch:**
- PostgreSQL has **no unsigned integer types**. Add CHECK constraints where non-negative values are required.
- MySQL `TIMESTAMP` has second precision. PostgreSQL `TIMESTAMPTZ` has microsecond precision. This matters for sync conflict resolution.
- MySQL `JSON` is stored as text and parsed on each access. PostgreSQL `JSONB` is stored in binary format and is significantly faster for queries.
- PostgreSQL does not have MySQL's `AUTO_INCREMENT`. Use `BIGSERIAL` (shorthand for `BIGINT` + sequence + `NOT NULL`).

---

*Generated from FusionCRM v3 live data analysis + v4 rebuild plan + starter kit delta report. All row counts from production replica queried 2026-03-23.*
