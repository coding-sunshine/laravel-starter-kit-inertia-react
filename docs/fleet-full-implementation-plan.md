# Fleet App: Full Implementation Plan

This document is the **single execution plan** to complete all pending work from:

- **Master plan:** Fleet Management App: Full Redesign from Scratch (AI-Native and Modern UX)
- **Remaining Work plan:** Fleet App AI UX Overhaul — Remaining Implementation Plan

It sequences every pending item into phases and tasks, with dependencies and suggested order. Do not edit the source plan files; use this document as the implementation roadmap.

---

## Principles

- **Dependencies first:** Phase 0 and 1 foundations before page-level and AI features.
- **Incremental delivery:** Each phase produces shippable improvements.
- **Tests and docs:** Feature tests and `docs:sync` per project rules after new actions/controllers/routes.
- **Optional items** are marked so you can defer or skip them.

---

## Phase 0: Design System Completion

**Goal:** Complete the design system and ui-v2 library so all later phases can “rebuild from scratch” on one system.

**Depends on:** Nothing.

### 0.1 Design tokens (verify/complete)

- [ ] Audit `resources/css/design-tokens.css`: ensure semantic palette (primary, success, warning, error, info, neutral), oklch where needed, dark base for AI panels (#0A0A0A–#1A1A2E).
- [ ] Confirm: 4pt spacing scale, radius scale (e.g. 16px for AI panels), shadow elevation, motion (fast 100–300ms, easing, `prefers-reduced-motion`).
- [ ] Glassmorphism 2.0: backdrop-filter 12–20px, translucent borders for AI panels.

### 0.2 Typography system

- [ ] Choose 1–2 fonts (e.g. Geist, Inter); variable fonts if desired.
- [ ] Define type scale (xs–4xl), weights, line heights; body 45–75ch; heading hierarchy (h1–h6).
- [ ] Ensure WCAG AA contrast; document in design system.

### 0.3 Component library (ui-v2 rebuild)

Build in `resources/js/components/ui-v2/` from scratch (no copy-paste from `ui/`):

- [ ] **Primitives:** Button, Input, Select, Checkbox (Radix where appropriate, CVA variants).
- [ ] **Patterns:** Card, Badge, Alert, Skeleton, EmptyState, ErrorState (align with 0.1 tokens).
- [ ] **Data display:** DataTable (or wrapper), StatCard, Chart wrappers.
- [ ] **Feedback:** Toast (e.g. Sonner), Loading states, Progress.
- [ ] Compound components, clear API, accessibility built-in.
- [ ] *(Optional)* Migrate one high-traffic page (e.g. login) to ui-v2 as pilot; then migrate rest incrementally or keep `ui/` and use ui-v2 only for new/redesigned pages.

### 0.4 Illustration and iconography

- [ ] Confirm empty-state assets in `public/images/empty/` (vehicles, fleet, data-empty) and `illustration` prop on empty-state components.
- [ ] Document icon set (Lucide retained); consistent status visuals (success, warning, error).

### 0.5 AI-specific UI (verify/complete)

- [ ] Confirm: Streaming text, skeleton shimmer, confidence indicators, AI panel (glassmorphism, aria-live). Add if missing.
- [ ] *(Optional)* Voice input: persistent mic, waveform, instant transcription (can be Phase 2).

**Exit criteria:** Design tokens and typography documented; ui-v2 has primitives + patterns + feedback components; empty states and AI components in place. One page on ui-v2 optional.

---

## Phase 1: App Shell and Layout Architecture

**Goal:** New layout system, navigation, and chrome. Rethink information architecture.

**Depends on:** Phase 0 (at least tokens and key components).

### 1.1 Information architecture

- [ ] Define primary nav, secondary nav, contextual actions; fleet-first when `fleet_only_app`.
- [ ] Plan: Favorites (save key pages), Recent (recently visited), Command palette as primary navigation aids.
- [ ] Optional: team-based groupings (dispatch, compliance, maintenance, safety).

### 1.2 App shell (new)

- [ ] **Header:** Global search, org switcher, notifications, user menu — new or rebuilt component.
- [ ] **Sidebar:** Collapsible, icon + label, badges, keyboard nav — rebuild app-sidebar (Samsara-style vertical nav).
- [ ] **Content area:** Consistent padding, max-width, responsive behavior.
- [ ] **FAB/Assistant:** Persistent AI entry point (may already exist; ensure it uses new shell).

### 1.3 Command palette (rebuild)

- [ ] Rebuild `command-dialog`: page navigation (recent, favorites), fleet shortcuts (e.g. V=Vehicles, A=Alerts, D=Drivers), AI tab, search (help, fleet entities).
- [ ] Wire to UnifiedAssistant for AI tab.

### 1.4 Layout templates

- [ ] **Auth layout:** Split layout (form left | benefits/illustration right); new `auth-split-layout`; org branding support.
- [ ] **App layout:** Use/refine `app-layout-v2` (sidebar + content).
- [ ] **Fleet layout:** Optional map-first variant, fleet-specific chrome.

**Exit criteria:** Vertical nav live; command palette with recent/favorites/AI; auth split layout available; fleet layout variant optional.

---

## Phase 2: AI-Native Completion

**Goal:** All AI surfaces and proactive features in place.

**Depends on:** Phase 0, 1.

### 2.1 UnifiedAssistant (verify)

- [ ] Confirm all entry points (FleetAssistantFab, Chat, Inline AI, Command palette) use UnifiedAssistant with context (page, entity_id, scope).
- [ ] Confirm dynamic tool loading by scope (fleet, help, org).

### 2.2 Conversational action tools

- [ ] Implement or verify: CreateWorkOrder, ScheduleInspection, SendDriverReminder, CreateAlert, UpdateComplianceItem.
- [ ] Each: validate org scope, confirmation for destructive actions, structured result (e.g. link to created resource).

### 2.3 Proactive AI

- [ ] **Daily digest:** `App\Jobs\SendFleetDailyDigest` — aggregate alerts, expiring compliance, work orders due; send email (and optional SMS).
- [ ] **Dashboard AI summary:** FleetDashboardController calls AI for 2–3 sentence fleet status; cache 1 hour; expose to dashboard.
- [ ] **Anomaly detection:** Background job for unusual fuel/mileage; store in `ai_analysis_results` or `anomalies` table; surface on dashboard.

### 2.4 AI in every surface

- [ ] **Help:** “Ask about this article” on help show page — opens sheet with assistant, context = article slug.
- [ ] **Settings:** “Explain this setting” — tooltip or link that calls assistant with setting key.
- [ ] **Billing:** “Explain my usage” (read-only org billing); keep “Recommend plan” prominent.
- [ ] NL list filter already done on Vehicles, Drivers, Work Orders.

**Exit criteria:** All five conversational tools working; daily digest job; dashboard AI summary + anomaly surface; Help/Settings/Billing AI entry points.

---

## Phase 3: Feature Improvements

**Goal:** Real-time, alerts, driver scoring.

**Depends on:** Phase 0, 1.

### 3.1 Real-time vehicle tracking

- [ ] Backend: WebSocket or SSE endpoint (e.g. `/fleet/vehicles/positions/stream`) or polling; use Reverb or polling for position updates.
- [ ] Fleet Dashboard map: subscribe or poll every 10–30s; show “Live” badge and last-updated timestamp.

### 3.2 Fleet health (verify)

- [ ] Confirm ComputeFleetHealthScore and dashboard Fleet Health card with drill-down.

### 3.3 Alerts — Web Push (optional)

- [ ] Add Web Push channel (e.g. laravel-notification-channels/webpush); VAPID in config/env.
- [ ] Frontend: service worker `pushManager.subscribe`, send subscription to backend; store per user.
- [ ] On CriticalAlertTriggered, send push when user/org has push enabled.
- [ ] Optional: in-app permission prompt and “Notify me” in alert preferences.  
  *(SMS path already done.)*

### 3.4 DVIR, ELD, IFTA (verify)

- [ ] Confirm DVIR wizard, ELD report, IFTA report routes and pages; add tests if missing.

### 3.5 Driver scoring

- [ ] Add `driver_safety_score` (or similar) from behavior events, incidents, compliance; store on Driver or `ai_analysis_results`.
- [ ] Display on driver show page and in assistant.

**Exit criteria:** Real-time or polling map with Live badge; Web Push optional; driver score computed and visible.

---

## Phase 4: Auth, Onboarding, Welcome (Redesign)

**Goal:** Modern first impression using new design system and layouts.

**Depends on:** Phase 0, 1.

### 4.1 Auth pages

- [ ] **Login:** Rebuild with split layout (form left, illustration/benefits right); optional “Trouble logging in? Ask AI” link.
- [ ] **Register:** Progressive steps (account → org → fleet setup); fleet-specific fields when `fleet_only_app`.
- [ ] **Auth layout:** Support split layout and org branding (use 1.4 auth-split-layout).

### 4.2 Onboarding

- [ ] Fleet-specific steps: add first vehicle, first driver, set up first alert; progress persisted (resumable); “Skip for now” with option to return.
- [ ] Backend: OnboardingController stores progress in `user_meta` or `onboarding_progress` table.

### 4.3 Welcome page

- [ ] Redesign: fleet hero, value props (real-time, AI, compliance); “Try Fleet Assistant” CTA; fleet-specific resources when `fleet_only_app` (replace generic docs links).

**Exit criteria:** Login/register on new layout; onboarding with fleet steps and persistence; welcome fleet-focused.

---

## Phase 5: Dashboard and Settings (Redesign)

**Goal:** Data-driven dashboard and polished settings on new design system.

**Depends on:** Phase 0, 1, 2 (for AI summary).

### 5.1 Main dashboard

- [ ] When not `fleet_only_app`: replace “Activity (sample)” with real data or “Connect data” CTA.
- [ ] Add AI summary card when available (from 2.3).
- [ ] Quick actions as cards with descriptions.
- [ ] Fleet summary card already done; keep.

### 5.2 Settings

- [ ] Confirm tabbed/sidebar layout, search, breadcrumbs; branding “Suggest from logo”; achievements (progress, share).
- [ ] Add branding live preview if not present.

**Exit criteria:** Dashboard uses real data or clear CTA; AI summary card; settings complete with live preview if planned.

---

## Phase 6: Help, Chat, Billing (Redesign)

**Goal:** AI-augmented support and billing on new design system.

**Depends on:** Phase 0, 1, 2.

### 6.1 Help Center

- [ ] Semantic search (Scout/Typesense) for help articles; expose in help index.
- [ ] “Ask about this article” on show page (sheet with assistant, article context) — may be done in 2.4.
- [ ] “People also asked” — AI-generated FAQs from article content.
- [ ] ToC and prose already done; keep.

### 6.2 Chat

- [ ] Suggested prompts and recent topics in sidebar.
- [ ] Improve markdown and citations if needed.
- [ ] Tool usage in messages already done; keep.

### 6.3 Billing and organizations

- [ ] Billing: usage charts (seats, API calls), plan comparison on index/detail if not present; “Recommend plan” prominent.
- [ ] Organizations: card layout, stats, quick actions; org show mini dashboard, members, usage — align with design tokens.

**Exit criteria:** Help has semantic search and “People also asked”; chat has suggested prompts/recent; billing has usage charts and plan comparison where applicable.

---

## Phase 7: Fleet UI Overhaul

**Goal:** Map-first, data-rich fleet experience on new design system.

**Depends on:** Phase 0, 1, 2, 3.

### 7.1 Fleet dashboard

- [ ] Map-first layout and Fleet Health card already done.
- [ ] Real-time map (from 3.1) with Live badge.
- [ ] AI insights card with proactive suggestions (from 2.3).
- [ ] Date range selector for charts; skeleton loaders for charts and map.

### 7.2 Fleet list pages

- [ ] Standardise on DataTable pattern (machour/laravel-data-table) where applicable.
- [ ] Column visibility, bulk actions, export (CSV/Excel), advanced filters on Vehicles, Drivers, Work Orders.
- [ ] Empty states with illustrations and CTAs.
- [ ] “Ask assistant” and NL filter already done; keep.

### 7.3 Fleet detail pages

- [ ] Tabbed layout for Vehicle, Driver, Work Order show pages.
- [ ] Related data sections (e.g. vehicle → trips, defects, work orders).
- [ ] “Ask assistant about this [entity]” already done; add inline actions (create work order, schedule inspection) where missing.

### 7.4 Fleet map

- [ ] Marker clustering for many vehicles.
- [ ] Geofence overlay.
- [ ] Route polylines.
- [ ] Vehicle popover already done; keep.

**Exit criteria:** Fleet dashboard has real-time map, AI card, date range, skeletons; list pages have export and advanced filters; detail pages tabbed with related data and inline actions; map has clustering, geofence, polylines.

---

## Phase 8: Mobile and PWA

**Goal:** Usable on mobile; installable and offline-capable PWA.

**Depends on:** Phase 1, 7.

### 8.1 Responsive design

- [ ] Auth: touch-friendly, large tap targets.
- [ ] Sidebar: sheet/drawer on mobile.
- [ ] Tables: card layout or horizontal scroll with sticky columns on small screens.
- [ ] Forms: single column, large inputs.
- [ ] Maps: full width; bottom sheet for vehicle details on mobile.

### 8.2 PWA

- [ ] Manifest and service worker already done.
- [ ] Install prompt already done.
- [ ] Offline: cache static assets; show “Offline” message when data requests fail.

**Exit criteria:** Responsive behavior per above; PWA installable; offline shell and offline messaging.

---

## Phase 9: Accessibility and Polish

**Goal:** WCAG 2.1/2.2 AA, keyboard nav, reduced motion.

**Depends on:** All phases (ongoing).

### 9.1 Focus and keyboard

- [ ] Visible focus rings; logical tab order across app.
- [ ] Full keyboard navigation; document shortcuts (e.g. in command palette or help).

### 9.2 ARIA and labels

- [ ] ARIA live regions for dynamic/streaming content (e.g. AI responses).
- [ ] Proper labels and roles; no status by color alone.

### 9.3 Motion and contrast

- [ ] `prefers-reduced-motion` respected for animations (tokens already support; verify usage).
- [ ] Contrast meets WCAG AA.

### 9.4 Verification

- [ ] Skip link and focus-visible already done; keep.
- [ ] Run axe (or equivalent); fix issues. Optional: screen reader testing.

**Exit criteria:** No critical a11y violations; keyboard nav and shortcuts documented; reduced motion and contrast verified.

---

## Testing and Documentation (Ongoing)

- [ ] **Feature tests:** NL filter API, DVIR submit, ELD/IFTA reports, SMS/push (or mocks), new AI tools, fleet health, alerts.
- [ ] **Performance:** Lighthouse; Core Web Vitals (INP ≤200ms, LCP <2.5s, CLS <0.1); load <3s on mobile; optional speed budget.
- [ ] **Docs:** After new Actions, Controllers, Pages, Routes — update `docs/developer/`, `docs/.manifest.json`; run `php artisan docs:sync --check`.

---

## Implementation Order (Summary)

| Order | Phase   | Focus |
|-------|--------|--------|
| 1     | 0      | Design tokens, typography, ui-v2 library, illustrations, AI components |
| 2     | 1      | App shell, vertical nav, command palette, auth/fleet layouts |
| 3     | 2      | Conversational tools, proactive AI, “AI in every surface” |
| 4     | 3      | Real-time map, Web Push (optional), driver scoring |
| 5     | 4      | Auth/onboarding/welcome redesign |
| 6     | 5      | Dashboard and settings redesign |
| 7     | 6      | Help (semantic search, People also asked), Chat (suggested prompts), Billing (usage charts) |
| 8     | 7      | Fleet list (DataTable, export, filters), detail (tabs, related data, actions), map (clustering, geofence, polylines) |
| 9     | 8      | Responsive, PWA offline |
| 10    | 9      | Accessibility audit and polish |
| —     | Ongoing| Tests, performance, docs |

---

## Optional / Deferrable

- **0.3** — Full migration of all pages to ui-v2 (can keep `ui/` and use ui-v2 for new/redesigned only).
- **0.5** — Voice input (persistent mic, waveform, transcription).
- **3.3** — Web Push for alerts.
- **1.1** — Team-based nav groupings (dispatch, compliance, etc.).

---

## Key Files (Reference)

| Area           | Files |
|----------------|-------|
| Design tokens  | `resources/css/design-tokens.css`, `resources/css/app.css` |
| ui-v2          | `resources/js/components/ui-v2/` |
| App shell      | `resources/js/layouts/`, `resources/js/components/app-sidebar*` |
| Command palette| `resources/js/components/command-dialog.tsx` |
| Auth           | `resources/js/pages/session/create.tsx`, `resources/js/layouts/auth/` |
| Fleet          | `resources/js/pages/Fleet/Dashboard.tsx`, `resources/js/components/fleet/FleetMap.tsx` |
| AI             | `app/Ai/Agents/UnifiedAssistant.php`, `app/Ai/Tools/Fleet/` |
| Help/Chat/Billing | `resources/js/pages/help/`, `resources/js/pages/chat/`, `resources/js/pages/billing/` |

Use this plan as the single source of truth for “what’s left” and in what order to implement it. Update checkboxes as work completes.
