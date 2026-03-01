# UI Step 4: Fleet dashboard

**Goal:** Redesign the Fleet dashboard (`/fleet` → `Fleet/Dashboard.tsx`) so it feels like a proper “Fleet home” with clear sections, consistent card design using the design system (#333333, #4348be, white), and a hierarchy that supports quick access to all fleet areas. Avoid the generic “admin card grid” look.

**References:** UI-01 (design system), UI-03 (sidebar with Fleet expandable), UI-07 (UX flows, charting with Recharts, reporting). Backend: `FleetDashboardController::index`, Inertia page `Fleet/Dashboard.tsx`, shared layout `AppLayout`.

---

## 1. Prerequisites

- UI-01 design system and tokens in place.
- Fleet dashboard already receives counts and data from `FleetDashboardController` (vehicles, drivers, routes, trips, fuel cards, fuel transactions, work orders, defects, compliance, alerts, AI, workflows, etc.) and optionally recent work orders, defects, compliance at risk, alerts. Page uses Card, CardHeader, CardTitle, CardContent and Lucide icons.

---

## 2. Current state

- **Layout:** AppLayout with breadcrumbs (Dashboard > Fleet). Title “Fleet dashboard” and subtitle “Overview and quick access to all fleet areas.”
- **Content:** Sections such as “Core”, “Trips & routes”, “Fuel” with cards; each card shows icon, label, count, and links to the corresponding index (e.g. Vehicles, Drivers, Routes, Trips, Fuel cards, Fuel transactions). Additional sections for maintenance, compliance, AI, alerts, etc., and possibly tables (recent work orders, defects, compliance at risk).
- **Pain:** Looks like a basic admin card grid; no strong visual hierarchy or “Fleet” identity; cards and typography not aligned with #333333 / #4348be / white.

---

## 3. Section structure

- **Hero / header:** Keep a clear page title (“Fleet dashboard”) and short subtitle. Use design system typography (#333333). Optional: small primary CTA (e.g. “Open assistant”) in #4348be.
- **Sections with labels:** Group cards into logical sections (e.g. “Core”, “Trips & routes”, “Fuel”, “Maintenance”, “Compliance & safety”, “AI & automation”, “Alerts”). Each section has a section heading (e.g. `text-lg font-semibold` in #333333) and a grid of cards.
- **Card design:**
  - Background white; border light grey (design system); rounded corners; consistent padding. Hover: subtle shadow or border change; use Framer Motion `whileHover` or Tailwind transition (see UI-01, UI-06). Card enter: optional stagger or fade when section mounts.
  - Each card: icon (Lucide) in a consistent style – e.g. muted grey or #4348be for accent; label (e.g. “Vehicles”) in #333333; count prominent (e.g. `text-2xl font-semibold` in #333333 or #4348be). Link to the resource (e.g. `/fleet/vehicles`) as card or “View” link in primary color.
  - Ensure touch targets and click area are clear (entire card or explicit button/link).
- **Tables (if present):** Recent work orders, defects, compliance at risk, alerts – use consistent table styling (header #333333 or muted, borders light grey, row hover). “View all” links in #4348be. Align with design system tables used elsewhere (e.g. Workflow definitions index). See UI-07 for table/report UX references.
- **Charts (optional):** Add Recharts (e.g. bar or line) for trends (vehicles, trips, fuel over time) in a “Trends” or “Overview” section; use design tokens (#4348be, #333333). See UI-07 for charting patterns and references.

---

## 4. Hierarchy and density

- **Above the fold:** Consider putting “Core” (Vehicles, Drivers, Driver-vehicle assignments) and “Trips & routes” (Routes, Trips) near the top, as they are high-traffic. Follow with Fuel, Maintenance, then Compliance, AI, Alerts.
- **Grid:** Use a responsive grid: e.g. `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4` so 1 column on mobile, 2–4 on larger screens. Consistent gap (e.g. `gap-4` or `gap-6`). See UI-06 for breakpoints and touch targets.
- **Counts:** Ensure counts are passed from controller as today; no backend change required unless new sections are added. Focus on presentation.

---

## 5. Consistency with other fleet pages

- Fleet dashboard is the “home” for Fleet; list and show pages (e.g. Vehicles index, Workflow definitions index) use the same AppLayout and sidebar. Their tables and buttons should already follow UI-01 (primary button #4348be, table headers and text #333333). Fleet dashboard cards should feel part of the same system: same Card component, same primary link color, same spacing scale.

---

## 6. Optional enhancements

- **Quick stats strip:** A single row of key metrics (e.g. total vehicles, active alerts, open work orders) with primary-colored numbers and links. Complements the sectioned cards.
- **Empty states:** If a section has zero count, show a short message and a CTA (e.g. “Add vehicle”) in primary color instead of a bare “0”.

---

## 7. Done when

- Fleet dashboard uses #333333, #4348be, and white consistently; section headings and card labels are clear; cards have consistent padding, border, and hover behavior.
- Sections (Core, Trips & routes, Fuel, etc.) are ordered and labeled; each card links to the correct fleet route. Tables (if any) match design system.
- The page feels like a dedicated “Fleet home” rather than a generic admin card grid.

Proceed to **UI-05** (`UI-05-google-maps.md`) for Google Maps API integration.
