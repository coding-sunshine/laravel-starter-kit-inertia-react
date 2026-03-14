# Step 7: Reporting and Dashboards

## Goal

Add **reporting and dashboard** features: KPIs by role (admin, sales agent, BDM, etc.), reservation reports, task reports, sales/commission reports, and optional exports. Use starter kit patterns (DataTables, Filament, Inertia) and avoid duplicating legacy report logic where a simpler modern approach suffices.

## Suggested implementation order

1. **Dashboard**: Add dashboard route + Inertia page; KPI queries (contacts by stage, open tasks, reservations this month, sales pipeline); role-aware widgets.
2. **Reports**: Reservation report (DataTable or Filament + filters + export); task report; sales/commission report; contact/lead report.
3. **Optional**: AI-generated dashboard insight (Prism or Laravel AI, cached).

## Starter Kit References

- **DataTables**: `docs/developer/backend/data-table.md` — exports, filters, summary rows
- **Filament**: `docs/developer/backend/filament.md` — widgets, custom pages
- **Inertia**: Dashboard page pattern; share data via controller or Inertia middleware
- **Laravel Excel**: `docs/developer/backend/laravel-excel.md` — if exports use maatwebsite/excel
- **Search & data**: `docs/developer/backend/search-and-data.md` — DTOs, filters

## UI Specification

### Dashboard Layout (inbox-first, 3-column)

```
┌──────────────────────────────────────────────────────┐
│  KPI row (full width, 4 cards)                       │
├──────────┬──────────────────────┬────────────────────┤
│ Today's  │ Pipeline Summary     │ AI Insight         │
│ Tasks    │ (Kanban mini view    │ (Thesys C1         │
│ (sorted  │ or funnel chart)     │ rendered card)     │
│ priority)│                      │                    │
├──────────┼──────────────────────┴────────────────────┤
│ Recent   │ Activity Feed (contacts + projects)        │
│ Contacts │                                             │
└──────────┴─────────────────────────────────────────────┘
```

**KPI cards** (4, top row): New Contacts This Week | Active Reservations | Settled This Month ($) | Tasks Overdue. Each card: large number, delta vs last period (↑/↓ with color), sparkline (7-day).

**Pipeline Summary widget**: Mini Kanban columns (stages) with count + total value per stage. Or horizontal funnel chart. Role-aware: admin sees all agents; sales agent sees own pipeline only.

**AI Insight card** (right column): Rendered by Thesys C1. Updated daily by `DashboardInsightAgent` scheduled job. Shows: top contacts to follow up, stalled reservations, newly available lots matching buyer criteria. One-click actions from inside the card.

**NLQ bar** (below KPI row, full width): Text input "Ask Fusion AI…" → `DashboardNlqAgent` → C1 renders result chart or table inline below the bar. Examples: "Show me all reservations settling next month", "Which agents have the most overdue tasks?"

### Report Viewer Layout (full-page per report type)

```
┌──────────────────────────────────────────────────────┐
│  [Report Title]  Date range filter  [Export ▼]  [Save View]│
├──────────────────────────────────────────────────────┤
│  Chart (bar/line/pie based on report type)           │
│  Height: 240px, responsive                           │
├──────────────────────────────────────────────────────┤
│  ReportDataTable (filters, NLQ bar, HasAi)           │
└──────────────────────────────────────────────────────┘
```

**Reports index**: Card grid of available report types. Each card: icon, report name, last-run date, "Run" button. "Run AI Query" NLQ bar at top of index page.

**Export options**: CSV, Excel (maatwebsite/excel), PDF (spatie/laravel-pdf). Export respects current filters.

**Save View**: Saves current date range + filters as a named view to `saved_views` (or data_table_saved_views if kit has it).

### DataTable Configuration — ReportDataTable (dynamic, per report type)

**Traits**: `HasExport, HasAi` (all 6 AI handlers enabled — primary NLQ interface for power users)

Columns are **dynamically defined** per report type at controller instantiation. `tableAiSystemContext()` is set at runtime per report type. Example for Sales/Commission report:

```php
$table->setAiSystemContext('You are analyzing a property sales and commission report for management.
Key fields: sale_price, commission_total, agent, settled_at, project.
Calculate totals, compare agent performance, and identify commission trends.');
```

**`tableAiQuickPrompts()`** (for all ReportDataTables):
```php
return [
    'Show total commission by agent this month',
    'Which projects have the highest settlement rate?',
    'Show me overdue finance conditions',
    'Compare this month vs last month for new reservations',
];
```

