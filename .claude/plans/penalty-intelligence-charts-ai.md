# PRD: Penalty Intelligence, Charts & AI Insights

## Goal
Reduce penalty costs through data visibility, root-cause attribution, trend analysis, and AI-powered insights. Replace hand-coded HTML charts with a proper charting library. Build the empty reports page. Make the AI assistant smarter about penalties.

---

## Phase 1: Foundation (must come first)

Everything else depends on these pieces being in place.

### 1.1 Install Recharts
- `npm install recharts`
- Recharts is React-native, tree-shakeable, works with React 19
- Create reusable chart wrapper components in `resources/js/components/charts/`:
  - `area-chart.tsx` — time-series line/area charts (penalties over time)
  - `bar-chart.tsx` — categorical comparisons (siding vs siding)
  - `pie-chart.tsx` — proportional breakdowns (penalty types)
  - `stat-card.tsx` — enhanced stat card with sparkline

### 1.2 Penalty Schema Enhancement
- Migration: add to `penalties` table:
  - `responsible_party` — enum string: `railway`, `siding`, `transporter`, `plant`, `other` (nullable, default null)
  - `root_cause` — text (nullable) — free-text description of what caused the penalty
  - `disputed_at` — timestamp (nullable)
  - `dispute_reason` — text (nullable)
  - `resolved_at` — timestamp (nullable)
  - `resolution_notes` — text (nullable)
- Update `Penalty` model casts and fillable
- Update `PenaltyFactory` with new fields

### 1.3 SidingPerformance Aggregation
- Create `SidingPerformance` model for the existing `siding_performance` table
- Create `AggregateSidingPerformance` action that:
  - Runs for each siding, for a given date
  - Counts rakes processed, sums penalty amounts, counts penalty incidents
  - Calculates average demurrage hours, overload incidents, closing stock
  - Uses `updateOrCreate` on the unique `[siding_id, as_of_date]` constraint
- Create `rrmcs:aggregate-performance` artisan command
- Schedule it daily at 00:30 in `routes/console.php`
- Backfill: command accepts `--from` and `--to` date flags to seed historical data

### 1.4 Enhance ChatContextBuilder
- Add `penaltyBreakdownBySiding(array $sidingIds): string`
  - Top 3 penalty types per siding this month
  - Total penalty amount per siding this month
- Add `sidingPerformanceSummary(array $sidingIds): string`
  - Uses `SidingPerformance` model for last 7 days
  - Avg demurrage hours, total penalties, rakes processed

### 1.5 Wire GenerateReports to Routes
- Add routes in `routes/web.php`:
  - `GET /reports/daily-operations/{siding}` → `GenerateReports@dailyOperationsSummary`
  - `GET /reports/demurrage-analysis/{siding}` → `GenerateReports@demurrageAnalysisReport`
  - `GET /reports/financial-impact/{siding}` → `GenerateReports@financialImpactReport`
  - `GET /reports/rake-lifecycle/{siding}` → `GenerateReports@rakeLifecycleReport`
  - `GET /reports/indent-fulfillment/{siding}` → `GenerateReports@indentFulfillmentReport`
- OR: add these as additional `report_key` options in the existing `RunReportAction` + `ReportsController`

**Deliverable**: Chart library ready, penalty schema enriched, performance data flowing, AI context richer, report endpoints available.

---

## Phase 2: Dashboard Overhaul

Replace the hand-coded HTML "charts" with real Recharts visualizations. Add penalty intelligence cards.

### 2.1 New Dashboard Controller Data
Add to `ExecutiveDashboardController`:
- `penaltyTrend` — last 12 months, monthly totals (for area chart)
- `penaltyByType` — grouped by `penalty_type`, summed amounts (for pie chart)
- `penaltyBySiding` — grouped by siding, summed amounts (for bar chart)
- `costAvoidance` — rakes within free time vs rakes with penalties this month, amounts
- `financialImpact` — YTD total, projected annual (extrapolate), cost-per-rake avg, worst siding

### 2.2 Dashboard Layout Changes (dashboard.tsx)
Replace the existing penalty HTML bar chart section with:

