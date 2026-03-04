## Codebase Patterns
- shadcn components live in `resources/js/components/ui/` — install with `npx shadcn@latest add <name> --yes --overwrite`
- Chart CSS variables (`--chart-1` through `--chart-5`) already defined in `resources/css/app.css` for both light and dark modes
- `recharts` is already installed as a dependency
- Pre-commit hook runs Pint + docs check. Use `php artisan docs:sync --generate` to fix missing doc stubs before committing.
- `components.json` uses `new-york` style, non-RSC, with aliases: `@/components`, `@/lib/utils`, `@/components/ui`
- Use `divide-border`, `border-border`, `bg-background`, `text-foreground`, `bg-card`, `text-card-foreground` etc. for theme-aware styling — never hardcode `white/X` or `gray-X` for theme elements
- All fleet models auto-scope to current org via `BelongsToOrganization` trait — no manual `where('organization_id', ...)` needed
- Use `Inertia::defer()` for heavy backend computations to avoid blocking initial page load
- PHPStan is strict — prefer `array<string, mixed>` for complex action return types to avoid list/array shape mismatches
- Key date fields: `FuelTransaction.transaction_timestamp`, `ServiceSchedule.next_service_due_date`, `Trip.started_at`, `WorkOrder.created_at`
- Use shadcn `ChartContainer` + `ChartConfig` + `ChartTooltip`/`ChartTooltipContent`/`ChartLegend`/`ChartLegendContent` for all charts — not raw recharts `ResponsiveContainer`, `Tooltip`, `Legend`
- `FleetNavSection` has `submenu?: FleetNavItem[]` — put low-priority items there. `fleetNavItems` flat export includes both primary + submenu items for search/command palette.
- AI enums: `analysis_type` includes fraud_detection, predictive_maintenance, route_optimization, driver_coaching, cost_optimization, compliance_prediction, risk_assessment, fuel_efficiency, safety_scoring, damage_detection, claims_processing, incident_analysis, electrification_planning. `priority`: low/medium/high/critical. `status`: pending/reviewed/actioned/dismissed/escalated
- `ai_job_runs.job_type` enum: fraud_detection, maintenance_prediction, behavior_analysis, route_optimization, cost_analysis, compliance_check, risk_assessment, model_training, data_processing, compliance_prediction
- `alerts` table has DB check constraints: `alert_type` (compliance_expiry, maintenance_due, defect_reported, incident_reported, behavior_violation, fuel_anomaly, cost_threshold, geofence_violation, speed_violation, working_time_violation, system_error), `severity` (info, warning, critical, emergency), `status` (active, acknowledged, resolved, dismissed)
- `compliance_items` has unique constraint on `entity_type + entity_id + compliance_type` — use `firstOrCreate` to avoid conflicts
- `compliance_items.expiry_date` is NOT NULL — always provide a value
- Existing dashboard has `fleet_health_score` (via `ComputeFleetHealthScoreAction`) and `fleet_ai_summary` (via `GetFleetDashboardSummaryAction`) — reuse these, don't recreate

---

## 2026-03-04 - US-001
- Installed shadcn chart component (`npx shadcn@latest add chart`)
- chart.tsx created with ChartContainer, ChartTooltip, ChartTooltipContent, ChartLegend, ChartLegendContent, ChartStyle exports
- card.tsx updated by shadcn to latest version (adds CardAction component, minor class reordering)
- Generated doc stubs for 5 pre-existing undocumented items to fix pre-commit hook
- npm run build passes
- Files changed: `resources/js/components/ui/chart.tsx` (new), `resources/js/components/ui/card.tsx` (updated), 5 doc stubs, `docs/.manifest.json`
- **Learnings for future iterations:**
  - `recharts` and chart CSS variables were already in place — only the shadcn chart wrapper was missing
  - The pre-commit hook blocks on `docs:sync --check` — run `php artisan docs:sync --generate` to create stubs for any undocumented items
  - shadcn `add chart` also updates `card.tsx` as a dependency — expect card.tsx changes when installing chart
---

## 2026-03-04 - US-002
- Changed `config/theme.php` default preset from `metronic` to `fleet`
- Changed `default_appearance` from `system` to `light`
- The fleet preset was already defined in `resources/css/themes.css` with indigo-blue primary (oklch hue 264), white backgrounds, and chart CSS variables
- npm run build passes
- Files changed: `config/theme.php`
- **Learnings for future iterations:**
  - Theme presets are defined in `resources/css/themes.css` using `[data-theme='name']` selectors — both light and `.dark[data-theme='name']` variants
  - `config/theme.php` only sets defaults; env vars `THEME_PRESET` and `THEME_DEFAULT_APPEARANCE` can override
  - The fleet preset uses oklch color space for all CSS variables, with indigo-blue at hue 264
---

## 2026-03-04 - US-003
- Replaced glass morphism `glassCardBase` with flat card styles: `bg-card text-card-foreground rounded-xl border shadow-sm transition-shadow hover:shadow-md`
- Removed all `backdrop-blur-xl`, `bg-white/55`, `border-white/50`, gradient overlays, and opacity modifiers
- Single change propagates to all fleet pages via `FleetGlassCard` component
- npm run build passes
- Files changed: `resources/js/components/fleet/glass-card.tsx`
- **Learnings for future iterations:**
  - `FleetGlassCard` is the single source of truth for card styles across all fleet pages — changing `glassCardBase` propagates everywhere
  - Using `bg-card` and `text-card-foreground` CSS variables ensures correct light/dark mode behavior via the theme system
---

## 2026-03-04 - US-004
- Removed the `pageBgClass` div containing 3 radial gradient overlays from `FleetPageShell`
- Added `bg-background` to `pageContainerClass` for clean white (light) / dark (dark mode) background
- Removed `pageBgClass` from exports (was not imported anywhere else)
- npm run build passes
- Files changed: `resources/js/components/fleet/fleet-page-shell.tsx`
- **Learnings for future iterations:**
  - `FleetPageShell` is the layout wrapper for all fleet pages — background changes here propagate everywhere
  - `bg-background` is the correct Tailwind CSS variable for theme-aware plain backgrounds (white in light, dark in dark mode)
  - `pageBgClass` was exported but never imported elsewhere — safe to remove
---

## 2026-03-04 - US-005
- Cleaned up FleetStatTile component: removed blur glow div, removed `/70` opacity modifiers for crisper colors, added `trend` and `trendValue` optional props
- Trend arrow renders with green (up), red (down), or gray (flat) indicator next to the value using lucide ArrowUp/ArrowDown/ArrowRight icons
- Removed `glow` property from `FLEET_STAT_TILE_TINT` type and all tint entries
- npm run build passes
- Files changed: `resources/js/components/fleet/fleet-stat-tile.tsx`
- **Learnings for future iterations:**
  - `FLEET_STAT_TILE_TINT` is the tint config map — when removing a property (like `glow`), update both the Record type definition and all entries
  - The `glow` property was only used within the component itself (blur div) — no external consumers referenced it
  - Trend arrows use `items-baseline` alignment so arrows sit nicely next to numbers of different sizes
---

