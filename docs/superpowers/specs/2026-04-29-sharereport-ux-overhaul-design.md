# SHAReReport UI/UX Overhaul — Design Spec

**Date:** 2026-04-29
**Client:** BGR Mining & Infra Limited
**System:** Railway Rack Management & Coal Supply System (SHAReReport / RRMCS)
**Scope:** Full UI/UX overhaul — desktop only (mobile is a separate app)
**Goal:** Help BGR Mining save on Indian Railways penalties for wagon overloading/underloading while delivering a professional BGR-branded platform

---

## 1. Business Context

BGR Mining supplies coal from three sidings (Dumka DUMK, Kurwa KURWA, Pakur PKUR) to five power plants (STPS-Santaldih, BTPC-Bakreswar, KPPS-Kolaghat, PSPM-Sagardighi, BTMT-Bandel) via railway rakes.

Indian Railways penalises per-wagon overloading (loaded MT > PCC) and underloading. These penalties are largely **preventable** — the PCC data exists in the system and is already sent to the frontend, but the current loading UI shows zero warnings when operators exceed PCC limits.

**Primary business goal:** Prevent penalty exposure by surfacing PCC violations at the moment of data entry, before rakes are dispatched.

---

## 2. Constraints

- **Desktop only** — mobile is a separate application, not in scope
- **No Filament admin panel** — excluded from overhaul
- **No breaking changes to existing API endpoints** — response shapes for all existing routes must remain unchanged. New routes follow existing naming conventions
- **Additive backend only** — no existing table columns removed or modified; only new tables and new Inertia props added alongside existing ones
- **Dark/light mode** — user choice, system default
- **Language** — English only (fix "Lignes par page" → "Rows per page" globally)

---

## 3. Implementation Approach

**Approach A — Phase-by-phase sequential delivery.** Complete and ship each phase before starting the next. Each phase is independently testable in production. Lower risk than a big-bang migration.

---

## 4. Role-Aware Views

Three roles, same Inertia pages, role-conditional content rendered via `usePage().props.auth`:

| Role | Dashboard | Loading Page | Analytics |
|------|-----------|--------------|-----------|
| Loader Operator | Active rake only, loading workflow | Full wagon spreadsheet + penalty panel | Hidden |
| Site Manager | Full penalty intelligence, clearance notifications, all rakes at their siding | Read-only + override log visible | Full access |
| Super Admin / Executive | All sidings summary, historical trends | Read-only | Full access |

---

## 5. Design System

### 5.1 Color Tokens (Tailwind v4 CSS-first in `app.css`)

```css
--color-primary:        oklch(0.22 0.06 150);   /* BGR Forest Green  #1E3A2F */
--color-primary-hover:  oklch(0.30 0.07 150);   /* Sidebar hover     #2D5540 */
--color-accent:         oklch(0.72 0.12 80);    /* BGR Gold          #C8A84B */
--color-accent-muted:   oklch(0.92 0.04 80);    /* Gold light bg     #F0E8C8 */
--color-background:     oklch(0.96 0.01 150);   /* Green canvas      #f0f4f1 */
--color-danger:         oklch(0.50 0.20 25);    /* Overload red      #dc2626 */
--color-warning:        oklch(0.60 0.15 60);    /* Near-PCC amber    #d97706 */
```

Replaces current `oklch(0.205 0 0)` (black) primary across sidebar, table headers, buttons.

### 5.2 Typography

- **Body / UI text:** System UI (`font-sans`) — existing
- **Numbers, weights, ₹ amounts, wagon IDs:** Fira Code (Google Fonts) with `'Courier New'` monospace fallback — applied via a `font-mono` utility on all MT/₹ values
- **Page headings:** System UI Bold 700, color `--color-primary`
- **Table column headers:** 10px, uppercase, letter-spacing 0.6px, `rgba(255,255,255,0.6)` on dark headers

### 5.3 Sidebar

- Full navigation item names (no truncation)
- Grouped sections with uppercase labels: Overview / Loading Operations / Finance & Compliance / Analytics
- Active item: gold (`--color-accent`) background, dark text
- Alert badges (red pill) on Penalties and Dashboard for unread alerts
- Footer: user avatar (initials), name, role + siding
- Logo: custom BGR SVG mark (triangle/mountain motif in gold on dark green) + "SHAReReport" + "BGR Mining & Infra" subtext

### 5.4 Button Hierarchy

