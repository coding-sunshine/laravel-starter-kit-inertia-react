# FusionCRM v4 Database Guide

> Start here. Full SQL DDL: `newdb.md` | Issues: `dbissues.md` | Build steps: `plan.md`
> Sync design: `sync-architecture.md` (forward reference — file will be created next)
>
> **Code lives in `modules/module-crm/`** (not `app/Models/`). See `plan.md` Step 0 for module setup.
> All CRM models use namespace `Cogneiss\ModuleCrm\Models\*` and `BelongsToOrganization` trait for multi-tenant scoping.
> CRM UI = Inertia + React pages. Filament = admin lookup management only.

---

## Organization Types

```
Organization types: 'platform' (PIAB), 'developer' (property developer), 'agency' (subscriber/agent)
organizations.type VARCHAR(20) CHECK (type IN ('platform', 'developer', 'agency')) NOT NULL DEFAULT 'agency'
```

---

## How to Read This Document

```
TABLE_NAME  [step]  SYNC_MARKER  ← v3 source table(s)
> org: <how organization_id is derived>

  column_name          type              <- v3_column
  ─── section ───                        <- visual grouping
```

| Marker | Meaning |
| --- | --- |
| `<- v3_col` | Migrated directly |
| `<- RENAMED` | Same data, new name |
| `<- REMAPPED` | FK remapped via legacy_lead_id -> contact_id |
| `<- FLATTENED` | From polymorphic table -> absorbed into this column |
| `<- JSONB` | Multiple v3 columns merged into extra_attributes |
| `NEW` | Doesn't exist in v3 |
| `⇄` | Bidirectional sync (v3↔v4, event-driven) |
| `→` | One-way sync (v3→v4, batch every 30min) |
| `—` | No sync (v4-only, new table) |

**FK Rules (all enforced)**:
- `organization_id` -> `ON DELETE CASCADE` (delete org = delete all its data)
- Parent entity FKs -> `ON DELETE CASCADE` (e.g. contact_id on emails/phones)
- Reference FKs -> `ON DELETE SET NULL` (e.g. source_id, company_id, created_by)

---

## Standard Columns (on every CRM table)

Every CRM table has these columns (not repeated per table):

```
id                bigint PK
organization_id   bigint FK CASCADE
created_by        bigint FK SET NULL
updated_by        bigint FK SET NULL
created_at        timestamptz
updated_at        timestamptz
deleted_at        timestamptz
```

Bidirectional sync tables (⇄) also have:

```
legacy_*_id       bigint UNIQUE
synced_at         timestamptz
sync_source       varchar(10)   'v3' | 'v4' | 'initial'
```

---

## Quick Summary

```
v3 MySQL (88 tables)               v4 PostgreSQL

  24 tables ──IMPORT──> 45 CRM tables (33 original + 12 builder portal)
  11 tables ──ABSORB──> (merged into above)
  53 tables ──SKIP────> not migrated (includes 12 rebuild-separately)
                    +   ~125 starter kit tables
                    =   ~170 total v4 tables
```

---

## SKIP LIST (42 tables + 12 rebuild-separately)

**Dead/Empty (23):** action_events, default_fonts, failed_jobs, model_has_permissions, oauth_access_tokens, oauth_auth_codes, oauth_clients, oauth_personal_access_clients, oauth_refresh_tokens, personal_access_tokens, settings, taggables, tags, team_user, teams, widget_settings, campaign_website_templates, finance_assessments, potential_properties, flyer_templates, spr_requests, survey_questions, questionnaires

**WordPress (3):** wordpress_websites, wordpress_templates, onlineform_contacts

**Replaced by starter kit (9):** activity_log, column_management, ad_managements, login_histories, user_api_tokens, user_api_ips, file_types, developer_state, password_resets

**Replaced by Spatie Permission (4):** permissions, roles, role_has_permissions, love_reaction_types

**AI bots -> laravel/ai (3):** ai_bot_boxes, ai_bot_categories, ai_bot_prompt_commands

**Framework (2):** migrations, sessions | **Stale (2):** services, company_service | **Geo (1):** au_towns

**Rebuild separately in v4 (12):** flyers, campaign_websites, campaign_website_project, websites, website_pages, website_elements, website_contacts, mail_lists, mail_list_contacts (NEW), brochure_mail_jobs, mail_job_statuses, email_settings — these features will be rebuilt from scratch in v4, not migrated from v3.

---

## ABSORB LIST (11 tables)

```
statusables       ->  .stage / .status column on each model
addresses         ->  contacts.address_* + projects.address_* columns
contacts/detail   ->  contact_emails + contact_phones + contacts.extra_attributes
commissions/poly  ->  commission_templates + normalized commissions table
love_reactants    ->  project_favorites pivot
model_has_roles   ->  Spatie Permission model_has_roles
love_reactions    ->  project_favorites pivot
love_reacters     ->  project_favorites pivot
statuses          ->  discarded (spatie/model-states replaces)
love_counters     ->  discarded (compute from favorites)
love_totals       ->  discarded (compute from favorites)
```

---

## STEP 1: CONTACTS

---

### contacts  [Step 1]  ⇄  ← leads + addresses + statusables
> org: subscriber → Lead → User → Org

