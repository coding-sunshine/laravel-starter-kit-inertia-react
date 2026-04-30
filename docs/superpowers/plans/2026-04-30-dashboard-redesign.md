# Dashboard Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the dropdown section switcher with a branded dark pill nav, dissolve the standalone Command Center into Executive Overview, and split the 5,799-line `dashboard.tsx` into 8 focused files.

**Architecture:** Single Inertia page (`/dashboard`) unchanged. The Laravel controller and all props are untouched. The frontend splits into a thin shell `dashboard.tsx` (~150 lines) plus 7 section files under `resources/js/pages/dashboard/`. Shared TypeScript interfaces move to `dashboard/types.ts`.

**Tech Stack:** React 19, Inertia v3, TypeScript, Tailwind CSS v4, Lucide icons.

---

## File Structure

```
resources/js/pages/
  dashboard.tsx                    ← MODIFY: become the thin shell
  dashboard/
    types.ts                       ← CREATE: all shared TypeScript interfaces
    ExecutiveOverview.tsx          ← CREATE: Command Center widgets + executive charts
    SidingOverview.tsx             ← CREATE: siding performance, penalty trend, PP distribution
    Operations.tsx                 ← CREATE: coal transport, daily rake details, live status
    PenaltyControl.tsx             ← CREATE: penalty trend, by-type, by-siding, yesterday
    RakePerformance.tsx            ← CREATE: thin wrapper around existing RakePerformanceSection
    LoaderOverloading.tsx          ← CREATE: thin wrapper around LoaderOverloadDashboardSection
    PowerPlant.tsx                 ← CREATE: thin wrapper around PowerPlantDispatchSection
```

---

## Task 1: Extract types to `dashboard/types.ts`

**Files:**
- Create: `resources/js/pages/dashboard/types.ts`
- Modify: `resources/js/pages/dashboard.tsx` (remove duplicated interfaces)

- [ ] **Step 1: Create `resources/js/pages/dashboard/types.ts`**

Copy these interfaces verbatim from `dashboard.tsx` (lines 225–889 cover the interface block):

