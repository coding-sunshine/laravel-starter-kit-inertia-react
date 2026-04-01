import { AreaChart as DashboardAreaChart } from '@/components/charts/area-chart';
import { BarChart } from '@/components/charts/bar-chart';
import { ComposedChart } from '@/components/charts/composed-chart';
import { StackedBarChart } from '@/components/charts/stacked-bar-chart';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { index as rakesIndex } from '@/routes/rakes';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowDown,
    ArrowLeft,
    ArrowRight,
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
import { Fragment, useCallback, useEffect, useMemo, useState } from 'react';
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
    { key: 'today', label: 'Today' },
    { key: 'week', label: 'This week' },
    { key: 'month', label: 'This month' },
    { key: 'quarter', label: 'Quarter' },
    { key: 'year', label: 'Year' },
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
    'rake-performance': ['rake_number', 'power_plant'] as const,
    'loader-overload': ['loader_id'] as const,
    'power-plant': ['power_plant'] as const,
} satisfies Record<
    (typeof DASHBOARD_SECTIONS)[number]['id'],
    readonly (
        | 'power_plant'
        | 'rake_number'
        | 'loader_id'
        | 'shift'
        | 'penalty_type'
        | 'daily_rake_date'
        | 'coal_transport_date'
    )[]
>;

const DEFAULT_DASHBOARD_SECTION = 'executive-overview';

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
    rake_number: string;
    siding: string;
    dispatch_date: string;
    wagon_count: number | null;
    net_weight: number | null;
    over_load: number | null;
    under_load: number | null;
    loading_minutes: number | null;
    penalty_amount: number;
    penalty_count: number;
    wagon_overloads?: Array<{ wagon_number: string; over_load_mt: number }>;
}

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
    shift: string | null;
    penalty_type: number | null;
    daily_rake_date?: string;
    coal_transport_date?: string;
}

interface DailyRakeDetailsRow {
    sl_no: number;
    siding_name: string;
    day_rakes: number;
    day_qty: number;
    month_rakes: number;
    month_qty: number;
    rake_day_avg: number;
    remarks: string;
}