```
  legacy_lead_id        bigint UNIQUE        <- leads.id
  ─── identity ───
  contact_origin        varchar(20) CHECK    NEW 'property' | 'saas_product'
  first_name            varchar NOT NULL     <- leads.first_name
  last_name             varchar              <- leads.last_name
  job_title             varchar              <- leads.job_title
  type                  varchar(30) CHECK    <- leads.is_partner REMAPPED to enum
  stage                 varchar(30)          <- leads.stage + statusables FLATTENED (model-states)
  ─── references ───
  source_id             bigint FK SET NULL   <- leads.source_id
  company_id            bigint FK SET NULL   <- leads.company_id
  lead_score            smallint CHECK 0-100 NEW
  ─── dates ───
  last_contacted_at     timestamptz          NEW
  next_followup_at      timestamptz          <- leads.next_followup_at
  last_followup_at      timestamptz          <- leads.last_followup_at
  ─── address ───
  address_line1         varchar              <- addresses.street FLATTENED
  address_line2         varchar              <- addresses.unit FLATTENED
  city                  varchar              <- addresses.suburb FLATTENED
  state                 varchar(50)          <- addresses.state FLATTENED
  postcode              varchar(10)          <- addresses.postcode FLATTENED
  country               varchar(50)          NEW default 'AU'
  ─── flexible data ───
  extra_attributes      jsonb GIN            <- leads.extra_attributes + important_note + summary_note + social links
```

---

### contact_emails  [Step 1]  ⇄  ← contacts/detail WHERE type LIKE 'email%'
> org: parent contact's organization_id

```
  contact_id            bigint FK CASCADE    <- contacts.model_id via legacy map
  legacy_email_id       bigint UNIQUE        <- v3 contacts.id
  type                  varchar(20) CHECK    NEW 'work|home|other'
  email                 varchar NOT NULL     <- contacts.value
  is_primary            boolean              <- type='email_1' -> true, 'email_2' -> false
  order_column          smallint             <- contacts.order_column
```

---

### contact_phones  [Step 1]  ⇄  ← contacts/detail WHERE type='phone'
> org: parent contact's organization_id | validation: propaganistas/laravel-phone

```
  contact_id            bigint FK CASCADE    <- contacts.model_id via legacy map
  legacy_phone_id       bigint UNIQUE        <- v3 contacts.id
  type                  varchar(20) CHECK    NEW 'work|home|mobile|other'
  phone                 varchar(50) NOT NULL <- contacts.value
  is_primary            boolean              NEW default true for first
  order_column          smallint             <- contacts.order_column
```

---

### Social links mapping

```
  v3 type='website'    -> contacts.extra_attributes.website
  v3 type='facebook'   -> contacts.extra_attributes.facebook
  v3 type='linkedin'   -> contacts.extra_attributes.linkedin
  v3 type='youtube'    -> contacts.extra_attributes.youtube
  v3 type='skype'      -> contacts.extra_attributes.skype
  v3 type='twitter'    -> contacts.extra_attributes.twitter
  v3 type='pinterest'  -> contacts.extra_attributes.pinterest
  v3 type='googleplus' -> DROP (dead platform)
```

---

## STEP 2: USERS

---

### users  [Step 2]  →  ← users + model_has_roles
> org: created_by → User → Org | Starter kit already has users table - import data into it

```
  Import: name, email, password, email_verified_at, timestamps, deleted_at
  Add:    contact_id bigint FK SET NULL (set after contacts imported, using legacy map)
  Roles:  model_has_roles -> Spatie Permission roles
  DROP:   all 44 v3-specific columns (CRM data now on contacts/org_settings)
```

---

## STEP 2 (cont): LOOKUPS

---

### sources  [Step 2]  →  ← sources
> org: current user's organization

```
  name                  varchar NOT NULL     <- sources.label RENAMED
  description           text                 NEW
  UNIQUE(organization_id, name)
```

---

### companies  [Step 2]  ⇄  ← companies
> org: subscriber → Lead → User → Org

```
  legacy_company_id     bigint UNIQUE        <- companies.id
  name                  varchar NOT NULL     <- companies.name
  bk_name               varchar              <- companies.bk_name
  slogan                varchar              <- companies.slogan
  extra_attributes      jsonb                <- companies.extra_company_info RENAMED
  UNIQUE(organization_id, name)
```

---

### developers  [Step 2]  →  ← developers
> org: PIAB org (shared marketplace listing)

```
  user_id               bigint FK SET NULL   <- developers.user_id
  is_active             boolean              <- developers.is_active
  is_onboard            boolean              <- developers.is_onboard
  build_time            varchar              <- developers.build_time
  commission_note       text                 <- developers.commission_note
  information_delivery  text                 <- developers.information_delivery
  relationship_status   varchar              <- developers.relationship_status
  login_info            text                 <- developers.login_info
```

---

### projecttypes  [Step 2]  →  ← projecttypes
> org: PIAB org (shared marketplace listing)

```
  title                 varchar NOT NULL     <- projecttypes.title
  UNIQUE(organization_id, title)
```

---

### states  [Step 2]  —  ← states (shared geographic, no org_id)

```
  long_name             varchar NOT NULL     <- states.long_name
  short_name            varchar(10) NOT NULL <- states.short_name
  (no organization_id — shared reference table)
```

---

### suburbs  [Step 2]  →  ← suburbs
> no org_id (shared geographic)

```
  suburb                varchar NOT NULL     <- suburbs.suburb
  postcode              varchar(10)          <- suburbs.postcode
  state_id              bigint FK SET NULL   <- backfilled from v3 state string
  state                 varchar(50)          <- suburbs.state (keep until backfill verified)
  latitude              numeric(10,7)        <- suburbs.latitude
  longitude             numeric(10,7)        <- suburbs.longitude
  (no organization_id — shared reference table)
```

