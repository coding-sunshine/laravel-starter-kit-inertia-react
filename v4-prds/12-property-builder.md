# PRD 12: Property Builder & Push Portal

> **PRIORITY: URGENT** — This PRD is the #1 strategic priority. PIAB is competing directly with Kenekt ($799-990/mo stock management platform). The Builder Portal is offered FREE to capture supply at the source. See `v4/specs/kenekt-competitive-feature-spec.md` for full competitive analysis.
>
> **Phase 1 — SHIP FIRST (2026-03-25).** Builder Portal is the first thing that launches.
> Build sequence: Step 0a (full) → 0b (full) → 2-slim → 3-slim → 4-slim → 1.5-slim → **this PRD**.
> Contacts import, full sync, subscriber features all deferred to Phase 2.
> See `v4/specs/2026-03-25-builder-portal-fast-track-design.md` for full design.

## Overview

Build the **Builder Workbench** (stock management backend for developers/builders) and **Agent Portal** (frontend for referral agents to browse and sell distributed stock). These are two architecturally separate experiences connected by a distribution/allocation layer. Includes: Construction Library, package creation engine, bulk stock import, dynamic brochure generation, self-signup for builders, and auto-push of stock to v3 so existing subscribers see builder stock immediately.

**Prerequisites:** Steps 0a + 0b complete (full). Steps 2, 3, 4 migrations + models created (no v3 import needed). Step 1.5-slim (mysql_legacy connection + v3 push observer). Does NOT require: Step 1 (Contacts), Steps 5-7, or any v3 data imports.

**v3 Integration:** Stock auto-pushes to v3 MySQL as enriched projects + lots. Zero v3 code changes. Subscribers see builder stock in their existing project/lot views.

## Technical Context

- **Filament:** Superadmin system settings only. All builder/agent-facing UI is Inertia + React
- **DataTable:** Package lists, lot lists, push history, listing versions via AbstractDataTable pattern
- **API:** Public REST API for stock syndication (Sanctum-authenticated per org) + internal endpoints
- **Media:** `spatie/laravel-media-library` for listing assets (photos, floor plans, renders, brochures) with cascade inheritance (project -> lot -> package)
- **AI:** `laravel/ai` for match intelligence and push suggestions
- **PDF:** Starter kit's `GeneratePdf` action + `GeneratePdfJob` (wraps `spatie/laravel-pdf`)
- **Module:** `modules/module-crm/`, namespace `Cogneiss\ModuleCrm`

### Architecture: Two Separate Systems

```
BUILDER WORKBENCH (Inertia + React)            AGENT PORTAL (Inertia + React)
├── Stock creation & management                ├── Browse distributed stock
├── Construction Library (CRUD)                ├── Search + filters + export
├── Package creation (lot + design + facade)   ├── My Lists (favorites)
├── Distribution & allocation control          ├── Saved Searches + email alerts
├── Deal tracking & commission management      ├── My Deals (commission by stage)
├── Bulk import (CSV/Excel/URL)                ├── Documents (self-service)
├── Brochure template management               ├── General Enquiry form
└── Analytics & insights                       └── Brochure download
```

### New Database Tables (see newdb.md §3.31-3.43)

| Table | Purpose |
|-------|---------|
| `construction_designs` | Floor plan designs (library) |
| `construction_facades` | Facade options with pricing (library) |
| `construction_inclusions` | Inclusion packages (library) |
| `construction_inclusion_items` | Individual line items within inclusion packages |
| `commission_templates` | Reusable commission rate structures (library) |
| `packages` | H&L packages = lot + design + facade + inclusions |
| `package_inclusions` | Package <-> inclusion pivot with `is_featured` |
| `package_distributions` | Package <-> agent distribution control |
| `agent_favorites` | Agent personal shortlists |
| `agent_saved_searches` | Saved filter criteria + email alert flag |
| `sale_commission_payments` | Commission payments by construction stage |
| `enquiries` | Agent -> builder enquiry messages |
| `stock_imports` | Import history tracking |

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `DB_CONNECTION=pgsql` — PostgreSQL connection (set in preflight)
- `MAIL_MAILER` — Required for email alerts on saved searches

No special API keys required for this PRD.

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

