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
    X,
    Zap,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
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
    { id: 'operations', label: 'Operations control' },
    { id: 'penalty-control', label: 'Penalty control' },
    { id: 'siding-stock', label: 'Siding stock' },
    { id: 'rake-performance', label: 'Rake-wise performance' },
    { id: 'loader-overload', label: 'Loader-wise overloading trends' },
    { id: 'power-plant', label: 'Power plant wise dispatch' },
] as const;

const DEFAULT_DASHBOARD_SECTION = 'executive-overview';

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

interface DashboardAlert {
    id: number;
    type: string;
    title: string;
    severity: string;
    rake_id: number | null;
    siding_id: number | null;
    created_at: string;
}

interface LiveRakeStatusRow {
    rake_number: string;
    siding_name: string;
    state: string;
    time_elapsed: string;
    risk: string;
}

interface TruckReceiptHour {
    hour: string;
    label: string;
    count: number;
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
    filters?: DashboardFilters;
    filterOptions?: FilterOptions;
    kpis?: DashboardKpis;
    penaltyTrendDaily?: PenaltyTrendPoint[];
    penaltyByType?: PenaltyByTypePoint[];
    penaltyBySiding?: PenaltyBySidingPoint[];
    alerts?: DashboardAlert[];
    liveRakeStatus?: LiveRakeStatusRow[];
    truckReceiptTrend?: TruckReceiptHour[];
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

function SectionHeader({ icon: Icon, title, subtitle, action }: {
    icon: React.ComponentType<{ className?: string }>;
    title: string;
    subtitle?: string;
    action?: React.ReactNode;
}) {
    return (
        <div className="flex items-center justify-between gap-4">
            <div className="flex items-center gap-3">
                <div className="flex size-9 items-center justify-center rounded-xl bg-primary/10">
                    <Icon className="size-4.5 text-primary" />
                </div>
                <div>
                    <h3 className="text-base font-semibold">{title}</h3>
                    {subtitle && <p className="text-xs text-gray-400">{subtitle}</p>}
                </div>
            </div>
            {action}
        </div>
    );
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

function SidingStockSection({ sidings, stocks }: { sidings: SidingOption[]; stocks: Record<number, SidingStock> }) {
    return (
        <div className="dashboard-card rounded-xl border-0 p-6">
            <SectionHeader icon={BarChart3} title="Siding stock" subtitle="Current balance per siding" />
            <div className="mt-5 grid gap-6 sm:grid-cols-1 md:grid-cols-3">
                {sidings.map((s) => {
                    const st = stocks[s.id];
                    const currentBalance = st?.closing_balance_mt ?? 0;
                    const accent = SIDING_ACCENT[s.name] ?? '#6B7280';
                    const status = currentBalance === 0 ? 'empty' : currentBalance < 1000 ? 'low' : 'sufficient';
                    return (
                        <div
                            key={s.id}
                            className="dashboard-card flex flex-1 flex-col rounded-xl border-0 p-5 transition-shadow"
                            style={{
                                borderTop: `4px solid ${accent}`,
                                background: `linear-gradient(to bottom, ${accent}08 0%, transparent 100%)`,
                            }}
                        >
                            <h4 className="text-[1.25rem] font-bold text-gray-900">{s.name}</h4>
                            <p className="mt-3 text-[3rem] font-extrabold tabular-nums leading-none text-gray-900">
                                {currentBalance === 0 ? '--' : `${currentBalance.toLocaleString(undefined, { maximumFractionDigits: 0 })} MT`}
                            </p>
                            <p className="mt-1 text-xs text-gray-400">Current balance</p>
                            <div className="mt-3 flex items-center gap-2">
                                <span className={`size-2 rounded-full ${status === 'sufficient' ? 'bg-green-500' : status === 'low' ? 'bg-amber-500' : 'bg-red-500'}`} />
                                <span className={`text-xs font-medium ${status === 'sufficient' ? 'text-green-700' : status === 'low' ? 'text-amber-700' : 'text-red-700'}`}>
                                    {status === 'sufficient' ? 'Sufficient' : status === 'low' ? 'Low stock' : 'Empty'}
                                </span>
                            </div>
                            <p className="mt-auto pt-4 text-xs text-gray-400">Last updated: 5 mins ago</p>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

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
        () => data.map((s) => ({ ...s, name: s.name, rakes: s.rakes, penalties: s.penalties, penalty_amount: s.penalty_amount, penalty_rate: s.penalty_rate })),
        [data],
    );

    const maxPenaltyAmount = useMemo(
        () => Math.max(...data.map((s) => s.penalty_amount ?? 0), 1),
        [data],
    );

    return (
        <div className="dashboard-card rounded-xl border-0 p-6">
            <SectionHeader icon={BarChart3} title="Siding performance" subtitle="Rakes, penalties & penalty rate" />

            <div className="mt-5 grid gap-6 lg:grid-cols-2">
                <div>
                    <p className="mb-2 text-xs font-medium text-gray-400">Rakes dispatched vs penalties</p>
                    <ResponsiveContainer width="100%" height={260}>
                        <RechartsBarChart data={chartData} layout="horizontal" margin={{ top: 8, right: 16, bottom: 0, left: 16 }}>
                            <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                            <XAxis dataKey="name" tick={{ fontSize: 12 }} />
                            <YAxis allowDecimals={false} tick={{ fontSize: 12 }} />
                            <Tooltip formatter={(value: number | string | undefined) => Number(value ?? 0).toLocaleString()} />
                            <Legend />
                            <Bar dataKey="rakes" name="Rakes dispatched" fill="#3B82F6" barSize={14} radius={[4, 4, 0, 0]} isAnimationActive />
                            <Bar dataKey="penalties" name="Penalties" fill="#EF4444" barSize={14} radius={[4, 4, 0, 0]} isAnimationActive>
                                <LabelList dataKey="penalties" position="right" />
                            </Bar>
                        </RechartsBarChart>
                    </ResponsiveContainer>
                </div>
                <div>
                    <p className="mb-2 text-xs font-medium text-gray-400">Penalty amount by siding</p>
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
                    <div className="mt-3 flex flex-wrap gap-4 text-sm text-gray-500">
                        {data.slice().sort((a, b) => b.penalty_amount - a.penalty_amount).map((s) => (
                            <div key={s.name} className="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-800">
                                <span>{s.name}:</span>
                                <span className="font-semibold tabular-nums">{formatCurrency(s.penalty_amount)}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            <div className="mt-6">
                <p className="mb-3 text-xs font-medium text-gray-400">Penalty rate by siding</p>
                <div className="space-y-3">
                    {data.map((s) => (
                        <div key={s.name} className="group">
                            <div className="mb-1 flex items-center justify-between text-sm">
                                <span className="font-medium">{s.name}</span>
                                <span className="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold tabular-nums text-blue-700">{s.penalty_rate}%</span>
                            </div>
                            <div className="h-3 w-full overflow-hidden rounded-full bg-gray-100">
                                <div
                                    className="h-full rounded-full bg-gradient-to-r from-[#3B82F6] to-[#60A5FA] transition-all duration-500"
                                    style={{ width: `${Math.min(s.penalty_rate, 100)}%` }}
                                />
                            </div>
                            <div className="mt-0.5 flex justify-between text-xs text-gray-400 opacity-0 transition-opacity group-hover:opacity-100">
                                <span>{s.rakes} rakes, {s.penalties} penalties</span>
                                <span className="tabular-nums">{formatCurrency(s.penalty_amount)}</span>
                            </div>
                        </div>
                    ))}
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
                    <p className="text-xs font-medium text-gray-400">Siding</p>
                    <p className="mt-1 font-bold text-gray-900">{r.siding}</p>
                </div>
                <div className="rounded-lg border border-gray-100 bg-gray-50/50 p-3">
                    <p className="text-xs font-medium text-gray-400">Dispatch date</p>
                    <p className="mt-1 font-bold tabular-nums text-gray-900">{r.dispatch_date}</p>
                </div>
                <div className="rounded-lg border border-gray-100 bg-gray-50/50 p-3">
                    <p className="text-xs font-medium text-gray-400">Wagons</p>
                    <p className="mt-1 font-bold tabular-nums text-gray-900">{r.wagon_count ?? '—'}</p>
                </div>
                <div className="rounded-lg border border-gray-100 bg-gray-50/50 p-3">
                    <p className="text-xs font-medium text-gray-400">Net weight</p>
                    <p className="mt-1 font-bold tabular-nums text-gray-900">{r.net_weight != null ? formatWeight(r.net_weight) : '—'}</p>
                </div>
                <div className="rounded-lg border border-gray-100 bg-gray-50/50 p-3">
                    <p className="text-xs font-medium text-gray-400">Loading time</p>
                    <p className="mt-1 font-bold tabular-nums text-gray-900">{loadingHours != null ? `${loadingHours}h ${loadingMins}m` : '—'}</p>
                </div>
                <div className={`rounded-lg border p-3 ${r.penalty_amount > 0 ? 'border-red-100 bg-red-50' : 'border-green-100 bg-green-50'}`}>
                    <p className="text-xs font-medium text-gray-400">Penalty</p>
                    <p className={`mt-1 font-bold tabular-nums ${r.penalty_amount > 0 ? 'text-red-700' : 'text-green-700'}`}>
                        {r.penalty_amount > 0 ? formatCurrency(r.penalty_amount) : 'None'}
                    </p>
                </div>
            </div>

            <div className="mt-5 grid gap-5 lg:grid-cols-2">
                <div>
                    <p className="mb-2 text-xs font-medium text-gray-400">Weight breakdown (MT)</p>
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
                        <div className="flex h-[220px] items-center justify-center rounded-lg border border-gray-100 bg-gray-50/50 text-sm text-gray-500">
                            No weighment data available
                        </div>
                    )}
                </div>
                <div>
                    <p className="mb-2 text-xs font-medium text-gray-400">Overload status</p>
                    <div className={`flex h-[220px] flex-col items-center justify-center gap-3 rounded-xl p-6 ${r.over_load != null && r.over_load > 0 ? 'bg-[#FEF2F2]' : 'bg-green-50'}`}>
                        {r.over_load != null && r.over_load > 0 ? (
                            <>
                                <TriangleAlert className="size-14 text-red-600" aria-hidden />
                                <span className="text-2xl font-bold tabular-nums text-red-700">+{r.over_load.toLocaleString()} MT</span>
                                <span className="text-sm font-medium text-red-700">Overloaded</span>
                            </>
                        ) : (
                            <>
                                <Check className="size-14 text-green-600" aria-hidden />
                                <span className="text-xl font-bold text-green-700">Within limits</span>
                                <span className="text-xs text-gray-500">No overloading detected</span>
                            </>
                        )}
                    </div>
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
                <div className="mt-8 flex flex-col items-center justify-center py-12 text-center text-gray-500">
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
                            <p className="mb-2 text-xs font-medium text-gray-400">Total wagons vs overloaded (monthly)</p>
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
                            <p className="mb-2 text-xs font-medium text-gray-400">Overloaded wagons per month</p>
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
                <div className="mt-6 flex flex-col items-center justify-center py-10 text-center text-gray-500">
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
                        <div className="text-xs font-medium text-gray-400">Destinations</div>
                        <div className="text-xl font-bold tabular-nums text-gray-900">{data.length}</div>
                    </div>
                </div>
                <div className="dashboard-card flex items-center gap-3 rounded-xl border-0 p-4" style={{ borderTop: '4px solid #3B82F6' }}>
                    <Train className="size-5 text-blue-600" />
                    <div>
                        <div className="text-xs font-medium text-gray-400">Total rakes</div>
                        <div className="text-xl font-bold tabular-nums text-gray-900">{totalRakes}</div>
                    </div>
                </div>
                <div className="dashboard-card flex items-center gap-3 rounded-xl border-0 p-4" style={{ borderTop: '4px solid #3B82F6' }}>
                    <BarChart3 className="size-5 text-blue-600" />
                    <div>
                        <div className="text-xs font-medium text-gray-400">Total weight</div>
                        <div className="text-xl font-bold tabular-nums text-gray-900">{formatWeight(totalWeight)}</div>
                    </div>
                </div>
                <div className="dashboard-card flex items-center gap-3 rounded-xl border-0 p-4" style={{ borderTop: '4px solid #3B82F6' }}>
                    <Zap className="size-5 text-blue-600" />
                    <div>
                        <div className="text-xs font-medium text-gray-400">Avg per destination</div>
                        <div className="text-xl font-bold tabular-nums text-gray-900">{data.length > 0 ? formatWeight(totalWeight / data.length) : '—'}</div>
                    </div>
                </div>
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <div>
                    <div className="mb-2 flex items-center justify-between">
                        <h4 className="text-xs font-medium text-gray-400">Rakes sent to each power plant by siding</h4>
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
                    <h4 className="mb-2 text-xs font-medium text-gray-400">Rakes dispatched per power plant</h4>
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

            <div className="mt-6 space-y-3">
                <h4 className="text-sm font-semibold text-gray-500">Destination breakdown</h4>
                {data.map((pp, i) => {
                    const color = PLANT_COLORS[pp.name] ?? SIDING_COLORS[i % SIDING_COLORS.length];
                    const isExpanded = expandedIdx === i;
                    const sidingEntries = Object.entries(pp.sidings);
                    const maxSidingRakes = Math.max(...sidingEntries.map(([, info]) => info.rakes), 1);
                    return (
                        <div
                            key={pp.name}
                            className="dashboard-card group rounded-xl border-0 p-4 transition-all hover:shadow-md"
                            style={{ borderLeft: `4px solid ${color}` }}
                        >
                            <button
                                type="button"
                                onClick={() => setExpandedIdx(isExpanded ? null : i)}
                                className="flex w-full items-center justify-between text-left"
                            >
                                <div className="flex items-center gap-3">
                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white" style={{ backgroundColor: color }}>
                                        {pp.name.slice(0, 2).toUpperCase()}
                                    </span>
                                    <span className="text-sm font-semibold text-gray-900">{pp.name}</span>
                                </div>
                                <div className="flex items-center gap-3">
                                    <span className="text-xs tabular-nums text-gray-500">{pp.rakes} rakes</span>
                                    <span className="text-xs tabular-nums text-gray-500">{formatWeight(pp.weight_mt)}</span>
                                    {isExpanded ? <ChevronUp className="size-4 text-gray-400" /> : <ChevronDown className="size-4 text-gray-400" />}
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
                                <div className="mt-4 border-t border-gray-100 pt-4">
                                    <div className="flex gap-4 overflow-x-auto pb-2">
                                        {sidingEntries.map(([sidingName, info], si) => (
                                            <div key={sidingName} className="flex min-w-[100px] flex-col">
                                                <span className="text-xs font-medium text-gray-600">{sidingName}</span>
                                                <div className="mt-1 h-8 w-full overflow-hidden rounded bg-gray-100">
                                                    <div
                                                        className="h-full rounded bg-blue-500 transition-[width] duration-700 ease-out"
                                                        style={{ width: `${(info.rakes / maxSidingRakes) * 100}%`, backgroundColor: SIDING_COLORS[si % SIDING_COLORS.length] }}
                                                    />
                                                </div>
                                                <span className="mt-0.5 text-xs tabular-nums text-gray-500">{info.rakes} rakes, {formatWeight(info.weight_mt)}</span>
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
    );
}

function DashboardFiltersBar({
    sidings,
    filters,
    filterOptions,
    inline = false,
    onClose,
}: {
    sidings: SidingOption[];
    filters: DashboardFilters;
    filterOptions: FilterOptions;
    inline?: boolean;
    onClose?: () => void;
}) {
    const [customFrom, setCustomFrom] = useState(filters.from);
    const [customTo, setCustomTo] = useState(filters.to);
    const [showSidingDropdown, setShowSidingDropdown] = useState(false);
    const [rakeNumberInput, setRakeNumberInput] = useState(filters.rake_number ?? '');
    useEffect(() => {
        setRakeNumberInput(filters.rake_number ?? '');
    }, [filters.rake_number]);
    useEffect(() => {
        setCustomFrom(filters.from);
        setCustomTo(filters.to);
    }, [filters.from, filters.to]);

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
            period: overrides.period ?? filters.period,
            ...overrides,
        };

        if (params.period === 'custom') {
            params.from = overrides.from ?? customFrom;
            params.to = overrides.to ?? customTo;
        } else {
            // Never send date range when period is not custom, so backend uses its default (e.g. current month).
            delete params.from;
            delete params.to;
        }

        const sidingIds = (overrides.siding_ids as number[] | undefined) ?? filters.siding_ids;
        if (sidingIds.length > 0 && sidingIds.length < allSidingIds.length) {
            params.siding_ids = sidingIds;
        }

        const powerPlant = (overrides.power_plant !== undefined ? overrides.power_plant : filters.power_plant) ?? '';
        if (powerPlant !== '') params.power_plant = powerPlant;
        const rakeNumber = (overrides.rake_number !== undefined ? overrides.rake_number : filters.rake_number) ?? '';
        if (rakeNumber !== '') params.rake_number = rakeNumber;
        const loaderId = (overrides.loader_id !== undefined ? overrides.loader_id : filters.loader_id) ?? '';
        if (loaderId !== '') params.loader_id = loaderId;
        const shift = (overrides.shift !== undefined ? overrides.shift : filters.shift) ?? '';
        if (shift !== '') params.shift = shift;
        const penaltyType = (overrides.penalty_type !== undefined ? overrides.penalty_type : filters.penalty_type) ?? null;
        if (penaltyType != null) params.penalty_type = penaltyType;

        // Use pathname only so query is exactly our params (no merge with current URL).
        const dashboardPath = dashboard().url.split('?')[0] || dashboard().url;
        router.get(dashboardPath, params as Record<string, string>, {
            preserveState: true,
            preserveScroll: true,
        });
    }, [filters, customFrom, customTo, allSidingIds]);

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
                <span className="flex shrink-0 items-center gap-1.5 text-[11px] font-medium text-gray-500">
                    <Filter className="size-3.5" />
                    Filters
                </span>
            )}
            {/* Period: dropdown when inline, pills when not */}
            {inline ? (
                <Select value={filters.period} onValueChange={(v) => applyFilters({ period: v })}>
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
                            onClick={() => applyFilters({ period: p.key })}
                            className={
                                'rounded-full px-3 py-1.5 text-[11px] font-medium transition-colors ' +
                                (filters.period === p.key
                                    ? 'bg-[#111827] text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200')
                            }
                        >
                            {p.label}
                        </button>
                    ))}
                </div>
            )}
            {filters.period === 'custom' && (
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
                    <Select
                        value={filters.power_plant ?? ALL_FILTER_VALUE}
                        onValueChange={(v) => applyFilters({ power_plant: v === ALL_FILTER_VALUE ? null : v })}
                    >
                        <SelectTrigger className="h-7 w-[120px] rounded-md border text-[11px]">
                            <SelectValue placeholder="Plant" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_FILTER_VALUE} className="text-xs">All plants</SelectItem>
                            {filterOptions.powerPlants.map((pp) => (
                                <SelectItem key={pp.value} value={pp.value} className="text-xs">
                                    {pp.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <input
                        type="text"
                        placeholder="Rake #"
                        value={rakeNumberInput}
                        onChange={(e) => setRakeNumberInput(e.target.value)}
                        onBlur={() => applyFilters({ rake_number: rakeNumberInput.trim() || null })}
                        onKeyDown={(e) => e.key === 'Enter' && applyFilters({ rake_number: rakeNumberInput.trim() || null })}
                        className="h-7 w-20 rounded-md border bg-background px-2 text-[11px]"
                    />
                    <Select
                        value={filters.loader_id != null ? String(filters.loader_id) : ALL_FILTER_VALUE}
                        onValueChange={(v) => applyFilters({ loader_id: v === ALL_FILTER_VALUE ? null : Number(v) })}
                    >
                        <SelectTrigger className="h-7 w-[120px] rounded-md border text-[11px]">
                            <SelectValue placeholder="Loader" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_FILTER_VALUE} className="text-xs">All loaders</SelectItem>
                            {filterOptions.loaders.map((l) => (
                                <SelectItem key={l.id} value={String(l.id)} className="text-xs">
                                    {l.name} ({l.siding_name})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.shift ?? ALL_FILTER_VALUE}
                        onValueChange={(v) => applyFilters({ shift: v === ALL_FILTER_VALUE ? null : v })}
                    >
                        <SelectTrigger className="h-7 w-[72px] rounded-md border text-[11px]">
                            <SelectValue placeholder="Shift" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_FILTER_VALUE} className="text-xs">All</SelectItem>
                            {filterOptions.shifts.map((s) => (
                                <SelectItem key={s.value} value={s.value} className="text-xs">
                                    {s.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select
                        value={filters.penalty_type != null ? String(filters.penalty_type) : ALL_FILTER_VALUE}
                        onValueChange={(v) => applyFilters({ penalty_type: v === ALL_FILTER_VALUE ? null : Number(v) })}
                    >
                        <SelectTrigger className="h-7 w-[130px] rounded-md border text-[11px]">
                            <SelectValue placeholder="Penalty type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_FILTER_VALUE} className="text-xs">All types</SelectItem>
                            {(filterOptions.penaltyTypes ?? []).map((pt) => (
                                <SelectItem key={pt.value} value={pt.value} className="text-xs">
                                    {pt.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
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
    const [activeSection, setActiveSection] = useState<string>(DEFAULT_DASHBOARD_SECTION);
    const [alertsOpen, setAlertsOpen] = useState(false);
    const [filtersExpanded, setFiltersExpanded] = useState(false);
    const sidings = props.sidings ?? [];
    const defaultFilters: DashboardFilters = {
        period: 'month',
        from: '',
        to: '',
        siding_ids: [],
        power_plant: null,
        rake_number: null,
        loader_id: null,
        shift: null,
        penalty_type: null,
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
                return 'this month';
        }
    }, [filters.period, filters.from, filters.to]);

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

    const filterOptions = props.filterOptions ?? { powerPlants: [], loaders: [], shifts: [], penaltyTypes: [] };
    const kpis = props.kpis;
    const penaltyTrendDaily = props.penaltyTrendDaily ?? [];
    const penaltyByType = props.penaltyByType ?? [];
    const penaltyBySiding = props.penaltyBySiding ?? [];
    const alerts = props.alerts ?? [];
    const liveRakeStatus = props.liveRakeStatus ?? [];
    const truckReceiptTrend = props.truckReceiptTrend ?? [];
    const stockGauge = props.stockGauge;
    const predictedVsActualPenalty = props.predictedVsActualPenalty ?? { predicted: 0, actual: 0, bySiding: [] };
    const sidingStocks = props.sidingStocks ?? {};
    const sidingPerformance = props.sidingPerformance ?? [];
    const sidingWiseMonthly = props.sidingWiseMonthly ?? [];
    const sidingRadar = props.sidingRadar ?? { sidings: [] };
    const dateWiseDispatch = props.dateWiseDispatch ?? { sidingNames: {}, dates: [] };
    const rakePerformance = props.rakePerformance ?? [];
    const loaderOverloadTrends = props.loaderOverloadTrends ?? { loaders: [], monthly: [] };
    const powerPlantDispatch = props.powerPlantDispatch ?? [];

    const filteredSidings = useMemo(() => {
        if (filters.siding_ids.length === 0 || filters.siding_ids.length === sidings.length) {
            return sidings;
        }
        const idSet = new Set(filters.siding_ids);
        return sidings.filter((s) => idSet.has(s.id));
    }, [sidings, filters.siding_ids]);

    const sidingStackKeys = useMemo(() => filteredSidings.map((s) => s.name), [filteredSidings]);

    const kpiCards = sidings.length > 0 && kpis ? [
        { label: `Rakes dispatched ${periodLabel}`, value: String(kpis.rakesDispatchedToday), borderColor: '#3B82F6', Icon: Train },
        { label: `Coal dispatched ${periodLabel}`, value: formatWeight(kpis.coalDispatchedToday), borderColor: '#10B981', Icon: Flame },
        { label: `Penalty ${periodLabel}`, value: formatCurrency(kpis.totalPenaltyThisMonth), borderColor: '#EF4444', Icon: AlertTriangle },
        { label: 'Predicted penalty risk', value: formatCurrency(kpis.predictedPenaltyRisk), borderColor: '#F59E0B', Icon: TrendingUp },
        { label: `Avg loading time (${periodLabel})`, value: kpis.avgLoadingTimeMinutes != null ? `${Math.floor(kpis.avgLoadingTimeMinutes / 60)}h ${kpis.avgLoadingTimeMinutes % 60}m` : '—', borderColor: '#8B5CF6', Icon: Clock },
        { label: `Trucks received ${periodLabel}`, value: String(kpis.trucksReceivedToday), borderColor: '#14B8A6', Icon: Truck },
    ] : [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="dashboard-page flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-[#FAFAFA] p-3">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold tracking-tight">
                        Management Dashboard
                    </h2>
                    <div className="flex flex-wrap items-center justify-end gap-2">
                        {filtersExpanded && sidings.length > 0 && (
                            <DashboardFiltersBar
                                sidings={sidings}
                                filters={filters}
                                filterOptions={filterOptions}
                                inline
                                onClose={() => setFiltersExpanded(false)}
                            />
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
                        <Button
                            type="button"
                            variant={filtersExpanded ? 'secondary' : 'outline'}
                            size="sm"
                            className="shrink-0 rounded-[10px]"
                            onClick={() => setFiltersExpanded((v) => !v)}
                        >
                            <Filter className="size-4 shrink-0" />
                            <span className="ml-1.5">Filters</span>
                        </Button>
                    </div>
                </div>

                {kpiCards.length > 0 && (
                    <div className="flex gap-4 overflow-x-auto pb-1 lg:grid lg:grid-cols-3 lg:overflow-visible xl:grid-cols-6">
                        {kpiCards.map(({ label, value, borderColor, Icon }) => (
                            <div
                                key={label}
                                className="dashboard-card flex min-w-[160px] flex-1 flex-col justify-between rounded-xl border-0 p-4 sm:min-w-0"
                                style={{ borderTop: `4px solid ${borderColor}` }}
                            >
                                <div className="text-[0.7rem] font-semibold text-gray-500 leading-snug">{label}</div>
                                <div className="mt-2 flex items-center justify-between gap-3">
                                    <span className="truncate text-[1.85rem] font-extrabold tabular-nums leading-tight">
                                        {value}
                                    </span>
                                    <Icon className="size-6 shrink-0 text-gray-300" aria-hidden />
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {sidings.length === 0 ? (
                    <div className="dashboard-card rounded-xl border-0 p-8 text-center text-sm text-gray-500">
                        <p>No sidings assigned to your account. Contact your administrator to get access.</p>
                    </div>
                ) : (
                    <>
                    <div className="min-w-0 space-y-6">
                        {activeSection === 'executive-overview' && (
                            <div className="space-y-6">
                                {sidingPerformance.length > 0 ? (
                                    <SidingPerformanceSection data={sidingPerformance} />
                                ) : (
                                    <div className="dashboard-card rounded-xl border-0 p-6">
                                        <SectionHeader icon={BarChart3} title="Siding performance" subtitle="Rakes, coal & penalty by siding" />
                                        <div className="mt-4 py-8 text-center text-sm text-gray-500">No performance data for selected filters.</div>
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
                                        <div className="mt-4 py-8 text-center text-sm text-gray-500">No penalty data for selected period.</div>
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
                                                    <span className="text-xs font-medium text-gray-400">Total</span>
                                                    <span className="text-lg font-bold tabular-nums text-gray-800">{formatWeight(totalWeight)}</span>
                                                </div>
                                            </div>
                                        );
                                    })() : (
                                        <div className="mt-4 py-8 text-center text-sm text-gray-500">No power plant dispatch data.</div>
                                    )}
                                </div>
                            </div>
                        )}

                        {activeSection === 'operations' && (
                            <div className="space-y-6">
                                <div className="dashboard-card rounded-xl border-0 p-5">
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <SectionHeader icon={Train} title="Live rake status" subtitle="Active rakes (not yet dispatched)" />
                                        <Button variant="outline" size="sm" className="rounded-lg" asChild>
                                            <Link href={rakesIndex().url} data-pan="dashboard-live-rakes-view-all">View all</Link>
                                        </Button>
                                    </div>
                                    <p className="mt-1 text-xs text-gray-400">
                                        Last updated: Just now
                                        {liveRakeStatus.length > 0 && (
                                            <span className="ml-2 font-medium text-gray-500">
                                                • {liveRakeStatus.length} active rake{liveRakeStatus.length === 1 ? '' : 's'}
                                            </span>
                                        )}
                                    </p>
                                    {liveRakeStatus.length > 0 ? (
                                        <div className="dashboard-table-scroll mt-4 max-h-[520px] overflow-y-auto overflow-x-auto">
                                            <table className="w-full text-sm">
                                                <thead className="sticky top-0 z-10 bg-white shadow-sm">
                                                    <tr className="border-b text-left text-gray-500">
                                                        <th className="group cursor-pointer pb-3 pl-4 pr-2 pt-2 font-medium"><span className="inline-flex items-center gap-1">Rake <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" /></span></th>
                                                        <th className="group cursor-pointer pb-3 px-2 pt-2 font-medium"><span className="inline-flex items-center gap-1">Siding <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" /></span></th>
                                                        <th className="group cursor-pointer pb-3 px-2 pt-2 font-medium"><span className="inline-flex items-center gap-1">Status <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" /></span></th>
                                                        <th className="group cursor-pointer pb-3 px-2 pt-2 font-medium"><span className="inline-flex items-center gap-1">Time elapsed <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" /></span></th>
                                                        <th className="group cursor-pointer pb-3 pr-4 pl-2 pt-2 font-medium"><span className="inline-flex items-center gap-1">Risk <ArrowUp className="size-3.5 opacity-0 group-hover:opacity-50" /></span></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {liveRakeStatus.map((row, i) => {
                                                        const statusVariant = row.state === 'completed' ? 'completed' : row.state === 'loading' || row.state === 'in-progress' ? 'in-progress' : 'pending';
                                                        const riskVariant = row.risk === 'penalty_risk' ? 'high' : row.risk === 'attention' ? 'medium' : 'normal';
                                                        const borderColor = riskVariant === 'high' ? '#DC2626' : riskVariant === 'medium' ? '#F59E0B' : '#E5E7EB';
                                                        return (
                                                            <tr
                                                                key={i}
                                                                className="border-b text-[0.875rem] last:border-0"
                                                                style={{ backgroundColor: i % 2 === 1 ? '#F9FAFB' : undefined, borderLeft: `3px solid ${borderColor}` }}
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
                                                                    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                                        statusVariant === 'completed' ? 'bg-[#DCFCE7] text-[#16A34A]' :
                                                                        statusVariant === 'in-progress' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600'
                                                                    }`}>
                                                                        {statusVariant === 'completed' ? 'Completed' : statusVariant === 'in-progress' ? 'In progress' : row.state || 'Pending'}
                                                                    </span>
                                                                </td>
                                                                <td className="py-3 tabular-nums px-2">{row.time_elapsed}</td>
                                                                <td className="py-3 pr-4 pl-2">
                                                                    {riskVariant === 'high' ? (
                                                                        <span className="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">High</span>
                                                                    ) : riskVariant === 'medium' ? (
                                                                        <span className="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Medium</span>
                                                                    ) : (
                                                                        <span className="text-gray-500">Normal</span>
                                                                    )}
                                                                </td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                            </table>
                                        </div>
                                    ) : (
                                        <div className="mt-4 py-8 text-center text-sm text-gray-500">No active rakes.</div>
                                    )}
                                </div>
                            </div>
                        )}

                        {activeSection === 'operations' && (
                            <div className="space-y-6">
                                <div className="dashboard-card rounded-xl border-0 p-5">
                                    <SectionHeader icon={BarChart3} title="Truck receipt trend" subtitle="Trips per hour (today)" />
                                    {truckReceiptTrend.length > 0 ? (
                                        <ResponsiveContainer width="100%" height={260}>
                                            <RechartsBarChart data={truckReceiptTrend} margin={{ top: 8, right: 8, left: 8, bottom: 0 }}>
                                                <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                                <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                                                <YAxis tick={{ fontSize: 11 }} />
                                                <Tooltip formatter={(v: number | undefined) => `Trips: ${v ?? 0}`} />
                                                <Bar dataKey="count" fill="#3B82F6" radius={[4, 4, 0, 0]} barSize={24} isAnimationActive />
                                            </RechartsBarChart>
                                        </ResponsiveContainer>
                                    ) : (
                                        <div className="mt-4 py-8 text-center text-sm text-gray-500">No truck receipt data for today.</div>
                                    )}
                                </div>
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
                            </div>
                        )}

                        {activeSection === 'penalty-control' && (
                            <div className="space-y-6">
                                {/* Penalty type distribution + Penalty amount by type: two equal columns first */}
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
                                                            <span className="text-xs text-gray-400">Total</span>
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
                                            <div className="mt-4 py-8 text-center text-sm text-gray-500">No penalty type data.</div>
                                        )}
                                    </div>
                                    <div className="dashboard-card min-w-0 rounded-xl border-0 p-6">
                                        <SectionHeader icon={BarChart3} title="Penalty amount by type" subtitle="Amount by penalty type" />
                                        {penaltyByType.length > 0 ? (() => {
                                            const typeColors: Record<string, string> = { Demurrage: '#DC2626', Overloading: '#F59E0B', Wharfage: '#8B5CF6' };
                                            return (
                                                <div className="mt-4">
                                                    <ResponsiveContainer width="100%" height={280}>
                                                        <RechartsBarChart
                                                            data={penaltyByType}
                                                            margin={{ top: 8, right: 16, bottom: 24, left: 16 }}
                                                        >
                                                            <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                                            <XAxis dataKey="name" type="category" tick={{ fontSize: 11 }} />
                                                            <YAxis type="number" tickFormatter={(v: number) => formatCurrency(v)} width={64} tick={{ fontSize: 10 }} />
                                                            <Tooltip formatter={(v: number | string | undefined) => formatCurrency(Number(v ?? 0))} />
                                                            <Bar dataKey="value" radius={[4, 4, 0, 0]} barSize={36} isAnimationActive>
                                                                <LabelList dataKey="value" position="top" formatter={(v: unknown) => formatCurrency(Number(v ?? 0))} />
                                                                {penaltyByType.map((entry, i) => (
                                                                    <Cell key={i} fill={typeColors[entry.name] ?? ['#64748b', '#94a3b8'][i % 2]} />
                                                                ))}
                                                            </Bar>
                                                        </RechartsBarChart>
                                                    </ResponsiveContainer>
                                                </div>
                                            );
                                        })() : (
                                            <div className="mt-4 py-8 text-center text-sm text-gray-500">No penalty type data.</div>
                                        )}
                                    </div>
                                </div>
                                <div className="dashboard-card rounded-xl border-0 p-6">
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
                                        <div className="mt-4 py-8 text-center text-sm text-gray-500">No sidings available for selected filters.</div>
                                    )}
                                </div>
                                <div className="dashboard-card rounded-xl border-0 p-6">
                                    <SectionHeader icon={BarChart3} title="Predicted vs actual penalty" subtitle="All sidings comparison" />
                                    <div className="mt-3 flex flex-wrap items-center justify-center gap-4 rounded-lg border border-gray-200 bg-gray-50/80 px-4 py-2 text-sm">
                                        <span className="flex items-center gap-2">
                                            <span className="font-medium text-gray-600">Total Predicted:</span>
                                            <span className="tabular-nums font-bold text-blue-700">{formatCurrency(predictedVsActualPenalty.predicted)}</span>
                                        </span>
                                        <span className="flex items-center gap-2">
                                            <span className="font-medium text-gray-600">Total Actual:</span>
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
                                                    <Bar dataKey="predicted" name="Predicted" fill="#3B82F6" radius={[4, 4, 0, 0]} barSize={28} isAnimationActive />
                                                    <Bar dataKey="actual" name="Actual" fill="#DC2626" radius={[4, 4, 0, 0]} barSize={28} isAnimationActive />
                                                </RechartsBarChart>
                                            </ResponsiveContainer>
                                        </div>
                                    ) : (
                                        <div className="mt-4 py-8 text-center text-sm text-gray-500">No predicted or actual penalty data for the selected period.</div>
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
                        )}

                        {activeSection === 'siding-performance' && (
                            sidingPerformance.length > 0 ? (
                                <SidingPerformanceSection data={sidingPerformance} />
                            ) : (
                                    <div className="dashboard-card rounded-xl border-0 p-6">
                                        <SectionHeader icon={BarChart3} title="Siding performance" subtitle="Rakes, penalties & penalty rate" />
                                        <div className="mt-6 flex flex-col items-center justify-center py-10 text-center text-gray-500">
                                            <BarChart3 className="mb-3 h-10 w-10 opacity-30" />
                                            <p className="text-sm font-medium">No data available</p>
                                            <p className="mt-1 text-xs">Apply filters or wait for dispatch data.</p>
                                        </div>
                                    </div>
                                )
                        )}

                        {activeSection === 'siding-stock' && (
                            Object.keys(sidingStocks).length > 0
                                ? <SidingStockSection sidings={filteredSidings} stocks={sidingStocks} />
                                : (
                                    <div className="dashboard-card rounded-xl border-0 p-6">
                                        <SectionHeader icon={BarChart3} title="Siding stock" subtitle="Opening & closing balance with total rakes" />
                                        <div className="mt-6 flex flex-col items-center justify-center py-10 text-center text-gray-500">
                                            <BarChart3 className="mb-3 h-10 w-10 opacity-30" />
                                            <p className="text-sm font-medium">No stock data available</p>
                                            <p className="mt-1 text-xs">Apply filters or wait for stock ledger data.</p>
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
                                        <div className="mt-6 flex flex-col items-center justify-center py-10 text-center text-gray-500">
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
                                        <div className="mt-6 flex flex-col items-center justify-center py-10 text-center text-gray-500">
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

                {/* Floating alerts button: always visible */}
                <button
                    type="button"
                    onClick={() => setAlertsOpen(true)}
                    className="fixed bottom-24 right-6 z-40 flex size-14 items-center justify-center rounded-full bg-amber-500 shadow-lg ring-2 ring-amber-600/50 transition hover:bg-amber-600 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                    aria-label={`Alerts${alerts.length > 0 ? ` (${alerts.length})` : ''}`}
                >
                    <Bell className="size-6 text-white" />
                    {alerts.length > 0 && (
                        <span className="absolute -right-0.5 -top-0.5 flex size-5 min-w-5 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white shadow ring-2 ring-white">
                            {alerts.length > 99 ? '99+' : alerts.length}
                        </span>
                    )}
                </button>
                <Dialog open={alertsOpen} onOpenChange={setAlertsOpen}>
                    <DialogContent className="max-h-[85vh] overflow-hidden flex flex-col sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <Bell className="size-5" />
                                Live alerts
                            </DialogTitle>
                        </DialogHeader>
                        <div className="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
                            {alerts.length === 0 ? (
                                <div className="flex flex-col items-center justify-center gap-2 rounded-lg bg-[#FEF3C7] py-8 text-center">
                                    <CheckCircle className="size-10 text-amber-600" aria-hidden />
                                    <p className="text-sm font-medium text-amber-800">All systems normal</p>
                                </div>
                            ) : (
                                alerts.map((a) => (
                                    <div
                                        key={a.id}
                                        className={`rounded-lg border p-2.5 text-xs ${
                                            a.severity === 'critical' ? 'border-red-200 bg-red-50' :
                                            a.severity === 'warning' ? 'border-amber-200 bg-amber-50' :
                                            'border-gray-200 bg-gray-50'
                                        }`}
                                    >
                                        <span className="font-medium">⚠ {a.title}</span>
                                        <div className="mt-0.5 text-gray-500">{new Date(a.created_at).toLocaleString()}</div>
                                    </div>
                                ))
                            )}
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
