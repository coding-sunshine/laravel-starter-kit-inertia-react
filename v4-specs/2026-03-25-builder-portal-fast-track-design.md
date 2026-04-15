# Builder Portal Fast-Track Design

> **Date:** 2026-03-25
> **Status:** Approved
> **Context:** Competing with Kenekt ($799-990/mo). PIAB offers builder portal FREE to capture supply.
> **Strategy:** Ship Builder Portal first, migrate subscribers later. One codebase, phased launch.
> **Note:** "Phase 1/2/3" in this doc = feature build phases. The older `archive/2026-03-23-implementation-plan.md` used "Phase 1/2/3" for document rewrite phases (now complete). This doc supersedes it for build ordering.

---

## 1. Architecture

One v4 codebase (starter kit), phased launch. No standalone app. No throwaway code.

```
PHASE 1: Builder Portal (SHIP FAST)
┌─────────────────────────────────┐         ┌─────────────────────────────┐
│  v4 App (starter kit)           │         │  v3 App (existing, no changes)
│                                 │         │                             │
│  Builder Workbench              │         │  Nova / CRM UI              │
│  ├── Construction Library       │         │  (subscribers keep using)   │
│  ├── Package Creation           │         │                             │
│  ├── Distribution Control       │         │  projects table ◄──────┐   │
│  ├── Bulk Import                │         │  lots table    ◄──────┐│   │
│  └── My Deals                   │         │                       ││   │
│                                 │         │  Subscribers see      ││   │
│  Agent Portal                   │         │  builder stock as     ││   │
│  ├── Property Search            │         │  normal project/lot   ││   │
│  ├── My Lists                   │         │  listings             ││   │
│  ├── Documents                  │         └───────────────────────┼┼───┘
│  ├── Enquiries                  │                                 ││
│  └── Brochure Download          │                                 ││
│                                 │         Auto-push via observers ││
│  Self-Signup                    │         (enriched projects+lots)││
│  ├── Builder registration       │─────────────────────────────────┘│
│  └── Org creation (developer)   │──────────────────────────────────┘
│                                 │
│  PostgreSQL (source of truth)   │
│  mysql_legacy (outbound push)   │
└─────────────────────────────────┘

PHASE 2: Subscriber Migration (LATER)
  - Import contacts, users, sales from v3
  - Full bidirectional sync
  - Subscriber CRM features
  - v3 decommissioned

PHASE 3: Advanced Features (LATER)
  - AI lead gen, property matching
  - Marketing, analytics
  - Xero integration
  - PRD 12 Tiers 2-3
```

## 2. Build Sequence (Phase 1)

| Order | Original Step | What To Do | Scope |
|-------|--------------|------------|-------|
| 1 | Pre-Flight | Clone starter kit, configure .env, run migrations | Full |
| 2 | Step 0a | Module setup, PostgreSQL extensions, morph map, directory structure | Full |
| 3 | Step 0b | Config, seeders, PIAB org, roles, Spatie Permission, lookup tables, Stripe stub | Full |
| 4 | Step 2 (Users) | User migrations + models + auth + self-signup | Migrations + models + auth. **SKIP** v3 user import (1,502 users). |
| 5 | Step 3 (Projects & Lots) | Project + lot migrations + models | Migrations + models. **SKIP** v3 import (15K projects, 121K lots). |
| 6 | Step 4 (Sales) | Sales, commissions, reservations, enquiries, searches — migrations + models | Migrations + models. **SKIP** v3 import (447 sales, 120 reservations). |
| 7 | Step 1.5-slim | `mysql_legacy` connection + outbound push observers only | **SKIP** bidirectional sync, pollers, field mappers, sync logs. Only outbound push. |
| 8 | **PRD 12 Tier 1** | Construction Library, Packages, Distribution, Workbench, Agent Portal, Brochures, v3 push | **THE PRODUCT** |
| 9 | **Launch** | Deploy. Builders self-signup. Agents browse portal. Stock visible in v3. | |

**What is SKIPPED in Phase 1:**
- Step 1 (Contacts) — 9,735 contact imports, polymorphic splits, stage mapping
- Step 1.5 full sync — bidirectional sync, pollers, field mappers
- Step 2 user import — 1,502 v3 users (builders register fresh)
- Step 3 data import — 15K projects, 121K lots from v3
- Step 4 data import — 447 sales, 120 reservations from v3
- Step 5 (Notes, partners, relationships) — subscriber CRM data
- Step 6 (AI, embeddings, credits) — nice-to-have
- Step 7 (Dashboards, reports) — subscriber dashboards

**What is DONE FULLY in Phase 1:**
- All migrations and models (schema is complete — ready for Phase 2 imports)
- Auth + self-signup (builders register, create orgs)
- Construction Library (designs, facades, inclusions, commission templates)
- Packages (lot + library = sellable product)
- Distribution & allocation (open/exclusive per agent)
- Builder Workbench UI
- Agent Portal UI (search, filter, export, my lists, documents, enquiry, brochure)
- Commission tracking by construction stage
- Document cascade + white-labelling
- v3 push (stock appears as projects + lots in v3)

## 3. v3 Push Design

**Trigger:** Model observers on `Package` create/update/delete in v4.

**What gets pushed:**