## 2026-03-04 - US-006
- Added `AppearanceToggleDropdown` import from `@/components/appearance-dropdown` to the header
- Placed toggle between the search button / time display and the user dropdown menu
- Component already supports light/dark/system switching and respects branding `allowUserCustomization` setting
- npm run build passes
- Files changed: `resources/js/components/app-sidebar-header.tsx`
- **Learnings for future iterations:**
  - `AppearanceToggleDropdown` already exists as a fully functional component at `resources/js/components/appearance-dropdown.tsx` — it handles light/dark/system with icons and respects branding settings
  - The header has two modes: `fleetOnly` (shows time/date, white-on-dark styling) and standard (shows search button) — appearance toggle should appear in both modes
  - The appearance system uses `useAppearance` hook which persists preference and applies `dark` class to the document root — all Tailwind dark: variants automatically work
---

## 2026-03-04 - US-007
- Changed `divide-white/30 dark:divide-white/20` to `divide-border` in `fleetDataCardListClass`
- `divide-border` uses the theme's `--border` CSS variable, which adapts correctly to both light and dark mode
- npm run build passes
- Files changed: `resources/js/components/fleet/fleet-data-card.tsx`
- **Learnings for future iterations:**
  - Use `divide-border` (not `divide-white/X` or `divide-gray-X`) for theme-aware dividers — it maps to the `--border` CSS variable which adapts to light/dark mode automatically
  - The `fleetDataCardListClass` is exported and used by consumers to style list containers within data cards
---

## 2026-03-04 - US-008
- Created `GetFleetDashboardChartDataAction` with all new dashboard data props: kpiTrends, kpiSparklines, chartFleetActivity, chartCostBreakdown, chartFuelCostTrend, chartDriverSafetyDistribution, aiPredictions, upcomingMaintenance
- Wired action into `FleetDashboardController` as a deferred Inertia prop (`chartData`)
- fleetHealthScore and fleetHealthSummary already existed as `fleet_health_score` and `fleet_ai_summary` props
- All queries auto-scoped to tenant via `BelongsToOrganization` trait on models
- Eager loading used for upcomingMaintenance (vehicle relationship)
- PHPStan passes clean, npm build passes
- Files changed: `app/Actions/Fleet/GetFleetDashboardChartDataAction.php` (new), `app/Http/Controllers/Fleet/FleetDashboardController.php`
- **Learnings for future iterations:**
  - All fleet models use `BelongsToOrganization` trait with a global `OrganizationScope` — no need to manually add `where('organization_id', ...)` to queries
  - `ServiceSchedule` due date field is `next_service_due_date` (nullable Carbon), not `due_date`
  - `FuelTransaction` uses `transaction_timestamp` (datetime), not `created_at`, for date filtering
  - `InsurancePolicy` has `premium_amount` for cost, `status` field with 'active' value
  - `WorkOrder` has `total_cost` (decimal:2) for maintenance cost tracking
  - The existing controller already has `fleet_health_score` and `fleet_ai_summary` (via `ComputeFleetHealthScoreAction` and `GetFleetDashboardSummaryAction`) — no need to add fleetHealthScore/fleetHealthSummary separately
  - Use `Inertia::defer()` for heavy computation props to avoid blocking initial page load
  - PHPStan level is strict — use `array<string, mixed>` for complex return types rather than detailed array shapes to avoid list vs array mismatches
---

## 2026-03-04 - US-016
- Changed `COUNT = 5` to `COUNT = 25` in `FleetFullSeeder`
- Expanded locations from 5 to 10 UK cities: London, Manchester, Birmingham, Leeds, Liverpool, Glasgow, Bristol, Edinburgh, Cardiff, Belfast
- Expanded vehicles from 5 to 25 with realistic UK registrations and mix of makes: Ford Transit (6), Mercedes Sprinter (4), Volvo FH (4), DAF XF (4), Tesla Model 3 (3), MAN TGX (4). Includes 2 in_maintenance status vehicles.
- Expanded drivers from 5 to 25 with UK names and safety scores ranging 62-98. Risk categories: low (14), medium (5), high (4), plus 2 existing medium. 1 non_compliant driver (Thomas Clark, score 62).
- Changed vehicle #4 from Vauxhall Vivaro to DAF XF to match AC requirement for specified makes
- Updated driver-vehicle assignment loop to assign all 25 drivers to vehicles (removed hardcoded `min(4, ...)` limit)
- Vehicles distributed across all 10 locations via `home_location_id`
- Pint, PHPStan (no new errors), npm build all pass
- Files changed: `database/seeders/Development/FleetFullSeeder.php`
- **Learnings for future iterations:**
  - `seedRecords` uses `min($count, count($rows))` — safe to increase COUNT without expanding every entity's row array; entities with fewer rows just create what they have
  - Locations use hardcoded count `10` (not `self::COUNT`) since the AC specifies exactly 10 cities
  - Other entities (CostCenters, Garages, FuelStations, etc.) that still have 5 row entries will create only 5 even with COUNT=25 — this is fine
  - Phase loops using `self::COUNT` (fuel transactions, service schedules, work orders) now create 25 instead of 5, which gives more realistic data volumes
  - The legacy dump in `seedFromLegacyDump()` adds 40 more vehicles on top of the 25, so the demo org will have ~65 vehicles total
---

## 2026-03-04 - US-017
- Added `seedHistoricalChartData()` method to `FleetFullSeeder` with 30 days of historical data
- Trips: 233 records (7-10/day weekdays, 3-5/day weekends), spread over 30 days with varying durations and distances
- Work Orders: 60 records with status mix (30% completed, 20% in_progress, 15% scheduled, 15% pending, 10% draft, 10% cancelled). Completed orders clustered toward recent dates.
- Fuel Transactions: 139 records, GBP 30-150 per transaction with subtle upward cost trend (base cost rises ~0.67/day over 30 days)
- Alerts: 31 records — 4 critical active, 6 warning active, 12+ resolved, plus 2-3 burst clusters simulating incidents. Uses valid `alert_type` enum values from DB constraint.
- Compliance Items: 30 records with 60% valid, 20% expiring_soon, 10% expired, 10% pending. Uses `firstOrCreate` to avoid unique constraint violations.
- Service Schedules: 17 records — 5 due within 14 days, 12 with future dates (15-90 days out)
- Method called after `seedPhase11ExtrasAudit` and before `seedFromLegacyDump`
- Pint passes, no new PHPStan errors, npm build passes, seeder completes without errors
- Files changed: `database/seeders/Development/FleetFullSeeder.php`
- **Learnings for future iterations:**
  - `alerts` table has a DB check constraint on `alert_type` — valid values: compliance_expiry, maintenance_due, defect_reported, incident_reported, behavior_violation, fuel_anomaly, cost_threshold, geofence_violation, speed_violation, working_time_violation, system_error
  - `alerts` severity constraint: info, warning, critical, emergency. Status: active, acknowledged, resolved, dismissed.
  - `compliance_items` has a unique constraint on `entity_type + entity_id + compliance_type` — use `firstOrCreate` to avoid conflicts with existing Phase 3 data
  - `compliance_items.expiry_date` is NOT NULL — always provide a date, even for 'pending' status
  - The seeder is idempotent for records using `firstOrCreate`, but `create()` will add new records on re-run — historical data uses `create()` intentionally to allow volume