---

## STEP 3: PROJECTS & LOTS

---

### projects  [Step 3]  ⇄  ← projects (63 cols DECOMPOSED into 5 tables) + addresses + statusables
> org: PIAB org (shared marketplace listing)

```
  legacy_project_id     bigint UNIQUE        <- projects.id
  ─── core ───
  title                 varchar NOT NULL     <- projects.title
  description           text                 <- projects.description
  stage                 varchar(30)          <- projects.stage + statusables FLATTENED (model-states)
  estate                varchar              <- projects.estate
  developer_id          bigint FK SET NULL   <- projects.developer_id
  projecttype_id        bigint FK SET NULL   <- projects.projecttype_id
  ─── address ───
  address_line1         varchar              <- addresses.street FLATTENED
  address_line2         varchar              <- addresses.unit FLATTENED
  city                  varchar              <- addresses.suburb FLATTENED
  state                 varchar(50)          <- addresses.state FLATTENED
  postcode              varchar(10)          <- addresses.postcode FLATTENED
  latitude              numeric(10,7)        <- addresses.latitude FLATTENED
  longitude             numeric(10,7)        <- addresses.longitude FLATTENED
  ─── flexible ───
  extra_attributes      jsonb GIN            <- is_featured, is_hot_property, land_info, property_conditions, trust_details, build_time
```

---

### project_specs  [Step 3]  —  ← projects building spec columns
> org: PIAB org (shared marketplace listing)

```
  project_id            bigint FK CASCADE    UNIQUE, 1:1
  min_bedrooms          smallint             <- projects.min_bedrooms
  max_bedrooms          smallint             <- projects.max_bedrooms
  min_bathrooms         smallint             <- projects.min_bathrooms
  max_bathrooms         smallint             <- projects.max_bathrooms
  living_area           numeric(8,2)         NEW
  min_living_area       numeric(8,2)         <- projects.min_living_area
  max_living_area       numeric(8,2)         <- projects.max_living_area
  internal_area         numeric(8,2)         NEW
  external_area         numeric(8,2)         NEW
  land_area             numeric(10,2)        NEW
  min_land_area         numeric(10,2)        <- projects.min_landsize RENAMED
  max_land_area         numeric(10,2)        <- projects.max_landsize RENAMED
  frontage              numeric(8,2)         NEW
  depth                 numeric(8,2)         NEW
```

---

### project_investment  [Step 3]  —  ← projects financial columns
> org: PIAB org (shared marketplace listing)

```
  project_id            bigint FK CASCADE    UNIQUE, 1:1
  min_rent              numeric(10,2)        <- projects.min_rent
  max_rent              numeric(10,2)        <- projects.max_rent
  avg_rent              numeric(10,2)        <- projects.avg_rent
  min_rent_yield        numeric(5,2)         <- projects.min_rent_yield
  max_rent_yield        numeric(5,2)         <- projects.max_rent_yield
  avg_rent_yield        numeric(5,2)         <- projects.avg_rent_yield
  rent_to_sell_yield    numeric(5,2)         <- projects.rent_to_sell_yield
  cap_rate              numeric(5,2)         NEW
  avg_price             numeric(12,2)        <- projects.avg_price
  historical_growth     numeric(5,2)         <- projects.historical_growth
  is_cashflow_positive  boolean              <- projects.is_cashflow_positive
  cashflow_weekly       numeric(10,2)        NEW
  cashflow_monthly      numeric(10,2)        NEW
  cashflow_annual       numeric(12,2)        NEW
  depreciation_yr1      numeric(12,2)        NEW
  depreciation_yr5      numeric(12,2)        NEW
```

---

### project_features  [Step 3]  —  ← projects boolean flags
> org: PIAB org (shared marketplace listing)

```
  project_id            bigint FK CASCADE    UNIQUE, 1:1
  is_co_living          boolean              <- projects.is_co_living
  is_ndis               boolean              <- projects.is_ndis
  is_smsf               boolean              <- projects.is_smsf
  is_exclusive          boolean              <- projects.is_exclusive
  is_rent_to_sell       boolean              <- projects.is_rent_to_sell
  is_rooming            boolean              <- projects.is_rooming
  is_firb               boolean              <- projects.is_firb
  is_house_land         boolean              NEW
  is_off_plan           boolean              NEW
```

---

### project_pricing  [Step 3]  —  ← projects pricing columns
> org: PIAB org (shared marketplace listing) | money: akaunting/laravel-money

```
  project_id            bigint FK CASCADE    UNIQUE, 1:1
  min_price             numeric(12,2)        <- projects.min_price
  max_price             numeric(12,2)        <- projects.max_price
  min_land_price        numeric(12,2)        NEW
  max_land_price        numeric(12,2)        NEW
  deposit_amount        numeric(12,2)        NEW
  deposit_percent       numeric(5,2)         NEW
  stamp_duty            numeric(12,2)        NEW
  rates_fees            numeric(10,2)        <- projects.rates_fees
  min_rates_fees        numeric(10,2)        <- projects.min_rates_fees
  max_rates_fees        numeric(10,2)        <- projects.max_rates_fees
  body_corporate_fees   numeric(10,2)        <- projects.body_corporate_fees
  min_body_corporate_fees numeric(10,2)      <- projects.min_body_corporate_fees
  max_body_corporate_fees numeric(10,2)      <- projects.max_body_corporate_fees
```

---

