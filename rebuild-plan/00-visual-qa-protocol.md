# Visual QA Protocol — Autonomous Browser MCP Checks

Chief runs this protocol after **every task** that produces Inertia pages. Uses **Playwright MCP** for interaction + screenshots, **browser-tools MCP** for console/network/accessibility audits. No human input required — all failures are self-healed or logged for the final report.

---

## Setup (run once per session, before Task 1)

### 1. Ensure dev server is running

```bash
# Terminal 1 (background)
php artisan serve --port=8000

# Terminal 2 (background)
bun run dev        # or: npm run dev
```

Verify: GET `http://localhost:8000` returns 200.

### 2. Login once, capture session

```
Navigate: http://localhost:8000/login
Fill:
  email    = APP_ADMIN_EMAIL from .env (or default: admin@example.com)
  password = APP_ADMIN_PASSWORD from .env (or default: password)
Click: Login button
Verify: redirected to /dashboard (not back to /login → wrong credentials)
```

If login fails: check `APP_ADMIN_EMAIL` / `APP_ADMIN_PASSWORD` in `.env`; if blank, run `php artisan db:seed --class=AdminUserSeeder` (or equivalent kit seeder).

Session is maintained in the browser across all checks below.

---

## Universal Checks (every page, every task)

Run these on every page visited during visual QA:

### U1 — No console errors
```
getConsoleErrors → must return []
```
If non-empty: read errors, identify source file/line, fix, reload, re-check.

### U2 — No network errors
```
getNetworkErrors → must return no 4xx/5xx responses
```
Exceptions: 404 on favicon (ignore). Any 500 = hard failure.

### U3 — Brand orange is applied
```
Snapshot page → check computed style of a primary button or link
Expected: CSS var --color-primary resolves to oklch near #f28036 (not blue #3b82f6)
```
DOM selector: `button.bg-primary` or `a.text-primary` — check computed color.

### U4 — Font is Geist Sans
```
Check computed font-family on body → must contain "Geist" (not "Inter", "Roboto", "Arial")
```

### U5 — Page title is set
```
document.title must not be "Laravel" or empty
Expected pattern: "[Page Name] – Fusion CRM"
```

### U6 — Accessibility score
```
runAccessibilityAudit → score must be ≥ 85
```
Auto-fix obvious issues (missing aria-labels on icon buttons). Score 70–84 = WARN (log, continue). Below 70 = FAIL (fix before advancing).

### U7 — No emoji icons in UI
```
Snapshot → search DOM for emoji characters in button/icon elements
Any found = WARN (log it, don't stop)
```

---

## Per-Task Visual QA Checklist

### After Task 1 — Step 0: Bootstrap

**Pages to check:** `/login`, `/dashboard`