---

## 2026-03-04 - US-018
- Added `seedAiAnalysisData()` method to `FleetFullSeeder` with comprehensive AI data
- 14 AiAnalysisResult records covering: compliance_prediction (2), predictive_maintenance (2), fraud_detection (1), cost_optimization (2), risk_assessment (1), damage_detection (1), electrification_planning (1), incident_analysis (1), fuel_efficiency (1), safety_scoring (1), driver_coaching (1)
- Each result has detailed_analysis JSON, recommendations array, action_items, business_impact, and confidence_score between 0.76-0.94
- 5 AiJobRun records (all completed status) covering: compliance_prediction, maintenance_prediction, fraud_detection, risk_assessment, cost_analysis
- 3 WorkflowDefinition records: Nightly Compliance Check (schedule), Fuel Anomaly Detection (event), Weekly Fleet Health Report (schedule)
- 7 WorkflowExecution records (all completed) distributed across the 3 definitions
- All records scoped to demo organization via `$this->org->id`
- Uses `firstOrCreate` for idempotent seeding
- Method called after `seedHistoricalChartData` and before `seedFromLegacyDump`
- Pint passes, no new PHPStan errors, npm build passes, seeder completes without errors
- Files changed: `database/seeders/Development/FleetFullSeeder.php`
- **Learnings for future iterations:**
  - `ai_analysis_results.analysis_type` full enum: fraud_detection, predictive_maintenance, route_optimization, driver_coaching, cost_optimization, compliance_prediction, risk_assessment, fuel_efficiency, safety_scoring, damage_detection, claims_processing, incident_analysis, electrification_planning
  - `ai_analysis_results.entity_type` enum: vehicle, driver, trip, transaction, organization, defect, incident, insurance_claim
  - `ai_analysis_results.priority` enum: low, medium, high, critical
  - `ai_analysis_results.status` enum: pending, reviewed, actioned, dismissed, escalated
  - `ai_job_runs.job_type` enum: fraud_detection, maintenance_prediction, behavior_analysis, route_optimization, cost_analysis, compliance_check, risk_assessment, model_training, data_processing, compliance_prediction
  - `ai_job_runs.status` enum: queued, processing, completed, failed, cancelled
  - `workflow_definitions.trigger_type` enum: schedule, event, manual, webhook
  - `workflow_executions.status` enum: pending, running, completed, failed, cancelled
  - Use `firstOrCreate` with composite key (organization_id + analysis_type + primary_finding) for AiAnalysisResult idempotency
  - Use `firstOrCreate` with composite key (organization_id + job_type + started_at) for AiJobRun idempotency
  - The PRD mentions "damage_assessment" but the actual enum is "damage_detection" — use the DB enum values
---

## 2026-03-04 - US-009
- Created `FleetHealthBanner` component with score circle (SVG), AI summary, breakdown row, and CTA button
- Score circle uses SVG `strokeDasharray`/`strokeDashoffset` for progress visualization
- Color coding: green (80+), amber (60-79), red (<60) with matching accent bar, icons, and labels
- Breakdown row shows: active alerts (red dot), overdue work orders (amber dot), compliance status (blue dot)
- CTA "View Details" links to `/fleet/alerts`
- Uses shadcn Card + Button components, Inertia Link, theme-aware CSS variables
- Exported from `resources/js/components/fleet/index.ts`
- npm run build passes, ESLint clean
- Files changed: `resources/js/components/fleet/fleet-health-banner.tsx` (new), `resources/js/components/fleet/index.ts`
- **Learnings for future iterations:**
  - Dashboard already provides `fleet_health_score` (int), `fleet_health_breakdown` (object with open_alerts, overdue_work_orders, compliance_pct, compliance_label, open_defects, vehicles), and `fleet_ai_summary` (string) — these map directly to the banner props
  - The `ComputeFleetHealthScoreAction` returns `{score: int, breakdown: {...}}` — reuse this in the dashboard, no need to recompute
  - SVG circle progress: radius 40, circumference = 2πr ≈ 251.3, offset = circumference * (1 - score/100)
  - shadcn Card has default padding via CardContent (px-6) — use `pt-5` override for top padding when accent bar is present
---

## 2026-03-04 - US-010
- Created `FleetKpiCard` component with large number display, trend arrows, sparkline, icon, and subtitle support
- Uses shadcn `Card`/`CardContent` for structure and `ChartContainer` + recharts `AreaChart` for the sparkline
- Trend arrows use green (up), red (down), gray (flat) with TREND_CONFIG pattern matching existing FleetStatTile
- Sparkline renders as a tiny 96x48px area chart with gradient fill using `--chart-1` CSS variable
- Exported from `resources/js/components/fleet/index.ts`
- npm run build and ESLint pass
- Files changed: `resources/js/components/fleet/fleet-kpi-card.tsx` (new), `resources/js/components/fleet/index.ts`
- **Learnings for future iterations:**
  - shadcn `ChartContainer` defaults to `aspect-video` — override with custom height/width classes for sparklines
  - `ChartConfig` is `const` satisfies (use `as const`) for type safety with the chart system
  - For sparklines: disable animation (`isAnimationActive={false}`), remove all axes/grids, use minimal margins
  - The gradient fill uses `linearGradient` with `var(--color-value)` which is set by ChartContainer from the config
  - Multiple sparklines on the same page sharing the same gradient ID (`sparklineFill`) will work because recharts scopes SVG defs per chart instance
---

## 2026-03-04 - US-011
- Created `FleetChartCard` component — reusable card wrapper for charts with title, optional description, and time-range selector
- Uses shadcn `Card`, `CardHeader`, `CardTitle`, `CardDescription`, `CardAction`, `CardContent` components
- Time range selector renders as a segmented button group inside `CardAction` with muted background and active state shadow
- Props: title, description?, timeRanges? ({label, value}[]), selectedRange?, onRangeChange?, children, className?
- Exported from `resources/js/components/fleet/index.ts`
- npm run build passes
- Files changed: `resources/js/components/fleet/fleet-chart-card.tsx` (new), `resources/js/components/fleet/index.ts`
- **Learnings for future iterations:**
  - shadcn Card v5 has `CardAction` component that positions content at top-right of CardHeader — ideal for time range selectors
  - For segmented button groups: use `bg-muted` container with `rounded-lg border p-0.5`, active button gets `bg-background shadow-sm text-foreground`
  - `ChartContainer` is not needed in the wrapper itself — it's used by the chart children. The wrapper provides Card structure only.
---

