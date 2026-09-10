# Dashboard Redesign Design Spec

**Date:** 2026-04-30  
**Branch:** feature/laravel-13  
**Status:** Approved — ready for implementation

---

## Goal

Unify the Management Dashboard into a single, cohesive experience: replace the dropdown section switcher with a branded pill nav, dissolve the standalone Command Center block into Executive Overview, and split the 5,799-line `dashboard.tsx` into 8 focused files — all without changing backend behaviour or breaking existing section content.

---

## Context

The current `dashboard.tsx` (5,799 lines) has two problems:

1. **Disjointed layout.** A "Command Center" block (4 AI widgets) sits above the main dashboard area with no visual connection to the 7-section switcher below it. Feels like two separate pages stacked.
2. **Hidden navigation.** The section switcher is a `<Select>` dropdown in the top-right corner — easy to miss, slow to use.

The section content itself (charts, coal stock, penalty data, rake performance) is intact and correct. This redesign moves things around; it does not replace data or logic.

---

## Decisions

| Question | Decision |
|----------|----------|
| Navigation style | Pill / chip row |
| Command Center placement | Merged into Executive Overview as "AI Insights" row |
| Visual direction | Dark navy header + gold pill nav, white content area |
| File architecture | Component split within single Inertia page (Approach 3) |

---

## Architecture

### Backend

No changes. The existing `DashboardController` passes all props via `Inertia::render('dashboard', $props)`. Route, middleware, and props payload are unchanged.

### Frontend file structure

```
resources/js/pages/
  dashboard.tsx                    ← shell (~150 lines): header, pill nav, section renderer
  dashboard/
    types.ts                       ← shared TypeScript types across sections
    ExecutiveOverview.tsx          ← AI Insights row + coal stock cards + dispatch charts
    SidingOverview.tsx             ← extracted verbatim from current dashboard.tsx
    Operations.tsx                 ← extracted verbatim
    PenaltyControl.tsx             ← extracted verbatim
    RakePerformance.tsx            ← extracted verbatim
    LoaderOverloading.tsx          ← extracted verbatim
    PowerPlant.tsx                 ← extracted verbatim
```

### Data flow

```
DashboardController
  └── Inertia::render('dashboard', $props)
        └── dashboard.tsx (shell)
              ├── activeSection: string  (useState, default: 'executive-overview')
              ├── viewMode: string       (useState, default: 'chart' — bar/table toggle)
              ├── pill nav              (filters by visibleSections, same logic as today)
              └── <ActiveSectionComponent {...relevantProps} />
```

Each section component receives only the props it needs, destructured from the full dashboard props object. No global store. No new API calls.

---

## Component Specifications

### `dashboard.tsx` — Shell

**Responsibilities:**
- Render the dark navy header bar
- Render the gold pill nav row
- Own `activeSection` state (default: `'executive-overview'`)
- Own `viewMode` state (default: `'chart'`) — passed to sections that use it
- Render the active section component
- Apply `visibleSections` filtering to pill nav (same permission logic as current `visibleSections` memo)

**Header bar:**
- Background: `#1a1a2e` (BGR navy)
- Title: "Management Dashboard", white text
- Right side: date display + "Bar Chart / Table" view toggle (moved from current position below header)
- No bottom border — the pill nav provides visual separation

**Pill nav row:**
- Background: `#0f0f1e` (slightly darker than header)
- `flex flex-wrap gap-2 px-4 py-2`
- Active pill: `bg-[#1a1a2e] text-[#d4af37] font-semibold` with gold text
- Inactive pill: `border border-slate-700 text-slate-400 hover:text-slate-200`
- All pills: `rounded-full px-4 py-1 text-sm cursor-pointer transition-colors`
- Pills shown: filtered by `visibleSections` (same logic as current dropdown)

**Removed:** The existing `<Select>` dropdown section switcher and the standalone "COMMAND CENTER" block with dashed orange border.

---

### `dashboard/ExecutiveOverview.tsx`

Layout (top to bottom):

**1. AI Insights row** (`grid grid-cols-4 gap-4 mb-6`)

Four cards, one per AI widget. Each card:
- White background, `rounded-lg border shadow-sm p-4`
- Coloured left border (4px): purple (AI Overload), blue (Active Rake Pipeline), red (Penalty Exposure), green (Alert Feed)
- Label (small, muted) + live value (large, bold, colour-matched to border)
- Uses existing widget components: `OverloadPatternsWidget`, `ActiveRakePipeline`, `PenaltyExposureStrip`, `AlertFeed` — relocated, not rebuilt

**2. Coal stock cards** (`grid grid-cols-3 gap-4 mb-6`)

Existing 3-card strip (Dumka, Kurwa, Pakur). Unchanged. Border-top colour per siding (blue / amber / emerald).

**3. Dispatch charts** (`grid grid-cols-2 gap-4`)

Existing Road Dispatch + Rail Dispatch charts. Respond to `viewMode` prop (chart vs table). Unchanged.

No "COMMAND CENTER" heading. No dashed border callout. The AI widgets live here naturally.

---

### Sections 2–7 (`SidingOverview` through `PowerPlant`)

Extracted verbatim from the current `dashboard.tsx`. No content changes. Each file:
- Imports only what it needs
- Receives only the props it uses (typed via `dashboard/types.ts`)
- Is independently readable and editable

---

### `dashboard/types.ts`

Collects TypeScript interfaces shared across section components (e.g., `Siding`, `Rake`, `Penalty`, `ChartDataPoint`). Currently these are defined inline in `dashboard.tsx` — moving them to `types.ts` makes them reusable without duplication.

---

## Migration Strategy

Extract one section at a time. After each extraction, the page still renders correctly. Commit after each section. This means the work is safe to pause mid-task without leaving the dashboard broken.

Order:
1. Create shell `dashboard.tsx` + `types.ts`
2. Extract `ExecutiveOverview.tsx` (most changes — AI widgets move here)
3. Extract remaining 6 sections in any order (verbatim moves)
4. Delete dead code from old `dashboard.tsx`

---

## What Does Not Change

- Laravel route `/dashboard`
- `DashboardController` and its props
- All existing chart components and data logic
- All existing section content (only the JSX location changes)
- The `visibleSections` / `allowedFilters` permission logic (moves into shell)
- Existing Pest feature test for `/dashboard`

---

## Out of Scope

- Adding new data to any section
- Changing chart types or data visualisation
- Backend API changes
- Authentication or permission changes
- Mobile-specific layout beyond `flex-wrap` on the pill nav