### lots  [Step 3]  ⇄  ← lots (48 cols) + statusables
> org: PIAB org (shared marketplace listing) | money: akaunting/laravel-money

```
  project_id            bigint FK CASCADE    <- lots.project_id
  legacy_lot_id         bigint UNIQUE        <- lots.id
  ─── identity ───
  lot_number            varchar(50)          <- lots.lot_number
  title                 varchar              <- lots.title
  uuid                  uuid                 <- lots.uuid
  stage                 varchar(30) CHECK    <- lots.stage + statusables FLATTENED (model-states)
  ─── pricing ───
  price                 numeric(12,2)        <- lots.price
  land_price            numeric(12,2)        <- lots.land_price
  build_price           numeric(12,2)        <- lots.build_price
  total_price           numeric(12,2)        <- lots.total RENAMED
  body_corporate        numeric(10,2)        <- lots.body_corporation RENAMED
  rates                 numeric(10,2)        <- lots.rates
  ─── specs ───
  bedrooms              smallint             <- lots.bedrooms
  bathrooms             smallint             <- lots.bathrooms
  car_spaces            smallint             <- lots.car RENAMED
  garage                smallint             <- lots.garage
  storeys               smallint             <- lots.storyes TYPO FIXED
  internal_area         numeric(8,2)         <- lots.internal RENAMED
  external_area         numeric(8,2)         <- lots.external RENAMED
  land_area             numeric(10,2)        <- lots.land_size RENAMED
  living_area           numeric(8,2)         <- lots.living_area
  balcony_area          numeric(8,2)         <- lots.balcony RENAMED
  level                 varchar(50)          <- lots.level
  aspect                varchar(100)         <- lots.aspect
  view                  varchar              <- lots.view
  building_name         varchar              <- lots.building RENAMED
  ─── financials ───
  rent_weekly           numeric(8,2)         <- lots.weekly_rent RENAMED
  rent_yield            numeric(5,2)         <- lots.rent_yield
  rent_to_sell_yield    numeric(5,2)         <- lots.rent_to_sell_yield
  mpr                   numeric(10,2)        <- lots.mpr
  ─── details ───
  title_status          varchar(50)          <- lots.title_status
  title_date            date                 <- lots.title_date
  completion_date       date                 <- lots.completion RENAMED
  floorplan_url         varchar(500)         <- lots.floorplan RENAMED
  ─── booleans ───
  is_study              boolean              <- lots.study RENAMED
  is_storage            boolean              <- lots.storage RENAMED
  is_powder_room        boolean              <- lots.powder_room RENAMED
  is_nras               boolean              <- lots.is_nras
  is_cashflow_positive  boolean              <- lots.is_cashflow_positive
  is_smsf               boolean              <- lots.is_smsf
```

---

### project_favorites  [Step 3]  —  ← love_reactions (5 tables -> 1 pivot)
> org: current user's organization

```
  user_id               bigint FK CASCADE PK <- love_reacters -> users
  project_id            bigint FK CASCADE PK <- love_reactants -> projects
  created_at            timestamptz          <- love_reactions.created_at
```

---

### project_updates  [Step 3]  →  ← project_updates (direct copy)
> org: PIAB org (shared marketplace listing)

```
  project_id            bigint FK CASCADE    <- project_updates.project_id
  user_id               bigint FK SET NULL   <- project_updates.user_id
  type                  varchar(30)          <- project_updates.type
  extra_attributes      jsonb                <- project_updates.extra_attributes
```

---

## STEP 4: SALES & COMMISSIONS

---

### sales  [Step 4]  ⇄  ← sales (40 cols) + statusables
> org: created_by → Lead → User → Org
> 7 flat commission cols REMOVED -> commissions table
> 7 contact FKs REMAPPED: lead_id -> contact_id via legacy map
> NOTE: v3 Sale.created_by references leads.id (NOT users.id)

```
  legacy_sale_id        bigint UNIQUE        <- sales.id
  ─── contact roles (all REMAPPED, ON DELETE SET NULL) ───
  client_contact_id     bigint FK SET NULL   <- sales.client_id
  sales_agent_contact_id bigint FK SET NULL  <- sales.sales_agent_id
  subscriber_contact_id bigint FK SET NULL   <- sales.subscriber_id
  bdm_contact_id        bigint FK SET NULL   <- sales.bdm_id
  referral_partner_contact_id bigint FK      <- sales.referral_partner_id
  affiliate_contact_id  bigint FK SET NULL   <- sales.affiliate_id
  agent_contact_id      bigint FK SET NULL   <- sales.agent_id
  ─── property refs ───
  lot_id                bigint FK SET NULL   <- sales.lot_id
  project_id            bigint FK SET NULL   <- sales.project_id
  developer_id          bigint FK SET NULL   <- sales.developer_id
  ─── sale data ───
  status                varchar(30)          <- statusables FLATTENED (model-states)
  purchase_price        numeric(12,2)        NEW (derive from lot price)
  finance_due_date      date                 <- sales.finance_due_date
  settlement_date       date                 NEW
  contract_date         date                 NEW
  ─── flexible ───
  extra_attributes      jsonb                <- divide_percent + expected_commissions + summary_note + custom_attributes
```

---

### commissions  [Step 4]  →  ← sales.piab_comm etc. + commissions polymorphic
> org: current user's organization