## Deliverables

1. **Dashboard**
   - **Inertia dashboard page**: Widgets for KPIs (e.g. contacts by stage, open tasks, reservations this month, sales pipeline value). Data from Eloquent aggregates or small action classes. Role-aware: show different widgets for admin vs sales agent vs BDM (use Spatie permissions or roles).
   - **Filament dashboard** (optional): Replicate or link to same KPIs in Filament admin; use Filament widgets (stats, chart).
   - **Reverb (WebSockets)**: Configure Reverb env; import Echo in frontend. Define broadcast events: **TaskAssigned**, **ReservationStatusChanged**, **SaleStageChanged**, **ContactCreated**. Add Echo listeners on task list, dashboard, reservation pages. See 00-kit-package-alignment.md.
   - **Email (laravel-database-mail)**: Create **CommissionUpdatedEvent**; register in `config/database-mail.php`. See 00-kit-package-alignment.md.

2. **Reports**
   - **Reservation report**: DataTable or Filament table — list reservations with filters (date range, agent, project), export CSV/Excel. Use HasExport if using kit’s DataTable.
   - **Task report**: List tasks with filters (assigned, status, date); completion rate or overdue count. Export optional.
   - **Sales / Commission report**: List sales with commission breakdown; filter by client, agent, date. Optional: summary by contact (total commission per agent). Export optional.
   - **Contact report**: Contacts by type, stage, source; optional funnel view (count by stage).
   - **Mail Status report** (uses `backstage/laravel-mails` `SentMail` model — no new table needed): Columns: recipient, subject, sent_at, status (sent/opened/bounced/failed), opened_at, mailable_type. Filter: date range, status, sent_by user. `tableAiSystemContext()`: `’You are analyzing email delivery analytics for a CRM. Help identify bounce patterns, engagement rates, and delivery failures.’`
   - **Network Activity report** (uses `spatie/laravel-activitylog` `Activity` model): Columns: user, action (description), subject_type, subject_id, ip_address, created_at. Filter: user, action type, date range.
   - **Notes History report**: Query `notes` table. Columns: contact (linked), note_preview (truncated), author (avatar_text), created_at. Filter: author, date range, note type.
   - **Log In History report**: Requires `login_events` table — add migration in this step: `id, user_id (FK), ip_address, user_agent, device_fingerprint (string 64, hashed SHA256), created_at`. Columns: user, ip, device (parsed from user_agent), login_at.
   - **Same Device Detection report** (fraud detection): Uses `login_events.device_fingerprint`. Query: find device fingerprints shared by 2+ distinct users within 30 days. Columns: fingerprint (masked), users (count), user list, last_seen. Frontend fingerprinting: `@fingerprintjs/fingerprintjs` (free OSS) — add via bun; hash and store on login.
   - **Approved API Keys report**: Query `personal_access_tokens` (or `api_key_requests` if using that approach) where `status = active`. Columns: user, key name, last_used_at, approved_at, scopes.
   - **Landing Page report** (campaign websites): Query `campaign_websites` + a future `campaign_website_visits` table (log page views per campaign). Columns: campaign name, page views, form submissions, conversion rate. If visit tracking not yet built, use placeholder with note "requires visit tracking middleware on `/c/{slug}`".
   - **Commission report** (separate from Sales report): Commission records grouped by type and agent. Uses `commissions` table from Step 4. Filter: commission_type, agent, date range, project. Analytics: total by type, total by agent.

3. **Implementation**
   - Each report: Backend = DataTable class or Filament resource with filters; or dedicated controller returning Inertia page with report data. Frontend = Inertia page with table + filters + export button, or Filament report page.
   - Use existing Contact, Task, PropertyReservation, Sale models and contact_id-based relations.
   - **LoginEventListener**: Create `app/Listeners/LoginEventListener.php`; bind to `Illuminate\Auth\Events\Login` in `EventServiceProvider::$listen`; captures `$event->user->id`, `request()->ip()`, `request()->userAgent()`. Device fingerprint arrives separately via `POST /api/login-event` (no auth required, rate-limited 10/min, stores SHA-256 hash of the fingerprint param into the most recent `login_events` row for that user within the last 60 seconds).
   - **Scheduler**: Register `DashboardInsightJob` in `routes/console.php` (or `Kernel::schedule`): `Schedule::job(DashboardInsightJob::class)->dailyAt('06:00');` — this is the scheduled job that powers the AI Insight card defined in Step 6.

