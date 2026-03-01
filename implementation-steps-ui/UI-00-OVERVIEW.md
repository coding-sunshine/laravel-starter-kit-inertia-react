# UI/UX Refactor – Step-by-step implementation guide

This folder contains **UI/UX refactor steps** that run **on top of** the existing fleet MVP (Laravel, Inertia, React). The goal is to move from a generic “admin dashboard” look to a **professional fleet dashboard** with a consistent design system, clear hierarchy (main dashboard → Fleet dashboard → all fleet areas), and improved usability. Google Maps API is integrated wherever maps or location context add value.

**Prerequisites:** Fleet MVP feature-complete (routes, Fleet dashboard, workflow definitions, AI job runs, etc.). Stack: Laravel, Inertia.js, React, Tailwind CSS v4, Radix UI primitives (Shadcn-style), Vite.

---

## Design direction

| Aspect | Target |
|--------|--------|
| **Primary palette** | `#333333` (dark grey), `#4348be` (primary blue), `white` (backgrounds, contrast). |
| **Look and feel** | Professional fleet operations dashboard: clear hierarchy, readable typography, consistent spacing, subtle motion. |
| **Main entry** | Login form → main dashboard (platform). Fleet is a first-class section inside the app with its own dashboard and full sidebar access. |
| **Navigation** | Single sidebar: Platform (Dashboard, Chat, Users, Organizations, Fleet, Pages, Billing, …) with **Fleet expandable** so all fleet routes (Vehicles, Drivers, Routes, Trips, Work orders, Alerts, AI, etc.) are directly accessible. |
| **Components** | Shadcn/Radix-based UI; align with design tokens; add animations (page transitions, card hover, loading states) via Framer Motion or similar. |
| **Animations** | Framer Motion (or agreed library): page transitions, list/card motion, modal enter/exit, loading skeletons, button feedback; respect `prefers-reduced-motion`. |
| **Responsive** | Mobile-first; breakpoints (sm/md/lg/xl); sidebar as drawer on small screens; tables scroll or card layout on mobile; touch targets ≥44px. |
| **UX** | Logical, connected, smooth: consistent navigation and breadcrumbs; clear flows (list → show → edit); feedback (toast, loading); empty and error states; predictable patterns. |
| **Charting** | Recharts for dashboards and data views (main dashboard, Fleet dashboard, optimization/electrification results, reports); design tokens; tooltips, legends, responsive. |
| **Reporting** | Fleet reports and report executions: clear list/show/run flow, “Run report” and “Download” CTAs, aligned with design system; optional chart preview. |
| **Maps** | Google Maps API used for routes, trips, geofences, locations, electrification/charging, and any screen where “where” matters. |

---

## Feature source → step mapping

| Current pain / goal | Implementation step | Delivers |
|----------------------|----------------------|----------|
| Generic admin look; no design system | UI-01 | Design tokens (colors, typography, spacing), Shadcn/Radix alignment, animation strategy. |
| Login form looks template-like | UI-02 | Redesigned login (and auth layout) with #333333 / #4348be / white, clear CTAs, accessibility. |
| Sidebar flat; Fleet buried; not “proper” hierarchy | UI-03 | App shell and sidebar: header, breadcrumbs, Fleet as expandable section with all fleet routes, everything accessible. |
| Fleet dashboard and cards look basic | UI-04 | Fleet dashboard as proper “Fleet home”: sectioned cards, clear hierarchy, consistent with design system. |
| No map context for routes/trips/locations | UI-05 | Google Maps API key, map components, integration points (routes, trips, geofences, locations, etc.). |
| Animations and responsive not defined | UI-06 | Framer Motion (or alternative), page/list/card animations, reduced motion; responsive sidebar, tables, breakpoints, touch targets; full references. |
| UX, charting, reporting not covered | UI-07 | UX principles (IA, flows, consistency, feedback); Recharts for charts; reporting UI (reports, run, download); all references. |

---

## Step index