**Row 1: Enhanced Stat Cards** (keep existing 4 + add 2)
- Existing: Active Rakes, Pending Indents, Penalties This Month, Vehicles Today
- New: **Money Saved** (cost avoidance — rakes that didn't incur penalties)
- New: **Avg Demurrage Hours** (this month vs last month trend)

**Row 2: Penalty Trend Chart**
- Area chart (Recharts) — 12-month penalty amounts
- Hover tooltips with month + amount
- Optional: overlay line showing rake count for correlation

**Row 3: Two-column**
- Left: **Penalty by Type** pie/donut chart
- Right: **Penalty by Siding** horizontal bar chart (sorted desc by amount)

**Row 4: Financial Impact Summary**
- Cards: YTD Total Penalties, Projected Annual Cost, Cost Per Rake, Worst Siding
- Color-coded: red if trending up, green if trending down vs last period

### 2.3 Keep Existing Features
- Live demurrage timers — keep as-is
- Alerts banner — keep as-is
- Quick actions — keep as-is
- Siding stock section — keep, but enhance with sparkline from `siding_performance.closing_stock_mt`

**Deliverable**: Dashboard with real charts, penalty intelligence at a glance, cost avoidance visibility.

---

## Phase 3: Penalty Intelligence Page

Enhance the penalties section with root-cause analysis and responsibility tracking.

### 3.1 Penalty Root Cause Dashboard
- New page or tab at `/penalties/analytics`:
  - **Summary cards**: Total penalties, by status (pending/incurred/waived/disputed), dispute success rate
  - **Root cause breakdown**: Bar chart grouped by `responsible_party`
  - **Type distribution**: Pie chart by `penalty_type`
  - **Siding comparison**: Multi-bar chart — penalties per siding, colored by type
  - **Time heatmap**: Day-of-week × time-of-day showing when penalties cluster
  - **Top offenders table**: Rakes/sidings with highest cumulative penalties

### 3.2 Responsibility Attribution UI
- On `/penalties` index, add:
  - Inline dropdown for `responsible_party` (railway/siding/transporter/plant/other)
  - Click-to-edit `root_cause` text field
  - Both save via PATCH endpoint
- New controller method: `PenaltyController@update` (PATCH `/penalties/{penalty}`)
- Form Request: `UpdatePenaltyRequest` — validates `responsible_party` enum + `root_cause` string

### 3.3 Penalty Trend Analysis
- On the analytics page:
  - Month-over-month line chart with configurable date range
  - Siding comparison toggle (overlay multiple sidings)
  - Rolling average line (3-month)
  - Percentage change badges (vs previous period)

### 3.4 Drill-Down Navigation
- Dashboard charts link to filtered penalty list:
  - Click pie slice → `/penalties?type=DEM`
  - Click siding bar → `/penalties?siding_id=5`
  - Click month on trend → `/penalties?from=2026-01-01&to=2026-01-31`

**Deliverable**: Full penalty intelligence — who, what, when, why — with actionable drill-downs.

---

## Phase 4: Reports Page

Build the empty `resources/js/pages/reports/index.tsx`.

### 4.1 Reports Index Page
- Tab or sidebar navigation for report types:
  - Daily Operations, Stock Movement, Rake Lifecycle, Demurrage Analysis, Indent Fulfillment, Financial Impact, Penalty Register, plus the existing RunReportAction types
- Universal controls: date range picker, siding selector, "Generate" button
- Results area: data table + relevant chart visualization
- Export: CSV download button (existing backend support)

### 4.2 Report Visualizations
Each report type gets a chart:
- **Demurrage Analysis**: Stacked bar (collected vs pending by month)
- **Financial Impact**: Waterfall chart (revenue impact breakdown)
- **Rake Lifecycle**: Gantt-style timeline per rake
- **Indent Fulfillment**: Progress bars with fulfillment % + donut for overall rate
- **Penalty Register**: Same charts as Phase 3 analytics, scoped to selected date range
- **Stock Movement**: Area chart showing opening/closing balance over time

### 4.3 Scheduled Reports (stretch)
- Allow users to save report configs and receive periodic email exports
- Uses existing `database-mail` system for templates

**Deliverable**: Fully functional reports page with charts, tables, and CSV export.

---

## Phase 5: AI Intelligence

Make the AI proactively helpful with penalty insights.

### 5.1 AI Daily Briefing
- New `GenerateDailyBriefing` action:
  - Collects: rakes processed yesterday, penalties incurred, demurrage total, pending indents, alerts
  - Sends to AI (PrismService) with a structured prompt
  - Returns 3-4 sentence summary
- Injected as a **deferred prop** on dashboard via `ExecutiveDashboardController`
- Frontend: card at top of dashboard with AI summary, skeleton/pulse while loading
- Cache the briefing per-user for 1 hour (or until new penalty/rake activity)

### 5.2 "Ask About This Penalty" Button
- Add button to each penalty row in `/penalties` index
- On click: opens chat widget with pre-filled message:
  `"Explain penalty #{id} (type: {type}, amount: ₹{amount}, rake: {rake_number}, siding: {siding_name}) and suggest how to avoid this in the future."`
- Enhancement: the `ChatContextBuilder` for that message also includes the `calculation_breakdown` JSON for the specific penalty
- Requires: `ChatWidget` to accept an external trigger (exposed via a custom event or shared state)

### 5.3 AI Penalty Insights (Scheduled)
- New `GeneratePenaltyInsights` action:
  - Runs weekly (scheduled command)
  - Aggregates: penalties by siding, by type, by day-of-week, trend vs previous period
  - Sends structured data to AI with prompt: "Analyze these penalty patterns and provide 3-5 actionable recommendations"
  - Stores result as a `Notification` or dedicated `AiInsight` model
- Frontend: "AI Insights" card on penalties analytics page
- Users can dismiss or act on each recommendation

### 5.4 Enhanced Chat Context (already started in Phase 1.4)
- Add to `ChatContextBuilder`:
  - Penalty trend (up/down vs last month, % change)
  - Top penalty type this month
  - Siding with highest penalties
  - Recent dispute outcomes
- This makes conversational queries like "Why are penalties up this month?" answerable with real data

### 5.5 Proactive Demurrage Warning (stretch)
- If user opens chat on `/rakes` and any rake has ≤30 mins free time remaining:
  - Chat widget shows a banner above the input: "⚠️ Rake #XYZ has 22 mins of free time left at Siding A"
  - Clicking it sends a pre-filled message asking the AI for mitigation options
- Data source: reuse the `loadingRakesWithRemainingTime` from `ChatContextBuilder`

**Deliverable**: AI that proactively surfaces penalty patterns, explains individual penalties, and warns about imminent demurrage.

---

## Dependency Graph

```
Phase 1 (Foundation)
  ├── 1.1 Recharts ──────────────┐
  ├── 1.2 Penalty Schema ────────┤
  ├── 1.3 SidingPerformance ─────┤
  ├── 1.4 ChatContext ───────────┤
  └── 1.5 Report Routes ────────┤
                                 │
  ┌──────────────────────────────┘
  │
  ├── Phase 2 (Dashboard) ─── depends on 1.1, 1.2, 1.3
  ├── Phase 3 (Penalty Intel) ─ depends on 1.1, 1.2
  ├── Phase 4 (Reports) ────── depends on 1.1, 1.5
  └── Phase 5 (AI) ─────────── depends on 1.2, 1.3, 1.4
       └── 5.2 Ask Penalty ─── depends on Phase 3.2 (responsibility UI)
       └── 5.3 AI Insights ─── depends on Phase 1.3 (aggregated data)
```

Phases 2, 3, 4 can run in parallel after Phase 1. Phase 5 can start after Phase 1 but some features (5.2, 5.3) benefit from Phase 3 data.

---

## Estimated Scope

| Phase | New Files | Modified Files | Migrations | Effort |
|-------|-----------|---------------|------------|--------|
| 1     | ~8        | ~5            | 1          | Medium |
| 2     | ~2        | ~3            | 0          | Medium |
| 3     | ~4        | ~3            | 0          | Medium-Large |
| 4     | ~2        | ~2            | 0          | Medium |
| 5     | ~3        | ~4            | 0-1        | Large |

---

## Files Affected (Key)

**New files:**
- `resources/js/components/charts/area-chart.tsx`
- `resources/js/components/charts/bar-chart.tsx`
- `resources/js/components/charts/pie-chart.tsx`
- `resources/js/components/charts/stat-card.tsx`
- `app/Models/SidingPerformance.php`
- `app/Actions/AggregateSidingPerformance.php`
- `app/Console/Commands/AggregateSidingPerformanceCommand.php`
- `resources/js/pages/penalties/analytics.tsx` (or tab within index)
- `resources/js/pages/reports/index.tsx` (currently empty)
- `app/Actions/GenerateDailyBriefing.php`
- `app/Actions/GeneratePenaltyInsights.php`

**Modified files:**
- `app/Models/Penalty.php` — new casts/fillable
- `database/factories/PenaltyFactory.php` — new fields
- `app/Http/Controllers/Dashboard/ExecutiveDashboardController.php` — new data props
- `resources/js/pages/dashboard.tsx` — chart components
- `app/Http/Controllers/RailwayReceipts/PenaltyController.php` — update method
- `app/Ai/ChatContextBuilder.php` — penalty breakdown methods
- `resources/js/pages/penalties/index.tsx` — responsibility UI + ask AI button
- `resources/js/components/chat-widget.tsx` — external trigger support
- `routes/web.php` — new routes
- `routes/console.php` — new scheduled command

---

## Success Metrics

1. **Penalty cost reduction**: ≥15% reduction in monthly penalties within 3 months of Phase 3 launch (measured by trend)
2. **Attribution coverage**: ≥80% of penalties have `responsible_party` tagged within 30 days
3. **AI engagement**: ≥20% of users interact with "Ask about this penalty" within first month
4. **Dashboard usage**: Page views on penalties analytics page ≥ 3x current penalties index views
5. **Report generation**: ≥ 50 reports generated per week within first month of Phase 4