1. **Primary** (green `--color-primary`) — main submit actions
2. **Accent** (gold `--color-accent`) — clearance/approval actions
3. **Outline** — secondary actions
4. **Ghost** — tertiary, navigation
5. **Danger** — destructive (mark unfit, reject)

### 5.5 Global Fixes

- "Lignes par page" → "Rows per page" — find and replace across all table pagination components
- Remove "Sign Up" link from login page
- Table row hover state — light green tint `#f0fdf4` on hover
- Page background — `#f0f4f1` (green-tinted canvas) replacing pure white/grey

---

## 6. Login Page

Two-column layout:
- **Left panel** (BGR green background): SVG logo, "Railway Rack Management System", tagline, siding and plant counts as stats
- **Right panel** (white): Email + Password fields, "Sign in to SHAReReport" button, "Forgot password? Contact your administrator." — **no Sign Up link**

---

## 7. Phase Breakdown

### Phase 1 — Brand & Foundation (~1–2 weeks)

**Scope:** CSS tokens, typography, sidebar, login, global table fixes. Pure frontend — zero backend changes.

Files touched:
- `resources/css/app.css` — color token replacement
- `resources/js/layouts/app-layout.tsx` (or equivalent sidebar component) — sidebar restructure
- `resources/js/pages/auth/login.tsx` — login redesign
- All table pagination components — "Rows per page" fix
- Shared button components — hierarchy update

Deliverable: Every page in the app automatically inherits BGR brand colours and fixed typography via token inheritance.

---

### Phase 2 — Operations Command Center Dashboard (~2–3 weeks)

**Scope:** Dashboard redesigned as a penalty-intelligence hub. Reads from existing models — only change is additional Inertia props passed from `DashboardController`.

**Manager/Exec dashboard widgets:**

1. **Penalty Exposure Strip** — today's total ₹ across all active sidings, trend vs last 7 days, % `is_preventable`
2. **Active Rake Pipeline** — kanban: Loading → Awaiting Clearance → Dispatched. Each card: rake number, wagon count, overloaded count, ₹ risk
3. **Siding Coal Stock** — current MT at each siding from `StockLedger.closing_balance_mt`, last receipt timestamp, days of stock at current dispatch rate
4. **Siding Risk Score Widget** — reads `SidingRiskScore.score` (0–100) + `risk_factors` array + `trend`. Colour-coded: green <30, amber 30–70, red >70
5. **Alert Feed** — `Alert` model records, grouped by severity, dismissible
6. **Today's Dispatch Summary** — road + rail dispatch totals from `StockLedger` for today

**Operator dashboard:** Single widget — their assigned rake's loading status and a link to the loading page.

**Backend:** `DashboardController` gets new Inertia props (`penaltySummary`, `rakes`, `sidingStocks`, `riskScores`, `alerts`). No existing props removed.

---

### Phase 3 — Loading Workflow Intelligence (~2–3 weeks)

**Scope:** Wagon loading page gains real-time PCC warnings. Core gauge logic is **100% frontend** — all required data (`pcc_weight_mt`, `loaded_quantity_mt`) is already in Inertia props.

**UI design:** Combined spreadsheet + inline gauges + live penalty panel (approved in brainstorming)

**Left — spreadsheet table** (familiar Excel-style, one row per wagon):
- Columns: `#` · Wagon · PCC (MT) · Load vs PCC · Loaded (MT) · Status · Penalty ₹
- Inline gauge bar in "Load vs PCC" column: fills green → amber (>90%) → red (>100%) as operator types
- Row background: white (default) / `#fffdf0` (near limit) / `#fff5f5` (over PCC)
- Per-row penalty ₹ shown in rightmost column when over PCC
- Overloaded rows show "+ X.X MT over limit" sub-text

**Right — penalty intelligence panel** (BGR green `#1E3A2F`):
- Live estimated penalty total (updates on every input change)
- Breakdown: overload ₹ / underload ₹
- Rake status grid: Within PCC / Over PCC / Near Limit / Not Loaded counts
- Problem wagon list with mini bar + ₹ per wagon
- AI suggestion strip (Phase 5 — stub in Phase 3 with static recommendation logic)
- Dispatch clearance button

**PCC violation thresholds:**
- >100% of PCC → red row, 🛑 Over PCC badge, penalty shown
- 90–100% of PCC → amber row, ⚡ Near limit badge
- <90% → green / normal

**Advisory override flow:**
- "Request Manager Clearance" button is gold and enabled regardless of PCC status
- If any wagon is over PCC, clicking the button opens a modal:
  - Reason dropdown: `Reduced load (remediated)` / `Equipment constraint` / `Railway instruction` / `Other`
  - Optional notes field
  - Operator confirms → override logged, button proceeds to submit