## 2026-03-04 - US-012
- Created `FleetAiPanel` component with header (BrainCircuit icon + "AI Insights"), prediction list, and empty state
- Each prediction row shows: priority dot (red=high/critical, amber=medium, blue=low), analysis type label (formatted from snake_case), primary finding text, "View" CTA link to `/fleet/ai-analysis-results/{id}`
- Empty state shows centered BrainCircuit icon + message: "No AI insights yet. Run an analysis to get started."
- Uses shadcn Card/CardHeader/CardTitle/CardContent, divide-border for row separators
- Exported from `resources/js/components/fleet/index.ts`
- npm run build passes
- Files changed: `resources/js/components/fleet/fleet-ai-panel.tsx` (new), `resources/js/components/fleet/index.ts`
- **Learnings for future iterations:**
  - Backend `computeAiPredictions()` returns: `{id, priority, primary_finding, analysis_type, entity_type, entity_id}` — these are the exact fields available for the AI panel
  - Priority values from DB: low, medium, high, critical — the panel maps critical to red (same as high)
  - Analysis type formatting: split on `_`, capitalize each word — e.g. `compliance_prediction` → `Compliance Prediction`
  - AI analysis results route is `/fleet/ai-analysis-results/{id}` (kebab-case)
---

## 2026-03-04 - US-013
- Created `AiInsightBadge` component — small inline pill/badge for showing AI insight summaries on entity tables and show pages
- Props: text, priority (high/medium/low), analysisType?, href?, className?
- Renders as a rounded-full pill with BrainCircuit icon + optional analysis type prefix + truncated text
- Priority color coding: red tint (high), amber tint (medium), blue tint (low) — with proper dark mode variants using `dark:` prefixes
- Clickable via optional `href` prop — wraps in Inertia `Link` when provided
- Exported from `resources/js/components/fleet/index.ts`
- npm run build passes
- Files changed: `resources/js/components/fleet/ai-insight-badge.tsx` (new), `resources/js/components/fleet/index.ts`
- **Learnings for future iterations:**
  - Priority color pattern for badges: use `border-{color}-200 bg-{color}-50 text-{color}-700` for light mode and `dark:border-{color}-800 dark:bg-{color}-950 dark:text-{color}-300` for dark mode
  - Use `max-w-xs` + `truncate` on text to prevent badges from becoming too wide on long findings
  - The component follows the same priority mapping as `FleetAiPanel` (PRIORITY_CONFIG) — high=red, medium=amber, low=blue
  - Badge is intentionally not a shadcn Badge wrapper — it's a custom pill with specific icon + text layout needs
---

## 2026-03-04 - US-014
- Created `AnimatedNumber` component using framer-motion `useMotionValue` + `useSpring` for smooth number animation
- Animates from 0 to target value on mount (via `useInView`) and when value changes
- Supports `prefix` and `suffix` props for currency/percentage formatting
- Integers display with `toLocaleString()`, decimals preserve original precision via `toFixed()`
- Respects `prefers-reduced-motion` — shows final value immediately when reduced motion enabled
- Uses spring physics with `bounce: 0` for natural easing (not linear)
- Exported from `resources/js/components/fleet/index.ts`
- npm run build passes, ESLint clean
- Files changed: `resources/js/components/fleet/animated-number.tsx` (new), `resources/js/components/fleet/index.ts`
- **Learnings for future iterations:**
  - framer-motion's `useSpring` with `duration` option and `bounce: 0` gives smooth ease-out animation without needing to configure stiffness/damping manually
  - `useInView` with `once: true` ensures animation triggers only when the element scrolls into view, preventing premature animation
  - Direct DOM manipulation via `ref.current.textContent` in the `springValue.on('change')` callback avoids React re-renders on every frame — critical for smooth 60fps animation
  - The `useMotionValue` → `useSpring` → `on('change')` pattern is the idiomatic framer-motion way to animate numbers without causing React state updates per frame
---

## 2026-03-04 - US-015
- Fully rewrote `Dashboard.tsx` with 6 clear sections following a storytelling layout
- Section 1: FleetHealthBanner (full width) — score circle, AI summary, breakdown, CTA
- Section 2: 4 Hero KPI Cards (grid-cols-2 md:grid-cols-4) — Vehicles, Active Drivers, Open Work Orders, Active Alerts — each with FleetKpiCard, sparklines, trends
- Section 3: Primary Charts (lg:grid-cols-7) — Fleet Activity area chart (col-span-4, trips + work orders overlaid) + Cost Breakdown donut (col-span-3)
- Section 4: AI Intelligence Layer (lg:grid-cols-7) — FleetAiPanel (col-span-4) + Fuel Spend Trend line chart (col-span-3)
- Section 5: Operational Detail (lg:grid-cols-3) — Recent Alerts card, Upcoming Maintenance card, Driver Safety Distribution donut
- Section 6: Fleet Map (collapsible, closed by default) — uses chevron toggle, preserves full map functionality with vehicle markers, geofences, polylines, info windows, and mobile sheet
- Removed ~60 KPI stat tiles, quick links section, suggested actions bar, old glass-card charts, overview bar chart, work orders by status donut — replaced with focused 4 KPI cards + proper charts
- All charts use shadcn `ChartContainer`, `ChartTooltip`, `ChartTooltipContent`, `ChartLegend`, `ChartLegendContent` — no more raw `ResponsiveContainer` or inline `Tooltip`/`Legend`
- Dashboard uses deferred `chartData` prop — shows Skeleton placeholders while loading
- ESLint clean, npm run build passes
- Files changed: `resources/js/pages/Fleet/Dashboard.tsx`
- **Learnings for future iterations:**
  - Use `ChartContainer` + `ChartConfig` pattern for all charts — it sets CSS custom properties (`--color-{key}`) that gradients and strokes reference
  - `ChartTooltip` + `ChartTooltipContent` replace raw recharts `Tooltip` — they auto-format using the `ChartConfig` labels
  - `ChartLegend` + `ChartLegendContent` replace raw recharts `Legend` — consistent styling with the design system
  - For donut charts with `Cell` components, pass `fill` directly using `CHART_COLORS` array or config-derived colors
  - Cost breakdown config needs both a generic `value` key and per-segment keys (e.g., `Fuel`, `Maintenance`) for the legend to display names correctly
  - The `@eslint-react/hooks-extra/no-direct-set-state-in-use-effect` rule triggers on `setLiveMapVehicles(mapVehicles)` in useEffect — suppress with eslint-disable comment since this is a valid server-prop-to-local-state sync pattern
  - Map collapsible pattern: use a `button` wrapping the card header with `ChevronUp`/`ChevronDown` icons, conditionally render map content below
  - Props interface uses `[key: string]: unknown` index signature to accept all legacy props from the controller without TypeScript errors
---

