# PRD 08: Reporting and Performance Dashboard

## Overview

Create CRM dashboards and performance widgets using the starter kit's dashboard builder (Puck), add CRM data sources, build per-org performance widgets, implement shared listings marketplace view, and replace any raw SQL with Eloquent. **Prerequisites:** PRD 07 must be complete — AI tools working, all CRM data verified, embeddings generated.

**CRITICAL: Never touch `sites/default/sqlconf.php`. Never commit without human approval.**

## Technical Context

- **Module path:** `modules/module-crm/` — namespace `Cogneiss\ModuleCrm`
- **Companion docs:** `plan.md` (Step 7), `dbissues.md` (Section 8 — raw SQL to replace)
- **Dashboard system (pre-built):** `modules/dashboards/` with Puck builder. `DashboardBuilderController` + `DashboardDataSourceRegistry` exist. Puck renders dashboard blocks.
- **Reports system (pre-built):** `modules/reports/` with Puck builder. Export via `maatwebsite/excel` (installed).
- **Key packages:** `maatwebsite/excel` (exports), `akaunting/laravel-money` (commission display)
- **Visibility rules:** PIAB org projects visible to ALL subscribers. Query: `WHERE organization_id = PIAB_ORG OR organization_id = current_org`.
- **ZERO raw SQL rule:** No `DB::raw()`, `selectRaw()`, `whereRaw()`, `orderByRaw()` in module-crm. Use Eloquent, PostgreSQL earthdistance extension for geographic queries, ILIKE for case-insensitive search.

### Required Environment Variables
Before starting this PRD, verify these are set in `.env`:
- `DB_CONNECTION=pgsql` — PostgreSQL connection (set in preflight)

No special API keys required for this PRD.

**If any required key is missing, ASK the user before proceeding. Do not skip, stub, or use placeholder values.**

## User Stories

### US-001: Reporting and Performance Dashboard

**Status:** todo
**Priority:** 1
**Description:** Add CRM data sources to the dashboard builder, create per-org performance widgets, implement shared listings marketplace view, add CRM report types with export, and ensure zero raw SQL.

**Dashboard Data Sources** — Register CRM data sources with `DashboardDataSourceRegistry`:
- Contact pipeline: contacts by stage, conversion funnel
- Sales: sales by status, total volume, average purchase price
- Lots: availability summary by project, stage distribution
- Commissions: total earned, breakdown by type, tier progress

**Performance Widgets** (Inertia React components, per-org scoped):
- **Sales Count**: Trailing 30/90/365 days, with trend indicator
- **Conversion Rate**: Contacts -> Sales ratio, with period comparison
- **Commission Earned**: Total + by tier + by type, using `akaunting/money` for display
- **Active Leads Count**: Contacts in active stages (not closed/lost)
- **Lot Availability Summary**: Available/reserved/sold counts per project

All widgets respect `OrganizationScope` — each org sees only their own data, except shared PIAB listings.

**Shared Listings Marketplace View:**
- PIAB org projects visible in every subscriber's dashboard and project list
- Query pattern: `WHERE organization_id = config('sync.piab_organization_id') OR organization_id = current_user_org_id`
- Any subscriber can sell lots from shared projects
- Visual distinction (badge/tag) between shared marketplace listings and org-owned projects

**CRM Report Types** — Register with reports module:
- Sales report: filterable by date range, org, status, project
- Commission report: filterable by date range, org, type
- Contact report: filterable by stage, origin, source
- Export all reports via `maatwebsite/excel`

**Replace v3 Raw SQL** (from `dbissues.md` Section 8):
- Geographic queries: Use PostgreSQL `earthdistance` extension via Eloquent
- Aggregate queries: Use Eloquent with parameter binding
- Case-insensitive search: Use `ILIKE` operator via `where('column', 'ILIKE', '%term%')`
- Ensure zero `DB::raw()` anywhere in `modules/module-crm/src/`

- [ ] Dashboard loads at `/dashboard` (200)
- [ ] CRM data sources registered with DashboardDataSourceRegistry
- [ ] Performance widgets show data: `Sale::where('created_at','>=',now()->subDays(365))->count()` > 0
- [ ] Widgets scoped per organization (each org sees own data)
- [ ] Shared listings query works: `Project::where('organization_id', config('sync.piab_organization_id'))->orWhere('organization_id', $orgId)->count()` >= 15,481
- [ ] Shared vs own projects visually distinguished with badge
- [ ] Reports page loads at `/reports` (200)
- [ ] Export works: `class_exists(Maatwebsite\Excel\Facades\Excel::class)` = true
- [ ] Sales report exportable with date/org/status filters
- [ ] Commission report exportable with date/org/type filters
- [ ] Contact report exportable with stage/origin/source filters
- [ ] **ZERO raw SQL:** `grep -rn 'DB::raw\|selectRaw\|whereRaw\|orderByRaw' modules/module-crm/src/` returns 0 matches
- [ ] Geographic queries use earthdistance extension (not raw SQL)
- [ ] Case-insensitive search uses ILIKE (not raw SQL)
