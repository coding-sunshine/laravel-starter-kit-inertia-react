# UI Step 7: UX, charting, and reporting

**Goal:** Ensure the app has **good UX** – everything is logically connected, smooth, and predictable (navigation, flows, feedback). Add **charting** (e.g. Recharts) for dashboards and data views, and **reporting** UI so fleet reports and report executions are clear and usable. Document UX, charting, and reporting references.

**References:** See § References at the end of this document.

---

## 1. Prerequisites

- UI-01 through UI-06 in place (design system, auth, shell, Fleet dashboard, maps, animations, responsive).
- Existing: main `dashboard.tsx` uses Recharts (AreaChart); Fleet has `reports`, `report-executions` routes and models; Fleet dashboard shows report counts and links.

---

## 2. UX principles: logical, connected, smooth

### 2.1 Information architecture and hierarchy

- **Clear entry and home:** Login → main dashboard or Fleet dashboard; user always knows “where I am” (breadcrumbs, page title, active nav).
- **Consistent navigation:** One sidebar with Platform + expandable Fleet; same pattern on every page. Back/context: breadcrumbs (Dashboard > Fleet > Workflow definitions) so users can go up one level or to Fleet home without using browser back.
- **Predictable patterns:** List → Show → Edit; Index has “New” → Create; Show has “Edit” and optional “Delete”. Use same button placement (e.g. primary CTA top-right on index pages) and same table actions (View, Edit, Delete) across fleet resources.
- **References:** [Nielsen Norman Group – Information Architecture](https://www.nngroup.com/articles/information-architecture/), [NNG – Navigation](https://www.nngroup.com/articles/navigation-ia-tests/).

### 2.2 User flows and task completion

- **Critical flows:** Log in → switch org (if needed) → open Fleet → go to Vehicles / Work orders / Alerts / Workflow definitions. Each flow should be short (few clicks) and obvious. Sidebar Fleet expandable gives direct access; avoid “nested only” entry points.
- **Feedback:** After create/update/delete, show success toast (Sonner) and optional redirect. Inline validation on forms; clear error messages. Loading states for async actions (e.g. “Run workflow”, “Generate report”) so the user knows the system is working.
- **Empty and error states:** Empty list: “No X yet” + CTA “Create X”. Error page: clear message + link back to safe place (e.g. Fleet dashboard). [NNG – Empty States](https://www.nngroup.com/articles/empty-states/).
- **References:** [NNG – Usability](https://www.nngroup.com/articles/usa/), [NNG – Task Completion](https://www.nngroup.com/articles/task-completion/).

### 2.3 Consistency and cognitive load

- **Terminology:** Use the same labels app-wide (e.g. “Workflow definitions” not “Workflows” in one place and “Definitions” elsewhere). Fleet entity names match sidebar and breadcrumbs.
- **Visual consistency:** Same card style, table style, and button hierarchy on list/show/edit across fleet. Design tokens (UI-01) enforce this.
- **Reduced clutter:** One primary action per screen or card; secondary actions (Edit, Delete) available but not competing. [NNG – Consistency](https://www.nngroup.com/articles/consistency-in-ux/).

### 2.4 Accessibility and inclusivity (UX aspect)

- **Keyboard and focus:** All interactive elements reachable and focusable; focus order logical; focus visible (design system focus ring). Skip link for main content where helpful.
- **Screen readers:** Labels, headings, and live regions so state changes (e.g. toast, modal) are announced. Tables have proper headers.
- **References:** [WCAG 2.1 Quick Reference](https://www.w3.org/WAI/WCAG21/quickref/), [NNG – Accessibility](https://www.nngroup.com/articles/accessibility/).

### 2.5 Smoothness and perceived performance

- **Inertia + animations:** Page changes feel smooth (UI-06 page transition); list/card enter animations make the app feel responsive. Avoid full-page white flash.
- **Optimistic UI (where safe):** For simple toggles or quick actions, consider optimistic update with rollback on error. For destructive or irreversible actions, keep confirmation.
- **References:** [NNG – Response Times](https://www.nngroup.com/articles/response-times-3-important-limits/), [NNG – Perceived Performance](https://www.nngroup.com/articles/website-response-times/).

---

## 3. Charting (Recharts and data viz)

### 3.1 Library and stack

- **Recharts** is already in the project (`recharts` in package.json). Use it for line, bar, area, and pie charts. [Recharts](https://recharts.org/), [Recharts – Examples](https://recharts.org/en-US/examples).
- **Theming:** Use design tokens – primary #4348be for lines/bars, #333333 for text and axes; white or light grey background. Keep charts readable and consistent with the rest of the UI.
- **Responsive:** Recharts `ResponsiveContainer` so charts scale with layout; on mobile, simplify (fewer ticks, or single series) if needed.

### 3.2 Where to use charts

| Location | Chart type | Data / purpose |
|----------|------------|----------------|
| **Main dashboard** | Area/line or bar | Platform-level metrics (already in `dashboard.tsx`). |
| **Fleet dashboard** | Bar or small sparklines | Optional: trend of vehicles, trips, or fuel over time; or keep count cards only and add charts in a “Trends” section. |
| **Fleet optimization result** | Bar/line | Cost or utilization comparison (e.g. by vehicle or period). |
| **Electrification plan result** | Bar or table + chart | EV readiness or TCO by vehicle/depot. |
| **AI analysis / job runs** | Bar or list with metrics | Summary of findings (e.g. risk distribution, compliance scores). |
| **Reports** | Depends on report type | Fuel efficiency, utilization, cost – embed chart in report view or in report execution result. |

### 3.3 Chart UX best practices

- **Title and legend:** Every chart has a clear title; multiple series need a legend. Axis labels and units (e.g. “Miles”, “£”) visible.
- **Tooltip:** Hover shows exact values; use Recharts `Tooltip` with consistent formatting.
- **Empty state:** No data → “No data for this period” or similar; don’t render an empty chart.
- **References:** [Recharts – API](https://recharts.org/en-US/api), [Storybook Recharts](https://recharts.org/en-US/examples) for patterns; [NNG – Data Visualization](https://www.nngroup.com/articles/data-visualization/).

---

## 4. Reporting UI

### 4.1 Existing backend and routes

- **Models:** `Report`, `ReportExecution` (Fleet). Report has name, description, report_type, format (e.g. excel), schedule, etc. ReportExecution stores run result and status.
- **Routes:** `fleet.reports` (resource: index, create, store, show, edit, update, destroy), `fleet.reports.run`, `fleet.report-executions` (index, show).
- **Pages:** `Fleet/Reports/Index.tsx`, `Fleet/Reports/Show.tsx`, etc.; `Fleet/ReportExecutions/Index.tsx`, `Fleet/ReportExecutions/Show.tsx`.

### 4.2 Reporting UX

- **List (Reports index):** Table or card list: name, type, format, schedule, last run (if available), “Run” action. Clear “Create report” CTA. Breadcrumb: Dashboard > Fleet > Reports.
- **Report show:** Report details; “Run report” button that POSTs to `reports/{id}/run`; show recent executions (link to report-executions show). If report type supports preview, show summary or chart (see § 3).
- **Report execution show:** Status (pending, running, completed, failed), start/end time, triggered by; link to download if result is a file (e.g. Excel). Clear “Back to report” or “Back to executions”.
- **Run flow:** Click “Run” → loading state → success toast + redirect to execution show or poll for completion. Error: toast + inline message.
- **References:** [NNG – Report Design](https://www.nngroup.com/articles/report-design/), [Baymard – Tables](https://baymard.com/blog/tables) for report/execution lists.

### 4.3 Export and formats

- If reports generate Excel/PDF, provide a clear “Download” button on the report execution show page. Use design system primary button; show file name or “Preparing…” while generating.

---

## 5. Implementation checklist

- [x] Audit key user flows (login → Fleet → entity list → show → edit); ensure breadcrumbs and sidebar match; fix any dead ends or inconsistent labels.
- [x] Ensure every list has empty state + CTA; every async action has loading and success/error feedback.
- [x] Add or refine charts on main dashboard and Fleet dashboard (Recharts, design tokens); add charts to Fleet optimization / electrification result views if data supports it.
- [x] Align report and report-execution pages with design system; clear “Run” and “Download”; optional chart/summary on report or execution show.
- [x] Document UX decisions (IA, terminology, patterns) in DESIGN_SYSTEM.md or a short UX_NOTES.md; link references below.

---

## 6. References (consolidated)

### UX (usability, IA, flows)

| Resource | URL | Use |
|----------|-----|-----|
| Nielsen Norman Group – Information Architecture | https://www.nngroup.com/articles/information-architecture/ | IA, structure. |
| NNG – Navigation | https://www.nngroup.com/articles/navigation-ia-tests/ | Nav patterns. |
| NNG – Empty States | https://www.nngroup.com/articles/empty-states/ | Empty list and error states. |
| NNG – Usability 101 | https://www.nngroup.com/articles/usability-101-introduction-to-usability/ | Core usability. |
| NNG – Task Completion | https://www.nngroup.com/articles/task-completion/ | Task-focused design. |
| NNG – Consistency | https://www.nngroup.com/articles/consistency-in-ux/ | Consistency. |
| NNG – Accessibility | https://www.nngroup.com/articles/accessibility/ | UX and a11y. |
| NNG – Response Times | https://www.nngroup.com/articles/response-times-3-important-limits/ | Feedback and delay. |
| NNG – Data Visualization | https://www.nngroup.com/articles/data-visualization/ | Charts and tables. |
| NNG – Report Design | https://www.nngroup.com/articles/report-design/ | Reports. |
| WCAG 2.1 Quick Reference | https://www.w3.org/WAI/WCAG21/quickref/ | Accessibility. |
| Baymard Institute (e.g. tables) | https://baymard.com/blog | Tables, forms, checkout-style patterns. |

### Charting

| Resource | URL | Use |
|----------|-----|-----|
| Recharts | https://recharts.org/ | React charts, API. |
| Recharts – Examples | https://recharts.org/en-US/examples | Line, bar, area, pie. |
| Recharts – ResponsiveContainer | https://recharts.org/en-US/api/ResponsiveContainer | Responsive charts. |
| Recharts – Tooltip | https://recharts.org/en-US/api/Tooltip | Tooltips. |
| NNG – Data Visualization | https://www.nngroup.com/articles/data-visualization/ | When and how to chart. |

### Reporting

| Resource | URL | Use |
|----------|-----|-----|
| NNG – Report Design | https://www.nngroup.com/articles/report-design/ | Report layout and clarity. |
| Baymard – Tables | https://baymard.com/blog/tables | Table UX for report lists. |

---

## 7. Done when

- Navigation and breadcrumbs are consistent; key flows (login → Fleet → entities) are short and predictable; empty and error states are clear; feedback (toast, loading) is in place.
- Dashboards and result views use Recharts (or agreed library) with design tokens; charts have titles, legends, tooltips; responsive and empty states handled.
- Reports and report executions have a clear list/show/run flow; “Run” and “Download” are obvious; reporting UI is aligned with design system.
- All UX, charting, and reporting references above are linked and available for the team.

After UI-07, the refactor covers **UX** (logical, connected, smooth), **charting** (Recharts, dashboards, reports), and **reporting** UI with full references.