## 2026-03-04 - US-019
- Restructured `fleet-nav.ts` from 9 groups to 6 logical groups: AI & Intelligence, Fleet Operations, Maintenance, Fuel & Energy, Compliance & Safety, Reports & Admin
- Extended `FleetNavSection` interface with optional `submenu?: FleetNavItem[]` and `icon?: LucideIcon` properties
- Each group has primary items (always visible) and optional submenu items (in collapsible "More" section)
- Updated `nav-main.tsx` fleet-only layout to render primary items as direct SidebarMenuItems and submenu items inside a Collapsible "More" trigger with SidebarMenuSub
- Auto-expand logic: submenu sections auto-open when a submenu item is the active page
- Added all 80+ fleet pages to the navigation — pages not in the previous nav (e.g., driver qualifications, training courses, tachograph downloads, etc.) are now in submenu sections
- AI & Intelligence promoted to first group (was 7th of 9)
- `fleetNavItems` flat list updated to include both primary and submenu items for backward compatibility
- Added icons: BrainCircuit, BotMessageSquare, Truck, Shield, Zap, MoreHorizontal
- Removed unused CreditCard import that ESLint flagged
- npm run build passes, ESLint has only pre-existing warnings (no errors)
- Files changed: `resources/js/config/fleet-nav.ts`, `resources/js/components/nav-main.tsx`
- **Learnings for future iterations:**
  - `FleetNavSection` now supports `submenu?: FleetNavItem[]` — use this for low-priority items that should be accessible but not clutter the primary view
  - The nav-main.tsx fleet-only layout renders each section as a `SidebarGroup` with `SidebarGroupLabel` — primary items as `SidebarMenuItem` and submenu in a `Collapsible` with `MoreHorizontal` icon
  - `fleetNavItems` flat export includes BOTH primary and submenu items — use this for search/command palette
  - The `command-dialog-v2.tsx` uses `fleetNavSections.flatMap((s) => s.items)` — only primary items shown in shortcuts (submenu items not included). US-022 will expand this.
  - Route pattern for all fleet pages: `/fleet/{kebab-case-resource}` — e.g., `/fleet/driver-qualifications`, `/fleet/tachograph-calibrations`
  - E-lock events route is `/fleet/e-lock-events` (with hyphen), not `/fleet/elock-events`
---

## 2026-03-04 - US-021
- Backend `fleet_alert_count` was already implemented in `HandleInertiaRequests.php` from a previous iteration
- Added `fleet_alert_count?: number` to `SharedData` TypeScript interface for type safety
- Query: `Alert::query()->where('status', 'active')->whereNull('acknowledged_at')->count()` — auto-scoped to current org via `BelongsToOrganization` trait
- Returns 0 when no user or no current organization (non-fleet users unaffected)
- Lazy closure `fn ()` ensures query only runs when data is accessed
- HandleInertiaRequests tests pass (8 tests, 21 assertions)
- npm run build passes
- Files changed: `resources/js/types/index.d.ts`
- **Learnings for future iterations:**
  - `fleet_alert_count` is shared Inertia data — access via `usePage().props.fleet_alert_count` on frontend
  - The `alerts` table has no boolean `acknowledged` column — use `whereNull('acknowledged_at')` to find unacknowledged alerts
  - `BelongsToOrganization` global scope auto-filters by current org — no manual `where('organization_id')` needed
  - `SharedData` interface at `resources/js/types/index.d.ts` has `[key: string]: unknown` catch-all, but explicit props give better TypeScript support
---

## 2026-03-04 - US-020
- Added pulsing dot/sparkle next to "AI & Intelligence" section label in fleet sidebar
- Pulse uses Tailwind `animate-ping` on a violet dot with a solid violet center — subtle, not distracting
- Added red badge (`SidebarMenuBadge`) on "Alerts" nav item showing `fleet_alert_count` from shared Inertia data
- Badge hidden when count is 0 (conditionally rendered)
- Used `usePage<SharedData>()` to type-safely access `fleet_alert_count` prop
- Imported `SidebarMenuBadge` from sidebar component library (already existed, just not used)
- npm run build passes
- Files changed: `resources/js/components/nav-main.tsx`
- **Learnings for future iterations:**
  - `SidebarMenuBadge` is already available in the sidebar component library — use it for count badges on menu items, it positions absolute right with proper sizing
  - `fleet_alert_count` is available in `SharedData` (typed in `resources/js/types/index.d.ts`) — access via `usePage<SharedData>().props.fleet_alert_count`
  - For pulsing dots in Tailwind: use `animate-ping` on an absolute positioned span with a solid relative span behind it — this creates the classic "live" indicator effect
  - The "AI & Intelligence" label check is string-based (`section.label === 'AI & Intelligence'`) — if the nav label changes, the pulse will stop appearing
---

## 2026-03-04 - US-022
- Added "Fleet" CommandGroup to `command-dialog.tsx` listing all fleet pages from `fleetNavItems`
- Imports `fleetNavItems` from `@/config/fleet-nav` — includes both primary nav items and submenu items (80+ pages total)
- Each item shows its full name (e.g., "Vehicles", "Driver Qualifications", "Workshop Bays") with its icon (falls back to Truck icon if none defined)
- Selecting a page navigates to it via `router.visit()`
- Search/filter works across all fleet page names since each CommandItem has a `value` set to the page title
- Fleet group appears between Navigation and Account groups in browse mode
- npm run build passes
- Files changed: `resources/js/components/command-dialog.tsx`
- **Learnings for future iterations:**
  - `fleetNavItems` is a flat array that includes dashboard + all primary items + all submenu items — ideal for search/command palette use
  - CommandDialog's built-in filtering (via cmdk) automatically handles search matching against `value` props — no custom filtering needed
  - Items without icons in the nav config (submenu items) need a fallback icon — `Truck` works as a generic fleet icon
  - The `shouldFilter={!isSearchMode}` prop means cmdk's built-in filter only runs in browse mode (not search mode where backend search is used) — fleet items are filterable in browse mode
---

## 2026-03-04 - US-023
- Created `FleetIndexSummaryBar` component — reusable horizontal stat bar with `SummaryStat` interface (label, value, icon, variant)
- Renders as a responsive 2-col (mobile) / 4-col (desktop) grid of shadcn Cards
- Variant color coding: default (foreground), success (emerald), warning (amber), danger (red) — with dark mode variants
- Backend: added `Inertia::defer()` summary stats to 5 controllers (VehicleController, WorkOrderController, DriverController, FuelTransactionController, ComplianceItemController)
- Vehicles Index: Total (Car), Active (CheckCircle/success), In Maintenance (Wrench/warning), Due for Service (AlertTriangle/danger if >0)
- Work Orders Index: Open (ClipboardList), Overdue (AlertTriangle/danger if >0), Completed This Week (CheckCircle/success), Avg Resolution (Clock, "Xd" format)
- Drivers Index: Total (Users), Active (CheckCircle/success), Low Safety <70 (AlertTriangle/danger if >0), Quals Expiring (Award/warning if >0)
- Fuel Transactions Index: Total Spend 30d (PoundSterling, £ prefix), Avg per Vehicle (Car, £ prefix), Flagged (AlertTriangle/warning if >0) — 3 stats (simplified from 4)
- Compliance Items Index: Total (ShieldCheck), Valid (CheckCircle/success), Expiring Soon (Clock/warning if >0), Expired (XCircle/danger if >0)
- Pint passes, PHPStan clean (no new errors), npm build passes, tests pass
- Files changed: `resources/js/components/fleet/fleet-index-summary-bar.tsx` (new), `resources/js/components/fleet/index.ts`, 5 controllers, 5 index pages
- **Learnings for future iterations:**
  - `Inertia::defer()` returns a `DeferProp` object — pass as `'summary' => $summary` key, NOT spread with `...$summary` (not iterable)
  - The dashboard controller uses the same pattern: `'chartData' => Inertia::defer(fn () => ...)` — follow this for all deferred props
  - PHPStan strict mode: use `is_numeric()` guard before `(float)` cast on `mixed` values from `selectRaw()->value()` calls
  - `ComplianceItemStatus` enum values: `valid`, `expiring_soon`, `expired`, `renewed`, `revoked` — use string values in queries
  - `WorkOrder` open statuses for summary: `draft`, `pending`, `approved`, `in_progress`
  - For "due for service": query `ServiceSchedule.next_service_due_date <= now()->addDays(14)`, count distinct `vehicle_id`
  - Frontend summary prop is optional (`summary?`) since `Inertia::defer()` sends data asynchronously after initial page load