```typescript
// resources/js/pages/dashboard/types.ts

export interface SidingOption {
    id: number;
    name: string;
    code: string;
}

export interface SidingStock {
    siding_id: number;
    opening_balance_mt: number;
    closing_balance_mt: number;
    total_rakes: number;
    received_mt: number;
    dispatched_mt: number;
    last_receipt_at: string | null;
    last_dispatch_at: string | null;
}

export interface SidingPerformanceItem {
    name: string;
    rakes: number;
    penalties: number;
    penalty_amount: number;
    penalty_rate: number;
}

export interface SidingWiseMonthlyPoint {
    month: string;
    [sidingName: string]: string | number;
}

export interface SidingComparisonItem {
    name: string;
    rakes_dispatched: number;
    on_time: number;
    vehicles: number;
    penalty_amount: number;
}

export interface SidingComparisonData {
    sidings: SidingComparisonItem[];
}

export interface DateWiseDispatchData {
    sidingNames: Record<number, string>;
    dates: Record<string, unknown>[];
}

export interface RakePerformanceItem {
    id: number;
    siding_id?: number;
    rake_number: string;
    rake_serial_number?: string | null;
    siding: string;
    dispatch_date: string;
    wagon_count: number | null;
    net_weight: number | null;
    over_load: number | null;
    under_load: number | null;
    loading_minutes: number | null;
    predicted_penalty_amount: number;
    predicted_penalty_count: number;
    actual_penalty_amount: number;
    actual_penalty_count: number;
    wagon_overloads?: Array<{
        wagon_number: string;
        over_load_mt: number;
        under_load_mt?: number | null;
        cc_capacity_mt?: number | null;
        net_weight_mt?: number | null;
        loader_id?: number | null;
        loader_name?: string | null;
        loader_operator_name?: string | null;
    }>;
}

export type RakePerformanceSummaryItem = Omit<RakePerformanceItem, 'loading_minutes' | 'wagon_overloads'> & {
    siding_id: number;
};

export interface LoaderInfo {
    id: number;
    name: string;
    siding: string;
}

export interface LoaderOverloadTrends {
    loaders: LoaderInfo[];
    monthly: Record<string, unknown>[];
}

export interface PowerPlantSidingBreakdown {
    rakes: number;
    weight_mt: number;
}

export interface PowerPlantDispatchItem {
    [key: string]: unknown;
    name: string;
    rakes: number;
    weight_mt: number;
    sidings: Record<string, PowerPlantSidingBreakdown>;
}

export interface DashboardFilters {
    period: string;
    from: string;
    to: string;
    siding_ids: number[];
    power_plant: string | null;
    rake_number: string | null;
    loader_id: number | null;
    loader_operator: string | null;
    underload_threshold: number;
    shift: string | null;
    penalty_type: number | null;
    rake_penalty_scope?: 'all' | 'with_penalties';
    daily_rake_date?: string;
    coal_transport_date?: string;
}

export interface FilterOptions {
    powerPlants: Array<{ value: string; label: string }>;
    loaders: Array<{ id: number; name: string; siding_name: string }>;
    shifts: Array<{ value: string; label: string }>;
    penaltyTypes: Array<{ value: string; label: string }>;
    loaderOperatorsByLoader?: Record<string, string[]>;
}

export interface DashboardKpis {
    rakesDispatchedToday: number;
    coalDispatchedToday: number;
    totalPenaltyThisMonth: number;
    predictedPenaltyRisk: number;
    avgLoadingTimeMinutes: number | null;
    trucksReceivedToday: number;
}

export interface PenaltyTrendPoint {
    date: string;
    label: string;
    total: number;
}

export interface PenaltyByTypePoint {
    name: string;
    value: number;
}

export interface PenaltyBySidingPoint {
    name: string;
    total: number;
}

export interface YesterdayPenaltyRow {
    type_code: string;
    type_name: string;
    amount: number;
}

export interface YesterdayPenaltyRake {
    rake_id: number;
    rake_number: string;
    total_penalty: number;
    penalties: YesterdayPenaltyRow[];
}

export interface YesterdayPredictedPenaltyItem {
    siding_id: number;
    siding_name: string;
    rakes: YesterdayPenaltyRake[];
}

export interface ExecutiveTimelineValue {
    trips: number | null;
    qty: number | null;
}

export interface ExecutiveTimelineSeries {
    dateWise: ExecutiveTimelineValue;
    monthWise: ExecutiveTimelineValue;
    fyWise: ExecutiveTimelineValue;
}

export interface ExecutiveYesterdayData {
    anchorDate: string;
    fyLabel: string;
    periods: Record<'yesterday' | 'today' | 'week' | 'month' | 'fy', { from: string; to: string }>;
    roadDispatch: {
        totals: Record<'yesterday' | 'today' | 'week' | 'month' | 'fy', { trips: number; qty: number }>;
        bySiding: Array<{
            sidingId: number;
            sidingName: string;
            totals: Record<'yesterday' | 'today' | 'week' | 'month' | 'fy', { trips: number; qty: number }>;
        }>;
    };
    railDispatch: {
        totals: Record<'yesterday' | 'today' | 'week' | 'month' | 'fy', { rakes: number; qty: number }>;
        bySiding: Array<{
            sidingId: number;
            sidingName: string;
            totals: Record<'yesterday' | 'today' | 'week' | 'month' | 'fy', { rakes: number; qty: number }>;
        }>;
    };
    obProduction: Record<'yesterday' | 'today' | 'week' | 'month' | 'fy', { trips: number; qty: number }>;
    coalProduction: Record<'yesterday' | 'today' | 'week' | 'month' | 'fy', { trips: number; qty: number }>;
    customRanges: {
        roadDispatch: {
            from: string;
            to: string;
            totals: { trips: number; qty: number };
            bySiding: Array<{ sidingId: number; sidingName: string; trips: number; qty: number }>;
            summary: { granularity: string; columns: string[]; data: Record<string, { trips: number; qty: number }> };
        };
        railDispatch: {
            from: string;
            to: string;
            totals: { rakes: number; qty: number };
            bySiding: Array<{ sidingId: number; sidingName: string; rakes: number; qty: number }>;
            summary: { granularity: string; columns: string[]; data: Record<string, { rakes: number; qty: number }> };
        };
        obProduction: {
            from: string;
            to: string;
            totals: { trips: number; qty: number };
            summary: { granularity: string; columns: string[]; data: Record<string, { trips: number; qty: number }> };
        };
        coalProduction: {
            from: string;
            to: string;
            totals: { trips: number; qty: number };
            summary: { granularity: string; columns: string[]; data: Record<string, { trips: number; qty: number }> };
        };
    };
    fySummary: {
        rows: Array<{
            fy: string;
            production: { obQty: number; coalQty: number };
            roadDispatch: { trips: number; qty: number };
            railDispatch: { rakes: number; qty: number };
        }>;
    };
    fyCharts: {
        cutoverDate: string;
        rows: Array<{
            fy: string;
            production: { obQty: number; coalQty: number };
            dispatch: { roadQty: number; railQty: number };
        }>;
    };
    penaltyBySidingByPeriod?: Record<'yesterday' | 'today' | 'month' | 'fy', PenaltyBySidingPoint[]>;
    powerPlantDispatchByPeriod?: Record<'yesterday' | 'today' | 'month' | 'fy', PowerPlantDispatchItem[]>;
}

export interface DashboardAlert {
    id: number;
    type: string;
    title: string;
    severity: string;
    rake_id: number | null;
    siding_id: number | null;
    created_at: string;
}

export interface LiveRakeStatusRow {
    rake_number: string;
    rake_serial_number?: string | null;
    siding_name: string;
    state: string;
    workflow_steps?: import('@/components/rake-workflow-progress').WorkflowSteps;
    time_elapsed: string;
    loading_date?: string;
    risk: string;
}

export interface DailyRakeDetailsRow {
    sl_no: number;
    siding_name: string;
    year: string;
    day_rakes: number;
    day_qty: number;
    month_rakes: number;
    month_qty: number;
    year_rakes: number;
    year_qty: number;
}

export interface DailyRakeDetailsData {
    date: string;
    rows: DailyRakeDetailsRow[];
    totals: {
        day_rakes: number;
        day_qty: number;
        month_rakes: number;
        month_qty: number;
        year_rakes: number;
        year_qty: number;
    };
}

export interface CoalTransportSidingMetric {
    siding_name: string;
    trips: number;
    qty: number;
}

export interface CoalTransportReportRow {
    sl_no: number;
    shift_label: string;
    siding_metrics: CoalTransportSidingMetric[];
    total_trips: number;
    total_qty: number;
}

export interface CoalTransportReportData {
    date: string;
    sidings: Array<{ id: number; name: string }>;
    rows: CoalTransportReportRow[];
    totals: { siding_metrics: CoalTransportSidingMetric[]; total_trips: number; total_qty: number };
}

export interface TruckReceiptHour {
    hour: string;
    label: string;
    count: number;
}

export interface ShiftWiseVehicleReceiptPoint {
    shift_label: string;
    [sidingName: string]: string | number;
}

export interface StockGaugeSidingItem {
    siding_id: number;
    siding_name: string;
    stock_available_mt: number;
    rake_required_mt: number;
    status: string;
}

export type StockGaugeData = StockGaugeSidingItem[];

export interface PenaltySummary {
    today_rs: number;
    trend_7d: { date: string; rs: number }[];
    preventable_pct: number;
}

export interface RakePipelineCard {
    rake_id: number;
    rake_number: string;
    siding_name: string;
    siding_code: string;
    wagon_count: number;
    overloaded_count: number;
    penalty_risk_rs: number;
    state: string;
    loading_date: string | null;
}

export interface ActiveRakePipelineData {
    loading: RakePipelineCard[];
    awaiting_clearance: RakePipelineCard[];
    dispatched: RakePipelineCard[];
}

export interface SidingRiskScoreData {
    siding_id: number;
    siding_name: string;
    siding_code: string;
    score: number;
    trend: string;
    risk_factors: string[];
    calculated_at: string | null;
}

export interface AlertRecord {
    id: number;
    type: string;
    title: string;
    body: string;
    severity: string;
    siding_id: number | null;
    rake_id: number | null;
    created_at: string;
}

export interface OperatorRake {
    rake_id: number;
    rake_number: string;
    siding_name: string;
    state: string;
    loaded: number;
    total: number;
    status: string;
    loading_date: string | null;
}

export type ExecutiveChartPeriodKey = 'yesterday' | 'today' | 'month' | 'fy';
```

