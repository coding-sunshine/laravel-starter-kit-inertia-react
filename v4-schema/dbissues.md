# FusionCRM v3 Database Issues & Design Report

> **Generated**: 2026-03-18 | **Scope**: Full schema audit of v3 (MySQL) + v4 rebuild plan (PostgreSQL)
> **Method**: Migration analysis (154 files, 71 tables, 821 columns), Model analysis (61 models, 165 relationships), raw SQL audit, seeder/observer/Nova review, rebuild plan review (27 files), industry best practices research
> **Passes**: 3 (initial analysis + verification pass + final sweep + live data query)
> **Companion**: See `newdb.md` for complete v4 schema, visual diagrams, and column-level migration maps

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Critical Issues (Must Fix)](#2-critical-issues-must-fix)
3. [High-Priority Issues](#3-high-priority-issues)
4. [Medium-Priority Issues](#4-medium-priority-issues)
5. [Low-Priority Issues](#5-low-priority-issues)
6. [v4 Rebuild Plan Gaps](#6-v4-rebuild-plan-gaps)
7. [Anti-Patterns Detected](#7-anti-patterns-detected)
8. [Raw SQL and Query Safety Audit](#8-raw-sql-and-query-safety-audit)
9. [Infrastructure and Tooling Issues](#9-infrastructure-and-tooling-issues)
10. [Best Practice Recommendations](#10-best-practice-recommendations)
11. [Table-by-Table Audit](#11-table-by-table-audit)
12. [Action Plan](#12-action-plan)

---

## 1. Executive Summary

### Current State (v3)

| Metric | Value | Assessment |
| --- | --- | --- |
| Total tables | 75 | High - some redundant (updated from 71 after final sweep) |
| Total columns | 821+ | Bloated - top 5 tables average 51 cols each |
| Total migrations | 154 | Fragmented - 83 CREATE + 71 ALTER |
| Models | 61 | Moderate coupling |
| Relationships | ~165 | High complexity |
| Models with SoftDeletes | 25 (40%) | Inconsistent application |
| Models with Activity logging | 48 (78%) | Good coverage |
| Foreign key constraints defined | ~30% | **POOR - major integrity risk** |
| Indexes on FK columns | ~20% | **POOR - major performance risk** |
| Raw SQL queries (DB::raw) | 19+ | **SQL injection risk** |
| DB::table() bypassing Eloquent | 5 | Inconsistent data access |
| Database connections | 4 | Sync complexity risk |
| Naming consistency | ~60% | Mixed conventions |
| Nova resources | 2 of 61 models | Admin visibility gap |
| Observers | 0 | All logic in boot() methods |
| Seeders (legacy import) | 35+ | Obsolete for v4 |
| Aecor package dependency | 20+ models | **Replacement needed for v4** |
| Eager loading (with()) | 0 instances | **ZERO across all 53 controllers** |
| DB::transaction() usage | 0 instances | **No transaction wrapping anywhere** |
| Pessimistic locking | 0 instances | No race condition protection |
| Missing jobs table migration | 1 | Queue driver=database but no migration |
| Missing cache table migration | 1 | Cache driver=database but no migration |
| Morph map defined | No | Polymorphic types stored as FQCN strings |

### Top 10 Tables by Column Count

| Rank | Table | Columns | Assessment |
| --- | --- | --- | --- |
| 1 | projects | 80 | **CRITICAL - needs decomposition** |
| 2 | lots | 48 | High - review for normalization |
| 3 | property_reservations | 47 | High - includes finance/legal/broker details |
| 4 | users | 44 | High - mixed auth + CRM + subscription flags |
| 5 | leads | 38 | High - God Object hub |
| 6 | campaign_websites | 36 | Marketing config bloat |
| 7 | finance_assessments | 35 | Financial analysis |
| 8 | flyers | 31 | Marketing materials |
| 9 | websites | 29 | Website management |
| 10 | wordpress_websites | 27 | WordPress integration |

### Risk Score: **8.7/10** (Very High) - upgraded from 7.2 -> 8.1 -> 8.7 across 3 passes

The v3 database has accumulated severe technical debt over its lifecycle (2014-2025). Across three audit passes, the picture is stark:

- **Zero eager loading** across all 53 controllers (N+1 on every page)
- **Zero transaction wrapping** on any multi-step operation (orphan records on failure)
- **Dangerous cascade deletions** in boot() methods (Lead deletes Users!)
- **19+ raw SQL queries** with injection risk
- **No morph map** defined (Lead -> Contact rename will break all polymorphic records)
- **Aecor package dependency** across 20+ models (replacement needed)
- **Projects table has 63 columns** (needs decomposition)
- **Missing jobs/cache table migrations** despite database drivers configured
- **Addresses table** missed in initial planning (5+ models depend on it)
- **75 tables, 821+ columns, 83+ documented issues**

---

## 2. Critical Issues (Must Fix)

### CRIT-01: Missing Foreign Key Constraints (~70% of FKs)

**Impact**: Data integrity violations, orphaned records, import failures
**Tables affected**: sales, lots, property_reservations, tasks, leads, and 15+ others

Foreign keys are defined as integer columns but lack actual `FOREIGN KEY` constraints in migrations. This means:

- Orphaned records can exist (e.g., a sale referencing a deleted lot)
- No cascade behavior on delete/update
- Database cannot enforce referential integrity

```php
// CURRENT (v3) - column only, no constraint
$table->unsignedBigInteger('developer_id')->nullable();

// SHOULD BE
$table->foreignId('developer_id')->nullable()->constrained('developers')->nullOnDelete();
```

**Affected FK columns without constraints**:

| Table | Column | Should Reference |
| --- | --- | --- |
| sales | developer_id | developers.id |
| sales | affiliate_id | users.id |
| sales | subscriber_id | users.id |
| sales | sales_agent_id | users.id |
| sales | bdm_id | users.id |
| sales | referral_partner_id | users.id |
| sales | agent_id | users.id |
| sales | lot_id | lots.id |
| sales | project_id | projects.id |
| lots | project_id | projects.id |
| property_reservations | lot_id | lots.id |
| property_reservations | agent_id | users.id |
| property_reservations | primary_contact_id | leads.id |
| property_reservations | secondary_contact_id | leads.id |
| leads | source_id | sources.id |
| leads | company_id | companies.id |
| tasks | lead_id | leads.id |
| tasks | assigned_id | users.id |
| finance_assessments | lead_id | leads.id |
| finance_assessments | project_id | projects.id |
| property_enquiries | lead_id | leads.id |
| property_enquiries | lot_id | lots.id |
| property_searches | lead_id | leads.id |

**Recommendation for v4**: Define ALL foreign keys with explicit constraints, cascade rules, and indexes in the same migration as the table creation. Never split FK definitions into separate migrations.

---

### CRIT-02: Denormalized Commission Data in Sales Table

**Impact**: Data redundancy, update anomalies, calculation errors
**Table**: `sales`

The sales table stores 7 separate commission columns as flat fields:

```
piab_comm, affiliate_comm, subscriber_comm, sales_agent_comm,
bdm_comm, referral_partner_comm, agent_comm
```

Plus totals: `comms_in_total`, `comms_out_total`

**Problems**:

1. **Violation of 1NF/2NF**: Repeating groups of the same data type
2. **Update anomalies**: Changing commission logic requires altering the table structure
3. **No audit trail**: Can't track commission changes over time
4. **Rigid structure**: Can't add new commission types without schema migration
5. **No rate tracking**: Only stores amounts, not the rate percentage used

**v4 plan addresses this** with a `commissions` table (sale_id, commission_type enum, agent_user_id, rate_percentage, amount). This is correct. Ensure:

- Unique constraint on `(sale_id, commission_type, agent_user_id)`
- `amount` uses `DECIMAL(12,2)` with Money cast
- `rate_percentage` uses `DECIMAL(5,4)` for precision

---

### CRIT-03: Polymorphic "contacts" Table Naming Collision

**Impact**: Schema confusion, import mapping errors, v4 migration complexity
**Table**: `contacts` (v3)

In v3, `contacts` stores **contact details** (email, phone) via polymorphic `contactable_type`/`contactable_id` - NOT person entities. But in v4, `contacts` will be the **primary person entity** (replacing `leads`).

**Problems**:

1. Same table name, completely different semantics between v3 and v4
2. Import scripts must carefully distinguish between the two meanings
3. Developers reading v3 code will confuse `Contact` model with person data

**Recommendation**: In v4 migration scripts, always reference the v3 table as `legacy_contacts` or `contact_details_v3` in comments and mapping logic. The v4 schema correctly splits this into `contact_emails` and `contact_phones`.

---

### CRIT-04: No Soft Delete Cascade Configuration

**Impact**: Orphaned child records when parents are soft-deleted
**Models affected**: 25 models with SoftDeletes, 0 with soft cascade config

When a `Lead` (future `Contact`) is soft-deleted, none of its children (emails, phones, tasks, notes, sales, reservations) are automatically soft-deleted. This creates ghost data that appears in queries.

```php
// CURRENT: Manual boot() deletion (Project model only)
static::deleting(function ($project) {
    $project->lots()->delete(); // Only handles one level
});

// SHOULD BE (v4): Using askedio/laravel-soft-cascade
use SoftCascadeTrait;
protected $softCascade = ['emails', 'phones', 'tasks', 'notes'];
```

**Recommendation for v4**: Apply `askedio/laravel-soft-cascade` to all parent models. Define cascade chains:

- Contact -> contact_emails, contact_phones, tasks, notes, partners
- Project -> lots
- Lot -> property_reservations, sales (review: should sales soft-delete with lot?)
- Sale -> commissions

---

### CRIT-05: Dangerous Cascade Deletion in boot() Methods (NEW - Pass 2)

**Impact**: **DATA LOSS** - deleting a parent can wipe entire relationship chains
**Severity**: CRITICAL - potential for catastrophic data loss

Four models have `boot()` methods that perform hard deletes on related records:

| Model | boot() Deletes | Danger Level |
| --- | --- | --- |
| **Lead** | ALL users AND ALL partners | **EXTREME - deleting a lead wipes users!** |
| **User** | ALL developers, then ALL websites | HIGH - cascading wipe |
| **Developer** | ALL projects | HIGH - wipes all project data |
| **Project** | ALL lots | MEDIUM - expected but not soft-cascade |

**The Lead -> User cascade is almost certainly a bug.** Deleting a lead should NOT delete user accounts. This chain can cascade:

```
Lead deleted
  -> User deleted (ALL users linked to this lead)
    -> Developer deleted (ALL developers linked to this user)
      -> Project deleted (ALL projects linked to this developer)
        -> Lot deleted (ALL lots linked to this project)
```

**Recommendation for v4**:

- NEVER use boot() for cascade deletions
- Use `askedio/laravel-soft-cascade` for parent-child chains
- Use Observers for side effects (not boot methods)
- The Lead -> User relationship should be `nullOnDelete`, not cascade

---

### CRIT-06: Projects Table Has 80 Columns (Table Bloat) (NEW - Pass 2)

**Impact**: Poor query performance, maintenance nightmare, unclear domain boundaries
**Table**: `projects` (63 columns)

The projects table is a massive catch-all containing:

- **Core project data**: title, description, address, lat/lng
- **Building specifications**: bedrooms, bathrooms, living_area, garage, storeys
- **Investment metrics**: min/max rent yields, cap rates, cashflow projections
- **Special feature flags**: co_living, ndis, smsf, flexi_exclusive, rent_to_sell
- **Pricing**: min/max price ranges, land prices
- **Marketing**: description_summary, video URLs, image URLs
- **Status/lifecycle**: status, stage, completion dates

**Problems**:

1. Violates Single Responsibility - one table serves 6+ domains
2. Every query loads 63 columns even when only 3 are needed
3. Adding features requires altering a massive table
4. Hard to index effectively

**Recommendation for v4**: Decompose into focused tables:

- `projects` (core: id, org_id, title, address, developer_id, status, stage)
- `project_specifications` (building: bedrooms, bathrooms, area, storeys, garage)
- `project_investment_metrics` (finance: rent yields, cap rates, cashflow)
- `project_features` (flags: co_living, ndis, smsf, etc.)
- `project_pricing` (price ranges, land prices)
- `project_marketing` (description, videos, images - or use media library)

---

### CRIT-07: Aecor Package Dependency Across 20+ Models (NEW - Pass 2)

**Impact**: v4 migration blocked if Aecor packages not replaced
**Packages**: `aecor/laravel-*` (HasStatus, HasAddress, HasContact traits)

20+ models depend on Aecor package traits:

- **HasStatus** (15+ models): Lead, Project, Sale, PropertyReservation, PropertyEnquiry, etc.
- **HasAddress** (5+ models): Lead, Developer, Project, Company
- **HasContact** (3+ models): Lead, Developer, Company

These traits create polymorphic `statuses`, `addresses`, and `contacts` tables. In v4:

- **HasStatus** -> Replace with `spatie/laravel-model-states` (already planned)
- **HasAddress** -> Replace with dedicated address columns or `addresses` table
- **HasContact** -> Replace with `contact_emails` / `contact_phones` tables

**Every model using these traits needs a migration strategy.**

---

### CRIT-08: Zero Eager Loading Across All Controllers (NEW - Pass 3)

**Impact**: N+1 query explosion on EVERY page that loads relationships
**Scope**: All 53 controllers, zero instances of `->with()` or `->load()`

Not a single controller in the entire v3 codebase uses eager loading. Every relationship is lazy-loaded, meaning:

- A list page showing 50 leads with their source and company triggers **1 + 50 + 50 = 101 queries**
- A project detail page with lots, sales, and reservations triggers **1 + N + N + N queries**
- With 25 relationships on Lead, a single detail page could fire **25+ queries**

```php
// CURRENT: Lazy loading everywhere
$leads = Lead::all(); // 1 query
foreach ($leads as $lead) {
    echo $lead->source->name;  // +1 query per lead (N+1)
    echo $lead->company->name; // +1 query per lead (N+1)
}

// SHOULD BE:
$leads = Lead::with(['source', 'company'])->get(); // 3 queries total
```

**Recommendation for v4**:

- Add `Model::preventLazyLoading(!app()->isProduction())` in AppServiceProvider
- Define `$with` defaults on models for always-needed relationships
- Use explicit `->with()` in every controller query
- Create dedicated query classes for complex pages

---

### CRIT-09: Zero Transaction Wrapping in Multi-Step Operations (NEW - Pass 3)

**Impact**: Partial data creation on failure, orphaned records
**Scope**: All controllers, jobs, and services - zero `DB::transaction()` calls

No multi-step database operation in v3 is wrapped in a transaction. Examples:

- Creating a Project then creating its Lots - if lot creation fails, orphaned project exists
- Creating a Sale with commission records - if commission save fails, sale exists without commissions
- Creating a Lead with contact details - if contact save fails, lead has no email/phone

```php
// CURRENT: No transaction
$project = Project::create($data);
$project->lots()->createMany($lotsData); // If this fails, orphan project exists

// SHOULD BE (v4):
DB::transaction(function () use ($data, $lotsData) {
    $project = Project::create($data);
    $project->lots()->createMany($lotsData);
});
```

**Recommendation for v4**: Wrap ALL multi-step creates/updates in `DB::transaction()`. PostgreSQL has excellent transaction support - use it.

---

### CRIT-10: Connection Name Mismatch (OLD_DB vs mysql_legacy) (NEW - Pass 3)

**Impact**: v4 import commands will fail on Step 0
**File**: `config/database.php`

The v3 config defines the legacy connection as `OLD_DB` (env vars `OLD_DB_HOST`, etc.), but the v4 rebuild plan references `mysql_legacy` as the connection name for import commands. If not aligned, all import artisan commands will throw connection errors.

**Recommendation**: In v4 Step 0, ensure the legacy connection is named `mysql_legacy` to match the rebuild plan documentation.

---

## 3. High-Priority Issues

### HIGH-01: Missing Indexes on Foreign Key and Query Columns

**Impact**: Full table scans on joins, slow list pages, poor filter performance
**Severity**: Performance degradation scales with data volume

| Table | Missing Index On | Query Pattern |
| --- | --- | --- |
| leads | source_id | Filter by lead source |
| leads | company_id | Filter by company |
| leads | created_at | Sort by date |
| leads | status | Filter active/inactive |
| sales | lot_id | Join to lots |
| sales | project_id | Filter by project |
| sales | status | Filter pipeline stage |
| sales | created_at | Date range queries |
| lots | project_id | Join to projects |
| lots | status | Available/sold filter |
| tasks | lead_id | Tasks for a lead |
| tasks | assigned_id | My tasks query |
| tasks | due_date | Overdue tasks |
| property_reservations | lot_id | Reservations per lot |
| property_reservations | status | Active reservations |
| finance_assessments | lead_id | Assessments per lead |
| property_enquiries | lead_id | Enquiries per lead |
| property_enquiries | lot_id | Enquiries per lot |

**Recommendation for v4**: Every foreign key column gets an index. Add composite indexes for common filter combinations:

```sql
CREATE INDEX idx_contacts_org_stage ON contacts(organization_id, stage);
CREATE INDEX idx_sales_org_status ON sales(organization_id, status);
CREATE INDEX idx_lots_project_status ON lots(project_id, status);
CREATE INDEX idx_tasks_assigned_due ON tasks(assigned_to_user_id, due_date);
```

---

### HIGH-02: Money Stored Without Proper Type/Cast

**Impact**: Floating-point precision errors, incorrect financial calculations
**Tables**: sales (9 money columns), lots (price, land_price), property_reservations (purchase_price, deposit)

```php
// CURRENT: No cast, raw decimal
'comms_in_total' => null,  // No cast in model

// SHOULD BE (v4):
protected $casts = [
    'amount' => MoneyCast::class,
    'rate_percentage' => 'decimal:4',
];
```

**Affected columns**: All price, commission, and financial fields across sales, lots, property_reservations, finance_assessments, and the polymorphic commission model.

**Recommendation**: Use `DECIMAL(12,2)` in PostgreSQL migrations + `akaunting/laravel-money` Money cast on all monetary model attributes. Never use `float` or `double` for money.

---

### HIGH-03: Lead Model Has 25 Relationships (God Object)

**Impact**: N+1 query explosion, high coupling, maintenance nightmare
**Model**: `Lead.php` (25 relationships, 16 accessors, 38 columns)

The Lead model is a "God Object" anti-pattern - it's the central hub for nearly everything:

```
contacts, addresses, statuses, sales, partners, tasks, notes,
property_enquiries, property_reservations, property_searches,
questionnaires, website_contacts, relationships, emails, phones,
activities, media, tags, ...
```

**Problems**:

1. **N+1 risk**: Any page loading a lead can trigger 25+ queries without eager loading
2. **16 accessors** create additional query overhead (computed on every model access)
3. **High coupling**: Changes to any related module affect the Lead model
4. **Testing complexity**: Mocking requires setting up deep relationship trees
5. **Memory**: Loading a lead with all relations consumes excessive memory

**Recommendation for v4**: The Contact model should:

- Use explicit eager loading in controllers/queries (`->with(['emails', 'phones'])`)
- Group relationships via traits: `HasCommunications`, `HasPropertyInteractions`, `HasSalesActivity`
- Create dedicated query scopes for common relationship sets
- Use DTOs/Resources to control which relationships are loaded per context
- Consider read-model projections for dashboard/list views

---

### HIGH-04: Inconsistent Timestamp and Soft Delete Usage

**Impact**: Broken audit trails, inconsistent record lifecycle
**Models**: 36 without SoftDeletes, 25 with SoftDeletes, 31 tables with soft deletes in migrations

| Should Have SoftDeletes | Currently Has | Issue |
| --- | --- | --- |
| sales | Yes | OK |
| lots | No | **Sales reference lots - deleting a lot breaks sales** |
| tasks | Yes | OK |
| sources (lookup) | No | OK - lookups don't need soft delete |
| companies | No | **Leads reference companies** |
| property_reservations | Yes | OK |
| finance_assessments | Unknown | **Referenced by leads and projects** |
| developers | No | **Projects reference developers** |

**Recommendation**: Apply SoftDeletes to all entities that have foreign key references from other tables. Lookup tables (sources, states, suburbs, projecttypes) can use hard delete if not referenced.

---

### HIGH-05: Duplicate HasFactory Trait in User Model

**Impact**: PHP warning/error, test factory confusion
**File**: `app/Models/User.php`

```php
use HasFactory;
use HasFactory;  // DUPLICATE - remove
```

---

### HIGH-06: Polymorphic Commission Model (NEW - Pass 2)

**Impact**: Cannot enforce FK integrity, complex querying
**Models**: Lot, Project, Sale all use `morphOne` to Commission

The Commission model uses polymorphic relationships (`commissionable_type`, `commissionable_id`) to attach to Lot, Project, AND Sale. This means:

1. **No FK constraint possible** - polymorphic FKs can't reference multiple tables
2. **Confusing semantics** - a commission on a Lot vs a Sale has different business meaning
3. **Query complexity** - finding all commissions requires type checking

**Recommendation for v4**: The rebuild plan already normalizes this to a `commissions` table with `sale_id` FK. Ensure lot-level and project-level commission rates are stored differently (perhaps as columns on those tables, not as polymorphic records).

---

### HIGH-07: N+1 Query Risk from Excessive Accessors (NEW - Pass 2)

**Impact**: Slow page loads, excessive database queries
**Models**: Lead (16 accessors), Project (24 accessors)

```php
// Lead model accessors that trigger queries on every access:
getFullNameAttribute()
getStatusLabelAttribute()
getSourceNameAttribute()  // Lazy loads source relationship
getCompanyNameAttribute() // Lazy loads company relationship
// ... 12 more
```

Each accessor that references a relationship triggers a lazy load query. On a list page showing 50 leads, `getSourceNameAttribute()` alone generates 50 extra queries.

**Recommendation for v4**:

- Replace accessor-based relationship lookups with eager loading
- Use `$appends` sparingly - only for truly computed values
- Prefer Eloquent API Resources over accessors for API responses

---

### HIGH-08: Livewire Components Query DB on Every Interaction (NEW - Pass 3)

**Impact**: Database saturation under concurrent users
**Count**: 10+ Livewire components with mount/updated queries

Livewire components re-query the database on every user interaction (typing, selecting, toggling):

| Component | Query in mount/updated | Issue |
| --- | --- | --- |
| BotInABox/CreateForm | `AiBotCategory::get()` | Reloads on every form interaction |
| Tasks/Form | `State::all()`, `Projecttype::all()` | Full table loads on mount |
| Projects/ProjectForm | Status lookup with double `where()` | Queries on every update |
| Resources/Index | `ResourceCategory` query | Reloads categories on mount |
| 6+ other components | Various lookups | Same pattern |

**Recommendation for v4**:

- Cache lookup queries in component properties
- Use `#[Computed]` attributes (Livewire 3+)
- Use `$this->cache()` for expensive queries
- Move static lookups to cached config/constants

---

### HIGH-09: 11 Unprotected DB::connection() Calls (NEW - Pass 3)

**Impact**: Unhandled connection failures crash requests
**Files**: AiBotController, ProjectController, SaleController, Api/ProjectController

Direct `DB::connection('botmysql')` calls without try/catch or retry logic. If the bot database is unreachable, the entire request fails with an unhandled exception.

**Recommendation for v4**: Use try/catch with graceful degradation, or remove direct cross-database calls entirely.

---

### HIGH-10: Missing jobs and cache Table Migrations (NEW - Pass 3)

**Impact**: Queue and cache drivers configured for `database` but no tables exist
**Config**: `config/queue.php` uses `driver='database'`, `config/cache.php` uses `driver='database'`

No migration exists for `jobs`, `failed_jobs`, or `cache` tables. These are required by Laravel when using the database driver but were never generated.

**Recommendation for v4**: Run `php artisan queue:table`, `php artisan queue:failed-table`, `php artisan cache:table` during Step 0. Or switch to Redis driver (recommended for PostgreSQL stack).

---

### HIGH-11: No Morph Map Defined (NEW - Pass 3)

**Impact**: Full class names stored in polymorphic type columns (brittle, verbose)
**Location**: No `Relation::enforceMorphMap()` in any service provider

All polymorphic relationships store the full PHP namespace as the type:
`App\Models\Lead` instead of `lead`

If models are renamed or moved (Lead -> Contact in v4), **all polymorphic records break**.

**Recommendation for v4**: Define a morph map in AppServiceProvider:

```php
Relation::enforceMorphMap([
    'contact' => Contact::class,
    'project' => Project::class,
    'lot' => Lot::class,
    'sale' => Sale::class,
    // ...
]);
```

---

## 4. Medium-Priority Issues

### MED-01: Foreign Keys Defined in Separate Migrations

**Impact**: Schema integrity depends on migration execution order
**Count**: ~15 ALTER migrations adding FKs after table creation

If migrations run out of order (e.g., during testing or fresh install), foreign key constraints may fail. In v4, define all constraints in the CREATE migration.

---

### MED-02: Inconsistent Naming Conventions

| Issue | Example | Should Be |
| --- | --- | --- |
| Typo in column name | `storyes` (lots table) | `stories` or `storeys` |
| Mixed singular/plural | `contacts` (detail) vs `leads` (entity) | Consistent entity naming |
| Inconsistent FK naming | `agent_id`, `assigned_id`, `attached_id` | `agent_user_id`, `assigned_user_id` |
| Mixed boolean naming | `is_active`, `active`, `enabled` | Always `is_` prefix |
| Table prefix inconsistency | `property_reservations` vs `sales` | Either prefix all or none |
| Relationship naming | `client_lead`, `agent_lead` | `client_contact`, `agent_contact` (v4) |

**Recommendation for v4**: Establish naming convention document:

- FK columns: `{referenced_table_singular}_id` (e.g., `contact_id`, `user_id`)
- Disambiguated FKs: `{role}_{entity}_id` (e.g., `agent_contact_id`, `primary_contact_id`)
- Booleans: `is_` prefix always
- Timestamps: `{event}_at` (e.g., `last_contacted_at`, `reserved_at`)

---

### MED-03: Master/Lookup Tables Missing UNIQUE Constraints

**Impact**: Duplicate data in reference tables
**Tables**: sources, companies, developers, projecttypes, states, suburbs, services

```sql
-- CURRENT: No unique constraint
CREATE TABLE sources (id, name, timestamps);

-- SHOULD BE:
CREATE TABLE sources (id, organization_id, name, UNIQUE(organization_id, name), timestamps);
```

---

### MED-04: Overuse of JSON/Schemaless Attributes

**Impact**: Cannot query, index, or validate JSON fields efficiently in MySQL
**Tables**: leads (extra_attributes), users (extra_attributes)

JSON columns are appropriate for truly dynamic data, but v3 uses them as a catch-all for fields that should be proper columns. In PostgreSQL (v4), `jsonb` is queryable and indexable, which improves the situation - but structured columns are still preferred for frequently queried data.

**Recommendation**: Audit `extra_attributes` usage. Any field queried in filters/reports should be a proper column. Use `jsonb` + GIN index in PostgreSQL for genuinely dynamic fields.

---

### MED-05: No CHECK Constraints on Enum-like Columns

**Impact**: Invalid data can be inserted (e.g., status = "banana")
**Tables**: All tables with status, type, stage columns

```sql
-- CURRENT: No constraint
$table->string('status')->default('active');

-- SHOULD BE (PostgreSQL v4):
$table->string('status')->default('active');
-- Plus:
-- ALTER TABLE contacts ADD CONSTRAINT chk_stage
--   CHECK (stage IN ('new','contacted','qualified','proposal','won','lost'));
```

**Or better**: Use `spatie/laravel-model-states` (already planned for v4) which enforces valid transitions at the application level + a CHECK constraint at the DB level for defense in depth.

---

### MED-06: No Composite Indexes for Multi-Tenant Queries

**Impact**: Every query for "my organization's data" does a full scan + filter
**Pattern**: `WHERE organization_id = ? AND status = ?`

Every tenant-scoped query needs `organization_id` as the leading column in composite indexes:

```sql
CREATE INDEX idx_contacts_org_stage ON contacts(organization_id, stage);
CREATE INDEX idx_sales_org_status_created ON sales(organization_id, status, created_at);
CREATE INDEX idx_lots_org_project ON lots(organization_id, project_id);
```

---

### MED-07: 11 Models With Implicit Table Names (NEW - Pass 2)

**Impact**: Table name mismatch risk if model naming doesn't follow Laravel convention
**Count**: 11 models without explicit `$table` property

These models rely on Laravel's automatic pluralization to determine the table name. If the model name doesn't follow English pluralization rules, the wrong table is queried. Verify each one maps correctly.

---

### MED-08: Property Transaction Models Reference `client_lead` and `agent_lead` (NEW - Pass 2)

**Impact**: FK naming confusion during v3 -> v4 migration
**Models**: PropertyEnquiry, PropertyReservation, PropertySearch

All three property transaction models use relationship names like `client_lead` and `agent_lead` which reference the Lead model. In v4, these must become `client_contact` and `agent_contact`, and the FK columns must change from `lead_id` patterns to `contact_id` patterns.

---

### MED-09: Custom Searchable Trait on 12+ Models (NEW - Pass 2)

**Impact**: Full-text search won't work without migration to Scout/Typesense
**Models**: 12+ models use `App\Traits\Searchable` (custom implementation)

v4 plan uses `laravel/scout` + `typesense/typesense-php`. Every model with the custom `Searchable` trait needs to be migrated to Scout's `Searchable` trait with proper `toSearchableArray()` definitions.

---

### MED-10: Array Casts on Email/Marketing Models (NEW - Pass 2)

**Impact**: Data stored as serialized arrays in text columns - not queryable
**Models**: BrochureMailJob, MailList, Flyer, CampaignWebsite

Fields like `emails`, `client_ids`, `external_emails` are cast to arrays but stored as JSON text. In PostgreSQL v4, these should be `jsonb` columns with proper indexes if queried, or normalized into proper relationship tables.

---

### MED-11: Missing Pessimistic Locking on Concurrent-Write Tables (NEW - Pass 3)

**Impact**: Race conditions on high-traffic tables
**Scope**: Zero instances of `->lockForUpdate()` or `->sharedLock()` anywhere

Tables like `lots` (status changes between available/reserved/sold), `property_reservations` (concurrent agents reserving same lot), and `sales` (commission calculations) have no locking. Two agents could reserve the same lot simultaneously.

**Recommendation for v4**: Use `lockForUpdate()` inside transactions for status transitions on lots, reservations, and sales.

---

### MED-12: SyncUsers Listener Calls Artisan Synchronously (NEW - Pass 3)

**Impact**: Request blocking on user save/delete events
**File**: `app/Listeners/SyncUsers.php`

The listener calls `Artisan::call('sync:users')` synchronously inside an event handler. This means every time a user is saved or deleted (with `IS_BOT_ENABLED=true`), the request blocks until the sync completes.

**Recommendation for v4**: If bot sync is needed, dispatch as a queued job: `SyncUsersJob::dispatch()->onQueue('sync')`.

---

### MED-13: Addresses Polymorphic Table Not Previously Documented (NEW - Pass 3)

**Impact**: Missing from migration mapping, data could be lost
**Table**: `addresses` (polymorphic via Aecor HasAddress trait)
**Models using it**: Lead, Developer, Project, PotentialProperty, Company

This table was not captured in the initial two passes. It uses `addressable_type`/`addressable_id` polymorphism. In v4, address data must be migrated to either:

- Dedicated address columns on each entity (simpler)
- A non-polymorphic `addresses` table with explicit FKs (more normalized)

---

## 5. Low-Priority Issues

### LOW-01: Pivot Tables Missing Timestamps

Tables `company_service`, `campaign_website_project` lack `created_at`/`updated_at`. Minor but useful for audit.

### LOW-02: Inconsistent Decimal Precision

Price columns use mixed precision: `decimal(10,2)`, `decimal(12,2)`, `decimal(8,2)`. Standardize to `decimal(12,2)` for all money.

### LOW-03: Legacy `old_table_id` Columns Not Cleaned Up

Several tables have `old_table_id` or similar columns from previous migrations. These should not carry over to v4.

### LOW-04: `password_resets` Table Missing FK to Users

Standard Laravel table, but adding `FOREIGN KEY (email) REFERENCES users(email)` improves integrity.

### LOW-05: Activity Log Tables Are Verbose

48 models log activity. Consider selective logging in v4 to reduce storage.

### LOW-06: Schema::defaultStringLength(191) Still Set (NEW - Pass 2)

**File**: `app/Providers/AppServiceProvider.php`

This is a MySQL-era workaround for the InnoDB index length limit. PostgreSQL doesn't have this limitation. Remove in v4 and use `VARCHAR(255)` as default.

### LOW-07: HasManyNotes Trait Uses Deprecated Package (NEW - Pass 2)

**Models**: Project, Sale use `Arcanedev\LaravelNotes` HasManyNotes trait. This package may not be maintained. In v4, use the planned polymorphic notes table or `spatie/laravel-comments`.

---

## 6. v4 Rebuild Plan Gaps

Issues found in the rebuild plan (`rebuild-plan/`) that affect database design:

| # | Gap | Impact | Recommendation |
| --- | --- | --- | --- |
| 1 | Model States not in migration files | States exist only in PHP code | Add CHECK constraints as DB-level defense |
| 2 | ~~Database Mail tables not in any step~~ | RESOLVED | Starter kit now includes `mail_templates` migration + org-scoped `EmailTemplatesController` |
| 3 | Scout/Typesense not in schema steps | No full-text search index plan | Add Scout index configuration to Step 0 |
| 4 | Reverb tables not planned | Real-time notifications need storage | Add Reverb migration to Step 0 |
| 5 | Durable Workflow tables absent | AI pipelines need state persistence | Add `laravel-workflow` migration to Step 6 |
| 6 | `legacy_lead_id` index strategy unclear | Import performance at risk | Add explicit B-tree index + document lookup pattern |
| 7 | No testing strategy per migration step | Regressions undetected | Add migration verification commands per step |
| 8 | Commissions table missing unique constraint | Duplicate commission entries possible | Add `UNIQUE(sale_id, commission_type, agent_user_id)` |
| 9 | Contact.extra_attributes no GIN index | JSONB queries will be slow | Add `GIN` index on `extra_attributes` |
| 10 | No partial indexes for soft deletes | Queries always filter `deleted_at IS NULL` | Add partial indexes: `WHERE deleted_at IS NULL` |
| 11 | Aecor replacement strategy not documented | 20+ models blocked | Document HasStatus/HasAddress/HasContact replacements per model |
| 12 | Custom Searchable trait migration not planned | 12+ models affected | Map each model's searchable fields to Scout `toSearchableArray()` |
| 13 | boot() cascade logic not documented for removal | Data loss risk carries over | Explicitly list all boot() deletions to replace with soft-cascade |
| 14 | SyncProject/SyncUsers commands reference v2 connections | Won't work in v4 | Archive or rewrite for v4 import commands |
| 15 | 35 legacy seeders not flagged for archival | Confusion during v4 setup | Mark as deprecated, create v4-specific seeders |
| 16 | Connection name mismatch (OLD_DB vs mysql_legacy) | Import commands will fail | Align connection name in Step 0 config |
| 17 | No morph map defined | Polymorphic records break on model rename | Add `Relation::enforceMorphMap()` in Step 0 |
| 18 | Missing jobs/cache table migrations | Queue/cache drivers fail | Add queue/cache migrations or switch to Redis |
| 19 | Addresses table not in migration mapping | Address data lost during import | Add addresses to import mapping in Step 1 |
| 20 | Zero eager loading pattern to replicate | No examples to follow | Establish eager loading standards in Step 0 |
| 21 | Zero transaction patterns to replicate | No examples to follow | Establish transaction wrapping standards in Step 0 |

---

## 7. Anti-Patterns Detected

### 7.1 God Object (Lead Model)

**Pattern**: One model with 25+ relationships, 16 accessors, and 38 columns acting as the center of all business logic.
**Fix**: In v4, Contact model should delegate to focused service classes and relationship traits. Use query scopes to pre-define relationship loading sets.

### 7.2 Table Bloat (Projects: 80 Columns)

**Pattern**: Single table accumulates columns from multiple domains (specs, finance, marketing, features, pricing) instead of being normalized.
**Fix**: Decompose into focused tables: `projects`, `project_specifications`, `project_investment_metrics`, `project_features`, `project_pricing`.

### 7.3 Polymorphic Abuse

**Pattern**: `contacts`, `addresses`, `statuses` tables use polymorphic relationships where simple HasMany would suffice.
**Fix**: v4 correctly moves to `contact_emails` and `contact_phones` as dedicated tables.

### 7.4 Destructive boot() Methods

**Pattern**: Model `boot()` methods perform hard deletes on related records, creating cascading data loss chains. Lead -> User cascade is almost certainly a bug.
**Fix**: Use Observers for side effects. Use `askedio/laravel-soft-cascade` for parent-child chains. Never hard-delete in boot().

### 7.5 Implicit Defaults (No DB-Level Constraints)

**Pattern**: Business rules enforced only in PHP, not at the database level.
**Fix**: Add CHECK constraints, NOT NULL where required, UNIQUE constraints, and DEFAULT values in migrations.

### 7.6 Entity-Attribute-Value Tendencies

**Pattern**: `extra_attributes` JSON columns used as a catch-all instead of proper schema design.
**Fix**: Audit usage, promote frequently-queried fields to columns, use `jsonb` + GIN index for truly dynamic data.

### 7.7 Soft Delete Without Cascade

**Pattern**: Parent soft-deleted but children remain visible.
**Fix**: Use `askedio/laravel-soft-cascade` with defined cascade chains.

### 7.8 Split Migration Definitions

**Pattern**: Table created in one migration, foreign keys added in another.
**Fix**: Define complete table structure (columns + constraints + indexes) in a single migration.

### 7.9 Missing Referential Integrity

**Pattern**: FK columns exist as plain integers without FOREIGN KEY constraints.
**Fix**: Every FK column must have a constraint with explicit ON DELETE behavior.

### 7.10 Accessor-Driven N+1 Queries

**Pattern**: Model accessors that lazy-load relationships, triggering N+1 queries on list pages.
**Fix**: Use eager loading and API Resources instead of computed accessors.

### 7.11 Zero Eager Loading (Global Lazy Loading) (NEW - Pass 3)

**Pattern**: Not a single `->with()` call in 53 controllers. Every relationship is lazy-loaded everywhere.
**Fix**: Add `Model::preventLazyLoading()` in development. Use explicit `->with()` in every controller query.

### 7.12 Zero Transaction Safety (NEW - Pass 3)

**Pattern**: No multi-step operation is wrapped in `DB::transaction()`. Partial failures leave orphaned records.
**Fix**: Wrap all multi-model creates/updates in transactions. PostgreSQL excels at this.

### 7.13 Missing Morph Map (NEW - Pass 3)

**Pattern**: Polymorphic type columns store full PHP class names (`App\Models\Lead`). Renaming models breaks all polymorphic records.
**Fix**: Define `Relation::enforceMorphMap()` with short aliases. Critical for Lead -> Contact rename in v4.

---

## 8. Raw SQL and Query Safety Audit

### 8.1 DB::raw() Usage (19+ instances)

**Impact**: Potential SQL injection, PostgreSQL syntax incompatibility
**Location**: Primarily in Livewire components

| Location | Usage | Risk |
| --- | --- | --- |
| ProjectList (Livewire) | Geographic distance calculation (lat/lng) | Medium - hardcoded formula, but dynamic column |
| LotList (Livewire) | Geographic distance calculation | Medium - same pattern |
| CommissionChart (Livewire) | `SUM($this->columnName) as total_comm` | **HIGH - dynamic column name in raw SQL** |
| PropertyFiles (Livewire) | Case-insensitive search `lower(title_status)` | Low - but MySQL syntax |
| Various controllers | Date formatting, aggregates | Low-Medium |

**Critical concern**: `CommissionChart` uses a PHP variable (`$this->columnName`) directly in `DB::raw()`. If this value comes from user input, it's a SQL injection vector.

**Recommendation for v4**:

- Replace geographic calculations with PostgreSQL's `earth_distance` extension or PostGIS
- Replace `DB::raw('lower(x)')` with PostgreSQL's native case-insensitive operators (`ILIKE`)
- Never interpolate PHP variables into `DB::raw()` - use parameter binding
- Audit every `DB::raw()` for PostgreSQL syntax compatibility

### 8.2 DB::table() Bypassing Eloquent (5 instances)

| File | Usage | Issue |
| --- | --- | --- |
| ProjectController | Raw suburb queries | Bypasses Suburb model scopes |
| AccessController | Role count check | Should use Eloquent count |
| SyncProject command | Status/media lookups on legacy DB | Acceptable for legacy sync |

**Recommendation**: Replace with Eloquent queries except for legacy sync commands.

---

## 9. Infrastructure and Tooling Issues

### 9.1 Multiple Database Connections (4 active)

| Connection | Database | Purpose | v4 Status |
| --- | --- | --- | --- |
| `mysql` | Primary CRM | Main application | Replace with `pgsql` |
| `botmysql` | Bot database | AI bot sync | Review - may use Prism/AI SDK instead |
| `oldmysql` / `fusionv2` | Legacy v2 | Data sync from v2 | Archive after migration |
| `pgsql` | PostgreSQL | Unused in v3 | Becomes primary in v4 |

**Risk**: SyncProject reads from `fusionv2`, SyncUsers syncs `mysql` -> `botmysql`. These create data consistency risks and won't exist in v4.

### 9.2 Zero Observers (All Logic in boot())

**Impact**: Untestable side effects, scattered business logic
**Current state**: `/app/Observers/` directory is empty

All model lifecycle hooks are embedded in `boot()` methods across 4 models (Lead, User, Developer, Project). This makes the logic:

- Hard to test in isolation
- Easy to miss during code review
- Impossible to temporarily disable

**Recommendation for v4**: Create dedicated Observers for each model with side effects.

### 9.3 Only 2 Nova Resources for 61 Models

**Impact**: 59 models have no admin panel visibility
**Current**: Only `User.php` and `Resource.php` exist in `/app/Nova/`

In v4 with Filament, ensure all CRM entities have admin resources.

### 9.4 35+ Legacy Seeders

**Impact**: Confusion during development, won't work with PostgreSQL
**Examples**: ImportUserSeeder, ImportLotSeeder, ImportClientSeeder, SyncAirtable, WPWebsiteImport, ProjectBasicDetailsUpdate

These seeders were designed for v3 MySQL imports and won't work on PostgreSQL. They should be archived and replaced with v4-specific import commands (already planned in rebuild steps).

### 9.5 SyncUsers Event on Model Save/Delete

**Impact**: Silent failures if queue not configured
**Model**: User fires `SyncUsers` event on `saved` and `deleted` when `IS_BOT_ENABLED=true`

If `QUEUE_CONNECTION=sync` (synchronous), this blocks the HTTP request. If the bot database is unavailable, it may throw unhandled exceptions.

**Recommendation for v4**: Replace with proper queued jobs if bot sync is needed, or remove entirely if using Prism/AI SDK.

### 9.6 Missing jobs/cache/sessions Table Migrations (NEW - Pass 3)

Queue driver is set to `database` in `config/queue.php` and cache driver is set to `database` in `config/cache.php`, but neither `jobs`, `failed_jobs`, nor `cache` table migrations exist. These must be created in v4 Step 0 or the drivers should switch to Redis.

### 9.7 No Morph Map Anywhere (NEW - Pass 3)

No `Relation::enforceMorphMap()` defined in any service provider. All polymorphic `*_type` columns store full PHP namespaces (`App\Models\Lead`). When Lead is renamed to Contact in v4, every polymorphic record referencing `App\Models\Lead` will break unless a morph map or data migration is in place.

### 9.8 Addresses Table/Model Not Previously Captured (NEW - Pass 3)

The `addresses` table (polymorphic via Aecor `HasAddress` trait) was missed in initial passes. It's used by Lead, Developer, Project, PotentialProperty, and Company. Must be included in the v4 import mapping strategy.

---

## 10. Best Practice Recommendations

### 10.1 PostgreSQL-Specific Optimizations (v4)

```sql
-- 1. Partial indexes for soft-deleted queries (huge performance win)
CREATE INDEX idx_contacts_active ON contacts(organization_id, stage)
WHERE deleted_at IS NULL;

-- 2. GIN index for JSONB queries
CREATE INDEX idx_contacts_extra ON contacts USING GIN (extra_attributes);

-- 3. BRIN index for time-series data (created_at ranges)
CREATE INDEX idx_sales_created ON sales USING BRIN (created_at);

-- 4. Expression indexes for case-insensitive search
CREATE INDEX idx_contacts_email_lower ON contact_emails (LOWER(email));

-- 5. Covering indexes for common queries
CREATE INDEX idx_lots_project_covering ON lots(project_id)
INCLUDE (lot_number, price, status);

-- 6. Use earth_distance for geographic queries (replaces DB::raw lat/lng math)
CREATE EXTENSION IF NOT EXISTS cube;
CREATE EXTENSION IF NOT EXISTS earthdistance;
```

### 10.2 Laravel Migration Best Practices

1. **One migration per table** - include ALL columns, constraints, and indexes
2. **Use `foreignId()` helper** - auto-creates `unsignedBigInteger` + `constrained()`
3. **Always specify `onDelete()`** - `cascade`, `nullOnDelete()`, or `restrictOnDelete()`
4. **Add composite indexes** - for multi-tenant queries (`organization_id` first)
5. **Use `after()` sparingly** - column order doesn't matter in PostgreSQL
6. **Test migrations fresh** - `php artisan migrate:fresh` should work from zero
7. **Remove `defaultStringLength(191)`** - PostgreSQL doesn't need this workaround

### 10.3 Model Design Best Practices

1. **Explicit eager loading** - never rely on lazy loading for lists/APIs
2. **Query scopes for common filters** - `scopeActive()`, `scopeForOrganization()`
3. **Money casts** - all financial fields use `MoneyCast::class`
4. **State machines** - `spatie/laravel-model-states` for status fields
5. **Soft cascade** - `askedio/laravel-soft-cascade` for parent-child chains
6. **DTOs for API responses** - don't expose model internals
7. **Observers not boot()** - side effects belong in Observer classes
8. **Relationship traits** - group related relationships (HasCommunications, HasSalesActivity)
9. **Prevent lazy loading** - `Model::preventLazyLoading(!app()->isProduction())`

### 10.4 CRM-Specific Database Design Patterns

1. **Contact-centric design** - single person entity, multiple detail tables
2. **Multi-tenant from day one** - `organization_id` on every table, composite indexes
3. **Audit trail** - `created_by`, `updated_by` on all business tables
4. **Temporal data** - `last_contacted_at`, `next_followup_at` as indexed columns
5. **Flexible tagging** - polymorphic tags table (already in v3, keep for v4)
6. **Activity timeline** - `spatie/laravel-activitylog` (already configured)
7. **Table decomposition** - keep tables under 20-25 columns; split by domain

---

## 11. Live Data Analysis - Features to Remove/Keep

> Data queried from live replica database `fv3` on 2026-03-18

### 11.1 Complete Table Row Counts

| Table | Rows | Last Updated | Verdict |
| --- | --- | --- | --- |
| activity_log | 461,740 | 2026-03-09 | **KEEP** - active audit trail (but prune old data) |
| addresses | 24,646 | 2026-03-07 | **KEEP** - 15K project + 9K lead addresses |
| ad_managements | 11 | 2026-03-07 | **REVIEW** - very low usage but recently active |
| ai_bot_boxes | 46 | 2025-11-29 | **REPLACE** - moving to Prism/AI SDK in v4 |
| ai_bot_categories | 11 | 2024-06-20 | **REPLACE** - part of old bot system |
| ai_bot_prompt_commands | 481 | 2025-11-29 | **REPLACE** - part of old bot system |
| au_towns | 15,299 | N/A (static) | **REVIEW** - geographic reference data, may overlap with suburbs |
| brochure_mail_jobs | 191 | 2026-03-04 | **KEEP** - active email marketing |
| campaign_websites | 200 | 2026-02-23 | **KEEP** - active marketing sites |
| campaign_website_project | 328 | N/A | **KEEP** - pivot for campaign-project links |
| campaign_website_templates | 1 | 2021-07-12 | **REMOVE** - 1 row, never updated since seed |
| column_management | 4,519 | 2026-03-09 | **KEEP** - actively used UI column preferences |
| comments | 4,259 | 2026-03-09 | **KEEP** - active (PropertyEnquiry: 2489, PropertySearch: 1502, PropertyReservation: 268) |
| commissions | 19,516 | 2026-03-06 | **KEEP + REFACTOR** - Project: 15254, Lot: 4262 (normalize in v4) |
| companies | 976 | 2026-03-08 | **KEEP** - active lookup |
| contacts (detail) | 20,159 | 2026-03-07 | **KEEP + RENAME** - All Lead data (phone: 9601, email: 9163, website: 692, etc.) |
| developers | 336 | 2026-03-03 | **KEEP** - active lookup |
| email_settings | 45 | 2026-03-04 | **KEEP** - active email config |
| flyers | 10,435 | 2026-03-08 | **KEEP** - active marketing |
| leads | 9,735 | 2026-03-08 | **KEEP** - core entity (becomes contacts in v4) |
| login_histories | 42,033 | 2026-03-17 | **KEEP** - active security audit |
| lots | 121,360 | 2026-03-09 | **KEEP** - core entity, largest business table |
| love_reactants | 16,064 | 2026-03-06 | **REVIEW** - only used for Projects (16064 = all projects) |
| love_reacters | 1,510 | 2026-03-06 | **REVIEW** - matches user count exactly |
| love_reactions | 2,172 | 2026-03-08 | **REVIEW** - "Like" system for projects only |
| love_reaction_types | 1 | 2021-07-12 | **REVIEW** - only 1 type: "Like" |
| mail_lists | 74 | 2026-03-04 | **KEEP** - active email lists |
| media | 108,510 | 2026-03-08 | **KEEP** - Project: 88K, Flyer: 11K, Sale: 4K, Lead: 2K |
| notes | 22,681 | 2026-03-06 | **KEEP** - Sale: 16189, Lead: 6337, Project: 155 |
| partners | 299 | 2026-02-27 | **KEEP** - active referral tracking |
| projects | 15,481 | 2026-03-09 | **KEEP** - core entity |
| project_updates | 155,273 | 2026-03-09 | **KEEP** - changelog (updated: 148K, created: 6.5K) |
| property_enquiries | 721 | 2026-03-06 | **KEEP** - active property workflow |
| property_reservations | 120 | 2026-02-19 | **KEEP** - active (86 unique lots) |
| property_searches | 316 | 2026-03-07 | **KEEP** - active buyer matching |
| questionnaires | 31 | 2025-09-01 | **REVIEW** - 31 records in 4 years, barely used |
| relationships | 7,378 | N/A | **KEEP** - lead-to-lead relationship mapping |
| resources | 138 | 2026-02-11 | **KEEP** - training/resource library |
| sales | 447 | 2026-03-04 | **KEEP** - core entity (392 unique lots, 256 projects) |
| statusables | 148,680 | N/A | **REPLACE** - moving to spatie/laravel-model-states |
| statuses | 214 | 2023-04-01 | **REPLACE** - Aecor status definitions, 3 years stale |
| suburbs | 15,299 | 2021-07-12 | **KEEP** - geographic reference (static seed data) |
| tasks | 57 | 2026-02-21 | **KEEP** - low usage but recently active |
| users | 1,502 | 2026-03-06 | **KEEP** - core entity |
| user_api_tokens | 1,502 | 2026-03-06 | **KEEP** - active API auth |
| user_api_ips | 129 | 2026-03-07 | **KEEP** - active IP whitelisting |
| website_contacts | 1,235 | 2026-03-07 | **KEEP** - active form submissions |
| websites | 574 | 2026-03-06 | **KEEP** - active website management |
| website_pages | 137 | 2026-01-20 | **KEEP** - active CMS |
| website_elements | 705 | 2026-01-20 | **KEEP** - active CMS |
| wordpress_websites | 207 | 2026-02-24 | **KEEP** - active WP integration |

### 11.2 Features to REMOVE (Dead/Unused - 16 tables, 0 business value)

| Table | Rows | Reason | Action |
| --- | --- | --- | --- |
| **action_events** | 0 | Nova event log - never used, Nova not used | DROP - not migrating to v4 |
| **default_fonts** | 0 | Never populated | DROP |
| **failed_jobs** | 0 | Queue failure log - never captured any | Recreate fresh in v4 |
| **model_has_permissions** | 0 | Individual permissions never used (only roles) | DROP - use Spatie Permission in v4 |
| **oauth_access_tokens** | 0 | Laravel Passport - never used | DROP - use Sanctum in v4 |
| **oauth_auth_codes** | 0 | Laravel Passport - never used | DROP |
| **oauth_clients** | 0 | Laravel Passport - never used | DROP |
| **oauth_personal_access_clients** | 0 | Laravel Passport - never used | DROP |
| **oauth_refresh_tokens** | 0 | Laravel Passport - never used | DROP |
| **personal_access_tokens** | 0 | Sanctum tokens - never used | Recreate fresh in v4 if needed |
| **settings** | 0 | App settings - never populated | DROP or redesign in v4 |
| **taggables** | 0 | Polymorphic tags - never used | DROP |
| **tags** | 0 | Tag definitions - never used | DROP |
| **team_user** | 0 | Team membership - never used | DROP |
| **teams** | 0 | Teams feature - never used | DROP |
| **widget_settings** | 0 | Widget config - never used | DROP |

### 11.3 Features to REMOVE (Barely Used/Abandoned - 7 tables)

| Table | Rows | Last Activity | Reason | Action |
| --- | --- | --- | --- | --- |
| **campaign_website_templates** | 1 | 2021-07-12 | 1 seed row, never used in 5 years | DROP |
| **finance_assessments** | 1 | 2025-11-05 | 1 test record in 35-column table | DROP - feature never launched |
| **potential_properties** | 1 | 2025-11-05 | 1 test record | DROP - feature never launched |
| **flyer_templates** | 3 | 2021-07-12 | 3 seed rows, never updated | DROP - flyers use media instead |
| **spr_requests** | 4 | 2025-11-27 | 4 records in 3.5 years | DROP - barely used |
| **wordpress_templates** | 4 | 2021-07-12 | Seed data only | DROP - WordPress uses its own templates |
| **survey_questions** | 7 | 2021-07-12 | Seed data only, never updated | DROP |

### 11.4 Features to REVIEW (Low Usage - Decide Keep/Remove)

| Table | Rows | Evidence | Recommendation |
| --- | --- | --- | --- |
| **questionnaires** | 31 | 31 records across 29 leads in 4 years | LIKELY REMOVE - barely adopted |
| **onlineform_contacts** | 19 | 19 submissions in 3.5 years | LIKELY REMOVE - replaced by website_contacts (1235 rows) |
| **ad_managements** | 11 | 11 ads, but recently active (2026-03-07) | KEEP if actively managed |
| **au_towns** | 15,299 | Geographic data overlapping with suburbs (15,299) | MERGE with suburbs or keep as enrichment |
| **love_* tables** (5 tables) | 2,172 reactions | Only used for Projects "Like" feature (1 reaction type) | SIMPLIFY - replace 5 tables with a simple `project_favorites` boolean or pivot table |
| **resource_categories** | 5 | Static seed data | KEEP if resources feature continues |
| **resource_groups** | 9 | Static seed data | KEEP if resources feature continues |

### 11.5 Features to REPLACE in v4

| Current | Rows | Replacement | Migration Strategy |
| --- | --- | --- | --- |
| **ai_bot_boxes** (46) + **ai_bot_categories** (11) + **ai_bot_prompt_commands** (481) | 538 total | Prism/AI SDK + `agent_conversations` table in starter kit | Export prompt commands as AI agent config; discard bot_boxes |
| **statuses** (214) + **statusables** (148,680) | 148,894 | `spatie/laravel-model-states` - state stored as column on each model | Flatten: map statusable records to model columns (contact.stage, sale.status, etc.) |
| **addresses** (24,646 polymorphic) | 24,646 | Dedicated columns on contacts + projects, or normalized addresses table with explicit FKs | Extract by model_type: Project addresses -> project columns, Lead addresses -> contact columns |
| **contacts** (20,159 polymorphic detail) | 20,159 | `contact_emails` + `contact_phones` tables | Split by type: email_1/email_2 -> contact_emails, phone -> contact_phones, social -> contact_socials or extra_attributes |
| **commissions** (19,516 polymorphic) | 19,516 | Normalized `commissions` table with sale_id FK | Only 4,262 on Lots need special handling (lot-level commission rates) |
| **6 OAuth/Passport tables** | 0 | Laravel Sanctum (already in starter kit) | Nothing to migrate - all empty |

### 11.6 Data Volume Summary for Migration Planning

| Entity | Row Count | Migration Priority | Estimated Effort |
| --- | --- | --- | --- |
| lots | 121,360 | HIGH - largest business table | Medium (straightforward mapping) |
| media | 108,510 | HIGH - file references | Medium (Spatie media library compatible) |
| statusables | 148,680 | HIGH - must flatten to model columns | High (complex mapping per model type) |
| activity_log | 461,740 | LOW - can skip, regenerate in v4 | Skip |
| project_updates | 155,273 | MEDIUM - changelog history | Low (direct copy) |
| login_histories | 42,033 | LOW - historical only | Skip or archive |
| addresses | 24,646 | HIGH - split by model type | Medium |
| notes | 22,681 | MEDIUM - mostly Sale + Lead notes | Low (morph mapping) |
| contacts (detail) | 20,159 | HIGH - split to emails/phones | Medium |
| commissions | 19,516 | HIGH - normalize to sale-based | High (polymorphic -> FK) |
| projects | 15,481 | HIGH - core entity + decompose | High (63 cols -> 5+ tables) |
| leads | 9,735 | HIGH - becomes contacts | Medium |
| relationships | 7,378 | MEDIUM - lead-to-lead links | Low (FK remapping) |
| users | 1,502 | HIGH - core entity | Low |
| websites + pages + elements | 1,416 | MEDIUM | Low |

---

## 12. Table-by-Table Audit

### Core CRM Tables

| Table | Columns | FKs Defined | FKs Constrained | Indexes | SoftDelete | Issues |
| --- | --- | --- | --- | --- | --- | --- |
| leads | 38 | 3 | 0 | 1 | Yes | God object (25 rels), missing FK constraints, boot() deletes users |
| projects | 80 | 4 | 1 | 2 | Yes | **CRITICAL bloat**, needs decomposition into 5+ tables |
| lots | 48 | 2 | 0 | 0 | No | "storyes" typo, no soft delete, no indexes, 5% share tracking |
| sales | 30 | 10 | 0 | 0 | Yes | Denormalized commissions, no FK constraints |
| property_reservations | 47 | 5 | 0 | 0 | Yes | Bloated (finance/legal/broker), missing FK constraints |
| property_enquiries | ~12 | 3 | 0 | 0 | Yes | Missing FK constraints, uses client_lead/agent_lead naming |
| property_searches | ~10 | 2 | 0 | 0 | Yes | Polymorphic contact relationship |
| tasks | 12 | 3 | 0 | 0 | Yes | Missing indexes on due_date, assigned_id |
| contacts (v3) | 8 | 2 | 0 | 0 | No | Polymorphic detail table, naming collision with v4 |
| finance_assessments | 35 | 2 | 0 | 0 | Unknown | Heavy financial data, needs money casts |
| users | 44 | 1 | 0 | 1 | Yes | Mixed concerns, duplicate HasFactory, boot() deletes devs |
| developers | ~15 | 1 | 0 | 0 | Yes | boot() deletes ALL projects on delete |

### Marketing/Website Tables

| Table | Columns | Issues |
| --- | --- | --- |
| campaign_websites | 36 | Config bloat |
| flyers | 31 | Marketing materials |
| websites | 29 | Website management |
| wordpress_websites | 27 | WordPress integration |
| mail_lists | ~10 | Array casts for emails |
| brochure_mail_jobs | ~12 | Array casts for client_ids |

### Lookup Tables

| Table | Has UNIQUE | Has organization_id | Issue |
| --- | --- | --- | --- |
| sources | No | No | Allows duplicate names, no tenant scope |
| companies | No | No | Same |
| developers | No | No | Same |
| projecttypes | No | No | Same |
| states | No | N/A | OK - geographic, shared |
| suburbs | No | No | OK - geographic, shared |
| services | No | No | Allows duplicates |
| partners | No | No | Should scope to organization |

### Pivot Tables

| Table | Has Timestamps | Has Unique Constraint | Issue |
| --- | --- | --- | --- |
| company_service | No | Yes | Missing timestamps |
| team_user | No | Yes | Missing timestamps |
| campaign_website_project | No | No | Missing unique + timestamps |
| taggables | No | No | Missing unique constraint |

### Polymorphic Tables (via Aecor)

| Table | Morph Columns | Models Using | v4 Replacement |
| --- | --- | --- | --- |
| statuses | statusable_type/id | 15+ models | spatie/laravel-model-states |
| addresses | addressable_type/id | 5+ models | Dedicated address columns |
| contacts (detail) | contactable_type/id | 3+ models | contact_emails, contact_phones |
| commissions | commissionable_type/id | Lot, Project, Sale | Dedicated commissions table |

---

## 12. Action Plan

### Phase 0: Pre-Build Documentation (Before Step 0)

- [ ] Establish naming convention document (FKs, booleans, timestamps)
- [ ] Document Aecor trait replacement strategy for all 20+ affected models
- [ ] Document all boot() cascade deletions to replace
- [ ] Map custom Searchable trait fields to Scout `toSearchableArray()`
- [ ] Archive 35 legacy seeders
- [ ] Archive SyncProject/SyncUsers commands
- [ ] Audit `extra_attributes` usage to determine which fields become columns
- [ ] Define morph map for all polymorphic models (critical for Lead -> Contact rename)
- [ ] Document addresses table migration strategy
- [ ] Align legacy connection name (OLD_DB -> mysql_legacy)
- [ ] Establish eager loading and transaction standards document
- [ ] **Confirm removal of 23 dead/unused tables** (get stakeholder sign-off)
- [ ] **Decide on 7 review tables** (questionnaires, online forms, ads, au_towns, love system)
- [ ] **Plan love_* simplification** (5 tables -> 1 `project_favorites` pivot)
- [ ] **Plan statusables flattening** (148K rows -> model columns by type)
- [ ] **Plan contacts detail split** (20K rows -> contact_emails + contact_phones + contact_socials)

### Phase 1: v4 Migration Design (Step 0)

- [ ] Define ALL tables with complete constraints in single migrations
- [ ] Decompose projects (63 cols) into 5+ focused tables
- [ ] Normalize commissions into dedicated table with unique constraint
- [ ] Add composite indexes for multi-tenant queries
- [ ] Add CHECK constraints for all enum-like columns
- [ ] Plan partial indexes for soft-deleted tables
- [ ] Add GIN index for JSONB `extra_attributes`
- [ ] Remove `defaultStringLength(191)` workaround
- [x] ~~Include Database Mail migration~~ (done — starter kit has it + org-scoped EmailTemplatesController)
- [ ] Include Reverb migration
- [ ] Include Scout/Typesense configuration
- [ ] Include Durable Workflow migration
- [ ] Set up Sentry + Backup
- [ ] Add jobs/failed_jobs/cache migrations (or switch to Redis driver)
- [ ] Define `Relation::enforceMorphMap()` in AppServiceProvider
- [ ] Add `Model::preventLazyLoading(!app()->isProduction())` in AppServiceProvider

### Phase 2: Model Architecture (Step 0-1)

- [ ] Create Contact model with relationship traits (not God Object)
- [ ] Create Observers for Lead/User/Developer/Project (replace boot())
- [ ] Implement soft-cascade chains
- [ ] Add Money casts to all financial fields
- [ ] Implement Model States for status/stage fields
- [ ] Add `Model::preventLazyLoading()` in development
- [ ] Replace Aecor traits with v4 equivalents
- [ ] Establish transaction wrapping pattern for all multi-step operations
- [ ] Add pessimistic locking for lot/reservation status transitions
- [ ] Cache Livewire component lookups (use #[Computed] or property caching)

### Phase 3: Data Migration (Steps 1-5)

- [ ] Create `legacy_lead_id` with B-tree index on contacts
- [ ] Implement idempotent import commands with verification
- [ ] Validate FK integrity after each import step
- [ ] Test soft cascade chains after import
- [ ] Verify commission normalization from flat columns
- [ ] Migrate polymorphic statuses/addresses/contacts to dedicated tables
- [ ] Migrate addresses table data (Lead, Developer, Project, Company addresses)
- [ ] Convert array-cast fields to proper jsonb or normalized tables

### Phase 4: Search, AI, and Beyond (Step 6+)

- [ ] Add pgvector indexes for embedding tables
- [ ] Configure Scout indexes for Contact/Project/Lot (replace custom Searchable)
- [ ] Set up real-time notification channels (Reverb)
- [ ] Replace DB::raw geographic queries with earth_distance extension
- [ ] Audit all raw SQL for PostgreSQL compatibility
- [ ] Add monitoring for query performance (slow query log)

---

## Issue Summary

| Severity | Count | Key Areas |
| --- | --- | --- |
| **CRITICAL** | 10 | Missing FKs, cascade deletions, table bloat, Aecor, zero eager loading, zero transactions, connection mismatch |
| **HIGH** | 11 | Missing indexes, money types, God Object, N+1, Livewire queries, missing morph map, jobs/cache tables |
| **MEDIUM** | 13 | Naming, UNIQUE constraints, JSON overuse, CHECK constraints, search migration, locking, addresses table |
| **LOW** | 7 | Pivot timestamps, decimal precision, legacy columns, string length |
| **v4 Plan Gaps** | 21 | Aecor strategy, boot() removal, morph map, addresses, eager loading standards, transactions |
| **Anti-Patterns** | 13 | God Object, table bloat, polymorphic abuse, destructive boot(), zero eager loading, zero transactions, missing morph map |
| **Raw SQL Risks** | 19+ | Dynamic column injection, MySQL-specific syntax, bypassing Eloquent |
| **Infra Issues** | 8 | 4 DB connections, 0 observers, 2 Nova resources, 35 seeders, missing tables, morph map, addresses |
| **Tables to REMOVE** | 23 | 16 empty/dead + 7 barely used/abandoned |
| **Tables to REPLACE** | 11 | OAuth, AI Bot, Aecor statuses/addresses/contacts, commissions |
| **Tables to REVIEW** | 7 | Questionnaires, online forms, ads, au_towns, love system |
| **TOTAL ISSUES** | **83+** | |
| **TOTAL TABLES TO DROP** | **23-30** | Reduces 75 tables to ~45-52 in v4 |

---

## Sources and References

### Best Practices Research

- [Top 10 Database Schema Design Best Practices](https://www.bytebase.com/blog/top-database-schema-design-best-practices/)
- [PostgreSQL Database Design Best Practices](https://dev.to/adityabhuyan/best-practices-for-postgresql-database-design-to-ensure-performance-scalability-and-efficiency-3j4n)
- [Database Design Patterns Every Developer Should Know](https://www.bytebase.com/blog/database-design-patterns/)
- [Database Anti-Patterns: The Silent Killers of Data Integrity](https://thearchitectsnotebook.substack.com/p/ep-67-database-anti-patterns-5-signs)
- [SQL Anti-Patterns (GitHub)](https://github.com/boralp/sql-anti-patterns)
- [Common Foreign Key Mistakes](https://www.cockroachlabs.com/blog/common-foreign-key-mistakes/)
- [Anti-Patterns in Database Design: Real-World Failures](https://levitation.in/posts/anti-patterns-in-database-design-lessons-from-real-world-failures)
- [Common Anti-patterns in Laravel Development](https://www.binarcode.com/blog/common-antipaterns-laravel-development)
- [Laravel Best Practices (GitHub)](https://github.com/alexeymezenin/laravel-best-practices)
- [Top 10 PostgreSQL Best Practices for 2025](https://www.instaclustr.com/education/postgresql/top-10-postgresql-best-practices-for-2025/)

---

*Report generated by SuperClaude Database Analyzer (3-pass audit). Review with development team before implementing changes.*