interface DailyRakeDetailsData {
    date: string;
    rows: DailyRakeDetailsRow[];
    totals: { day_rakes: number; day_qty: number; month_rakes: number; month_qty: number; rake_day_avg: number };
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
    siding_name: string;
    state: string;
    workflow_steps?: WorkflowSteps;
    time_elapsed: string;
    risk: string;
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
}: {
    data: ExecutiveYesterdayData;
    viewMode: 'table' | 'charts';
}) {
    const [executiveData, setExecutiveData] = useState<ExecutiveYesterdayData>(data);

    const [roadFrom, setRoadFrom] = useState<string>(data.customRanges.roadDispatch.from);
    const [roadTo, setRoadTo] = useState<string>(data.customRanges.roadDispatch.to);
    const [railFrom, setRailFrom] = useState<string>(data.customRanges.railDispatch.from);
    const [railTo, setRailTo] = useState<string>(data.customRanges.railDispatch.to);
    const [obFrom, setObFrom] = useState<string>(data.customRanges.obProduction.from);
    const [obTo, setObTo] = useState<string>(data.customRanges.obProduction.to);
    const [coalFrom, setCoalFrom] = useState<string>(data.customRanges.coalProduction.from);
    const [coalTo, setCoalTo] = useState<string>(data.customRanges.coalProduction.to);

    const [isCustomLoading, setIsCustomLoading] = useState(false);
    const [customError, setCustomError] = useState<string | null>(null);

    useEffect(() => {
        setExecutiveData(data);
        setRoadFrom(data.customRanges.roadDispatch.from);
        setRoadTo(data.customRanges.roadDispatch.to);
        setRailFrom(data.customRanges.railDispatch.from);
        setRailTo(data.customRanges.railDispatch.to);
        setObFrom(data.customRanges.obProduction.from);
        setObTo(data.customRanges.obProduction.to);
        setCoalFrom(data.customRanges.coalProduction.from);
        setCoalTo(data.customRanges.coalProduction.to);
    }, [data]);

    const fmtNumber = (n: number, fractionDigits = 0): string =>
        n.toLocaleString(undefined, { minimumFractionDigits: fractionDigits, maximumFractionDigits: fractionDigits });

    const periodKeys: Array<'yesterday' | 'today' | 'week' | 'month' | 'fy'> = ['yesterday', 'today', 'week', 'month', 'fy'];
    const periodLabels: Record<(typeof periodKeys)[number], string> = {
        yesterday: 'Yesterday',
        today: 'Today',
        week: 'Week',
        month: 'Month',
        fy: 'Year',
    };

    const dashboardPath = dashboard().url.split('?')[0] || dashboard().url;

    const isValidRange = (from: string, to: string): boolean => Boolean(from) && Boolean(to) && from <= to;

    const applyCustomRanges = useCallback(async (): Promise<void> => {
        setIsCustomLoading(true);
        setCustomError(null);
        try {
            const url = new URL(`${dashboardPath.replace(/\/$/, '')}/executive-yesterday-data`, window.location.origin);
            url.searchParams.set('executive_yesterday_date', executiveData.anchorDate);
            url.searchParams.set('executive_road_from', roadFrom);
            url.searchParams.set('executive_road_to', roadTo);
            url.searchParams.set('executive_rail_from', railFrom);
            url.searchParams.set('executive_rail_to', railTo);
            url.searchParams.set('executive_ob_from', obFrom);
            url.searchParams.set('executive_ob_to', obTo);
            url.searchParams.set('executive_coal_from', coalFrom);
            url.searchParams.set('executive_coal_to', coalTo);

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

            const next = (await res.json()) as ExecutiveYesterdayData;
            setExecutiveData(next);
        } catch (e) {
            setCustomError(e instanceof Error ? e.message : 'Failed to load custom range data.');
        } finally {
            setIsCustomLoading(false);
        }
    }, [dashboardPath, executiveData.anchorDate, roadFrom, roadTo, railFrom, railTo, obFrom, obTo, coalFrom, coalTo]);

    const SummaryHeader = ({ label, columns }: { label: string; columns: string[] }) => (
        <thead className="bg-[#eef2f7] text-black">
            <tr>
                <th className="border border-[#d5dbe4] px-3 py-2 text-left font-medium" colSpan={2}>{label}</th>
                {columns.map((c) => (
                    <th key={c} className="border border-[#d5dbe4] px-3 py-2 text-center font-medium">
                        {c === 'MonthToDate' ? 'Month to date' : c}
                    </th>
                ))}
            </tr>
        </thead>
    );

    const DispatchBlock = (props: {
        title: string;
        metricCountLabel: string;
        countKey: 'trips' | 'rakes';
        summary: { columns: string[]; data: Record<string, { qty: number } & Partial<Record<'trips' | 'rakes', number>> > };
        toolbar?: React.ReactNode;
    }) => (
        <div className="overflow-hidden rounded-xl border border-[#d5dbe4] bg-white">
            <div className="border-b border-[#d5dbe4] bg-[#f8fafc] px-4 py-3">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm font-semibold text-gray-900">{props.title}</p>
                    {props.toolbar ? <div className="flex flex-wrap items-center gap-2">{props.toolbar}</div> : null}
                </div>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full border-separate text-xs" style={{ borderSpacing: 0 }}>
                    <SummaryHeader label={props.title} columns={props.summary.columns} />
                    <tbody>
                        <tr className="bg-white">
                            <td className="border border-[#d5dbe4] px-3 py-2 font-semibold" rowSpan={2}>{props.title}</td>
                            <td className="border border-[#d5dbe4] px-3 py-2 font-medium">{props.metricCountLabel}</td>
                            {props.summary.columns.map((c) => (
                                <td key={c} className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                    {fmtNumber(Number(props.summary.data?.[c]?.[props.countKey] ?? 0), 0)}
                                </td>
                            ))}
                        </tr>
                        <tr className="bg-white">
                            <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Qty</td>
                            {props.summary.columns.map((c) => (
                                <td key={c} className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                    {fmtNumber(Number(props.summary.data?.[c]?.qty ?? 0), 2)}
                                </td>
                            ))}
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    );

    const ProductionBlock = (props: {
        title: string;
        summary: { columns: string[]; data: Record<string, { trips: number; qty: number }> };
        toolbar?: React.ReactNode;
    }) => (
        <div className="overflow-hidden rounded-xl border border-[#d5dbe4] bg-white">
            <div className="border-b border-[#d5dbe4] bg-[#f8fafc] px-4 py-3">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm font-semibold text-gray-900">{props.title}</p>
                    {props.toolbar ? <div className="flex flex-wrap items-center gap-2">{props.toolbar}</div> : null}
                </div>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full border-separate text-xs" style={{ borderSpacing: 0 }}>
                    <SummaryHeader label={props.title} columns={props.summary.columns} />
                    <tbody>
                        <tr className="bg-white">
                            <td className="border border-[#d5dbe4] px-3 py-2 font-semibold" rowSpan={2}>{props.title}</td>
                            <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Trips</td>
                            {props.summary.columns.map((c) => (
                                <td key={c} className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                    {fmtNumber(props.summary.data?.[c]?.trips ?? 0, 0)}
                                </td>
                            ))}
                        </tr>
                        <tr className="bg-white">
                            <td className="border border-[#d5dbe4] px-3 py-2 font-medium">Qty</td>
                            {props.summary.columns.map((c) => (
                                <td key={c} className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">
                                    {fmtNumber(props.summary.data?.[c]?.qty ?? 0, 2)}
                                </td>
                            ))}
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    );

    const TableView = (
        <div className="space-y-6">
            <DispatchBlock
                title="Road Dispatch"
                metricCountLabel="Trips"
                countKey="trips"
                summary={executiveData.customRanges.roadDispatch.summary}
                toolbar={
                    <>
                        <span className="hidden text-[11px] text-muted-foreground sm:inline">
                            Custom: {fmtNumber(executiveData.customRanges.roadDispatch.totals.trips, 0)} trips, {fmtNumber(executiveData.customRanges.roadDispatch.totals.qty, 2)} MT
                        </span>
                        <label className="text-xs font-medium text-gray-600">From</label>
                        <input
                            type="date"
                            value={roadFrom}
                            onChange={(e) => setRoadFrom(e.target.value)}
                            className="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm"
                        />
                        <label className="text-xs font-medium text-gray-600">To</label>
                        <input
                            type="date"
                            value={roadTo}
                            onChange={(e) => setRoadTo(e.target.value)}
                            className="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm"
                        />
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            disabled={isCustomLoading || !isValidRange(roadFrom, roadTo)}
                            onClick={applyCustomRanges}
                        >
                            {isCustomLoading ? 'Applying…' : 'Apply'}
                        </Button>
                    </>
                }
            />

            <DispatchBlock
                title="Rail Dispatch"
                metricCountLabel="Rakes"
                countKey="rakes"
                summary={executiveData.customRanges.railDispatch.summary}
                toolbar={
                    <>
                        <span className="hidden text-[11px] text-muted-foreground sm:inline">
                            Custom: {fmtNumber(executiveData.customRanges.railDispatch.totals.rakes, 0)} rakes, {fmtNumber(executiveData.customRanges.railDispatch.totals.qty, 2)} MT
                        </span>
                        <label className="text-xs font-medium text-gray-600">From</label>
                        <input
                            type="date"
                            value={railFrom}
                            onChange={(e) => setRailFrom(e.target.value)}
                            className="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm"
                        />
                        <label className="text-xs font-medium text-gray-600">To</label>
                        <input
                            type="date"
                            value={railTo}
                            onChange={(e) => setRailTo(e.target.value)}
                            className="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm"
                        />
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            disabled={isCustomLoading || !isValidRange(railFrom, railTo)}
                            onClick={applyCustomRanges}
                        >
                            {isCustomLoading ? 'Applying…' : 'Apply'}
                        </Button>
                    </>
                }
            />

            {customError ? (
                <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {customError}
                </div>
            ) : null}

            <ProductionBlock
                title="OB Production"
                summary={executiveData.customRanges.obProduction.summary}
                toolbar={
                    <>
                        <span className="hidden text-[11px] text-muted-foreground sm:inline">
                            Custom: {fmtNumber(executiveData.customRanges.obProduction.totals.trips, 0)} trips, {fmtNumber(executiveData.customRanges.obProduction.totals.qty, 2)} MT
                        </span>
                        <label className="text-xs font-medium text-gray-600">From</label>
                        <input
                            type="date"
                            value={obFrom}
                            onChange={(e) => setObFrom(e.target.value)}
                            className="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm"
                        />
                        <label className="text-xs font-medium text-gray-600">To</label>
                        <input
                            type="date"
                            value={obTo}
                            onChange={(e) => setObTo(e.target.value)}
                            className="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm"
                        />
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            disabled={isCustomLoading || !isValidRange(obFrom, obTo)}
                            onClick={applyCustomRanges}
                        >
                            {isCustomLoading ? 'Applying…' : 'Apply'}
                        </Button>
                    </>
                }
            />

            <ProductionBlock
                title="Coal Production"
                summary={executiveData.customRanges.coalProduction.summary}
                toolbar={
                    <>
                        <span className="hidden text-[11px] text-muted-foreground sm:inline">
                            Custom: {fmtNumber(executiveData.customRanges.coalProduction.totals.trips, 0)} trips, {fmtNumber(executiveData.customRanges.coalProduction.totals.qty, 2)} MT
                        </span>
                        <label className="text-xs font-medium text-gray-600">From</label>
                        <input
                            type="date"
                            value={coalFrom}
                            onChange={(e) => setCoalFrom(e.target.value)}
                            className="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm"
                        />
                        <label className="text-xs font-medium text-gray-600">To</label>
                        <input
                            type="date"
                            value={coalTo}
                            onChange={(e) => setCoalTo(e.target.value)}
                            className="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm"
                        />
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            disabled={isCustomLoading || !isValidRange(coalFrom, coalTo)}
                            onClick={applyCustomRanges}
                        >
                            {isCustomLoading ? 'Applying…' : 'Apply'}
                        </Button>
                    </>
                }
            />

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
                            {executiveData.fySummary.rows.map((r) => (
                                <tr key={r.fy} className="bg-white">
                                    <td className="border border-[#d5dbe4] px-3 py-2 font-medium">{r.fy}</td>
                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{fmtNumber(r.production.obQty, 2)}</td>
                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{fmtNumber(r.production.coalQty, 2)}</td>
                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{fmtNumber(r.roadDispatch.trips, 0)}</td>
                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{fmtNumber(r.roadDispatch.qty, 2)}</td>
                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{fmtNumber(r.railDispatch.rakes, 0)}</td>
                                    <td className="border border-[#d5dbe4] px-3 py-2 text-right tabular-nums">{fmtNumber(r.railDispatch.qty, 2)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );

    const compactQty = (n: number): string => fmtNumber(n, n % 1 === 0 ? 0 : 2);

    const [isCombinedChartVisible, setIsCombinedChartVisible] = useState(false);

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

    const fyCombinedRows = executiveData.fyCharts.rows.map((r) => ({
        fy: r.fy,
        OB: r.production.obQty,
        COAL: r.production.coalQty,
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

            <div className="flex items-center justify-end">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => setIsCombinedChartVisible((v) => !v)}
                    className="gap-2"
                >
                    {isCombinedChartVisible ? (
                        <>
                            <ChevronUp className="size-4" />
                            Hide Production & Dispatch
                        </>
                    ) : (
                        <>
                            <ChevronDown className="size-4" />
                            Show Production & Dispatch
                        </>
                    )}
                </Button>
            </div>

            {isCombinedChartVisible ? (
                <FyChartCard
                    title="Production & Dispatch"
                    rows={fyCombinedRows}
                    series={[
                        { key: 'OB', label: 'OB', color: DASHBOARD_PALETTE.successGreen },
                        { key: 'COAL', label: 'Coal', color: DASHBOARD_PALETTE.steelBlue },
                        { key: 'ROAD', label: 'Road', color: DASHBOARD_PALETTE.safetyYellow },
                        { key: 'RAIL', label: 'Rail', color: DASHBOARD_PALETTE.darkGrey },
                    ]}
                    height={340}
                />
            ) : null}
        </div>
    );

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

function RakePerformanceSection({ rakes }: { rakes: RakePerformanceItem[] }) {
    const rakeOptions = useMemo(() => {
        return rakes.map((r, i) => ({ idx: i, label: `${r.rake_number} — ${r.siding} (${r.dispatch_date})` }));
    }, [rakes]);

    const [selectedIdx, setSelectedIdx] = useState(0);
    const r = rakes[selectedIdx] ?? rakes[0];

    const loadingHours = r.loading_minutes != null ? Math.floor(r.loading_minutes / 60) : null;
    const loadingMins = r.loading_minutes != null ? r.loading_minutes % 60 : null;

    const weightChartData = useMemo(() => {
        const items: { name: string; value: number; fill?: string }[] = [];
        if (r.net_weight != null) items.push({ name: 'Net weight', value: r.net_weight, fill: '#4B72BE' });
        if (r.over_load != null && r.over_load > 0) items.push({ name: 'Overload', value: r.over_load, fill: '#DC2626' });
        if (r.under_load != null && r.under_load > 0) items.push({ name: 'Underload', value: r.under_load, fill: '#F59E0B' });
        return items;
    }, [r]);

    const wagonOverloadChartData = useMemo(() => {
        const list = r.wagon_overloads ?? [];
        return list.map((w, i) => ({ position: i + 1, wagon_number: w.wagon_number, over_load_mt: w.over_load_mt }));
    }, [r.wagon_overloads]);

    const canPrev = selectedIdx > 0;
    const canNext = selectedIdx < rakes.length - 1 && rakes.length > 1;

    return (
        <div className="dashboard-card rounded-xl border-0 p-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <SectionHeader
                    icon={Train}
                    title="Rake-wise performance"
                    subtitle="Select a rake to view its details"
                    action={
                        <div className="flex flex-wrap items-center gap-2">
                            <div className="flex items-center gap-1">
                                <button
                                    type="button"
                                    onClick={() => setSelectedIdx((i) => Math.max(0, i - 1))}
                                    disabled={!canPrev}
                                    className="rounded-lg border border-gray-200 bg-white p-2 text-gray-600 disabled:opacity-40"
                                    aria-label="Previous rake"
                                >
                                    <ArrowLeft className="size-4" />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setSelectedIdx((i) => Math.min(rakes.length - 1, i + 1))}
                                    disabled={!canNext}
                                    className="rounded-lg border border-gray-200 bg-white p-2 text-gray-600 disabled:opacity-40"
                                    aria-label="Next rake"
                                >
                                    <ArrowRight className="size-4" />
                                </button>
                            </div>
                            <Select value={String(selectedIdx)} onValueChange={(v) => setSelectedIdx(Number(v))}>
                                <SelectTrigger className="min-w-[200px] rounded-lg border border-gray-200 bg-white text-sm">
                                    <SelectValue placeholder="Select rake" />
                                </SelectTrigger>
                                <SelectContent>
                                    {rakeOptions.map((opt) => (
                                        <SelectItem key={opt.idx} value={String(opt.idx)}>
                                            {opt.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button variant="outline" size="sm" className="rounded-lg" asChild>
                                <Link href={rakesIndex().url} data-pan="dashboard-view-all-rakes">View all rakes</Link>
                            </Button>
                        </div>
                    }
                />
            </div>

            <div className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
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
                <div className={`rounded-lg border p-3 ${r.penalty_amount > 0 ? 'border-red-100 bg-red-50' : 'border-green-100 bg-green-50'}`}>
                    <p className="text-xs font-medium text-gray-600">Penalty</p>
                    <p className={`mt-1 font-bold tabular-nums ${r.penalty_amount > 0 ? 'text-red-700' : 'text-green-700'}`}>
                        {r.penalty_amount > 0 ? formatCurrency(r.penalty_amount) : 'None'}
                    </p>
                </div>
            </div>

            <div className="mt-5 grid gap-5 lg:grid-cols-2">
                <div>
                    <p className="mb-2 text-xs font-medium text-gray-600">Weight breakdown (MT)</p>
                    {weightChartData.length > 0 ? (
                        <ResponsiveContainer width="100%" height={220}>
                            <RechartsBarChart data={weightChartData} margin={{ top: 8, right: 8, left: 8, bottom: 0 }}>
                                <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                <XAxis dataKey="name" tick={{ fontSize: 11 }} />
                                <YAxis tick={{ fontSize: 11 }} tickFormatter={(v) => `${v} MT`} />
                                <Tooltip formatter={(v: number | undefined) => `${Number(v ?? 0).toLocaleString()} MT`} />
                                <Bar dataKey="value" radius={[4, 4, 0, 0]} barSize={32} isAnimationActive>
                                    {weightChartData.map((entry, i) => (
                                        <Cell key={i} fill={entry.fill ?? '#4B72BE'} />
                                    ))}
                                </Bar>
                            </RechartsBarChart>
                        </ResponsiveContainer>
                    ) : (
                        <div className="flex h-[220px] items-center justify-center rounded-lg border border-gray-100 bg-gray-50/50 text-sm text-gray-600">
                            No weighment data available
                        </div>
                    )}
                </div>
                <div>
                    <p className="mb-2 text-xs font-medium text-gray-600">Wagon-wise overload (MT)</p>
                    {wagonOverloadChartData.length > 0 ? (
                        <ResponsiveContainer width="100%" height={220}>
                            <RechartsBarChart
                                data={wagonOverloadChartData}
                                margin={{ top: 8, right: 8, left: 8, bottom: 24 }}
                            >
                                <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                <XAxis dataKey="position" tick={{ fontSize: 10 }} interval={0} height={40} />
                                <YAxis tick={{ fontSize: 11 }} tickFormatter={(v: number) => `${v} MT`} label={{ value: 'Overload (MT)', angle: -90, position: 'insideLeft', style: { fontSize: 10 } }} />
                                <Tooltip
                                    content={({ active, payload }) => {
                                        if (!active || !payload?.length) return null;
                                        const p = payload[0];
                                        const wagonNum = (p.payload as { wagon_number?: string }).wagon_number ?? '—';
                                        const value = Number(p.value ?? 0);
                                        return (
                                            <div className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm shadow-lg">
                                                <p className="font-medium text-gray-800">Wagon {wagonNum}</p>
                                                <p className="tabular-nums text-gray-600">Overload: {value.toLocaleString()} MT</p>
                                            </div>
                                        );
                                    }}
                                />
                                <Bar dataKey="over_load_mt" radius={[4, 4, 0, 0]} barSize={20} isAnimationActive>
                                    {wagonOverloadChartData.map((entry, i) => (
                                        <Cell key={i} fill={entry.over_load_mt > 0 ? '#DC2626' : '#E5E7EB'} />
                                    ))}
                                </Bar>
                            </RechartsBarChart>
                        </ResponsiveContainer>
                    ) : (
                        <div className={`flex h-[220px] flex-col items-center justify-center gap-3 rounded-xl p-6 ${r.over_load != null && r.over_load > 0 ? 'bg-[#FEF2F2]' : 'bg-gray-50'}`}>
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
        </div>
    );
}

function LoaderOverloadSection({ loaders, monthly }: { loaders: LoaderInfo[]; monthly: Record<string, unknown>[] }) {
    const [selectedLoaderId, setSelectedLoaderId] = useState<number>(loaders[0]?.id ?? 0);

    const selectedLoader = loaders.find((l) => l.id === selectedLoaderId) ?? loaders[0];

    const stats = useMemo(() => {
        if (!selectedLoader) return { totalWagons: 0, totalOverload: 0, rate: 0, trend: 0 };
        const totalOverload = monthly.reduce(
            (sum, m) => sum + ((m[`loader_${selectedLoader.id}_overload`] as number) ?? 0), 0,
        );
        const totalWagons = monthly.reduce(
            (sum, m) => sum + ((m[`loader_${selectedLoader.id}_total`] as number) ?? 0), 0,
        );
        const rate = totalWagons > 0 ? (totalOverload / totalWagons) * 100 : 0;
        const lastTwo = monthly.slice(-2);
        const trend = lastTwo.length === 2
            ? ((lastTwo[1][`loader_${selectedLoader.id}_overload`] as number) ?? 0) - ((lastTwo[0][`loader_${selectedLoader.id}_overload`] as number) ?? 0)
            : 0;
        return { totalWagons, totalOverload, rate, trend };
    }, [selectedLoader, monthly]);

    const trendData = useMemo(() => {
        if (!selectedLoader) return [];
        return monthly.map((m) => ({
            month: m.month as string,
            overloaded: (m[`loader_${selectedLoader.id}_overload`] as number) ?? 0,
            total: (m[`loader_${selectedLoader.id}_total`] as number) ?? 0,
        }));
    }, [monthly, selectedLoader]);

    const barChartData = useMemo(() => {
        if (!selectedLoader) return [];
        return monthly.map((m) => ({
            month: m.month as string,
            value: (m[`loader_${selectedLoader.id}_overload`] as number) ?? 0,
        }));
    }, [monthly, selectedLoader]);

    const avgOverload = useMemo(() => {
        if (barChartData.length === 0) return 0;
        const sum = barChartData.reduce((s, d) => s + d.value, 0);
        return sum / barChartData.length;
    }, [barChartData]);

    if (!selectedLoader) return null;

    const hasData = trendData.some((d) => d.total > 0 || d.overloaded > 0);

    return (
        <div className="dashboard-card rounded-xl border-0 p-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <SectionHeader
                    icon={AlertTriangle}
                    title="Loader-wise overloading trends"
                    subtitle="Select a loader to view its performance"
                />
                <Select value={String(selectedLoaderId)} onValueChange={(v) => setSelectedLoaderId(Number(v))}>
                    <SelectTrigger className="min-w-[200px] rounded-[8px] border border-gray-200 bg-white text-sm">
                        <SelectValue placeholder="Select loader" />
                    </SelectTrigger>
                    <SelectContent>
                        {loaders.map((l) => (
                            <SelectItem key={l.id} value={String(l.id)}>
                                {l.name} — {l.siding}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {!hasData ? (
                <div className="mt-8 flex flex-col items-center justify-center py-12 text-center text-gray-600">
                    <AlertTriangle className="mb-3 h-12 w-12 opacity-40" />
                    <p className="font-medium">No data for selected loader in this period</p>
                </div>
            ) : (
                <>
                    <div className="mt-5 grid gap-4 sm:grid-cols-3">
                        <div className="rounded-xl border-0 bg-blue-50 p-4 shadow-sm" style={{ boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }}>
                            <p className="text-xs font-medium text-blue-600">Total wagons loaded</p>
                            <p className="mt-1 text-2xl font-bold tabular-nums text-blue-900">{stats.totalWagons}</p>
                        </div>
                        <div className="rounded-xl border-0 bg-red-50 p-4 shadow-sm" style={{ boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }}>
                            <p className="text-xs font-medium text-red-600">Overloaded wagons</p>
                            <p className="mt-1 text-2xl font-bold tabular-nums text-red-900">{stats.totalOverload}</p>
                        </div>
                        <div className={`rounded-xl border-0 p-4 shadow-sm ${stats.rate > 15 ? 'bg-red-50' : stats.rate > 5 ? 'bg-amber-50' : 'bg-green-50'}`} style={{ boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }}>
                            <p className={`text-xs font-medium ${stats.rate > 15 ? 'text-red-600' : stats.rate > 5 ? 'text-amber-600' : 'text-green-600'}`}>Overload rate</p>
                            <div className="mt-1 flex items-center gap-2">
                                <span className={`text-2xl font-bold tabular-nums ${stats.rate > 15 ? 'text-red-900' : stats.rate > 5 ? 'text-amber-900' : 'text-green-900'}`}>{stats.rate.toFixed(1)}%</span>
                                {stats.trend !== 0 && (
                                    stats.trend > 0 ? <ArrowUp className="size-5 text-red-600" /> : <ArrowDown className="size-5 text-green-600" />
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 grid gap-6 lg:grid-cols-2">
                        <div>
                            <p className="mb-2 text-xs font-medium text-gray-600">Total wagons vs overloaded (monthly)</p>
                            <ResponsiveContainer width="100%" height={240}>
                                <RechartsAreaChart data={trendData} margin={{ top: 8, right: 8, left: 8, bottom: 0 }}>
                                    <defs>
                                        <linearGradient id="loader-total-gradient" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stopColor="#93C5FD" stopOpacity={0.4} />
                                            <stop offset="100%" stopColor="#93C5FD" stopOpacity={0} />
                                        </linearGradient>
                                        <linearGradient id="loader-overload-gradient" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stopColor="#DC2626" stopOpacity={0.4} />
                                            <stop offset="100%" stopColor="#DC2626" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                    <XAxis dataKey="month" tick={{ fontSize: 11 }} />
                                    <YAxis tick={{ fontSize: 11 }} />
                                    <Tooltip formatter={(v: number | undefined, name?: string) => [v ?? 0, name === 'total' ? 'Total wagons' : 'Overloaded']} />
                                    <Legend />
                                    <Area type="monotone" dataKey="total" name="Total wagons" stroke="#3B82F6" fill="url(#loader-total-gradient)" strokeWidth={2} dot={false} />
                                    <Area type="monotone" dataKey="overloaded" name="Overloaded" stroke="#DC2626" fill="url(#loader-overload-gradient)" strokeWidth={2} dot={false} />
                                </RechartsAreaChart>
                            </ResponsiveContainer>
                        </div>
                        <div>
                            <p className="mb-2 text-xs font-medium text-gray-600">Overloaded wagons per month</p>
                            <ResponsiveContainer width="100%" height={240}>
                                <RechartsBarChart data={barChartData} margin={{ top: 8, right: 8, left: 8, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                    <XAxis dataKey="month" tick={{ fontSize: 11 }} />
                                    <YAxis tick={{ fontSize: 11 }} />
                                    <Tooltip formatter={(v: number | undefined) => [`${v ?? 0} wagons`, 'Overloaded']} />
                                    <ReferenceLine y={avgOverload} stroke="#9ca3af" strokeDasharray="5 5" />
                                    <Bar dataKey="value" fill="#DC2626" radius={[4, 4, 0, 0]} barSize={24} isAnimationActive />
                                </RechartsBarChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    <p className="mt-4 text-sm text-gray-600">
                        {selectedLoader.name} has <span className="font-semibold">{stats.rate.toFixed(1)}%</span> overload rate
                        {barChartData.length > 0 && avgOverload >= 0 ? `, ${stats.totalOverload > avgOverload * barChartData.length ? 'above' : 'below'} average (${avgOverload.toFixed(0)} overloaded/month).` : '.'}
                    </p>
                </>
            )}
        </div>
    );
}

const SIDING_COLORS = [DASHBOARD_PALETTE.steelBlue, DASHBOARD_PALETTE.successGreen, DASHBOARD_PALETTE.safetyYellow, DASHBOARD_PALETTE.steelBlueLight, DASHBOARD_PALETTE.successGreenLight];

const PLANT_COLORS: Record<string, string> = { PSPM: '#3B82F6', STPS: '#10B981', BTPC: '#F59E0B', KPPS: '#8B5CF6' };

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
            <div className="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
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
                <div className="dashboard-card flex items-center gap-3 rounded-xl border-0 p-4" style={{ borderTop: '4px solid #3B82F6' }}>
                    <Zap className="size-5 text-blue-600" />
                    <div>
                        <div className="text-xs font-medium text-gray-600">Avg per destination</div>
                        <div className="text-xl font-bold tabular-nums text-gray-900">{data.length > 0 ? formatWeight(totalWeight / data.length) : '—'}</div>
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
                    <ResponsiveContainer width="100%" height={Math.max(260, data.length * 48)}>
                        <RechartsBarChart data={stackedChartData} margin={{ left: 10, right: 20, top: 5, bottom: 5 }} barCategoryGap="20%">
                            <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                            <XAxis dataKey="name" tick={{ fontSize: 11 }} />
                            <YAxis allowDecimals={false} />
                            <Tooltip />
                            <Legend />
                            {allSidingNames.map((sn, i) => (
                                <Bar
                                    key={sn}
                                    dataKey={sn}
                                    stackId={stacked ? 'stack' : undefined}
                                    fill={SIDING_COLORS[i % SIDING_COLORS.length]}
                                    name={sn}
                                    radius={[4, 4, 0, 0]}
                                    barSize={32}
                                >
                                    <LabelList dataKey={sn} position="top" />
                                </Bar>
                            ))}
                        </RechartsBarChart>
                    </ResponsiveContainer>
                </div>

                <div>
                    <h4 className="mb-2 text-xs font-medium text-gray-600">Rakes dispatched per power plant</h4>
                    <ResponsiveContainer width="100%" height={Math.max(260, data.length * 40)}>
                        <RechartsBarChart data={sortedByRakes} margin={{ top: 8, right: 16, bottom: 0, left: 8 }}>
                            <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                            <XAxis dataKey="name" tick={{ fontSize: 11 }} />
                            <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                            <Tooltip formatter={(v: number | undefined) => `${v ?? 0} rakes`} />
                            <Bar dataKey="rakes" radius={[4, 4, 0, 0]} barSize={32} isAnimationActive>
                                {sortedByRakes.map((pp, i) => (
                                    <Cell key={pp.name} fill={PLANT_COLORS[pp.name] ?? SIDING_COLORS[i % SIDING_COLORS.length]} />
                                ))}
                                <LabelList dataKey="rakes" position="top" />
                            </Bar>
                        </RechartsBarChart>
                    </ResponsiveContainer>
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

    const applyFilters = useCallback((overrides: Record<string, unknown> = {}) => {
        const params: Record<string, unknown> = {
            period: overrides.period ?? period,
            ...overrides,
        };

        if (params.period === 'custom') {
            params.from = overrides.from ?? customFrom;
            params.to = overrides.to ?? customTo;
        } else {
            // Never send date range when period is not custom, so backend uses its default range for the period.
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

        if (sectionHasFilter('loader_id')) {
            const loaderId = (overrides.loader_id !== undefined ? overrides.loader_id : filters.loader_id) ?? '';
            if (loaderId !== '') params.loader_id = loaderId;
        }

        if (sectionHasFilter('shift')) {
            const shift = (overrides.shift !== undefined ? overrides.shift : filters.shift) ?? '';
            if (shift !== '') params.shift = shift;
        }

        if (sectionHasFilter('penalty_type')) {
            const penaltyType = (overrides.penalty_type !== undefined ? overrides.penalty_type : filters.penalty_type) ?? null;
            if (penaltyType != null) params.penalty_type = penaltyType;
        }

        if (sectionHasFilter('daily_rake_date')) {
            const dailyRakeDate =
                (overrides.daily_rake_date !== undefined ? overrides.daily_rake_date : filters.daily_rake_date) ?? '';
            if (dailyRakeDate !== '') params.daily_rake_date = dailyRakeDate;
        }

        if (sectionHasFilter('coal_transport_date')) {
            const coalTransportDate =
                (overrides.coal_transport_date !== undefined ? overrides.coal_transport_date : filters.coal_transport_date) ?? '';
            if (coalTransportDate !== '') params.coal_transport_date = coalTransportDate;
        }

        if (currentSection) params.section = currentSection;

        // Use pathname only so query is exactly our params (no merge with current URL).
        const dashboardPath = dashboard().url.split('?')[0] || dashboard().url;
        router.get(dashboardPath, params as Record<string, string>, {
            preserveState: true,
            preserveScroll: true,
        });
    }, [filters, customFrom, customTo, allSidingIds, currentSection]);

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
        setPeriod('today');
        setCustomFrom(filters.from);
        setCustomTo(filters.to);
        setPendingSidingIds(allSidingIds);
        setRakeNumberInput('');
        applyFilters({
            period: 'today',
            siding_ids: allSidingIds,
            power_plant: null,
            rake_number: null,
            loader_id: null,
            shift: null,
            penalty_type: null,
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
                    <SelectTrigger className="h-7 w-[100px] rounded-md border text-[11px]">
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
                        onValueChange={(v) => applyFilters({ loader_id: v === ALL_FILTER_VALUE ? null : Number(v) })}
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
    const [activeSection, setActiveSection] = useState<string>(() => {
        const s = props.section ?? DEFAULT_DASHBOARD_SECTION;
        return DASHBOARD_SECTIONS.some((sec) => sec.id === s) ? s : DEFAULT_DASHBOARD_SECTION;
    });
    const [executiveYesterdayViewMode, setExecutiveYesterdayViewMode] = useState<'table' | 'charts'>('charts');
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
        period: 'today',
        from: '',
        to: '',
        siding_ids: [],
        power_plant: null,
        rake_number: null,
        loader_id: null,
        shift: null,
        penalty_type: null,
        daily_rake_date: undefined,
        coal_transport_date: undefined,
    };
    const filters: DashboardFilters = {
        ...defaultFilters,
        ...(props.filters ?? {}),
        power_plant: props.filters?.power_plant ?? null,
        rake_number: props.filters?.rake_number ?? null,
        loader_id: props.filters?.loader_id ?? null,
        shift: props.filters?.shift ?? null,
        penalty_type: props.filters?.penalty_type ?? null,
    };
    const periodLabel = useMemo(() => {
        switch (filters.period) {
            case 'today':
                return 'today';
            case 'week':
                return 'this week';
            case 'month':
                return 'this month';
            case 'quarter':
                return 'this quarter';
            case 'year':
                return 'this year';
            case 'custom':
                return 'selected period';
            default:
                return 'today';
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
        if (filters.shift) params.shift = filters.shift;
        if (filters.penalty_type != null) params.penalty_type = filters.penalty_type;
        router.get(dashboardPath, params as Record<string, string>, { replace: true, preserveState: false });
    }, [dashboardPath, filters.period, filters.siding_ids, filters.power_plant, filters.rake_number, filters.loader_id, filters.shift, filters.penalty_type, sidings.length]);

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
        if (filters.shift) params.shift = filters.shift;
        if (filters.penalty_type != null) params.penalty_type = filters.penalty_type;
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
        if (filters.shift) params.shift = filters.shift;
        if (filters.penalty_type != null) params.penalty_type = filters.penalty_type;
        if (filters.daily_rake_date) params.daily_rake_date = filters.daily_rake_date;
        router.get(dashboardPath, params as Record<string, string>, { preserveState: true, preserveScroll: true });
    }, [dashboardPath, filters, activeSection, allSidingIds.length]);

    const filterOptions = props.filterOptions ?? { powerPlants: [], loaders: [], shifts: [], penaltyTypes: [] };
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
                };
            }
        }
        return merged;
    }, [baseSidingStocks, stockOverrides]);
    const sidingPerformance = props.sidingPerformance ?? [];
    const sidingWiseMonthly = props.sidingWiseMonthly ?? [];
    const sidingRadar = props.sidingRadar ?? { sidings: [] };
    const dateWiseDispatch = props.dateWiseDispatch ?? { sidingNames: {}, dates: [] };
    const rakePerformance = props.rakePerformance ?? [];
    const loaderOverloadTrends = props.loaderOverloadTrends ?? { loaders: [], monthly: [] };
    const powerPlantDispatch = props.powerPlantDispatch ?? [];
    const yesterdayPredictedPenalties = props.yesterdayPredictedPenalties ?? [];
    const executiveYesterday = props.executiveYesterday;

    const filteredSidings = useMemo(() => {
        if (filters.siding_ids.length === 0 || filters.siding_ids.length === sidings.length) {
            return sidings;
        }
        const idSet = new Set(filters.siding_ids);
        return sidings.filter((s) => idSet.has(s.id));
    }, [sidings, filters.siding_ids]);

    const hasActiveFilters = useMemo(() => {
        if (filters.period !== 'today') return true;
        if (filters.power_plant) return true;
        if (filters.rake_number?.trim()) return true;
        if (filters.loader_id != null) return true;
        if (filters.shift) return true;
        if (filters.penalty_type != null) return true;
        if (sidings.length > 0 && filters.siding_ids.length > 0 && filters.siding_ids.length < sidings.length) return true;
        return false;
    }, [filters.period, filters.power_plant, filters.rake_number, filters.loader_id, filters.shift, filters.penalty_type, filters.siding_ids.length, sidings.length]);

    const activeFilterCount = useMemo(() => {
        let n = 0;
        if (filters.period !== 'today') n += 1;
        if (filters.power_plant) n += 1;
        if (filters.rake_number?.trim()) n += 1;
        if (filters.loader_id != null) n += 1;
        if (filters.shift) n += 1;
        if (filters.penalty_type != null) n += 1;
        if (sidings.length > 0 && filters.siding_ids.length > 0 && filters.siding_ids.length < sidings.length) n += 1;
        return n;
    }, [filters.period, filters.power_plant, filters.rake_number, filters.loader_id, filters.shift, filters.penalty_type, filters.siding_ids.length, sidings.length]);

    const sidingStackKeys = useMemo(() => filteredSidings.map((s) => s.name), [filteredSidings]);

    const kpiCards = sidings.length > 0 && kpis ? [
        { label: `Rakes dispatched ${periodLabel}`, value: String(kpis.rakesDispatchedToday), borderColor: '#3B82F6', Icon: Train },
        { label: `Coal dispatched ${periodLabel}`, value: formatWeight(kpis.coalDispatchedToday), borderColor: '#10B981', Icon: Flame },
        { label: `Penalty ${periodLabel}`, value: formatCurrency(kpis.totalPenaltyThisMonth), borderColor: '#EF4444', Icon: AlertTriangle },
        // Temporarily hidden per client request:
        // { label: 'Predicted penalty risk', value: formatCurrency(kpis.predictedPenaltyRisk), borderColor: '#F59E0B', Icon: TrendingUp },
        // { label: `Avg loading time (${periodLabel})`, value: kpis.avgLoadingTimeMinutes != null ? `${Math.floor(kpis.avgLoadingTimeMinutes / 60)}h ${kpis.avgLoadingTimeMinutes % 60}m` : '—', borderColor: '#8B5CF6', Icon: Clock },
        // { label: `Trucks received ${periodLabel}`, value: String(kpis.trucksReceivedToday), borderColor: '#14B8A6', Icon: Truck },
    ] : [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
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
                        {activeSection === 'executive-overview' && !!props.executiveYesterday && (
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
                        )}
                        <Select value={activeSection} onValueChange={setActiveSection}>
                            <SelectTrigger className="min-w-[200px] rounded-[10px] border border-gray-200 bg-white shadow-sm w-full sm:w-auto">
                                <SelectValue placeholder="Select section" />
                            </SelectTrigger>
                            <SelectContent>
                                {DASHBOARD_SECTIONS.map((s) => (
                                    <SelectItem key={s.id} value={s.id}>
                                        {s.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
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
                {filteredSidings.length > 0 && (
                    <div className="space-y-1">
                        <div className="flex items-center justify-between">
                            <p className="text-[10px] text-gray-500">Coal stock updates live from the ledger (and real-time events when connected).</p>
                        </div>
                        <div className="flex gap-2 overflow-x-auto pb-0.5 lg:grid lg:grid-cols-3 lg:gap-2 lg:overflow-visible">
                            {filteredSidings.map((s) => {
                                const stockMt = sidingStocks[s.id]?.closing_balance_mt ?? 0;
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
                                    <ExecutiveYesterdaySection data={executiveYesterday} viewMode={executiveYesterdayViewMode} />
                                ) : (
                                    <div className="dashboard-card rounded-xl border-0 p-6 text-sm text-gray-600">Yesterday data is not available.</div>
                                )}
                            </div>
                        )}

                        {activeSection === 'siding-overview' && (
                            <div className="space-y-6">
                                {sidingPerformance.length > 0 ? (
                                    <SidingPerformanceSection data={sidingPerformance} />
                                ) : (
                                    <div className="dashboard-card rounded-xl border-0 p-6">
                                        <SectionHeader icon={BarChart3} title="Siding performance" subtitle="Rakes, coal & penalty by siding" />
                                        <div className="mt-4 py-8 text-center text-sm text-gray-600">No performance data for selected filters.</div>
                                    </div>
                                )}
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
                            </div>
                        )}

                        {activeSection === 'operations' && (
                            <div className="space-y-6">
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
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
                                                    if (coalDate) {
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
                                                        <Button variant="outline" size="sm" disabled title="Select a date">
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
                                                <div className="dashboard-table-scroll mt-3 max-h-[420px] overflow-y-auto overflow-x-auto">
                                                    <table className="w-full border-separate text-sm" style={{ borderSpacing: 0 }}>
                                                        <thead className="sticky top-0 z-10 bg-[#369c7a] text-black">
                                                            <tr>
                                                                <th className="border border-[#1a3d2e] px-2 py-2 text-center font-medium" style={{ borderWidth: '1px' }}>Sl No</th>
                                                                <th className="border border-[#1a3d2e] px-2 py-2 text-center font-medium" style={{ borderWidth: '1px' }}>Shift</th>
                                                                {coalTransportReport.sidings.map((s) => (
                                                                    <th key={s.id} colSpan={2} className="border border-[#1a3d2e] px-2 py-2 text-center font-medium" style={{ borderWidth: '1px' }}>{s.name}</th>
                                                                ))}
                                                                <th colSpan={2} className="border border-[#1a3d2e] px-2 py-2 text-center font-medium" style={{ borderWidth: '1px' }}>Total</th>
                                                            </tr>
                                                            <tr>
                                                                <th className="border border-[#1a3d2e] px-2 py-1" style={{ borderWidth: '1px' }} />
                                                                <th className="border border-[#1a3d2e] px-2 py-1" style={{ borderWidth: '1px' }} />
                                                                {coalTransportReport.sidings.flatMap((s) => [<th key={`${s.id}-t`} className="border border-[#1a3d2e] px-2 py-1 text-center font-medium" style={{ borderWidth: '1px' }}>Trips</th>, <th key={`${s.id}-q`} className="border border-[#1a3d2e] px-2 py-1 text-center font-medium" style={{ borderWidth: '1px' }}>Qty</th>])}
                                                                <th className="border border-[#1a3d2e] px-2 py-1 text-center font-medium" style={{ borderWidth: '1px' }}>Trips</th>
                                                                <th className="border border-[#1a3d2e] px-2 py-1 text-center font-medium" style={{ borderWidth: '1px' }}>Qty</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            {coalTransportReport.rows.map((row) => (
                                                                <tr key={row.shift_label} className="bg-white text-[0.875rem]">
                                                                    <td className="border border-[#1a3d2e] px-2 py-2 tabular-nums" style={{ borderWidth: '1px' }}>{row.sl_no}</td>
                                                                    <td className="border border-[#1a3d2e] px-2 py-2 font-medium" style={{ borderWidth: '1px' }}>{row.shift_label}</td>
                                                                    {row.siding_metrics.map((m) => (
                                                                        <Fragment key={m.siding_name}>
                                                                            <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{m.trips}</td>
                                                                            <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{m.qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                                        </Fragment>
                                                                    ))}
                                                                    <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{row.total_trips}</td>
                                                                    <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{row.total_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                                </tr>
                                                            ))}
                                                            <tr className="bg-[#369c7a] font-bold text-black">
                                                                <td className="border border-[#1a3d2e] px-2 py-2" style={{ borderWidth: '1px' }} />
                                                                <td className="border border-[#1a3d2e] px-2 py-2" style={{ borderWidth: '1px' }}>TOTAL</td>
                                                                {coalTransportReport.totals.siding_metrics.map((m) => (
                                                                    <Fragment key={m.siding_name}>
                                                                        <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{m.trips}</td>
                                                                        <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{m.qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                                    </Fragment>
                                                                ))}
                                                                <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{coalTransportReport.totals.total_trips}</td>
                                                                <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{coalTransportReport.totals.total_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </>
                                        ) : (
                                            <div className="mt-4 py-8 text-center text-sm text-gray-600">No coal transport data.</div>
                                        )}
                                    </div>
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
                                                    {dailyRakeDetails.totals.day_rakes === 0 && dailyRakeDetails.rows.length > 0 && (
                                                        <span className="ml-2 text-amber-600">— No rake dispatches for this date</span>
                                                    )}
                                                </p>
                                                <div className="dashboard-table-scroll mt-3 max-h-[420px] overflow-y-auto overflow-x-auto">
                                                    <table className="w-full border-separate text-sm" style={{ borderSpacing: 0 }}>
                                                        <thead className="sticky top-0 z-10 bg-[#369c7a] text-black">
                                                            <tr>
                                                                <th className="border border-[#1a3d2e] px-2 py-2 text-center font-medium" style={{ borderWidth: '1px' }}>SL NO</th>
                                                                <th className="border border-[#1a3d2e] px-2 py-2 text-center font-medium" style={{ borderWidth: '1px' }}>SIDING</th>
                                                                <th colSpan={2} className="border border-[#1a3d2e] px-2 py-2 text-center font-medium" style={{ borderWidth: '1px' }}>DAY</th>
                                                                <th colSpan={2} className="border border-[#1a3d2e] px-2 py-2 text-center font-medium" style={{ borderWidth: '1px' }}>MONTH</th>
                                                                <th className="border border-[#1a3d2e] px-2 py-2 text-center font-medium" style={{ borderWidth: '1px' }}>FOR RAKE DAY/AVG</th>
                                                                <th className="border border-[#1a3d2e] px-2 py-2 text-center font-medium" style={{ borderWidth: '1px' }}>REMARKS</th>
                                                            </tr>
                                                            <tr>
                                                                <th className="border border-[#1a3d2e] px-2 py-1" style={{ borderWidth: '1px' }} />
                                                                <th className="border border-[#1a3d2e] px-2 py-1" style={{ borderWidth: '1px' }} />
                                                                <th className="border border-[#1a3d2e] px-2 py-1 text-center font-medium" style={{ borderWidth: '1px' }}>RAKES</th>
                                                                <th className="border border-[#1a3d2e] px-2 py-1 text-center font-medium" style={{ borderWidth: '1px' }}>QTY</th>
                                                                <th className="border border-[#1a3d2e] px-2 py-1 text-center font-medium" style={{ borderWidth: '1px' }}>RAKES</th>
                                                                <th className="border border-[#1a3d2e] px-2 py-1 text-center font-medium" style={{ borderWidth: '1px' }}>QTY</th>
                                                                <th className="border border-[#1a3d2e] px-2 py-1" style={{ borderWidth: '1px' }} />
                                                                <th className="border border-[#1a3d2e] px-2 py-1" style={{ borderWidth: '1px' }} />
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            {dailyRakeDetails.rows.map((r) => (
                                                                <tr key={r.siding_name} className="bg-white text-[0.875rem]">
                                                                    <td className="border border-[#1a3d2e] px-2 py-2 tabular-nums" style={{ borderWidth: '1px' }}>{r.sl_no}</td>
                                                                    <td className="border border-[#1a3d2e] px-2 py-2 font-medium" style={{ borderWidth: '1px' }}>{r.siding_name}</td>
                                                                    <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{r.day_rakes}</td>
                                                                    <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{r.day_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                                    <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{r.month_rakes}</td>
                                                                    <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{r.month_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                                    <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{r.rake_day_avg.toFixed(2)}</td>
                                                                    <td className="border border-[#1a3d2e] px-2 py-2 text-gray-600" style={{ borderWidth: '1px' }}>{r.remarks || '—'}</td>
                                                                </tr>
                                                            ))}
                                                            <tr className="bg-[#369c7a] font-bold text-black">
                                                                <td className="border border-[#1a3d2e] px-2 py-2" style={{ borderWidth: '1px' }} />
                                                                <td className="border border-[#1a3d2e] px-2 py-2" style={{ borderWidth: '1px' }}>TOTAL</td>
                                                                <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{dailyRakeDetails.totals.day_rakes}</td>
                                                                <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{dailyRakeDetails.totals.day_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                                <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{dailyRakeDetails.totals.month_rakes}</td>
                                                                <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{dailyRakeDetails.totals.month_qty.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                                <td className="border border-[#1a3d2e] px-2 py-2 text-right tabular-nums" style={{ borderWidth: '1px' }}>{dailyRakeDetails.totals.rake_day_avg.toFixed(2)}</td>
                                                                <td className="border border-[#1a3d2e] px-2 py-2" style={{ borderWidth: '1px' }} />
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </>
                                        ) : (
                                            <div className="mt-4 py-8 text-center text-sm text-gray-600">No siding selected or no data for this date.</div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}

                        {activeSection === 'operations' && (
                            <div className="space-y-6">
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
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
                                 <div className="dashboard-card rounded-xl border-0 p-5">
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <SectionHeader icon={Train} title="Live rake status" subtitle="Pending on siding — no weighment receipt yet" />
                                            <Button variant="outline" size="sm" className="rounded-lg" asChild>
                                                <Link href={rakesIndex().url} data-pan="dashboard-live-rakes-view-all">
                                                    View all
                                                </Link>
                                            </Button>
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
                                                                    Rake
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
                                                                    Loading time
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
                                                                        <span className="inline-flex items-center gap-2">
                                                                            <span
                                                                                className="inline-block size-2.5 rounded-full"
                                                                                style={{ backgroundColor: borderColor }}
                                                                            />
                                                                            <span>{row.rake_number}</span>
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
                                                                    <td className="py-3 tabular-nums px-2">{row.time_elapsed}</td>
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
                            </div>
                        )}

                        {activeSection === 'penalty-control' && (
                            <div className="space-y-6">
                                {/* Penalty type distribution (left) + Yesterday predicted penalties by siding (right) */}
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
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
                                </div>
                                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <div className="dashboard-card min-w-0 rounded-xl border-0 p-6">
                                        <SectionHeader icon={BarChart3} title="Penalty by siding" subtitle="Which siding causes most penalties" />
                                        {penaltyBySiding.length > 0 ? (
                                        <div className="mt-4">
                                            {(() => {
                                                const sorted = [...penaltyBySiding].sort((a, b) => b.total - a.total);
                                                const barColors = ['#DC2626', '#EA580C', '#CA8A04', '#65A30D', '#059669', '#0D9488', '#2563EB', '#7C3AED', '#C026D3', '#DB2777'];
                                                return (
                                                    <ResponsiveContainer width="100%" height={320}>
                                                        <RechartsBarChart
                                                            data={sorted}
                                                            margin={{ top: 8, right: 24, bottom: 24, left: 16 }}
                                                        >
                                                            <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                                            <XAxis dataKey="name" type="category" tick={{ fontSize: 11 }} interval={0} height={48} />
                                                            <YAxis type="number" tickFormatter={(v: number) => formatCurrency(v)} width={72} tick={{ fontSize: 11 }} />
                                                            <Tooltip formatter={(v: number | string | undefined) => formatCurrency(Number(v ?? 0))} />
                                                            <Bar dataKey="total" radius={[4, 4, 0, 0]} barSize={28} isAnimationActive>
                                                                <LabelList dataKey="total" position="top" formatter={(v: unknown) => formatCurrency(Number(v ?? 0))} />
                                                                {sorted.map((_, i) => (
                                                                    <Cell key={i} fill={barColors[i % barColors.length]} />
                                                                ))}
                                                            </Bar>
                                                        </RechartsBarChart>
                                                    </ResponsiveContainer>
                                                );
                                            })()}
                                            </div>
                                        ) : (
                                        <div className="mt-4 py-8 text-center text-sm text-gray-600">No sidings available for selected filters.</div>
                                        )}
                                    </div>
                                    <div className="grid grid-cols-1 gap-6">
                                   
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

                        {activeSection === 'rake-performance' && (
                            rakePerformance.length > 0
                                ? <RakePerformanceSection rakes={rakePerformance} />
                                : (
                                    <div className="dashboard-card rounded-xl border-0 p-6">
                                        <SectionHeader icon={Train} title="Rake-wise performance" subtitle="Top dispatched rakes" />
                                        <div className="mt-6 flex flex-col items-center justify-center py-10 text-center text-gray-600">
                                            <Train className="mb-3 h-10 w-10 opacity-30" />
                                            <p className="text-sm font-medium">No rake performance data available</p>
                                            <p className="mt-1 text-xs">Apply filters or wait for dispatch data.</p>
                                        </div>
                                    </div>
                                )
                        )}

                        {activeSection === 'loader-overload' && (
                            loaderOverloadTrends.loaders.length > 0
                                ? (
                                    <LoaderOverloadSection
                                        loaders={loaderOverloadTrends.loaders}
                                        monthly={loaderOverloadTrends.monthly}
                                    />
                                )
                                : (
                                    <div className="dashboard-card rounded-xl border-0 p-6">
                                        <SectionHeader icon={AlertTriangle} title="Loader-wise overloading trends" subtitle="Overload cases by loader" />
                                        <div className="mt-6 flex flex-col items-center justify-center py-10 text-center text-gray-600">
                                            <AlertTriangle className="mb-3 h-10 w-10 opacity-30" />
                                            <p className="text-sm font-medium">No loader data available</p>
                                            <p className="mt-1 text-xs">Apply filters or wait for weighment data.</p>
                                        </div>
                                    </div>
                                )
                        )}

                        {activeSection === 'power-plant' && (
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