> Stories are grouped into three tiers:
> - **Tier 1 (US-001 to US-008):** Kenekt parity — MUST SHIP for competitive displacement
> - **Tier 2 (US-009 to US-014):** High-impact differentiators — SHOULD SHIP
> - **Tier 3 (US-015 to US-020):** Phase 2 / nice-to-have

---

### TIER 1: Kenekt Parity (MUST SHIP)

### US-001: Construction Library — Migrations & Models

**Status:** todo
**Priority:** 1
**Description:** Create the Construction Library — the relational "recipe book" engine that powers package creation. This is NOT a media folder; it's a relational database of reusable building components with pricing logic at each level.

**Library categories** (each a separate entity):
1. **Floor Plan Designs** — design name, width/area, rooms, property type, build price, range/brand
2. **Facades** — facade name, colour scheme, elevation, storey type, facade price
3. **Inclusion Packages** — named tiers (Standard, Premium, etc.) with individual line items
4. **Commission Templates** — reusable commission rate structures (%, flat, tiered by stage)

**Relational linking:** design -> facade -> inclusions (mix and match across packages)

Migrations (all with `organization_id` FK CASCADE):

1. `create_construction_designs_table`: id, organization_id FK, name, slug, width_m DECIMAL(6,2), depth_m DECIMAL(6,2), floor_area_sqm DECIMAL(8,2), bedrooms SMALLINT, bathrooms SMALLINT, garages SMALLINT, living_areas SMALLINT, storeys SMALLINT DEFAULT 1, property_type VARCHAR (House/Duplex/Townhouse/Unit/etc.), build_price DECIMAL(12,2), range_name VARCHAR, brand VARCHAR, description TEXT, is_active BOOLEAN DEFAULT true, extra_attributes JSONB, timestamps, softDeletes
2. `create_construction_facades_table`: id, organization_id FK, name, slug, colour_scheme VARCHAR, elevation VARCHAR, storey_type VARCHAR(20), facade_price DECIMAL(12,2), is_active BOOLEAN DEFAULT true, extra_attributes JSONB, timestamps, softDeletes
3. `create_construction_inclusions_table`: id, organization_id FK, name (e.g. "Standard", "Premium"), slug, tier VARCHAR(20), description TEXT, is_active BOOLEAN DEFAULT true, extra_attributes JSONB, timestamps, softDeletes
4. `create_construction_inclusion_items_table`: id, inclusion_id FK CASCADE, category VARCHAR (kitchen/bathroom/exterior/etc.), item_name VARCHAR, description TEXT, is_featured BOOLEAN DEFAULT false, sort_order SMALLINT DEFAULT 0, timestamps
5. `create_commission_templates_table`: id, organization_id FK CASCADE, name, commission_type VARCHAR CHECK (percentage/flat/tiered), rate DECIMAL(8,4), flat_amount DECIMAL(12,2), stages JSONB (e.g. `{"unconditional": 25, "frame": 25, "lockup": 25, "completion": 25}`), is_active BOOLEAN DEFAULT true, timestamps, softDeletes

Models (all `Cogneiss\ModuleCrm\Models`):
- `ConstructionDesign`: `BelongsToOrganization`, `SoftDeletes`, `LogsActivity`. HasMany facades (via packages), hasMany media (floor plan images via Spatie).
- `ConstructionFacade`: `BelongsToOrganization`, `SoftDeletes`. HasMany media (renders).
- `ConstructionInclusion`: `BelongsToOrganization`, `SoftDeletes`. HasMany `ConstructionInclusionItem`.
- `ConstructionInclusionItem`: belongsTo inclusion. Scoped by `is_featured` for flyer display.
- `CommissionTemplate`: `BelongsToOrganization`, `SoftDeletes`. Stores stage-based payment splits in `stages` JSONB.

- [ ] 5 tables created with correct schema
- [ ] `organization_id` FK CASCADE on all library tables
- [ ] Construction designs have pricing, room counts, property type
- [ ] Facades have pricing, colour scheme, elevation
- [ ] Inclusion items have `is_featured` flag and `category` grouping
- [ ] Commission templates support percentage/flat/tiered types with stage splits
- [ ] All models use `BelongsToOrganization` trait
- [ ] `php artisan migrate` succeeds