```
  mapping:  piab_comm -> type='piab', subscriber_comm -> 'subscriber',
            affiliate_comm -> 'affiliate', sales_agent_comm -> 'sales_agent',
            bdm_comm -> 'bdm', referral_partner_comm -> 'referral_partner',
            agent_comm -> 'sub_agent'

  sale_id               bigint FK CASCADE    <- derived from sale
  legacy_commission_id  bigint UNIQUE        NEW
  commission_type       varchar(30) CHECK    <- mapped from column name
  agent_user_id         bigint FK SET NULL   NEW nullable
  rate_percentage       numeric(5,4)         NEW
  amount                numeric(12,2)        <- sales.{type}_comm value
  override_amount       numeric(12,2)        NEW
  notes                 text                 NEW

  UNIQUE: (sale_id, commission_type, agent_user_id) WHERE deleted_at IS NULL
```

---

### commission_tiers  (NEW)  [Step 4]  —
> org: current user's organization | Volume-based commission rate lookup

```
  name                  varchar NOT NULL     NEW
  min_sales_count       integer NOT NULL     NEW default 0
  max_sales_count       integer              NEW
  commission_rate       numeric(5,4) NOT NULL NEW
  is_active             boolean              NEW default true
```

---

### property_reservations  [Step 4]  ⇄  ← property_reservations (29 cols) + statusables
> org: subscriber → Lead → User → Org

```
  legacy_reservation_id bigint UNIQUE        NEW
  lot_id                bigint FK SET NULL   <- lot_id
  project_id            bigint FK SET NULL   <- property_id RENAMED
  ─── contacts (REMAPPED, ON DELETE SET NULL) ───
  agent_contact_id      bigint FK SET NULL   <- agent_id REMAPPED
  primary_contact_id    bigint FK SET NULL   <- purchaser1_id REMAPPED
  secondary_contact_id  bigint FK SET NULL   <- purchaser2_id REMAPPED
  logged_in_user_id     bigint FK SET NULL   <- loggedin_agent_id REMAPPED
  ─── financial ───
  purchase_price        numeric(12,2)        <- purchase_price
  deposit_amount        numeric(12,2)        <- deposit RENAMED
  status                varchar(30)          <- statusables FLATTENED (model-states)
  ─── purchaser ───
  purchaser_type        jsonb                <- purchaser_type
  trustee_name          varchar              <- trustee_name
  finance_pre_approved  boolean              <- finance_preapproval RENAMED
  finance_days_required smallint             <- finance_days_req RENAMED
  abn_acn               varchar(50)          <- abn_acn
  ─── trust/legal ───
  is_family_trust       boolean              <- family_trust RENAMED
  is_bare_trust         boolean              <- bare_trust_setup RENAMED
  is_smsf_trust         boolean              <- SMSF_trust_setup RENAMED
  is_funds_rollover     boolean              <- funds_rollover RENAMED
  ─── broker ───
  broker_name           varchar              <- broker RENAMED
  firm_name             varchar              <- firm RENAMED
  ─── agreement ───
  is_agreed             boolean              <- agree RENAMED
  is_lawlab_agreed      boolean              <- agree_lawlab RENAMED
  contract_sent_at      timestamptz          <- contract_send RENAMED
```

---

### property_enquiries  [Step 4]  →  ← property_enquiries (18 cols) + statusables
> org: subscriber → Lead → User → Org

```
  client_contact_id     bigint FK SET NULL   <- client_id REMAPPED
  agent_contact_id      bigint FK SET NULL   <- agent_id REMAPPED
  logged_in_user_id     bigint FK SET NULL   <- loggedin_agent_id REMAPPED
  status                varchar(30)          <- statusables FLATTENED (model-states)
  extra_attributes      jsonb                <- property, purchaser_type, requesting_info, cash_purchase, instructions, preapproval, max_capacity, preferred_location, inspection_time, inspection_person
```

---

### property_searches  [Step 4]  →  ← property_searches (22 cols) + statusables
> org: subscriber → Lead → User → Org

```
  client_contact_id     bigint FK SET NULL   <- client_id REMAPPED
  agent_contact_id      bigint FK SET NULL   <- agent_id REMAPPED
  logged_in_user_id     bigint FK SET NULL   <- loggedin_agent_id REMAPPED
  status                varchar(30)          <- statusables FLATTENED (model-states)
  search_criteria       jsonb                <- property_type, build_status, purchaser_type, preferred_location, bedrooms/bathrooms/carspaces, max_capacity, purchase_type, finance, extra_instructions, lender, lvr, config_other
```

---

## STEP 5: SUPPORTING DATA

---

### tasks  [Step 5]  →  ← tasks
> org: created_by → User → Org

```
  title                 varchar NOT NULL     <- tasks.title
  description           text                 <- tasks.description
  type                  varchar(30)          <- tasks.type
  assigned_to_user_id   bigint FK SET NULL   <- tasks.assigned_id RENAMED
  assigned_contact_id   bigint FK SET NULL   NEW
  attached_contact_id   bigint FK SET NULL   <- tasks.attached_id RENAMED + REMAPPED
  is_complete           boolean              <- tasks.is_complete
  due_at                timestamptz          <- tasks.start_at RENAMED
  completed_at          timestamptz          NEW
```

---

### notes  [Step 5]  →  ← notes (morph map: App\Models\Lead -> 'contact')
> org: created_by → User → Org | Sale | Lead | Project