4. **AI enhancements (optional)**
   - Use Prism or Laravel AI to generate a short “insight” summary for the dashboard (e.g. “3 high-value leads need follow-up”) from aggregated data. Can be a small prompt in the dashboard controller or a scheduled job that caches result.

## DB Design (this step)

- **`login_events`** table (new, required for Log In History and Same Device Detection reports):
  ```php
  Schema::create('login_events', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('ip_address', 45)->nullable();
      $table->text('user_agent')->nullable();
      $table->string('device_fingerprint', 64)->nullable()->index(); // SHA-256 hashed
      $table->timestamp('created_at')->useCurrent();
  });
  ```
  Populated by a `LoginEventListener` bound to Laravel's `Login` event in `EventServiceProvider`. Frontend sends device fingerprint via `@fingerprintjs/fingerprintjs` on each login → POSTed to `POST /api/login-event` endpoint.

- **`saved_views`** (optional): DataTable saved views config if kit has `data_table_saved_views`.
- All other reporting reads from existing contacts, tasks, property_reservations, sales, commissions, activity_log, memories.

## Data Import

- None. Reporting reads from already-imported data.

## AI Enhancements

- **Dashboard insight (recommended)**: Use Prism or Laravel AI to generate a short “insight” or recommendation (e.g. “3 high-value leads need follow-up”, “2 sales pending finance this week”) from dashboard KPI data. Call from dashboard controller; cache result for 5–15 minutes to limit API usage. Expose as a small card or banner on the dashboard.
- *More ideas (natural language report query, anomaly hint):* **13-ai-native-features-by-section.md** § Step 7.

## Verification (verifiable results)

- No new data import. Verify: dashboard and report pages load; KPI totals match aggregated data from imported tables (e.g. total contacts, total sales, total reservations). Spot-check that report filters and exports use new schema (contact_id). See **11-verification-per-step.md** § Step 7.

## Human-in-the-loop (end of step)

**End of plan. Human must confirm the rebuild is complete.**

Human must:
- [ ] Confirm dashboard loads and shows role-appropriate KPIs (contacts, tasks, reservations, sales).
- [ ] Confirm at least two report views (e.g. reservations, sales) work with filters and optional export.
- [ ] Confirm dashboard/report numbers are consistent with imported data (spot-check).
- [ ] Confirm AI dashboard insight (if implemented) displays without error.
- [ ] Sign off on rebuild completion or list remaining follow-ups.

## Acceptance Criteria

- [ ] Dashboard shows role-appropriate KPIs (at least contacts, tasks, reservations, sales).
- [ ] At least two report views (e.g. reservations, sales) with filters and optional export.
- [ ] All report data uses new schema (contact_id, no lead_id).
- [ ] Dashboard/report numbers are consistent with imported data (verifiable by manual or automated check).
- [ ] `login_events` migration runs; `LoginEventListener` is registered in `EventServiceProvider` bound to `Illuminate\Auth\Events\Login`; login creates a row in `login_events`.
- [ ] `POST /api/login-event` endpoint accepts `device_fingerprint` and stores SHA-256 hash in `login_events.device_fingerprint`.
- [ ] Log In History report loads and shows login rows for the authenticated user.
- [ ] Same Device Detection report loads and correctly flags fingerprints shared by 2+ distinct users within 30 days.
- [ ] Mail Status report loads using `SentMail` model; status filter (sent/opened/bounced/failed) works.
- [ ] Commission report loads with filter by `commission_type`; totals by type and agent are correct.

**UI acceptance criteria (verified by Visual QA protocol — Task 8 checks):**
- [ ] `/dashboard` renders with exactly 4 KPI stat cards in top row (not a plain list)
- [ ] Each KPI card shows a number + label + delta indicator (↑/↓ vs prior period)
- [ ] Pipeline Summary widget visible (chart or Kanban mini)
- [ ] AI Insight card visible (rendered via Thesys C1, not plain text paragraph)
- [ ] NLQ bar "Ask Fusion AI…" present on dashboard
- [ ] `/reports` renders report type cards in a grid (not a plain list)
- [ ] Opening a report shows chart above DataTable
- [ ] Export button visible on report page
- [ ] Date range filter visible and functional on report page
- [ ] Dashboard is role-aware: agent sees own pipeline; admin sees all (verify by logging in as different roles)