### US-002: Packages — Migrations, Models & Creation Engine

**Status:** todo
**Priority:** 1
**Description:** Create the package system — combining a lot with Construction Library components into a sellable house & land product. Auto-calculates total price. A single lot can have multiple packages.

Migration `create_packages_table`: id, organization_id FK CASCADE, lot_id FK SET NULL (lots), project_id FK SET NULL (projects), construction_design_id FK SET NULL, construction_facade_id FK SET NULL, name VARCHAR, slug, land_price DECIMAL(12,2), build_price DECIMAL(12,2), facade_price DECIMAL(12,2), total_price DECIMAL(12,2) GENERATED ALWAYS AS (land_price + build_price + facade_price) STORED, status VARCHAR CHECK (draft/available/sold/archived) DEFAULT 'draft', distribution_type VARCHAR CHECK (none/open/exclusive) DEFAULT 'none', publish_at TIMESTAMPTZ, published_at TIMESTAMPTZ, commission_template_id FK SET NULL, extra_attributes JSONB, created_by FK SET NULL (users), timestamps, softDeletes

Migration `create_package_inclusions_table`: id, package_id FK CASCADE, construction_inclusion_id FK CASCADE, is_featured BOOLEAN DEFAULT false, sort_order SMALLINT DEFAULT 0, UNIQUE(package_id, construction_inclusion_id)

Package creation workflow:
1. Select lot → auto-fill land_price from lot, project from lot.project
2. Select design from Construction Library → auto-fill build_price
3. Select facade → auto-fill facade_price
4. Select inclusion package(s)
5. Total price auto-calculated (land + build + facade)
6. Set commission template
7. Package saved as `draft` → builder reviews → sets to `available`

Package detail has 7 sections/tabs:
- **Design/Floorplan**: design image, room specs, build price
- **Details**: lot details, address, land size, registration date
- **Inclusions**: inclusion packages with featured items flagged, warning if <15 featured items for flyer
- **Media**: floor plans, renders, documents (inherits from project/lot via cascade)
- **Distribution & Docs**: distribution controls + cascaded documents
- **Newsletters**: email distribution (see US-013)
- **Summary**: full price breakdown, specifications, status

- [ ] Packages table created with auto-calculated total_price
- [ ] Package links lot + design + facade + inclusions
- [ ] A single lot can have multiple packages
- [ ] Package status transitions: draft → available → sold/archived
- [ ] Package creation auto-fills prices from library components
- [ ] `PackageInclusion` pivot with `is_featured` flag
- [ ] Package show page with 7 tabs
- [ ] Warning when <15 featured inclusions selected

### US-003: Distribution & Allocation Control

**Status:** todo
**Priority:** 1
**Description:** Control which packages are visible to which agents. Supports open distribution (all agents) and exclusive allocation to specific agents.

Migration `create_package_distributions_table`: id, package_id FK CASCADE, user_id FK CASCADE (agent user), allocated_by FK SET NULL (users), allocated_at TIMESTAMPTZ DEFAULT NOW(), UNIQUE(package_id, user_id)

Distribution workflow:
- Package starts as `distribution_type = 'none'` (not distributed)
- Builder clicks "Open Distribution" → sets `distribution_type = 'open'` → all agents in network see it
- Builder clicks "Allocate" → search agents → select specific agents → creates `package_distribution` rows → `distribution_type = 'exclusive'`
- "Distribute to all" button → creates rows for all agent users in network
- "Clear All" → removes all distribution rows → `distribution_type = 'none'`
- Agent portal query: show packages where `distribution_type = 'open'` OR `package_distributions` row exists for current user

UI:
- "Open Distribution" button on package detail (workbench side)
- "Allocations" modal: agent search, "Distribute to all" / "Clear All" buttons
- Distribution status badge on package list (None / Open / Exclusive)
- Package preview: "Preview" button lets builder see exact agent portal view

- [ ] Distribution table created with unique constraint
- [ ] Open distribution makes package visible to all agents
- [ ] Exclusive distribution only visible to allocated agents
- [ ] "Distribute to all" creates rows for all agent users
- [ ] "Clear All" removes all distributions
- [ ] Preview button shows agent portal view of package
- [ ] Distribution status visible on package list