- [ ] **Step 2: Verify file compiles**

```bash
cd /Users/apple/Code/clients/shar/railwayrack
npx tsc --noEmit 2>&1 | head -20
```

Expected: no errors from the new file (it's not imported anywhere yet).

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/dashboard/types.ts
git commit -m "feat: add dashboard/types.ts with shared TypeScript interfaces"
```

---

## Task 2: Redesign the `dashboard.tsx` shell

This is the most impactful task. It:
1. Replaces the current white sticky header + `<Select>` dropdown with a dark navy header + gold pill nav
2. Removes the standalone "Command Center" block (lines 4669–4703 in the current file)
3. Keeps all state variables, memos, and callbacks intact
4. Passes the Command Center props through to `ExecutiveOverview` (which Task 3 creates)

**Files:**
- Modify: `resources/js/pages/dashboard.tsx`

- [ ] **Step 1: Remove the Command Center section block**

In `dashboard.tsx`, find the block starting with:
```tsx
{/* ── Operations Command Center ── */}
<section className="mb-6 flex flex-col gap-4 px-4 pt-4 lg:px-6">
```
and ending with:
```tsx
{/* ── End Command Center ── */}
```
Delete these lines entirely (approximately lines 4669–4704). The Command Center widgets move to `ExecutiveOverview.tsx` in Task 3.

- [ ] **Step 2: Replace the sticky header and section Select with the dark header + pill nav**

Find the current sticky header block (starts at `<div className="-m-4 sm:-m-6 lg:-m-8">`). Replace the entire block from `-m-4` wrapper through the end of the sticky header `</div>` — specifically replace:

```tsx
{/* AppSidebarLayout adds p-4/sm:p-6/lg:p-8 around pages; cancel it for dashboard full-bleed layout. */}
<div className="-m-4 sm:-m-6 lg:-m-8">
    <div className="dashboard-page flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#FAFAFA] p-3">
    <div className="sticky top-0 z-10 -mx-3 flex flex-col gap-1 bg-[#FAFAFA] px-3 pb-2 pt-1">
        <div className="flex flex-col">
            <h2 className="text-xl font-semibold tracking-tight">
                Management Dashboard
            </h2>
            {mainDateRangeLabel && activeSection !== 'executive-overview' && (
                <span className="mt-1 inline-flex items-center self-start rounded-full border border-green-500/70 bg-green-50 px-3 py-0.5 text-[11px] font-medium text-green-700">
                    {mainDateRangeLabel} ({periodLabel})
                </span> 
            )}
        </div>
        <div className="flex flex-wrap items-center justify-end gap-2">
            {activeSection !== 'executive-overview' && hasActiveFilters && !filtersExpanded && (
                <span className="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                    {activeFilterCount} filter{activeFilterCount !== 1 ? 's' : ''} applied
                </span>
            )}
            {activeSection !== 'executive-overview' && (
                <>
                    <Button
                        type="button"
                        variant={filtersExpanded ? 'secondary' : hasActiveFilters ? 'default' : 'outline'}
                        size="sm"
                        className="shrink-0 rounded-[10px] relative"
                        onClick={() => setFiltersExpanded((v) => !v)}
                    >
                        <Filter className="size-4 shrink-0" />
                        <span className="ml-1.5">Filters</span>
                        {hasActiveFilters && (
                            <span className="ml-1.5 flex size-5 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground">
                                {activeFilterCount > 9 ? '9+' : activeFilterCount}
                            </span>
                        )}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="shrink-0 rounded-[10px]"
                        onClick={() => {
                            const basePath = dashboard().url.split('?')[0] || dashboard().url;
                            router.get(basePath, { section: activeSection }, { preserveState: false, preserveScroll: true });
                        }}
                    >
                        Reset
                    </Button>
                </>
            )}
            {activeSection === 'executive-overview' && !!props.executiveYesterday && showExecutiveYesterdayViewToggle ? (
                <div className="flex items-center gap-2">
                    <select
                        value={executiveYesterdayViewMode}
                        onChange={(e) => setExecutiveYesterdayViewMode(e.target.value as 'table' | 'charts')}
                        className="rounded-[10px] border border-gray-200 bg-white px-2.5 py-1.5 text-sm shadow-sm"
                    >
                        <option value="charts">Bar Chart View</option>
                        <option value="table">Table View</option>
                    </select>
                </div>
            ) : null}
            {visibleSections.length === 0 ? (
                <span className="text-xs text-muted-foreground">No dashboard sections enabled for your role.</span>
            ) : (
            <Select value={activeSection} onValueChange={setActiveSection}>
                <SelectTrigger className="min-w-[200px] rounded-[10px] border border-gray-200 bg-white shadow-sm w-full sm:w-auto">
                    <SelectValue placeholder="Select section" />
                </SelectTrigger>
                <SelectContent>
                    {visibleSections.map((s) => (
                        <SelectItem key={s.id} value={s.id}>
                            {s.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            )}
            {filtersExpanded && sidings.length > 0 && (
                <DashboardFiltersBar
                    sidings={sidings}
                    filters={filters}
                    filterOptions={filterOptions}
                    currentSection={activeSection}
                    inline
                    onClose={() => setFiltersExpanded(false)}
                />
            )}
        </div>
    </div>
```

With this new header:

```tsx
{/* AppSidebarLayout adds p-4/sm:p-6/lg:p-8 around pages; cancel it for dashboard full-bleed layout. */}
<div className="-m-4 sm:-m-6 lg:-m-8">
    <div className="dashboard-page flex h-full flex-1 flex-col overflow-x-auto">

    {/* ── Dark Header ── */}
    <div className="sticky top-0 z-10 flex flex-col" style={{ background: '#1a1a2e' }}>
        {/* Title row */}
        <div className="flex items-center justify-between px-4 py-3 lg:px-6">
            <h2 className="text-lg font-semibold tracking-tight text-white">
                Management Dashboard
            </h2>
            <div className="flex items-center gap-2">
                {activeSection === 'executive-overview' && !!props.executiveYesterday && showExecutiveYesterdayViewToggle && (
                    <select
                        value={executiveYesterdayViewMode}
                        onChange={(e) => setExecutiveYesterdayViewMode(e.target.value as 'table' | 'charts')}
                        className="rounded-lg border border-slate-600 bg-slate-800 px-2.5 py-1.5 text-sm text-white shadow-sm"
                    >
                        <option value="charts">Bar Chart View</option>
                        <option value="table">Table View</option>
                    </select>
                )}
                {activeSection !== 'executive-overview' && (
                    <div className="flex items-center gap-2">
                        {mainDateRangeLabel && (
                            <span className="inline-flex items-center rounded-full border border-green-500/50 bg-green-900/30 px-3 py-0.5 text-[11px] font-medium text-green-300">
                                {mainDateRangeLabel} ({periodLabel})
                            </span>
                        )}
                        {hasActiveFilters && !filtersExpanded && (
                            <span className="rounded-full bg-white/10 px-2.5 py-1 text-xs font-medium text-white/80">
                                {activeFilterCount} filter{activeFilterCount !== 1 ? 's' : ''} applied
                            </span>
                        )}
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="rounded-lg border border-slate-600 text-white/80 hover:bg-white/10 hover:text-white"
                            onClick={() => setFiltersExpanded((v) => !v)}
                        >
                            <Filter className="size-4 shrink-0" />
                            <span className="ml-1.5">Filters</span>
                            {hasActiveFilters && (
                                <span className="ml-1.5 flex size-5 items-center justify-center rounded-full bg-white/20 text-[10px] font-bold text-white">
                                    {activeFilterCount > 9 ? '9+' : activeFilterCount}
                                </span>
                            )}
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="rounded-lg border border-slate-600 text-white/60 hover:bg-white/10 hover:text-white"
                            onClick={() => {
                                const basePath = dashboard().url.split('?')[0] || dashboard().url;
                                router.get(basePath, { section: activeSection }, { preserveState: false, preserveScroll: true });
                            }}
                        >
                            Reset
                        </Button>
                    </div>
                )}
            </div>
        </div>

        {/* Pill nav row */}
        <div className="flex flex-wrap gap-2 px-4 pb-3 lg:px-6" style={{ background: '#0f0f1e' }}>
            {visibleSections.length === 0 ? (
                <span className="text-xs text-slate-400">No dashboard sections enabled for your role.</span>
            ) : (
                visibleSections.map((s) => (
                    <button
                        key={s.id}
                        type="button"
                        onClick={() => setActiveSection(s.id)}
                        className={
                            s.id === activeSection
                                ? 'cursor-pointer rounded-full px-4 py-1 text-sm font-semibold transition-colors'
                                : 'cursor-pointer rounded-full border border-slate-700 px-4 py-1 text-sm text-slate-400 transition-colors hover:border-slate-500 hover:text-slate-200'
                        }
                        style={s.id === activeSection ? { background: '#1a1a2e', color: '#d4af37' } : undefined}
                    >
                        {s.label}
                    </button>
                ))
            )}
        </div>

        {/* Inline filters bar (non-executive sections) */}
        {filtersExpanded && sidings.length > 0 && (
            <div style={{ background: '#0f0f1e' }} className="px-4 pb-3 lg:px-6">
                <DashboardFiltersBar
                    sidings={sidings}
                    filters={filters}
                    filterOptions={filterOptions}
                    currentSection={activeSection}
                    inline
                    onClose={() => setFiltersExpanded(false)}
                />
            </div>
        )}
    </div>
    {/* ── End Dark Header ── */}

    <div className="flex min-w-0 flex-1 gap-3 bg-[#FAFAFA] p-3">
        <div className="min-w-0 flex-1 space-y-6">
```

- [ ] **Step 3: Remove now-unused import `Select, SelectContent, SelectItem, SelectTrigger, SelectValue`**

In the import block at the top of `dashboard.tsx`, remove:
```tsx
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
```

- [ ] **Step 4: Verify the page still renders**

```bash
php artisan test --compact --filter=DashboardTest 2>/dev/null || echo "no test yet, check browser"
```

Open https://rrmanagementlatest.test/dashboard — you should see the dark navy header, gold pill nav, and sections switching correctly. The Command Center block should be gone from the top.

- [ ] **Step 5: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/pages/dashboard.tsx
git commit -m "feat: replace dropdown section switcher with dark navy header and pill nav"
```

---

## Task 3: Create `ExecutiveOverview.tsx`

This file receives the Command Center props (currently rendered above the switcher) and the existing executive section props. It renders both together.

**Files:**
- Create: `resources/js/pages/dashboard/ExecutiveOverview.tsx`
- Modify: `resources/js/pages/dashboard.tsx` (wire it up in the `activeSection === 'executive-overview'` block)

- [ ] **Step 1: Create `resources/js/pages/dashboard/ExecutiveOverview.tsx`**

```tsx
import { ActiveRakePipeline } from '@/components/dashboard/active-rake-pipeline';
import { AlertFeed } from '@/components/dashboard/alert-feed';
import { DispatchSummary } from '@/components/dashboard/dispatch-summary';
import { OperatorRakeWidget } from '@/components/dashboard/operator-rake-widget';
import { OverloadPatternsWidget } from '@/components/dashboard/overload-patterns-widget';
import { PenaltyExposureStrip } from '@/components/dashboard/penalty-exposure-strip';
import { PenaltyPredictionsWidget } from '@/components/dashboard/penalty-predictions-widget';
import { SidingCoalStock } from '@/components/dashboard/siding-coal-stock';
import { SidingRiskScoreWidget } from '@/components/dashboard/siding-risk-score';
import { SlidingNumber } from '@/components/SlidingNumber';
import type {
    ActiveRakePipelineData,
    AlertRecord,
    ExecutiveChartPeriodKey,
    ExecutiveYesterdayData,
    OperatorRake,
    PenaltyBySidingPoint,
    PenaltySummary,
    PowerPlantDispatchItem,
    SidingOption,
    SidingRiskScoreData,
    SidingStock,
} from './types';
import { ExecutiveYesterdaySection } from '../dashboard';

const MT_PER_RAKE_LOAD = 3500;

const SIDING_ACCENT: Record<string, string> = {
    Dumka: '#3B82F6',
    Kurwa: '#10B981',
    Pakur: '#F59E0B',
};

interface Props {
    // Command Center widgets
    isExecutive: boolean;
    operatorRake: OperatorRake | null;
    penaltySummary: PenaltySummary | undefined;
    activeRakePipeline: ActiveRakePipelineData | undefined;
    sidingStocksMap: Record<number, SidingStock>;
    riskScores: Record<number, SidingRiskScoreData>;
    alertsData: Record<string, AlertRecord[]>;
    penaltyPredictions: Array<{
        siding_name: string;
        risk_level: 'high' | 'medium' | 'low';
        predicted_amount_min: number;
        predicted_amount_max: number;
        top_recommendation: string | null;
    }>;
    overloadPatterns: Array<{
        siding_name: string;
        patterns: Array<{
            wagon_type: string;
            overload_rate_percent: number;
            overloaded_count: number;
            total_count: number;
        }>;
    }>;
    // Coal stock strip
    filteredSidings: SidingOption[];
    sidingStocks: Record<number, SidingStock>;
    canWidget: (name: string) => boolean;
    // Executive chart section
    executiveYesterday: ExecutiveYesterdayData | undefined;
    executiveYesterdayViewMode: 'table' | 'charts';
    penaltyBySiding: PenaltyBySidingPoint[];
    powerPlantDispatch: PowerPlantDispatchItem[];
}

export function ExecutiveOverview({
    isExecutive,
    operatorRake,
    penaltySummary,
    activeRakePipeline,
    sidingStocksMap,
    riskScores,
    alertsData,
    penaltyPredictions,
    overloadPatterns,
    filteredSidings,
    sidingStocks,
    canWidget,
    executiveYesterday,
    executiveYesterdayViewMode,
    penaltyBySiding,
    powerPlantDispatch,
}: Props) {
    return (
        <div className="space-y-6">
            {/* ── AI Insights / Command Center ── */}
            <div className="flex flex-col gap-4">
                {!isExecutive && <OperatorRakeWidget rake={operatorRake} />}

                {isExecutive && (
                    <>
                        {penaltySummary && <PenaltyExposureStrip data={penaltySummary} />}

                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            {activeRakePipeline && (
                                <div className="lg:col-span-2">
                                    <ActiveRakePipeline data={activeRakePipeline} />
                                </div>
                            )}
                            <DispatchSummary stocks={sidingStocksMap} />
                        </div>

                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <SidingCoalStock stocks={sidingStocksMap} />
                            <SidingRiskScoreWidget scores={riskScores} />
                            <AlertFeed alerts={alertsData} />
                        </div>

                        <PenaltyPredictionsWidget predictions={penaltyPredictions} />
                        <OverloadPatternsWidget overloadPatterns={overloadPatterns} />
                    </>
                )}
            </div>

            {/* ── Coal stock strip ── */}
            {canWidget('dashboard.widgets.global_coal_stock_strip') && filteredSidings.length > 0 && (
                <div className="space-y-1">
                    <p className="text-[10px] text-gray-500">
                        Coal stock updates live from the ledger (and real-time events when connected).
                    </p>
                    <div className="flex gap-2 overflow-x-auto pb-0.5 lg:grid lg:grid-cols-3 lg:gap-2 lg:overflow-visible">
                        {filteredSidings.map((s) => {
                            const stock = sidingStocks[s.id];
                            const stockMt = stock?.closing_balance_mt ?? 0;
                            const rakesLoadable = Math.floor(stockMt / MT_PER_RAKE_LOAD);
                            const accent = SIDING_ACCENT[s.name] ?? '#6B7280';
                            return (
                                <div
                                    key={s.id}
                                    className="dashboard-card flex min-w-[220px] flex-1 flex-col rounded-lg border-0 p-2 sm:min-w-0"
                                    style={{ borderTop: `4px solid ${accent}` }}
                                >
                                    <div className="text-[11px] font-semibold leading-snug text-gray-600">
                                        {s.name}
                                    </div>
                                    <div className="mt-1 flex items-baseline justify-between gap-3">
                                        <div>
                                            <p className="text-sm font-bold leading-tight tabular-nums text-gray-900">
                                                <SlidingNumber
                                                    value={stockMt}
                                                    format={(v) =>
                                                        `${v.toLocaleString(undefined, { maximumFractionDigits: 0 })} MT`
                                                    }
                                                />
                                            </p>
                                            <p className="text-[10px] text-gray-600">Stock</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-sm font-bold leading-tight tabular-nums text-gray-900">
                                                {rakesLoadable}
                                            </p>
                                            <p className="text-[10px] text-gray-600">Rakes</p>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* ── Executive charts / tables ── */}
            {executiveYesterday ? (
                <ExecutiveYesterdaySection
                    data={executiveYesterday}
                    viewMode={executiveYesterdayViewMode}
                    penaltyBySiding={penaltyBySiding}
                    powerPlantDispatch={powerPlantDispatch}
                    canWidget={canWidget}
                />
            ) : (
                <div className="dashboard-card rounded-xl border-0 p-6 text-sm text-gray-600">
                    Yesterday data is not available.
                </div>
            )}
        </div>
    );
}
```

**Note:** The coal stock strip in this file is a simplified version — if the original has additional fields (last receipt time, opening balance, etc.), copy those from `dashboard.tsx` lines ~4800–4860 verbatim. The key structure above is correct; flesh it out if there's more content in the original.

- [ ] **Step 2: Export `ExecutiveYesterdaySection` from `dashboard.tsx`**

In `dashboard.tsx`, find the line:
```tsx
function ExecutiveYesterdaySection({
```
Change it to:
```tsx
export function ExecutiveYesterdaySection({
```

- [ ] **Step 3: Wire `ExecutiveOverview` into the shell**

In `dashboard.tsx`, find:
```tsx
{activeSection === 'executive-overview' && (
    <div className="space-y-6">
        {executiveYesterday ? (
            <ExecutiveYesterdaySection
                data={executiveYesterday}
                viewMode={executiveYesterdayViewMode}
                penaltyBySiding={penaltyBySiding}
                powerPlantDispatch={powerPlantDispatch}
                canWidget={canWidget}
            />
        ) : (
            <div className="dashboard-card rounded-xl border-0 p-6 text-sm text-gray-600">Yesterday data is not available.</div>
        )}
    </div>
)}
```

Replace with:
```tsx
{activeSection === 'executive-overview' && (
    <ExecutiveOverview
        isExecutive={isExecutive}
        operatorRake={operatorRake}
        penaltySummary={penaltySummary}
        activeRakePipeline={activeRakePipeline}
        sidingStocksMap={sidingStocksMap}
        riskScores={riskScores}
        alertsData={alertsData}
        penaltyPredictions={penaltyPredictions}
        overloadPatterns={overloadPatterns}
        filteredSidings={filteredSidings}
        sidingStocks={sidingStocks}
        canWidget={canWidget}
        executiveYesterday={executiveYesterday}
        executiveYesterdayViewMode={executiveYesterdayViewMode}
        penaltyBySiding={penaltyBySiding}
        powerPlantDispatch={powerPlantDispatch}
    />
)}
```

- [ ] **Step 4: Add import for `ExecutiveOverview` in `dashboard.tsx`**

Near the top of `dashboard.tsx`, add:
```tsx
import { ExecutiveOverview } from '@/pages/dashboard/ExecutiveOverview';
```

- [ ] **Step 5: Remove the global coal stock strip from `dashboard.tsx`**

Find and delete the block:
```tsx
{canWidget('dashboard.widgets.global_coal_stock_strip') && filteredSidings.length > 0 && (
    <div className="space-y-1">
        ...all the coal stock content...
    </div>
)}
```
This content is now inside `ExecutiveOverview`.

- [ ] **Step 6: Verify in browser**

Open https://rrmanagementlatest.test/dashboard — Executive Overview should show the AI widgets at top, coal stock cards below, then the charts. Other sections should NOT show the coal stock strip.

- [ ] **Step 7: Commit**

```bash
git add resources/js/pages/dashboard/ExecutiveOverview.tsx resources/js/pages/dashboard.tsx
git commit -m "feat: extract ExecutiveOverview component, merge Command Center into it"
```

---

## Task 4: Create `SidingOverview.tsx`

**Files:**
- Create: `resources/js/pages/dashboard/SidingOverview.tsx`
- Modify: `resources/js/pages/dashboard.tsx`

- [ ] **Step 1: Identify the block to extract**

In `dashboard.tsx`, find the block:
```tsx
{activeSection === 'siding-overview' && (
    <div className="space-y-6">
        ...
    </div>
)}
```
Note its line range. Copy the content of the inner `<div className="space-y-6">` (everything inside it).

- [ ] **Step 2: Create `resources/js/pages/dashboard/SidingOverview.tsx`**

```tsx
import { BarChart3, Calendar, Factory } from 'lucide-react';
import { Cell, Legend, Pie, PieChart as RechartsPieChart, ResponsiveContainer, Tooltip } from 'recharts';
import { CartesianGrid, RechartsAreaChart, Area, XAxis, YAxis } from 'recharts';
// (copy the exact recharts imports used in the siding-overview block)
import { SidingPerformanceSection } from '../dashboard';
import { DateWiseDispatchSection } from '../dashboard';
import { SectionHeader } from '../dashboard';
import type {
    DashboardFilters,
    ExecutiveChartPeriodKey,
    PenaltyBySidingPoint,
    PowerPlantDispatchItem,
    SidingComparisonData,
    SidingOption,
    SidingPerformanceItem,
    SidingWiseMonthlyPoint,
    DateWiseDispatchData,
} from './types';

interface Props {
    canWidget: (name: string) => boolean;
    sidingPerformance: SidingPerformanceItem[];
    penaltyTrendDaily: Array<{ date: string; label: string; total: number }>;
    powerPlantDispatch: PowerPlantDispatchItem[];
    penaltyBySidingForSidingOverview: PenaltyBySidingPoint[];
    sidingWiseMonthly: SidingWiseMonthlyPoint[];
    sidingRadar: SidingComparisonData;
    dateWiseDispatch: DateWiseDispatchData;
    sidings: SidingOption[];
    sidingOverviewPenaltyPeriod: ExecutiveChartPeriodKey;
    setSidingOverviewPenaltyPeriod: (period: ExecutiveChartPeriodKey) => void;
    filters: DashboardFilters;
}

export function SidingOverview(props: Props) {
    const {
        canWidget,
        sidingPerformance,
        penaltyTrendDaily,
        powerPlantDispatch,
    } = props;

    return (
        <div className="space-y-6">
            {/* PASTE THE EXACT JSX from the activeSection === 'siding-overview' block in dashboard.tsx here */}
            {/* Remove the outer {activeSection === 'siding-overview' && (...)} wrapper */}
        </div>
    );
}
```

**Important:** The comment above is a placeholder only for illustration. The actual implementation must paste the verbatim JSX from `dashboard.tsx` — do NOT leave the comment in the final file. Copy all the content from inside `{activeSection === 'siding-overview' && (<div className="space-y-6">...</div>)}` into the return body.

Also export `SidingPerformanceSection`, `DateWiseDispatchSection`, and `SectionHeader` from `dashboard.tsx` by adding `export` to their function declarations, then import them in this file.

- [ ] **Step 3: Wire it in `dashboard.tsx`**

Replace:
```tsx
{activeSection === 'siding-overview' && (
    <div className="space-y-6">
        ...all the JSX...
    </div>
)}
```

With:
```tsx
{activeSection === 'siding-overview' && (
    <SidingOverview
        canWidget={canWidget}
        sidingPerformance={sidingPerformance}
        penaltyTrendDaily={penaltyTrendDaily}
        powerPlantDispatch={powerPlantDispatch}
        penaltyBySidingForSidingOverview={penaltyBySidingForSidingOverview}
        sidingWiseMonthly={sidingWiseMonthly}
        sidingRadar={sidingRadar}
        dateWiseDispatch={dateWiseDispatch}
        sidings={sidings}
        sidingOverviewPenaltyPeriod={sidingOverviewPenaltyPeriod}
        setSidingOverviewPenaltyPeriod={setSidingOverviewPenaltyPeriod}
        filters={filters}
    />
)}
```

- [ ] **Step 4: Add import**

```tsx
import { SidingOverview } from '@/pages/dashboard/SidingOverview';
```

- [ ] **Step 5: Verify in browser**

Switch to "Siding overview" pill — content should be identical to before.

- [ ] **Step 6: Commit**

```bash
git add resources/js/pages/dashboard/SidingOverview.tsx resources/js/pages/dashboard.tsx
git commit -m "feat: extract SidingOverview section component"
```

---

## Task 5: Create `Operations.tsx`

**Files:**
- Create: `resources/js/pages/dashboard/Operations.tsx`
- Modify: `resources/js/pages/dashboard.tsx`

- [ ] **Step 1: Create `resources/js/pages/dashboard/Operations.tsx`**

Props interface:
```tsx
interface Props {
    canWidget: (name: string) => boolean;
    coalTransportReport: import('./types').CoalTransportReportData | undefined;
    sidings: import('./types').SidingOption[];
    canExportCoalTransport: boolean;
    dailyRakeDetails: import('./types').DailyRakeDetailsData | undefined;
    truckReceiptTrend: import('./types').TruckReceiptHour[];
    shiftWiseVehicleReceipt: import('./types').ShiftWiseVehicleReceiptPoint[];
    stockGauge: import('./types').StockGaugeData | undefined;
    liveRakeStatus: import('./types').LiveRakeStatusRow[];
    filteredSidings: import('./types').SidingOption[];
    filterOptions: import('./types').FilterOptions;
    filters: import('./types').DashboardFilters;
    navigateDashboard: (overrides?: Record<string, unknown>) => void;
    mainDateRangeLabel: string | null;
}
```

Extract the verbatim JSX from `{activeSection === 'operations' && (...)}` block in `dashboard.tsx`.

- [ ] **Step 2: Wire it in `dashboard.tsx`**

Replace the `activeSection === 'operations'` block with:
```tsx
{activeSection === 'operations' && (
    <Operations
        canWidget={canWidget}
        coalTransportReport={coalTransportReport}
        sidings={sidings}
        canExportCoalTransport={canExportCoalTransport}
        dailyRakeDetails={dailyRakeDetails}
        truckReceiptTrend={truckReceiptTrend}
        shiftWiseVehicleReceipt={shiftWiseVehicleReceipt}
        stockGauge={stockGauge}
        liveRakeStatus={liveRakeStatus}
        filteredSidings={filteredSidings}
        filterOptions={filterOptions}
        filters={filters}
        navigateDashboard={navigateDashboard}
        mainDateRangeLabel={mainDateRangeLabel}
    />
)}
```

- [ ] **Step 3: Add import, verify, commit**

```bash
git add resources/js/pages/dashboard/Operations.tsx resources/js/pages/dashboard.tsx
git commit -m "feat: extract Operations section component"
```

---

## Task 6: Create `PenaltyControl.tsx`

**Files:**
- Create: `resources/js/pages/dashboard/PenaltyControl.tsx`
- Modify: `resources/js/pages/dashboard.tsx`

- [ ] **Step 1: Create `resources/js/pages/dashboard/PenaltyControl.tsx`**

Props interface:
```tsx
interface Props {
    canWidget: (name: string) => boolean;
    penaltyTrendDaily: import('./types').PenaltyTrendPoint[];
    penaltyByType: import('./types').PenaltyByTypePoint[];
    penaltyBySiding: import('./types').PenaltyBySidingPoint[];
    yesterdayPredictedPenalties: import('./types').YesterdayPredictedPenaltyItem[];
    predictedVsActualPenalty: { predicted: number; actual: number; bySiding?: Array<{ name: string; predicted: number; actual: number }> };
    sidings: import('./types').SidingOption[];
    filters: import('./types').DashboardFilters;
}
```

Extract the verbatim JSX from `{activeSection === 'penalty-control' && (...)}` block.

- [ ] **Step 2: Wire it in `dashboard.tsx`**

```tsx
{activeSection === 'penalty-control' && (
    <PenaltyControl
        canWidget={canWidget}
        penaltyTrendDaily={penaltyTrendDaily}
        penaltyByType={penaltyByType}
        penaltyBySiding={penaltyBySiding}
        yesterdayPredictedPenalties={yesterdayPredictedPenalties}
        predictedVsActualPenalty={predictedVsActualPenalty}
        sidings={sidings}
        filters={filters}
    />
)}
```

- [ ] **Step 3: Add import, verify, commit**

```bash
git add resources/js/pages/dashboard/PenaltyControl.tsx resources/js/pages/dashboard.tsx
git commit -m "feat: extract PenaltyControl section component"
```

---

## Task 7: Create `RakePerformance.tsx`

**Files:**
- Create: `resources/js/pages/dashboard/RakePerformance.tsx`
- Modify: `resources/js/pages/dashboard.tsx`

- [ ] **Step 1: Create `resources/js/pages/dashboard/RakePerformance.tsx`**

```tsx
import { RakePerformanceSection } from '../dashboard';
import type { DashboardFilters, SidingOption } from './types';

interface Props {
    canWidget: (name: string) => boolean;
    filters: DashboardFilters;
    allSidingIds: number[];
    filteredSidings: SidingOption[];
    onRakePenaltyScopeChange: (scope: 'all' | 'with_penalties') => void;
    navigateToLoaderTrends: (loaderId: number, loaderName: string) => void;
}

export function RakePerformance({
    canWidget,
    filters,
    allSidingIds,
    filteredSidings,
    onRakePenaltyScopeChange,
    navigateToLoaderTrends,
}: Props) {
    if (!canWidget('dashboard.widgets.rake_performance')) return null;

    return (
        <RakePerformanceSection
            filters={filters}
            allSidingIds={allSidingIds}
            sidings={filteredSidings}
            rakePenaltyScope={filters.rake_penalty_scope ?? 'all'}
            onRakePenaltyScopeChange={onRakePenaltyScopeChange}
            onNavigateToLoader={navigateToLoaderTrends}
        />
    );
}
```

Also export `RakePerformanceSection` from `dashboard.tsx` by adding `export` to its function declaration.

- [ ] **Step 2: Wire it in `dashboard.tsx`**

Replace:
```tsx
{activeSection === 'rake-performance' && canWidget('dashboard.widgets.rake_performance') && (
    <RakePerformanceSection ... />
)}
```

With:
```tsx
{activeSection === 'rake-performance' && (
    <RakePerformance
        canWidget={canWidget}
        filters={filters}
        allSidingIds={allSidingIds}
        filteredSidings={filteredSidings}
        onRakePenaltyScopeChange={onRakePenaltyScopeChange}
        navigateToLoaderTrends={navigateToLoaderTrends}
    />
)}
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/dashboard/RakePerformance.tsx resources/js/pages/dashboard.tsx
git commit -m "feat: extract RakePerformance section component"
```

---

## Task 8: Create `LoaderOverloading.tsx`

**Files:**
- Create: `resources/js/pages/dashboard/LoaderOverloading.tsx`
- Modify: `resources/js/pages/dashboard.tsx`

- [ ] **Step 1: Create `resources/js/pages/dashboard/LoaderOverloading.tsx`**

```tsx
import { LoaderOverloadDashboardSection } from '@/components/dashboard/loader-overload-dashboard-section';

interface Props {
    canWidget: (name: string) => boolean;
    buildLoaderOverloadApiParams: (args: { page?: number; perPage?: number }) => string;
    defaultDetailUnderloadPercent: number;
    mainDateRangeLabel: string | null;
    loaderIdFromUrl: number | null;
    loaderOverloadFilterKey: string;
}

export function LoaderOverloading({
    canWidget,
    buildLoaderOverloadApiParams,
    defaultDetailUnderloadPercent,
    mainDateRangeLabel,
    loaderIdFromUrl,
    loaderOverloadFilterKey,
}: Props) {
    if (!canWidget('dashboard.widgets.loader_overload_trends')) return null;

    return (
        <LoaderOverloadDashboardSection
            buildApiSearchParams={buildLoaderOverloadApiParams}
            defaultDetailUnderloadPercent={defaultDetailUnderloadPercent}
            mainDateRangeLabel={mainDateRangeLabel}
            loaderIdFromUrl={loaderIdFromUrl}
            filterKey={loaderOverloadFilterKey}
        />
    );
}
```

- [ ] **Step 2: Wire it in `dashboard.tsx`**

Replace:
```tsx
{activeSection === 'loader-overload' && canWidget('dashboard.widgets.loader_overload_trends') && (
    <LoaderOverloadDashboardSection ... />
)}
```

With:
```tsx
{activeSection === 'loader-overload' && (
    <LoaderOverloading
        canWidget={canWidget}
        buildLoaderOverloadApiParams={buildLoaderOverloadApiParams}
        defaultDetailUnderloadPercent={filters.underload_threshold}
        mainDateRangeLabel={mainDateRangeLabel}
        loaderIdFromUrl={filters.loader_id}
        loaderOverloadFilterKey={loaderOverloadFilterKey}
    />
)}
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/dashboard/LoaderOverloading.tsx resources/js/pages/dashboard.tsx
git commit -m "feat: extract LoaderOverloading section component"
```

---

## Task 9: Create `PowerPlant.tsx`

**Files:**
- Create: `resources/js/pages/dashboard/PowerPlant.tsx`
- Modify: `resources/js/pages/dashboard.tsx`

- [ ] **Step 1: Create `resources/js/pages/dashboard/PowerPlant.tsx`**

```tsx
import { PowerPlantDispatchSection } from '../dashboard';
import type { PowerPlantDispatchItem } from './types';

interface Props {
    canWidget: (name: string) => boolean;
    powerPlantDispatch: PowerPlantDispatchItem[];
}

export function PowerPlant({ canWidget, powerPlantDispatch }: Props) {
    if (!canWidget('dashboard.widgets.power_plant_dispatch_section')) return null;

    return <PowerPlantDispatchSection data={powerPlantDispatch} />;
}
```

Also export `PowerPlantDispatchSection` from `dashboard.tsx` by adding `export` to its function declaration.

- [ ] **Step 2: Wire it in `dashboard.tsx`**

Replace:
```tsx
{activeSection === 'power-plant' && canWidget('dashboard.widgets.power_plant_dispatch_section') && (
    <PowerPlantDispatchSection data={powerPlantDispatch} />
)}
```

With:
```tsx
{activeSection === 'power-plant' && (
    <PowerPlant
        canWidget={canWidget}
        powerPlantDispatch={powerPlantDispatch}
    />
)}
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/dashboard/PowerPlant.tsx resources/js/pages/dashboard.tsx
git commit -m "feat: extract PowerPlant section component"
```

---

## Task 10: Clean up `dashboard.tsx`

**Files:**
- Modify: `resources/js/pages/dashboard.tsx`

- [ ] **Step 1: Remove dead imports**

Remove any imports that are no longer used in `dashboard.tsx` now that section JSX has moved out. Run:

```bash
npx tsc --noEmit 2>&1 | grep "is declared but"
```

Remove each flagged unused import.

- [ ] **Step 2: Remove the `ActiveRakePipeline` interface**

The `ActiveRakePipeline` interface in `dashboard.tsx` conflicts with the component import of the same name from `@/components/dashboard/active-rake-pipeline`. Since types are now in `dashboard/types.ts` as `ActiveRakePipelineData`, remove the old local interface definition from `dashboard.tsx`.

- [ ] **Step 3: Final TypeScript check**

```bash
npx tsc --noEmit 2>&1
```

Expected: no errors.

- [ ] **Step 4: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 5: Full test suite**

```bash
php artisan test --compact
```

Expected: all tests pass.

- [ ] **Step 6: Verify dashboard in browser**

Check each pill section switches correctly:
- Executive (shows AI widgets + coal stock + charts)
- Siding Overview
- Operations
- Penalty Control
- Rake Performance
- Loader Overloading
- Power Plant

- [ ] **Step 7: Commit**

```bash
git add resources/js/pages/dashboard.tsx
git commit -m "chore: remove dead imports and clean up dashboard.tsx after section extraction"
```