```
  legacy_note_id        bigint UNIQUE        NEW
  noteable_type         varchar(50)          <- notes.noteable_type (UPDATE morph map)
  noteable_id           bigint               <- notes.noteable_id (REMAP lead IDs -> contact IDs)
  author_id             bigint FK SET NULL   <- notes.author_id
  content               text NOT NULL        <- notes.content
  type                  varchar(20) CHECK    <- notes.type ('note|email_draft|system')
  is_pinned             boolean              NEW
```

---

### crm_comments  [Step 5]  →  ← comments (starter kit compatible)
> org: created_by → User → Org | PropertyEnquiry | PropertySearch | PropertyReservation

```
  Direct copy: commentable_type (update morph map), commentable_id,
               user_id, comment, is_approved, timestamps
```

---

### partners  [Step 5]  →  ← partners
> org: subscriber → Lead → User → Org

```
  contact_id            bigint FK CASCADE    <- partners.lead_id REMAPPED
  parent_partner_id     bigint FK SET NULL   <- partners.parent_id RENAMED (self-ref)
  partner_type          varchar(50)          <- partners.relationship RENAMED
  status                varchar(30)          NEW
  commission_rate       numeric(5,2)         NEW
  territory             varchar              NEW
```

---

### relationships  [Step 5]  →  ← relationships
> org: subscriber → Lead → User → Org

```
  account_contact_id    bigint FK CASCADE    <- relationships.account_id RENAMED + REMAPPED
  relation_contact_id   bigint FK CASCADE    <- relationships.relation_id RENAMED + REMAPPED
  relation_type         varchar(30) CHECK    <- relationships.relation_type (agent|partner|referral|colleague|family|spouse)
```

---

### media  [Step 5]  →  ← media (Spatie media library format)
> org: parent entity's organization_id | Project | Sale | Lead

```
  Only change: update model_type to match morph map
    App\Models\Lead -> 'contact'
    App\Models\Project -> 'project'    etc.
```

---

## STEP 5 (cont): RESOURCES

---

### resource_categories  [Step 5]  →  ← resource_categories
> No org_id (shared reference table)

```
  title                 varchar NOT NULL     <- resource_categories.title
  slug                  varchar              <- resource_categories.slug
  files_type_allowed    varchar              <- resource_categories.files_type_allowed
  (no organization_id — shared reference table)
```

---

### resource_groups  [Step 5]  →  ← resource_groups
> org: PIAB org (shared marketplace listing)

```
  resource_category_id  bigint FK CASCADE    <- resource_groups.resource_category_id
  title                 varchar NOT NULL     <- resource_groups.title
  slug                  varchar              <- resource_groups.slug
```

---

### resources  [Step 5]  →  ← resources
> org: PIAB org (shared marketplace listing)

```
  title                 varchar NOT NULL     <- resources.title
  slug                  varchar              <- resources.slug
  url                   varchar(500)         <- resources.url
  resource_group_id     bigint FK SET NULL   <- resources.resource_group_id
  order_column          smallint             <- resources.order_column
```

---

## SYNC INFRASTRUCTURE

---

### sync_logs  (NEW)  [Step 0]  —
> Audit trail for bidirectional v3-v4 sync operations

```
  entity_type           varchar(50) NOT NULL NEW 'contact' | 'project' | etc.
  entity_id             bigint NOT NULL      NEW
  direction             varchar(10) NOT NULL NEW 'v3_to_v4' | 'v4_to_v3'
  action                varchar(10) NOT NULL NEW 'created|updated|skipped|conflict|failed|orphaned'
  legacy_id             bigint               NEW
  payload               jsonb                NEW
  synced_at             timestamptz NOT NULL NEW default NOW()
  error                 text                 NEW
```

---

## STARTER KIT (~125 tables - pre-built, start empty)

**Architecture** (updated 2026-03-23):
- Telescope REMOVED -> **Laravel Pulse** + **Spatie Health**
- Filament TWO panels: **Admin** (CRM resources) + **System** (superadmin only)
- Module system: `modules/` (announcements, blog, changelog, contact, dashboards, gamification, help, reports)
- Scaffold: `php artisan make:model:full --panel=admin`
- Spatie Onboard: already integrated (3 steps in OnboardingServiceProvider.php)
- Dashboard + Report builders: Puck-based (`modules/dashboards/`, `modules/reports/`)

| Domain | Count | Key Tables |
| --- | --- | --- |
| Framework | 14 | users, organizations, sessions, cache, jobs, Sanctum |
| Monitoring | 6 | pulse_* (5), health_check_result_history_items |
| AI & Agents | 13 | ai_bots, agent_conversations, model_embeddings |
| Billing | 9 | credits, invoices, payment_gateways (Stripe only) |
| Email | 12 | email_campaigns, nurture_sequences, mail_templates |
| Workflows | 8 | workflows, automation_rules, funnel_templates |
| Deals | 4 | deals, deal_documents, lead_scores |
| Custom Fields | 2 | custom_fields, custom_field_values |
| Affiliates | 4 | affiliates, affiliate_commissions, referrals |
| Engagement | 9 | achievements, streaks, levels |
| CMS | 10 | pages, posts, help_articles |
| Spatie | 6 | activity_log, media, tags, permissions |
| Flags | 3 | features, flags |
| Notifications | 4 | notifications, push_history |
| Integrations | 8 | xero_*, webhooks, social_accounts |
| Property (Builder Portal) | 13 | packages, construction_designs, construction_facades, construction_inclusions, package_distributions, agent_favorites, agent_saved_searches, enquiries, stock_imports |
| Analytics | 7 | attributions, pan_analytics |
| Misc | 5 | onboarding, imports/exports |

---