### US-004: Builder Workbench Dashboard

**Status:** todo
**Priority:** 1
**Description:** Create the builder-facing workbench — the stock management backend for developer orgs.

Workbench pages (under `/crm/workbench/` route prefix, scoped to `developer` org type):
- `/crm/workbench/` — Dashboard: project count, lot count (available/sold), package count, active agents, recent activity
- `/crm/workbench/projects` — Project list with lot counts (Available badge + Archived badge), "View lots" action per project
- `/crm/workbench/lots` — Lot list with status, address, width, size, price, registration date, "New Package" action
- `/crm/workbench/packages` — Package list with status, design name, lot, total price, distribution status
- `/crm/workbench/library` — Construction Library management (CRUD for designs, facades, inclusions, commission templates)
- `/crm/workbench/deals` — My Deals: sales pipeline with commission tracking by stage
- `/crm/workbench/team` — Team/agent management

Portal branding: org logo, colors from `organizations.settings` JSON
Scoped by `organization_id` — builder sees only their org's data
Feature-flagged behind `BuilderPortalFeature` (Pennant)

- [ ] Workbench dashboard loads at `/crm/workbench/` showing stock overview
- [ ] Project list with lot counts and Available/Archived badges
- [ ] Lot list with "New Package" action button per lot
- [ ] Package list with distribution status badges
- [ ] Construction Library CRUD pages for all 4 component types
- [ ] Builder sees only their org's data
- [ ] Feature-flagged behind Pennant

### US-005: Bulk Stock Import

**Status:** todo
**Priority:** 2
**Description:** Bulk import system for lots — drag-drop CSV/Excel files or import from URL. Reliable structured import with validation (NOT unreliable AI parsing — this is our edge over Kenekt).

Migration `create_stock_imports_table`: id, organization_id FK CASCADE, project_id FK SET NULL, file_name VARCHAR, file_path VARCHAR, import_url VARCHAR, status VARCHAR CHECK (pending/parsing/completed/failed) DEFAULT 'pending', total_rows INTEGER, success_count INTEGER DEFAULT 0, error_count INTEGER DEFAULT 0, errors JSONB, imported_by FK SET NULL (users), completed_at TIMESTAMPTZ, timestamps

Import flow:
1. Navigate to project → "Import Lot Lists" section
2. Drag-drop CSV/Excel file OR enter URL → system fetches file
3. System validates against CSV template (downloadable template provided)
4. Column mapping UI if needed (auto-detect common formats)
5. Preview: show parsed rows with validation status per row
6. Confirm → queue `ImportStockJob` → creates project stages + lots
7. Import history table: date, file name, status (Parsing/Completed/Failed), rows, success, errors

API endpoint: `POST /api/crm/inventory/import` accepting JSON or CSV (Sanctum auth)

- [ ] Stock imports table created
- [ ] Drag-drop file upload zone on project page
- [ ] CSV template downloadable
- [ ] Import from URL option
- [ ] Validation with per-row error reporting
- [ ] Import creates project stages and lots
- [ ] Import history with status tracking
- [ ] API endpoint for programmatic import
- [ ] Re-running import is idempotent (update existing lots by lot_number + project)

### US-006: Agent Property Portal

**Status:** todo
**Priority:** 1
**Description:** Create the agent-facing property portal — a separate, clean frontend for referral agents to browse and sell distributed stock.

Portal pages (under `/portal/` route prefix):
- `/portal/properties` — Browse all distributed stock (DataTable with rich filters)
- `/portal/properties/{id}` — Package detail page (lot + design + price + inclusions + brochure download)
- `/portal/my-lists` — Personal shortlists/favorites
- `/portal/saved-searches` — Saved filter criteria with email alert toggle (see US-012)
- `/portal/my-deals` — Sales and commission tracking by construction stage (see US-007)
- `/portal/documents` — Self-service document/collateral library (cascaded from workbench)
- `/portal/enquiry` — General enquiry form to builder/aggregator

Portal layout:
- Uses `<AppSidebarLayout>` with portal-specific nav items
- Header shows agency branding (logo from organization settings)
- Properties use DataTable with all filter capabilities
- Clean, modern UI optimized for non-technical sales agents

