# Fusion CRM UI Design System

This document defines the **brand tokens**, **layout**, **component conventions**, and **anti-patterns** for the rebuild. Chief (and any AI or developer) must follow it for every Inertia page and DataTable so the CRM does **not** default to generic AI-generated UI. Read this in **Step 0** and apply it for all subsequent steps.

---

## 1. Brand tokens (v3 → v4)

Fusion v3 brand — use these in the new app:

| Token        | Hex       | Use |
|-------------|-----------|-----|
| Primary     | `#f28036` | Warm orange — buttons, links, active states, focus rings (distinctive property/real estate feel) |
| Body bg     | `#f3f1ee` | Warm off-white, not clinical pure white |
| Nav / card  | `#f9f8f7` | Barely-warm white |
| Text body   | `#727E8C` | Blue-gray body |
| Headings    | `#475F7B` | Dark blue-gray |
| Border      | `#DFE3E7` | Light gray |

---

## 2. Tailwind v4 CSS variables (Step 0)

Add to **resources/css/app.css** (or the kit's main CSS entry) so all components use the Fusion theme:

```css
/* Fusion brand theme — add to :root */
:root {
  /* Brand orange — oklch for Tailwind v4 */
  --color-primary: oklch(70% 0.18 48);           /* #f28036 equivalent */
  --color-primary-foreground: oklch(98% 0 0);

  /* Warm neutral backgrounds — not clinical white */
  --color-background: oklch(96% 0.005 70);      /* #f3f1ee */
  --color-card: oklch(98% 0.003 70);           /* #f9f8f7 */

  /* Blue-gray text — professional */
  --color-foreground: oklch(45% 0.05 240);      /* #475F7B */
  --color-muted-foreground: oklch(55% 0.04 240); /* #727E8C */

  /* Border */
  --color-border: oklch(88% 0.02 240);          /* #DFE3E7 */

  /* Status dot colors */
  --color-status-new: oklch(65% 0.15 240);       /* blue-gray */
  --color-status-qualified: oklch(65% 0.18 160); /* teal-green */
  --color-status-proposal: oklch(70% 0.18 48);   /* orange (primary) */
  --color-status-converted: oklch(60% 0.20 145); /* green */
  --color-status-archived: oklch(70% 0.02 240);  /* muted gray */
  --color-status-reserved: oklch(65% 0.18 30);   /* amber */
  --color-status-sold: oklch(55% 0.20 145);      /* dark green */
  --color-status-cancelled: oklch(55% 0.18 15);  /* red */
}

/* Fusion dark theme — for [data-theme="fusion-dark"] or .dark */
[data-theme="fusion-dark"],
.dark {
  --color-background: oklch(15% 0.01 240);
  --color-card: oklch(18% 0.01 240);
  --color-foreground: oklch(90% 0.02 240);
  --color-muted-foreground: oklch(65% 0.03 240);
  --color-primary: oklch(72% 0.18 48);          /* orange stays vibrant in dark */
  --color-border: oklch(28% 0.02 240);
}
```

Use CSS variables everywhere so dark mode and theme switching work; do **not** hardcode `#f28036` or `text-gray-700` in components.

---

## 3. shadcn / components (Step 0)

When customizing the kit's UI (e.g. shadcn `components.json`):

- **Base color:** zinc (closest to the warm neutral palette).
- **CSS variables:** true (use the variables above).
- **Radius:** `0.5rem` (purposeful; not pill, not square).

---

## 3b. App Shell — Navigation & Layout (Step 0)

Chief must build a single consistent app shell in Step 0. All subsequent pages slot into this shell — do NOT reinvent the layout per step.

### Sidebar navigation (left, persistent, Linear.app model)

```
┌──────────────────────────────┐
│  [Fusion CRM logo]      [≡]  │  ← collapse to 56px icon-only
├──────────────────────────────┤
│  ● Dashboard                 │  (Lucide: LayoutDashboard)
├──────────────────────────────┤
│  ▶ Contacts              ›   │  (Lucide: Users) — expands
│      ● All Contacts          │
│      ● Buyer Pipeline        │
│      ● Seller Pipeline       │
├──────────────────────────────┤
│  ▶ Properties            ›   │  (Lucide: Building2) — expands
│      ● Projects              │
│      ● Lots                  │
│      ● Potential Properties  │
│      ● Favourites            │
├──────────────────────────────┤
│  ▶ Sales                 ›   │  (Lucide: TrendingUp) — expands
│      ● Reservations          │
│      ● Sales & Commissions   │
├──────────────────────────────┤
│  ● Tasks                     │  (Lucide: CheckSquare) — flat
├──────────────────────────────┤
│  ▶ Marketing             ›   │  (Lucide: Megaphone) — expands
│      ● Mail Lists            │
│      ● Campaigns             │
│      ● Flyers                │
│      ● Bot in a Box          │
│      ● Ad Manager [admin]    │
├──────────────────────────────┤
│  ▶ Reports               ›   │  (Lucide: BarChart3) — expands
│      ● Overview              │
│      ● Agent Performance     │
│      ● Login History         │
│      ● Same Device [admin]   │
├──────────────────────────────┤
│  ● Resources                 │  (Lucide: FolderOpen)
│  ● Websites                  │  (Lucide: Globe) — subscriber role
├──────────────────────────────┤
│  ⚙ Settings                  │  — bottom, muted
│  🛡 Admin Panel              │  — superadmin only
└──────────────────────────────┘
```

- **Width**: 220px expanded, 56px collapsed (icon-only). Collapse toggle top-right.
- **Expandable groups**: `▶` icon (ChevronRight) rotates to `▼` when open. Parent stays `text-primary font-medium border-l-2 border-primary` when any child is active.
- **Sub-items**: indented `pl-9`, smaller `text-xs`, `text-muted-foreground`, small 5px dot bullet. Active sub-item: `text-primary font-medium`.
- **Collapsed icon-only mode** (56px): shows only the parent icon, no text, no children. Hover shows tooltip with the group name.
- **Bottom items** (Settings, Admin): pushed to bottom with `mt-auto`, visually lighter (`text-muted-foreground`).
- **Admin Panel**: hidden for non-superadmin users (Spatie `can('access filament')` check).
- **Floating AI button** (brain/sparkles icon, 56px circle, `bg-primary text-white`, shadow-md): fixed bottom-right on all pages, outside the sidebar — NOT a nav item.

### Pages that use Smart List sub-panel

The Smart List sidebar (220px, border-r) only appears on specific pages — it replaces part of the main content area, it is NOT part of the left nav sidebar:

| Page | Smart List? | Note |
|---|---|---|
| `/contacts` | ✅ Yes | Left Smart List + DataTable |
| `/contacts/{id}` | ✅ Yes (collapsed) | Collapsible, shows current list context |
| All other pages | ❌ No | Full-width content |

### Page shell structure

```
┌─────────────────────────────────────────────────────────┐
│  [Sidebar 220px]  │  [Page header: breadcrumb + actions]│
│                   │  ─────────────────────────────────── │
│                   │  [Page content]                     │
│                   │                                     │
│  [AI btn 56px]    │  (scrollable)                       │
└─────────────────────────────────────────────────────────┘
```

**Page header** (sticky top, `h-14`, `border-b border-border`, `bg-card`): breadcrumb (Home > Contacts > John Smith), page title `text-xl font-semibold`, right-side action buttons (New Contact, Export, etc.).

**Breadcrumb pattern**: use `laravel/wayfinder` routes. Format: `Home / [Section] / [Record name]`. Section link returns to index. Record name is non-linked.

### Route/URL map (canonical — Chief must use these exactly)

Chief must use these exact URL slugs. All routes are registered in `routes/web.php` and use `laravel/wayfinder` for type-safe React links.

| Page | URL | Controller method |
|---|---|---|
| Dashboard | `/dashboard` | `DashboardController@index` |
| Contact list | `/contacts` | `ContactController@index` |
| Contact detail | `/contacts/{contact}` | `ContactController@show` |
| Contact create | `/contacts/create` | `ContactController@create` |
| Project list | `/projects` | `ProjectController@index` |
| Project detail | `/projects/{project}` | `ProjectController@show` |
| Lot list (standalone) | `/lots` | `LotController@index` |
| Lot detail | `/lots/{lot}` | `LotController@show` |
| Reservation list | `/reservations` | `ReservationController@index` |
| Reservation detail | `/reservations/{reservation}` | `ReservationController@show` |
| Sale list | `/sales` | `SaleController@index` |
| Sale detail | `/sales/{sale}` | `SaleController@show` |
| Task list | `/tasks` | `TaskController@index` |
| Report list | `/reports` | `ReportController@index` |
| Report detail | `/reports/{report}` | `ReportController@show` |
| Flyer list | `/flyers` | `FlyerController@index` |
| Mail list index | `/mail-lists` | `MailListController@index` |
| AI chat API | `/api/chat` | `ChatController@stream` |
| Filament admin | `/admin` | Filament (superadmin only) |

> **Important for Visual QA**: `00-visual-qa-protocol.md` references these exact URLs. If Chief deviates from this map, Visual QA checks will fail navigation.

---

## 4. Theme preset (Step 0)

Add a **fusion** theme preset so the app loads Fusion branding by default. In the kit's theme config (e.g. **config/theme.php** or equivalent):

```php
'presets' => [
    'fusion' => ['label' => 'Fusion CRM'],
    'fusion-dark' => ['label' => 'Fusion CRM Dark'],
    // ... existing presets
],
'preset' => env('THEME_PRESET', 'fusion'),
'font' => env('THEME_FONT', 'geist-sans'),  // sharper, data-focused
```

Set in **.env**: `THEME_PRESET=fusion`, `THEME_FONT=geist-sans`.

---

## 5. Design principles (every Inertia page)

Chief must follow these for **every** CRM page. Do **not** default to generic shadcn neutral styles.

| Principle | What it means | What to avoid |
|-----------|----------------|----------------|
| **Data-first density** | CRM users power-use all day. Tables and lists compact (`text-sm`, `py-1.5` rows), not spacious cards. | Do NOT use `p-6` card padding on every element. |
| **Orange primary** | Buttons, links, active states, focus rings use `--color-primary` (#f28036). | Do NOT default to gray/zinc buttons everywhere. |
| **Warm neutral surfaces** | Background #f3f1ee, not pure white or generic gray-100. | Do NOT use `bg-gray-100` or `bg-white` as default. |
| **Left-anchored layouts** | Sidebar + content. Navigation always left, never top-only. | Do NOT center main content on wide screens. |
| **Status as dots, not pills** | Contact stage, sale status = ● 8px colored dot + label, not `<Badge>` pills everywhere. | Do NOT badge-ify every status field. |
| **Dark mode parity** | Every page works in dark mode; use CSS vars, not hardcoded colors. | Do NOT use `text-gray-700` directly. |
| **Command palette** | Use the kit's Cmd+K command palette on every page. | Do NOT add standalone search bars that duplicate it. |
| **AI as natural affordance** | Floating AI assistant (kit ai-chat component) accessible from every CRM page. | Do NOT bury AI in a settings menu. |

---

## 6. Reference apps (by area)

Use these as design anchors so the UI does not look like a generic "SaaS dashboard":

| Area | Reference | Why |
|------|-----------|-----|
| Navigation / layout | **Linear.app** sidebar | Narrow, icon+text, collapsible sections |
| Tables | **Vercel** dashboard | Dense, sortable, no unnecessary whitespace |
| Contact / record detail | **Raycast** | Command-driven, fast, clear information hierarchy |
| Dashboard KPIs | **Clerk** dashboard | Card KPIs with trend indicators, warm background |
| Property cards | **Airbnb** search | Photo + key data, not generic table rows |

---

## 7. Layout patterns by page type

### Primary pages

| Page | Pattern |
|------|---------|
| **Dashboard / Home** | **Inbox-first, not static KPI grid.** Top row: 3 action-required counts (new leads, tasks due today, stale contacts). Below: priority contact work queue (5–10 contacts to action now, sorted by Days-Since-Contact). Below: bento grid with pipeline funnel chart + commission trend + AI insight card. Bottom: KPI stats. Reference: Follow Up Boss Inbox model. |
| **Contact list** | Full-width DataTable with HasAi NLQ search bar at top. Columns: checkbox, name+avatar, lead source dot, stage dot+label, last-contact date (color-coded: green <7d, amber 7–30d, red >30d), assigned agent, tags, quick-action buttons. Left sub-panel: **Smart List sidebar** (saved filter sets in collapsed groups: "My Leads", "Hot", "Stale", "Past Clients", etc.). |
| **Contact detail** | 2-col: left ~60% main (header bar with name + lead stage pill-less indicator + lead score badge + Days-Since-Contact badge → AI summary card → **chronological timeline** of all activity: calls, emails, notes, property views, tasks). Right ~40% sidebar: stage state-machine transitions + upcoming tasks + Property Interests section + related contacts. Inline action on timeline items (reply email, complete task, add note). |
| **Pipeline (Buyer)** | Kanban board: columns = lead stages (New → Contacted → Nurturing → Searching → Showing → Offer → Under Contract → Closed). Each card: avatar + name + price range + days-in-stage + assigned agent. Column header shows deal count + aggregate value. One-click slide-in drawer for contact detail. Filter bar: agent, source, date range. |
| **Pipeline (Seller)** | Separate Kanban from Buyer pipeline. Columns: New → Appointment Set → Listing Signed → Active → Under Contract → Closed. Cards show property address + contact name + list price + days-on-market. |
| **Project / lot list** | **Grid is the default view** (used for client presentation). Cards: hero photo, project name, suburb + state, developer, price-from, stage dot, available/reserved/sold counts inline. Click "View Lots" → **slide-over panel** (right drawer, no page navigation) showing lot inventory table: lot number, floor, bed/bath/car, size, price, status dot, Reserve action. Table view is secondary (for admin/reporting): project name, available/reserved/sold counts, stage, last-updated. HasAi NLQ bar at top. Filter bar: suburb, price range, stage, developer. |
| **Sale / Transaction detail** | Timeline-heavy with **milestone checklist** (inspection, finance, settlement date) showing progress toward closing. Commission breakdown table (Money formatted). Xero status indicator. Document vault. AI Summary button → C1 card. |
| **Reports** | C1-powered NLQ bar at top: "Show sales by agent last quarter" → C1 renders chart + table. Standard pre-built charts below: pipeline funnel, lead source ROI, agent performance, commission trend. |

### Smart List sidebar (contact list left panel)

Follows the **Follow Up Boss model** — the most effective real estate CRM UX pattern. Treat it as a dynamic work queue, not just nav.

```
▼ My Work Queue
    New Leads – No Contact          (12)
    Hot – Active Last 7 Days         (8)
    Stale – No Contact 30+ Days     (34)
▼ Pipelines
    Buyers – Active                 (21)
    Sellers – Active                 (9)
    Under Contract                   (4)
▼ Past Clients
    Anniversary This Month           (3)
    Reconnect – 12+ Months          (47)
▼ All Contacts
    My Contacts                    (341)
    Team Contacts                  (982)
```

- Each Smart List = saved filter set. Clicking loads the DataTable pre-filtered.
- Counts update in real time (or on page load).
- Users can create custom Smart Lists from any DataTable filter state.
- Sidebar width: 220px fixed, `border-r border-border`.
- Group headers: `text-xs font-medium text-muted-foreground uppercase` + collapse toggle.
- Active Smart List: `bg-primary/10 text-primary font-medium rounded-md`.

### Contact record timeline

All activity in one chronological feed — not tabbed sections:

```
[●] Note added · 2h ago          "Called, left voicemail"         [Edit] [Delete]
[✉] Email sent · Yesterday       "Finance pre-approval follow-up"  [View] [Reply]
[☎] Call logged · 3 days ago     Duration: 4m · Outcome: Interested  [Log outcome]
[🏠] Property viewed · 4 days ago  "Aria Residences, Lot 14"         [View lot]
[✓] Task completed · 1 week ago  "Send project brochure"
```

- Each entry: icon + event type + timestamp + brief content + inline action buttons.
- Newest at top.
- Add Note / Log Call / Send Email buttons pinned above the timeline.
- No separate tabs for Notes, Emails, Tasks — all in one feed. Filter chips to narrow.

### Days-Since-Contact color coding

Apply to both the contact list column and the contact record header badge:

| Days | Color | Tailwind class |
|------|-------|----------------|
| 0–6 | Green | `text-green-600 bg-green-50` |
| 7–29 | Amber | `text-amber-600 bg-amber-50` |
| 30–89 | Orange | `text-orange-600 bg-orange-50` |
| 90+ | Red | `text-red-600 bg-red-50` |

### Lead score badge

AI-generated 0–100 score shown on contact list rows and contact record header.

```tsx
// Small badge on list row
<span className="text-xs font-semibold px-1.5 py-0.5 rounded bg-primary/10 text-primary">87</span>

// Larger on record header — with label
<span className="text-sm font-medium">Lead Score <strong className="text-primary">87/100</strong></span>
```

Score inputs: recency of activity, number of property views, email open rate, price range match, last call outcome. Computed by AI (Step 6 agent) or rule-based initially.

---

## 8. Typography system

**Font**: Geist Sans (`THEME_FONT=geist-sans`). Do **not** use system fonts (Arial, Roboto, Inter) or mixed serif/sans combos.

### Type scale

| Class | Size | Weight | Line-height | Use |
|-------|------|--------|-------------|-----|
| Page title | `text-xl` (20px) | `font-semibold` (600) | 1.4 | Page `<h1>` |
| Section heading | `text-base` (16px) | `font-medium` (500) | 1.4 | Section labels |
| Body default | `text-sm` (14px) | `font-normal` (400) | 1.6 | All body content, table cells |
| Small / meta | `text-xs` (12px) | `font-normal` | 1.5 | Timestamps, helper text, badges |
| Stat / KPI | `text-2xl` (24px) | `font-bold` (700) | 1.2 | KPI numbers on dashboard |

**Rules:**
- Body minimum: `text-sm` (14px). Never smaller than `text-xs` for any displayed value.
- No uppercase labels with `tracking-widest`. Use sentence case only.
- No eyebrow labels (e.g. `<small>MARCH SNAPSHOT</small>` above headings). Do NOT do this.
- Line length: limit to 65–75 characters per line for readable paragraphs. Use `max-w-prose`.

---

## 9. Spacing & sizing system

Use a **4px base grid**. Every margin, padding, gap must be a multiple of 4.

| Token | px | Tailwind | Use |
|-------|----|----------|-----|
| xs | 4px | `p-1` / `gap-1` | Icon internal padding |
| sm | 8px | `p-2` / `gap-2` | Table cell padding, badge padding |
| md | 12px | `p-3` / `gap-3` | Form field padding |
| lg | 16px | `p-4` / `gap-4` | Card padding (default) |
| xl | 24px | `p-6` / `gap-6` | Section spacing |
| 2xl | 32px | `p-8` / `gap-8` | Page-level spacing |
| 3xl | 48px | `p-12` / `gap-12` | Large section breaks |

**Fixed dimensions:**
- **Sidebar width:** 240–260px fixed, solid background, `border-r` only — no floating, no rounded outer shell.
- **Toolbar / top bar height:** 48–56px.
- **Table row height:** 40px default (`py-2.5` or `py-1.5 text-sm`).
- **Status dot:** 8px (`w-2 h-2 rounded-full`).
- **Touch targets (mobile):** minimum 44×44px for all interactive elements.
- **Content max-width:** 1280–1440px (`max-w-7xl`). Use the same container width throughout.
- **Card border-radius:** 8–12px (`rounded-lg`). **Never above 12px on functional UI cards.**

---

## 10. Interactive states

Every interactive element must implement all four states:

| State | Tailwind pattern | Notes |
|-------|-----------------|-------|
| **Default** | — | Baseline style |
| **Hover** | `hover:bg-muted/60 cursor-pointer` | Subtle background tint, 150–200ms transition. No `scale` or `translate`. |
| **Focus** | `focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2` | Orange ring (#f28036). Keyboard-navigable. |
| **Active** | `active:bg-muted/80` | Slightly darker than hover |
| **Disabled** | `opacity-50 cursor-not-allowed pointer-events-none` | Never hide, always dim. |
| **Loading** | `opacity-70 cursor-wait` + spinner or `animate-pulse` skeleton | Disable button during async. |

**Transitions:** Always `transition-colors duration-150` (or `duration-200` max). Never `transition-all`. Never bouncy/spring animations.

---

## 11. Animation tokens

| Purpose | Duration | Easing | Notes |
|---------|----------|--------|-------|
| Hover color change | 150ms | ease | Default micro-interaction |
| Dropdown / popover open | 150ms | ease-out | Fade + subtle scale from 0.97 |
| Sidebar collapse | 200ms | ease-in-out | Width transition |
| Page skeleton | — | `animate-pulse` | While data loads |
| Toast / notification | 200ms in, 150ms out | ease | Slide from top-right |

**Hard rules:**
- `prefers-reduced-motion`: wrap all animations in `@media (prefers-reduced-motion: no-preference)`.
- No `translateX` / `translateY` on navigation link hover.
- No bouncy `cubic-bezier` or spring physics on functional UI.
- No `transition-all` (too broad, causes layout recalculation).

---

## 12. Form conventions

| Element | Spec |
|---------|------|
| **Label position** | Always **above** the input (`mb-1.5 text-sm font-medium`). Never floating labels. |
| **Input style** | `border border-border rounded-md px-3 py-2 text-sm bg-card focus-visible:ring-2 focus-visible:ring-primary` |
| **Placeholder text** | Muted gray (`text-muted-foreground`), supplementary only — not the label. |
| **Error state** | `border-destructive` + error message in red directly below the field (never a toast). |
| **Helper text** | `text-xs text-muted-foreground mt-1` below the field. |
| **Required indicator** | `*` in orange (`text-primary`) after label. |
| **Select dropdowns** | Standard `<Select>` from shadcn; no fancy custom animations. |
| **Multi-select** | Use shadcn Combobox or Command component. |
| **Form sections** | Group related fields with a `text-sm font-medium text-foreground` heading + `border-t` divider. |
| **Submit button** | `bg-primary text-primary-foreground hover:bg-primary/90` — orange, right-aligned or full-width on mobile. |

---

## 13. DataTable visual conventions

Every CRM DataTable (Contact, Project, Lot, Sale, Task, Reservation) must follow:

| Element | Spec |
|---------|------|
| **Row height** | 40px (`py-2.5`) — dense, not airy |
| **Header** | `text-xs font-medium text-muted-foreground uppercase tracking-wide` — subtle, not dominant |
| **Cell text** | `text-sm text-foreground` |
| **Hover row** | `hover:bg-muted/40 cursor-pointer` — subtle, 150ms |
| **Selected row** | `bg-primary/8 border-l-2 border-primary` — orange left accent |
| **Checkbox column** | First column, 40px wide, `w-10` — for bulk actions |
| **Status column** | `● dot (8px) + text-sm label` — never a `<Badge>` pill |
| **Action column** | Last column, icon buttons only (`w-8 h-8`), visible on row hover |
| **Empty state** | Centered: icon (48px) + `text-sm text-muted-foreground` message + optional CTA |
| **Loading state** | `animate-pulse` skeleton rows (same height as data rows) |
| **Bulk action bar** | Appears above table when rows selected: `bg-primary/10 rounded-md px-4 py-2` with count + actions |
| **HasAi NLQ bar** | Full-width above table, `border border-border rounded-md`, Cmd+K shortcut hinted |
| **Pagination** | Simple: previous/next + page count. Bottom of table. Left-aligned row-count, right-aligned controls. |
| **Zebra stripes** | Do NOT use. Use hover state instead. |
| **Column sort** | Chevron icon inline with header label; sorted column header slightly bolder. |

**Mobile (< 768px):** DataTables collapse to card-per-row layout. Each card shows 3–4 key fields + action button. Use `overflow-x-auto` wrapper for tables that cannot collapse.

---

## 14. Icon system

- **Library:** [Lucide React](https://lucide.dev/) — already standard in shadcn/kit. Do NOT mix in other icon sets.
- **Size:** `w-4 h-4` (16px) for inline/table icons; `w-5 h-5` (20px) for toolbar/nav; `w-6 h-6` (24px) for feature icons.
- **Color:** `text-muted-foreground` by default; `text-primary` for active/highlight; `text-destructive` for destructive actions.
- **Decorative backgrounds:** Do NOT wrap icons in colored `rounded-full` pill backgrounds. No icon badges with gradient fills.
- **Icon-only buttons:** Always add `aria-label="..."` and `title="..."`.
- **No emoji as icons.** Use SVG icons only.

---

## 15. Anti-patterns (Uncodixfy hard no list)

Chief must actively avoid these patterns. They produce generic AI-looking UI.

### Structure
- ❌ Floating, detached sidebar with rounded outer shell — use fixed 240–260px sidebar with `border-r`
- ❌ Hero sections inside internal CRM pages (dashboards, contact lists, etc.)
- ❌ Eyebrow labels: `<small>MARCH SNAPSHOT</small>` above headings — never
- ❌ Decorative copy as page headers (e.g. "Operational clarity without the clutter")
- ❌ Section notes inside UI explaining what the UI does
- ❌ Rail panels on the right side with "Today" schedule (use sidebar properly)
- ❌ Multiple nested panel types (panel, panel-2, rail-panel)
- ❌ `<small>` inside headers; big rounded `<span>` badges as labels

### Visual
- ❌ Oversized border radius (20–32px range) — max 12px on cards, 8–10px on buttons
- ❌ Pill-shaped buttons (`rounded-full`) — use `rounded-md` (6–8px)
- ❌ Gradient button backgrounds — solid fills only
- ❌ Glassmorphism / frosted panels as default visual language
- ❌ Soft corporate gradients faking taste
- ❌ Decorative sidebar blobs or brand-mark gradient backgrounds
- ❌ Dramatic drop shadows (`0 24px 60px rgba(0,0,0,0.35)`) — max `shadow-sm` or `0 2px 8px rgba(0,0,0,0.08)`
- ❌ Colored shadows or glows
- ❌ Conic-gradient donuts or random blur haze as decoration
- ❌ Donut charts with hand-wavy percentages used only to fill space
- ❌ Tag badges (`<Badge>`) on every status value — use status dots instead

### Animation
- ❌ `transform: translateX(2px)` on nav link hover — no translate animations
- ❌ Bouncy spring animations on any functional UI element
- ❌ Sliding pill indicators under tabs
- ❌ Animated underlines on inputs (no morphing shapes)

### Typography
- ❌ Uppercase labels with `letter-spacing: 0.15em` everywhere — sentence case only
- ❌ Mixed serif headline + system sans body as "premium" shortcut
- ❌ Muted gray-blue text so muted it fails contrast (use `text-muted-foreground` ≥ 4.5:1 ratio)
- ❌ Oversized startup-style headlines inside CRM pages

### Layout
- ❌ Mixed alignment (some content left-hugging, some floating center-ish) — pick one
- ❌ Overpadded layouts where every card has `p-8` or more
- ❌ Mobile collapse that stacks everything into one long scroll — use proper responsive breakpoints
- ❌ Fake charts that exist only to fill space — every chart must answer a real business question
- ❌ KPI metric-card grid as the **only** dashboard element — use bento grid per §7

---

## 16. Status dot color map

Status values render as `● dot + label`. Colors:

| Status | Dot color CSS var | Applies to |
|--------|------------------|------------|
| New | `--color-status-new` (blue-gray) | Contact stage |
| Qualified | `--color-status-qualified` (teal) | Contact stage |
| Proposal | `--color-status-proposal` (orange) | Contact stage |
| Converted | `--color-status-converted` (green) | Contact stage |
| Archived | `--color-status-archived` (muted) | Contact stage |
| Reserved | `--color-status-reserved` (amber) | Lot / Reservation |
| Sold | `--color-status-sold` (dark green) | Sale / Lot |
| Cancelled | `--color-status-cancelled` (red) | Sale / Reservation |

**Implementation:** `<span class="w-2 h-2 rounded-full inline-block mr-1.5" style="background: var(--color-status-new)"></span> New`

---

## 17. Mobile & responsive

- **Breakpoints** (Tailwind defaults): `sm` 640px, `md` 768px, `lg` 1024px, `xl` 1280px.
- **CRM is primarily desktop.** Mobile is secondary but must not break.
- **Touch targets:** 44×44px minimum for all buttons and interactive elements on mobile.
- **DataTables on mobile:** collapse to card layout (`< md`). Each card: contact name bold, 2–3 key values, action chevron.
- **Sidebar on mobile:** drawer pattern — hidden by default, slide-in on hamburger tap; `duration-200 ease-in-out`.
- **Horizontal scroll:** wrap all desktop tables in `overflow-x-auto` on mobile; do not let them break layout.
- **Font size:** minimum `text-sm` (14px) on mobile, never smaller.
- **Form inputs:** `text-base` (16px) on mobile to prevent iOS auto-zoom.
- **Viewport meta:** `width=device-width, initial-scale=1` — required.

---

## 18. Accessibility minimums

- **Color contrast:** 4.5:1 minimum for body text, 3:1 for large text (18px+ bold). Test with `--color-foreground` on `--color-background`.
- **Focus rings:** every interactive element gets `focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2`. Never remove focus outline.
- **ARIA labels:** every icon-only button must have `aria-label="..."`. Every form input must have `<label for="...">`.
- **Color not the only indicator:** status dots always include a text label, not color alone.
- **Keyboard navigation:** tab order matches visual order. No keyboard traps.
- **Reduced motion:** wrap all transitions in `@media (prefers-reduced-motion: no-preference)`. Provide instant fallback.
- **Semantic HTML:** `<button>` for actions, `<a>` for navigation, `<h1>/<h2>` hierarchy, `<table>` for tabular data. Never `<div role="button">`.

---

## 19. Summary for Chief

- **Step 0:** Apply brand tokens (and dark theme variables above) to `resources/css/app.css`, create the `fusion` (and `fusion-dark`) theme preset in config, set `THEME_PRESET=fusion` and `THEME_FONT=geist-sans` in .env.
- **Logo:** Copy the Fusion CRM logo PNG from `{LEGACY_APP_PATH}/public/images/logo/logo.png` into the new app's `public/images/logo/logo.png`. In `AppSidebar.tsx`: `<img src="/images/logo/logo.png" alt="Fusion CRM" className="h-9 w-auto" />`. In Filament (`AdminPanelProvider`): `->brandLogo(asset('images/logo/logo.png'))->brandLogoHeight('36px')`.
- **Every step that adds an Inertia page or DataTable:** Follow the design principles (§5), use the layout patterns (§7), the typography system (§8), spacing system (§9), interactive states (§10), animation tokens (§11), form conventions (§12), DataTable conventions (§13), icon rules (§14), and **avoid every anti-pattern in §15**.
- **Status values** always render as 8px colored dot + label text (§16). Never `<Badge>` pills.
- **All UI must pass:** pre-delivery checklist below.

### Pre-delivery checklist (every page/component)

- [ ] No emoji used as icons — SVG only (Lucide React)
- [ ] All clickable elements have `cursor-pointer`
- [ ] Hover states have smooth transitions (150–200ms), no layout shift
- [ ] Focus states visible (`ring-2 ring-primary`) for keyboard nav
- [ ] Light mode text contrast ≥ 4.5:1
- [ ] No hardcoded colors — CSS vars only
- [ ] No glassmorphism, gradient buttons, or dramatic shadows
- [ ] No oversized border radius (>12px on cards, >10px on buttons)
- [ ] `prefers-reduced-motion` respected
- [ ] Responsive at 375px, 768px, 1024px, 1440px
- [ ] No horizontal scroll on mobile
- [ ] Icon-only buttons have `aria-label`
- [ ] Status values rendered as dot + label, not `<Badge>`
- [ ] DataTable rows are compact (40px / `py-2.5`)
- [ ] Sidebar is 240–260px, not floating, not rounded outer shell
- [ ] See §21 for additional required checks (AI states, empty states, filters, toasts, error pages)

---

## 20. Prototype reference

The HTML files in `rebuild-plan/ui-prototypes/` are **visual design references only** — static HTML using Tailwind CDN. They are NOT React/Inertia components and must NOT be copied into the codebase. Chief uses them as visual QA targets (screenshot comparison via Playwright). The actual implementation is in `resources/js/Pages/` using React TSX + shadcn/ui components. See **`rebuild-plan/00-component-map.md`** for the full page-to-component mapping.

---

## 21. UX gaps — required specs (apply per step)

These are additional UX patterns not covered in §§1–20. Chief must implement each section in the step where the feature is first built.

---

### 21.1 AI panel — streaming, loading, and error states (Step 6)

The AI chat panel (`AiChatPanel.tsx`) must handle all async states — not just the happy path:

| State | UI Pattern |
|---|---|
| **Thinking / waiting** | Animated typing indicator: 3 dots pulsing (`animate-pulse`, `bg-muted-foreground`, 6px circles). Show immediately after user sends message. Never leave the input frozen. |
| **Streaming response** | Stream tokens token-by-token as they arrive (typewriter effect via `@thesys/client` streaming). Do NOT wait for full response before rendering. |
| **Partial C1 component** | While the component JSON is still building, show a skeleton matching the expected component shape (contact card skeleton, table skeleton). Switch to live component when JSON is complete. |
| **AI error / timeout** | Show inline error card (NOT a toast): `"Something went wrong — try again."` with a `Retry` button. Preserve the user's last message so they can resend. |
| **Credits exhausted** | Show inline credits-empty card: `"You've used all [N] AI credits for this period."` with an `Upgrade Plan` button (links to plan upgrade) OR a `Add Your Own API Key` button (BYOK flow). Block further AI input until resolved. |
| **No API key set** | Show inline setup card: `"AI features need an API key."` with a `Configure` button (links to Settings → AI Keys). |

**AI feedback buttons** — every C1 response card must include:
```tsx
// Below each AI response
<div className="flex gap-2 mt-2">
  <button aria-label="Helpful" className="text-muted-foreground hover:text-green-600 cursor-pointer">
    <ThumbsUp className="w-3.5 h-3.5" />
  </button>
  <button aria-label="Not helpful" className="text-muted-foreground hover:text-destructive cursor-pointer">
    <ThumbsDown className="w-3.5 h-3.5" />
  </button>
  <button aria-label="Regenerate" className="text-muted-foreground hover:text-primary cursor-pointer ml-1">
    <RefreshCw className="w-3.5 h-3.5" />
  </button>
</div>
```

**Mobile AI panel (< 640px):** The 400px Sheet covers most of the screen. On `sm` breakpoint and below: use `side="bottom"` + `h-[85vh]` instead of the right-side 400px Sheet. This gives a natural bottom-drawer pattern on mobile.

---

### 21.2 AI credits widget (Step 6 — sidebar + chat panel)

Place the credit balance widget in **two locations**:

**Location A — Sidebar bottom** (above Settings, below the nav items):
```tsx
// Small compact row in AppSidebar.tsx — collapsed mode shows icon only
<div className="px-3 py-2 border-t border-border mt-auto">
  <div className="flex items-center gap-2 text-xs text-muted-foreground">
    <Zap className="w-3.5 h-3.5 text-primary" />
    <span>{creditsUsed}/{creditsTotal} AI credits</span>
  </div>
  <div className="mt-1 h-1 rounded-full bg-muted overflow-hidden">
    <div className="h-full bg-primary rounded-full transition-all"
         style={{ width: `${(creditsUsed / creditsTotal) * 100}%` }} />
  </div>
</div>
```
- When collapsed (56px icon-only mode): show only the `<Zap>` icon with a Tooltip showing `"{N} AI credits remaining"`.
- When < 20% remaining: progress bar turns `bg-amber-500`; when < 5%: `bg-destructive`.
- BYOK users: replace the widget with `<Zap className="text-primary" /> Own Key` (no credit bar shown).

**Location B — AI chat panel header** (always visible):
- Right side of the chat panel header: `"87 credits"` in `text-xs text-muted-foreground`. Clicking opens the credit detail popover (used today, total this period, reset date).
- If BYOK: show `"Using your key"` with a `Shield` icon.

---

### 21.3 Onboarding progress widget (Step 23)

The onboarding checklist (7 steps from `OnboardingStep` enum) is displayed as a **dismissible banner on the dashboard**, not a modal or overlay.

**Dashboard banner spec:**
```tsx
// Appears below the 3-action-required cards, above the priority work queue
// Dismissed via an X button — stored in user preferences (never show again once dismissed)
<Card className="border-primary/20 bg-primary/5 mb-6">
  <CardHeader className="pb-2">
    <div className="flex items-center justify-between">
      <span className="text-sm font-medium">Get started — {completedCount}/7 steps done</span>
      <div className="flex items-center gap-3">
        <div className="w-32 h-1.5 rounded-full bg-muted overflow-hidden">
          <div className="h-full bg-primary rounded-full"
               style={{ width: `${(completedCount / 7) * 100}%` }} />
        </div>
        <button aria-label="Dismiss onboarding" onClick={dismiss}><X className="w-4 h-4 text-muted-foreground" /></button>
      </div>
    </div>
  </CardHeader>
  <CardContent>
    <div className="flex gap-3 flex-wrap">
      {steps.map(step => (
        <button key={step.key}
          className={cn("flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-md cursor-pointer",
            step.completed
              ? "bg-green-50 text-green-700 line-through"
              : "bg-card border border-border text-foreground hover:border-primary/50")}>
          {step.completed ? <Check className="w-3 h-3" /> : <Circle className="w-3 h-3" />}
          {step.label}
        </button>
      ))}
    </div>
  </CardContent>
</Card>
```

**Rules:**
- Show only until all 7 steps are completed or the user dismisses it.
- Each step button is clickable and navigates to the relevant part of the app (e.g. `upload_contacts` → `/contacts?import=true`).
- Completed steps show with green check + strikethrough text.
- Auto-hide the banner once all steps are complete (replace with a single `"🎉 Setup complete!"` line that fades after 5 seconds, then removes itself).
- Never show the onboarding banner to superadmin or piab_admin — only subscribers.

---

### 21.4 Signup / plan selection UX (Step 23)

The public `/register` page and post-signup plan selection must follow this layout:

**Signup form layout** (`/register` — public, no auth):
- Single-column centered form, `max-w-md`, warm card background.
- Honeypot field: visually hidden (`sr-only` + `tabindex="-1"` + `autocomplete="off"`).
- Progress indicator (2 steps): `[1 Your details] → [2 Choose plan]` — simple numbered steps at top of card.
- Step 1 fields: Full name, Email, Mobile, Business name, ABN (optional), Password, Referral code (optional, collapsed behind "Have a referral code?" link).
- No page reload between steps — use React state to show/hide.

**Plan selection layout** (Step 2 — same page, shown after Step 1 submits):
- 3-column plan cards side-by-side on desktop; stacked on mobile.
- Recommended plan (Growth $415/mo) has `border-primary border-2` + `"Most Popular"` label (`text-xs font-medium text-white bg-primary px-2 py-0.5 rounded-full` positioned above card top).
- Each card: plan name, price (large `text-3xl font-bold`), billing period, feature list (checkmarks for included, muted X for not included), CTA button.
- Annual plan shown as a toggle at top: `Monthly / Annual (save 20%)`. Toggling updates all 3 card prices.
- Payment is stubbed — CTA button says `"Start Free Trial"` or `"Get Started"` (no payment fields shown until Stripe/eWAY credentials are configured).
- After plan click: show a loading state on the button (`opacity-70 cursor-wait`), then redirect to `/onboarding`.

**Mobile plan cards (< 768px):** Stack vertically. Show abbreviated feature list (top 4 features only) with a `"Show all features"` expand toggle.

---

### 21.5 Filter state persistence and active filter indicators (all DataTables)

Filters must persist in the URL via query parameters — not only in React state — so users can share filtered views and use browser Back/Forward:

```tsx
// Pattern: sync filters to URL with Inertia
import { router } from '@inertiajs/react'

function FilterBar({ filters }) {
  const apply = (newFilters) => {
    router.get(window.location.pathname, newFilters, {
      preserveState: true,
      preserveScroll: true,
      replace: true,         // don't add browser history entry
    })
  }
}
```

**Active filter indicator** — when any filter is active beyond defaults:
- Filter button shows an orange dot indicator: `<span className="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-primary" />`
- A `"Clear filters"` link appears inline in the filter bar: `text-xs text-primary underline cursor-pointer`.
- Active filter count badge on the filter toggle button: `"Filters (3)"`.

**Smart Lists** (Contact list sidebar): clicking a Smart List sets `?list={slug}` in the URL. The active Smart List slug is persisted in the URL, so refreshing or sharing the URL opens the same filtered view.

---

### 21.6 Toast notifications and real-time event badges (Steps 0, 6)

**Toast system** (use `sonner` — already in shadcn install list):
```tsx
// resources/js/app.tsx — wrap app in Toaster
import { Toaster } from 'sonner'
<Toaster position="top-right" richColors closeButton duration={4000} />

// Usage throughout app:
import { toast } from 'sonner'
toast.success("Contact saved")
toast.error("Failed to send email — try again")
toast.warning("3 contacts imported with warnings")
toast.info("Lead score updated for 12 contacts")
```

**Toast rules:**
- Success, info toasts: auto-dismiss after 4 seconds.
- Error toasts: do NOT auto-dismiss. Show `closeButton`. Include a one-line action if applicable (e.g. "View error details").
- Never stack more than 3 toasts — `sonner` handles this with the `visibleToasts={3}` prop.
- Never use toasts for form validation errors — those go inline below the field (§12).

**Real-time notification badge** (Reverb WebSocket — Step 6):
- Add a `<Bell>` icon in the **page header** (right side, before the user avatar), with an unread count badge.
- Badge: `absolute -top-1 -right-1 w-4 h-4 rounded-full bg-destructive text-white text-[10px] font-bold flex items-center justify-center`.
- Notification types that badge: task assigned to me, new lead assigned, contact stage changed by another agent, reservation status changed.
- Clicking the bell opens a `<Popover>` (not a new page) showing the last 10 notifications with timestamps and a `"View all"` link.
- On mobile: bell stays in the header; popover becomes a full-width bottom-anchored drawer.
- When Reverb is not connected (offline/dev): badge and popover are hidden — no error shown.

---

### 21.7 Kanban drag-and-drop visual feedback (Steps 14, 15, 20)

All Kanban boards (Buyer Pipeline, Seller Pipeline, Deal Tracker) use `@hello-pangea/dnd` (the React 18-compatible fork of `react-beautiful-dnd`) or `dnd-kit`. Whichever library is used, these visual states are required:

| State | Visual |
|---|---|
| **Dragging card** | Card lifts with `shadow-lg`, `opacity-90`, `rotate-1` (1deg tilt — subtle). `cursor-grabbing`. |
| **Drop zone (hover)** | Column background shifts to `bg-primary/5`, `border-primary/30 border-dashed`. |
| **Invalid drop zone** | Column background shifts to `bg-destructive/5`. Do not show a "forbidden" cursor — just no visual drop affordance. |
| **Drop success** | Card animates into new position (200ms ease). Show a `toast.success("Stage updated")`. |
| **Drag handle** | Cards have a `<GripVertical>` icon (`text-muted-foreground`) visible on hover only — not always visible (reduces visual noise). |

**Mobile Kanban:** Drag-and-drop does not work reliably on touch — use a `<Select>` dropdown inside the card to change stage instead. The drag-handle icon is hidden on mobile (`hidden sm:block`). A `"Move to stage →"` button appears on each card on mobile that opens a stage select popover.

---

### 21.8 Slide-over (Sheet) mobile behavior

Two Sheets are full-width-impacting on mobile:

**Lot slide-over** (`/projects` → "View Lots" — 640px Sheet):
- Desktop (≥ 768px): `className="w-[640px] sm:max-w-[640px]"` as defined.
- Mobile (< 768px): full-screen `side="bottom"` + `h-[90vh]` (bottom drawer). Header shows project name + close button. Lot table becomes a card list (same card-per-row collapse pattern as DataTables).

**AI chat panel** (400px right Sheet):
- Desktop (≥ 640px): right-side Sheet, 400px wide.
- Mobile (< 640px): `side="bottom"` + `h-[85vh]`, full width. No Sheet header needed — just the close button in the chat header.

---

### 21.9 Error pages (Step 0)

Implement two custom error pages in Step 0. These are Inertia pages rendered by Laravel's exception handler:

**404 — Not Found** (`resources/js/Pages/Errors/404.tsx`):
- Layout: centered vertically and horizontally, no sidebar (standalone).
- Content: Fusion logo (top), `"404"` in `text-8xl font-bold text-primary/20`, `"Page not found"` in `text-xl font-semibold`, one-line description `"The page you're looking for doesn't exist or has been moved."`, `"Go to Dashboard"` button (`bg-primary`).
- No illustration needed — the large muted "404" number is the visual anchor.

**500 — Server Error** (`resources/js/Pages/Errors/500.tsx`):
- Same layout as 404.
- `"500"` in same muted large style, `"Something went wrong"`, description `"An unexpected error occurred. We've been notified."`, `"Refresh page"` button + `"Go to Dashboard"` secondary link.
- In production: trigger Sentry error capture before rendering.

Both error pages: apply Fusion brand tokens (`bg-background`, Geist font, orange primary button). No sidebar. No navigation bar. Just the logo, error content, and recovery action.

---

### 21.10 Empty states per section (all DataTables and lists)

Every major list/table must have a unique, actionable empty state — not a generic "No records found". Format: `icon (w-12 h-12 text-muted-foreground/40) + heading (text-sm font-medium) + description (text-xs text-muted-foreground) + CTA button (optional)`.

| Page | Empty state message | CTA |
|---|---|---|
| Contacts | "No contacts yet" / "No contacts match your filter" | `New Contact` / `Clear filters` |
| Projects | "No projects yet" | `Add Project` |
| Lots (slide-over) | "No lots added to this project yet" | `Add Lot` (if admin) |
| Reservations | "No reservations yet" | `New Reservation` |
| Sales | "No sales recorded yet" | `—` |
| Tasks | "You're all caught up!" (when no due tasks) | `Create Task` |
| Smart List (sidebar) | "No contacts in this list" | `Adjust filters` |
| Mail Lists | "No mail lists created yet" | `Create Mail List` |
| AI chat (no messages) | "Ask me anything about your contacts, properties, or pipeline." | Chip suggestions (3 pre-filled prompts) |
| Bot-in-a-Box (no bots) | "No bots set up yet" | `Create Bot` |
| Notifications popover | "You're up to date" | `—` |

**Do NOT use:** blank white space, "No data available", or generic placeholder text.

---

### 21.11 Lot slide-over — Reserve action UX (Steps 3, 5)

When a user clicks **Reserve** on a lot inside the lot slide-over:
- Do NOT navigate away from the Projects page.
- Open a nested `<Dialog>` (modal) over the slide-over: pre-filled reservation form (contact select, purchase price, notes).
- On successful submit: close the dialog, update the lot row status to `reserved` (optimistic update), show `toast.success("Reservation created")`.
- On error: show inline error inside the dialog — do NOT close it.

This keeps the user in context (still on the Projects list) while completing the reservation.

---

### 21.12 Pre-delivery checklist additions (apply from Step 0 onward)

Add these checks to the §19 pre-delivery checklist for every page/component:

- [ ] DataTable filters persist in URL query params (no filter state lost on refresh)
- [ ] Active filter count badge shown on filter toggle when filters are non-default
- [ ] All async operations show skeleton or spinner > 300ms
- [ ] AI responses show typing indicator while waiting; error state + retry on failure
- [ ] Empty states have specific message + actionable CTA (not generic "No data")
- [ ] Toast system uses `sonner` — success auto-dismisses (4s), errors persist
- [ ] Error pages (404/500) use Fusion brand tokens, not Laravel defaults
- [ ] Onboarding banner shows for subscribers only; dismissed state persists
- [ ] Kanban drag: cards tilt on drag, drop zones highlight, mobile falls back to select
- [ ] Sheets (slide-over, AI panel) use bottom-drawer pattern on mobile (< 640px / < 768px)
- [ ] Notification bell in page header with real-time Reverb badge (hidden when offline)