## BUILDER PORTAL TABLES (12 new — v4 only, no v3 source)

> These tables are created in Phase 1 (Builder Portal). No data import — builders create fresh data.
> Full DDL: `newdb.md` §3.31-3.42

```
construction_designs  [Phase 1]  —  ← NEW
> org: builder's organization_id

  name                varchar(255)              NEW
  slug                varchar(255) UNIQUE/org   NEW
  width_m             decimal(6,2)              NEW
  depth_m             decimal(6,2)              NEW
  floor_area_sqm      decimal(8,2)              NEW
  bedrooms            smallint                  NEW
  bathrooms           smallint                  NEW
  garages             smallint                  NEW
  living_areas        smallint                  NEW
  storeys             smallint DEFAULT 1        NEW
  property_type       varchar(30) CHECK         NEW  (house/duplex/townhouse/unit/villa/etc.)
  build_price         decimal(12,2)             NEW
  range_name          varchar(100)              NEW
  brand               varchar(100)              NEW
  is_active           boolean DEFAULT true      NEW
  extra_attributes    jsonb                     NEW
```

```
construction_facades  [Phase 1]  —  ← NEW
> org: builder's organization_id

  name                varchar(255)              NEW
  slug                varchar(255) UNIQUE/org   NEW
  colour_scheme       varchar(100)              NEW
  elevation           varchar(100)              NEW
  storey_type         varchar(20)               NEW
  facade_price        decimal(12,2)             NEW
  is_active           boolean DEFAULT true      NEW
```

```
construction_inclusions  [Phase 1]  —  ← NEW
> org: builder's organization_id

  name                varchar(255)              NEW  (e.g. "Standard", "Premium")
  slug                varchar(255) UNIQUE/org   NEW
  tier                varchar(20)               NEW
  is_active           boolean DEFAULT true      NEW

construction_inclusion_items  [Phase 1]  —  ← NEW (child of above)
> FK: inclusion_id CASCADE

  category            varchar(50)               NEW  (kitchen/bathroom/exterior/etc.)
  item_name           varchar(255)              NEW
  is_featured         boolean DEFAULT false     NEW  (shown on flyers/brochures)
  sort_order          smallint DEFAULT 0        NEW
```

```
commission_templates  [Phase 1]  —  ← NEW
> org: builder's organization_id

  name                varchar(255)              NEW
  commission_type     varchar(20) CHECK         NEW  (percentage/flat/tiered)
  rate                decimal(8,4)              NEW
  flat_amount         decimal(12,2)             NEW
  stages              jsonb                     NEW  (e.g. {"unconditional":25,"frame":25,"lockup":25,"completion":25})
  is_active           boolean DEFAULT true      NEW
```

```
packages  [Phase 1]  —  ← NEW
> org: builder's organization_id
> Package = lot + design + facade + inclusions = sellable H&L product

  lot_id              bigint FK SET NULL        NEW  -> lots
  project_id          bigint FK SET NULL        NEW  -> projects
  construction_design_id  bigint FK SET NULL    NEW  -> construction_designs
  construction_facade_id  bigint FK SET NULL    NEW  -> construction_facades
  commission_template_id  bigint FK SET NULL    NEW  -> commission_templates
  name                varchar(255)              NEW
  slug                varchar(255)              NEW
  land_price          decimal(12,2)             NEW
  build_price         decimal(12,2)             NEW
  facade_price        decimal(12,2)             NEW
  total_price         decimal(12,2) GENERATED   NEW  (land + build + facade, auto-calculated)
  weekly_rent_est     decimal(8,2)              NEW
  gross_yield_pct     decimal(5,2)              NEW
  status              varchar(20) CHECK         NEW  (draft/available/sold/archived)
  distribution_type   varchar(20) CHECK         NEW  (none/open/exclusive)
  publish_at          timestamptz               NEW
  published_at        timestamptz               NEW
```

```
package_inclusions  [Phase 1]  —  ← NEW (pivot)
> FK: package_id CASCADE, construction_inclusion_id CASCADE

  is_featured         boolean DEFAULT false     NEW
  sort_order          smallint DEFAULT 0        NEW
  UNIQUE (package_id, construction_inclusion_id)
```

```
package_distributions  [Phase 1]  —  ← NEW (pivot)
> FK: package_id CASCADE, user_id CASCADE (agent)

  allocated_by        bigint FK SET NULL        NEW  -> users
  allocated_at        timestamptz               NEW
  UNIQUE (package_id, user_id)
```

```
agent_favorites  [Phase 1]  —  ← NEW
> FK: user_id CASCADE, package_id CASCADE

  list_name           varchar(100) DEFAULT 'Favorites'  NEW
  UNIQUE (user_id, package_id, list_name)
```

```
agent_saved_searches  [Phase 1]  —  ← NEW
> org: agent's organization_id

  name                varchar(255)              NEW
  search_criteria     jsonb                     NEW  (state, region, price_min/max, type, etc.)
  email_alerts_enabled boolean DEFAULT false    NEW
  alert_frequency     varchar(10) CHECK         NEW  (immediate/daily/weekly)
  last_alerted_at     timestamptz               NEW
```

```
sale_commission_payments  [Phase 1]  —  ← NEW
> FK: sale_id CASCADE, commission_id CASCADE

  stage               varchar(20) CHECK         NEW  (unconditional/frame/lockup/completion)
  amount              decimal(12,2)             NEW
  is_paid             boolean DEFAULT false     NEW
  paid_at             timestamptz               NEW
  invoice_reference   varchar(100)              NEW
```