---

## 2026-03-04 - US-024
- Added `aiInsights` deferred Inertia prop to VehicleController and DriverController index methods
- Backend queries `AiAnalysisResult` for paginated entity IDs, returns latest per entity as keyed map `{entity_id: {id, primary_finding, priority, analysis_type}}`
- Frontend: added `AiInsightBadge` column to Vehicles/Index.tsx and Drivers/Index.tsx tables
- AI Insight column only renders when `aiInsights` map has entries (no empty column when no AI data exists)
- Badge shows primary finding text and priority color; clicking navigates to `/fleet/ai-analysis-results/{id}`
- Rows without AI data show empty cell (within the column); rows without any AI data at all see no column
- Pint passes, npm build passes, HandleInertiaRequests tests pass (8/8)
- Files changed: `app/Http/Controllers/Fleet/VehicleController.php`, `app/Http/Controllers/Fleet/DriverController.php`, `resources/js/pages/Fleet/Vehicles/Index.tsx`, `resources/js/pages/Fleet/Drivers/Index.tsx`
- **Learnings for future iterations:**
  - Use `Inertia::defer()` with a named group for side-loaded data that's separate from the main paginated results — keeps initial page load fast
  - Query `AiAnalysisResult` with `->orderByDesc('created_at')->get()->unique('entity_id')` to get latest per entity without complex subqueries
  - The AI Insight column conditionally renders based on `Object.keys(aiInsights).length > 0` to avoid showing an empty column when no entities have AI data
  - `AiInsightBadge` accepts `href` prop for click-through — use `/fleet/ai-analysis-results/{id}` route
  - `AiAnalysisResult.entity_type` uses simple strings ('vehicle', 'driver') — not model class names
---

## 2026-03-04 - US-025
- Created `AiInsightBanner` component for prominent AI insight cards on entity show pages
- Component features: BrainCircuit icon, priority-based left border color (red/amber/blue), analysis type label, primary finding text, first recommendation, "View Analysis" link
- Backend: added `aiInsight` prop to `VehicleController::show()` and `DriverController::show()` — queries latest `AiAnalysisResult` by entity_type + entity_id
- Frontend: added `AiInsightBanner` to both Vehicle Show and Driver Show pages, rendered between header and tabs
- Card hidden when no AI analysis exists (conditional rendering on `aiInsight` prop)
- Priority colors: critical/high → red, medium → amber, low → blue — with dark mode variants
- Exported from `resources/js/components/fleet/index.ts`
- Pint passes, npm build passes, tests pass
- Files changed: `resources/js/components/fleet/ai-insight-banner.tsx` (new), `resources/js/components/fleet/index.ts`, `app/Http/Controllers/Fleet/VehicleController.php`, `app/Http/Controllers/Fleet/DriverController.php`, `resources/js/pages/Fleet/Vehicles/Show.tsx`, `resources/js/pages/Fleet/Drivers/Show.tsx`
- **Learnings for future iterations:**
  - `AiAnalysisResult` `recommendations` field is cast to `array` — returns `string[]` or `null`. Use `first(['id', 'primary_finding', 'priority', 'analysis_type', 'recommendations'])` to select only needed columns.
  - For show pages, load the latest AI result synchronously (not deferred) since it's a single query per entity — no need for `Inertia::defer()` overhead on show pages
  - The `AiInsightBanner` is distinct from `AiInsightBadge` — banner is a full-width card for show pages, badge is a small inline pill for tables
  - Priority includes 'critical' in addition to high/medium/low — handle both critical and high as red styling
---

## 2026-03-04 - US-026
- Enhanced `FleetEmptyState` component with two new optional props: `aiSuggestion?: string` and `features?: string[]`
- `aiSuggestion` renders a violet-tinted pill with BotMessageSquare icon: "Try: Ask the Fleet Assistant: '{suggestion}'"
- `features` renders a "What you can do here" section with CheckCircle2 icons and feature list
- Added contextual AI suggestions and features to all 6 pages using FleetEmptyState:
  - Vehicles: "What vehicles should I add first?" + 4 features
  - Drivers: "How should I set up driver profiles?" + 4 features
  - Work Orders: "What maintenance should I schedule first?" + 4 features
  - Trips: "How do trips get recorded?" + 3 features
  - Routes: "How can AI optimise my routes?" + 3 features
  - Defects: "What defects should I look out for?" + 3 features
- npm run build passes
- Files changed: `resources/js/components/fleet/fleet-empty-state.tsx`, `resources/js/pages/Fleet/Vehicles/Index.tsx`, `resources/js/pages/Fleet/Drivers/Index.tsx`, `resources/js/pages/Fleet/WorkOrders/Index.tsx`, `resources/js/pages/Fleet/Trips/Index.tsx`, `resources/js/pages/Fleet/Routes/Index.tsx`, `resources/js/pages/Fleet/Defects/Index.tsx`
- **Learnings for future iterations:**
  - `FleetEmptyState` wraps the shared `EmptyState` component — new props are added to the Fleet wrapper, not the shared component
  - The AI suggestion pill uses violet theming (consistent with AI branding in the app: BrainCircuit/BotMessageSquare icons + violet colors)
  - Features list uses `CheckCircle2` from lucide-react for a clean checklist appearance
  - All 6 pages that use `FleetEmptyState` are: Vehicles, Drivers, Work Orders, Trips, Routes, Defects
---

## 2026-03-04 - US-027
- Created `FleetDashboardSkeleton` component matching the full dashboard layout with pulse-animated skeletons
- Skeleton covers: health banner (score circle + text + CTA), 4 KPI cards (title + value + trend + sparkline area), 2 primary chart areas (4+3 grid), AI panel (icon + 5 prediction rows), fuel trend chart, 3 operational detail cards (alerts, maintenance list, safety donut)
- Uses shadcn `Skeleton`, `Card`, `CardContent`, `CardHeader` components
- Updated `Dashboard.tsx` to show `FleetDashboardSkeleton` while `chartData` deferred prop is loading, replacing all inline `Skeleton` elements
- Health banner renders immediately (non-deferred data), sections 2-5 show skeleton → real content transition
- Removed unused `Skeleton` import from Dashboard.tsx
- Exported from `resources/js/components/fleet/index.ts`
- npm run build passes, ESLint clean (only pre-existing warnings), tests pass
- Files changed: `resources/js/components/fleet/fleet-dashboard-skeleton.tsx` (new), `resources/js/components/fleet/index.ts`, `resources/js/pages/Fleet/Dashboard.tsx`
- **Learnings for future iterations:**
  - Dashboard uses `Inertia::defer()` for `chartData` — `!chartData` is the loading check. Non-deferred props (`counts`, `fleet_health_score`, etc.) are available on first render.
  - For skeleton components matching complex layouts: use the same grid classes (`grid-cols-2 md:grid-cols-4`, `lg:grid-cols-7`, `lg:grid-cols-3`) as the real layout to prevent layout shift when data loads
  - Skeleton `Array.from({ length: N })` with index keys is fine for static placeholder items — ESLint warns but doesn't error since these items never reorder
  - The `FleetDashboardSkeleton` wraps sections 2-5 in a single `<div className="flex flex-col gap-6">` to match the parent `contentWrapperClassName` gap
  - When replacing inline skeletons with a dedicated component, also remove the conditional ternaries (`isChartDataLoading ? <Skeleton> : ...`) — wrap the entire loaded content in an else branch instead