Property list features:
- Columns: Lot/Location/Project, Status, Package Price, Property Type, Build Area, Land Price, Land Area, Registration Date, Contract Type, Weekly Rent Est.
- Left sidebar filters: State, Region, Price range, Property Type (House/Duplex/etc.), Category
- Quick search bar + sort options (Price Low-High, etc.)
- Result count display (e.g., "918 results")
- **Export button** — agents export filtered results as **PDF or Excel** (format selection dialog)
- **"Search nearby suburbs" toggle** — proximity-based search expansion

Migration `create_agent_favorites_table`: id, user_id FK CASCADE, package_id FK CASCADE, list_name VARCHAR DEFAULT 'Favorites', created_at, UNIQUE(user_id, package_id, list_name)

**Shareable Package Pages (EPack):**
- "Share" button on package detail generates a clean, branded URL
- Shared pages remove all platform branding — client sees agent's brand only
- Builder details hidden until buyer formally registered (privacy control)

**Structured Enquiry Form:**
Migration `create_enquiries_table`: id, organization_id FK CASCADE, from_user_id FK SET NULL (agent), to_organization_id FK SET NULL (builder org), package_id FK SET NULL, enquiry_type VARCHAR CHECK (buyer_presentation/package_research/general), presentation_date DATE, buyer_has_preapproval BOOLEAN, ownership_type VARCHAR CHECK (investor/owner_occupier), support_requested JSONB (e.g. ["confirming_availability", "request_epack", "commission_terms", "register_eoi"]), subject VARCHAR, message TEXT, status VARCHAR CHECK (pending/responded/closed) DEFAULT 'pending', responded_at TIMESTAMPTZ, timestamps

Access control:
- Agency users → see portal + CRM
- Developer users → see workbench + portal preview
- PIAB users → see everything

- [ ] Portal properties page loads at `/portal/properties` with DataTable
- [ ] Filter by state, region, price range, property type, developer
- [ ] Export button generates CSV of filtered results
- [ ] My Lists: create/manage personal shortlists
- [ ] Documents tab shows cascaded collateral from workbench
- [ ] Structured Enquiry form with type (buyer_presentation/package_research/general)
- [ ] Enquiry captures: presentation date, pre-approval status, ownership type, support needed
- [ ] Share button generates clean branded URL (EPack) — no platform branding visible to clients
- [ ] Enquiry submits to builder org with notification
- [ ] Portal accessible via agency subdomain
- [ ] Agency portal shows stock from all developers they have access to
- [ ] Developer portal preview shows their own stock as agents see it

### US-007: Commission Tracking by Construction Stage

**Status:** todo
**Priority:** 2
**Description:** "My Deals" section showing sales with commission payments broken down by construction stages (Unconditional, Frame, Lock-up, Completion).

Migration `create_sale_commission_payments_table`: id, sale_id FK CASCADE, commission_id FK CASCADE, stage VARCHAR CHECK (unconditional/frame/lockup/completion), amount DECIMAL(12,2) NOT NULL, is_paid BOOLEAN DEFAULT false, paid_at TIMESTAMPTZ, invoice_reference VARCHAR, timestamps

My Deals UI (both workbench `/crm/workbench/deals` and portal `/portal/my-deals`):
- List of all sales with: property (lot + project), total commission, outstanding amount
- Click sale → slide-over showing commission payment breakdown:
  - Unconditional: $X (paid/unpaid)
  - Frame: $X (paid/unpaid)
  - Lock-up: $X (paid/unpaid)
  - Completion: $X (paid/unpaid)
- "Recent Activity" feed per deal
- Total outstanding calculation across all deals

When a sale is created with a commission template that has `stages` JSONB, auto-create `sale_commission_payments` rows splitting the commission by stage percentages.

- [ ] Sale commission payments table created
- [ ] Commission breakdown by 4 construction stages
- [ ] Paid/unpaid status per stage payment
- [ ] Auto-created from commission template stage splits
- [ ] My Deals page in both workbench and portal
- [ ] Total outstanding calculation works correctly

### US-008: Document Cascade & White-Labelling

**Status:** todo
**Priority:** 2
**Description:** Document management with hierarchical cascade inheritance (project → lot → package) and org-level white-labelling.