- Override logged to new `loading_overrides` table (see §8)
- Manager sees override entries flagged in their dashboard alert feed

**Toolbar status pills:** `✓ X OK` · `⚡ X Near` · `⚠ X Over` · `○ X Empty` — always visible above the table

---

### Phase 4 — Analytics & Root Cause (~2–3 weeks)

**Scope:** New "Penalty Analytics" page (manager + exec roles only).

**Sections:**
- **Penalty by Operator** — bar chart: which loader operator generates the most overload weight across shifts
- **Penalty by Shift** — 1st / 2nd / 3rd shift comparison
- **Penalty by Wagon Type** — BCN vs BOXN vs other penalty rate
- **Penalty by Siding & Route** — per-siding penalty ₹ breakdown
- **Preventability Analysis** — `is_preventable` ratio: what % of total penalty ₹ was avoidable
- **Dispute Management** — table of penalties with `disputed_at` / `dispute_reason` / `resolved_at` workflow: flag → reason → resolution

**Loader Performance Leaderboard** (operator-visible, gamification): ranked by lowest overload rate per shift.

All data from existing `penalties`, `wagon_loadings`, `wagons`, `loaders` tables — read-only queries.

---

### Phase 5 — Predictive AI & Optimization (~3–4 weeks)

**Scope:** Surface existing AI model data + Claude AI integration for loading recommendations.

- **Pre-loading recommendation panel** — before a rake starts loading, Claude AI analyses wagon types + historical loading data for that route + current siding stock and suggests target load per wagon type
- **PenaltyPrediction widget** — surfaces existing `penalty_predictions` table on dashboard and on rake detail pages
- **RrPrediction** — surfaces `rr_predictions` on Railway Receipt upload, shows predicted vs actual outcome
- **Automated weekly penalty report** — email via existing `database-mail` system, dispatched every Monday 08:00; report covers: ₹ total, preventable %, top 3 problem operators, comparison to prior week
- **Pattern learning** — historical overload rate per wagon type per siding fed into dashboard "AI risk" indicator

Claude AI integration uses `laravel/ai` SDK (already installed). All AI calls are additive new endpoints.

---

## 8. New Database Table

### `loading_overrides`

```
id                        bigint unsigned PK
wagon_loading_id          bigint unsigned FK → wagon_loadings.id
rake_id                   bigint unsigned FK → rakes.id
operator_id               bigint unsigned FK → users.id
reason                    enum('reduced_load','equipment_constraint','railway_instruction','other')
notes                     text nullable
overload_mt               decimal(8,3)
estimated_penalty_at_time decimal(10,2)
created_at                timestamp
```

No existing tables modified.

---

## 9. API Contract

**Rule:** No existing endpoint response shapes are modified. Existing mobile app routes are unaffected.

New routes added (following existing naming conventions):
- `POST /wagon-loadings/{wagonLoading}/override` — log advisory override
- `GET /dashboard` (existing) — receives additional Inertia props: `penaltySummary`, `riskScores`, `alerts`, `sidingStocks`
- New analytics routes added under `/penalty-analytics/*`
- New AI routes added under `/ai/*`

Existing `POST /wagon-loadings`, `GET /rake-loader/rakes/{rake}/loading`, and all other existing endpoints: response shapes unchanged.

---

## 10. Out of Scope

- Filament admin panel — excluded entirely
- Mobile / responsive breakpoints — desktop only
- Authentication system changes — existing Fortify setup unchanged
- Any existing API endpoint modification

---

## 11. Success Criteria

- Phase 1: BGR brand visible across all pages; "Rows per page" fixed; no Sign Up on login
- Phase 2: Dashboard shows live penalty exposure ₹, risk scores, active rake pipeline
- Phase 3: Operators see red rows + penalty ₹ before submitting; overload override logged with reason
- Phase 4: Manager can identify which operator/shift/wagon-type drives most penalties
- Phase 5: Pre-loading AI recommendation reduces average overload rate vs baseline

---

## 12. Visual Mockups

Brainstorming session mockups saved at:
- `.superpowers/brainstorm/6688-1777423843/content/phases-overview.html`
- `.superpowers/brainstorm/6688-1777423843/content/wagon-loading-v2.html` *(approved design)*
- `.superpowers/brainstorm/6688-1777423843/content/design-system.html` *(approved design)*