| Check | Selector / Method | Pass condition |
|---|---|---|
| Login page loads | Navigate `/login` | 200, no console errors |
| Login form fields present | `input[name=email]`, `input[name=password]` | Both visible |
| Dashboard loads after login | Navigate `/dashboard` | 200, not redirected back to `/login` |
| Brand colors applied | `--color-primary` in `:root` | Orange (#f28036 equivalent), not blue |
| Dark sidebar or nav exists | `nav`, `aside[role=navigation]` | Element present |
| No 500 errors | getNetworkErrors | Empty |
| Screenshot | takeScreenshot | Save as `qa/task-01-dashboard.png` |

---

### After Task 2 — Step 3: Projects and Lots

**Pages to check:** `/projects`, `/projects` (grid view), one project detail

| Check | Selector / Method | Pass condition |
|---|---|---|
| `/projects` loads | Navigate | 200, no console errors |
| Grid layout rendered | `.grid` or `[data-layout=grid]` container | At least 1 project card visible |
| Project card has photo or fallback | `img` or gradient div inside card | Present, no broken images |
| Project card shows price | `[data-field=min_price]` or text containing `$` | Visible |
| "View Lots" button on card | `button:contains('View Lots')` or data-attr | Exists on ≥1 card |
| Lot slide-over opens | Click "View Lots" → sheet | `<div role="dialog">` or `[data-state=open]` appears |
| Lot table inside slide-over | Table with rows | ≥1 lot row visible |
| HasAi NLQ bar present | `input[placeholder*="Ask"]` or NLQ input | Visible on page |
| View toggle (grid/table) | Button group with grid/table icons | Visible |
| Screenshot: grid | takeScreenshot | Save `qa/task-02-projects-grid.png` |
| Screenshot: slide-over | takeScreenshot (after opening) | Save `qa/task-02-lot-slideover.png` |

---

### After Task 3 — Step 1: Contacts and Roles

**Pages to check:** `/contacts`, one contact detail `/contacts/{id}`

| Check | Selector / Method | Pass condition |
|---|---|---|
| `/contacts` loads | Navigate | 200, no errors |
| Smart List sidebar visible | Left panel, 220px wide, contains list items | Present and non-empty |
| Smart List has count badges | `span` with number next to list name | Visible on ≥1 item |
| DataTable renders | `table` or data-table container | Visible with rows |
| lead_score column visible | Column header text "Lead Score" or badge in row | Present |
| last_contacted_at column | Color-coded date badge in row | Present |
| Stage badge in row | Colored dot + stage name | Present |
| HasAi NLQ bar | Input field | Visible |
| `/contacts/{id}` loads | Navigate to first contact | 200, no errors |
| Unified timeline present | `[data-timeline]` or timeline container | Visible |
| **NO separate tab bar on contact record** | Must NOT find `[role=tablist]` with Email/Task/Note tabs | FAIL if tabs present |
| Filter chips on timeline | Chip group for filtering feed | Present |
| RHS summary panel | Right column with stage dropdown + quick actions | Visible |
| lead_score badge in header | Badge showing number | Present |
| Screenshot: contact list | takeScreenshot | Save `qa/task-03-contacts-list.png` |
| Screenshot: contact detail | takeScreenshot | Save `qa/task-03-contact-detail.png` |

---

### After Task 4 — Step 2: Users and Contact Link

**Pages to check:** `/users` (or Filament `/admin/users`)

| Check | Selector / Method | Pass condition |
|---|---|---|
| Users page loads | Navigate | 200, no errors |
| User row shows linked contact | Contact name/link in user row | Present if users imported |
| Screenshot | takeScreenshot | Save `qa/task-04-users.png` |

---

### After Task 5 — Step 4: Reservations, Sales, Commissions

**Pages to check:** `/reservations`, `/reservations/{id}`, `/sales`

| Check | Selector / Method | Pass condition |
|---|---|---|
| `/reservations` loads | Navigate | 200, no errors |
| ReservationDataTable renders | Table with rows | Visible |
| Stage badge column | Colored stage badge | Present |
| Deposit status badge | Pending/Paid/Failed badge | Present |
| `/reservations/{id}` loads | Navigate to first reservation | 200, no errors |
| Milestone timeline visible | Vertical stepper/timeline on left side | Present |
| 6 milestone steps | 6 steps: Enquiry→Qualified→Reservation→Contract→Unconditional→Settled | All 6 visible |
| Completed step is filled circle | Active step differentiated from pending | Visual distinction present |
| Details panel on right | Contact name, lot, agent fields | Visible |
| Commission field | Dollar amount visible | Present |
| `/sales` loads | Navigate | 200, no errors |
| SaleDataTable renders | Table with rows | Visible |
| commission_total column | Money-formatted column | Present |
| Screenshot: reservations list | takeScreenshot | Save `qa/task-05-reservations.png` |
| Screenshot: reservation detail | takeScreenshot | Save `qa/task-05-reservation-detail.png` |

---

### After Task 6 — Step 5: Tasks, Relationships, Marketing

**Pages to check:** `/tasks`, `/bot-in-a-box`, `/mail-list`, `/ad-management`

**Tasks page:**

| Check | Selector / Method | Pass condition |
|---|---|---|
| `/tasks` loads | Navigate | 200, no errors |
| Grouped view rendered | Groups: "Due Today", "Overdue", "Upcoming" | ≥1 group header visible |
| Task row shows priority badge | Colored priority badge | Present |
| Task row shows due date | Date in row | Present |
| Inline complete checkbox | `input[type=checkbox]` at row start | Present per row |
| Screenshot | takeScreenshot | Save `qa/task-06-tasks.png` |

**Bot-in-a-Box page:**

| Check | Selector / Method | Pass condition |
|---|---|---|
| `/bot-in-a-box` loads | Navigate | 200, no errors |
| Category sidebar present | Left panel with category list | ≥3 categories visible |
| Bot grid renders in main area | Grid of bot cards | ≥1 bot card per category |
| Bot card has name + description | Text inside card | Present |
| "Use Bot" button on card | `button:contains('Use Bot')` or equivalent | Present on ≥1 card |
| Click "Use Bot" navigates to run page | Navigate to `/bot-in-a-box/{bot}/run` | 200, no errors |
| Prompt chips visible on run page | Clickable chip/button group | ≥1 chip present |
| Context textarea present | `textarea` for custom context | Present |
| Real-time Data toggle present | Toggle/checkbox | Present |
| Generate button present | `button:contains('Generate')` or equivalent | Present |
| Click Generate → output appears | Click button, wait 3s | Output panel appears (even if streaming) |
| Output rendered as C1 (not plain `<p>`) | Output container uses C1 wrapper OR structured card | `<p>plain text</p>` alone = FAIL |
| Screenshot: bot list | takeScreenshot | Save `qa/task-06-bot-list.png` |
| Screenshot: bot run page | takeScreenshot (after Generate) | Save `qa/task-06-bot-run.png` |

**Mail List page:**

| Check | Selector / Method | Pass condition |
|---|---|---|
| `/mail-list` loads | Navigate | 200, no errors |
| DataTable renders | Table with rows or "empty" state | Present |
| Create button visible | `button:contains('Create')` or equivalent | Present |
| Screenshot | takeScreenshot | Save `qa/task-06-mail-list.png` |

**Ad Management page:**

| Check | Selector / Method | Pass condition |
|---|---|---|
| `/ad-management` loads | Navigate | 200, no errors |
| DataTable renders | Table with rows or "empty" state | Present |
| Ad toggle visible | `HasToggle` status column | Present |
| Screenshot | takeScreenshot | Save `qa/task-06-ads.png` |

---

### After Task 7 — Step 6: AI-Native Features

**Pages to check:** Any CRM page (check floating widget), try one agent interaction

| Check | Selector / Method | Pass condition |
|---|---|---|
| Floating AI button visible | `button[aria-label*="AI"]` or 56px circle in bottom-right | Present on any page |
| Chat panel opens | Click floating button | Sheet panel opens (400px wide) |
| Chat input visible | `textarea` or `input` in panel | Present |
| Suggested prompts visible | Chip row below input | ≥1 chip present |
| lead_score badge on `/contacts/{id}` | Badge with number 0–100 | Present |
| lead_score badge colored | Badge color class (red/amber/green based on value) | Not all same color |
| Contact assistant responds | Type "Hello" in chat → submit | Response appears (even if just "Hi") |
| Response is NOT plain text paragraph | Response rendered as C1 component OR structured card, not `<p>plain text</p>` | C1 component or card container present |
| Screenshot: chat open | takeScreenshot | Save `qa/task-07-ai-chat.png` |

---

### After Task 8 — Step 7: Reporting and Dashboards

**Pages to check:** `/dashboard`, `/reports`

| Check | Selector / Method | Pass condition |
|---|---|---|
| `/dashboard` loads | Navigate | 200, no errors |
| 4 KPI cards visible | 4 stat card elements | Present |
| KPI card has number + label | `[data-kpi]` or `.stat-card` | Present |
| Pipeline summary widget | Chart or Kanban mini view | Present |
| AI Insight card | `[data-ai-insight]` or Thesys C1 card | Present (even if loading) |
| NLQ bar on dashboard | Input field "Ask Fusion AI…" | Present |
| `/reports` loads | Navigate | 200, no errors |
| Report type cards visible | Grid of report cards | ≥1 card present |
| Report card has "Run" button | `button:contains('Run')` | Present |
| Open a report | Click first report | Chart + DataTable renders |
| Chart renders | `canvas` or SVG chart element | Present |
| DataTable renders under chart | Table element | Present |
| Export button | `button:contains('Export')` | Present |
| Screenshot: dashboard | takeScreenshot | Save `qa/task-08-dashboard.png` |
| Screenshot: report detail | takeScreenshot | Save `qa/task-08-report-detail.png` |

---

---

### After Task 9 — Step 14: Phase 1 Parity

**Pages to check:** `/spr`, `/spr-history`, `/favourites`, `/featured_projects`, `/potential-properties`, `/api-keys`

| Check | Selector / Method | Pass condition |
|---|---|---|
| `/spr` loads (SPR submit form) | Navigate | 200, no errors |
| SPR form has title + description + price | Form fields + "$55" price visible | Present |
| `/spr-history` loads | Navigate | 200, no errors |
| SPR history table renders | Table with rows or empty state | Present |
| SPR status badge | Pending/In Progress/Completed badge | Present if records exist |
| `/favourites` loads | Navigate | 200, no errors |
| Project cards render (favourited projects) | Grid of project cards OR empty state msg | Present |
| Heart icon toggle on project cards (from `/projects`) | Navigate `/projects`, heart icon button per card | Present |
| `/featured_projects` loads | Navigate | 200, no errors |
| Featured projects list or management UI | Table/list of projects with is_featured toggle | Present |
| `/potential-properties` loads | Navigate | 200, no errors |
| DataTable with CSV import button | Table + HasImport button | Both present |
| State filter chips (NSW/VIC/QLD etc.) | Filter chip row | ≥3 state chips visible |
| `/api-keys` loads | Navigate (admin or subscriber) | 200, no errors |
| API key table or create form | Table with key list OR create button | Present |
| U1–U7 on each page | Universal checks | All pass / warn logged |
| Screenshot: SPR form | takeScreenshot | Save `qa/task-09-spr.png` |
| Screenshot: potential properties | takeScreenshot | Save `qa/task-09-potential-props.png` |

---

### After Task 10 — Step 15: Phase 2 — AI Lead Generation

**Pages to check:** Any AI lead generation UI added in this step (e.g. lead scoring dashboard, automated nurture sequence UI)

| Check | Selector / Method | Pass condition |
|---|---|---|
| New pages from step file load | Navigate each new route | 200, no errors on each |
| Lead score update visible | Contact list shows updated scores | lead_score values not all null |
| AI lead nurture pipeline (if built) | Any nurture sequence UI or list | Present if route exists |
| AI-generated insights on contacts page | C1 card or badge on contact detail | Present if step spec includes it |
| HasAi NLQ on any new DataTable | NLQ input | Present on tables with HasAi |
| U1–U7 on each new page | Universal checks | All pass / warn logged |
| Screenshot: main new page for this step | takeScreenshot | Save `qa/task-10-ai-leads.png` |

---

### After Task 11 — Step 16: Phase 2 — AI Core and Voice

**Pages to check:** AI assistant panel (already built Task 7 — verify enhancements), voice input feature

| Check | Selector / Method | Pass condition |
|---|---|---|
| AI chat panel still opens | Click floating AI button | Sheet opens, 400px wide |
| Enhanced agent responses | Send "Show my hot leads" | C1 ContactCard list renders (not plain text) |
| Voice input button present (if VAPI_API_KEY set) | `button[aria-label*="voice"]` or mic icon in chat | Present; if VAPI key absent → log as deferred |
| Voice button activates mic (if key set) | Click mic button | Recording indicator appears OR permission prompt |
| Agent memory recall works | Send "What were we discussing about [known contact]?" | Agent references prior context OR graceful "I don't recall" |
| Memory stored after session interaction | Check `memories` table has rows for this user | `SELECT COUNT(*) FROM memories WHERE user_id = X` > 0 |
| U1–U7 on affected pages | Universal checks | All pass |
| Screenshot: chat with memory response | takeScreenshot | Save `qa/task-11-ai-voice.png` |

---

### After Task 12 — Step 17: Phase 2 — Property, Builder & Push Portal

**Pages to check:** `/flyers` (or equivalent), `/property-portal` (if built), property push notifications UI

| Check | Selector / Method | Pass condition |
|---|---|---|
| Flyer/marketing material list loads | Navigate `/flyers` or equivalent | 200, no errors |
| Flyer DataTable renders | Table with rows or empty state | Present |
| Flyer editor opens | Click create/edit → Puck editor loads | Puck canvas appears (NOT GrapesJS) |
| Puck editor has drag-drop canvas | `[data-puck-component]` or Puck root element | Present |
| Property portal (subscriber-facing) loads | Navigate portal route | 200, no errors |
| Project cards visible in portal | PropertyCard components | ≥1 card if data exists |
| Push notification management (if built) | Navigate admin push UI | 200, no errors |
| U1–U7 on each new page | Universal checks | All pass / warn logged |
| Screenshot: flyer editor | takeScreenshot | Save `qa/task-12-flyer-editor.png` |
| Screenshot: property portal | takeScreenshot | Save `qa/task-12-portal.png` |

---

### After Task 13 — Step 18: Phase 2 — CRM Analytics

**Pages to check:** Advanced analytics pages added in this step

| Check | Selector / Method | Pass condition |
|---|---|---|
| Analytics overview page loads | Navigate main analytics route | 200, no errors |
| ≥2 chart types render | `canvas` or SVG elements | ≥2 distinct chart elements |
| NLQ bar on analytics | Input field for natural language query | Present |
| "Ask AI" query returns C1 chart | Type "Show sales by agent last quarter" → submit | C1 chart component renders (not plain text) |
| Date range filter | Date picker or range selector | Present |
| DataTable below chart | Table element | Present |
| Export button | `button:contains('Export')` | Present |
| HasAi visualize tab | Click "Visualize" tab on any DataTable | Chart renders |
| U1–U7 on new pages | Universal checks | All pass |
| Screenshot: analytics dashboard | takeScreenshot | Save `qa/task-13-analytics.png` |

---

### After Task 14 — Step 19: Phase 2 — Marketing and Content

**Pages to check:** `/campaign-websites` (or `/landing-pages`), mail campaign UI, any new content pages

| Check | Selector / Method | Pass condition |
|---|---|---|
| Campaign/landing page list loads | Navigate | 200, no errors |
| Form-based editor opens | Click create/edit landing page | Multi-section form with CKEditor loads |
| CKEditor rich text visible | `.ck-editor` or `[contenteditable]` | Present in editor |
| Color picker inputs present | Color input or color swatch picker | Present |
| Short link field visible | TinyURL field or "Generate Short Link" button | Present |
| Project association selector | Multi-select for projects | Present |
| Campaign DataTable | Table with campaign rows | Present |
| Mail List referenced in campaign | Campaign create form includes mail list selector | Present |
| U1–U7 on new pages | Universal checks | All pass |
| Screenshot: landing page editor | takeScreenshot | Save `qa/task-14-landing-editor.png` |

---

### After Task 15 — Step 20: Phase 2 — Deal Tracker

**Pages to check:** Deal tracker UI (Kanban or board view of deals/reservations pipeline)

| Check | Selector / Method | Pass condition |
|---|---|---|
| Deal tracker page loads | Navigate deal tracker route | 200, no errors |
| Kanban columns or pipeline board render | `[data-column]` or kanban column elements | ≥3 stage columns visible |
| Deal cards in columns | Cards with contact name + lot/project | ≥1 card if data exists |
| Drag-to-move (if implemented) | Card is draggable (`draggable` attr or drag handle) | Present |
| Stage badge on each card | Colored stage indicator | Present |
| Filter bar (if present) | Filter by agent/project | Present |
| Click card → detail loads | Click deal card | Detail panel OR page opens |
| U1–U7 on new pages | Universal checks | All pass |
| Screenshot: deal tracker board | takeScreenshot | Save `qa/task-15-deal-tracker.png` |

---

### After Task 16 — Step 21: Phase 2 — Websites and API

**Pages to check:** `/websites` (subscriber site management), `/website-index`, API key management

| Check | Selector / Method | Pass condition |
|---|---|---|
| `/websites` or website index loads | Navigate | 200, no errors |
| Site slots visible (up to 5: 2 PHP + 3 WP types) | Site slot cards or rows | ≥1 slot shown |
| Site status badge | Active/Inactive/Provisioning badge | Present per slot |
| Create/provision site button | Button visible | Present |
| WordPress site type selector | Dropdown with `wp_real_estate`, `wp_wealth_creation`, `wp_finance` | Present in create form |
| API keys page loads | Navigate `/settings/api-keys` | 200, no errors |
| Create API key button | Button present | Present |
| Key token displayed once on create | "Copy token" UI after create | Check exists in UI flow |
| `/all-ads` or `/other-services` loads | Navigate | 200, no errors |
| Active ads gallery renders | Image cards | ≥1 ad card if ads exist |
| U1–U7 on new pages | Universal checks | All pass |
| Screenshot: website management | takeScreenshot | Save `qa/task-16-websites.png` |
| Screenshot: API keys | takeScreenshot | Save `qa/task-16-api-keys.png` |

---

### After Task 17 — Step 22: Phase 2 — Xero Integration

**Pages to check:** Xero settings/integration page, commission sync status

| Check | Selector / Method | Pass condition |
|---|---|---|
| Xero integration settings page loads | Navigate Xero settings route | 200, no errors |
| "Connect to Xero" button present | OAuth connect button | Present if not yet connected |
| Xero sync status indicator | Last synced timestamp OR connection status | Present |
| Commission records show Xero status | Xero invoice status column on commissions | Present (even if "Not synced") |
| Manual sync trigger button | "Sync now" button | Present |
| U1–U7 on Xero pages | Universal checks | All pass |
| Screenshot: Xero integration | takeScreenshot | Save `qa/task-17-xero.png` |

---

### After Task 18 — Step 23: Phase 2 — Signup and Onboarding

**Pages to check:** `/register`, `/onboarding` (or onboarding wizard), subscription plan selection

| Check | Selector / Method | Pass condition |
|---|---|---|
| `/register` loads | Navigate (logged out) | 200, no errors |
| Signup form fields present | name, email, password, org name fields | All present |
| Honeypot field present (hidden) | `input[style*="display:none"]` or `input[tabindex="-1"]` | Present (spam protection) |
| Plan selection step | After signup → plan picker | 3 plan cards (Starter/Growth/Enterprise) with prices visible |
| Onboarding wizard loads | `/onboarding` or wizard route | 200, no errors |
| Wizard steps visible | Step indicators or progress bar | ≥2 steps shown |
| CRM details step present | Company/profile setup form | Present |
| First project/contact setup step | Guided creation form | Present |
| Feature flags enabled per plan | Subscribe to "Growth" → AI features visible; "Starter" → AI hidden | Feature visibility matches plan tier |
| U1–U7 on signup/onboarding pages | Universal checks | All pass |
| Screenshot: signup page | takeScreenshot | Save `qa/task-18-signup.png` |
| Screenshot: onboarding wizard | takeScreenshot | Save `qa/task-18-onboarding.png` |
| Screenshot: plan selection | takeScreenshot | Save `qa/task-18-plans.png` |

---

### After Task 19 — Step 24: Phase 2 — R&D and Special

**Pages to check:** Any new pages introduced in this step (finance assessment form, login history, same device detection report)

| Check | Selector / Method | Pass condition |
|---|---|---|
| Finance assessment form loads | Navigate `/forms/finance-assessment` (public) | 200, no errors (no auth required) |
| Finance form fields present | employment_type, income, deposit_available fields | Present |
| Form submission creates record | Submit with test data | 201/redirect, no 500 |
| Login history report loads | Navigate login history report | 200, no errors |
| Login events table renders | Rows with user, ip, device, login_at | Present |
| Same device detection report | Navigate same-device report | 200, no errors |
| Flagged device rows visible | Rows where 2+ users share fingerprint (or empty state) | Table renders |
| Network activity report | Navigate network activity report | 200, no errors |
| U1–U7 on new pages | Universal checks | All pass |
| Screenshot: finance assessment | takeScreenshot | Save `qa/task-19-finance-form.png` |
| Screenshot: login history | takeScreenshot | Save `qa/task-19-login-history.png` |

---

### After Task 20 — Two-Way Sync Infrastructure

**No Inertia pages** — verify Artisan commands exist and dry-run succeeds.

| Check | Method | Pass condition |
|---|---|---|
| Sync command importable (MySQL→PG) | `php artisan list` → grep `fusion:sync-mysql-to-postgres` | Command listed |
| Sync command importable (PG→MySQL) | `php artisan list` → grep `fusion:sync-postgres-to-mysql` | Command listed |
| Dry-run MySQL→PG executes | `php artisan fusion:sync-mysql-to-postgres --dry-run` | Exit 0, "Dry run" in output, no exception |
| Dry-run PG→MySQL executes | `php artisan fusion:sync-postgres-to-mysql --dry-run` | Exit 0, "Dry run" in output, no exception |
| Conflict resolution log output | Either dry-run output | At least 1 log line about conflict strategy ("last-write-wins" or similar) |
| Scheduler entry registered | `php artisan schedule:list` → grep `sync` | Entry listed at 30min interval |
| `composer test:quick` | Run full test suite | All pass (or failures logged from prior tasks) |

---

## Self-Healing Protocol

When any check FAILS, run this sequence before advancing:

```
1. getConsoleErrors → read full error message and stack trace
2. If PHP error: read storage/logs/laravel.log (last 50 lines)
3. If JS error: identify component file from stack trace
4. Fix the identified issue (missing route, undefined prop, broken import, etc.)
5. Reload the page
6. Re-run the specific failing check
7. If still failing: attempt 2 (different fix approach)
8. If still failing: attempt 3 (minimal workaround — e.g. stub the component)
9. If failing after 3 attempts:
   - Log: "VISUAL QA FAIL — Task N — [check name] — [error] — 3 attempts exhausted"
   - Take screenshot, save as qa/FAIL-task-N-[check].png
   - Continue to next task (do NOT stop for human review)
   - Include in final report
```

### Common failure patterns and fixes

| Symptom | Likely cause | Fix |
|---|---|---|
| Page shows 500 | Missing migration / route | Run `php artisan migrate` + check route is registered |
| Page redirects to /login | Middleware not whitelisted | Check `RouteServiceProvider` / auth middleware config |
| DataTable empty (no rows) | Import not run or seed missing | Run `php artisan db:seed` or re-run import command |
| Chart not rendering | Missing JS dependency / env var | Check `bun run build` output; check `THESYS_API_KEY` |
| C1 renders plain text | Missing `@thesys/client` install or wrong env | Run `bun add @thesys/client`; check `THESYS_API_KEY` |
| Smart List sidebar missing | Component not added to Contacts page | Add `<SmartListSidebar>` to contacts layout |
| lead_score always null | Score job not run | Run `php artisan app:compute-lead-scores` (or equivalent) |
| Font is Inter/Roboto not Geist | `THEME_FONT` not applied to CSS | Check Step 0 CSS import |

---

## Screenshot Storage

All screenshots saved to `storage/app/qa/` (relative to kit root). Create directory in Step 0:

```bash
mkdir -p storage/app/qa
```

Screenshots are attached to the final human report.

---

## Final Report Format

After all 20 tasks complete, compile this report for human sign-off:

```
## Fusion CRM Rebuild — Visual QA Final Report

### Summary
✅ Tasks passed QA:  [n]/20
⚠️  Tasks with warnings: [n]
❌ Tasks with self-healed failures: [n] (details below)
❌ Tasks with unresolved failures: [n] (requires human review)

### Page screenshots
[list each qa/*.png with one-line status]

### Self-healed failures (resolved autonomously)
- Task N: [check] → [fix applied]

### Unresolved failures (need human review)
- Task N: [check] → [error] → [3 attempts, all failed] → [screenshot: qa/FAIL-*.png]

### Warnings (functional but not ideal)
- Task N: [check] → [warning detail]

### Functional test summary
composer test:quick results: [pass/fail + test count]

### Next steps for human
[list any unresolved failures with suggested manual fix]
```

This report is the **second and final** human interaction gate.