---

## 2026-03-04 - US-028
- Enhanced Fleet Assistant FAB with pulse animation on first visit and updated suggestion chips
- Pulse animation: `animate-ping` on an absolute span overlaying the FAB button, controlled by `showPulse` state
- Pulse tracked via `localStorage` key `fleet-assistant-fab-pulse-seen` — only shows once per browser, fades after 3 seconds via `setTimeout`
- Suggestion chips updated from generic queries to AI-forward prompts: "What needs attention?", "Show driver safety trends", "Predict maintenance costs", "Fleet health summary"
- Reduced suggestions from 6 to 4 (more focused, AI-oriented)
- npm run build passes
- Files changed: `resources/js/components/fleet/fleet-assistant-fab.tsx`
- **Learnings for future iterations:**
  - `animate-ping` with `bg-primary/40` creates a clean pulsing ring effect — use absolute positioning + `inset-0` to overlay the button
  - For one-time animations tracked via localStorage: set `showPulse` state in `useEffect`, mark localStorage immediately, then `setTimeout` to clear the state after duration
  - Wrap localStorage access in try/catch for SSR safety and incognito mode
  - The FAB already has `position: relative` implicitly from Button component — absolute children position correctly within it
---

## 2026-03-04 - US-029
- Redesigned AI Analysis Results index page from plain table to card-based "AI command center" layout
- Backend: added `computeSummary()` private method returning totalResults, highPriority, mediumPriority, avgConfidence as deferred Inertia prop
- Frontend: summary cards at top using `FleetIndexSummaryBar` (Total Results, High Priority, Medium Priority, Avg Confidence)
- Results grouped by `analysis_type` using `useMemo` — each group has section header with type icon + count
- Each result card shows: priority-coded left border (red=high/critical, amber=medium, blue=low), priority badge, confidence percentage, primary finding, entity type + id, date
- Cards are clickable (full card links to `/fleet/ai-analysis-results/{id}`)
- 13 analysis type icons mapped: fraud_detection→ShieldAlert, predictive_maintenance→Wrench, route_optimization→Route, etc.
- Pagination preserved at bottom
- Skeleton loading state while summary deferred prop loads
- Empty state preserved with BrainCircuit icon
- Pint passes, npm build passes, ESLint clean (warnings only), tests pass
- Files changed: `app/Http/Controllers/Fleet/AiAnalysisResultController.php`, `resources/js/pages/Fleet/AiAnalysisResults/Index.tsx`
- **Learnings for future iterations:**
  - `AiAnalysisResult` model returns `confidence_score` as a string (decimal:4 cast) — use `Number()` on frontend to convert for display
  - For grouped card layouts: use `useMemo` to group paginated results by a key field, then render each group as a `<section>` with header
  - `Inertia::defer()` summary stats pattern: add a private method on the controller, return as named deferred prop — frontend checks `summary?` for loading state
  - `line-clamp-2` Tailwind utility is useful for truncating multi-line text in cards (better than `truncate` for 2+ lines)
  - Priority config pattern: use a single `PRIORITY_CONFIG` Record with `border`, `dot`, `badgeCls`, `label` keys for consistent priority styling across components
  - Laravel pagination `links` array has HTML entities in labels (`&laquo;`, `&raquo;`) — use `dangerouslySetInnerHTML` to render them correctly
---

## 2026-03-04 - US-030
- Redesigned AI Analysis Result show page from plain text/JSON dump to structured card-based layout
- Header card: analysis type icon + priority badge + status badge + risk score badge + confidence gauge (SVG circular progress) + entity link
- Detailed analysis: parsed JSON into structured cards (long text spans full width, short values in 2-col grid)
- Recommendations: bullet list with primary-colored dots
- Action items: interactive checklist with shadcn Checkbox (client-side state via useState)
- Business impact: card grid showing risk/cost/operational impact
- Review section: preserved reviewer info display
- Metadata footer: created date, entity info, model name/version, analysis ID
- Files changed: `resources/js/pages/Fleet/AiAnalysisResults/Show.tsx`
- **Learnings for future iterations:**
  - `AiAnalysisResult.detailed_analysis` is cast to `array` in the model — always arrives as an object with mixed keys (finding, affected_vehicles, wear_percentage, etc.)
  - `recommendations` and `action_items` are string arrays — simple to map over
  - `business_impact` is a key-value object (risk, cost, downtime, etc.)
  - No `ai_job_run_id` FK on `ai_analysis_results` — can't link directly to job runs from the show page
  - SVG circular progress is straightforward: use `strokeDasharray` + `strokeDashoffset` with `-rotate-90` for top-start position
  - PRIORITY_CONFIG and TYPE_ICONS patterns from Index.tsx are reusable — consider extracting to shared file if more pages need them
---

## 2026-03-04 - US-031
- Added stagger animations to all dashboard sections using existing StaggerList/StaggerItem components
- Enhanced StaggerList with configurable `staggerDelay` and `delayChildren` props
- Enhanced StaggerItem with configurable `duration` prop
- Dashboard animation structure: outer StaggerList (80ms between sections) wraps 4 section StaggerItems, each 250ms duration. KPI cards have a nested StaggerList (60ms between cards)
- Sections animate in order: KPI cards → Primary Charts → AI Intelligence → Operational Detail
- Uses opacity + 8px y-translate with easeOut for smooth, jank-free entrance
- Respects prefers-reduced-motion via useReducedMotion hook
- Files changed: `resources/js/components/motion/stagger-list.tsx`, `resources/js/pages/Fleet/Dashboard.tsx`
- **Learnings for future iterations:**
  - StaggerList now accepts `staggerDelay` (default 0.03s) and `delayChildren` (default 0) props for customization
  - StaggerItem now accepts `duration` prop to override the default 0.2s
  - Nested StaggerList/StaggerItem works correctly — inner lists animate independently within their parent StaggerItem
  - The `y: 8` (8px) shift is subtle enough to avoid layout shift while still being perceptible
  - framer-motion's LazyMotion with domAnimation is already used — no additional bundle cost for these animations
---