| Step | File | Focus |
|------|------|--------|
| **UI-00** | `UI-00-OVERVIEW.md` | This file: goals, palette, roadmap index, order. |
| **UI-01** | `UI-01-design-system.md` | Design system: tokens (#333333, #4348be, white), typography, spacing, Shadcn/Radix, animation library, references. |
| **UI-02** | `UI-02-auth-login.md` | Login form and auth layout: redesign with new palette and proper look and feel. |
| **UI-03** | `UI-03-app-shell-sidebar.md` | App shell: main dashboard layout, sidebar restructure, Fleet expandable with full fleet nav, breadcrumbs, header. |
| **UI-04** | `UI-04-fleet-dashboard.md` | Fleet dashboard: card design, sections (Core, Trips & routes, Fuel, etc.), hierarchy, consistency. |
| **UI-05** | `UI-05-google-maps.md` | Google Maps API: key/config, where to use (routes, trips, geofences, locations, electrification), components. |
| **UI-06** | `UI-06-animations-responsive.md` | Animations (Framer Motion, page/list/card/modal, reduced motion); responsive (breakpoints, sidebar drawer, tables, touch targets); all references. |
| **UI-07** | `UI-07-ux-charting-reporting.md` | UX (IA, flows, consistency, feedback, empty/error states); charting (Recharts, dashboards, reports); reporting UI (reports, run, download); all references. |

---

## Order of execution

1. **UI-01** first: Design system and tokens so every subsequent step uses the same colors and patterns.
2. **UI-02** next: Login and auth layout – first impression and entry into the app.
3. **UI-03**: App shell and sidebar – main dashboard, Fleet as expandable section, all routes accessible.
4. **UI-04**: Fleet dashboard – cards and sections aligned with design system.
5. **UI-05**: Google Maps API and map components where needed (can run in parallel after UI-01).
6. **UI-06**: Animations (Framer Motion or alternative) and responsive design (sidebar drawer, tables, breakpoints); implement after or in parallel with UI-03/UI-04.
7. **UI-07**: UX (logical flows, consistency, feedback), charting (Recharts on dashboards and reports), and reporting UI (reports list/show/run, download); references in UI-07.

---

## Route and page references (existing)

- **Auth:** `GET/POST /login` → `session/create` (Inertia). Auth layout: `auth-layout.tsx` → `auth-simple-layout`.
- **Main:** `GET /dashboard` → `dashboard.tsx`; sidebar from `app-sidebar.tsx` + `nav-main.tsx`; Fleet single link `/fleet`.
- **Fleet:** `GET /fleet` → `Fleet/Dashboard.tsx`; all fleet resources under `Route::prefix('fleet')->name('fleet.')` (vehicles, drivers, routes, trips, work-orders, alerts, ai-job-runs, workflow-definitions, electrification-plan, fleet-optimization, etc.). See `routes/web.php` lines 203–317.
- **Layout:** `app-layout.tsx` → `app-sidebar-layout.tsx` (AppShell, AppSidebar, AppContent, AppSidebarHeader with breadcrumbs).

---

## Stack reminder

- **Frontend:** React 19, Inertia.js, TypeScript, Tailwind CSS v4, Radix UI (Shadcn-style components in `resources/js/components/ui/`), Lucide icons, Sonner toasts, Recharts (charting).
- **Build:** Vite, Laravel Vite plugin.
- **Maps:** Google Maps JavaScript API (to be added); key in `.env` (e.g. `VITE_GOOGLE_MAPS_API_KEY`).
- **Animations:** Framer Motion (recommended) or Tailwind CSS transitions; see UI-06 for patterns and references.
- **Responsive:** Tailwind breakpoints (sm/md/lg/xl); sidebar as drawer on small screens; see UI-06 for full guidelines and references.

---

## References (overview)

| Topic | Primary reference | Step |
|-------|-------------------|------|
| Design system, tokens, Shadcn | [Shadcn UI](https://ui.shadcn.com/), [Radix UI](https://www.radix-ui.com/), [Tailwind](https://tailwindcss.com/) | UI-01 |
| Animations | [Framer Motion](https://www.framer.com/motion/), [Reduce bundle / reduced motion](https://www.framer.com/motion/guide-reduce-bundle-size/) | UI-06 |
| Responsive | [Tailwind Responsive](https://tailwindcss.com/docs/responsive-design), [Radix Sidebar](https://www.radix-ui.com/primitives/docs/components/sidebar), [WCAG Target size](https://www.w3.org/WAI/WCAG22/Understanding/target-size.html) | UI-06 |
| **UX** | [Nielsen Norman Group](https://www.nngroup.com/articles/) (IA, navigation, empty states, consistency, reports), [WCAG 2.1](https://www.w3.org/WAI/WCAG21/quickref/) | UI-07 |
| **Charting** | [Recharts](https://recharts.org/), [Recharts examples](https://recharts.org/en-US/examples), [NNG – Data visualization](https://www.nngroup.com/articles/data-visualization/) | UI-07 |
| **Reporting** | [NNG – Report design](https://www.nngroup.com/articles/report-design/), [Baymard](https://baymard.com/blog) (tables) | UI-07 |
| Maps | [Google Maps JavaScript API](https://developers.google.com/maps/documentation/javascript) | UI-05 |

Full reference lists with URLs are in **UI-01** (design system), **UI-06** (animations and responsive), and **UI-07** (UX, charting, reporting).

After completing UI-01 through UI-07, the application will have a consistent professional dashboard look, clear navigation with Fleet as a first-class section, **good UX** (logical, connected, smooth), **charting** on dashboards and reports, **reporting** UI (reports, run, download), map support, polished animations, and proper responsive behaviour with documented references.