```
enquiries  [Phase 1]  —  ← NEW
> org: agent's organization_id

  from_user_id        bigint FK SET NULL        NEW  -> users (agent)
  to_organization_id  bigint FK SET NULL        NEW  -> organizations (builder)
  package_id          bigint FK SET NULL        NEW  -> packages
  enquiry_type        varchar(30) CHECK         NEW  (buyer_presentation/package_research/general)
  presentation_date   date                      NEW
  buyer_has_preapproval boolean                 NEW
  ownership_type      varchar(20) CHECK         NEW  (investor/owner_occupier)
  support_requested   jsonb                     NEW  (["confirming_availability","request_epack",...])
  subject             varchar(255)              NEW
  message             text                      NEW
  status              varchar(20) CHECK         NEW  (pending/responded/closed)
```

```
stock_imports  [Phase 1]  —  ← NEW
> org: builder's organization_id

  project_id          bigint FK SET NULL        NEW
  file_name           varchar(255)              NEW
  file_path           varchar(500)              NEW
  import_url          varchar(500)              NEW
  status              varchar(20) CHECK         NEW  (pending/parsing/completed/failed)
  total_rows          integer                   NEW
  success_count       integer DEFAULT 0         NEW
  error_count         integer DEFAULT 0         NEW
  errors              jsonb                     NEW
  imported_by         bigint FK SET NULL        NEW  -> users
  completed_at        timestamptz               NEW
```

---

## MIGRATION ORDER

```
═══════════════════════════════════════════════════════
  PHASE 1: BUILDER PORTAL (SHIP FIRST)
═══════════════════════════════════════════════════════

Step 0  BOOTSTRAP (full)
   |    PostgreSQL extensions (pgvector, cube, earthdistance)
   |    Seed lookups, configure mysql_legacy connection
   |    Morph map, preventLazyLoading, Spatie Permission roles
   v
Step 2  USERS (slim: migrations + models + auth + self-signup)
   |    SKIP: v3 user import (1,502 users)
   v
Step 3  PROJECTS & LOTS (slim: migrations + models only)
   |    SKIP: v3 import (15K projects, 121K lots)
   v
Step 4  SALES (slim: migrations + models only)
   |    SKIP: v3 import (447 sales, 120 reservations)
   v
Step 1.5  V3 PUSH (slim: mysql_legacy + PackageV3PushObserver)
   |    SKIP: full bidirectional sync
   v
PRD 12  BUILDER PORTAL (Tier 1)
   |    Construction Library (designs, facades, inclusions)
   |    Packages (lot + design + facade = auto-priced product)
   |    Distribution & allocation (open/exclusive per agent)
   |    Builder Workbench + Agent Portal + Brochures
   |    v3 push (packages -> v3 projects + lots)
   v
   🚀  LAUNCH

═══════════════════════════════════════════════════════
  PHASE 2: SUBSCRIBER MIGRATION (after launch)
═══════════════════════════════════════════════════════

Step 1  CONTACTS (full)
   |    leads -> contacts + emails + phones
   |    addresses -> contact columns | statusables -> stage
   |    ** BUILD legacy_lead_id -> contact_id MAP **
   v
Step 1.5  SYNC (full: bidirectional)
   |    8 entity sync, pollers, field mappers
   v
Step 2  USERS import (1,502 v3 users)
   v
Step 3  PROJECTS & LOTS import (15K + 121K — merge with builder data)
   v
Step 4  SALES import (447 sales + 120 reservations)
   v
Step 5  EVERYTHING ELSE
   |    notes + comments + media + partners + relationships + tasks
   v
Step 6  AI & VERIFY
   |    Prism agents + pgvector embeddings + full verification
   v
Step 7  DASHBOARDS & REPORTS
   v
   🔄  SUBSCRIBER CUTOVER (v3 decommissioned)

═══════════════════════════════════════════════════════
  PHASE 3: ADVANCED FEATURES
═══════════════════════════════════════════════════════

PRD 12 Tier 2-3  |  PRDs 09-20 (search, AI, marketing, Xero, R&D)
```

---

## INDEX STRATEGY

| Type | Where | Why |
| --- | --- | --- |
| **Partial** | contacts, lots, sales, notes, partners, tasks, reservations, enquiries, searches, commissions (org+stage/status WHERE deleted_at IS NULL) | Fast tenant queries on active records |
| **GIN** | contacts.extra_attributes, projects.extra_attributes, property_searches.search_criteria, property_enquiries.extra_attributes | JSONB field queries |
| **BRIN** | project_updates.created_at, sales.created_at | Time-range scans on big tables |
| **Expression** | contact_emails LOWER(email), suburbs LOWER(suburb) | Case-insensitive lookup |
| **Covering** | lots(project_id) INCLUDE (lot_number, price, stage) | Lot list page optimization |
| **Composite** | contacts(org,type), tasks(user,due_at) | Multi-filter queries |
| **earthdistance** | PostgreSQL extension | Replaces v3 raw SQL lat/lng math |

**FK rules enforced on ALL tables**:
- `organization_id` -> `ON DELETE CASCADE`
- Parent entity (contact->emails, project->specs, sale->commissions) -> `ON DELETE CASCADE`
- Reference FKs (source_id, company_id, created_by, user_id) -> `ON DELETE SET NULL`

---

> **Full SQL DDL**: `newdb.md` | **v3 issues**: `dbissues.md` | **Build steps**: `plan.md`