## 2026-03-04 - US-032
- Integrated `AnimatedNumber` component into `FleetKpiCard` and `FleetHealthBanner` for count-up animation on KPI values
- `FleetKpiCard`: replaced static `{value.toLocaleString()}` with `<AnimatedNumber value={value} duration={400} />`
- `FleetHealthBanner`: replaced static `{score}` in `ScoreCircle` with `<AnimatedNumber value={score} duration={400} />`
- AnimatedNumber already uses framer-motion `useSpring` with `bounce: 0` for natural easing (not linear)
- AnimatedNumber already supports `prefix`/`suffix` props for currency/percentage formatting
- npm run build passes
- Files changed: `resources/js/components/fleet/fleet-kpi-card.tsx`, `resources/js/components/fleet/fleet-health-banner.tsx`
- **Learnings for future iterations:**
  - `AnimatedNumber` component was already fully built in US-014 — just needed to be wired into the KPI components
  - The component uses `useInView` with `once: true` so animation only triggers when scrolled into view, and `useSpring` for easing
  - `toLocaleString()` formatting is handled inside AnimatedNumber via `Math.round(latest).toLocaleString()` for integers
  - Respects `prefers-reduced-motion` automatically via `useReducedMotion` hook
---

## 2026-03-04 - US-033
- Added chart entrance animations to all recharts components in Dashboard.tsx
- Area charts (Fleet Activity): `isAnimationActive={true}`, `animationDuration={800}`, `animationBegin={150/200}`, `animationEasing="ease-out"` — smooth fill-in with slight stagger between trips and work_orders series
- Pie/donut charts (Cost Breakdown, Driver Safety): `startAngle={90}`, `endAngle={-270}` for clockwise draw from top, plus animation props matching area charts
- Line chart (Fuel Spend Trend): same animation props for left-to-right draw
- KPI sparklines deliberately kept at `isAnimationActive={false}` — they're tiny inline charts where animation would be distracting
- StaggerList already handles visual entrance stagger for sections; recharts `animationBegin` adds data-level delay on top
- npm run build passes
- Files changed: `resources/js/pages/Fleet/Dashboard.tsx`
- **Learnings for future iterations:**
  - recharts default `isAnimationActive` is `true`, but adding it explicitly makes intent clear
  - `animationBegin` (ms) delays chart data animation start — useful for stagger within a section
  - `startAngle={90}` + `endAngle={-270}` makes Pie draw clockwise from the 12 o'clock position
  - `animationEasing="ease-out"` gives a natural deceleration feel
---

## 2026-03-04 - US-034
- Added horizontal stacked bar chart to Vehicles Index page showing status distribution (Active/In Maintenance/Inactive)
- Backend: Added `inactive` count to the deferred `summary` prop in `VehicleController::index()`
- Frontend: Created inline `StatusDistributionBar` component using recharts `BarChart` (layout="vertical") + shadcn `ChartContainer`
- Chart shows color-coded segments with percentage labels in a legend row
- Chart only renders when `summary.total > 0`
- Files changed: `app/Http/Controllers/Fleet/VehicleController.php`, `resources/js/pages/Fleet/Vehicles/Index.tsx`
- **Learnings for future iterations:**
  - For horizontal stacked bars, use recharts `BarChart` with `layout="vertical"`, `XAxis type="number" hide`, `YAxis type="category" hide`, and `stackId` on each `Bar`
  - `radius` on stacked bars: first bar gets `[4,0,0,4]` (left corners), last bar gets `[0,4,4,0]` (right corners), middle bars get `0`
  - shadcn `ChartContainer` maps `ChartConfig` keys to CSS variables via `--color-{key}` — use `fill="var(--color-{key})"` in recharts components
  - The `summary` prop is deferred via `Inertia::defer()` — adding new fields is straightforward, just add the count query and include in the return array
---

## 2026-03-04 - US-035
- Added status donut chart to Work Orders Index page
- Backend: Added `statusCounts` deferred Inertia prop to `WorkOrderController::index()` with grouped counts (open=draft+pending+approved, in_progress, completed, cancelled)
- Frontend: Added `StatusDonut` component using recharts PieChart + shadcn ChartContainer/ChartTooltip/ChartLegend
- Donut renders in the summary bar area alongside existing stat cards using flex layout
- Color coding: blue (open), amber (in progress), green (completed), gray (cancelled)
- Zero-value segments filtered out; donut hidden when total is 0
- Files changed: `app/Http/Controllers/Fleet/WorkOrderController.php`, `resources/js/pages/Fleet/WorkOrders/Index.tsx`
- **Learnings for future iterations:**
  - Follow the pattern from Vehicles Index (US-034) for index page mini-charts — inline component + ChartContainer + deferred prop
  - WorkOrder statuses are: draft, pending, approved, in_progress, completed, cancelled — group logically for charts (open = draft+pending+approved)
  - Use `Inertia::defer()` with a named group for chart data to avoid blocking page load
  - Wrap summary bar + chart in a flex container to place them side by side on larger screens
---

## 2026-03-04 - US-036
- Added 30-day daily spend sparkline to Fuel Transactions Index page
- Backend: new `dailySpend` deferred Inertia prop in `FuelTransactionController::index()` — aggregates `SUM(total_cost)` grouped by `DATE(transaction_timestamp)` over 30 days, fills in zero for days with no transactions
- Frontend: `SpendSparkline` inline component using recharts `AreaChart` + `Area` with gradient fill, wrapped in shadcn `ChartContainer`, minimal styling (no axes, just the line/area)
- Chart placed between summary bar and filter form
- Files changed: `app/Http/Controllers/Fleet/FuelTransactionController.php`, `resources/js/pages/Fleet/FuelTransactions/Index.tsx`
- **Learnings for future iterations:**
  - `FuelTransaction.transaction_timestamp` is the key date field — use `DATE(transaction_timestamp)` for daily grouping
  - Fill in zero-value days using a loop over the date range to ensure the sparkline has continuous data points
  - Use `Area` with a gradient fill (`linearGradient` in `defs`) for a polished sparkline look instead of a plain `Line`
  - Follow same deferred prop pattern as summary — keeps initial page load fast
---

## 2026-03-04 - US-037
- Added radial/gauge chart to Compliance Items Index showing overall compliance health percentage
- Backend: added `complianceHealth` deferred Inertia prop in `ComplianceItemController::index()` computing percentage of items in "valid" status
- Frontend: added `ComplianceHealthGauge` component using recharts `RadialBarChart` + `RadialBar` + `PolarAngleAxis` with shadcn `ChartContainer`
- Color coding: green (>80%), amber (60-80%), red (<60%)
- Gauge placed alongside summary bar in a flex row layout (same pattern as Work Orders Index donut)
- Animated entrance (800ms ease-out) matching other index page charts
- npm run build passes, no new TS errors, Pint passes
- Files changed: `app/Http/Controllers/Fleet/ComplianceItemController.php`, `resources/js/pages/Fleet/ComplianceItems/Index.tsx`
- **Learnings for future iterations:**
  - Use `RadialBarChart` with `PolarAngleAxis` (type="number", domain=[0,100]) to create a gauge that fills proportionally — without `PolarAngleAxis`, the bar always fills 100%
  - SVG `<text>` with `<tspan>` elements is the standard way to add centered labels inside radial charts in recharts
  - Follow the Work Orders Index pattern for placing charts alongside summary bars: wrap in `flex flex-col gap-3 lg:flex-row lg:items-start`
  - `Inertia::defer()` second param is the prop name — keep it short and camelCase
---