Document cascade logic:
- Documents attached at project level → automatically available on all child lots and packages
- Documents attached at lot level → available on all packages for that lot
- Documents attached at package level → only on that package
- Uses Spatie Media Library with custom collection names per level
- Query: for a package, merge media from package + lot + project (deduplicate by filename)

White-labelling:
- Org settings store: logo URL, primary color, contact name, contact email, contact phone
- Builder workbench uses org branding throughout
- Agent portal shows agency org branding (header logo, colors)
- Generated brochures include builder branding
- Subdomain: `metricon.fusioncrm.com` (org_domains table already exists)
- Phase 2: CNAME for custom domains (e.g., `partners.metricon.com.au`)

- [ ] Documents cascade from project → lot → package (inheritance query works)
- [ ] Media uploaded at project level appears on child packages
- [ ] No manual duplication needed — true inheritance
- [ ] Org settings store branding fields (logo, colors, contact info)
- [ ] Workbench and portal display org branding
- [ ] Subdomain routing works for developer and agency orgs

---

### TIER 2: High-Impact Differentiators (SHOULD SHIP)

### US-009: Dynamic Brochure Generation

**Status:** todo
**Priority:** 3
**Description:** One-click PDF brochure generation from live package data. Always up-to-date — if price changes, next download reflects new price automatically.

Brochure contents:
- Property hero image (from media)
- Lot details: address, area, width, length, registration status
- House details: property type, floor area, design name, facade name
- Room counts: bedrooms, bathrooms, garages
- Floorplan image
- Pricing: Package Price, Land Price, Build Price
- Featured inclusions (from `is_featured` items)
- Builder branding (logo, contact details from org settings)

Implementation:
- Blade template for brochure layout
- Use starter kit's `GeneratePdf` action + `GeneratePdfJob` for async PDF generation
- Route: `GET /api/crm/packages/{id}/brochure` → returns PDF
- "Download Brochure" button on package detail (both workbench and portal)
- Can be embedded on public websites via API

- [ ] PDF brochure generates from live package data
- [ ] Includes property image, lot details, house details, pricing, inclusions
- [ ] Builder branding on brochure
- [ ] Price changes automatically reflected in next download
- [ ] Download button on package detail (workbench + portal)
- [ ] API endpoint returns PDF for external website integration

### US-010: API / Data Syndication

**Status:** todo
**Priority:** 3
**Description:** Public REST API making Fusion the source of truth for all downstream systems (websites, landing pages, portals, future integrations).

API endpoints (Sanctum auth, scoped per org):
- `GET /api/v1/projects` — list projects with lot counts
- `GET /api/v1/projects/{id}` — project detail with stages
- `GET /api/v1/lots` — list lots with filters (status, project, price range)
- `GET /api/v1/packages` — list published packages with full details
- `GET /api/v1/packages/{id}` — package detail (lot + design + facade + inclusions + pricing + media URLs)
- `GET /api/v1/packages/{id}/brochure` — download PDF brochure
- `POST /api/v1/enquiries` — submit enquiry from external source
- `GET /api/v1/library/designs` — list designs
- `GET /api/v1/library/facades` — list facades

All endpoints:
- Paginated (cursor-based)
- Filterable by standard query params
- Return JSON with media URLs (not binary)
- Rate-limited (60/min default, configurable per org)
- Documented via `dedoc/scramble` auto-generated OpenAPI spec

- [ ] All API endpoints return correct JSON responses
- [ ] Sanctum authentication scoped per org
- [ ] Pagination works on all list endpoints
- [ ] Filters work (status, price range, property type)
- [ ] Media URLs included in responses
- [ ] Rate limiting applied
- [ ] OpenAPI spec auto-generated

### US-011: Property Match Intelligence

**Status:** todo
**Priority:** 3
**Description:** AI-driven buyer-property match scoring exposed on contact and search results.

- [ ] Create `match_scores` table: contact_id, project_id, lot_id, organization_id, score (0-100), factors (jsonb)
- [ ] `CalculateMatchScoreAction`: uses contact preferences + lot attributes to compute match
- [ ] Uses pgvector similarity where embeddings available, else rule-based scoring
- [ ] Show top 5 property matches on contact show page
- [ ] Show top 5 buyer matches on lot show page

