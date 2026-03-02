# Phase 2 Step 1: Current improvements and gaps

**Goal:** Systematically improve existing fleet features and close UX/data gaps so the product feels cohesive and investor-ready. No new domains; focus on polish, consistency, and clear next steps for AI-first behaviour.

**Prerequisites:** Phase 2 overview read; codebase and routes under `fleet` understood.

---

## 1. Fleet Assistant (current)

**What exists:** Conversational assistant with RAG, fleet tools (e.g. vehicles, drivers, trips, alerts), streaming, conversation list (rename, delete), floating FAB on fleet pages, pre-fill from suggestions.

**Improvements:**

- **Tool coverage:** Ensure every major entity the user can ask about has a tool (e.g. work orders, routes, compliance items, defects, service schedules). Add tools in priority order: work orders, compliance, service due, alerts by type.
- **RAG scope:** Confirm document_chunks are populated for key document types (MOT, V5C, insurance, policies) and that the assistant cites sources in answers. Add “Current only” or “Include past” for time-sensitive answers where relevant.
- **Suggestions and entry points:** Keep FAB suggestions; add contextual suggestions on list pages (e.g. “Ask: When is the next service for this vehicle?” on Vehicle show). Optional: quick “Ask assistant” from table row actions.
- **Conversation UX:** Ensure delete uses a proper dialog (no raw `confirm()`); conversation titles stay meaningful after rename; empty state and loading states are clear.
- **Errors and limits:** Handle rate limits and model errors with clear messages and optional retry; show when a tool or RAG call failed so the user is not left with a generic error.

**Done when:** Assistant answers reliably for vehicles, drivers, trips, work orders, and compliance; suggestions and FAB feel consistent; conversation management is clear and robust.

---

## 2. Fleet dashboard (current)

**What exists:** Dashboard with KPIs (vehicles, drivers, trips, work orders), trends (trips last 14 days, work orders created), fleet overview by category, work orders by status, activity cards (recent work orders, defects, compliance at risk, quick links).

**Improvements:**

- **KPI links:** Each KPI card should link to the corresponding list (e.g. Vehicles, Drivers, Trips, Work orders) with sensible default filters if needed.
- **Charts and empty states:** All charts have clear empty states (“No trip data”, “No work orders yet”); consider a single “No data yet” CTA that points to adding vehicles or trips.
- **Activity cards:** Ensure “View all” and primary actions go to the right place; consider adding “Ask assistant” for the same context (e.g. “What’s at risk?” for compliance).
- **Performance:** Dashboard should load with a single or minimal set of queries (e.g. one controller that returns counts, chart data, and activity in one Inertia payload). Lazy-load heavy charts only if needed for a later “advanced analytics” view.
- **Consistency:** Use the same card and chart components and spacing as elsewhere in fleet; sidebar and header match fleet-only layout.

**Done when:** Dashboard loads quickly, every element is actionable or clearly informational, and empty states guide the user.

---

## 3. List and detail pages (current)

**What exists:** Index/Create/Edit/Show for all major entities; Vehicles, Drivers, Routes use view (eye) + three-dot (Edit, Delete); Driver–vehicle assignments use a remove action (fixed 404 via route binding and router.delete).

**Improvements:**

- **View vs Edit:** Apply the same pattern everywhere it makes sense: primary row action = View (eye) to Show page; three-dot = Edit, Delete (and any other actions). Audit: Trips, Work orders, Defects, Compliance, etc. Add Show where missing and ensure list has eye + menu.
- **Tables:** Consistent use of Card + Table (or equivalent), status badges, and row actions. Pagination and filters (e.g. status, date range) where the list is large. Avoid duplicate actions (e.g. two Edit buttons).
- **Forms:** Sectioned forms (Identity, Details, Assignment, etc.) in Cards; shared select/input styling; validation errors surfaced in a clear block or card. Required fields marked; optional fields grouped.
- **Delete:** Always use a confirmation dialog (no raw `confirm()`); for destructive actions use a clear “Delete [entity]?” with the name and optional reason.
- **Empty states:** Every list has an empty state with icon, short copy, and primary CTA (e.g. “Add vehicle”, “Create route”). Optional secondary “Ask assistant” link.

**Done when:** All list pages have a consistent view/edit/delete pattern; forms are sectioned and clear; deletes use a dialog; empty states are consistent.

---

## 4. Navigation and layout (current)

**What exists:** Fleet-only layout; sidebar with Main (Dashboard, Assistant), then sections (Operations, Maintenance, Fuel & energy, Compliance & risk, Setup & locations); duplicate section labels removed; sidebar can be toggled (offcanvas).

**Improvements:**

- **Section grouping:** Ensure items under each section match the label (Operations = vehicles, drivers, assignments, routes, trips; Maintenance = work orders, defects, service schedules, etc.). Move or rename nav items if anything is under the wrong section.
- **Breadcrumbs:** Every fleet page has correct breadcrumbs (Dashboard > Fleet > [Section] > [Resource] > [Detail]). No missing or wrong links.
- **Mobile:** Sidebar toggle and FAB work on small screens; tables or cards adapt (e.g. cards on mobile, table on desktop) where it improves usability.
- **FAB:** Assistant FAB appears on all fleet pages except the assistant page itself; hover state is clear (e.g. scale/shadow). No overlap with critical buttons.

**Done when:** Navigation is logical, breadcrumbs are correct, and layout works on desktop and mobile.

---

## 5. Performance and errors (current)

**Improvements:**

- **N+1:** Audit index and show actions: use `with()` so related data (e.g. vehicle.driver, work_order.vehicle) is loaded in one go. Add eager loading where lists are slow.
- **Route model binding:** All fleet resource routes that use `{id}` or `{model}` have the correct binding registered in AppServiceProvider (or equivalent) so 404 is not returned when the record exists in another org; policy returns 403 instead.
- **Inertia:** Shared data (e.g. flash, user, org) provided consistently; validation errors returned as 422 with Inertia share; no full-page reloads for forms where avoidable.
- **Logging:** Assistant and AI job failures logged with enough context (org, user, prompt/job type) for debugging; no sensitive data in logs.

**Done when:** No N+1 on main list/show pages; 403/404 behaviour is correct; Inertia and errors feel consistent.

---

## 6. Identified gaps (short list)

- **Proactive AI:** Assistant is reactive only. Phase 2-02 adds proactive insights (e.g. “3 vehicles with MOT due in 14 days”, “Unacknowledged alerts”).
- **Natural language everywhere:** Beyond the assistant page, no “Ask about this” from entity pages. Phase 2-02 adds contextual “Ask assistant” with pre-filled context.
- **Unified reporting:** Reports exist but may not be surfaced as a single “Reporting” story (saved reports, run history, download). Phase 2-03 can group and extend.
- **Electrification and optimization:** Electrification plan and fleet optimization exist; ensure they are visible in nav and dashboard and that results are clearly presented. Phase 2-04 polishes.
- **Investor storyline:** No single narrative or one-pager yet. Phase 2-05 adds demo narrative and key metrics.

---

## 7. Done when (this step)

- All improvements above are implemented or ticketed with clear owners.
- No duplicate Edit actions; view (eye) + three-dot pattern applied consistently.
- Assistant has sufficient tools and RAG; conversations and FAB are polished.
- Dashboard is fast and actionable; list/detail and navigation are consistent.
- Route bindings and error handling are correct; N+1 addressed on critical paths.

Proceed to **phase-2-02** (`phase-2-02-ai-first-assistant-and-insights.md`) for AI-first enhancements.
