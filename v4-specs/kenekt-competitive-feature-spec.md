# Kenekt Competitive Feature Spec — Fusion v4 Builder Portal

> **Purpose**: Complete feature parity checklist derived from Kenekt platform demo + feature analysis documents.
> **Date**: 2026-03-25
> **Source docs**: `KENEKT PLATFORM DEMONSTRATION.pdf`, `Kenekt Platform: Features List with Video Timestamps.pdf`
> **Status**: Approved for PRD development

---

## Kenekt Overview

- **What**: B2B platform for new home sales — stock management + distribution to agent networks
- **Two systems**: Stock Management Workbench (backend for builders) + Private Agent Portal (frontend for agents)
- **Target users**: Small-to-medium builders, aggregators, referral agents/marketers
- **Pricing**: LITE $799/mo (<20 agents), STANDARD $990/mo (unlimited agents) + $1,500 setup + marketplace commission
- **Core problem solved**: Packaging land with home designs and distributing to sales agents (replaces spreadsheets/email PDFs)
- **Key differentiators**: Construction Library ("recipe book"), AI PDF import, dynamic brochures, white-labelling, channel partner network

---

## Kenekt Scale (Dec 2025)

- 50+ active builder/developer suppliers
- ~1,020 packages nationally: VIC (~450), QLD (~250), NSW (~200), SA (~100), WA (~10), TAS (~10)
- Aiming to double supplier count and stock in 2026
- Small team: 6 people (1 Business Dev, 2 BDMs, 1 Ops, 1 Tech, 1 Admin)
- Only 30% of builders use Kenekt's own Stock Management System; others use Salesforce etc. or AI doc extraction
- Revenue model: SaaS ($799-990/mo for builders) + commission splits (0-70% of referral fee depending on support tier)

## Kenekt Commission Structures (Agent-side)

Kenekt offers 4 support tiers for referral agents, based on how much support Kenekt provides:

| Tier | Kenekt's Role | Kenekt Share | Agent Keeps | Example ($33K fee) |
|------|---------------|-------------|-------------|-------------------|
| **Standard** | None — agent manages buyer independently | 0% | 100% ($33,000) | Agent handles everything |
| **Deal Support** (30%) | Kenekt assists in closing after agent intro | 30% | 70% ($23,100) | Kenekt gets $9,900 |
| **Discovery & Presentation** (50%) | Both manage buyer journey together | 50% | 50% ($16,500) | Kenekt gets $16,500 |
| **Lead Management** (70%) | Kenekt manages buyer end-to-end | 70% | 30% ($9,900) | Kenekt gets $23,100 |

**Minimum requirements:** Referrers = 1 unconditional deal per quarter. Agencies = 1 unconditional deal per month.

## Kenekt Weaknesses (PIAB Advantages)

| Weakness | PIAB Advantage |
|----------|---------------|
| No CRM functionality | Full Fusion CRM with pipeline, stages, follow-ups |
| No lead management | Contact management, lead scoring, agent communication |
| No site siting (house-fits-lot validation) | Can be built as AI-powered feature |
| No marketing systems | Marketing infrastructure, mail lists, Growth Academy |
| No sales analytics | Performance dashboard, top agents, sales velocity |
| Unreliable AI PDF parsing ("variable") | Reliable structured bulk import (CSV templates + validation) |
| Dated/inconsistent UI/UX | Modern Inertia + React with consistent design system |
| Basic commission tracking only | Full pipeline tracking, commission tiers, stages |

---

## Feature Spec: MUST BUILD (Phase 1 — Kenekt Parity)

### F1: Stock Management Workbench

- **Kenekt**: Central backend at `app.kenekt.com.au` for builders/aggregators
- **What to build**: Dedicated builder-facing UI within Fusion (org type `developer`)
- **Key capabilities**:
  - Manage inventory (projects, lots, packages)
  - Create and configure packages
  - Control distribution to agents
  - View deals and commissions
- **Architecture**: Must be a **separate experience** from the Agent Portal — different UX optimized for stock management vs. stock consumption
- **PIAB mapping**: Filament admin panel for `developer` org type, or dedicated Inertia pages scoped to builder role

### F2: Lot/Land Management

