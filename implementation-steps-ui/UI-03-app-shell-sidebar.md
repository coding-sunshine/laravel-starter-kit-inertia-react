# UI Step 3: App shell and sidebar

**Goal:** Restructure the main app shell and sidebar so the app feels like a proper professional dashboard: main dashboard is the hub, and **Fleet** is a first-class section with **all fleet routes accessible** from the sidebar (expandable or grouped). Apply design tokens (#333333, #4348be, white) to header, sidebar, and breadcrumbs. Everything remains accessible without digging through a generic “admin” menu.

**References:** UI-01 (design system), `resources/js/layouts/app-layout.tsx`, `resources/js/layouts/app/app-sidebar-layout.tsx`, `resources/js/components/app-sidebar.tsx`, `resources/js/components/nav-main.tsx`, `resources/js/components/app-sidebar-header.tsx`. Routes: `routes/web.php` (auth + tenant + fleet prefix).

---

## 1. Prerequisites

- UI-01 design system applied (colors, typography, spacing).
- Current sidebar: single “Platform” group with flat items (Dashboard, Chat, Users, Organizations, **Fleet**, Pages, Billing, Blog, Changelog, Help, Contact). Fleet is one link to `/fleet`; no sub-nav for vehicles, drivers, routes, etc.

---

## 2. Sidebar structure target

- **Platform (top-level):**
  - Dashboard → `/dashboard`
  - Chat → `/chat`
  - Users → `/users`
  - Organizations → (tenancy) → org index
  - **Fleet** → expandable/collapsible section (see below).
  - Pages, Billing, Blog, Changelog, Help, Contact → as today, with permissions/features respected.
- **Fleet section (expandable):**
  - When “Fleet” is expanded, show direct links to:
    - Fleet dashboard → `/fleet`
    - Assistant → `/fleet/assistant`
    - Core: Vehicles, Drivers, Driver-vehicle assignments, Locations, Cost centers, Garages, etc.
    - Trips & routes: Routes, Trips, Geofences, Geofence events, Behavior events, Telematics devices.
    - Fuel: Fuel cards, Fuel transactions, Fuel stations, EV charging stations, EV charging sessions, EV battery data.
    - Maintenance: Service schedules, Work orders, Defects, Workshop bays, Parts inventory, Parts suppliers, Tyre inventory, Vehicle tyres.
    - Compliance & safety: Compliance items, Driver working time, Tachograph downloads/calibrations, Vehicle discs, Safety (policy acknowledgments, permit-to-work, PPE, safety observations, toolbox talks), Risk assessments, Vehicle checks, etc.
    - Insurance & incidents: Insurance policies, Incidents, Insurance claims, Fines, Vehicle leases, Vehicle recalls, Warranty claims.
    - AI & automation: AI job runs, AI analysis results, Electrification plan, Fleet optimization, Workflow definitions, Workflow executions, Alerts, Alert preferences.
    - Other: Reports, Report executions, API integrations, API logs, Dashcam clips, Training, Contractors, Mileage claims, Pool vehicle bookings, Grey fleet, Data migration runs, etc.
  - Grouping can be logical (e.g. “Core”, “Trips & routes”, “Maintenance”, “AI & workflows”) with optional sub-labels. Items that map to `Route::resource` or named routes under `fleet.*` should be linked (e.g. `fleet.vehicles.index`, `fleet.drivers.index`, `fleet.routes.index`, `fleet.workflow-definitions.index`).
- **Footer:** Keep API docs, Repository, Documentation, etc.; apply design tokens.
- **User and org switcher:** Keep in sidebar header; style with design system. Organization switcher remains when tenancy is enabled.

---

## 3. Implementation approach

- **Data source for fleet nav:** Build an array of fleet nav items (title, href, permission if any, optional group label). Use route names from `routes/web.php` (e.g. `route('fleet.dashboard')`, `route('fleet.vehicles.index')`, …). Filter by permission/feature where applicable (reuse existing `canShowNavItem`-style logic).
- **Expandable section:** Use Radix Collapsible (or Sidebar’s built-in expand/collapse if the sidebar component supports sub-groups). “Fleet” row expands to show the fleet links. Persist open/closed state in localStorage or sidebar context if desired (e.g. Fleet expanded by default when on any `/fleet/*` path).
- **Active state:** Current page highlight: use `page.url.startsWith('/fleet')` for Fleet section and exact or startsWith for each sub-item. Apply primary color (#4348be) for active item background or left border.
- **Icons:** Reuse Lucide icons per item (Truck for Fleet, Car for Vehicles, Users for Drivers, MapPin/Route for Routes, etc.). Keep icon set consistent.

---

## 4. Header and breadcrumbs

- **AppSidebarHeader:** Already renders breadcrumbs. Ensure breadcrumb labels and separators use #333333; last (current) item can be bold or primary color. Breadcrumb root (e.g. “Dashboard”) links to `/dashboard`; “Fleet” links to `/fleet`; current page is text only.
- **Header bar:** If there is a top bar (e.g. mobile menu trigger, search), apply design tokens. Ensure sufficient contrast and touch targets.

---

## 5. Design tokens in shell

- **Sidebar background:** White or very light grey; border right light grey. Text #333333; active item #4348be background (or left border) and white or light text.
- **Content area:** White background; card and table styles from UI-01.
- **AppShell:** No heavy borders; clean separation between sidebar and content.

---

## 6. Main dashboard page

- **Route:** `GET /dashboard` → `dashboard.tsx` (Inertia). This is the “main” dashboard (platform level). Ensure it uses the same app layout and sidebar; content can be a simple welcome or high-level platform stats. Fleet-specific overview lives at `/fleet` (Fleet dashboard).
- **Hierarchy:** User flow: Login → Main dashboard (`/dashboard`) or redirect to `/fleet` if desired by product. From main dashboard, user can open Fleet in sidebar and go to Fleet dashboard (`/fleet`) or any fleet sub-route. Breadcrumb: Dashboard > Fleet > … or Dashboard > Fleet > Workflow definitions, etc.

---

## 7. Done when

- Sidebar shows Fleet as an expandable section; expanding it reveals direct links to all fleet routes (vehicles, drivers, routes, trips, work orders, alerts, AI job runs, workflow definitions, electrification, fleet optimization, etc.).
- Active route is clearly highlighted (#4348be); sidebar and header use #333333 and white (and optional light grey).
- Breadcrumbs reflect current location (Dashboard > Fleet > …). Main dashboard and Fleet dashboard are both reachable; full navigation is possible from the sidebar without a generic “admin” feel.

Proceed to **UI-04** (`UI-04-fleet-dashboard.md`) for Fleet dashboard card and section redesign.
