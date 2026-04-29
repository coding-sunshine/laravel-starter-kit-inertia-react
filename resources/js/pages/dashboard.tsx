import { AreaChart as DashboardAreaChart } from '@/components/charts/area-chart';
import { BarChart } from '@/components/charts/bar-chart';
import { ComposedChart } from '@/components/charts/composed-chart';
import { StackedBarChart } from '@/components/charts/stacked-bar-chart';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { index as rakesIndex } from '@/routes/rakes';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowDown,
    ArrowUp,
    BarChart3,
    Bell,
    Calendar,
    Check,
    CheckCircle,
    ChevronDown,
    ChevronUp,
    Factory,
    Filter,
    Flame,
    Clock,
    MapPin,
    Train,
    TriangleAlert,
    TrendingUp,
    Truck,
    FileSpreadsheet,
    X,
    Zap,
} from 'lucide-react';
import { useSidingStockBroadcast } from '@/hooks/use-siding-stock-broadcast';
import { Fragment, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
    Area,
    AreaChart as RechartsAreaChart,
    Bar,
    BarChart as RechartsBarChart,
    CartesianGrid,
    Cell,
    Legend,
    LabelList,
    Line,
    Pie,
    PieChart as RechartsPieChart,
    ReferenceLine,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { PieChart } from '@/components/charts/pie-chart';
import SpeedometerGauge from '@/Components/Charts/SpeedometerGauge';
import { SlidingNumber } from '@/components/SlidingNumber';
import type { WorkflowSteps } from '@/components/rake-workflow-progress';
import { RakeWorkflowProgressCell } from '@/components/rake-workflow-progress';
import { LoaderOverloadDashboardSection } from '@/components/dashboard/loader-overload-dashboard-section';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { laravelJsonFetch } from '@/lib/laravel-json-fetch';
import { ActiveRakePipeline } from '@/components/dashboard/active-rake-pipeline';
import { AlertFeed } from '@/components/dashboard/alert-feed';
import { DispatchSummary } from '@/components/dashboard/dispatch-summary';
import { OperatorRakeWidget } from '@/components/dashboard/operator-rake-widget';
import { PenaltyExposureStrip } from '@/components/dashboard/penalty-exposure-strip';
import { PenaltyPredictionsWidget } from '@/components/dashboard/penalty-predictions-widget';
import { SidingCoalStock } from '@/components/dashboard/siding-coal-stock';
import { SidingRiskScoreWidget } from '@/components/dashboard/siding-risk-score';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

/** COLOR DESIGN from rake management dashboards.pdf: simple professional palette for visual impact. */
const DASHBOARD_PALETTE = {
    coalBlack: '#1e293b',
    darkGrey: '#64748b',
    steelBlue: '#4682b4',
    steelBlueLight: '#6b9bc4',
    safetyYellow: '#e6b800',
    safetyYellowLight: '#f0c933',
    alertRed: '#c41e3a',
    alertRedLight: '#dc3545',
    successGreen: '#2d6a4f',
    successGreenLight: '#40916c',
} as const;

const ALL_FILTER_VALUE = '__all__';

const PERIODS = [
    { key: 'last_month', label: 'Last month' },
    { key: 'last_week', label: 'Last week' },
    { key: 'yesterday', label: 'Yesterday' },
    { key: 'today', label: 'Today' },
    { key: 'week', label: 'This week' },
    { key: 'month', label: 'This month' },
    { key: 'custom', label: 'Custom' },
] as const;

const DASHBOARD_SECTIONS = [
    { id: 'executive-overview', label: 'Executive overview' },
    { id: 'siding-overview', label: 'Siding overview' },
    { id: 'operations', label: 'Operations control' },
    { id: 'penalty-control', label: 'Penalty control' },
    { id: 'rake-performance', label: 'Rake-wise performance' },
    { id: 'loader-overload', label: 'Loader-wise overloading trends' },
    { id: 'power-plant', label: 'Power plant wise dispatch' },
] as const;

const SECTION_FILTER_KEYS = {
    'executive-overview': ['power_plant', 'rake_number', 'penalty_type'] as const,
    'siding-overview': ['power_plant', 'rake_number', 'penalty_type'] as const,
    operations: ['shift', 'daily_rake_date', 'coal_transport_date'] as const,
    'penalty-control': ['penalty_type'] as const,
    'rake-performance': ['rake_number', 'power_plant', 'rake_penalty_scope'] as const,
    /** Loader / operator live in the Loader-wise overloading card (not the sticky Filters bar). */
    'loader-overload': [] as const,
    'power-plant': ['power_plant'] as const,
} satisfies Record<
    (typeof DASHBOARD_SECTIONS)[number]['id'],
    readonly (
        | 'power_plant'
        | 'rake_number'
        | 'loader_id'
        | 'loader_operator'
        | 'shift'
        | 'penalty_type'
        | 'rake_penalty_scope'
        | 'daily_rake_date'
        | 'coal_transport_date'
    )[]
>;

const DEFAULT_DASHBOARD_SECTION = 'executive-overview';

/** Mirrors `DashboardWidgetPermissions::executiveWidgetNames()` — used for section + executive empty states. */
const EXECUTIVE_DASHBOARD_WIDGET_NAMES = [
    'dashboard.widgets.executive_tables_road_dispatch',
    'dashboard.widgets.executive_tables_rail_dispatch',
    'dashboard.widgets.executive_tables_production',
    'dashboard.widgets.executive_tables_custom',
    'dashboard.widgets.executive_tables_fy_summary',
    'dashboard.widgets.executive_chart_road_dispatch',
    'dashboard.widgets.executive_chart_rail_dispatch',
    'dashboard.widgets.executive_chart_production',
    'dashboard.widgets.executive_chart_penalty_by_siding',
    'dashboard.widgets.executive_chart_powerplant_dispatch',
    'dashboard.widgets.executive_chart_fy',
] as const;

const EXEC_TABLE_WIDGETS: readonly string[] = [
    'dashboard.widgets.executive_tables_road_dispatch',
    'dashboard.widgets.executive_tables_rail_dispatch',
    'dashboard.widgets.executive_tables_production',
    'dashboard.widgets.executive_tables_custom',
    'dashboard.widgets.executive_tables_fy_summary',
];

const EXEC_CHART_WIDGETS: readonly string[] = [
    'dashboard.widgets.executive_chart_road_dispatch',
    'dashboard.widgets.executive_chart_rail_dispatch',
    'dashboard.widgets.executive_chart_production',
    'dashboard.widgets.executive_chart_penalty_by_siding',
    'dashboard.widgets.executive_chart_powerplant_dispatch',
    'dashboard.widgets.executive_chart_fy',
];

function dashboardSectionVisible(sectionId: (typeof DASHBOARD_SECTIONS)[number]['id'], canWidget: (name: string) => boolean): boolean {
    switch (sectionId) {
        case 'executive-overview':
            return EXECUTIVE_DASHBOARD_WIDGET_NAMES.some((n) => canWidget(n));
        case 'siding-overview':
            return (
                canWidget('dashboard.widgets.siding_overview_performance') ||
                canWidget('dashboard.widgets.siding_overview_penalty_trend') ||
                canWidget('dashboard.widgets.siding_overview_power_plant_distribution')
            );
        case 'operations':
            return (
                canWidget('dashboard.widgets.operations_coal_transport') ||
                canWidget('dashboard.widgets.operations_daily_rake_details') ||
                canWidget('dashboard.widgets.operations_truck_receipt_trend') ||
                canWidget('dashboard.widgets.operations_shift_vehicle_receipt') ||
                canWidget('dashboard.widgets.operations_live_rake_status')
            );
        case 'penalty-control':
            return (
                canWidget('dashboard.widgets.penalty_control_type_distribution') ||
                canWidget('dashboard.widgets.penalty_control_yesterday_predicted') ||
                canWidget('dashboard.widgets.penalty_control_penalty_by_siding') ||
                canWidget('dashboard.widgets.penalty_control_applied_vs_rr')
            );
        case 'rake-performance':
            return canWidget('dashboard.widgets.rake_performance');
        case 'loader-overload':
            return canWidget('dashboard.widgets.loader_overload_trends');
        case 'power-plant':
            return canWidget('dashboard.widgets.power_plant_dispatch_section');
        default:
            return false;
    }
}

/** MT of coal required to load one rake; used for "rakes we can load" KPI. */
const MT_PER_RAKE_LOAD = 3500;

interface SidingOption {
    id: number;
    name: string;
    code: string;
}

interface SidingStock {
    siding_id: number;
    opening_balance_mt: number;
    closing_balance_mt: number;
    total_rakes: number;
    received_mt: number;
    dispatched_mt: number;
    last_receipt_at: string | null;
    last_dispatch_at: string | null;
}

interface SidingPerformanceItem {
    name: string;
    rakes: number;
    penalties: number;
    penalty_amount: number;
    penalty_rate: number;
}

interface SidingWiseMonthlyPoint {
    month: string;
    [sidingName: string]: string | number;
}

interface SidingComparisonItem {
    name: string;
    rakes_dispatched: number;
    on_time: number;
    vehicles: number;
    penalty_amount: number;
}

interface SidingComparisonData {
    sidings: SidingComparisonItem[];
}

interface DateWiseDispatchData {
    sidingNames: Record<number, string>;
    dates: Record<string, unknown>[];
}

interface RakePerformanceItem {
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

/** List API row: no loading_minutes, no wagon_overloads, includes siding_id. */
type RakePerformanceSummaryItem = Omit<RakePerformanceItem, 'loading_minutes' | 'wagon_overloads'> & {
    siding_id: number;
};

interface LoaderInfo {
    id: number;
    name: string;
    siding: string;
}

interface LoaderOverloadTrends {
    loaders: LoaderInfo[];
    monthly: Record<string, unknown>[];
}

interface PowerPlantSidingBreakdown {
    rakes: number;
    weight_mt: number;
}

interface PowerPlantDispatchItem {
    [key: string]: unknown;
    name: string;
    rakes: number;
    weight_mt: number;
    sidings: Record<string, PowerPlantSidingBreakdown>;
}

interface DashboardFilters {
    period: string;
    from: string;
    to: string;
    siding_ids: number[];
    power_plant: string | null;
    rake_number: string | null;
    loader_id: number | null;
    loader_operator: string | null;
    /** Minimum shortfall % of CC to count as underload on loader trends (default 1; URL `underload_threshold`). */
    underload_threshold: number;
    shift: string | null;
    penalty_type: number | null;
    rake_penalty_scope?: 'all' | 'with_penalties';
    daily_rake_date?: string;
    coal_transport_date?: string;
}

/**
 * Builds query params for `router.get` to the dashboard. Persists `loader_id` / `loader_operator`
 * when `section=loader-overload` even though those keys are not in the sticky Filters bar.
 */
function buildDashboardGetParams(args: {
    overrides: Record<string, unknown>;
    filters: DashboardFilters;
    currentSection: string | undefined;
    allSidingIds: number[];
    resolvedPeriod: string;
    resolvedFrom: string;
    resolvedTo: string;
}): Record<string, unknown> {
    const { overrides, filters, currentSection, allSidingIds, resolvedPeriod, resolvedFrom, resolvedTo } = args;
    const sectionId = (currentSection ?? DEFAULT_DASHBOARD_SECTION) as keyof typeof SECTION_FILTER_KEYS;
    const sectionFilterKeys = SECTION_FILTER_KEYS[sectionId] ?? [];
    const sectionHasFilter = (key: string): boolean => (sectionFilterKeys as readonly string[]).includes(key);
    const persistLoaderFilters = sectionId === 'loader-overload';

    const params: Record<string, unknown> = {
        period: (overrides.period as string | undefined) ?? resolvedPeriod,
        ...overrides,
    };

    if (params.period === 'custom') {
        params.from = (overrides.from as string | undefined) ?? resolvedFrom;
        params.to = (overrides.to as string | undefined) ?? resolvedTo;
    } else {
        delete params.from;
        delete params.to;
    }

    const sidingIds = (overrides.siding_ids as number[] | undefined) ?? filters.siding_ids;
    if (sidingIds.length > 0 && sidingIds.length < allSidingIds.length) {
        params.siding_ids = sidingIds.join(',');
    }

    if (sectionHasFilter('power_plant')) {
        const powerPlant = (overrides.power_plant !== undefined ? overrides.power_plant : filters.power_plant) ?? '';
        if (powerPlant !== '') params.power_plant = powerPlant;
    }

    if (sectionHasFilter('rake_number')) {
        const rakeNumber = (overrides.rake_number !== undefined ? overrides.rake_number : filters.rake_number) ?? '';
        if (rakeNumber !== '') params.rake_number = rakeNumber;
    }

    if (sectionHasFilter('loader_id') || persistLoaderFilters) {
        const loaderId = (overrides.loader_id !== undefined ? overrides.loader_id : filters.loader_id) ?? '';
        if (loaderId !== '' && loaderId !== null) params.loader_id = loaderId;
    }

    if (sectionHasFilter('loader_operator') || persistLoaderFilters) {
        const loaderOp =
            (overrides.loader_operator !== undefined ? overrides.loader_operator : filters.loader_operator) ?? null;
        if (loaderOp != null && loaderOp !== '') params.loader_operator = loaderOp;
    }

    if (persistLoaderFilters) {
        const utRaw = overrides.underload_threshold !== undefined ? overrides.underload_threshold : filters.underload_threshold;
        const ut = typeof utRaw === 'number' ? utRaw : parseFloat(String(utRaw ?? '1'));
        if (!Number.isNaN(ut) && ut !== 1) {
            params.underload_threshold = ut;
        }
    }

    if (sectionHasFilter('shift')) {
        const shift = (overrides.shift !== undefined ? overrides.shift : filters.shift) ?? '';
        if (shift !== '') params.shift = shift;
    }

    if (sectionHasFilter('penalty_type')) {
        const penaltyType = (overrides.penalty_type !== undefined ? overrides.penalty_type : filters.penalty_type) ?? null;
        if (penaltyType != null) params.penalty_type = penaltyType;
    }

    if (sectionHasFilter('rake_penalty_scope')) {
        const rakePenaltyScope =
            (overrides.rake_penalty_scope !== undefined
                ? overrides.rake_penalty_scope
                : filters.rake_penalty_scope) ?? 'all';
        if (rakePenaltyScope === 'with_penalties') {
            params.rake_penalty_scope = 'with_penalties';
        }
    }

    if (sectionHasFilter('daily_rake_date')) {
        const dailyRakeDate =
            (overrides.daily_rake_date !== undefined ? overrides.daily_rake_date : filters.daily_rake_date) ?? '';
        if (dailyRakeDate !== '') params.daily_rake_date = dailyRakeDate;
    }

    if (sectionHasFilter('coal_transport_date')) {
        const coalTransportDate =
            (overrides.coal_transport_date !== undefined ? overrides.coal_transport_date : filters.coal_transport_date) ??
            '';
        if (coalTransportDate !== '') params.coal_transport_date = coalTransportDate;
    }

    if (currentSection) params.section = currentSection;

    return params;
}

function buildRakePerformanceApiSearchParams(args: {
    filters: DashboardFilters;
    allSidingIds: number[];
    page?: number;
    perPage?: number;
    /** When set, list/detail are scoped to this siding (must be in current filter scope). */
    sidingId?: number;
}): string {
    const params = buildDashboardGetParams({
        overrides: { section: 'rake-performance' },
        filters: args.filters,
        currentSection: 'rake-performance',
        allSidingIds: args.allSidingIds,
        resolvedPeriod: args.filters.period,
        resolvedFrom: args.filters.from,
        resolvedTo: args.filters.to,
    });
    const u = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
        if (v === undefined || v === null) {
            continue;
        }
        u.set(k, String(v));
    }
    if (args.page != null) {
        u.set('page', String(args.page));
    }
    if (args.perPage != null) {
        u.set('per_page', String(args.perPage));
    }
    if (args.sidingId != null && args.sidingId > 0) {
        u.set('siding_id', String(args.sidingId));
    }

    return u.toString();
}

function buildLoaderOverloadApiSearchParams(args: {
    filters: DashboardFilters;
    allSidingIds: number[];
    page?: number;
    perPage?: number;
}): string {
    const params = buildDashboardGetParams({
        overrides: { section: 'loader-overload' },
        filters: args.filters,
        currentSection: 'loader-overload',
        allSidingIds: args.allSidingIds,
        resolvedPeriod: args.filters.period,
        resolvedFrom: args.filters.from,
        resolvedTo: args.filters.to,
    });
    const u = new URLSearchParams();
    for (const [k, v] of Object.entries(params)) {
        if (v === undefined || v === null) {
            continue;
        }
        u.set(k, String(v));
    }
    if (args.page != null) {
        u.set('page', String(args.page));
    }
    if (args.perPage != null) {
        u.set('per_page', String(args.perPage));
    }

    return u.toString();
}

interface DailyRakeDetailsRow {
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

interface DailyRakeDetailsData {
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

interface CoalTransportSidingMetric {
    siding_name: string;
    trips: number;
    qty: number;
}

interface CoalTransportReportRow {
    sl_no: number;
    shift_label: string;
    siding_metrics: CoalTransportSidingMetric[];
    total_trips: number;
    total_qty: number;
}

interface CoalTransportReportData {
    date: string;
    sidings: Array<{ id: number; name: string }>;
    rows: CoalTransportReportRow[];
    totals: { siding_metrics: CoalTransportSidingMetric[]; total_trips: number; total_qty: number };
}

interface FilterOptions {
    powerPlants: Array<{ value: string; label: string }>;
    loaders: Array<{ id: number; name: string; siding_name: string }>;
    shifts: Array<{ value: string; label: string }>;
    penaltyTypes: Array<{ value: string; label: string }>;
    /** loader id (string) -> operator names for dashboard filters */
    loaderOperatorsByLoader?: Record<string, string[]>;
}

interface DashboardKpis {
    rakesDispatchedToday: number;
    coalDispatchedToday: number;
    totalPenaltyThisMonth: number;
    predictedPenaltyRisk: number;
    avgLoadingTimeMinutes: number | null;
    trucksReceivedToday: number;
}

interface PenaltyTrendPoint {
    date: string;
    label: string;
    total: number;
}

interface PenaltyByTypePoint {
    name: string;
    value: number;
}

interface PenaltyBySidingPoint {
    name: string;
    total: number;
}

interface YesterdayPenaltyRow {
    type_code: string;
    type_name: string;
    amount: number;
}

interface YesterdayPenaltyRake {
    rake_id: number;
    rake_number: string;
    total_penalty: number;
    penalties: YesterdayPenaltyRow[];
}

interface YesterdayPredictedPenaltyItem {
    siding_id: number;
    siding_name: string;
    rakes: YesterdayPenaltyRake[];
}

interface ExecutiveTimelineValue {
    trips: number | null;
    qty: number | null;
}

interface ExecutiveTimelineSeries {
    dateWise: ExecutiveTimelineValue;
    monthWise: ExecutiveTimelineValue;
    fyWise: ExecutiveTimelineValue;
}

interface ExecutiveYesterdayData {
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
    /** Per chart-period slices (anchor-relative), same ranges as road/rail production charts. */
    penaltyBySidingByPeriod?: Record<'yesterday' | 'today' | 'month' | 'fy', PenaltyBySidingPoint[]>;
    powerPlantDispatchByPeriod?: Record<'yesterday' | 'today' | 'month' | 'fy', PowerPlantDispatchItem[]>;
}

interface DashboardAlert {
    id: number;
    type: string;
    title: string;
    severity: string;
    rake_id: number | null;
    siding_id: number | null;
    created_at: string;
}

const DEFAULT_LIVE_RAKE_WORKFLOW_STEPS: WorkflowSteps = {
    txr_done: false,
    wagon_loading_done: false,
    guard_done: false,
    weighment_done: false,
    rr_done: false,
};

interface LiveRakeStatusRow {
    rake_number: string;
    rake_serial_number?: string | null;
    siding_name: string;
    state: string;
    workflow_steps?: WorkflowSteps;
    time_elapsed: string;
    /** YYYY-MM-DD or em dash when unset */
    loading_date?: string;
    risk: string;
}

function formatRakeSequenceBySiding(rakeNumber: string, sidingName: string): string {
    const normalized = rakeNumber.trim();
    if (normalized === '') {
        return normalized;
    }

    const siding = sidingName.toLowerCase();
    let prefix = '';
    if (siding.includes('pakur')) {
        prefix = 'P';
    } else if (siding.includes('kurwa')) {
        prefix = 'K';
    } else if (siding.includes('dumka')) {
        prefix = 'D';
    }

    if (prefix === '') {
        return normalized;
    }

    return normalized.startsWith(`${prefix}-`) ? normalized : `${prefix}-${normalized}`;
}

interface TruckReceiptHour {
    hour: string;
    label: string;
    count: number;
}

/** Shift label + one key per siding name with vehicle count. */
interface ShiftWiseVehicleReceiptPoint {
    shift_label: string;
    [sidingName: string]: string | number;
}

interface StockGaugeSidingItem {
    siding_id: number;
    siding_name: string;
    stock_available_mt: number;
    rake_required_mt: number;
    status: string;
}

type StockGaugeData = StockGaugeSidingItem[];

interface PenaltySummary {
    today_rs: number;
    trend_7d: { date: string; rs: number }[];
    preventable_pct: number;
}

interface RakePipelineCard {
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

interface ActiveRakePipeline {
    loading: RakePipelineCard[];
    awaiting_clearance: RakePipelineCard[];
    dispatched: RakePipelineCard[];
}

interface SidingRiskScoreData {
    siding_id: number;
    siding_name: string;
    siding_code: string;
    score: number;
    trend: string;
    risk_factors: string[];
    calculated_at: string | null;
}

interface AlertRecord {
    id: number;
    type: string;
    title: string;
    body: string;
    severity: string;
    siding_id: number | null;
    rake_id: number | null;
    created_at: string;
}

interface OperatorRake {
    rake_id: number;
    rake_number: string;
    siding_name: string;
    state: string;
    loaded: number;
    total: number;
    status: string;
    loading_date: string | null;
}

type DashboardProps = SharedData & {
    sidings?: SidingOption[];
    section?: string;
    filters?: DashboardFilters;
    filterOptions?: FilterOptions;
    kpis?: DashboardKpis;
    penaltyTrendDaily?: PenaltyTrendPoint[];
    penaltyByType?: PenaltyByTypePoint[];
    penaltyBySiding?: PenaltyBySidingPoint[];
    notifications?: Array<{
        id: string;
        type: string;
        data: Record<string, unknown>;
        read_at: string | null;
        created_at: string;
    }>;
    notificationsUnreadCount?: number;
    liveRakeStatus?: LiveRakeStatusRow[];
    dailyRakeDetails?: DailyRakeDetailsData;
    coalTransportReport?: CoalTransportReportData;
    truckReceiptTrend?: TruckReceiptHour[];
    shiftWiseVehicleReceipt?: ShiftWiseVehicleReceiptPoint[];
    stockGauge?: StockGaugeData;
    predictedVsActualPenalty?: { predicted: number; actual: number; bySiding?: Array<{ name: string; predicted: number; actual: number }> };
    sidingStocks?: Record<number, SidingStock>;
    sidingPerformance?: SidingPerformanceItem[];
    sidingWiseMonthly?: SidingWiseMonthlyPoint[];
    sidingRadar?: SidingComparisonData;
    dateWiseDispatch?: DateWiseDispatchData;
    rakePerformance?: RakePerformanceItem[];
    loaderOverloadTrends?: LoaderOverloadTrends;
    powerPlantDispatch?: PowerPlantDispatchItem[];
    yesterdayPredictedPenalties?: YesterdayPredictedPenaltyItem[];
    executiveYesterday?: ExecutiveYesterdayData;
    /** Spatie permission names; omit or empty = no widgets (after deploy, always set for authenticated users). */
    allowedDashboardWidgets?: string[];
    penaltySummary?: PenaltySummary;
    activeRakePipeline?: ActiveRakePipeline;
    riskScores?: Record<number, SidingRiskScoreData>;
    alerts?: Record<string, AlertRecord[]>;
    operatorRake?: OperatorRake | null;
    penaltyPredictions?: Array<{
        siding_name: string;
        risk_level: 'high' | 'medium' | 'low';
        predicted_amount_min: number;
        predicted_amount_max: number;
        top_recommendation: string | null;
    }>;
};

function formatCurrency(n: number): string {
    if (n >= 100000) return `₹${(n / 100000).toFixed(1)}L`;
    if (n >= 1000) return `₹${(n / 1000).toFixed(1)}K`;
    return `₹${n.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
}

function formatWeight(n: number): string {
    if (n >= 1000) return `${(n / 1000).toFixed(1)}K MT`;
    return `${n.toLocaleString()} MT`;
}

function SectionHeader({ icon: Icon, title, subtitle, action, titleClassName }: {
    icon: React.ComponentType<{ className?: string }>;
    title: string;
    subtitle?: string;
    action?: React.ReactNode;
    titleClassName?: string;
}) {
    return (
        <div className="flex items-center justify-between gap-4">
            <div className="flex items-center gap-3">
                <div className="flex size-9 items-center justify-center rounded-xl bg-primary/10">
                    <Icon className="size-4.5 text-primary" />
                </div>
                <div>
                    <h3 className={`text-base font-semibold ${titleClassName ?? ''}`.trim()}>{title}</h3>
                    {subtitle && <p className="text-xs text-gray-600">{subtitle}</p>}
                </div>
            </div>
            {action}
        </div>
    );
}

/** Period keys aligned with executive table / backend totals (FY = year-to-date in FY). */
type ExecutiveChartPeriodKey = 'yesterday' | 'today' | 'month' | 'fy';

const EXEC_CHART_PERIOD_OPTIONS: { value: ExecutiveChartPeriodKey; label: string }[] = [
    { value: 'yesterday', label: 'Yesterday' },
    { value: 'today', label: 'Today' },
    { value: 'month', label: 'This month' },
    { value: 'fy', label: 'Year' },
];

function executiveChartFormatBarTooltipValue(n: number, unit: 'count' | 'mt'): string {
    if (unit === 'count') {
        return n.toLocaleString(undefined, { maximumFractionDigits: 0 });
    }

    return `${n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })} MT`;
}

/** One color per siding bar (cycles); matches footer legend. */
const EXECUTIVE_SIDING_BAR_CHART_COLORS = [
    DASHBOARD_PALETTE.steelBlue,
    DASHBOARD_PALETTE.successGreen,
    DASHBOARD_PALETTE.safetyYellow,
    DASHBOARD_PALETTE.steelBlueLight,
    DASHBOARD_PALETTE.successGreenLight,
    DASHBOARD_PALETTE.darkGrey,
    '#8B5CF6',
    '#F97316',
    '#EC4899',
    '#14B8A6',
    '#6366F1',
];

function ExecutiveSidingBarChartCard(props: {
    title: string;
    rows: Array<{ name: string; value: number }>;
    period: ExecutiveChartPeriodKey;
    onPeriodChange: (p: ExecutiveChartPeriodKey) => void;
    valueKind: 'count' | 'qty';
    onValueKindChange: (k: 'count' | 'qty') => void;
    countLabel: string;
}) {
    const coloredRows = useMemo(
        () =>
            props.rows.map((r, i) => ({
                ...r,
                fill: EXECUTIVE_SIDING_BAR_CHART_COLORS[i % EXECUTIVE_SIDING_BAR_CHART_COLORS.length],
            })),
        [props.rows],
    );

    const max = Math.max(...props.rows.map((r) => r.value), 1);
    const unit: 'count' | 'mt' = props.valueKind === 'qty' ? 'mt' : 'count';

    return (
        <div className="dashboard-card overflow-hidden rounded-xl border border-[#d5dbe4] bg-white p-0">
            <div className="border-b border-[#d5dbe4] bg-[#f8fafc] px-4 py-3">
                <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                    <p className="text-sm font-semibold text-gray-900">{props.title}</p>
                    <div className="flex flex-wrap items-center gap-2">
                        <Select
                            value={props.period}
                            onValueChange={(v) => props.onPeriodChange(v as ExecutiveChartPeriodKey)}
                        >
                            <SelectTrigger className="h-9 w-[160px] text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {EXEC_CHART_PERIOD_OPTIONS.map((o) => (
                                    <SelectItem key={o.value} value={o.value} className="text-xs">
                                        {o.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <div className="flex rounded-lg border border-gray-200 p-0.5">
                            <Button
                                type="button"
                                variant={props.valueKind === 'count' ? 'default' : 'ghost'}
                                size="sm"
                                className="h-8 px-3 text-xs"
                                onClick={() => props.onValueKindChange('count')}
                            >
                                {props.countLabel}
                            </Button>
                            <Button
                                type="button"
                                variant={props.valueKind === 'qty' ? 'default' : 'ghost'}
                                size="sm"
                                className="h-8 px-3 text-xs"
                                onClick={() => props.onValueKindChange('qty')}
                            >
                                Qty
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
            <div className="bg-[#fbfbfc] p-4">
                {props.rows.length === 0 ? (
                    <p className="py-12 text-center text-sm text-muted-foreground">No siding data.</p>
                ) : (
                    <>
                        <ResponsiveContainer width="100%" height={300}>
                            <RechartsBarChart
                                data={coloredRows}
                                margin={{ top: 12, right: 16, bottom: 12, left: 8 }}
                                barCategoryGap="18%"
                            >
                                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                <XAxis
                                    dataKey="name"
                                    tick={{ fontSize: 10, fill: '#374151' }}
                                    interval={0}
                                    angle={0}
                                    textAnchor="middle"
                                    height={36}
                                />
                                <YAxis tick={{ fontSize: 11 }} domain={[0, max * 1.12]} />
                                <Tooltip
                                    content={({ active, payload, label }) => {
                                        if (!active || !payload?.length) {
                                            return null;
                                        }
                                        const v = Number(payload[0]?.value ?? 0);

                                        return (
                                            <div className="rounded-lg border bg-popover px-3 py-2 text-xs shadow-md">
                                                <div className="font-semibold">{String(label ?? '')}</div>
                                                <div className="mt-1 tabular-nums font-semibold">
                                                    {executiveChartFormatBarTooltipValue(v, unit)}
                                                </div>
                                            </div>
                                        );
                                    }}
                                />
                                <Bar dataKey="value" radius={[2, 2, 0, 0]} maxBarSize={48}>
                                    {coloredRows.map((row, i) => (
                                        <Cell key={`${row.name}-${i}`} fill={row.fill} />
                                    ))}
                                </Bar>
                            </RechartsBarChart>
                        </ResponsiveContainer>
                        <div className="mt-3 flex flex-wrap justify-center gap-x-4 gap-y-2 border-t border-[#d5dbe4] pt-3">
                            {coloredRows.map((row, i) => (
                                <span
                                    key={`${row.name}-${i}`}
                                    className="inline-flex max-w-[min(100%,12rem)] items-center gap-1.5 text-[11px] text-gray-700"
                                    title={row.name}
                                >
                                    <span
                                        className="size-2.5 shrink-0 rounded-sm"
                                        style={{ backgroundColor: row.fill }}
                                        aria-hidden
                                    />
                                    <span className="truncate">{row.name}</span>
                                </span>
                            ))}
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}

function ExecutiveProductionDonutCard(props: {
    period: ExecutiveChartPeriodKey;
    onPeriodChange: (p: ExecutiveChartPeriodKey) => void;
    valueKind: 'trips' | 'qty';
    onValueKindChange: (k: 'trips' | 'qty') => void;
    obValue: number;
    coalValue: number;
}) {
    const data = [
        { name: 'OB', value: props.obValue, fill: DASHBOARD_PALETTE.successGreen },
        { name: 'Coal', value: props.coalValue, fill: DASHBOARD_PALETTE.steelBlue },
    ];
    const total = props.obValue + props.coalValue;
    const isEmpty = total <= 0;
    /** Muted 50/50 donut so the chart frame is visible when there is no production data. */
    const chartData = isEmpty
        ? [
              { name: 'OB', value: 1, fill: '#e2e8f0' },
              { name: 'Coal', value: 1, fill: '#cbd5e1' },
          ]
        : data;

    return (
        <div className="dashboard-card overflow-hidden rounded-xl border border-[#d5dbe4] bg-white p-0">
            <div className="border-b border-[#d5dbe4] bg-[#f8fafc] px-4 py-3">
                <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                    <p className="text-sm font-semibold text-gray-900">Production</p>
                    <div className="flex flex-wrap items-center gap-2">
                        <Select
                            value={props.period}
                            onValueChange={(v) => props.onPeriodChange(v as ExecutiveChartPeriodKey)}
                        >
                            <SelectTrigger className="h-9 w-[160px] text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {EXEC_CHART_PERIOD_OPTIONS.map((o) => (
                                    <SelectItem key={o.value} value={o.value} className="text-xs">
                                        {o.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <div className="flex rounded-lg border border-gray-200 p-0.5">
                            <Button
                                type="button"
                                variant={props.valueKind === 'trips' ? 'default' : 'ghost'}
                                size="sm"
                                className="h-8 px-3 text-xs"
                                onClick={() => props.onValueKindChange('trips')}
                            >
                                Trips
                            </Button>
                            <Button
                                type="button"
                                variant={props.valueKind === 'qty' ? 'default' : 'ghost'}
                                size="sm"
                                className="h-8 px-3 text-xs"
                                onClick={() => props.onValueKindChange('qty')}
                            >
                                Qty
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
            <div className="relative bg-[#fbfbfc] p-4">
                <div className="relative min-h-[280px]">
                    <ResponsiveContainer width="100%" height={280}>
                        <RechartsPieChart margin={{ top: 8, right: 8, bottom: 8, left: 8 }}>
                            <Pie
                                data={chartData}
                                dataKey="value"
                                nameKey="name"
                                cx="50%"
                                cy="50%"
                                innerRadius={72}
                                outerRadius={104}
                                paddingAngle={isEmpty ? 1 : 2}
                                isAnimationActive={!isEmpty}
                                stroke={isEmpty ? '#f8fafc' : undefined}
                                strokeWidth={isEmpty ? 1 : 0}
                            >
                                {chartData.map((entry) => (
                                    <Cell key={`cell-${entry.name}`} fill={entry.fill} />
                                ))}
                            </Pie>
                            <Tooltip
                                formatter={(value, name) => {
                                    if (isEmpty) {
                                        return ['No production data for this period', String(name)];
                                    }
                                    const v = Number(value ?? 0);

                                    return [
                                        props.valueKind === 'qty'
                                            ? `${v.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })} MT`
                                            : v.toLocaleString(undefined, { maximumFractionDigits: 0 }),
                                        String(name),
                                    ];
                                }}
                            />
                            <Legend wrapperStyle={{ fontSize: 12, opacity: isEmpty ? 0.45 : 1 }} />
                        </RechartsPieChart>
                    </ResponsiveContainer>
                    {isEmpty ? (
                        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center pb-6 text-center">
                            <p className="text-xs font-medium text-muted-foreground">No production data</p>
                            <p className="mt-0.5 text-[11px] text-muted-foreground/80">for this period</p>
                        </div>
                    ) : null}
                </div>
            </div>
        </div>
    );
}

const PENALTY_BY_SIDING_CHART_COLORS = [
    '#DC2626',
    '#EA580C',
    '#CA8A04',
    '#65A30D',
    '#059669',
    '#0D9488',
    '#2563EB',
    '#7C3AED',
    '#C026D3',
    '#DB2777',
];

function DashboardPenaltyBySidingChart({
    data,
    period,
    onPeriodChange,
}: {
    data: PenaltyBySidingPoint[];
    period?: ExecutiveChartPeriodKey;
    onPeriodChange?: (p: ExecutiveChartPeriodKey) => void;
}) {
    const sorted = useMemo(() => [...data].sort((a, b) => b.total - a.total), [data]);
    const showPeriod = onPeriodChange != null && period != null;

    return (
        <div className="dashboard-card overflow-hidden rounded-xl border border-[#d5dbe4] bg-white p-0">
            <div className="border-b border-[#d5dbe4] bg-[#f8fafc] px-4 py-3">
                {showPeriod ? (
                    <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                        <SectionHeader icon={BarChart3} title="Penalty by siding" subtitle="Which siding causes most penalties" />
                        <Select value={period} onValueChange={(v) => onPeriodChange(v as ExecutiveChartPeriodKey)}>
                            <SelectTrigger className="h-9 w-[160px] text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {EXEC_CHART_PERIOD_OPTIONS.map((o) => (
                                    <SelectItem key={o.value} value={o.value} className="text-xs">
                                        {o.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                ) : (
                    <SectionHeader icon={BarChart3} title="Penalty by siding" subtitle="Which siding causes most penalties" />
                )}
            </div>
            <div className="bg-[#fbfbfc] p-4">
                {sorted.length === 0 ? (
                    <p className="py-8 text-center text-sm text-muted-foreground">No sidings available for selected filters.</p>
                ) : (
                    <ResponsiveContainer width="100%" height={320}>
                        <RechartsBarChart data={sorted} margin={{ top: 8, right: 24, bottom: 24, left: 16 }}>
                            <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                            <XAxis dataKey="name" type="category" tick={{ fontSize: 11 }} interval={0} height={48} />
                            <YAxis type="number" tickFormatter={(v: number) => formatCurrency(v)} width={72} tick={{ fontSize: 11 }} />
                            <Tooltip formatter={(v: number | string | undefined) => formatCurrency(Number(v ?? 0))} />
                            <Bar dataKey="total" radius={[4, 4, 0, 0]} barSize={28} isAnimationActive>
                                <LabelList dataKey="total" position="top" formatter={(v: unknown) => formatCurrency(Number(v ?? 0))} />
                                {sorted.map((row, i) => (
                                    <Cell key={row.name} fill={PENALTY_BY_SIDING_CHART_COLORS[i % PENALTY_BY_SIDING_CHART_COLORS.length]} />
                                ))}
                            </Bar>
                        </RechartsBarChart>
                    </ResponsiveContainer>
                )}
            </div>
        </div>
    );
}

function ExecutiveYesterdayTable({
    title,
    values,
    dateLabel,
    monthLabel,
    fyLabel,
}: {
    title: string;
    values: ExecutiveTimelineSeries;
    dateLabel: string;
    monthLabel: string;
    fyLabel: string;
}) {
    const rows: Array<{ label: string; value: ExecutiveTimelineValue }> = [
        { label: `Date wise (${dateLabel})`, value: values.dateWise },
        { label: `Month wise (${monthLabel})`, value: values.monthWise },
        { label: `FY wise (${fyLabel})`, value: values.fyWise },
    ];

    const formatQtyOrDash = (qty: number | null): string => {
        if (qty == null) {
            return '—';
        }

        return qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const formatTripsOrDash = (trips: number | null): string => {
        if (trips == null) {
            return '—';
        }

        return trips.toLocaleString();
    };

    return (
        <div className="dashboard-card rounded-xl border-0 p-5">
            <SectionHeader icon={BarChart3} title={title} subtitle="Timeline" titleClassName="font-bold text-black" />
            <div className="dashboard-table-scroll mt-3 overflow-x-auto">
                <table className="w-full border-separate text-sm" style={{ borderSpacing: 0 }}>
                    <thead className="bg-[#eef2f7] text-black">
                        <tr>
                            <th className="border border-[#d5dbe4] px-3 py-2 text-left font-medium">Period</th>
                            <th className="border border-[#d5dbe4] px-3 py-2 text-right font-medium">Trips</th>
                            <th className="border border-[#d5dbe4] px-3 py-2 text-right font-medium">Qty (MT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.label} className="bg-white">
                                <td className="border border-[#d5dbe4] px-3 py-2">{row.label}</td>
                                <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{formatTripsOrDash(row.value.trips)}</td>
                                <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{formatQtyOrDash(row.value.qty)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function ExecutiveYesterdaySection({
    data,
    viewMode,
    penaltyBySiding = [],
    powerPlantDispatch = [],
    canWidget,
}: {
    data: ExecutiveYesterdayData;
    viewMode: 'table' | 'charts';
    penaltyBySiding?: PenaltyBySidingPoint[];
    powerPlantDispatch?: PowerPlantDispatchItem[];
    canWidget: (permissionName: string) => boolean;
}) {
    const [executiveData, setExecutiveData] = useState<ExecutiveYesterdayData>(data);

    /** Single range for the Custom (by siding) table — applied to both road and rail. */
    const [customFrom, setCustomFrom] = useState<string>(data.customRanges.roadDispatch.from);
    const [customTo, setCustomTo] = useState<string>(data.customRanges.roadDispatch.to);

    /** Custom (by siding) table Apply in flight. */
    const [customApplyLoading, setCustomApplyLoading] = useState<'customTable' | null>(null);
    const [customError, setCustomError] = useState<string | null>(null);

    const [roadChartPeriod, setRoadChartPeriod] = useState<ExecutiveChartPeriodKey>('yesterday');
    const [roadChartValueKind, setRoadChartValueKind] = useState<'count' | 'qty'>('count');
    const [railChartPeriod, setRailChartPeriod] = useState<ExecutiveChartPeriodKey>('yesterday');
    const [railChartValueKind, setRailChartValueKind] = useState<'count' | 'qty'>('count');
    const [productionChartPeriod, setProductionChartPeriod] = useState<ExecutiveChartPeriodKey>('yesterday');
    const [productionChartMetric, setProductionChartMetric] = useState<'trips' | 'qty'>('trips');
    const [penaltyChartPeriod, setPenaltyChartPeriod] = useState<ExecutiveChartPeriodKey>('yesterday');
    const [powerPlantChartPeriod, setPowerPlantChartPeriod] = useState<ExecutiveChartPeriodKey>('yesterday');
    const [powerPlantMetric, setPowerPlantMetric] = useState<'rakes' | 'qty'>('rakes');

    /** Table view: road/rail totals visible; siding rows hidden until expanded. */
    const [roadTableSidingExpanded, setRoadTableSidingExpanded] = useState(false);
    const [railTableSidingExpanded, setRailTableSidingExpanded] = useState(false);

    useEffect(() => {
        setExecutiveData(data);
        setCustomFrom(data.customRanges.roadDispatch.from);
        setCustomTo(data.customRanges.roadDispatch.to);
    }, [data]);

    const fmtNumber = (n: number, fractionDigits = 0): string =>
        n.toLocaleString(undefined, { minimumFractionDigits: fractionDigits, maximumFractionDigits: fractionDigits });

    const execPeriodOrder = ['yesterday', 'today', 'week', 'month', 'fy'] as const;
    const execPeriodColumnLabel: Record<(typeof execPeriodOrder)[number], string> = {
        yesterday: 'Yesterday',
        today: 'Today',
        week: 'Week',
        month: 'Month',
        fy: 'Year',
    };

    const dashboardPath = dashboard().url.split('?')[0] || dashboard().url;

    const isValidRange = (from: string, to: string): boolean => Boolean(from) && Boolean(to) && from <= to;

    const applyCustomTableRoadRail = useCallback(async (): Promise<void> => {
        setCustomApplyLoading('customTable');
        setCustomError(null);
        try {
            const url = new URL(`${dashboardPath.replace(/\/$/, '')}/executive-yesterday-data`, window.location.origin);
            url.searchParams.set('executive_yesterday_date', executiveData.anchorDate);
            url.searchParams.set('executive_road_from', customFrom);
            url.searchParams.set('executive_road_to', customTo);
            url.searchParams.set('executive_rail_from', customFrom);
            url.searchParams.set('executive_rail_to', customTo);
            url.searchParams.set('executive_ob_from', executiveData.customRanges.obProduction.from);
            url.searchParams.set('executive_ob_to', executiveData.customRanges.obProduction.to);
            url.searchParams.set('executive_coal_from', executiveData.customRanges.coalProduction.from);
            url.searchParams.set('executive_coal_to', executiveData.customRanges.coalProduction.to);
            url.searchParams.set('executive_apply_scope', 'road,rail');

            const res = await fetch(url.toString(), {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) {
                throw new Error(`Request failed (${res.status})`);
            }

            const body: unknown = await res.json();
            if (
                typeof body === 'object' &&
                body !== null &&
                'customRanges' in body &&
                typeof (body as { customRanges?: unknown }).customRanges === 'object' &&
                (body as { customRanges?: unknown }).customRanges !== null
            ) {
                const partial = (body as { customRanges: Partial<ExecutiveYesterdayData['customRanges']> }).customRanges;
                setExecutiveData((prev) => ({
                    ...prev,
                    customRanges: {
                        ...prev.customRanges,
                        ...partial,
                    },
                }));
                return;
            }
            throw new Error('Unexpected response shape.');
        } catch (e) {
            setCustomError(e instanceof Error ? e.message : 'Failed to load custom range data.');
        } finally {
            setCustomApplyLoading(null);
        }
    }, [dashboardPath, executiveData.anchorDate, executiveData.customRanges, customFrom, customTo]);

    const railCustomBySidingId = useMemo(
        () => new Map(executiveData.customRanges.railDispatch.bySiding.map((r) => [r.sidingId, r])),
        [executiveData.customRanges.railDispatch.bySiding],
    );

    const roadDispatchBarRows = useMemo(
        () =>
            executiveData.roadDispatch.bySiding.map((s) => ({
                name: s.sidingName,
                value:
                    roadChartValueKind === 'count'
                        ? s.totals[roadChartPeriod].trips
                        : s.totals[roadChartPeriod].qty,
            })),
        [executiveData.roadDispatch.bySiding, roadChartPeriod, roadChartValueKind],
    );

    const railDispatchBarRows = useMemo(
        () =>
            executiveData.railDispatch.bySiding.map((s) => ({
                name: s.sidingName,
                value:
                    railChartValueKind === 'count'
                        ? s.totals[railChartPeriod].rakes
                        : s.totals[railChartPeriod].qty,
            })),
        [executiveData.railDispatch.bySiding, railChartPeriod, railChartValueKind],
    );

    const productionObValue = useMemo(() => {
        const row = executiveData.obProduction[productionChartPeriod];

        return productionChartMetric === 'trips' ? row.trips : row.qty;
    }, [executiveData.obProduction, productionChartPeriod, productionChartMetric]);

    const productionCoalValue = useMemo(() => {
        const row = executiveData.coalProduction[productionChartPeriod];

        return productionChartMetric === 'trips' ? row.trips : row.qty;
    }, [executiveData.coalProduction, productionChartPeriod, productionChartMetric]);

    const hasPenaltyPeriodSlices = executiveData.penaltyBySidingByPeriod != null;
    const hasPowerPlantPeriodSlices = executiveData.powerPlantDispatchByPeriod != null;

    const penaltyChartData = useMemo(() => {
        if (hasPenaltyPeriodSlices && executiveData.penaltyBySidingByPeriod) {
            return executiveData.penaltyBySidingByPeriod[penaltyChartPeriod] ?? [];
        }

        return penaltyBySiding;
    }, [executiveData.penaltyBySidingByPeriod, hasPenaltyPeriodSlices, penaltyBySiding, penaltyChartPeriod]);

    const powerPlantChartData = useMemo(() => {
        if (hasPowerPlantPeriodSlices && executiveData.powerPlantDispatchByPeriod) {
            return executiveData.powerPlantDispatchByPeriod[powerPlantChartPeriod] ?? [];
        }

        return powerPlantDispatch;
    }, [executiveData.powerPlantDispatchByPeriod, hasPowerPlantPeriodSlices, powerPlantChartPeriod, powerPlantDispatch]);

    const customTableToolbar = (
        <>
            <label className="text-xs font-medium text-gray-600">From</label>
            <input
                type="date"
                value={customFrom}
                onChange={(e) => setCustomFrom(e.target.value)}
                className="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm"
            />
            <label className="text-xs font-medium text-gray-600">To</label>
            <input
                type="date"
                value={customTo}
                onChange={(e) => setCustomTo(e.target.value)}
                className="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm"
            />
            <Button
                type="button"
                size="sm"
                variant="outline"
                disabled={customApplyLoading === 'customTable' || !isValidRange(customFrom, customTo)}
                onClick={() => void applyCustomTableRoadRail()}
            >
                {customApplyLoading === 'customTable' ? 'Applying…' : 'Apply'}
            </Button>
        </>
    );

    const TableView = (
        <div className="space-y-6">
            {canWidget('dashboard.widgets.executive_tables_road_dispatch') ? (
            <div className="overflow-hidden rounded-xl border border-[#d5dbe4] bg-white">
                <div className="border-b border-[#d5dbe4] bg-[#f8fafc] px-4 py-3">
                    <p className="text-sm font-semibold text-gray-900">Road Dispatch</p>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full border-separate text-xs" style={{ borderSpacing: 0 }}>
                        <thead className="bg-[#eef2f7] text-black">
                            <tr>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-left font-medium" colSpan={2}>
                                    {executiveData.fyLabel}
                                </th>
                                {execPeriodOrder.map((k) => (
                                    <th key={k} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                        {execPeriodColumnLabel[k]}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            <tr className="bg-white">
                                <td className="border border-[#d5dbe4] px-3 py-2 font-semibold" rowSpan={2}>
                                    Road Dispatch
                                </td>
                                <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Trips</td>
                                {execPeriodOrder.map((k) => (
                                    <td
                                        key={k}
                                        className="border border-[#d5dbe4] px-3 py-2 text-right font-bold tabular-nums"
                                    >
                                        {fmtNumber(executiveData.roadDispatch.totals[k].trips, 0)}
                                    </td>
                                ))}
                            </tr>
                            <tr className="bg-white">
                                <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Qty</td>
                                {execPeriodOrder.map((k) => (
                                    <td
                                        key={k}
                                        className="border border-[#d5dbe4] px-3 py-2 text-right font-bold tabular-nums"
                                    >
                                        {fmtNumber(executiveData.roadDispatch.totals[k].qty, 2)}
                                    </td>
                                ))}
                            </tr>
                            {roadTableSidingExpanded
                                ? executiveData.roadDispatch.bySiding.map((s) => (
                                      <Fragment key={s.sidingId}>
                                          <tr className="bg-white">
                                              <td className="border border-[#d5dbe4] px-3 py-2 font-medium" rowSpan={2}>
                                                  {s.sidingName}
                                              </td>
                                              <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Trips</td>
                                              {execPeriodOrder.map((k) => (
                                                  <td key={k} className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                      {fmtNumber(s.totals[k].trips, 0)}
                                                  </td>
                                              ))}
                                          </tr>
                                          <tr className="bg-white">
                                              <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Qty</td>
                                              {execPeriodOrder.map((k) => (
                                                  <td key={k} className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                      {fmtNumber(s.totals[k].qty, 2)}
                                                  </td>
                                              ))}
                                          </tr>
                                      </Fragment>
                                  ))
                                : null}
                            {executiveData.roadDispatch.bySiding.length > 0 ? (
                                <tr className="bg-[#f8fafc]">
                                    <td colSpan={2 + execPeriodOrder.length} className="border border-[#d5dbe4] p-0">
                                        <button
                                            type="button"
                                            className="flex w-full items-center justify-center gap-2 py-2.5 text-xs font-medium text-gray-700 transition hover:bg-gray-100"
                                            onClick={() => setRoadTableSidingExpanded((v) => !v)}
                                            aria-expanded={roadTableSidingExpanded}
                                        >
                                            <ChevronDown
                                                className={`size-4 shrink-0 text-gray-500 transition-transform ${roadTableSidingExpanded ? 'rotate-180' : ''}`}
                                            />
                                            {roadTableSidingExpanded ? 'Hide siding breakdown' : 'Show siding breakdown'}
                                        </button>
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
            </div>
            ) : null}
            {canWidget('dashboard.widgets.executive_tables_rail_dispatch') ? (
            <div className="overflow-hidden rounded-xl border border-[#d5dbe4] bg-white">
                <div className="border-b border-[#d5dbe4] bg-[#f8fafc] px-4 py-3">
                    <p className="text-sm font-semibold text-gray-900">Rail Dispatch</p>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full border-separate text-xs" style={{ borderSpacing: 0 }}>
                        <thead className="bg-[#eef2f7] text-black">
                            <tr>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-left font-medium" colSpan={2}>
                                    {executiveData.fyLabel}
                                </th>
                                {execPeriodOrder.map((k) => (
                                    <th key={k} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                        {execPeriodColumnLabel[k]}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            <tr className="bg-white">
                                <td className="border border-[#d5dbe4] px-3 py-2 font-semibold" rowSpan={2}>
                                    Rail Dispatch
                                </td>
                                <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Rakes</td>
                                {execPeriodOrder.map((k) => (
                                    <td
                                        key={k}
                                        className="border border-[#d5dbe4] px-3 py-2 text-right font-bold tabular-nums"
                                    >
                                        {fmtNumber(executiveData.railDispatch.totals[k].rakes, 0)}
                                    </td>
                                ))}
                            </tr>
                            <tr className="bg-white">
                                <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Qty</td>
                                {execPeriodOrder.map((k) => (
                                    <td
                                        key={k}
                                        className="border border-[#d5dbe4] px-3 py-2 text-right font-bold tabular-nums"
                                    >
                                        {fmtNumber(executiveData.railDispatch.totals[k].qty, 2)}
                                    </td>
                                ))}
                            </tr>
                            {railTableSidingExpanded
                                ? executiveData.railDispatch.bySiding.map((s) => (
                                      <Fragment key={s.sidingId}>
                                          <tr className="bg-white">
                                              <td className="border border-[#d5dbe4] px-3 py-2 font-medium" rowSpan={2}>
                                                  {s.sidingName}
                                              </td>
                                              <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Rakes</td>
                                              {execPeriodOrder.map((k) => (
                                                  <td key={k} className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                      {fmtNumber(s.totals[k].rakes, 0)}
                                                  </td>
                                              ))}
                                          </tr>
                                          <tr className="bg-white">
                                              <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Qty</td>
                                              {execPeriodOrder.map((k) => (
                                                  <td key={k} className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                      {fmtNumber(s.totals[k].qty, 2)}
                                                  </td>
                                              ))}
                                          </tr>
                                      </Fragment>
                                  ))
                                : null}
                            {executiveData.railDispatch.bySiding.length > 0 ? (
                                <tr className="bg-[#f8fafc]">
                                    <td colSpan={2 + execPeriodOrder.length} className="border border-[#d5dbe4] p-0">
                                        <button
                                            type="button"
                                            className="flex w-full items-center justify-center gap-2 py-2.5 text-xs font-medium text-gray-700 transition hover:bg-gray-100"
                                            onClick={() => setRailTableSidingExpanded((v) => !v)}
                                            aria-expanded={railTableSidingExpanded}
                                        >
                                            <ChevronDown
                                                className={`size-4 shrink-0 text-gray-500 transition-transform ${railTableSidingExpanded ? 'rotate-180' : ''}`}
                                            />
                                            {railTableSidingExpanded ? 'Hide siding breakdown' : 'Show siding breakdown'}
                                        </button>
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
            </div>
            ) : null}

            {canWidget('dashboard.widgets.executive_tables_custom') && customError ? (
                <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {customError}
                </div>
            ) : null}

            {canWidget('dashboard.widgets.executive_tables_production') ? (
            <div className="overflow-hidden rounded-xl border border-[#d5dbe4] bg-white">
                <div className="border-b border-[#d5dbe4] bg-[#f8fafc] px-4 py-3">
                    <p className="text-sm font-semibold text-gray-900">Production</p>
                </div>
                <div className="space-y-0">
                    <p className="border-b border-[#d5dbe4] bg-[#f8fafc] px-4 py-2 text-xs font-semibold text-gray-800">
                        OB Production
                    </p>
                    <div className="overflow-x-auto">
                        <table className="w-full border-separate text-xs" style={{ borderSpacing: 0 }}>
                            <thead className="bg-[#eef2f7] text-black">
                                <tr>
                                    <th className="border border-[#d5dbe4] px-3 py-2 text-left font-medium" colSpan={2}>
                                        {executiveData.fyLabel}
                                    </th>
                                    {execPeriodOrder.map((k) => (
                                        <th key={k} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                            {execPeriodColumnLabel[k]}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="bg-white">
                                    <td className="border border-[#d5dbe4] px-3 py-2 font-semibold" rowSpan={2}>
                                        OB Production
                                    </td>
                                    <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Trips</td>
                                    {execPeriodOrder.map((k) => (
                                        <td
                                            key={k}
                                            className="border border-[#d5dbe4] px-3 py-2 text-right font-bold tabular-nums"
                                        >
                                            {fmtNumber(executiveData.obProduction[k].trips, 0)}
                                        </td>
                                    ))}
                                </tr>
                                <tr className="bg-white">
                                    <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Qty</td>
                                    {execPeriodOrder.map((k) => (
                                        <td
                                            key={k}
                                            className="border border-[#d5dbe4] px-3 py-2 text-right font-bold tabular-nums"
                                        >
                                            {fmtNumber(executiveData.obProduction[k].qty, 2)}
                                        </td>
                                    ))}
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p className="border-b border-t border-[#d5dbe4] bg-[#f8fafc] px-4 py-2 text-xs font-semibold text-gray-800">
                        Coal Production
                    </p>
                    <div className="overflow-x-auto">
                        <table className="w-full border-separate text-xs" style={{ borderSpacing: 0 }}>
                            <thead className="bg-[#eef2f7] text-black">
                                <tr>
                                    <th className="border border-[#d5dbe4] px-3 py-2 text-left font-medium" colSpan={2}>
                                        {executiveData.fyLabel}
                                    </th>
                                    {execPeriodOrder.map((k) => (
                                        <th key={k} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                            {execPeriodColumnLabel[k]}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="bg-white">
                                    <td className="border border-[#d5dbe4] px-3 py-2 font-semibold" rowSpan={2}>
                                        Coal Production
                                    </td>
                                    <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Trips</td>
                                    {execPeriodOrder.map((k) => (
                                        <td
                                            key={k}
                                            className="border border-[#d5dbe4] px-3 py-2 text-right font-bold tabular-nums"
                                        >
                                            {fmtNumber(executiveData.coalProduction[k].trips, 0)}
                                        </td>
                                    ))}
                                </tr>
                                <tr className="bg-white">
                                    <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Qty</td>
                                    {execPeriodOrder.map((k) => (
                                        <td
                                            key={k}
                                            className="border border-[#d5dbe4] px-3 py-2 text-right font-bold tabular-nums"
                                        >
                                            {fmtNumber(executiveData.coalProduction[k].qty, 2)}
                                        </td>
                                    ))}
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            ) : null}

            {canWidget('dashboard.widgets.executive_tables_custom') ? (
            <div className="overflow-hidden rounded-xl border border-[#d5dbe4] bg-white">
                <div className="border-b border-[#d5dbe4] bg-[#f8fafc] px-4 py-3">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="text-sm font-semibold text-gray-900">Custom</p>
                        <div className="flex flex-wrap items-center gap-2">{customTableToolbar}</div>
                    </div>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full border-separate text-xs" style={{ borderSpacing: 0 }}>
                        <thead className="bg-[#eef2f7] text-black">
                            <tr>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-left font-medium" rowSpan={2}>
                                    Siding
                                </th>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium" colSpan={2}>
                                    Road dispatch
                                </th>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium" colSpan={2}>
                                    Rail dispatch
                                </th>
                            </tr>
                            <tr>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-right font-medium">Trips</th>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-right font-medium">Qty (MT)</th>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-right font-medium">Rakes</th>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-right font-medium">Qty (MT)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr className="bg-[#f1f5f9]">
                                <td className="border border-[#d5dbe4] px-3 py-2 font-semibold">Total</td>
                                <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums font-semibold">
                                    {fmtNumber(executiveData.customRanges.roadDispatch.totals.trips, 0)}
                                </td>
                                <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums font-semibold">
                                    {fmtNumber(executiveData.customRanges.roadDispatch.totals.qty, 2)}
                                </td>
                                <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums font-semibold">
                                    {fmtNumber(executiveData.customRanges.railDispatch.totals.rakes, 0)}
                                </td>
                                <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums font-semibold">
                                    {fmtNumber(executiveData.customRanges.railDispatch.totals.qty, 2)}
                                </td>
                            </tr>
                            {executiveData.customRanges.roadDispatch.bySiding.map((row) => {
                                const rail = railCustomBySidingId.get(row.sidingId);

                                return (
                                    <tr key={row.sidingId} className="bg-white">
                                        <td className="border border-[#d5dbe4] px-3 py-2 font-medium">{row.sidingName}</td>
                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{fmtNumber(row.trips, 0)}</td>
                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{fmtNumber(row.qty, 2)}</td>
                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{fmtNumber(rail?.rakes ?? 0, 0)}</td>
                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{fmtNumber(rail?.qty ?? 0, 2)}</td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </div>
            ) : null}

            {canWidget('dashboard.widgets.executive_tables_fy_summary') ? (
            <div className="overflow-hidden rounded-xl border border-[#d5dbe4] bg-white">
                <div className="border-b border-[#d5dbe4] bg-[#f8fafc] px-4 py-3">
                    <p className="text-sm font-semibold text-gray-900">FY Summary</p>
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full border-separate text-xs" style={{ borderSpacing: 0 }}>
                        <thead className="bg-[#eef2f7] text-black">
                            <tr>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-left font-medium">FY</th>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium" colSpan={2}>Production</th>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium" colSpan={2}>Road Dispatch</th>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium" colSpan={2}>Rail Dispatch</th>
                            </tr>
                            <tr>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-left font-medium" />
                                <th className="border border-[#d5dbe4] px-3 py-2 text-right font-medium">OB</th>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-right font-medium">Coal</th>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-right font-medium">Trips</th>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-right font-medium">Qty</th>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-right font-medium">Rakes</th>
                                <th className="border border-[#d5dbe4] px-3 py-2 text-right font-medium">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            {executiveData.fySummary.rows.map((r) => {
                                const isTillDate = r.fy === 'Till Date';
                                const strong = isTillDate ? 'font-bold' : '';

                                return (
                                    <tr key={r.fy} className="bg-white">
                                        <td
                                            className={`border border-[#d5dbe4] px-3 py-2 ${isTillDate ? 'font-bold' : 'font-medium'}`}
                                        >
                                            {r.fy}
                                        </td>
                                        <td
                                            className={`border border-[#d5dbe4] px-3 py-2 text-right tabular-nums ${strong}`}
                                        >
                                            {fmtNumber(r.production.obQty, 2)}
                                        </td>
                                        <td
                                            className={`border border-[#d5dbe4] px-3 py-2 text-right tabular-nums ${strong}`}
                                        >
                                            {fmtNumber(r.production.coalQty, 2)}
                                        </td>
                                        <td
                                            className={`border border-[#d5dbe4] px-3 py-2 text-right tabular-nums ${strong}`}
                                        >
                                            {fmtNumber(r.roadDispatch.trips, 0)}
                                        </td>
                                        <td
                                            className={`border border-[#d5dbe4] px-3 py-2 text-right tabular-nums ${strong}`}
                                        >
                                            {fmtNumber(r.roadDispatch.qty, 2)}
                                        </td>
                                        <td
                                            className={`border border-[#d5dbe4] px-3 py-2 text-right tabular-nums ${strong}`}
                                        >
                                            {fmtNumber(r.railDispatch.rakes, 0)}
                                        </td>
                                        <td
                                            className={`border border-[#d5dbe4] px-3 py-2 text-right tabular-nums ${strong}`}
                                        >
                                            {fmtNumber(r.railDispatch.qty, 2)}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </div>
            ) : null}
        </div>
    );

    const compactQty = (n: number): string => fmtNumber(n, n % 1 === 0 ? 0 : 2);

    const fyProductionRows = executiveData.fyCharts.rows.map((r) => ({
        fy: r.fy,
        OB: r.production.obQty,
        COAL: r.production.coalQty,
        unit: 'MT',
    }));

    const fyDispatchRows = executiveData.fyCharts.rows.map((r) => ({
        fy: r.fy,
        ROAD: r.dispatch.roadQty,
        RAIL: r.dispatch.railQty,
        unit: 'MT',
    }));

    const maxOf = (rows: Array<Record<string, number | string>>) => Math.max(
        ...rows.flatMap((row) => Object.values(row)).filter((v) => typeof v === 'number') as number[],
        1,
    );

    const FyChartCard = (props: {
        title: string;
        subtitle?: string;
        rows: Array<Record<string, number | string>>;
        series: Array<{ key: string; label: string; color: string }>;
        height?: number;
    }) => {
        const max = maxOf(props.rows);

        return (
            <div className="dashboard-card overflow-hidden rounded-xl border-0 p-0">
                <div className="border-b border-[#d5dbe4] bg-[#f8fafc] px-4 py-3">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p className="text-sm font-semibold text-gray-900">{props.title}</p>
                            {props.subtitle ? <p className="text-[11px] text-muted-foreground">{props.subtitle}</p> : null}
                        </div>
                        <div className="flex flex-wrap items-center gap-3 text-[11px] font-medium text-muted-foreground">
                            {props.series.map((s) => (
                                <span key={s.key} className="inline-flex items-center gap-1.5">
                                    <span className="inline-block size-2.5 rounded-sm" style={{ backgroundColor: s.color }} />
                                    {s.label}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="bg-[#fbfbfc] p-4">
                    <ResponsiveContainer width="100%" height={props.height ?? 300}>
                        <RechartsBarChart data={props.rows} margin={{ top: 12, right: 16, bottom: 8, left: 8 }} barCategoryGap="28%">
                            <CartesianGrid strokeDasharray="3 3" vertical={false} />
                            <XAxis dataKey="fy" tick={{ fontSize: 11 }} interval={0} height={36} />
                            <YAxis tick={{ fontSize: 11 }} domain={[0, max * 1.12]} />
                            <Tooltip
                                content={(tooltipProps: unknown) => {
                                    const { active, payload, label } = (tooltipProps ?? {}) as {
                                        active?: boolean;
                                        payload?: ReadonlyArray<{ name?: string; value?: number | string; payload?: { unit?: string } }>;
                                        label?: string;
                                    };
                                    if (!active || !payload?.length) {
                                        return null;
                                    }

                                    const unit = String(payload[0]?.payload?.unit ?? 'MT');

                                    return (
                                        <div className="rounded-lg border bg-popover px-3 py-2 text-xs shadow-md">
                                            <div className="font-semibold">{String(label ?? '')}</div>
                                            <div className="mt-1 space-y-0.5">
                                                {payload.map((p) => (
                                                    <div key={String(p.name)} className="flex items-center justify-between gap-4">
                                                        <span className="text-muted-foreground">{String(p.name)}</span>
                                                        <span className="tabular-nums font-semibold">
                                                            {compactQty(Number(p.value ?? 0))}{' '}
                                                            <span className="text-muted-foreground font-normal">{unit}</span>
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    );
                                }}
                            />
                            {props.series.map((s) => (
                                <Bar key={s.key} name={s.label} dataKey={s.key} fill={s.color} radius={0} barSize={16} />
                            ))}
                        </RechartsBarChart>
                    </ResponsiveContainer>
                </div>
            </div>
        );
    };

    const ChartsView = (
        <div className="space-y-6">
            {canWidget('dashboard.widgets.executive_chart_road_dispatch') || canWidget('dashboard.widgets.executive_chart_rail_dispatch') ? (
            <div className="grid gap-4 lg:grid-cols-2 lg:items-start">
                {canWidget('dashboard.widgets.executive_chart_road_dispatch') ? (
                <ExecutiveSidingBarChartCard
                    title="Road Dispatch"
                    rows={roadDispatchBarRows}
                    period={roadChartPeriod}
                    onPeriodChange={setRoadChartPeriod}
                    valueKind={roadChartValueKind}
                    onValueKindChange={setRoadChartValueKind}
                    countLabel="Trips"
                />
                ) : null}
                {canWidget('dashboard.widgets.executive_chart_rail_dispatch') ? (
                <ExecutiveSidingBarChartCard
                    title="Rail Dispatch"
                    rows={railDispatchBarRows}
                    period={railChartPeriod}
                    onPeriodChange={setRailChartPeriod}
                    valueKind={railChartValueKind}
                    onValueKindChange={setRailChartValueKind}
                    countLabel="Rakes"
                />
                ) : null}
            </div>
            ) : null}

            {canWidget('dashboard.widgets.executive_chart_production') || canWidget('dashboard.widgets.executive_chart_penalty_by_siding') ? (
            <div className="grid gap-4 lg:grid-cols-2 lg:items-start">
                {canWidget('dashboard.widgets.executive_chart_production') ? (
                <ExecutiveProductionDonutCard
                    period={productionChartPeriod}
                    onPeriodChange={setProductionChartPeriod}
                    valueKind={productionChartMetric}
                    onValueKindChange={setProductionChartMetric}
                    obValue={productionObValue}
                    coalValue={productionCoalValue}
                />
                ) : null}
                {canWidget('dashboard.widgets.executive_chart_penalty_by_siding') ? (
                <DashboardPenaltyBySidingChart
                    data={penaltyChartData}
                    {...(hasPenaltyPeriodSlices
                        ? { period: penaltyChartPeriod, onPeriodChange: setPenaltyChartPeriod }
                        : {})}
                />
                ) : null}
            </div>
            ) : null}

            {canWidget('dashboard.widgets.executive_chart_powerplant_dispatch') ? (
            <RakesPerPowerPlantExecutiveChart
                data={powerPlantChartData}
                {...(hasPowerPlantPeriodSlices
                    ? {
                          period: powerPlantChartPeriod,
                          onPeriodChange: setPowerPlantChartPeriod,
                          metric: powerPlantMetric,
                          onMetricChange: setPowerPlantMetric,
                      }
                    : {})}
            />
            ) : null}

            {canWidget('dashboard.widgets.executive_chart_fy') ? (
            <div className="grid gap-4 lg:grid-cols-2 lg:items-start">
                <FyChartCard
                    title="Production"
                    rows={fyProductionRows}
                    series={[
                        { key: 'OB', label: 'OB', color: DASHBOARD_PALETTE.successGreen },
                        { key: 'COAL', label: 'COAL', color: DASHBOARD_PALETTE.steelBlue },
                    ]}
                />
                <FyChartCard
                    title="Dispatch"
                    rows={fyDispatchRows}
                    series={[
                        { key: 'ROAD', label: 'ROAD', color: DASHBOARD_PALETTE.steelBlue },
                        { key: 'RAIL', label: 'RAIL', color: DASHBOARD_PALETTE.darkGrey },
                    ]}
                />
            </div>
            ) : null}
        </div>
    );

    const tableAllowed = EXEC_TABLE_WIDGETS.some((n) => canWidget(n));
    const chartsAllowed = EXEC_CHART_WIDGETS.some((n) => canWidget(n));
    if (viewMode === 'table' && !tableAllowed) {
        return (
            <div className="dashboard-card rounded-xl border-0 p-6 text-sm text-gray-600">
                No executive table widgets are enabled for your account.
            </div>
        );
    }
    if (viewMode === 'charts' && !chartsAllowed) {
        return (
            <div className="dashboard-card rounded-xl border-0 p-6 text-sm text-gray-600">
                No executive chart widgets are enabled for your account.
            </div>
        );
    }

    return viewMode === 'table' ? TableView : ChartsView;
}

const SIDING_BAR_COLORS = [DASHBOARD_PALETTE.steelBlue, DASHBOARD_PALETTE.successGreen, DASHBOARD_PALETTE.safetyYellow];
const SIDING_DOT_COLORS = [DASHBOARD_PALETTE.steelBlue, DASHBOARD_PALETTE.successGreen, DASHBOARD_PALETTE.safetyYellow];

interface MetricDef {
    key: keyof SidingComparisonItem;
    label: string;
    format: (v: number) => string;
}

const COMPARISON_METRICS: MetricDef[] = [
    { key: 'rakes_dispatched', label: 'Rakes dispatched', format: (v) => `${v}` },
    { key: 'on_time', label: 'On-time rakes', format: (v) => `${v}` },
    { key: 'vehicles', label: 'Vehicles', format: (v) => `${v}` },
    { key: 'penalty_amount', label: 'Penalty', format: (v) => formatCurrency(v) },
];

function SidingComparisonVertical({ data }: { data: SidingComparisonItem[] }) {
    const [hoveredBar, setHoveredBar] = useState<{ siding: string; metric: string } | null>(null);

    return (
        <div className="rounded-xl border bg-card p-5">
            <SectionHeader icon={Zap} title="Siding comparison" subtitle="Key metrics across sidings" />
            <div className="mt-3 flex flex-wrap items-center gap-4">
                {data.map((s, i) => (
                    <div key={s.name} className="flex items-center gap-1.5 text-xs">
                        <span className="inline-block size-2.5 rounded-full" style={{ backgroundColor: SIDING_DOT_COLORS[i % SIDING_DOT_COLORS.length] }} />
                        <span className="font-medium">{s.name}</span>
                    </div>
                ))}
            </div>
            <div className="mt-5 grid grid-cols-4 gap-4">
                {COMPARISON_METRICS.map((metric) => {
                    const values = data.map((s) => s[metric.key] as number);
                    const max = Math.max(...values, 1);

                    return (
                        <div key={metric.key}>
                            <p className="mb-3 text-center text-xs font-medium text-muted-foreground">{metric.label}</p>
                            <div className="flex items-end justify-center gap-2" style={{ height: 160 }}>
                                {data.map((s, i) => {
                                    const value = s[metric.key] as number;
                                    const pct = Math.max(5, (value / max) * 100);
                                    const isHovered = hoveredBar?.siding === s.name && hoveredBar?.metric === metric.key;

                                    return (
                                        <div
                                            key={s.name}
                                            className="group relative flex flex-1 flex-col items-center"
                                            onMouseEnter={() => setHoveredBar({ siding: s.name, metric: metric.key })}
                                            onMouseLeave={() => setHoveredBar(null)}
                                        >
                                            {isHovered && (
                                                <div className="absolute -top-12 z-10 whitespace-nowrap rounded-lg border bg-popover px-3 py-1.5 text-xs shadow-md">
                                                    <span className="font-semibold">{s.name}</span>
                                                    <span className="ml-1.5">{metric.format(value)}</span>
                                                    <div className="absolute -bottom-1 left-1/2 size-2 -translate-x-1/2 rotate-45 border-b border-r bg-popover" />
                                                </div>
                                            )}
                                            <div className="flex w-full flex-col items-center" style={{ height: 140 }}>
                                                <div className="mt-auto w-full max-w-10">
                                                    <div
                                                        className={`w-full rounded-t-md transition-all duration-500 ${isHovered ? 'opacity-100' : 'opacity-80'}`}
                                                        style={{ backgroundColor: SIDING_BAR_COLORS[i % SIDING_BAR_COLORS.length], height: `${pct}%`, minHeight: 8 }}
                                                    />
                                                </div>
                                            </div>
                                            <span className="mt-1.5 text-[10px] font-bold tabular-nums">{metric.format(value)}</span>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

const SIDING_ACCENT: Record<string, string> = { Dumka: '#3B82F6', Kurwa: '#10B981', Pakur: '#F59E0B' };

const SIDING_PERF_COLORS = [
    // Vibrant palette inspired by rd2.jpeg for siding rows
    '#3b82f6', // blue
    '#f97316', // orange
    '#22c55e', // green
    '#eab308', // amber
    '#a855f7', // purple
];

function SidingPerformanceSection({ data }: { data: SidingPerformanceItem[] }) {
    const chartData = useMemo(
        () => data.map((s) => ({ ...s, name: s.name, rakes: s.rakes, penalty_amount: s.penalty_amount, penalty_rate: s.penalty_rate })),
        [data],
    );

    return (
        <div className="dashboard-card rounded-xl border-0 p-6">
            <SectionHeader icon={BarChart3} title="Siding performance" subtitle="Rakes dispatched & penalty amount by siding" />

            <div className="mt-5 grid gap-6 lg:grid-cols-2">
                <div>
                    <p className="mb-2 text-xs font-medium text-gray-600">Rakes dispatched</p>
                    <ResponsiveContainer width="100%" height={260}>
                        <RechartsBarChart data={chartData} layout="horizontal" margin={{ top: 8, right: 16, bottom: 0, left: 16 }}>
                            <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                            <XAxis dataKey="name" tick={{ fontSize: 12 }} />
                            <YAxis allowDecimals={false} tick={{ fontSize: 12 }} />
                            <Tooltip formatter={(value: number | string | undefined) => Number(value ?? 0).toLocaleString()} />
                            <Bar dataKey="rakes" name="Rakes dispatched" fill="#3B82F6" barSize={14} radius={[4, 4, 0, 0]} isAnimationActive>
                                <LabelList dataKey="rakes" position="right" />
                            </Bar>
                        </RechartsBarChart>
                    </ResponsiveContainer>
                </div>
                <div>
                    <p className="mb-2 text-xs font-medium text-gray-600">Penalty amount by siding</p>
                    <ResponsiveContainer width="100%" height={260}>
                        <RechartsBarChart data={chartData} margin={{ top: 8, right: 16, bottom: 0, left: 8 }}>
                            <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                            <XAxis dataKey="name" tick={{ fontSize: 12 }} />
                            <YAxis tick={{ fontSize: 12 }} tickFormatter={(v) => formatCurrency(v)} />
                            <Tooltip formatter={(value: number | string | undefined) => formatCurrency(Number(value ?? 0))} />
                            <Bar dataKey="penalty_amount" fill="#DC2626" radius={[4, 4, 0, 0]} barSize={14} isAnimationActive>
                                <LabelList dataKey="penalty_amount" position="top" formatter={(v: unknown) => formatCurrency(Number(v ?? 0))} />
                            </Bar>
                        </RechartsBarChart>
                    </ResponsiveContainer>
                    <div className="mt-3 flex flex-wrap gap-4 text-sm text-gray-600">
                        {data.slice().sort((a, b) => b.penalty_amount - a.penalty_amount).map((s) => (
                            <div key={s.name} className="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-800">
                                <span>{s.name}:</span>
                                <span className="font-semibold tabular-nums">{formatCurrency(s.penalty_amount)}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}

const DISPATCH_COLORS = [DASHBOARD_PALETTE.steelBlue, DASHBOARD_PALETTE.successGreen, DASHBOARD_PALETTE.safetyYellow, DASHBOARD_PALETTE.steelBlueLight, DASHBOARD_PALETTE.successGreenLight];
const PENALTY_COLORS = [DASHBOARD_PALETTE.alertRed, DASHBOARD_PALETTE.safetyYellow, DASHBOARD_PALETTE.alertRedLight, DASHBOARD_PALETTE.darkGrey];

function DateWiseDispatchSection({ data }: { data: DateWiseDispatchData }) {
    const { sidingNames, dates } = data;
    const sidingIds = useMemo(() => Object.keys(sidingNames).map(Number), [sidingNames]);

    const dispatchKeys = useMemo(() => sidingIds.map((id) => `dispatched_${id}`), [sidingIds]);
    const penaltyKeys = useMemo(() => sidingIds.map((id) => `penalty_${id}`), [sidingIds]);

    const dispatchLabels = useMemo(() => {
        const labels: Record<string, string> = {};
        for (const id of sidingIds) {
            labels[`dispatched_${id}`] = sidingNames[id];
        }
        return labels;
    }, [sidingIds, sidingNames]);

    const penaltyLabels = useMemo(() => {
        const labels: Record<string, string> = {};
        for (const id of sidingIds) {
            labels[`penalty_${id}`] = sidingNames[id];
        }
        return labels;
    }, [sidingIds, sidingNames]);

    const dispatchColors = useMemo(() => {
        const c: Record<string, string> = {};
        sidingIds.forEach((id, i) => {
            c[`dispatched_${id}`] = DISPATCH_COLORS[i % DISPATCH_COLORS.length];
        });
        return c;
    }, [sidingIds]);

    const penaltyColors = useMemo(() => {
        const c: Record<string, string> = {};
        sidingIds.forEach((id, i) => {
            c[`penalty_${id}`] = PENALTY_COLORS[i % PENALTY_COLORS.length];
        });
        return c;
    }, [sidingIds]);

    const totals = useMemo(() => {
        let dispatched = 0;
        let penalty = 0;
        for (const row of dates) {
            dispatched += (row.total_dispatched as number) ?? 0;
            penalty += (row.total_penalty as number) ?? 0;
        }
        return { dispatched, penalty: Math.round(penalty) };
    }, [dates]);

    return (
        <div className="rounded-xl border bg-card p-5">
            <SectionHeader
                icon={Train}
                title="Date-wise rail dispatch & penalties"
                subtitle="Siding-wise breakdown by date"
            />

            {/* Summary cards */}
            <div className="mt-4 grid gap-3 sm:grid-cols-3">
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">Total rakes dispatched</p>
                    <p className="mt-1 text-2xl font-bold tabular-nums">{totals.dispatched}</p>
                </div>
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">Total penalty amount</p>
                    <p className="mt-1 text-2xl font-bold tabular-nums text-red-600 dark:text-red-400">
                        {formatCurrency(totals.penalty)}
                    </p>
                </div>
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">Sidings</p>
                    <p className="mt-1 text-2xl font-bold tabular-nums">{sidingIds.length}</p>
                    <p className="mt-0.5 text-xs text-muted-foreground">{Object.values(sidingNames).join(', ')}</p>
                </div>
            </div>

            {/* Stacked bar: rakes dispatched per siding */}
            <div className="mt-5">
                <p className="mb-2 text-sm font-semibold">Rakes dispatched (siding-wise)</p>
                <StackedBarChart
                    data={dates}
                    xKey="date"
                    stackKeys={dispatchKeys}
                    stackLabels={dispatchLabels}
                    stackColors={dispatchColors}
                    yLabel="Rakes"
                    height={300}
                    allowDecimals={false}
                    formatTooltip={(v) => `${v} rakes`}
                />
            </div>

            {/* Stacked bar: penalty amounts per siding */}
            <div className="mt-5">
                <p className="mb-2 text-sm font-semibold">Penalty amount (siding-wise)</p>
                <StackedBarChart
                    data={dates}
                    xKey="date"
                    stackKeys={penaltyKeys}
                    stackLabels={penaltyLabels}
                    stackColors={penaltyColors}
                    yLabel="₹"
                    height={300}
                    formatTooltip={(v) => `₹${v.toLocaleString()}`}
                />
            </div>
        </div>
    );
}

function RakePerformanceDetailCharts({
    r,
    underloadThresholdPercent,
    onUnderloadThresholdChange,
    onNavigateToLoader,
}: {
    r: RakePerformanceItem;
    underloadThresholdPercent: number;
    onUnderloadThresholdChange: (v: number) => void;
    onNavigateToLoader: (loaderId: number, rakeUnderloadThresholdPercent: number) => void;
}) {
    const [noLoaderDialogOpen, setNoLoaderDialogOpen] = useState(false);
    const [noLoaderWagonNumber, setNoLoaderWagonNumber] = useState<string | null>(null);

    const loadingHours = r.loading_minutes != null ? Math.floor(r.loading_minutes / 60) : null;
    const loadingMins = r.loading_minutes != null ? r.loading_minutes % 60 : null;

    const weightChartData = useMemo(() => {
        const items: { name: string; value: number; fill?: string }[] = [];
        if (r.net_weight != null) items.push({ name: 'Net weight', value: r.net_weight, fill: '#4B72BE' });
        if (r.over_load != null && r.over_load > 0) items.push({ name: 'Overload', value: r.over_load, fill: '#DC2626' });
        if (r.under_load != null && r.under_load > 0) items.push({ name: 'Underload', value: r.under_load, fill: '#F59E0B' });
        return items;
    }, [r]);

    const wagonBarChartData = useMemo(() => {
        const list = r.wagon_overloads ?? [];
        const thr = underloadThresholdPercent;
        return list.map((w, i) => {
            const over = w.over_load_mt > 0 ? w.over_load_mt : 0;
            const underLoadMt = w.under_load_mt != null && w.under_load_mt > 0 ? w.under_load_mt : 0;
            const cc = w.cc_capacity_mt ?? null;
            let shortfallPct: number | null = null;
            if (underLoadMt > 0 && cc != null && cc > 0) {
                shortfallPct = (underLoadMt / cc) * 100;
            }
            let bar_mt = 0;
            if (over > 0) {
                bar_mt = over;
            } else if (underLoadMt > 0 && shortfallPct != null && shortfallPct >= thr) {
                bar_mt = -underLoadMt;
            }
            return {
                position: i + 1,
                wagon_number: w.wagon_number,
                over_load_mt: w.over_load_mt,
                under_load_mt: w.under_load_mt ?? null,
                cc_capacity_mt: cc,
                net_weight_mt: w.net_weight_mt ?? null,
                loader_id: w.loader_id ?? null,
                loader_name: w.loader_name ?? null,
                loader_operator_name: w.loader_operator_name ?? null,
                shortfall_pct: shortfallPct,
                bar_mt,
            };
        });
    }, [r.wagon_overloads, underloadThresholdPercent]);

    /**
     * Stock (MT) and wagon counts — same rules as bars: overload takes precedence; underload uses threshold % of CC.
     */
    const wagonWeighmentSummary = useMemo(() => {
        const list = r.wagon_overloads ?? [];
        const thr = underloadThresholdPercent;
        let underloadStockMt = 0;
        let overloadStockMt = 0;
        let underloadWagons = 0;
        let overloadWagons = 0;
        for (const w of list) {
            const over = w.over_load_mt != null && w.over_load_mt > 0 ? w.over_load_mt : 0;
            if (over > 0) {
                overloadStockMt += over;
                overloadWagons++;
                continue;
            }
            const underLoadMt = w.under_load_mt != null && w.under_load_mt > 0 ? w.under_load_mt : 0;
            if (underLoadMt <= 0) {
                continue;
            }
            const cc = w.cc_capacity_mt ?? null;
            if (cc == null || cc <= 0) {
                continue;
            }
            const shortfallPct = (underLoadMt / cc) * 100;
            if (shortfallPct >= thr) {
                underloadStockMt += underLoadMt;
                underloadWagons++;
            }
        }
        return { underloadStockMt, overloadStockMt, underloadWagons, overloadWagons };
    }, [r.wagon_overloads, underloadThresholdPercent]);

    const fmtMt = (n: number): string =>
        n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });

    return (
        <div className="space-y-5">
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
                <div className="rounded-lg border border-gray-100 bg-gray-50/50 p-3">
                    <p className="text-xs font-medium text-gray-600">Siding</p>
                    <p className="mt-1 font-bold text-gray-900">{r.siding}</p>
                </div>
                <div className="rounded-lg border border-gray-100 bg-gray-50/50 p-3">
                    <p className="text-xs font-medium text-gray-600">Dispatch date</p>
                    <p className="mt-1 font-bold tabular-nums text-gray-900">{r.dispatch_date}</p>
                </div>
                <div className="rounded-lg border border-gray-100 bg-gray-50/50 p-3">
                    <p className="text-xs font-medium text-gray-600">Wagons</p>
                    <p className="mt-1 font-bold tabular-nums text-gray-900">{r.wagon_count ?? '—'}</p>
                </div>
                <div className="rounded-lg border border-gray-100 bg-gray-50/50 p-3">
                    <p className="text-xs font-medium text-gray-600">Net weight</p>
                    <p className="mt-1 font-bold tabular-nums text-gray-900">{r.net_weight != null ? formatWeight(r.net_weight) : '—'}</p>
                </div>
                <div className="rounded-lg border border-gray-100 bg-gray-50/50 p-3">
                    <p className="text-xs font-medium text-gray-600">Loading time</p>
                    <p className="mt-1 font-bold tabular-nums text-gray-900">{loadingHours != null ? `${loadingHours}h ${loadingMins}m` : '—'}</p>
                </div>
                <div
                    className={`rounded-lg border p-3 ${
                        r.predicted_penalty_amount > 0
                            ? 'border-red-100 bg-red-50'
                            : 'border-green-100 bg-green-50'
                    }`}
                >
                    <p className="text-xs font-medium text-gray-600">
                        Predicted Penalty
                    </p>
                    <p
                        className={`mt-1 font-bold tabular-nums ${
                            r.predicted_penalty_amount > 0
                                ? 'text-red-700'
                                : 'text-green-700'
                        }`}
                    >
                        {r.predicted_penalty_amount > 0
                            ? formatCurrency(r.predicted_penalty_amount)
                            : 'None'}
                    </p>
                </div>
                <div
                    className={`rounded-lg border p-3 ${
                        r.actual_penalty_amount > 0
                            ? 'border-red-100 bg-red-50'
                            : 'border-green-100 bg-green-50'
                    }`}
                >
                    <p className="text-xs font-medium text-gray-600">
                        Actual Penalty
                    </p>
                    <p
                        className={`mt-1 font-bold tabular-nums ${
                            r.actual_penalty_amount > 0
                                ? 'text-red-700'
                                : 'text-green-700'
                        }`}
                    >
                        {r.actual_penalty_amount > 0
                            ? formatCurrency(r.actual_penalty_amount)
                            : 'None'}
                    </p>
                </div>
            </div>

            <div className="mt-5 space-y-8">
                <div>
                    <p className="mb-2 text-xs font-medium text-gray-600">Weight breakdown (MT)</p>
                    {weightChartData.length > 0 ? (
                        <ResponsiveContainer width="100%" height={260}>
                            <RechartsPieChart margin={{ top: 8, right: 8, bottom: 8, left: 8 }}>
                                <Pie
                                    data={weightChartData}
                                    dataKey="value"
                                    nameKey="name"
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={56}
                                    outerRadius={92}
                                    minAngle={2}
                                    paddingAngle={2}
                                    isAnimationActive
                                >
                                    {weightChartData.map((entry, i) => (
                                        <Cell key={`${entry.name}-${i}`} fill={entry.fill ?? '#4B72BE'} />
                                    ))}
                                </Pie>
                                <Tooltip
                                    formatter={(value, name) => [
                                        `${Number(value ?? 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })} MT`,
                                        String(name ?? ''),
                                    ]}
                                />
                                <Legend verticalAlign="bottom" wrapperStyle={{ fontSize: 12, paddingTop: 8 }} />
                            </RechartsPieChart>
                        </ResponsiveContainer>
                    ) : (
                        <div className="flex h-[220px] items-center justify-center rounded-lg border border-gray-100 bg-gray-50/50 text-sm text-gray-600">
                            No weighment data available
                        </div>
                    )}
                </div>
                <div>
                    <div className="mb-3 flex flex-wrap items-end justify-between gap-x-3 gap-y-2">
                        <div className="min-w-0 space-y-0.5">
                            <p className="text-xs font-semibold text-gray-900">Wagon-wise overload / underload (MT)</p>
                            <p className="text-[11px] font-semibold text-red-600">This data is based on weighment.</p>
                        </div>
                        <div className="flex flex-wrap items-end justify-end gap-2 sm:ml-auto">
                            <Popover>
                                <PopoverTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="h-8 gap-1.5 px-2.5 text-[11px] font-medium"
                                    >
                                        Weighment KPIs
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent align="end" sideOffset={6} className="w-[min(100vw-2rem,18rem)] p-3 text-xs shadow-lg">
                                    <p className="mb-2 border-b border-gray-100 pb-2 text-[11px] font-semibold text-gray-900">
                                        Weighment summary
                                    </p>
                                    <dl className="space-y-2 text-[11px]">
                                        <div className="flex items-baseline justify-between gap-3">
                                            <dt className="shrink-0 text-gray-600">Underload stock</dt>
                                            <dd className="font-semibold tabular-nums text-amber-900">
                                                {fmtMt(wagonWeighmentSummary.underloadStockMt)} MT
                                            </dd>
                                        </div>
                                        <div className="flex items-baseline justify-between gap-3">
                                            <dt className="shrink-0 text-gray-600">Overload stock</dt>
                                            <dd className="font-semibold tabular-nums text-red-900">
                                                {fmtMt(wagonWeighmentSummary.overloadStockMt)} MT
                                            </dd>
                                        </div>
                                        <div className="flex items-baseline justify-between gap-3">
                                            <dt className="shrink-0 text-gray-600">Underload wagons</dt>
                                            <dd className="font-semibold tabular-nums text-gray-900">
                                                {wagonWeighmentSummary.underloadWagons}
                                            </dd>
                                        </div>
                                        <div className="flex items-baseline justify-between gap-3">
                                            <dt className="shrink-0 text-gray-600">Overload wagons</dt>
                                            <dd className="font-semibold tabular-nums text-gray-900">
                                                {wagonWeighmentSummary.overloadWagons}
                                            </dd>
                                        </div>
                                    </dl>
                                </PopoverContent>
                            </Popover>
                            <div className="flex flex-col items-end gap-0.5">
                                <label
                                    htmlFor="rake-perf-modal-underload"
                                    className="text-[10px] font-medium leading-tight text-gray-600"
                                >
                                    Underload threshold (% of CC)
                                </label>
                                <Input
                                    id="rake-perf-modal-underload"
                                    type="number"
                                    inputMode="decimal"
                                    min={0}
                                    step={0.1}
                                    className="h-8 w-[4.5rem] rounded-md border-gray-200 bg-white px-2 text-xs font-semibold tabular-nums"
                                    value={underloadThresholdPercent}
                                    onChange={(e) => {
                                        const raw = e.target.value;
                                        if (raw === '') {
                                            onUnderloadThresholdChange(0);
                                            return;
                                        }
                                        const v = parseFloat(raw);
                                        if (!Number.isNaN(v)) {
                                            onUnderloadThresholdChange(Math.max(0, v));
                                        }
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                    {wagonBarChartData.length > 0 ? (
                        <ResponsiveContainer width="100%" height={400}>
                            <RechartsBarChart
                                data={wagonBarChartData}
                                margin={{ top: 8, right: 8, left: 8, bottom: 24 }}
                            >
                                <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                <XAxis dataKey="position" tick={{ fontSize: 10 }} interval={0} height={40} />
                                <YAxis
                                    tick={{ fontSize: 11 }}
                                    tickFormatter={(v: number) => `${v} MT`}
                                    label={{
                                        value: 'Overload + / Underload − (MT)',
                                        angle: -90,
                                        position: 'insideLeft',
                                        style: { fontSize: 10 },
                                    }}
                                />
                                <ReferenceLine y={0} stroke="#9CA3AF" strokeWidth={1} />
                                <Tooltip
                                    content={({ active, payload }) => {
                                        if (!active || !payload?.length) return null;
                                        const pl = payload[0]?.payload as {
                                            wagon_number?: string;
                                            bar_mt?: number;
                                            over_load_mt?: number;
                                            under_load_mt?: number | null;
                                            cc_capacity_mt?: number | null;
                                            net_weight_mt?: number | null;
                                            loader_name?: string | null;
                                            loader_operator_name?: string | null;
                                            shortfall_pct?: number | null;
                                        };
                                        const wagonNum = pl.wagon_number ?? '—';
                                        const cc =
                                            pl.cc_capacity_mt != null
                                                ? `${Number(pl.cc_capacity_mt).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })} MT`
                                                : '—';
                                        const net =
                                            pl.net_weight_mt != null
                                                ? `${Number(pl.net_weight_mt).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })} MT`
                                                : '—';
                                        const over =
                                            pl.over_load_mt != null && pl.over_load_mt > 0
                                                ? `${Number(pl.over_load_mt).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })} MT`
                                                : '—';
                                        const under =
                                            pl.under_load_mt != null && pl.under_load_mt > 0
                                                ? `${Number(pl.under_load_mt).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })} MT`
                                                : '—';
                                        const sf =
                                            pl.shortfall_pct != null
                                                ? `${pl.shortfall_pct.toLocaleString(undefined, { maximumFractionDigits: 2 })}%`
                                                : '—';
                                        const loaderLabel =
                                            pl.loader_name != null && String(pl.loader_name).trim() !== ''
                                                ? String(pl.loader_name).trim()
                                                : '—';
                                        const operatorLabel =
                                            pl.loader_operator_name != null && String(pl.loader_operator_name).trim() !== ''
                                                ? String(pl.loader_operator_name).trim()
                                                : '—';
                                        return (
                                            <div className="max-w-xs rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm shadow-lg">
                                                <p className="font-medium text-gray-800">Wagon {wagonNum}</p>
                                                <p className="mt-1 text-gray-600">
                                                    Loader: <span className="font-medium text-gray-900">{loaderLabel}</span>
                                                </p>
                                                <p className="text-gray-600">
                                                    Operator: <span className="text-gray-900">{operatorLabel}</span>
                                                </p>
                                                <p className="mt-1 text-gray-600">
                                                    CC: <span className="font-medium tabular-nums text-gray-900">{cc}</span>
                                                </p>
                                                <p className="text-gray-600">
                                                    Net: <span className="tabular-nums">{net}</span>
                                                </p>
                                                <p className="text-gray-600">
                                                    Overload: <span className="tabular-nums text-red-700">{over}</span>
                                                </p>
                                                <p className="text-gray-600">
                                                    Underload: <span className="tabular-nums text-amber-700">{under}</span>
                                                </p>
                                                <p className="text-gray-600">
                                                    Shortfall % of CC: <span className="tabular-nums">{sf}</span>
                                                </p>
                                            </div>
                                        );
                                    }}
                                />
                                <Bar
                                    dataKey="bar_mt"
                                    radius={[4, 4, 4, 4]}
                                    barSize={20}
                                    isAnimationActive
                                    className="cursor-pointer [&_.recharts-rectangle]:cursor-pointer"
                                    onClick={(item) => {
                                        const row = item?.payload as
                                            | { loader_id?: number | null; wagon_number?: string }
                                            | undefined;
                                        if (row?.loader_id != null) {
                                            onNavigateToLoader(row.loader_id, underloadThresholdPercent);
                                            return;
                                        }
                                        setNoLoaderWagonNumber(row?.wagon_number ?? null);
                                        setNoLoaderDialogOpen(true);
                                    }}
                                >
                                    {wagonBarChartData.map((entry, i) => {
                                        let fill = '#E5E7EB';
                                        if (entry.bar_mt > 0) {
                                            fill = '#DC2626';
                                        } else if (entry.bar_mt < 0) {
                                            fill = '#D97706';
                                        }
                                        return <Cell key={i} fill={fill} />;
                                    })}
                                </Bar>
                            </RechartsBarChart>
                        </ResponsiveContainer>
                    ) : (
                        <div className={`flex min-h-[280px] flex-col items-center justify-center gap-3 rounded-xl p-6 ${r.over_load != null && r.over_load > 0 ? 'bg-[#FEF2F2]' : 'bg-gray-50'}`}>
                            {r.over_load != null && r.over_load > 0 ? (
                                <>
                                    <TriangleAlert className="size-10 text-red-600" aria-hidden />
                                    <span className="text-lg font-bold tabular-nums text-red-700">+{r.over_load.toLocaleString()} MT total</span>
                                    <span className="text-xs text-gray-600">No wagon-level weighment data</span>
                                </>
                            ) : (
                                <>
                                    <Check className="size-10 text-green-600" aria-hidden />
                                    <span className="text-sm font-medium text-green-700">Within limits</span>
                                    <span className="text-xs text-gray-600">No wagon-level weighment data</span>
                                </>
                            )}
                        </div>
                    )}
                </div>
            </div>

            <Dialog open={noLoaderDialogOpen} onOpenChange={setNoLoaderDialogOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Loader data unavailable</DialogTitle>
                        <DialogDescription>
                            {noLoaderWagonNumber != null
                                ? `Loader data is not available for wagon ${noLoaderWagonNumber}.`
                                : 'Loader data is not available for this wagon.'}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" onClick={() => setNoLoaderDialogOpen(false)}>
                            OK
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

/**
 * Siding-specific display for the rake sequence column: P-/D-/K- + rake_number for known locations.
 */
function formatRakeSequenceForSiding(sidingName: string, rakeNumber: string): string {
    const n = sidingName.trim().toLowerCase();
    if (n.includes('pakur')) {
        return `P-${rakeNumber}`;
    }
    if (n.includes('dumka')) {
        return `D-${rakeNumber}`;
    }
    if (n.includes('kurwa') || n.includes('kurawa')) {
        return `K-${rakeNumber}`;
    }
    return rakeNumber;
}

function RakePerformanceSection({
    filters,
    allSidingIds,
    sidings,
    rakePenaltyScope,
    onRakePenaltyScopeChange,
    onNavigateToLoader,
}: {
    filters: DashboardFilters;
    allSidingIds: number[];
    /** Sidings in scope (for pills + siding_id filter). */
    sidings: SidingOption[];
    rakePenaltyScope: 'all' | 'with_penalties';
    onRakePenaltyScopeChange: (scope: 'all' | 'with_penalties') => void;
    onNavigateToLoader: (loaderId: number, rakeUnderloadThresholdPercent: number) => void;
}) {
    const filterKey = useMemo(
        () =>
            [
                filters.period,
                filters.from,
                filters.to,
                filters.siding_ids.join(','),
                filters.power_plant,
                filters.rake_number ?? '',
                filters.rake_penalty_scope ?? 'all',
            ].join('|'),
        [
            filters.period,
            filters.from,
            filters.to,
            filters.siding_ids,
            filters.power_plant,
            filters.rake_number,
            filters.rake_penalty_scope,
        ],
    );

    const [page, setPage] = useState(1);
    const [selectedSidingTab, setSelectedSidingTab] = useState<'all' | number>('all');
    const [rows, setRows] = useState<RakePerformanceSummaryItem[]>([]);
    const [listMeta, setListMeta] = useState<{
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    } | null>(null);
    const [listLoading, setListLoading] = useState(true);
    const [listError, setListError] = useState<string | null>(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [modalRakeId, setModalRakeId] = useState<number | null>(null);
    const [detail, setDetail] = useState<RakePerformanceItem | null>(null);
    const [detailLoading, setDetailLoading] = useState(false);
    const [detailError, setDetailError] = useState<string | null>(null);
    const [modalUnderloadThreshold, setModalUnderloadThreshold] = useState(1);

    useEffect(() => {
        setSelectedSidingTab('all');
    }, [filterKey]);

    useEffect(() => {
        setPage(1);
    }, [filterKey, selectedSidingTab]);

    useEffect(() => {
        if (selectedSidingTab === 'all') {
            return;
        }
        if (!sidings.some((s) => s.id === selectedSidingTab)) {
            setSelectedSidingTab('all');
        }
    }, [sidings, selectedSidingTab]);

    useEffect(() => {
        let cancelled = false;
        setListLoading(true);
        setListError(null);
        const qs = buildRakePerformanceApiSearchParams({
            filters,
            allSidingIds,
            page,
            perPage: 15,
            sidingId: selectedSidingTab === 'all' ? undefined : selectedSidingTab,
        });
        laravelJsonFetch<{
            data: RakePerformanceSummaryItem[];
            meta: { current_page: number; last_page: number; per_page: number; total: number };
        }>(`/dashboard/rake-performance/rakes?${qs}`)
            .then((res) => {
                if (!cancelled) {
                    setRows(res.data);
                    setListMeta(res.meta);
                }
            })
            .catch((e: unknown) => {
                if (!cancelled) {
                    setListError(e instanceof Error ? e.message : 'Failed to load rakes');
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setListLoading(false);
                }
            });
        return () => {
            cancelled = true;
        };
    }, [filterKey, allSidingIds, page, selectedSidingTab]);

    useEffect(() => {
        if (modalRakeId == null) {
            setDetail(null);
            return;
        }
        let cancelled = false;
        setDetailLoading(true);
        setDetailError(null);
        const qs = buildRakePerformanceApiSearchParams({
            filters,
            allSidingIds,
            sidingId: selectedSidingTab === 'all' ? undefined : selectedSidingTab,
        });
        laravelJsonFetch<{ data: RakePerformanceItem }>(`/dashboard/rake-performance/rakes/${modalRakeId}?${qs}`)
            .then((res) => {
                if (!cancelled) {
                    setDetail(res.data);
                }
            })
            .catch((e: unknown) => {
                if (!cancelled) {
                    setDetailError(e instanceof Error ? e.message : 'Failed to load');
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setDetailLoading(false);
                }
            });
        return () => {
            cancelled = true;
        };
    }, [modalRakeId, filterKey, allSidingIds, selectedSidingTab]);

    const showSidingColumn = selectedSidingTab === 'all';

    const openRake = (id: number) => {
        setModalRakeId(id);
        setModalOpen(true);
        setModalUnderloadThreshold(1);
    };

    return (
        <div className="dashboard-card rounded-xl border-0 p-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <SectionHeader
                    icon={Train}
                    title="Rake-wise performance"
                    subtitle="Click a row to view weighment charts and details"
                    action={
                        <Select
                            value={rakePenaltyScope}
                            onValueChange={(v) =>
                                onRakePenaltyScopeChange(
                                    v === 'with_penalties' ? 'with_penalties' : 'all',
                                )
                            }
                        >
                            <SelectTrigger className="min-w-[160px] rounded-lg border border-gray-200 bg-white text-sm">
                                <SelectValue placeholder="Penalty scope" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All</SelectItem>
                                <SelectItem value="with_penalties">With penalties</SelectItem>
                            </SelectContent>
                        </Select>
                    }
                />
            </div>

            {listError != null && (
                <p className="mt-4 text-sm text-red-600" role="alert">
                    {listError}
                </p>
            )}

            {sidings.length > 0 && (
                <div className="mt-4">
                    <p className="mb-2 text-xs font-medium text-gray-600">Siding</p>
                    <div className="flex flex-wrap items-center gap-1">
                        <button
                            type="button"
                            onClick={() => {
                                setSelectedSidingTab('all');
                            }}
                            className={
                                'rounded-full px-3 py-1.5 text-[11px] font-medium transition-colors ' +
                                (selectedSidingTab === 'all'
                                    ? 'bg-[#111827] text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200')
                            }
                        >
                            All
                        </button>
                        {sidings.map((s) => (
                            <button
                                key={s.id}
                                type="button"
                                onClick={() => {
                                    setSelectedSidingTab(s.id);
                                }}
                                className={
                                    'rounded-full px-3 py-1.5 text-[11px] font-medium transition-colors ' +
                                    (selectedSidingTab === s.id
                                        ? 'bg-[#111827] text-white'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200')
                                }
                            >
                                {s.name}
                            </button>
                        ))}
                    </div>
                </div>
            )}

            <div className="mt-4 overflow-x-auto">
                {listLoading ? (
                    <p className="py-8 text-center text-sm text-gray-600">Loading rakes…</p>
                ) : rows.length === 0 ? (
                    <p className="py-8 text-center text-sm text-gray-600">No rake performance data for this range.</p>
                ) : (
                    <Table className="w-full min-w-0 text-xs [border-spacing:0] [&_th]:h-8 [&_th]:px-1.5 [&_th]:py-1.5 [&_td]:px-1.5 [&_td]:py-1.5 sm:[&_th]:px-2 sm:[&_td]:px-2">
                        <TableHeader>
                            <TableRow>
                                <TableHead className="whitespace-normal sm:whitespace-nowrap">
                                    Rake sequence
                                </TableHead>
                                <TableHead className="whitespace-normal sm:whitespace-nowrap">Rake number</TableHead>
                                <TableHead>Dispatch</TableHead>
                                {showSidingColumn && <TableHead>Siding</TableHead>}
                                <TableHead className="text-right">Net (MT)</TableHead>
                                <TableHead className="text-right">Pred. penalty</TableHead>
                                <TableHead className="text-right">Actual penalty</TableHead>
                                <TableHead className="text-right">Overload</TableHead>
                                <TableHead className="text-right">Underload</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.map((row) => (
                                <TableRow
                                    key={row.id}
                                    className="cursor-pointer"
                                    onClick={() => {
                                        openRake(row.id);
                                    }}
                                >
                                    <TableCell className="font-medium [overflow-wrap:anywhere] sm:whitespace-nowrap">
                                        {formatRakeSequenceForSiding(row.siding, row.rake_number)}
                                    </TableCell>
                                    <TableCell className="[overflow-wrap:anywhere] sm:whitespace-nowrap">
                                        {row.rake_serial_number != null && String(row.rake_serial_number).trim() !== '' ? (
                                            <span className="text-gray-700">{row.rake_serial_number}</span>
                                        ) : (
                                            <span className="font-medium text-yellow-600 tabular-nums">
                                                {row.rake_number}
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="tabular-nums [overflow-wrap:anywhere] sm:whitespace-nowrap">
                                        {row.dispatch_date}
                                    </TableCell>
                                    {showSidingColumn && (
                                        <TableCell className="[overflow-wrap:anywhere] text-gray-800 sm:whitespace-nowrap">
                                            {row.siding}
                                        </TableCell>
                                    )}
                                    <TableCell className="text-right tabular-nums text-gray-900">
                                        {row.net_weight != null ? formatWeight(row.net_weight) : '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums text-gray-900">
                                        {row.predicted_penalty_amount > 0
                                            ? formatCurrency(row.predicted_penalty_amount)
                                            : '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums text-gray-900">
                                        {row.actual_penalty_amount > 0
                                            ? formatCurrency(row.actual_penalty_amount)
                                            : '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums text-red-700">
                                        {row.over_load != null && row.over_load > 0
                                            ? row.over_load.toLocaleString()
                                            : '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums text-amber-800">
                                        {row.under_load != null && row.under_load > 0
                                            ? row.under_load.toLocaleString()
                                            : '—'}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </div>

            {listMeta != null && listMeta.total > 0 && (
                <div className="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm text-gray-600">
                    <p>
                        Page {listMeta.current_page} of {listMeta.last_page} ({listMeta.total} rakes)
                    </p>
                    <div className="flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={listMeta.current_page <= 1}
                            onClick={() => {
                                setPage((p) => Math.max(1, p - 1));
                            }}
                        >
                            Previous
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={listMeta.current_page >= listMeta.last_page}
                            onClick={() => {
                                setPage((p) => p + 1);
                            }}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            )}

            <Dialog
                open={modalOpen}
                onOpenChange={(open) => {
                    setModalOpen(open);
                    if (!open) {
                        setModalRakeId(null);
                    }
                }}
            >
                <DialogContent className="max-h-[95vh] w-[min(96vw,1400px)] max-w-[min(96vw,1400px)] overflow-y-auto sm:max-w-[min(96vw,1400px)]">
                    <DialogHeader>
                        <DialogTitle>
                            {detail != null
                                ? `${detail.rake_serial_number ?? detail.rake_number} — ${detail.siding}`
                                : 'Rake performance'}
                        </DialogTitle>
                        <DialogDescription className="sr-only">
                            Weighment breakdown and wagon-wise overload and underload for the selected rake.
                        </DialogDescription>
                    </DialogHeader>
                    {detailLoading && <p className="text-sm text-gray-600">Loading details…</p>}
                    {detailError != null && <p className="text-sm text-red-600">{detailError}</p>}
                    {detail != null && !detailLoading && (
                        <RakePerformanceDetailCharts
                            r={detail}
                            underloadThresholdPercent={modalUnderloadThreshold}
                            onUnderloadThresholdChange={setModalUnderloadThreshold}
                            onNavigateToLoader={onNavigateToLoader}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
}


const SIDING_COLORS = [DASHBOARD_PALETTE.steelBlue, DASHBOARD_PALETTE.successGreen, DASHBOARD_PALETTE.safetyYellow, DASHBOARD_PALETTE.steelBlueLight, DASHBOARD_PALETTE.successGreenLight];

const PLANT_COLORS: Record<string, string> = { PSPM: '#3B82F6', STPS: '#10B981', BTPC: '#F59E0B', KPPS: '#8B5CF6' };

/** Muted bars so the chart frame is visible when there is no dispatch data. */
const POWER_PLANT_CHART_EMPTY_ROWS: PowerPlantDispatchItem[] = [
    { name: '—', rakes: 1, weight_mt: 100, sidings: {} },
    { name: '—', rakes: 1, weight_mt: 100, sidings: {} },
    { name: '—', rakes: 1, weight_mt: 100, sidings: {} },
];

/** Rakes-by-plant bar only (subset of Power plant wise dispatch). */
function RakesPerPowerPlantExecutiveChart({
    data,
    period,
    onPeriodChange,
    metric,
    onMetricChange,
}: {
    data: PowerPlantDispatchItem[];
    period?: ExecutiveChartPeriodKey;
    onPeriodChange?: (p: ExecutiveChartPeriodKey) => void;
    metric?: 'rakes' | 'qty';
    onMetricChange?: (k: 'rakes' | 'qty') => void;
}) {
    const valueKind = metric ?? 'rakes';
    const sorted = useMemo(() => {
        const copy = [...data];
        copy.sort((a, b) => (valueKind === 'qty' ? b.weight_mt - a.weight_mt : b.rakes - a.rakes));

        return copy;
    }, [data, valueKind]);

    const isEmpty = data.length === 0;
    const chartRows = isEmpty ? POWER_PLANT_CHART_EMPTY_ROWS : sorted;
    const dataKey = valueKind === 'qty' ? 'weight_mt' : 'rakes';
    const showControls = onPeriodChange != null && period != null && onMetricChange != null && metric != null;
    const chartHeight = Math.max(260, chartRows.length * 40);

    return (
        <div className="dashboard-card overflow-hidden rounded-xl border border-[#d5dbe4] bg-white p-0">
            <div className="border-b border-[#d5dbe4] bg-[#f8fafc] px-4 py-3">
                {showControls ? (
                    <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                        <SectionHeader
                            icon={Factory}
                            title="Powerplant Dispatch"
                            subtitle="Rake load dispatched to each station (anchor period)"
                        />
                        <div className="flex flex-wrap items-center gap-2">
                            <Select value={period} onValueChange={(v) => onPeriodChange(v as ExecutiveChartPeriodKey)}>
                                <SelectTrigger className="h-9 w-[160px] text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {EXEC_CHART_PERIOD_OPTIONS.map((o) => (
                                        <SelectItem key={o.value} value={o.value} className="text-xs">
                                            {o.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <div className="flex rounded-lg border border-gray-200 p-0.5">
                                <Button
                                    type="button"
                                    variant={metric === 'rakes' ? 'default' : 'ghost'}
                                    size="sm"
                                    className="h-8 px-3 text-xs"
                                    onClick={() => onMetricChange('rakes')}
                                >
                                    Rakes
                                </Button>
                                <Button
                                    type="button"
                                    variant={metric === 'qty' ? 'default' : 'ghost'}
                                    size="sm"
                                    className="h-8 px-3 text-xs"
                                    onClick={() => onMetricChange('qty')}
                                >
                                    Qty
                                </Button>
                            </div>
                        </div>
                    </div>
                ) : (
                    <SectionHeader
                        icon={Factory}
                        title="Powerplant Dispatch"
                        subtitle="From weighment destinations for current filters"
                    />
                )}
            </div>
            <div className="relative bg-[#fbfbfc] p-4">
                <div className="relative min-h-[260px]">
                    <ResponsiveContainer width="100%" height={chartHeight}>
                        <RechartsBarChart data={chartRows} margin={{ top: 8, right: 16, bottom: 0, left: 8 }}>
                            <CartesianGrid strokeDasharray="3 3" strokeOpacity={isEmpty ? 0.2 : 0.3} />
                            <XAxis
                                dataKey="name"
                                tick={{ fontSize: 11, fill: isEmpty ? '#94a3b8' : undefined }}
                            />
                            <YAxis
                                allowDecimals={valueKind === 'qty'}
                                tick={{ fontSize: 11 }}
                                tickFormatter={(v) =>
                                    valueKind === 'qty' ? `${Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 })}` : `${v}`
                                }
                                domain={isEmpty ? [0, 'auto'] : undefined}
                            />
                            <Tooltip
                                formatter={(v: number | undefined) => {
                                    if (isEmpty) {
                                        return ['No dispatch data for this period', ''];
                                    }

                                    return valueKind === 'qty'
                                        ? `${(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })} MT`
                                        : `${v ?? 0} rakes`;
                                }}
                            />
                            <Bar
                                dataKey={dataKey}
                                radius={[4, 4, 0, 0]}
                                barSize={32}
                                isAnimationActive={!isEmpty}
                            >
                                {chartRows.map((pp, i) => (
                                    <Cell
                                        key={`${pp.name}-${i}`}
                                        fill={isEmpty ? '#e2e8f0' : PLANT_COLORS[pp.name] ?? SIDING_COLORS[i % SIDING_COLORS.length]}
                                    />
                                ))}
                                {!isEmpty ? (
                                    <LabelList
                                        dataKey={dataKey}
                                        position="top"
                                        formatter={(v: unknown) =>
                                            valueKind === 'qty'
                                                ? `${Number(v ?? 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}`
                                                : String(v ?? '')
                                        }
                                    />
                                ) : null}
                            </Bar>
                        </RechartsBarChart>
                    </ResponsiveContainer>
                    {isEmpty ? (
                        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center pb-8 text-center">
                            <Factory className="mb-2 h-8 w-8 text-muted-foreground/35" />
                            <p className="text-xs font-medium text-muted-foreground">No dispatch data</p>
                            <p className="mt-0.5 text-[11px] text-muted-foreground/80">for this period</p>
                        </div>
                    ) : null}
                </div>
            </div>
        </div>
    );
}

function PowerPlantDispatchSection({ data }: { data: PowerPlantDispatchItem[] }) {
    const [stacked, setStacked] = useState(true);
    const [expandedIdx, setExpandedIdx] = useState<number | null>(null);

    const totalRakes = useMemo(() => data.reduce((sum, pp) => sum + pp.rakes, 0), [data]);
    const totalWeight = useMemo(() => data.reduce((sum, pp) => sum + pp.weight_mt, 0), [data]);
    const allSidingNames = useMemo(() => {
        const names = new Set<string>();
        data.forEach((pp) => Object.keys(pp.sidings).forEach((s) => names.add(s)));
        return Array.from(names);
    }, [data]);
    const maxWeight = useMemo(() => Math.max(...data.map((pp) => pp.weight_mt), 1), [data]);

    const stackedChartData = useMemo(
        () =>
            data.map((pp) => {
                const row: Record<string, unknown> = { name: pp.name };
                allSidingNames.forEach((sn) => {
                    row[sn] = pp.sidings[sn]?.rakes ?? 0;
                });
                return row;
            }),
        [data, allSidingNames],
    );

    const sortedByRakes = useMemo(() => [...data].sort((a, b) => b.rakes - a.rakes), [data]);

    if (data.length === 0) {
        return (
            <div className="dashboard-card rounded-xl border-0 p-6">
                <SectionHeader icon={Factory} title="Power plant wise dispatch" subtitle="How many rakes sent to each power plant" />
                <div className="mt-6 flex flex-col items-center justify-center py-10 text-center text-gray-600">
                    <Factory className="mb-3 h-10 w-10 opacity-30" />
                    <p className="text-sm font-medium">No dispatch data available</p>
                    <p className="mt-1 text-xs">Weighment data with destination stations will appear here once rakes are dispatched.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="dashboard-card rounded-xl border-0 p-6">
            <SectionHeader icon={Factory} title="Power plant wise dispatch" subtitle="How many rakes sent to each power plant" />
            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div className="dashboard-card flex items-center gap-3 rounded-xl border-0 p-4" style={{ borderTop: '4px solid #3B82F6' }}>
                    <MapPin className="size-5 text-blue-600" />
                    <div>
                        <div className="text-xs font-medium text-gray-600">Destinations</div>
                        <div className="text-xl font-bold tabular-nums text-gray-900">{data.length}</div>
                    </div>
                </div>
                <div className="dashboard-card flex items-center gap-3 rounded-xl border-0 p-4" style={{ borderTop: '4px solid #3B82F6' }}>
                    <Train className="size-5 text-blue-600" />
                    <div>
                        <div className="text-xs font-medium text-gray-600">Total rakes</div>
                        <div className="text-xl font-bold tabular-nums text-gray-900">{totalRakes}</div>
                    </div>
                </div>
                <div className="dashboard-card flex items-center gap-3 rounded-xl border-0 p-4" style={{ borderTop: '4px solid #3B82F6' }}>
                    <BarChart3 className="size-5 text-blue-600" />
                    <div>
                        <div className="text-xs font-medium text-gray-600">Total weight</div>
                        <div className="text-xl font-bold tabular-nums text-gray-900">{formatWeight(totalWeight)}</div>
                    </div>
                </div>
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <div>
                    <div className="mb-2 flex items-center justify-between">
                        <h4 className="text-xs font-medium text-gray-600">Rakes sent to each power plant by siding</h4>
                        <button
                            type="button"
                            onClick={() => setStacked(!stacked)}
                            className="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-medium text-gray-600"
                        >
                            {stacked ? 'Grouped' : 'Stacked'}
                        </button>
                    </div>
                    <div className="min-h-0 w-full overflow-x-auto pt-1">
                        <ResponsiveContainer width="100%" height={Math.max(280, data.length * 52)}>
                            <RechartsBarChart
                                data={stackedChartData}
                                margin={{ left: 8, right: 12, top: stacked ? 12 : 22, bottom: 8 }}
                                barCategoryGap="18%"
                            >
                                <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                <XAxis dataKey="name" tick={{ fontSize: 10 }} interval={0} height={52} />
                                <YAxis allowDecimals={false} tick={{ fontSize: 10 }} width={40} domain={[0, 'auto']} />
                                <Tooltip />
                                <Legend wrapperStyle={{ fontSize: 11 }} />
                                {allSidingNames.map((sn, i) => (
                                    <Bar
                                        key={sn}
                                        dataKey={sn}
                                        stackId={stacked ? 'stack' : undefined}
                                        fill={SIDING_COLORS[i % SIDING_COLORS.length]}
                                        name={sn}
                                        radius={[2, 2, 0, 0]}
                                        maxBarSize={28}
                                    >
                                        {!stacked ? (
                                            <LabelList
                                                dataKey={sn}
                                                position="top"
                                                fill="#4b5563"
                                                fontSize={10}
                                                formatter={(label) => {
                                                    const n = Number(label ?? 0);

                                                    return n > 0 && !Number.isNaN(n) ? String(n) : '';
                                                }}
                                            />
                                        ) : null}
                                    </Bar>
                                ))}
                            </RechartsBarChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                <div>
                    <h4 className="mb-2 text-xs font-medium text-gray-600">Rakes dispatched per power plant</h4>
                    <div className="min-h-0 w-full overflow-x-auto pt-1">
                        <ResponsiveContainer width="100%" height={Math.max(280, data.length * 44)}>
                            <RechartsBarChart data={sortedByRakes} margin={{ top: 22, right: 12, bottom: 4, left: 8 }}>
                                <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                <XAxis dataKey="name" tick={{ fontSize: 10 }} />
                                <YAxis allowDecimals={false} tick={{ fontSize: 10 }} width={40} domain={[0, 'auto']} />
                                <Tooltip formatter={(v: number | undefined) => `${v ?? 0} rakes`} />
                                <Bar dataKey="rakes" radius={[4, 4, 0, 0]} maxBarSize={32} isAnimationActive>
                                    {sortedByRakes.map((pp, i) => (
                                        <Cell key={pp.name} fill={PLANT_COLORS[pp.name] ?? SIDING_COLORS[i % SIDING_COLORS.length]} />
                                    ))}
                                    <LabelList
                                        dataKey="rakes"
                                        position="top"
                                        fill="#4b5563"
                                        fontSize={10}
                                        formatter={(label) => {
                                            const n = Number(label ?? 0);

                                            return n > 0 && !Number.isNaN(n) ? String(n) : '';
                                        }}
                                    />
                                </Bar>
                            </RechartsBarChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>

            <div className="mt-6">
                <h4 className="mb-3 text-sm font-semibold text-gray-600">Destination breakdown</h4>
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {data.map((pp, i) => {
                        const color = PLANT_COLORS[pp.name] ?? SIDING_COLORS[i % SIDING_COLORS.length];
                        const isExpanded = expandedIdx === i;
                        const sidingEntries = Object.entries(pp.sidings);
                        const maxSidingRakes = Math.max(...sidingEntries.map(([, info]) => info.rakes), 1);
                        return (
                            <div
                                key={pp.name}
                                className="dashboard-card group min-w-0 rounded-xl border-0 p-4 transition-all hover:shadow-md"
                                style={{ borderLeft: `4px solid ${color}` }}
                            >
                                <button
                                    type="button"
                                    onClick={() => setExpandedIdx(isExpanded ? null : i)}
                                    className="flex w-full items-center justify-between gap-2 text-left"
                                >
                                    <div className="flex min-w-0 items-center gap-2">
                                        <span className="flex size-8 shrink-0 items-center justify-center rounded-lg text-[10px] font-bold text-white" style={{ backgroundColor: color }}>
                                            {pp.name.slice(0, 2).toUpperCase()}
                                        </span>
                                        <span className="truncate text-sm font-semibold text-gray-900">{pp.name}</span>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-2">
                                        <span className="text-xs tabular-nums text-gray-600">{pp.rakes} rakes</span>
                                        <span className="text-xs tabular-nums text-gray-600">{formatWeight(pp.weight_mt)}</span>
                                        {isExpanded ? <ChevronUp className="size-4 text-gray-600" /> : <ChevronDown className="size-4 text-gray-600" />}
                                    </div>
                                </button>
                                <div className="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-100" style={{ transition: 'width 0.8s ease' }}>
                                    <div
                                        className="h-full rounded-full transition-[width] duration-700 ease-out"
                                        style={{
                                            width: `${Math.min(100, (pp.weight_mt / maxWeight) * 100)}%`,
                                            background: `linear-gradient(90deg, ${color}, ${color}99)`,
                                        }}
                                    />
                                </div>
                                {isExpanded && sidingEntries.length > 0 && (
                                    <div className="mt-3 border-t border-gray-100 pt-3">
                                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            {sidingEntries.map(([sidingName, info], si) => (
                                                <div key={sidingName} className="min-w-0">
                                                    <span className="block truncate text-xs font-medium text-gray-600">{sidingName}</span>
                                                    <div className="mt-0.5 h-6 w-full overflow-hidden rounded bg-gray-100">
                                                        <div
                                                            className="h-full rounded transition-[width] duration-500 ease-out"
                                                            style={{ width: `${(info.rakes / maxSidingRakes) * 100}%`, backgroundColor: SIDING_COLORS[si % SIDING_COLORS.length] }}
                                                        />
                                                    </div>
                                                    <span className="mt-0.5 block text-[11px] tabular-nums text-gray-600">{info.rakes} rakes · {formatWeight(info.weight_mt)}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

function DashboardFiltersBar({
    sidings,
    filters,
    filterOptions,
    currentSection,
    inline = false,
    onClose,
}: {
    sidings: SidingOption[];
    filters: DashboardFilters;
    filterOptions: FilterOptions;
    currentSection?: string;
    inline?: boolean;
    onClose?: () => void;
}) {
    const [period, setPeriod] = useState(filters.period);
    const [customFrom, setCustomFrom] = useState(filters.from);
    const [customTo, setCustomTo] = useState(filters.to);
    const [showSidingDropdown, setShowSidingDropdown] = useState(false);
    const [rakeNumberInput, setRakeNumberInput] = useState(filters.rake_number ?? '');
    useEffect(() => {
        setRakeNumberInput(filters.rake_number ?? '');
    }, [filters.rake_number]);
    useEffect(() => {
        setPeriod(filters.period);
        setCustomFrom(filters.from);
        setCustomTo(filters.to);
    }, [filters.period, filters.from, filters.to]);

    const sectionId = (currentSection as (typeof DASHBOARD_SECTIONS)[number]['id']) ?? DEFAULT_DASHBOARD_SECTION;
    const sectionFilterKeys = SECTION_FILTER_KEYS[sectionId] ?? [];
    const sectionHasFilter = (key: (typeof sectionFilterKeys)[number] | string): boolean =>
        (sectionFilterKeys as readonly string[]).includes(key);

    const allSidingIds = useMemo(() => sidings.map((s) => s.id), [sidings]);

    const [pendingSidingIds, setPendingSidingIds] = useState<number[]>(filters.siding_ids);
    const isAllPendingSelected = pendingSidingIds.length === allSidingIds.length || pendingSidingIds.length === 0;

    const isAllSidingsSelected = filters.siding_ids.length === allSidingIds.length ||
        filters.siding_ids.length === 0;

    const hasPendingSidingChanges = useMemo(() => {
        const appliedSet = new Set(isAllSidingsSelected ? allSidingIds : filters.siding_ids);
        const pendingSet = new Set(isAllPendingSelected ? allSidingIds : pendingSidingIds);
        if (appliedSet.size !== pendingSet.size) return true;
        for (const id of appliedSet) {
            if (!pendingSet.has(id)) return true;
        }
        return false;
    }, [filters.siding_ids, pendingSidingIds, allSidingIds, isAllSidingsSelected, isAllPendingSelected]);

    const applyFilters = useCallback(
        (overrides: Record<string, unknown> = {}) => {
            const params = buildDashboardGetParams({
                overrides,
                filters,
                currentSection,
                allSidingIds,
                resolvedPeriod: (overrides.period as string | undefined) ?? period,
                resolvedFrom: (overrides.from as string | undefined) ?? customFrom,
                resolvedTo: (overrides.to as string | undefined) ?? customTo,
            });

            const dashboardPath = dashboard().url.split('?')[0] || dashboard().url;
            router.get(dashboardPath, params as Record<string, string>, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        [filters, customFrom, customTo, allSidingIds, currentSection, period],
    );

    const togglePendingSiding = useCallback((sidingId: number) => {
        setPendingSidingIds((prev) => {
            const current = prev.length === allSidingIds.length || prev.length === 0
                ? [...allSidingIds]
                : [...prev];
            const idx = current.indexOf(sidingId);
            if (idx >= 0) {
                current.splice(idx, 1);
            } else {
                current.push(sidingId);
            }
            return current.length === 0 ? prev : current;
        });
    }, [allSidingIds]);

    const applySidingFilter = useCallback(() => {
        applyFilters({ siding_ids: isAllPendingSelected ? allSidingIds : pendingSidingIds });
        setShowSidingDropdown(false);
    }, [pendingSidingIds, isAllPendingSelected, allSidingIds, applyFilters]);

    const resetSidingFilter = useCallback(() => {
        setPendingSidingIds(allSidingIds);
        applyFilters({ siding_ids: allSidingIds });
    }, [allSidingIds, applyFilters]);

    const resetAllFilters = useCallback(() => {
        setPeriod('yesterday');
        setCustomFrom(filters.from);
        setCustomTo(filters.to);
        setPendingSidingIds(allSidingIds);
        setRakeNumberInput('');
        applyFilters({
            period: 'yesterday',
            siding_ids: allSidingIds,
            power_plant: null,
            rake_number: null,
            loader_id: null,
            loader_operator: null,
            underload_threshold: 1,
            shift: null,
            penalty_type: null,
            rake_penalty_scope: 'all',
            daily_rake_date: '',
            coal_transport_date: '',
        });
    }, [allSidingIds, applyFilters, filters.from, filters.to]);

    const selectedSidingNames = useMemo(() => {
        if (isAllSidingsSelected) return 'All sidings';
        return sidings
            .filter((s) => filters.siding_ids.includes(s.id))
            .map((s) => s.name)
            .join(', ');
    }, [sidings, filters.siding_ids, isAllSidingsSelected]);

    const content = (
        <div className={inline ? 'flex flex-wrap items-center gap-2' : 'flex flex-wrap items-center gap-x-3 gap-y-2'}>
            {!inline && (
                <span className="flex shrink-0 items-center gap-1.5 text-[11px] font-medium text-gray-600">
                    <Filter className="size-3.5" />
                    Filters
                </span>
            )}
            {/* Period: dropdown when inline, pills when not */}
            {inline ? (
                <Select
                    value={period}
                    onValueChange={(v) => {
                        if (v === 'custom') {
                            setPeriod('custom');
                            return;
                        }
                        setPeriod(v);
                        applyFilters({ period: v });
                    }}
                >
                    <SelectTrigger className="h-7 min-w-[128px] rounded-md border text-[11px]">
                        <SelectValue placeholder="Period" />
                    </SelectTrigger>
                    <SelectContent>
                        {PERIODS.map((p) => (
                            <SelectItem key={p.key} value={p.key} className="text-xs">
                                {p.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ) : (
                <div className="flex flex-wrap items-center gap-1">
                    {PERIODS.map((p) => (
                        <button
                            key={p.key}
                            type="button"
                            onClick={() => {
                                if (p.key === 'custom') {
                                    setPeriod('custom');
                                    return;
                                }
                                setPeriod(p.key);
                                applyFilters({ period: p.key });
                            }}
                            className={
                                'rounded-full px-3 py-1.5 text-[11px] font-medium transition-colors ' +
                                (period === p.key
                                    ? 'bg-[#111827] text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200')
                            }
                        >
                            {p.label}
                        </button>
                    ))}
                </div>
            )}
            {period === 'custom' && (
                <>
                    <Calendar className="size-3.5 shrink-0 text-muted-foreground" />
                    <input
                        type="date"
                        value={customFrom}
                        onChange={(e) => setCustomFrom(e.target.value)}
                        className="h-7 w-28 rounded border bg-background px-2 text-[11px]"
                    />
                    <span className="text-[11px] text-muted-foreground">→</span>
                    <input
                        type="date"
                        value={customTo}
                        onChange={(e) => setCustomTo(e.target.value)}
                        className="h-7 w-28 rounded border bg-background px-2 text-[11px]"
                    />
                    {!inline && (
                        <Button
                            variant="outline"
                            size="sm"
                            className="h-7 shrink-0 px-2 text-[11px]"
                            onClick={() => applyFilters({ period: 'custom', from: customFrom, to: customTo })}
                        >
                            Apply
                        </Button>
                    )}
                </>
            )}
            <div className={inline ? 'flex flex-wrap items-center gap-2' : 'ml-auto flex flex-wrap items-center gap-2'}>
                {/* <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    className="h-7 px-2 text-[11px]"
                    onClick={resetAllFilters}
                >
                    Reset Test
                </Button> */}
                {sectionHasFilter('power_plant') && (
                    <Select
                        value={filters.power_plant ?? ALL_FILTER_VALUE}
                        onValueChange={(v) => applyFilters({ power_plant: v === ALL_FILTER_VALUE ? null : v })}
                    >
                        <SelectTrigger className="h-7 w-[120px] rounded-md border text-[11px]">
                            <SelectValue placeholder="Plant" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_FILTER_VALUE} className="text-xs">
                                All plants
                            </SelectItem>
                            {filterOptions.powerPlants.map((pp) => (
                                <SelectItem key={pp.value} value={pp.value} className="text-xs">
                                    {pp.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
                {sectionHasFilter('rake_number') && (
                    <input
                        type="text"
                        placeholder="Rake #"
                        value={rakeNumberInput}
                        onChange={(e) => setRakeNumberInput(e.target.value)}
                        onBlur={() => applyFilters({ rake_number: rakeNumberInput.trim() || null })}
                        onKeyDown={(e) => e.key === 'Enter' && applyFilters({ rake_number: rakeNumberInput.trim() || null })}
                        className="h-7 w-20 rounded-md border bg-background px-2 text-[11px]"
                    />
                )}
                {sectionHasFilter('loader_id') && (
                    <Select
                        value={filters.loader_id != null ? String(filters.loader_id) : ALL_FILTER_VALUE}
                        onValueChange={(v) =>
                            applyFilters({
                                loader_id: v === ALL_FILTER_VALUE ? null : Number(v),
                                loader_operator: null,
                            })
                        }
                    >
                        <SelectTrigger className="h-7 w-[120px] rounded-md border text-[11px]">
                            <SelectValue placeholder="Loader" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_FILTER_VALUE} className="text-xs">
                                All loaders
                            </SelectItem>
                            {filterOptions.loaders.map((l) => (
                                <SelectItem key={l.id} value={String(l.id)} className="text-xs">
                                    {l.name} ({l.siding_name})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
                {sectionHasFilter('loader_operator') && filters.loader_id != null && (
                    <Select
                        value={filters.loader_operator ?? ALL_FILTER_VALUE}
                        onValueChange={(v) =>
                            applyFilters({ loader_operator: v === ALL_FILTER_VALUE ? null : v })
                        }
                    >
                        <SelectTrigger className="h-7 min-w-[140px] rounded-md border text-[11px]">
                            <SelectValue placeholder="Operator" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_FILTER_VALUE} className="text-xs">
                                All operators
                            </SelectItem>
                            {(filterOptions.loaderOperatorsByLoader?.[String(filters.loader_id)] ?? []).map((name) => (
                                <SelectItem key={name} value={name} className="text-xs">
                                    {name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
                {sectionHasFilter('shift') && (
                    <Select
                        value={filters.shift ?? ALL_FILTER_VALUE}
                        onValueChange={(v) => applyFilters({ shift: v === ALL_FILTER_VALUE ? null : v })}
                    >
                        <SelectTrigger className="h-7 w-[72px] rounded-md border text-[11px]">
                            <SelectValue placeholder="Shift" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_FILTER_VALUE} className="text-xs">
                                All
                            </SelectItem>
                            {filterOptions.shifts.map((s) => (
                                <SelectItem key={s.value} value={s.value} className="text-xs">
                                    {s.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
                {sectionHasFilter('penalty_type') && (
                    <Select
                        value={filters.penalty_type != null ? String(filters.penalty_type) : ALL_FILTER_VALUE}
                        onValueChange={(v) => applyFilters({ penalty_type: v === ALL_FILTER_VALUE ? null : Number(v) })}
                    >
                        <SelectTrigger className="h-7 w-[130px] rounded-md border text-[11px]">
                            <SelectValue placeholder="Penalty type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_FILTER_VALUE} className="text-xs">
                                All types
                            </SelectItem>
                            {(filterOptions.penaltyTypes ?? []).map((pt) => (
                                <SelectItem key={pt.value} value={pt.value} className="text-xs">
                                    {pt.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
                    <div className="relative">
                        <button
                            type="button"
                            onClick={() => {
                                setPendingSidingIds(isAllSidingsSelected ? allSidingIds : filters.siding_ids);
                                setShowSidingDropdown(!showSidingDropdown);
                            }}
                            className="flex h-7 min-w-0 items-center gap-1.5 rounded-md border bg-background px-2.5 text-[11px] font-medium transition-colors hover:bg-muted"
                        >
                            <span className="max-w-24 truncate">{selectedSidingNames}</span>
                            <ChevronDown className="size-3 shrink-0 opacity-50" />
                            {!isAllSidingsSelected && (
                                <button
                                    type="button"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        resetSidingFilter();
                                    }}
                                    className="rounded p-0.5 hover:bg-muted-foreground/20"
                                >
                                    <X className="size-3" />
                                </button>
                            )}
                        </button>
                        {showSidingDropdown && (
                            <>
                                <div className="fixed inset-0 z-40" onClick={() => setShowSidingDropdown(false)} />
                                <div className="absolute right-0 top-full z-50 mt-1 w-52 rounded-lg border bg-card p-2 shadow-lg">
                                    <button
                                        type="button"
                                        onClick={() => setPendingSidingIds(allSidingIds)}
                                        className={
                                            'flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-[11px] transition-colors hover:bg-muted ' +
                                            (isAllPendingSelected ? 'font-semibold text-primary' : 'text-muted-foreground')
                                        }
                                    >
                                        <div className={
                                            'flex size-3.5 items-center justify-center rounded border ' +
                                            (isAllPendingSelected ? 'border-primary bg-primary' : 'border-muted-foreground/30')
                                        }>
                                            {isAllPendingSelected && <span className="text-[8px] text-primary-foreground">✓</span>}
                                        </div>
                                        All sidings
                                    </button>
                                    {sidings.map((s) => {
                                        const isSelected = isAllPendingSelected || pendingSidingIds.includes(s.id);
                                        return (
                                            <button
                                                key={s.id}
                                                type="button"
                                                onClick={() => togglePendingSiding(s.id)}
                                                className="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-[11px] transition-colors hover:bg-muted"
                                            >
                                                <div className={
                                                    'flex size-3.5 items-center justify-center rounded border ' +
                                                    (isSelected ? 'border-primary bg-primary' : 'border-muted-foreground/30')
                                                }>
                                                    {isSelected && <span className="text-[8px] text-primary-foreground">✓</span>}
                                                </div>
                                                <span className="truncate">{s.name}</span>
                                                <span className="ml-auto shrink-0 text-muted-foreground">{s.code}</span>
                                            </button>
                                        );
                                    })}
                                    <div className="mt-1.5 border-t pt-1.5">
                                        <Button
                                            size="sm"
                                            className="h-7 w-full text-[11px]"
                                            disabled={!hasPendingSidingChanges}
                                            onClick={applySidingFilter}
                                        >
                                            Apply
                                        </Button>
                                    </div>
                                </div>
                            </>
                        )}
                    </div>
            {inline && (
                <Button
                    size="sm"
                    className="h-7 shrink-0 rounded-md text-[11px]"
                    onClick={() => {
                        applyFilters();
                        onClose?.();
                    }}
                >
                    Apply
                </Button>
            )}
                </div>
            </div>
    );

    if (inline) {
        return content;
    }
    return (
        <div className="dashboard-card rounded-xl border-0 p-3">
            {content}
            {filters.period !== 'custom' && (
                <p className="mt-1.5 text-[11px] text-muted-foreground">
                    Showing data from {filters.from} to {filters.to}
                </p>
            )}
        </div>
    );
}

export default function Dashboard() {
    const props = usePage<DashboardProps>().props;
    const userId = props.auth?.user?.id as number | undefined;

    const canWidget = useCallback(
        (name: string) => {
            if (props.auth?.can_bypass) {
                return true;
            }
            const allowed = props.allowedDashboardWidgets;
            if (allowed === undefined) {
                return true;
            }

            return new Set(allowed).has(name);
        },
        [props.auth?.can_bypass, props.allowedDashboardWidgets],
    );

    const visibleSections = useMemo(
        () => DASHBOARD_SECTIONS.filter((s) => dashboardSectionVisible(s.id, canWidget)),
        [canWidget],
    );

    const executiveYesterdayTableAllowed = useMemo(
        () => EXEC_TABLE_WIDGETS.some((n) => canWidget(n)),
        [canWidget],
    );
    const executiveYesterdayChartsAllowed = useMemo(
        () => EXEC_CHART_WIDGETS.some((n) => canWidget(n)),
        [canWidget],
    );
    const showExecutiveYesterdayViewToggle = executiveYesterdayTableAllowed && executiveYesterdayChartsAllowed;

    const [activeSection, setActiveSection] = useState<string>(() => {
        const allowedList = props.allowedDashboardWidgets;
        const can = (name: string) =>
            props.auth?.can_bypass === true ||
            allowedList === undefined ||
            new Set(allowedList).has(name);
        const fromUrl = props.section ?? DEFAULT_DASHBOARD_SECTION;
        const firstPermitted = DASHBOARD_SECTIONS.find((s) => dashboardSectionVisible(s.id, can))?.id;
        const fromUrlOk =
            DASHBOARD_SECTIONS.some((sec) => sec.id === fromUrl) &&
            dashboardSectionVisible(fromUrl as (typeof DASHBOARD_SECTIONS)[number]['id'], can);
        if (fromUrlOk) {
            return fromUrl;
        }

        return firstPermitted ?? DEFAULT_DASHBOARD_SECTION;
    });
    const [executiveYesterdayViewMode, setExecutiveYesterdayViewMode] = useState<'table' | 'charts'>(() => {
        const allowedList = props.allowedDashboardWidgets;
        const can = (name: string) =>
            props.auth?.can_bypass === true ||
            allowedList === undefined ||
            new Set(allowedList).has(name);
        const table = EXEC_TABLE_WIDGETS.some((n) => can(n));
        const charts = EXEC_CHART_WIDGETS.some((n) => can(n));
        if (table && !charts) {
            return 'table';
        }
        if (!table && charts) {
            return 'charts';
        }

        return 'charts';
    });
    const [sidingOverviewPenaltyPeriod, setSidingOverviewPenaltyPeriod] = useState<ExecutiveChartPeriodKey>('yesterday');
    const [alertsOpen, setAlertsOpen] = useState(false);
    const [notifications, setNotifications] = useState(props.notifications ?? []);
    const [notificationsUnreadCount, setNotificationsUnreadCount] = useState(props.notificationsUnreadCount ?? 0);
    const [filtersExpanded, setFiltersExpanded] = useState(false);
    const sidings = props.sidings ?? [];
    const allSidingIds = useMemo(() => sidings.map((s) => s.id), [sidings]);
    const [stockOverrides, setStockOverrides] = useState<Record<number, number>>({});
    useSidingStockBroadcast(allSidingIds, (sidingId, closingBalanceMt) => {
        setStockOverrides((prev) => ({ ...prev, [sidingId]: closingBalanceMt }));
    });

    useEffect(() => {
        if (!userId || typeof window === 'undefined' || !window.Echo) return;
        const channelName = `App.Models.User.${userId}`;
        const channel = window.Echo.private(channelName);

        channel.notification((notification: any) => {
            setNotificationsUnreadCount((c) => c + 1);
            setNotifications((prev) => [{ id: notification.id ?? crypto.randomUUID(), type: notification.type ?? 'notification', data: notification, read_at: null, created_at: new Date().toISOString() }, ...prev].slice(0, 20));
        });

        return () => {
            window.Echo?.leaveChannel(channelName);
        };
    }, [userId]);

    useEffect(() => {
        if (visibleSections.length === 0) {
            return;
        }
        if (!visibleSections.some((s) => s.id === activeSection)) {
            setActiveSection(visibleSections[0].id);
        }
    }, [visibleSections, activeSection]);

    useEffect(() => {
        if (executiveYesterdayTableAllowed && !executiveYesterdayChartsAllowed) {
            setExecutiveYesterdayViewMode('table');
        } else if (!executiveYesterdayTableAllowed && executiveYesterdayChartsAllowed) {
            setExecutiveYesterdayViewMode('charts');
        }
    }, [executiveYesterdayTableAllowed, executiveYesterdayChartsAllowed]);

    const csrfToken = useMemo(() => {
        if (typeof document === 'undefined') return null;
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? null;
    }, []);

    const markAllNotificationsRead = useCallback(async () => {
        if (!csrfToken) return;
        await fetch('/notifications/read-all', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({}),
        });
        setNotificationsUnreadCount(0);
        setNotifications((prev) => prev.map((n) => (n.read_at ? n : { ...n, read_at: new Date().toISOString() })));
    }, [csrfToken]);

    const markOneNotificationRead = useCallback(async (id: string) => {
        if (!csrfToken) return;
        await fetch(`/notifications/${encodeURIComponent(id)}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({}),
        });
        setNotificationsUnreadCount((c) => Math.max(0, c - 1));
        setNotifications((prev) => prev.map((n) => (n.id === id ? { ...n, read_at: n.read_at ?? new Date().toISOString() } : n)));
    }, [csrfToken]);
    const defaultFilters: DashboardFilters = {
        period: 'yesterday',
        from: '',
        to: '',
        siding_ids: [],
        power_plant: null,
        rake_number: null,
        loader_id: null,
        loader_operator: null,
        underload_threshold: 1,
        shift: null,
        penalty_type: null,
        rake_penalty_scope: 'all',
        daily_rake_date: undefined,
        coal_transport_date: undefined,
    };
    const filters: DashboardFilters = {
        ...defaultFilters,
        ...(props.filters ?? {}),
        power_plant: props.filters?.power_plant ?? null,
        rake_number: props.filters?.rake_number ?? null,
        loader_id: props.filters?.loader_id ?? null,
        loader_operator: props.filters?.loader_operator ?? null,
        underload_threshold:
            props.filters?.underload_threshold != null && !Number.isNaN(Number(props.filters.underload_threshold))
                ? Number(props.filters.underload_threshold)
                : 1,
        shift: props.filters?.shift ?? null,
        penalty_type: props.filters?.penalty_type ?? null,
        rake_penalty_scope:
            props.filters?.rake_penalty_scope === 'with_penalties'
                ? 'with_penalties'
                : 'all',
    };
    const periodLabel = useMemo(() => {
        switch (filters.period) {
            case 'yesterday':
                return 'yesterday';
            case 'today':
                return 'today';
            case 'week':
                return 'this week';
            case 'last_week':
                return 'last week';
            case 'month':
                return 'this month';
            case 'last_month':
                return 'last month';
            case 'custom':
                return 'selected period';
            default:
                return 'yesterday';
        }
    }, [filters.period, filters.from, filters.to]);

    const formatDate = useCallback((value: string | undefined | null) => {
        if (!value) {
            return '';
        }
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) {
            return value;
        }
        return d.toLocaleDateString(undefined, {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    }, []);

    const mainDateRangeLabel = useMemo(() => {
        const from = formatDate(filters.from);
        const to = formatDate(filters.to);
        if (!from || !to) {
            return '';
        }
        if (from === to) {
            return `Data for ${from}`;
        }
        return `Data from ${from} to ${to}`;
    }, [filters.from, filters.to, formatDate]);

    // When period is not custom, URL must not contain from/to or backend can receive stale range from shared links or history.
    const dashboardPath = useMemo(() => dashboard().url.split('?')[0] || dashboard().url, []);
    useEffect(() => {
        if (sidings.length === 0 || filters.period === 'custom') return;
        const search = typeof window !== 'undefined' ? window.location.search : '';
        if (!search || (!search.includes('from=') && !search.includes('to='))) return;
        const params: Record<string, string | number | number[] | null> = {
            period: filters.period,
        };
        if (filters.siding_ids.length > 0 && filters.siding_ids.length < sidings.length) {
            params.siding_ids = filters.siding_ids;
        }
        if (filters.power_plant) params.power_plant = filters.power_plant;
        if (filters.rake_number) params.rake_number = filters.rake_number;
        if (filters.loader_id) params.loader_id = filters.loader_id;
        if (filters.loader_operator) params.loader_operator = filters.loader_operator;
        if (filters.shift) params.shift = filters.shift;
        if (filters.penalty_type != null) params.penalty_type = filters.penalty_type;
        if (filters.rake_penalty_scope === 'with_penalties') {
            params.rake_penalty_scope = 'with_penalties';
        }
        router.get(dashboardPath, params as Record<string, string>, { replace: true, preserveState: false });
    }, [
        dashboardPath,
        filters.period,
        filters.siding_ids,
        filters.power_plant,
        filters.rake_number,
        filters.loader_id,
        filters.loader_operator,
        filters.shift,
        filters.penalty_type,
        filters.rake_penalty_scope,
        sidings.length,
    ]);

    const applyDailyRakeDate = useCallback((date: string) => {
        const params: Record<string, unknown> = {
            period: filters.period,
            section: activeSection,
        };
        if (date !== '') params.daily_rake_date = date;
        if (filters.siding_ids.length > 0 && filters.siding_ids.length < allSidingIds.length) {
            params.siding_ids = filters.siding_ids;
        }
        if (filters.power_plant) params.power_plant = filters.power_plant;
        if (filters.rake_number) params.rake_number = filters.rake_number;
        if (filters.loader_id) params.loader_id = filters.loader_id;
        if (filters.loader_operator) params.loader_operator = filters.loader_operator;
        if (filters.shift) params.shift = filters.shift;
        if (filters.penalty_type != null) params.penalty_type = filters.penalty_type;
        if (filters.rake_penalty_scope === 'with_penalties') {
            params.rake_penalty_scope = 'with_penalties';
        }
        router.get(dashboardPath, params as Record<string, string>, { preserveState: true, preserveScroll: true });
    }, [dashboardPath, filters, activeSection, allSidingIds.length]);

    const applyCoalTransportDate = useCallback((date: string) => {
        const params: Record<string, unknown> = {
            period: filters.period,
            section: activeSection,
        };
        if (date !== '') params.coal_transport_date = date;
        if (filters.siding_ids.length > 0 && filters.siding_ids.length < allSidingIds.length) {
            params.siding_ids = filters.siding_ids;
        }
        if (filters.power_plant) params.power_plant = filters.power_plant;
        if (filters.rake_number) params.rake_number = filters.rake_number;
        if (filters.loader_id) params.loader_id = filters.loader_id;
        if (filters.loader_operator) params.loader_operator = filters.loader_operator;
        if (filters.shift) params.shift = filters.shift;
        if (filters.penalty_type != null) params.penalty_type = filters.penalty_type;
        if (filters.rake_penalty_scope === 'with_penalties') {
            params.rake_penalty_scope = 'with_penalties';
        }
        if (filters.daily_rake_date) params.daily_rake_date = filters.daily_rake_date;
        router.get(dashboardPath, params as Record<string, string>, { preserveState: true, preserveScroll: true });
    }, [dashboardPath, filters, activeSection, allSidingIds.length]);

    const filterOptions = props.filterOptions ?? { powerPlants: [], loaders: [], shifts: [], penaltyTypes: [], loaderOperatorsByLoader: {} };
    const kpis = props.kpis;
    const penaltyTrendDaily = props.penaltyTrendDaily ?? [];
    const penaltyByType = props.penaltyByType ?? [];
    const penaltyBySiding = props.penaltyBySiding ?? [];
    const alerts = props.alerts ?? [];
    const liveRakeStatus = props.liveRakeStatus ?? [];
    const dailyRakeDetails = props.dailyRakeDetails;
    const coalTransportReport = props.coalTransportReport;
    const truckReceiptTrend = props.truckReceiptTrend ?? [];
    const shiftWiseVehicleReceipt = props.shiftWiseVehicleReceipt ?? [];
    const stockGauge = props.stockGauge;
    const predictedVsActualPenalty = props.predictedVsActualPenalty ?? { predicted: 0, actual: 0, bySiding: [] };
    const baseSidingStocks = props.sidingStocks ?? {};
    const penaltySummary     = props.penaltySummary;
    const activeRakePipeline = props.activeRakePipeline;
    const riskScores         = props.riskScores ?? {};
    const alertsData         = props.alerts ?? {};
    const operatorRake       = props.operatorRake ?? null;
    const penaltyPredictions = props.penaltyPredictions ?? [];
    const sidingStocksMap    = props.sidingStocks ?? {};
    const allowedWidgets     = props.allowedDashboardWidgets ?? [];
    const isExecutive        = allowedWidgets.some((w) =>
        ['penalty_exposure_command', 'rake_pipeline_command', 'siding_risk_score'].includes(w),
    );
    const sidingStocks = useMemo(() => {
        if (Object.keys(stockOverrides).length === 0) return baseSidingStocks;
        const merged: Record<number, SidingStock> = {};
        for (const [idStr, st] of Object.entries(baseSidingStocks)) {
            const id = Number(idStr);
            merged[id] = {
                ...st,
                closing_balance_mt: stockOverrides[id] ?? st.closing_balance_mt,
            };
        }
        for (const id of Object.keys(stockOverrides).map(Number)) {
            if (!(id in merged)) {
                merged[id] = {
                    siding_id: id,
                    opening_balance_mt: 0,
                    closing_balance_mt: stockOverrides[id],
                    total_rakes: 0,
                    received_mt: 0,
                    dispatched_mt: 0,
                    last_receipt_at: null,
                    last_dispatch_at: null,
                };
            }
        }
        return merged;
    }, [baseSidingStocks, stockOverrides]);
    const sidingPerformance = props.sidingPerformance ?? [];
    const sidingWiseMonthly = props.sidingWiseMonthly ?? [];
    const sidingRadar = props.sidingRadar ?? { sidings: [] };
    const dateWiseDispatch = props.dateWiseDispatch ?? { sidingNames: {}, dates: [] };
    const loaderOverloadFilterKey = useMemo(
        () =>
            JSON.stringify({
                period: filters.period,
                from: filters.from,
                to: filters.to,
                siding_ids: filters.siding_ids,
                power_plant: filters.power_plant,
                rake_number: filters.rake_number,
                loader_id: filters.loader_id,
                loader_operator: filters.loader_operator,
                underload_threshold: filters.underload_threshold,
                shift: filters.shift,
                penalty_type: filters.penalty_type,
                rake_penalty_scope: filters.rake_penalty_scope,
            }),
        [
            filters.period,
            filters.from,
            filters.to,
            filters.siding_ids,
            filters.power_plant,
            filters.rake_number,
            filters.loader_id,
            filters.loader_operator,
            filters.underload_threshold,
            filters.shift,
            filters.penalty_type,
            filters.rake_penalty_scope,
        ],
    );

    const buildLoaderOverloadApiParams = useCallback(
        (args: { page?: number; perPage?: number }) =>
            buildLoaderOverloadApiSearchParams({
                filters,
                allSidingIds,
                page: args.page,
                perPage: args.perPage,
            }),
        [filters, allSidingIds],
    );

    const navigateDashboard = useCallback(
        (overrides: Record<string, unknown> = {}) => {
            const params = buildDashboardGetParams({
                overrides,
                filters,
                currentSection: activeSection,
                allSidingIds,
                resolvedPeriod: (overrides.period as string | undefined) ?? filters.period,
                resolvedFrom: (overrides.from as string | undefined) ?? filters.from,
                resolvedTo: (overrides.to as string | undefined) ?? filters.to,
            });
            const dashboardPath = dashboard().url.split('?')[0] || dashboard().url;
            router.get(dashboardPath, params as Record<string, string>, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        [filters, activeSection, allSidingIds],
    );

    const onRakePenaltyScopeChange = useCallback(
        (scope: 'all' | 'with_penalties') => {
            navigateDashboard({ rake_penalty_scope: scope });
        },
        [navigateDashboard],
    );

    const powerPlantDispatch = props.powerPlantDispatch ?? [];
    const yesterdayPredictedPenalties = props.yesterdayPredictedPenalties ?? [];
    const executiveYesterday = props.executiveYesterday;

    const penaltyBySidingForSidingOverview = useMemo(() => {
        const slices = executiveYesterday?.penaltyBySidingByPeriod;
        if (slices) {
            return slices[sidingOverviewPenaltyPeriod] ?? [];
        }

        return penaltyBySiding;
    }, [executiveYesterday?.penaltyBySidingByPeriod, penaltyBySiding, sidingOverviewPenaltyPeriod]);

    const filteredSidings = useMemo(() => {
        if (filters.siding_ids.length === 0 || filters.siding_ids.length === sidings.length) {
            return sidings;
        }
        const idSet = new Set(filters.siding_ids);
        return sidings.filter((s) => idSet.has(s.id));
    }, [sidings, filters.siding_ids]);

    const hasActiveFilters = useMemo(() => {
        if (filters.period !== 'yesterday') return true;
        if (filters.power_plant) return true;
        if (filters.rake_number?.trim()) return true;
        if (filters.loader_id != null) return true;
        if (filters.loader_operator) return true;
        if (filters.shift) return true;
        if (filters.penalty_type != null) return true;
        if (filters.rake_penalty_scope === 'with_penalties') return true;
        if (sidings.length > 0 && filters.siding_ids.length > 0 && filters.siding_ids.length < sidings.length) return true;
        return false;
    }, [filters.period, filters.power_plant, filters.rake_number, filters.loader_id, filters.loader_operator, filters.shift, filters.penalty_type, filters.rake_penalty_scope, filters.siding_ids.length, sidings.length]);

    const activeFilterCount = useMemo(() => {
        let n = 0;
        if (filters.period !== 'yesterday') n += 1;
        if (filters.power_plant) n += 1;
        if (filters.rake_number?.trim()) n += 1;
        if (filters.loader_id != null) n += 1;
        if (filters.loader_operator) n += 1;
        if (filters.shift) n += 1;
        if (filters.penalty_type != null) n += 1;
        if (filters.rake_penalty_scope === 'with_penalties') n += 1;
        if (sidings.length > 0 && filters.siding_ids.length > 0 && filters.siding_ids.length < sidings.length) n += 1;
        return n;
    }, [filters.period, filters.power_plant, filters.rake_number, filters.loader_id, filters.loader_operator, filters.shift, filters.penalty_type, filters.rake_penalty_scope, filters.siding_ids.length, sidings.length]);

    const navigateToLoaderTrends = useCallback(
        (loaderId: number, rakeUnderloadThresholdPercent?: number) => {
            const dashboardPath = dashboard().url.split('?')[0] || dashboard().url;
            // New tab URL is built from scratch; omit loader_operator so drill-down is not scoped to a prior operator filter.
            const params: Record<string, unknown> = {
                period: filters.period,
                section: 'loader-overload',
                loader_id: loaderId,
            };
            if (filters.period === 'custom') {
                params.from = filters.from;
                params.to = filters.to;
            }
            if (filters.siding_ids.length > 0 && filters.siding_ids.length < sidings.length) {
                params.siding_ids = filters.siding_ids.join(',');
            }
            if (filters.power_plant) {
                params.power_plant = filters.power_plant;
            }
            if (filters.rake_number) {
                params.rake_number = filters.rake_number;
            }
            if (filters.shift) {
                params.shift = filters.shift;
            }
            if (filters.penalty_type != null) {
                params.penalty_type = filters.penalty_type;
            }
            if (filters.rake_penalty_scope === 'with_penalties') {
                params.rake_penalty_scope = filters.rake_penalty_scope;
            }
            if (filters.daily_rake_date) {
                params.daily_rake_date = filters.daily_rake_date;
            }
            if (filters.coal_transport_date) {
                params.coal_transport_date = filters.coal_transport_date;
            }
            const effectiveUnderloadThreshold =
                rakeUnderloadThresholdPercent !== undefined
                    ? Math.max(0, Math.min(100, rakeUnderloadThresholdPercent))
                    : filters.underload_threshold;
            if (effectiveUnderloadThreshold != null && !Number.isNaN(effectiveUnderloadThreshold) && effectiveUnderloadThreshold !== 1) {
                params.underload_threshold = effectiveUnderloadThreshold;
            }
            if (typeof window === 'undefined') {
                return;
            }
            const url = new URL(dashboardPath, window.location.origin);
            for (const [key, value] of Object.entries(params)) {
                if (value === undefined || value === null || value === '') {
                    continue;
                }
                url.searchParams.set(key, String(value));
            }
            window.open(url.toString(), '_blank', 'noopener,noreferrer');
        },
        [filters, sidings.length],
    );

    const sidingStackKeys = useMemo(() => filteredSidings.map((s) => s.name), [filteredSidings]);

    const kpiCards =
        sidings.length > 0 && kpis && canWidget('dashboard.widgets.global_kpi_sidebar')
            ? [
                  { label: `Rakes dispatched ${periodLabel}`, value: String(kpis.rakesDispatchedToday), borderColor: '#3B82F6', Icon: Train },
                  { label: `Coal dispatched ${periodLabel}`, value: formatWeight(kpis.coalDispatchedToday), borderColor: '#10B981', Icon: Flame },
                  { label: `Penalty ${periodLabel}`, value: formatCurrency(kpis.totalPenaltyThisMonth), borderColor: '#EF4444', Icon: AlertTriangle },
                  // Temporarily hidden per client request:
                  // { label: 'Predicted penalty risk', value: formatCurrency(kpis.predictedPenaltyRisk), borderColor: '#F59E0B', Icon: TrendingUp },
                  // { label: `Avg loading time (${periodLabel})`, value: kpis.avgLoadingTimeMinutes != null ? `${Math.floor(kpis.avgLoadingTimeMinutes / 60)}h ${kpis.avgLoadingTimeMinutes % 60}m` : '—', borderColor: '#8B5CF6', Icon: Clock },
                  // { label: `Trucks received ${periodLabel}`, value: String(kpis.trucksReceivedToday), borderColor: '#14B8A6', Icon: Truck },
              ]
            : [];

    const canExportCoalTransport = useMemo(() => {
        if (props.auth?.can_bypass) {
            return true;
        }
        if (props.auth?.permissions?.includes('sections.mines_dispatch_data.view')) {
            return true;
        }

        return canWidget('dashboard.widgets.operations_coal_transport');
    }, [props.auth?.can_bypass, props.auth?.permissions, canWidget]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            {/* ── Operations Command Center ── */}
            <section className="mb-6 flex flex-col gap-4 px-4 pt-4 lg:px-6">
                <h2
                    className="text-xs font-bold uppercase tracking-widest"
                    style={{ color: 'oklch(0.22 0.06 150)' }}
                >
                    Operations Command Center
                </h2>

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
                    </>
                )}
            </section>
            {/* ── End Command Center ── */}
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

                <div className="flex min-w-0 gap-3">
                    <div className="min-w-0 flex-1 space-y-6">
                {canWidget('dashboard.widgets.global_coal_stock_strip') && filteredSidings.length > 0 && (
                    <div className="space-y-1">
                        <div className="flex items-center justify-between">
                            <p className="text-[10px] text-gray-500">Coal stock updates live from the ledger (and real-time events when connected).</p>
                        </div>
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
                                                        format={(v) => `${v.toLocaleString(undefined, { maximumFractionDigits: 0 })} MT`}
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
                                        <div className="mt-2 space-y-0.5 border-t border-gray-100 pt-2 text-[10px]">
                                            <p className="text-green-700">
                                                <span className="font-bold">Last receipt: </span>
                                                <span className="tabular-nums">
                                                    {stock?.last_receipt_at
                                                        ? new Date(stock.last_receipt_at).toLocaleString()
                                                        : '—'}
                                                </span>
                                            </p>
                                            <p className="text-red-700">
                                                <span className="font-bold">Last dispatch: </span>
                                                <span className="tabular-nums">
                                                    {stock?.last_dispatch_at
                                                        ? new Date(stock.last_dispatch_at).toLocaleString()
                                                        : '—'}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {sidings.length === 0 ? (
                    <div className="dashboard-card rounded-xl border-0 p-8 text-center text-sm text-gray-600">
                        <p>No sidings assigned to your account. Contact your administrator to get access.</p>
                    </div>
                ) : (
                    <>
                    <div className="min-w-0 space-y-6">
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

                        {activeSection === 'siding-overview' && (
                            <div className="space-y-6">
                                {canWidget('dashboard.widgets.siding_overview_performance') ? (
                                    sidingPerformance.length > 0 ? (
                                        <SidingPerformanceSection data={sidingPerformance} />
                                    ) : (
                                        <div className="dashboard-card rounded-xl border-0 p-6">
                                            <SectionHeader icon={BarChart3} title="Siding performance" subtitle="Rakes, coal & penalty by siding" />
                                            <div className="mt-4 py-8 text-center text-sm text-gray-600">No performance data for selected filters.</div>
                                        </div>
                                    )
                                ) : null}
                                {canWidget('dashboard.widgets.siding_overview_penalty_trend') ? (
                                <div className="dashboard-card rounded-xl border-0 p-6">
                                    <SectionHeader icon={Calendar} title="Penalty trend" subtitle="Date / month vs penalty amount" />
                                    {penaltyTrendDaily.length > 0 ? (
                                        <ResponsiveContainer width="100%" height={280}>
                                            <RechartsAreaChart data={penaltyTrendDaily} margin={{ top: 8, right: 16, left: 8, bottom: 0 }}>
                                                <defs>
                                                    <linearGradient id="penalty-trend-gradient" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="0%" stopColor="#DC2626" stopOpacity={0.4} />
                                                        <stop offset="100%" stopColor="#DC2626" stopOpacity={0} />
                                                    </linearGradient>
                                                </defs>
                                                <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                                <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                                                <YAxis tick={{ fontSize: 11 }} tickFormatter={(v) => formatCurrency(v)} />
                                                <Tooltip formatter={(v: number | string | undefined) => formatCurrency(Number(v ?? 0))} />
                                                <Area type="monotone" dataKey="total" name="Penalty" stroke="#DC2626" strokeWidth={2} fill="url(#penalty-trend-gradient)" dot={false} activeDot={{ r: 4 }} isAnimationActive />
                                            </RechartsAreaChart>
                                        </ResponsiveContainer>
                                    ) : (
                                        <div className="mt-4 py-8 text-center text-sm text-gray-600">No penalty data for selected period.</div>
                                    )}
                                </div>
                                ) : null}
                                {canWidget('dashboard.widgets.siding_overview_power_plant_distribution') ? (
                                <div className="dashboard-card rounded-xl border-0 p-6">
                                    <SectionHeader icon={Factory} title="Power plant dispatch distribution" subtitle="Coal supply by destination" />
                                    {powerPlantDispatch.length > 0 ? (() => {
                                        const totalWeight = powerPlantDispatch.reduce((s, p) => s + p.weight_mt, 0);
                                        const donutData = powerPlantDispatch.map((pp) => ({
                                            name: pp.name,
                                            value: Math.round((pp.weight_mt / Math.max(1, totalWeight)) * 100),
                                            weightMt: pp.weight_mt,
                                        }));
                                        return (
                                            <div className="relative">
                                                <ResponsiveContainer width="100%" height={280}>
                                                    <RechartsPieChart>
                                                        <Pie
                                                            data={donutData}
                                                            dataKey="value"
                                                            nameKey="name"
                                                            cx="50%"
                                                            cy="50%"
                                                            innerRadius="65%"
                                                            outerRadius="90%"
                                                            paddingAngle={2}
                                                            strokeWidth={0}
                                                        >
                                                            {donutData.map((_, i) => (
                                                                <Cell key={i} fill={['#22c55e', '#3b82f6', '#f97316', '#eab308', '#a855f7'][i % 5]} />
                                                            ))}
                                                        </Pie>
                                                        <Tooltip formatter={(v: number | undefined, _: unknown, props: { payload?: { weightMt: number } }) => [`${v ?? 0}%`, formatWeight(props.payload?.weightMt ?? 0)]} />
                                                        <Legend layout="horizontal" align="center" verticalAlign="bottom" wrapperStyle={{ paddingTop: 16 }} formatter={(value, entry) => `${value} ${Number((entry as { payload?: { value: number } }).payload?.value ?? 0)}%`} />
                                                    </RechartsPieChart>
                                                </ResponsiveContainer>
                                                <div className="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col items-center justify-center text-center">
                                                    <span className="text-xs font-medium text-gray-600">Total</span>
                                                    <span className="text-lg font-bold tabular-nums text-gray-800">{formatWeight(totalWeight)}</span>
                                                </div>
                                            </div>
                                        );
                                    })() : (
                                        <div className="mt-4 py-8 text-center text-sm text-gray-600">No power plant dispatch data.</div>
                                    )}
                                </div>
                                ) : null}
                            </div>
                        )}

                        {activeSection === 'operations' && (
                            <div className="space-y-6">
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    {canWidget('dashboard.widgets.operations_coal_transport') ? (
                                    <div className="dashboard-card min-w-0 rounded-xl border-0 p-5">
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <SectionHeader icon={Truck} title="Coal Transport Report" subtitle="Trips and quantity by shift and siding" titleClassName="font-bold text-black" />
                                            <div className="flex flex-wrap items-center gap-2">
                                                <label htmlFor="coal-transport-date" className="text-xs font-medium text-gray-600">Date</label>
                                                <input
                                                    id="coal-transport-date"
                                                    type="date"
                                                    value={filters.coal_transport_date ?? coalTransportReport?.date ?? ''}
                                                    onChange={(e) => {
                                                        const v = e.target.value;
                                                        applyCoalTransportDate(v ?? '');
                                                    }}
                                                    className="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm"
                                                />
                                                {(() => {
                                                    const coalDate =
                                                        filters.coal_transport_date ?? coalTransportReport?.date ?? '';
                                                    if (coalDate && canExportCoalTransport) {
                                                        return (
                                                            <Button variant="outline" size="sm" asChild>
                                                                <a
                                                                    href={`/exports/coal-transport-report?date=${encodeURIComponent(coalDate)}`}
                                                                    data-pan="dashboard-coal-transport-export-xlsx"
                                                                >
                                                                    <FileSpreadsheet className="mr-1.5 h-4 w-4" />
                                                                    Export XLSX
                                                                </a>
                                                            </Button>
                                                        );
                                                    }
                                                    return (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            disabled
                                                            title={
                                                                !coalDate
                                                                    ? 'Select a date'
                                                                    : 'You do not have permission to export this report'
                                                            }
                                                        >
                                                            <FileSpreadsheet className="mr-1.5 h-4 w-4" />
                                                            Export XLSX
                                                        </Button>
                                                    );
                                                })()}
                                            </div>
                                        </div>
                                        {coalTransportReport ? (
                                            <>
                                                <p className="mt-1 text-xs text-gray-600">
                                                    {new Date(coalTransportReport.date + 'T00:00:00').toLocaleDateString('en-IN', { day: '2-digit', month: '2-digit', year: 'numeric' })}
                                                </p>
                                                <div className="mt-3 overflow-hidden rounded-xl border border-[#d5dbe4] bg-white">
                                                    <div className="dashboard-table-scroll max-h-[420px] overflow-y-auto overflow-x-auto">
                                                        <table className="w-full border-separate text-xs" style={{ borderSpacing: 0 }}>
                                                            <thead className="sticky top-0 z-10 bg-[#eef2f7] text-black">
                                                                <tr>
                                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">Sl No</th>
                                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">Shift</th>
                                                                    {coalTransportReport.sidings.map((s) => (
                                                                        <th key={s.id} colSpan={2} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                                                            {s.name}
                                                                        </th>
                                                                    ))}
                                                                    <th colSpan={2} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                                                        Total
                                                                    </th>
                                                                </tr>
                                                                <tr>
                                                                    <th className="border border-[#d5dbe4] px-3 py-2" />
                                                                    <th className="border border-[#d5dbe4] px-3 py-2" />
                                                                    {coalTransportReport.sidings.flatMap((s) => [
                                                                        <th key={`${s.id}-t`} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                                                            Trips
                                                                        </th>,
                                                                        <th key={`${s.id}-q`} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                                                            Qty
                                                                        </th>,
                                                                    ])}
                                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">Trips</th>
                                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">Qty</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {coalTransportReport.rows.map((row) => (
                                                                    <tr key={row.shift_label} className="bg-white">
                                                                        <td className="border border-[#d5dbe4] px-3 py-2 tabular-nums">{row.sl_no}</td>
                                                                        <td className="border border-[#d5dbe4] px-3 py-2 font-medium">{row.shift_label}</td>
                                                                        {row.siding_metrics.map((m) => (
                                                                            <Fragment key={m.siding_name}>
                                                                                <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{m.trips}</td>
                                                                                <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                                                    {m.qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                                                </td>
                                                                            </Fragment>
                                                                        ))}
                                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{row.total_trips}</td>
                                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                                            {row.total_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                                        </td>
                                                                    </tr>
                                                                ))}
                                                                <tr className="bg-[#f1f5f9] font-semibold text-black">
                                                                    <td className="border border-[#d5dbe4] px-3 py-2" />
                                                                    <td className="border border-[#d5dbe4] px-3 py-2">TOTAL</td>
                                                                    {coalTransportReport.totals.siding_metrics.map((m) => (
                                                                        <Fragment key={m.siding_name}>
                                                                            <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{m.trips}</td>
                                                                            <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                                                {m.qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                                            </td>
                                                                        </Fragment>
                                                                    ))}
                                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{coalTransportReport.totals.total_trips}</td>
                                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                                        {coalTransportReport.totals.total_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </>
                                        ) : (
                                            <div className="mt-4 py-8 text-center text-sm text-gray-600">No coal transport data.</div>
                                        )}
                                    </div>
                                    ) : null}
                                    {canWidget('dashboard.widgets.operations_daily_rake_details') ? (
                                    <div className="dashboard-card rounded-xl border-0 p-5">
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <SectionHeader icon={Calendar} title="Daily rake details" subtitle="One day — filtered by selected sidings" titleClassName="font-bold text-black" />
                                            <div className="flex items-center gap-2">
                                                <label htmlFor="daily-rake-date" className="text-xs font-medium text-gray-600">Date</label>
                                                <input
                                                    id="daily-rake-date"
                                                    type="date"
                                                    value={filters.daily_rake_date ?? dailyRakeDetails?.date ?? ''}
                                                    onChange={(e) => {
                                                        const v = e.target.value;
                                                        applyDailyRakeDate(v ?? '');
                                                    }}
                                                    className="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm"
                                                />
                                            </div>
                                        </div>
                                        {dailyRakeDetails ? (
                                            <>
                                                <p className="mt-1 text-xs text-gray-600">
                                                    {new Date(dailyRakeDetails.date + 'T00:00:00').toLocaleDateString('en-IN', { day: '2-digit', month: '2-digit', year: 'numeric' })}
                                                    {dailyRakeDetails.rows.length > 0 && (
                                                        <span className="ml-2 text-gray-500">
                                                            ({dailyRakeDetails.rows[0].year})
                                                        </span>
                                                    )}
                                                    {dailyRakeDetails.totals.day_rakes === 0 && dailyRakeDetails.rows.length > 0 && (
                                                        <span className="ml-2 text-amber-600">— No rake dispatches for this date</span>
                                                    )}
                                                </p>
                                                <div className="mt-3 overflow-hidden rounded-xl border border-[#d5dbe4] bg-white">
                                                    <div className="dashboard-table-scroll max-h-[420px] overflow-y-auto overflow-x-auto">
                                                        <table className="w-full border-separate text-xs" style={{ borderSpacing: 0 }}>
                                                            <thead className="sticky top-0 z-10 bg-[#eef2f7] text-black">
                                                                <tr>
                                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">SL NO</th>
                                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">SIDING</th>
                                                                    <th colSpan={2} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                                                        DAY
                                                                    </th>
                                                                    <th colSpan={2} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                                                        MONTH
                                                                    </th>
                                                                    <th colSpan={2} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                                                                        YEAR
                                                                    </th>
                                                                </tr>
                                                                <tr>
                                                                    <th className="border border-[#d5dbe4] px-3 py-2" />
                                                                    <th className="border border-[#d5dbe4] px-3 py-2" />
                                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">RAKES</th>
                                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">QTY</th>
                                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">RAKES</th>
                                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">QTY</th>
                                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">RAKES</th>
                                                                    <th className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">QTY</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {dailyRakeDetails.rows.map((r) => (
                                                                    <tr key={r.siding_name} className="bg-white">
                                                                        <td className="border border-[#d5dbe4] px-3 py-2 tabular-nums">{r.sl_no}</td>
                                                                        <td className="border border-[#d5dbe4] px-3 py-2 font-medium">{r.siding_name}</td>
                                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{r.day_rakes}</td>
                                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                                            {r.day_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                                        </td>
                                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{r.month_rakes}</td>
                                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                                            {r.month_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                                        </td>
                                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{r.year_rakes}</td>
                                                                        <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                                            {r.year_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                                        </td>
                                                                    </tr>
                                                                ))}
                                                                <tr className="bg-[#f1f5f9] font-semibold text-black">
                                                                    <td className="border border-[#d5dbe4] px-3 py-2" />
                                                                    <td className="border border-[#d5dbe4] px-3 py-2">TOTAL</td>
                                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{dailyRakeDetails.totals.day_rakes}</td>
                                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                                        {dailyRakeDetails.totals.day_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                                    </td>
                                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{dailyRakeDetails.totals.month_rakes}</td>
                                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                                        {dailyRakeDetails.totals.month_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                                    </td>
                                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{dailyRakeDetails.totals.year_rakes}</td>
                                                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                                                        {dailyRakeDetails.totals.year_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </>
                                        ) : (
                                            <div className="mt-4 py-8 text-center text-sm text-gray-600">No siding selected or no data for this date.</div>
                                        )}
                                    </div>
                                    ) : null}
                                </div>
                            </div>
                        )}

                        {activeSection === 'operations' && (
                            <div className="space-y-6">
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    {canWidget('dashboard.widgets.operations_truck_receipt_trend') ? (
                                    <div className="dashboard-card rounded-xl border-0 p-5">
                                        <SectionHeader icon={BarChart3} title="Truck receipt trend" subtitle="Vehicles arrived per hour (today)" />
                                        {truckReceiptTrend.length > 0 ? (
                                            <ResponsiveContainer width="100%" height={260}>
                                                <RechartsBarChart data={truckReceiptTrend} margin={{ top: 8, right: 8, left: 8, bottom: 0 }}>
                                                    <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                                    <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                                    <Tooltip formatter={(v: number | undefined) => `Vehicles: ${v ?? 0}`} />
                                                    <Bar dataKey="count" fill="#3B82F6" radius={[4, 4, 0, 0]} barSize={24} isAnimationActive />
                                                </RechartsBarChart>
                                            </ResponsiveContainer>
                                        ) : (
                                            <div className="mt-4 py-8 text-center text-sm text-gray-600">No vehicle arrivals for today.</div>
                                        )}
                                    </div>
                                    ) : null}
                                    {canWidget('dashboard.widgets.operations_shift_vehicle_receipt') ? (
                                    <div className="dashboard-card rounded-xl border-0 p-5">
                                        <SectionHeader icon={BarChart3} title="Shift-wise vehicle receipt" subtitle="Vehicles received by shift and siding (today)" />
                                        {shiftWiseVehicleReceipt.length > 0 ? (() => {
                                            const sidingKeys = Object.keys(shiftWiseVehicleReceipt[0]).filter((k) => k !== 'shift_label');
                                            return (
                                                <ResponsiveContainer width="100%" height={260}>
                                                    <RechartsBarChart data={shiftWiseVehicleReceipt} margin={{ top: 8, right: 8, left: 8, bottom: 0 }}>
                                                        <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                                        <XAxis dataKey="shift_label" tick={{ fontSize: 11 }} />
                                                        <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                                        <Tooltip formatter={(v: number | undefined) => `Vehicles: ${v ?? 0}`} />
                                                        <Legend />
                                                        {sidingKeys.map((sidingName, i) => (
                                                            <Bar
                                                                key={sidingName}
                                                                dataKey={sidingName}
                                                                name={sidingName}
                                                                fill={SIDING_ACCENT[sidingName] ?? ['#3B82F6', '#10B981', '#F59E0B'][i % 3]}
                                                                radius={[4, 4, 0, 0]}
                                                                barSize={24}
                                                                isAnimationActive
                                                            />
                                                        ))}
                                                    </RechartsBarChart>
                                                </ResponsiveContainer>
                                            );
                                        })() : (
                                            <div className="mt-4 py-8 text-center text-sm text-gray-600">No shift-wise vehicle data for today.</div>
                                        )}
                                    </div>
                                    ) : null}
                                </div>
                                {/* Stock vs requirement — hidden for now
                                <SpeedometerGauge
                                    sidings={
                                        stockGauge?.map((item) => ({
                                            name: item.siding_name,
                                            current: item.stock_available_mt,
                                            required: item.rake_required_mt,
                                        })) ?? []
                                    }
                                    title="Stock vs requirement"
                                    subtitle="Minimum 3,800 MT per rake — side-wise"
                                />
                                */}
                                {canWidget('dashboard.widgets.operations_live_rake_status') ? (
                                 <div className="dashboard-card rounded-xl border-0 p-5">
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <SectionHeader icon={Train} title="Live rake status" subtitle="Pending on siding — no weighment receipt yet" />
                                        </div>
                                        <p className="mt-1 text-xs text-gray-600">
                                            Last updated: Just now
                                            {liveRakeStatus.length > 0 && (
                                                <span className="ml-2 font-medium text-gray-600">
                                                    • {liveRakeStatus.length} active rake{liveRakeStatus.length === 1 ? '' : 's'}
                                                </span>
                                            )}
                                        </p>
                                        {liveRakeStatus.length > 0 ? (
                                            <div className="dashboard-table-scroll mt-4 max-h-[520px] overflow-y-auto overflow-x-auto">
                                                <table className="w-full text-sm">
                                                    <thead className="sticky top-0 z-10 bg-white shadow-sm">
                                                        <tr className="border-b text-left text-gray-600">
                                                            <th className="group cursor-pointer pb-3 pl-4 pr-2 pt-2 font-medium">
                                                                <span className="inline-flex items-center gap-1">
                                                                    Rake Seq
                                                                    <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" />
                                                                </span>
                                                            </th>
                                                            <th className="group cursor-pointer pb-3 px-2 pt-2 font-medium">
                                                                <span className="inline-flex items-center gap-1">
                                                                    Rake number
                                                                    <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" />
                                                                </span>
                                                            </th>
                                                            <th className="group cursor-pointer pb-3 px-2 pt-2 font-medium">
                                                                <span className="inline-flex items-center gap-1">
                                                                    Siding
                                                                    <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" />
                                                                </span>
                                                            </th>
                                                            <th className="group cursor-pointer pb-3 px-2 pt-2 font-medium">
                                                                <span className="inline-flex items-center gap-1">
                                                                    Progress
                                                                    <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" />
                                                                </span>
                                                            </th>
                                                            <th className="group cursor-pointer pb-3 px-2 pt-2 font-medium">
                                                                <span className="inline-flex items-center gap-1">
                                                                    Loading date
                                                                    <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" />
                                                                </span>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {liveRakeStatus.map((row, i) => {
                                                            const riskVariant =
                                                                row.risk === 'penalty_risk'
                                                                    ? 'high'
                                                                    : row.risk === 'attention'
                                                                        ? 'medium'
                                                                        : 'normal';
                                                            const borderColor =
                                                                riskVariant === 'high'
                                                                    ? '#DC2626'
                                                                    : riskVariant === 'medium'
                                                                        ? '#F59E0B'
                                                                        : '#E5E7EB';

                                                            return (
                                                                <tr
                                                                    key={i}
                                                                    className="border-b text-[0.875rem] last:border-0"
                                                                    style={{
                                                                        backgroundColor: i % 2 === 1 ? '#F9FAFB' : undefined,
                                                                        borderLeft: `3px solid ${borderColor}`,
                                                                    }}
                                                                >
                                                                    <td className="py-3 pl-4 font-medium">
                                                                        <span>
                                                                            {formatRakeSequenceBySiding(
                                                                                row.rake_number,
                                                                                row.siding_name,
                                                                            )}
                                                                        </span>
                                                                    </td>
                                                                    <td className="py-3 px-2 font-medium">
                                                                        <span className="inline-flex items-center gap-2">
                                                                            <span
                                                                                className="inline-block size-2.5 rounded-full"
                                                                                style={{ backgroundColor: borderColor }}
                                                                            />
                                                                            <span>
                                                                                {row.rake_serial_number ? (
                                                                                    row.rake_serial_number
                                                                                ) : (
                                                                                    <span className="text-amber-600 dark:text-amber-400">
                                                                                        {row.rake_number}
                                                                                    </span>
                                                                                )}
                                                                            </span>
                                                                        </span>
                                                                    </td>
                                                                    <td className="py-3 px-2">{row.siding_name}</td>
                                                                    <td className="py-3 px-2">
                                                                        <RakeWorkflowProgressCell
                                                                            steps={
                                                                                row.workflow_steps ??
                                                                                DEFAULT_LIVE_RAKE_WORKFLOW_STEPS
                                                                            }
                                                                        />
                                                                    </td>
                                                                    <td className="py-3 tabular-nums px-2">
                                                                        {row.loading_date != null &&
                                                                        row.loading_date !== '' &&
                                                                        row.loading_date !== '—'
                                                                            ? new Date(`${row.loading_date}T12:00:00`).toLocaleDateString('en-IN', {
                                                                                  day: '2-digit',
                                                                                  month: '2-digit',
                                                                                  year: 'numeric',
                                                                              })
                                                                            : (row.loading_date ?? '—')}
                                                                    </td>
                                                                </tr>
                                                            );
                                                        })}
                                                    </tbody>
                                                </table>
                                            </div>
                                        ) : (
                                            <div className="mt-4 py-8 text-center text-sm text-gray-600">No active rakes.</div>
                                        )}
                                    </div>
                                ) : null}
                            </div>
                        )}

                        {activeSection === 'penalty-control' && (
                            <div className="space-y-6">
                                {/* Penalty type distribution (left) + Yesterday predicted penalties by siding (right) */}
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    {canWidget('dashboard.widgets.penalty_control_type_distribution') ? (
                                    <div className="dashboard-card min-w-0 rounded-xl border-0 p-6">
                                        <SectionHeader icon={AlertTriangle} title="Penalty type distribution" subtitle="Overloading, demurrage, wharfage, etc." />
                                        {penaltyByType.length > 0 ? (() => {
                                            const typeColors: Record<string, string> = { Demurrage: '#DC2626', Overloading: '#F59E0B', Wharfage: '#8B5CF6' };
                                            const totalType = penaltyByType.reduce((s, p) => s + p.value, 0);
                                            const donutData = penaltyByType.map((p) => ({ ...p, pct: totalType > 0 ? ((p.value / totalType) * 100).toFixed(1) : '0' }));
                                            const DonutTooltipContent = ({ active, payload }: { active?: boolean; payload?: Array<{ name: string; value: number; payload?: { pct: string } }> }) => {
                                                if (!active || !payload?.length) return null;
                                                const p = payload[0];
                                                return (
                                                    <div className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm shadow-lg">
                                                        <span className="font-medium text-gray-800">{p.name}</span>
                                                        <span className="ml-2 tabular-nums text-gray-600">{formatCurrency(p.value)} ({p.payload?.pct ?? '0'}%)</span>
                                                    </div>
                                                );
                                            };
                                            return (
                                                <div className="mt-4 flex flex-col gap-4">
                                                    <div className="relative flex justify-center">
                                                        <ResponsiveContainer width="100%" height={260}>
                                                            <RechartsPieChart>
                                                                <Pie
                                                                    data={donutData}
                                                                    dataKey="value"
                                                                    nameKey="name"
                                                                    cx="50%"
                                                                    cy="50%"
                                                                    innerRadius={60}
                                                                    outerRadius={90}
                                                                    paddingAngle={2}
                                                                    strokeWidth={0}
                                                                    activeShape={{ scale: 1.05 }}
                                                                >
                                                                    {donutData.map((entry, i) => (
                                                                        <Cell key={i} fill={typeColors[entry.name] ?? ['#64748b', '#94a3b8'][i % 2]} />
                                                                    ))}
                                                                </Pie>
                                                                <Tooltip
                                                                    content={<DonutTooltipContent />}
                                                                    wrapperStyle={{ outline: 'none', transform: 'translate(90px, -50%)' }}
                                                                />
                                                            </RechartsPieChart>
                                                        </ResponsiveContainer>
                                                        <div className="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col items-center pointer-events-none">
                                                            <span className="text-xs text-gray-600">Total</span>
                                                            <span className="text-lg font-bold tabular-nums text-gray-800">{formatCurrency(totalType)}</span>
                                                        </div>
                                                    </div>
                                                    <div className="flex flex-wrap justify-center gap-x-6 gap-y-2">
                                                        {donutData.map((entry, i) => (
                                                            <div key={entry.name} className="flex items-center gap-2 text-sm">
                                                                <span className="size-2.5 rounded-full shrink-0" style={{ backgroundColor: typeColors[entry.name] ?? '#64748b' }} />
                                                                <span className="text-gray-700">{entry.name}</span>
                                                                <span className="tabular-nums font-medium text-gray-800">{formatCurrency(entry.value)} ({entry.pct}%)</span>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            );
                                        })() : (
                                            <div className="mt-4 py-8 text-center text-sm text-gray-600">No penalty type data.</div>
                                        )}
                                    </div>
                                    ) : null}
                                    {canWidget('dashboard.widgets.penalty_control_yesterday_predicted') ? (
                                    <div className="dashboard-card min-w-0 rounded-xl border-0 p-5" style={{ padding: '1rem' }}>
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <SectionHeader icon={Calendar} title="Yesterday predicted penalties" subtitle="Siding-wise total (all sidings listed; ₹0 when no data)" />
                                            <Button variant="outline" size="sm" className="rounded-lg" asChild>
                                                <Link href={rakesIndex().url} data-pan="dashboard-yesterday-penalties-view-all">View all rakes</Link>
                                            </Button>
                                        </div>
                                        {(() => {
                                            const seen = new Set<string>();
                                            const sidingOrder: string[] = [];
                                            for (const s of filteredSidings) {
                                                if (!seen.has(s.name)) {
                                                    seen.add(s.name);
                                                    sidingOrder.push(s.name);
                                                }
                                            }
                                            for (const block of yesterdayPredictedPenalties) {
                                                if (!seen.has(block.siding_name)) {
                                                    seen.add(block.siding_name);
                                                    sidingOrder.push(block.siding_name);
                                                }
                                            }

                                            const bySiding = new Map<string, { rakesWithPenalty: number; totalPenalty: number }>();
                                            for (const s of yesterdayPredictedPenalties) {
                                                const totalPenalty = s.rakes.reduce((sum, r) => sum + (r.total_penalty ?? 0), 0);
                                                bySiding.set(s.siding_name, { rakesWithPenalty: s.rakes.length, totalPenalty });
                                            }

                                            const rows = sidingOrder.map((name) => {
                                                const hit = bySiding.get(name);
                                                return {
                                                    siding_name: name,
                                                    rakes_with_penalty: hit?.rakesWithPenalty ?? 0,
                                                    total_penalty: hit?.totalPenalty ?? 0,
                                                };
                                            });

                                            const totalRakesWithPenalty = rows.reduce((sum, r) => sum + r.rakes_with_penalty, 0);
                                            const grandTotal = rows.reduce((sum, r) => sum + r.total_penalty, 0);
                                            const anyPenalty = totalRakesWithPenalty > 0 || grandTotal > 0;
                                            const chartData = [...rows].sort((a, b) => b.total_penalty - a.total_penalty);
                                            const chartHeight = Math.min(420, Math.max(220, chartData.length * 36));

                                            if (rows.length === 0) {
                                                return (
                                                    <div className="mt-4 py-8 text-center text-sm text-gray-600">
                                                        No sidings in scope for this dashboard.
                                                    </div>
                                                );
                                            }

                                            return (
                                                <div className="mt-4 space-y-4">
                                                    <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-600">
                                                        <span>
                                                            {totalRakesWithPenalty} rake{totalRakesWithPenalty === 1 ? '' : 's'} with predicted penalties
                                                        </span>
                                                        <span className="font-medium text-gray-600">
                                                            Total: {formatCurrency(grandTotal)}
                                                        </span>
                                                    </div>

                                                    <div className="rounded-lg border bg-white p-3">
                                                        {anyPenalty ? (
                                                            <ResponsiveContainer width="100%" height={chartHeight}>
                                                                <RechartsBarChart
                                                                    data={chartData}
                                                                    layout="vertical"
                                                                    margin={{ top: 8, right: 24, bottom: 8, left: 8 }}
                                                                >
                                                                    <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.25} horizontal />
                                                                    <XAxis type="number" tick={{ fontSize: 11 }} tickFormatter={(v) => formatCurrency(v)} />
                                                                    <YAxis
                                                                        type="category"
                                                                        dataKey="siding_name"
                                                                        width={100}
                                                                        tick={{ fontSize: 11 }}
                                                                    />
                                                                    <Tooltip formatter={(v: number | string | undefined) => formatCurrency(Number(v ?? 0))} />
                                                                    <Bar dataKey="total_penalty" name="Predicted (₹)" fill="#DC2626" radius={[0, 4, 4, 0]} barSize={18} isAnimationActive>
                                                                        <LabelList dataKey="total_penalty" position="right" formatter={(v: unknown) => formatCurrency(Number(v ?? 0))} />
                                                                    </Bar>
                                                                </RechartsBarChart>
                                                            </ResponsiveContainer>
                                                        ) : (
                                                            <div className="py-8 text-center text-sm text-gray-600">
                                                                No predicted penalties for yesterday&apos;s loading date.
                                                            </div>
                                                        )}
                                                    </div>

                                                    <div className="grid grid-cols-1 gap-2">
                                                        {rows.map((r) => (
                                                            <div key={r.siding_name} className="flex items-center justify-between rounded-lg border bg-white px-3 py-2 text-sm">
                                                                <span className="font-medium text-gray-800">{r.siding_name}</span>
                                                                <span className="flex items-center gap-3 text-xs text-gray-600">
                                                                    <span className="tabular-nums">
                                                                        {r.rakes_with_penalty} rake{r.rakes_with_penalty === 1 ? '' : 's'}
                                                                    </span>
                                                                    <span className={`tabular-nums font-semibold ${r.total_penalty > 0 ? 'text-red-700' : 'text-gray-600'}`}>
                                                                        {formatCurrency(r.total_penalty)}
                                                                    </span>
                                                                </span>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            );
                                        })()}
                                    </div>
                                    ) : null}
                                </div>
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    {canWidget('dashboard.widgets.penalty_control_penalty_by_siding') ? (
                                    <DashboardPenaltyBySidingChart
                                        data={penaltyBySidingForSidingOverview}
                                        {...(executiveYesterday?.penaltyBySidingByPeriod
                                            ? {
                                                  period: sidingOverviewPenaltyPeriod,
                                                  onPeriodChange: setSidingOverviewPenaltyPeriod,
                                              }
                                            : {})}
                                    />
                                    ) : null}
                                    <div className="grid grid-cols-1 gap-6">
                                   
                                    {canWidget('dashboard.widgets.penalty_control_applied_vs_rr') ? (
                                    <div className="dashboard-card min-w-0 rounded-xl border-0 p-6">
                                        <SectionHeader icon={BarChart3} title="Applied vs RR penalty" subtitle="Applied penalties vs railway receipt snapshot, by siding" />
                                    <div className="mt-3 flex flex-wrap items-center justify-center gap-4 rounded-lg border border-gray-200 bg-gray-50/80 px-4 py-2 text-sm">
                                        <span className="flex items-center gap-2">
                                            <span className="font-medium text-gray-600">Total applied:</span>
                                            <span className="tabular-nums font-bold text-blue-700">{formatCurrency(predictedVsActualPenalty.predicted)}</span>
                                        </span>
                                        <span className="flex items-center gap-2">
                                            <span className="font-medium text-gray-600">Total RR snapshot:</span>
                                            <span className="tabular-nums font-bold text-red-700">{formatCurrency(predictedVsActualPenalty.actual)}</span>
                                        </span>
                                    </div>
                                    {(predictedVsActualPenalty.bySiding?.length ?? 0) > 0 ? (
                                        <div className="mt-4">
                                            <ResponsiveContainer width="100%" height={320}>
                                                <RechartsBarChart
                                                    data={predictedVsActualPenalty.bySiding}
                                                    margin={{ top: 8, right: 16, bottom: 80, left: 16 }}
                                                >
                                                    <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                                    <XAxis dataKey="name" type="category" tick={{ fontSize: 11 }} interval={0} height={70} />
                                                    <YAxis type="number" tickFormatter={(v: number) => formatCurrency(v)} width={72} tick={{ fontSize: 11 }} label={{ value: 'Penalty amount (₹)', angle: -90, position: 'insideLeft', style: { fontSize: 11 } }} />
                                                    <Tooltip formatter={(v: number | string | undefined) => formatCurrency(Number(v ?? 0))} />
                                                    <Legend verticalAlign="bottom" height={28} />
                                                    <Bar dataKey="predicted" name="Applied penalties" fill="#3B82F6" radius={[4, 4, 0, 0]} barSize={28} isAnimationActive />
                                                    <Bar dataKey="actual" name="RR snapshot" fill="#DC2626" radius={[4, 4, 0, 0]} barSize={28} isAnimationActive />
                                                </RechartsBarChart>
                                            </ResponsiveContainer>
                                        </div>
                                    ) : (
                                        <div className="mt-4 py-8 text-center text-sm text-gray-600">No predicted or actual penalty data for the selected period.</div>
                                    )}
                                    {(() => {
                                        const pred = predictedVsActualPenalty.predicted || 1;
                                        const pctDiff = ((predictedVsActualPenalty.actual - pred) / pred) * 100;
                                        const over = predictedVsActualPenalty.actual > pred;
                                        return (
                                            <div className={`mt-4 flex items-center gap-2 rounded-xl border-0 p-4 ${over ? 'bg-amber-50' : 'bg-green-50'}`} style={{ boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }}>
                                                {over ? <AlertTriangle className="size-5 shrink-0 text-amber-600" /> : <Check className="size-5 shrink-0 text-green-600" />}
                                                <p className={`text-sm font-medium ${over ? 'text-amber-800' : 'text-green-800'}`}>
                                                    Penalty is {pctDiff >= 0 ? `${pctDiff.toFixed(1)}% above` : `${Math.abs(pctDiff).toFixed(1)}% below`} predicted.
                                                </p>
                                            </div>
                                        );
                                    })()}
                                    </div>
                                    ) : null}
                                </div>
                                </div>

                                
                            </div>
                        )}

                        {activeSection === 'siding-performance' && (
                            sidingPerformance.length > 0 ? (
                                <SidingPerformanceSection data={sidingPerformance} />
                            ) : (
                                    <div className="dashboard-card rounded-xl border-0 p-6">
                                        <SectionHeader icon={BarChart3} title="Siding performance" subtitle="Rakes dispatched & penalty amount by siding" />
                                        <div className="mt-6 flex flex-col items-center justify-center py-10 text-center text-gray-600">
                                            <BarChart3 className="mb-3 h-10 w-10 opacity-30" />
                                            <p className="text-sm font-medium">No data available</p>
                                            <p className="mt-1 text-xs">Apply filters or wait for dispatch data.</p>
                                        </div>
                                    </div>
                                )
                        )}

                        {activeSection === 'rake-performance' && canWidget('dashboard.widgets.rake_performance') && (
                            <RakePerformanceSection
                                filters={filters}
                                allSidingIds={allSidingIds}
                                sidings={filteredSidings}
                                rakePenaltyScope={filters.rake_penalty_scope ?? 'all'}
                                onRakePenaltyScopeChange={onRakePenaltyScopeChange}
                                onNavigateToLoader={navigateToLoaderTrends}
                            />
                        )}

                        {activeSection === 'loader-overload' && canWidget('dashboard.widgets.loader_overload_trends') && (
                            <LoaderOverloadDashboardSection
                                buildApiSearchParams={buildLoaderOverloadApiParams}
                                defaultDetailUnderloadPercent={filters.underload_threshold}
                                mainDateRangeLabel={mainDateRangeLabel}
                                loaderIdFromUrl={filters.loader_id}
                                filterKey={loaderOverloadFilterKey}
                            />
                        )}

                        {activeSection === 'power-plant' && canWidget('dashboard.widgets.power_plant_dispatch_section') && (
                            <PowerPlantDispatchSection data={powerPlantDispatch} />
                        )}
                        </div>
                    </>
                )}
                    </div>

                    {kpiCards.length > 0 && (
                        <aside className="group sticky top-20 h-[calc(100vh-6rem)] shrink-0 self-start">
                            <div className="flex h-full flex-col overflow-hidden rounded-xl border bg-white shadow-sm transition-[width] duration-200 ease-out w-14 group-hover:w-72">
                                <div className="flex flex-1 flex-col gap-2 p-2">
                                    {kpiCards.map(({ label, value, borderColor, Icon }) => (
                                        <div
                                            key={label}
                                            className="flex items-center gap-2 rounded-lg border border-gray-100 bg-white px-2 py-2 shadow-[0_1px_0_rgba(0,0,0,0.02)]"
                                            style={{ borderLeft: `4px solid ${borderColor}` }}
                                        >
                                            <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-gray-50">
                                                <Icon className="size-5" style={{ color: borderColor }} aria-hidden />
                                            </div>
                                            <div className="min-w-0 flex-1 overflow-hidden opacity-0 transition-opacity duration-150 group-hover:opacity-100">
                                                <div className="truncate text-[11px] font-semibold text-gray-600">{label}</div>
                                                <div className="truncate text-lg font-extrabold tabular-nums text-gray-900">{value}</div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </aside>
                    )}
                </div>

                {/* Floating notifications button: always visible (superadmin only) */}
                <button
                    type="button"
                    onClick={() => setAlertsOpen(true)}
                    className="fixed bottom-24 right-6 z-40 flex size-14 items-center justify-center rounded-full bg-amber-500 shadow-lg ring-2 ring-amber-600/50 transition hover:bg-amber-600 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                    aria-label={`Notifications${notificationsUnreadCount > 0 ? ` (${notificationsUnreadCount})` : ''}`}
                >
                    <Bell className="size-6 text-white" />
                    {notificationsUnreadCount > 0 && (
                        <span className="absolute -right-0.5 -top-0.5 flex size-5 min-w-5 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white shadow ring-2 ring-white">
                            {notificationsUnreadCount > 99 ? '99+' : notificationsUnreadCount}
                        </span>
                    )}
                </button>
                <Dialog open={alertsOpen} onOpenChange={setAlertsOpen}>
                    <DialogContent className="max-h-[85vh] overflow-hidden flex flex-col sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <Bell className="size-5" />
                                Notifications
                            </DialogTitle>
                        </DialogHeader>
                        <div className="flex items-center justify-between gap-2">
                            <div className="text-xs text-muted-foreground">
                                {notificationsUnreadCount > 0 ? `${notificationsUnreadCount} unread` : 'All caught up'}
                            </div>
                            <Button
                                size="sm"
                                variant="outline"
                                className="h-7 text-[11px]"
                                disabled={notificationsUnreadCount === 0}
                                onClick={markAllNotificationsRead}
                            >
                                Mark all read
                            </Button>
                        </div>
                        <div className="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
                            {notifications.length === 0 ? (
                                <div className="flex flex-col items-center justify-center gap-2 rounded-lg bg-[#FEF3C7] py-8 text-center">
                                    <CheckCircle className="size-10 text-amber-600" aria-hidden />
                                    <p className="text-sm font-medium text-amber-800">No notifications</p>
                                </div>
                            ) : (
                                notifications.map((n) => (
                                    <div
                                        key={n.id}
                                        role="button"
                                        tabIndex={0}
                                        onClick={() => {
                                            if (!n.read_at) void markOneNotificationRead(n.id);
                                        }}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter' && !n.read_at) void markOneNotificationRead(n.id);
                                        }}
                                        className={`cursor-pointer rounded-lg border p-2.5 text-xs ${n.read_at ? 'border-gray-200 bg-gray-50' : 'border-amber-200 bg-amber-50'}`}
                                    >
                                        <span className="font-medium">{(n.data?.title as string) ?? 'Notification'}</span>
                                        <div className="mt-0.5 text-gray-700">{(n.data?.message as string) ?? ''}</div>
                                        <div className="mt-1 text-gray-600">{new Date(n.created_at).toLocaleString()}</div>
                                    </div>
                                ))
                            )}
                        </div>
                    </DialogContent>
                </Dialog>
                </div>
            </div>
        </AppLayout>
    );
}