- **Kenekt**: Table of lots with status, address, width, size, title status, action buttons
- **What to build**:
  - Lot CRUD with fields: lot number, address, land price, land size (width/depth/area), land registration date, status (Available/Archived/Sold)
  - Lots organized by **Project Stages** (e.g., "Stage 1 — 16 lots — Registration Jan-Mar 2027")
  - "View Lots" per project with Available/Archived counts shown as badges
  - Action buttons: "New Package" directly from lot row
- **PIAB mapping**: `lots` table exists in schema — ensure stage grouping and registration date fields

### F3: Construction Library (Core System)

- **Kenekt**: Reusable "recipe book" — the **engine of package creation**
- **What to build**: Relational database of reusable building components, NOT a media folder
- **Library categories** (each is a separate manageable entity):
  1. **Floor Plan Designs** — design name, width/area, rooms (bed/bath/garage), property type (House/Duplex/etc.), price, range/brand
  2. **Facades** — facade name, colour scheme, elevation, storey type, price
  3. **Inclusions / Site Improvements** — inclusion packages with named tiers, individual line items, "featured items" subset for flyers
  4. **Sales Commissions** — reusable commission rate templates (%, flat, tiered)
  5. **Agents** — agent profiles linked to library for assignment
- **Relational linking**: design -> facade -> inclusions (mix and match)
- **Pricing logic at each level**: design price + facade price + land price = package price (auto-calculated)
- **Reuse**: Upload once, use across many packages and projects
- **PIAB mapping**: New tables needed — `construction_designs`, `construction_facades`, `construction_inclusions`, `construction_inclusion_items`, `commission_templates`

### F4: Package Creation & Management

- **Kenekt**: Combine lot + library components = sellable H&L package
- **What to build**:
  - Package creation wizard: Select lot -> Select design from library -> Select facade -> Select inclusions -> Auto-calculate total price
  - A single lot can have **multiple packages** (different home options)
  - Package detail with **7 tabs**: Design/Floorplan, Details, Inclusions, Media, Distribution & Docs, Newsletters, Summary
  - **Status management**: Available/Sold/etc. dropdown — controls portal visibility
  - **Preview button**: Builder sees exactly what agents see before distributing
  - Dynamic price: land price + build price + facade price = total (auto-updated when components change)
- **PIAB mapping**: `packages` table (new) linking to `lots`, `construction_designs`, `construction_facades`, `construction_inclusions`

### F5: Package Inclusion Variations

- **Kenekt**: "Package Inclusions & Variations" with multiple tiers and "Featured Items"
- **What to build**:
  - Multiple inclusion packages per property package (standard, premium, etc.)
  - "Featured Items" flag on individual inclusions — these appear on flyers/brochures
  - Warning when fewer than 15 featured inclusions selected for flyer
  - "Add custom inclusion package" option for one-off packages
- **PIAB mapping**: `package_inclusions` pivot with `is_featured` flag

### F6: Document Management with Cascade Inheritance

- **Kenekt**: Upload docs at Estate/Project/Lot/Package level; higher-level docs cascade down
- **What to build**:
  - Document upload at 4 levels: Project (Estate) -> Stage -> Lot -> Package
  - **True inheritance**: Documents added at project level automatically available on all child lots and packages
  - Document types: Floor Plans, Internal Renders, Documents (PDFs), Marketing Collateral
  - Drag-and-drop upload areas per category
  - Central filing cabinet — no need to attach same doc to hundreds of packages
- **PIAB mapping**: Spatie Media Library with polymorphic `mediable` + inheritance query logic

### F7: Private Agent Portal

- **Kenekt**: White-labelled frontend at `partners.[brand].com.au` for agents
- **What to build**: Separate, clean frontend experience for referral agents
- **Portal navigation tabs**:
  1. **Properties** — searchable/filterable stock list
  2. **My Lists** — personal shortlists/favorites
  3. **Saved Searches** — saved filter criteria with email alert toggle
  4. **My Deals** — sales and commission tracking
  5. **Documents** — self-service collateral library
  6. **General Enquiry** — contact form to builder/aggregator