```
v4 Package Created/Updated
    │
    ├── UPSERT v3 `projects` row
    │   ├── name = project name from v4
    │   ├── suburb, state, postcode from lot address
    │   ├── status = 'available'
    │   ├── developer_id = resolve from v4 org
    │   ├── description = rich summary (design + builder info)
    │   └── images = media URLs from project
    │
    └── UPSERT v3 `lots` row
        ├── lot_number = from v4 lot
        ├── project_id = FK to v3 project (just created above)
        ├── price = package.total_price (what subscribers care about)
        ├── land_price = package.land_price
        ├── land_size, land_width, land_depth from v4 lot
        ├── registration_date from v4 lot
        ├── status = mapped from package status
        └── description = enriched text:
            "Design: Daintree 15 | 3 bed 2 bath 2 garage
             Build: $219,000 | Facade: Nautica 12.5 ($706,000)
             Inclusions: Standard Package
             Builder: Vantage Living"

v4 Package Deleted
    └── UPDATE v3 `lots` SET status = 'archived'
```

**Mapping rules:**
- v4 project → v3 project (upsert by `name` + org)
- v4 package → v3 lot (upsert by `lot_number` + `project_id`)
- v3 lot.price = v4 package.total_price
- v3 lot.description = formatted design + facade + inclusions summary
- Status sync: v4 `available` → v3 `available`, v4 `sold` → v3 `sold`, v4 `archived`/`draft` → v3 `archived`
- Push is immediate (observer, not queued) for create/update. Queued for bulk imports.

**Connection config:**
```php
// config/database.php
'mysql_legacy' => [
    'driver' => 'mysql',
    'host' => env('DB_LEGACY_HOST', '127.0.0.1'),
    'port' => env('DB_LEGACY_PORT', '3306'),
    'database' => env('DB_LEGACY_DATABASE', 'fv3'),
    'username' => env('DB_LEGACY_USERNAME', 'root'),
    'password' => env('DB_LEGACY_PASSWORD', ''),
],
```

## 4. Self-Signup Flow

Starter kit uses Fortify for auth. On user creation, a `UserCreated` event fires and
`CreatePersonalOrganizationOnUserCreated` listener auto-creates an Organization.

**What needs customizing for builder signup:**

1. Builder visits registration page (Fortify default)
2. Fills in: name, email, password, company name (add `company_name` field to registration form)
3. `CreateNewUser` action creates User (extend to accept `company_name`)
4. `UserCreated` event → listener creates Organization
5. **Customize listener**: set `organization.type = 'developer'` and `organization.name = company_name` (instead of default "{name}'s Workspace")
6. **Add:** Organization gets subdomain: `{company-slug}.fusioncrm.com` (set `slug` on org)
7. Builder redirected to Workbench dashboard (empty, ready to add stock)
8. Optional: PIAB admin approval gate via `BuilderPortalFeature` Pennant flag

**Key customization points:**
- `app/Actions/Fortify/CreateNewUser.php` — add `company_name` validation + pass to event
- `app/Listeners/CreatePersonalOrganizationOnUserCreated.php` — set type='developer', use company_name
- Registration Inertia page — add `company_name` input field
- Config: `tenancy.auto_create_personal_organization` must be `true` (default)

## 5. Phase 2: Subscriber Migration (Later)

After Builder Portal is live and builders are onboarded:

1. Run Step 1 (Contacts import — 9,735 contacts)
2. Run Step 1.5 full (bidirectional sync)
3. Run Step 2 import (1,502 v3 users)
4. Run Step 3 import (15K projects, 121K lots — merge with builder-created data)
5. Run Step 4 import (447 sales, 120 reservations)
6. Run Steps 5-7 (notes, partners, AI, dashboards)
7. Subscriber orgs switch from v3 to v4
8. v3 push observers removed (no longer needed)
9. v3 decommissioned

**Key merge consideration:** When Step 3 import runs, builder-created projects/lots already exist in v4. Import must detect and skip/merge duplicates using lot_number + project name matching.

## 6. Phase 3: Advanced Features (Later)

- PRD 12 Tier 2: Saved searches, email alerts, newsletters, brochure generation, API syndication
- PRD 12 Tier 3: AI push suggestions, MLS formatting, compliance, analytics
- PRDs 13-20: Marketing, websites API, Xero, R&D features

## 7. Data Model

All 45 CRM tables are created in Phase 1 (migrations run fully). Only the import commands are skipped.

**Phase 1 tables actively used:**
- organizations, users (auth + self-signup)
- projects, project_specs, project_investment, project_features, project_pricing, lots (builder stock)
- construction_designs, construction_facades, construction_inclusions, construction_inclusion_items (library)
- commission_templates (commission structures)
- packages, package_inclusions, package_distributions (core product)
- agent_favorites, agent_saved_searches (agent portal)
- sales, commissions, sale_commission_payments (My Deals)
- enquiries (structured enquiry form)
- stock_imports (bulk import tracking)
- media (Spatie — documents, images, brochures)

**Phase 1 tables created but empty (awaiting Phase 2 import):**
- contacts, contact_emails, contact_phones
- property_reservations, property_enquiries, property_searches
- tasks, notes, comments, partners, relationships
- project_favorites, project_updates
- sync_logs
- All lookup tables (populated by seeders, not import)

## 8. Success Criteria

**Phase 1 Launch = success when:**
- [ ] Builder can self-signup and land in workbench
- [ ] Builder can create designs, facades, inclusions in Construction Library
- [ ] Builder can create packages (lot + design + facade + inclusions = auto-priced)
- [ ] Builder can distribute packages to agents (open/exclusive)
- [ ] Agent can log in, browse stock, filter, search, export
- [ ] Agent can save favorites, submit structured enquiries, download brochures
- [ ] Stock automatically appears in v3 as projects + lots
- [ ] v3 subscribers see builder stock in their existing project/lot views
- [ ] Commission tracking by construction stage works in My Deals