### US-012: Saved Searches + Email Alerts

**Status:** todo
**Priority:** 3
**Description:** Agents save search criteria and subscribe to email alerts when new matching stock is distributed.

Migration `create_agent_saved_searches_table`: id, user_id FK CASCADE, organization_id FK CASCADE, name VARCHAR (e.g., "Properties in Blacktown"), search_criteria JSONB (stores filter state: state, region, price_min, price_max, property_type, bedrooms, etc.), email_alerts_enabled BOOLEAN DEFAULT false, alert_frequency VARCHAR CHECK (immediate/daily/weekly) DEFAULT 'daily', last_alerted_at TIMESTAMPTZ, timestamps

Alert flow:
1. Agent saves current filter state with a name
2. Toggles "Email Alerts" on/off per saved search
3. When new packages matching criteria are distributed, `CheckSavedSearchAlertsJob` (scheduled) compares new packages against saved criteria
4. Matching agents receive email notification with new listings
5. "View" button in email → portal filtered to matching results

- [ ] Saved searches table created
- [ ] Agent can save current filter state as named search
- [ ] Email alerts toggle per saved search
- [ ] Scheduled job matches new packages against saved criteria
- [ ] Email notification sent with matching listings
- [ ] "How to use saved searches" help text

### US-013: Package Newsletters

**Status:** todo
**Priority:** 4
**Description:** Send email newsletters about specific packages to distributed agents.

- [ ] "Newsletters" tab on package detail (workbench)
- [ ] Compose email with package details, images, pricing (template-based)
- [ ] Send to all distributed agents or selected agents
- [ ] Track sends/opens in activity log

### US-014: Builder + Project CRM Pipeline

**Status:** todo
**Priority:** 4
**Description:** Pipeline view for builder relationships: contract stages and agent engagement tracking.

- [ ] Inertia page at `/crm/builders/pipeline` showing builder contacts by engagement stage
- [ ] Stages: Prospecting, Contacted, Proposal Sent, Contracted, Active, Churned
- [ ] Cards show: builder name, project count, lot availability, last contact date
- [ ] Track agent engagement: which agents are selling builder's lots

---

### TIER 3: Phase 2 / Nice-to-Have

### US-015: Auto-Validation & MLS Formatting

**Status:** todo
**Priority:** 5
**Description:** Detect and correct incomplete listing data; format for MLS/REA/Domain standards.

- [ ] `ValidateListingAction`: checks project/lot for completeness
- [ ] Validation status badge (green/yellow/red) on listing
- [ ] Block publish if validation fails (configurable)

### US-016: Listing De-Duplication & Versioning

**Status:** todo
**Priority:** 5
**Description:** Prevent listing conflicts and track changes per listing.

- [ ] `listing_versions` table: morphTo listable, version number, snapshot JSONB
- [ ] Auto-create version on update (observer)
- [ ] Duplicate detection on create
- [ ] Version history + revert action

### US-017: AI Push Suggestions

**Status:** todo
**Priority:** 5
**Description:** Suggest optimal time and channel to publish listings using `laravel/ai`.

- [ ] `SuggestPushTimingAction` analyzes engagement data
- [ ] Show suggestion on control panel
- [ ] Dismissible — user can override

### US-018: Compliance Integrations (FIRB/NDIS)

**Status:** todo
**Priority:** 5
**Description:** Auto-check FIRB/NDIS eligibility from external providers (placeholder).

- [ ] Placeholder `FirbCheckAction` and `NdisCheckAction` with documented API integration points
- [ ] Compliance status on contact/sale detail

### US-019: WordPress Plugin / Embeddable Widgets

**Status:** todo
**Priority:** 5
**Description:** Convenience layer on top of API (US-010) for WordPress sites.

- [ ] WP plugin that consumes Fusion API
- [ ] Displays property lists and detail pages on WP site

### US-020: Advanced Analytics

**Status:** todo
**Priority:** 5
**Description:** Builder analytics beyond Kenekt's capabilities.

- [ ] Top-performing agents ranking
- [ ] Most popular designs/facades
- [ ] Sales velocity by estate/project
- [ ] Package conversion rates (views → enquiries → sales)
- [ ] Regional demand heatmaps