- **Key UX requirements**:
  - Clean, modern UI (Kenekt's portal is more modern than their workbench)
  - Fast search with filters: State, Region, Price, Property Type, Category
  - Sort options (Price Low-High, etc.)
  - Must be simple enough for non-technical sales agents
- **PIAB mapping**: Separate Inertia route group `/portal/*` with agent auth, scoped to distributed packages only

### F8: Distribution & Allocation Control

- **Kenekt**: "Open Distribution" (all agents) or exclusive allocation to specific agents
- **What to build**:
  - Per-package distribution toggle: Open (all agents see it) vs. Exclusive (selected agents only)
  - "Allocations" modal: "Distribute to all" button + agent search for exclusive allocation
  - "Clear All" to remove all allocations
  - Distribution status visible on package list
  - Agents only see packages distributed to them in their portal
- **PIAB mapping**: `package_distributions` pivot table (package_id, agent_user_id, distribution_type: open/exclusive)

### F9: Agent Portal — Property Search + Filters + Export

- **Kenekt**: Filterable stock list with rich columns and export button
- **What to build**:
  - Searchable table with columns: Lot/Location/Project, Status, Package Price, Property Type, Build Price, Land Area, Land Price, Registration Date, Contract Type, Weekly Rent Est.
  - Left sidebar filters: State, Region, Price range, Property Type (House/Duplex/etc.), Category (Completed Homes, Featured)
  - Quick search bar
  - **Export button** — agents export filtered results to CSV/Excel
  - Result count display (e.g., "918 results")
- **PIAB mapping**: DataTable pattern with agent-scoped query

### F10: Agent Portal — My Lists (Personal Shortlists)

- **Kenekt**: "My Lists" tab in portal nav — agents save favorite packages
- **What to build**:
  - Agents can add/remove packages to personal lists
  - Named lists (optional) or single favorites list
  - Quick access from portal nav
- **PIAB mapping**: `agent_favorites` table (user_id, package_id)

### F11: Agent Portal — My Deals (Commission by Construction Stage)

- **Kenekt**: "My Deals" dashboard with sales, commission, and payment stages
- **What to build**:
  - List of all agent's sales with: property, total commission, outstanding amount
  - Commission payment breakdown by **construction stages**:
    - Unconditional (paid/unpaid + amount)
    - Frame (paid/unpaid + amount)
    - Lock-up (paid/unpaid + amount)
    - Completion (paid/unpaid + amount)
  - "Recent Activity" feed per deal
  - Total outstanding calculation
- **PIAB mapping**: `sales` table already has commission fields — add `sale_commission_payments` table with stage-based tracking

### F12: Agent Portal — Documents (Self-Service Collateral)

- **Kenekt**: "Documents" tab in portal nav
- **What to build**:
  - Agents browse/download marketing collateral, contracts, brochures
  - Cascaded from workbench document management
  - Filterable by project/package
- **PIAB mapping**: Read-only view of cascaded Spatie media for distributed packages

### F13: Agent Portal — General Enquiry

- **Kenekt**: "General Enquiry" tab in portal nav
- **What to build**:
  - Contact form for agents to submit questions/enquiries to builder/aggregator
  - Enquiry lands in builder's workbench as a notification/ticket
- **PIAB mapping**: Simple form -> `enquiries` table + notification to org admin

### F14: Bulk Stock Import

- **Kenekt**: AI-powered PDF/Excel drag-drop + "Import from URL"
- **What to build** (strategic opportunity — be MORE reliable):
  - Drag-and-drop zone for CSV/Excel files
  - **Import from URL** option (fetch price list from remote URL)
  - **CSV templates** provided (downloadable) for standardized format
  - **Guided import flow** with column mapping
  - **Validation** with error reporting (rows/success/errors counts)
  - Status tracking: Parsing -> Completed
  - Auto-creates project stages and lots from imported data
  - Import history with date, file name, status, row counts
- **PIAB edge**: Reliability over AI gimmicks. Kenekt's AI parsing is "variable" — structured import with validation is more trustworthy for production use
- **PIAB mapping**: `fusion:import-stock` command + web UI equivalent

### F15: Dynamic Brochure Generation

- **Kenekt**: "Download Brochure" button generates branded multi-page PDF on-the-fly
- **What to build**:
  - One-click PDF generation from package data (live, always up-to-date)
  - Brochure includes: property image, lot details (area, width, length, registration), house details (type, area, design, facade), pricing (package/land/build), room counts, floorplan
  - **Builder + agent branding** on generated brochure
  - Can be triggered from agent portal OR embedded on public website
  - If price changes in backend, next brochure download reflects new price automatically
- **PIAB mapping**: Existing Flyers page concept — enhance to be data-driven PDF generation (e.g., `laravel-dompdf` or `browsershot`)

### F16: API / Data Syndication

- **Kenekt**: Full REST API powering public websites (e.g., Winton Homes example)
- **What to build**:
  - Public REST API: projects, lots, packages, designs, facades, media
  - API-first architecture: **Fusion = source of truth, everything else pulls from it**
  - Powers: builder public websites, landing pages, agent portals, future integrations
  - Authenticated endpoints for builder data, public endpoints for published stock
  - Example: Builder's WordPress site displays live stock synced from Fusion API
- **PIAB mapping**: Laravel API routes + Sanctum tokens, scoped per organization

---

## Feature Spec: SHOULD BUILD (High Impact)

### F17: Saved Searches + Email Alerts

- **Kenekt**: Agents save search criteria, enable "Email Alerts" for new matching stock
- **What to build**:
  - Save current filter state as named search (e.g., "Properties in Blacktown")
  - Toggle email alerts per saved search
  - When new packages matching criteria are distributed, agent gets email notification
  - "How to use saved searches" help link
  - Delete saved searches
- **High engagement impact** — keeps agents coming back without manual checking

### F18: Package Newsletters

- **Kenekt**: "Newsletters" tab on package detail
- **What to build**:
  - Send email newsletter about specific packages to distributed agents
  - Template-based email with package details, images, pricing
  - Track sends/opens

### F19: Package Preview

- **Kenekt**: "Preview" button on package detail
- **What to build**:
  - Builder clicks "Preview" to see exact agent portal view of the package
  - Ensures quality before distribution

### F20: White-Labelling

- **Kenekt**: Full custom domain (CNAME) + branding (logo, colors) — "Kenekt" brand invisible
- **What to build (Phase 1)**:
  - Organization branding: logo, primary color, org name
  - Subdomain: `metricon.fusioncrm.com` (already in v4 architecture)
- **Phase 2**:
  - Custom domain via CNAME (e.g., `partners.metricon.com.au`)
  - Full brand invisibility (no Fusion branding visible to agents)

---

## Feature Spec: PHASE 2 (Optional)

### F21: WordPress Plugin / Embeddable Widgets

- **Kenekt**: WP plugin to display stock on builder websites
- **Lower priority** — API (F16) covers this use case. Plugin is convenience layer.

### F22: Advanced Analytics

- **Kenekt weakness**: No analytics beyond deal list
- **PIAB opportunity**:
  - Top-performing agents
  - Most popular designs/facades
  - Sales velocity by estate/project
  - Package conversion rates
  - Regional demand heatmaps

---

## Additional Features (from Agent Brochure — Dec 2025)

### F16b: Structured Enquiry Processing

- **Kenekt**: Structured enquiry form with buyer presentation vs package research, presentation date, pre-approval status, ownership type (Investor/Owner-Occupier), support type checkboxes
- **What to build**: Structured `enquiries` table with `enquiry_type`, `presentation_date`, `buyer_has_preapproval`, `ownership_type`, `support_requested` JSONB
- **Added to**: PRD 12 US-006

### F16c: Shareable Package Pages (EPack)

- **Kenekt**: "Share" button generates clean URL. Builder details hidden until buyer formally registered. All platform branding removed — client sees agent brand only.
- **What to build**: Public shareable package URL with agent branding, no platform chrome. Builder details gated behind registration.
- **Added to**: PRD 12 US-006

### F16d: Export as PDF or Excel

- **Kenekt**: Export stocklist dialog with "Export as PDF" or "Export as Excel" buttons. Can export full list or filtered segments.
- **What to build**: Format selection dialog on export (PDF + Excel), not just CSV
- **Added to**: PRD 12 US-006

### F16e: 3 Integration Pathways for Agencies

- **Kenekt**: Subdomain (quickest, DNS only) | Embed into existing site | Full API integration
- **What to build**: Document all 3 pathways in API docs. Subdomain already planned. Embed = JS widget (Phase 2). API = US-010.

### F16f: Rent Estimation & Yield

- **Kenekt**: Uses SQM Research data for rent estimates. Cross-refs with REA. 3-bed baseline + 4-bed formula. Weekly rent + yield % shown in stock list.
- **What to build**: `weekly_rent_est` and `yield_est` columns on packages table (or computed). Show in portal stock list.
- **Schema impact**: Add `weekly_rent_est DECIMAL(8,2)` and `gross_yield_pct DECIMAL(5,2)` to `packages` table

---

## Workflows (from Kenekt Demo)

### W1: Onboarding New Land Stock
1. Navigate to Projects
2. Create New Development (high-level bucket = suburb/estate)
3. Drag-drop developer's PDF/Excel price list into Import area (or use CSV template)
4. System parses and creates project stages + individual lots
5. Review and verify imported data

### W2: Creating House & Land Packages
1. Go to Lots list
2. Find available lot
3. Click "New Package"
4. Select design/floorplan from Construction Library
5. Select facade from library
6. Select inclusions package from library
7. System auto-calculates: land price + build price + facade price = total
8. Package created — ready for distribution

### W3: Distributing Packages to Agents
1. Open package
2. Go to "Distribution & Docs" tab
3. Click "Open Distribution" to make available to all agents, OR search for specific agents for exclusive allocation
4. Package is now live in allocated agents' Private Portal

### W4: The Deal Process (from Agent Brochure)
1. **Research & Enquire** — Agent browses stock, creates branded brochures, submits structured enquiry. Platform confirms availability, commissions, and provides full Property EPack within 4 hours.
2. **Buyer Presentation** — Agent presents EPack to buyer. BDM supports with feedback, repositioning, deal guidance. Builder details hidden until buyer formally registered.
3. **Submit EOI** — Agent submits completed EOI form + buyer IDs. May require EOI deposit + finance approval letter. BDM confirms lot secured with developer.
4. **Contract & Signing** — BDMs manage contract queries. Contracts issued to buyers. Agent coordinates timely execution.
5. **Unconditional Stage** — Agent notifies once finance conditions met + supplies unconditional approval letter. Commission released. Platform tracks build progress (Frame, Lock-up, Completion).

### W5: Agent Browsing and Selling
1. Agent logs into Private Portal (white-labelled)
2. Searches/filters available stock
3. Adds favorites to "My Lists"
4. Saves search criteria with email alerts
5. Downloads brochure for client
6. Submits enquiry or records sale
7. Tracks commission in "My Deals"

---

## Pricing Strategy (PIAB vs Kenekt)

| | Kenekt | PIAB Strategy |
|---|---|---|
| **Price** | $799-990/mo + $1,500 setup | **FREE** (strategic move to capture supply) |
| **Model** | SaaS subscription + marketplace commission | Free portal as part of Fusion ecosystem |
| **Lock-in** | No lock-in contracts | N/A — free |
| **Value prop** | "Out of the box" stock management | Everything Kenekt does + full CRM + marketing + analytics |

---

## Data Model Summary (New Tables Needed)

```
construction_designs        — Floor plan designs (library)
construction_facades        — Facade options (library)
construction_inclusions     — Inclusion packages (library)
construction_inclusion_items — Individual inclusion line items
commission_templates        — Reusable commission rate structures
packages                    — H&L packages (lot + design + facade + inclusions)
package_inclusions          — Package <-> inclusion pivot with is_featured
package_distributions       — Package <-> agent distribution control
agent_favorites             — Agent personal shortlists
agent_saved_searches        — Saved filter criteria + email alert flag
sale_commission_payments    — Commission payments by construction stage
enquiries                   — Agent -> builder enquiry messages
stock_imports               — Import history tracking
```

---

## Next Steps

All completed (2026-03-25):

- [x] Review and approve this feature spec
- [x] PRD 12 rewritten with 20 user stories, 3 tiers (Kenekt parity in Tier 1)
- [x] `v4/schema/newdb.md` updated with 12 new tables (§3.31-3.42)
- [x] Phase 1 = Builder Portal (ships first). See `2026-03-25-builder-portal-fast-track-design.md`
- [x] `v4/plan/plan.md` restructured: Phase 1 (builder) → Phase 2 (subscriber migration) → Phase 3 (advanced)
