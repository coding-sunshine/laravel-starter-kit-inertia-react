import { BarChart } from '@/components/charts/bar-chart';
import { ComposedChart } from '@/components/charts/composed-chart';
import { StackedBarChart } from '@/components/charts/stacked-bar-chart';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { index as rakesIndex } from '@/routes/rakes';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    Calendar,
    Factory,
    Filter,
    Train,
    X,
    Zap,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import {
    Bar,
    BarChart as RechartsBarChart,
    CartesianGrid,
    Cell,
    Legend,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { PieChart } from '@/components/charts/pie-chart';
import { SemiCircleGauge } from '@/components/charts/semi-circle-gauge';

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
}

interface FilterOptions {
    powerPlants: Array<{ value: string; label: string }>;
    loaders: Array<{ id: number; name: string; siding_name: string }>;
    shifts: Array<{ value: string; label: string }>;
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
    predictedVsActualPenalty?: { predicted: number; actual: number };
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
                    <h3 className="font-semibold">{title}</h3>
                    {subtitle && <p className="text-xs text-muted-foreground">{subtitle}</p>}
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

function SidingStockSection({ sidings, stocks }: { sidings: SidingOption[]; stocks: Record<number, SidingStock> }) {
    return (
        <div className="rounded-xl border bg-card p-5">
            <SectionHeader icon={BarChart3} title="Siding stock" subtitle="Current balance per siding" />
            <div className="mt-4 grid gap-4 sm:grid-cols-3">
                {sidings.map((s) => {
                    const st = stocks[s.id];
                    const currentBalance = st?.closing_balance_mt ?? 0;
                    return (
                        <div key={s.id} className="rounded-lg border bg-muted/20 p-4 text-center">
                            <p className="text-sm font-medium text-muted-foreground">{s.name}</p>
                            <p className="mt-2 text-2xl font-bold tabular-nums">{currentBalance.toLocaleString(undefined, { maximumFractionDigits: 0 })} MT</p>
                            <p className="mt-0.5 text-xs text-muted-foreground">Current balance</p>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

const SIDING_PERF_COLORS = [DASHBOARD_PALETTE.steelBlue, DASHBOARD_PALETTE.successGreen, DASHBOARD_PALETTE.safetyYellow, DASHBOARD_PALETTE.steelBlueLight, DASHBOARD_PALETTE.successGreenLight];

function SidingPerformanceSection({ data }: { data: SidingPerformanceItem[] }) {
    const chartData = useMemo(
        () => data.map((s) => ({ ...s, name: s.name, rakes: s.rakes, penalties: s.penalties, penalty_amount: s.penalty_amount, penalty_rate: s.penalty_rate })),
        [data],
    );

    return (
        <div className="rounded-xl border bg-card p-5">
            <SectionHeader icon={BarChart3} title="Siding performance" subtitle="Rakes, penalties & penalty rate" />

            {/* Charts: horizontal bars, sidings on Y-axis, each siding a different color */}
            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <div>
                    <p className="mb-2 text-xs font-medium text-muted-foreground">Rakes dispatched vs penalties</p>
                    <ResponsiveContainer width="100%" height={260}>
                        <RechartsBarChart data={chartData} layout="vertical" margin={{ top: 4, right: 4, bottom: 0, left: 0 }}>
                            <CartesianGrid strokeDasharray="3 3" className="stroke-border/50" />
                            <XAxis type="number" allowDecimals={false} tick={{ fontSize: 12 }} />
                            <YAxis type="category" dataKey="name" width={80} tick={{ fontSize: 12 }} />
                            <Tooltip formatter={(v: number) => [v, '']} />
                            <Legend />
                            <Bar dataKey="rakes" stackId="a" name="Rakes dispatched" radius={[0, 0, 0, 0]}>
                                {chartData.map((_, i) => (
                                    <Cell key={`rakes-${i}`} fill={SIDING_PERF_COLORS[i % SIDING_PERF_COLORS.length]} />
                                ))}
                            </Bar>
                            <Bar dataKey="penalties" stackId="a" name="Penalties" fill="#ef4444" radius={[4, 4, 0, 0]} />
                        </RechartsBarChart>
                    </ResponsiveContainer>
                </div>
                <div>
                    <p className="mb-2 text-xs font-medium text-muted-foreground">Penalty amount by siding</p>
                    <ResponsiveContainer width="100%" height={260}>
                        <RechartsBarChart data={chartData} layout="vertical" margin={{ top: 4, right: 4, bottom: 0, left: 0 }}>
                            <CartesianGrid strokeDasharray="3 3" className="stroke-border/50" />
                            <XAxis type="number" tickFormatter={(v) => formatCurrency(v)} tick={{ fontSize: 12 }} />
                            <YAxis type="category" dataKey="name" width={80} tick={{ fontSize: 12 }} />
                            <Tooltip formatter={(v: number) => [formatCurrency(v), 'Penalty']} />
                            <Bar dataKey="penalty_amount" radius={[4, 4, 0, 0]}>
                                {chartData.map((_, i) => (
                                    <Cell key={i} fill={SIDING_PERF_COLORS[i % SIDING_PERF_COLORS.length]} />
                                ))}
                            </Bar>
                        </RechartsBarChart>
                    </ResponsiveContainer>
                </div>
            </div>

            {/* Penalty rate visual bars (same siding colors) */}
            <div className="mt-5">
                <p className="mb-3 text-xs font-medium text-muted-foreground">Penalty rate by siding</p>
                <div className="space-y-3">
                    {data.map((s, i) => (
                        <div key={s.name} className="group">
                            <div className="mb-1 flex items-center justify-between text-sm">
                                <span className="font-medium">{s.name}</span>
                                <span className="font-bold tabular-nums text-muted-foreground">{s.penalty_rate}%</span>
                            </div>
                            <div className="h-3 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full rounded-full transition-all duration-500"
                                    style={{
                                        width: `${Math.min(s.penalty_rate, 100)}%`,
                                        backgroundColor: SIDING_PERF_COLORS[i % SIDING_PERF_COLORS.length],
                                    }}
                                />
                            </div>
                            <div className="mt-0.5 flex justify-between text-xs text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100">
                                <span>{s.rakes} rakes, {s.penalties} penalties</span>
                                <span>{formatCurrency(s.penalty_amount)}</span>
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
        const seen = new Map<string, number>();
        return rakes.map((r, i) => {
            const count = (seen.get(r.rake_number) ?? 0) + 1;
            seen.set(r.rake_number, count);
            return { idx: i, label: `${r.rake_number} — ${r.siding} (${r.dispatch_date})` };
        });
    }, [rakes]);

    const [selectedIdx, setSelectedIdx] = useState(0);
    const r = rakes[selectedIdx] ?? rakes[0];

    const loadingHours = r.loading_minutes != null ? Math.floor(r.loading_minutes / 60) : null;
    const loadingMins = r.loading_minutes != null ? r.loading_minutes % 60 : null;

    const weightChartData = useMemo(() => {
        const items: { name: string; value: number }[] = [];
        if (r.net_weight != null) items.push({ name: 'Net weight', value: r.net_weight });
        if (r.over_load != null && r.over_load > 0) items.push({ name: 'Overload', value: r.over_load });
        if (r.under_load != null && r.under_load > 0) items.push({ name: 'Underload', value: r.under_load });
        return items;
    }, [r]);

    return (
        <div className="rounded-xl border bg-card p-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <SectionHeader
                    icon={Train}
                    title="Rake-wise performance"
                    subtitle="Select a rake to view its details"
                    action={
                        <Button variant="outline" size="sm" asChild>
                            <Link href={rakesIndex().url} data-pan="dashboard-view-all-rakes">View all rakes</Link>
                        </Button>
                    }
                />
                <select
                    value={selectedIdx}
                    onChange={(e) => setSelectedIdx(Number(e.target.value))}
                    className="max-w-xs rounded-lg border bg-background px-3 py-1.5 text-sm font-medium"
                >
                    {rakeOptions.map((opt) => (
                        <option key={opt.idx} value={opt.idx}>
                            {opt.label}
                        </option>
                    ))}
                </select>
            </div>

            {/* Summary stats */}
            <div className="mt-4 grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">Siding</p>
                    <p className="mt-1 text-lg font-bold">{r.siding}</p>
                </div>
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">Dispatch date</p>
                    <p className="mt-1 text-lg font-bold tabular-nums">{r.dispatch_date}</p>
                </div>
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">Wagons</p>
                    <p className="mt-1 text-lg font-bold tabular-nums">{r.wagon_count ?? '—'}</p>
                </div>
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">Net weight</p>
                    <p className="mt-1 text-lg font-bold tabular-nums">{r.net_weight != null ? formatWeight(r.net_weight) : '—'}</p>
                </div>
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">Loading time</p>
                    <p className="mt-1 text-lg font-bold tabular-nums">
                        {loadingHours != null ? `${loadingHours}h ${loadingMins}m` : '—'}
                    </p>
                </div>
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">Penalty</p>
                    <p className={`mt-1 text-lg font-bold tabular-nums ${r.penalty_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'}`}>
                        {r.penalty_amount > 0 ? formatCurrency(r.penalty_amount) : 'None'}
                    </p>
                </div>
            </div>

            {/* Charts */}
            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <div>
                    <p className="mb-2 text-xs font-medium text-muted-foreground">Weight breakdown (MT)</p>
                    {weightChartData.length > 0 ? (
                        <BarChart
                            data={weightChartData}
                            xKey="name"
                            yKey="value"
                            yLabel="MT"
                            height={220}
                            color={DASHBOARD_PALETTE.steelBlue}
                            formatTooltip={(v) => `${v.toLocaleString()} MT`}
                        />
                    ) : (
                        <div className="flex h-[220px] items-center justify-center rounded-lg border bg-muted/20 text-sm text-muted-foreground">
                            No weighment data available
                        </div>
                    )}
                </div>
                <div>
                    <p className="mb-2 text-xs font-medium text-muted-foreground">Overload status</p>
                    <div className="flex h-[220px] flex-col items-center justify-center gap-3 rounded-lg border bg-muted/20">
                        {r.over_load != null && r.over_load > 0 ? (
                            <>
                                <span className="inline-flex size-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-950/50">
                                    <AlertTriangle className="size-8 text-red-600 dark:text-red-400" />
                                </span>
                                <span className="text-2xl font-bold tabular-nums text-red-600 dark:text-red-400">
                                    +{r.over_load.toLocaleString()} MT
                                </span>
                                <span className="text-xs text-muted-foreground">Overloaded</span>
                            </>
                        ) : (
                            <>
                                <span className="inline-flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-950/50">
                                    <Train className="size-8 text-green-600 dark:text-green-400" />
                                </span>
                                <span className="text-xl font-bold text-green-600 dark:text-green-400">Within limits</span>
                                <span className="text-xs text-muted-foreground">No overloading detected</span>
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
        if (!selectedLoader) return { totalWagons: 0, totalOverload: 0, rate: 0 };
        const totalOverload = monthly.reduce(
            (sum, m) => sum + ((m[`loader_${selectedLoader.id}_overload`] as number) ?? 0), 0,
        );
        const totalWagons = monthly.reduce(
            (sum, m) => sum + ((m[`loader_${selectedLoader.id}_total`] as number) ?? 0), 0,
        );
        const rate = totalWagons > 0 ? (totalOverload / totalWagons) * 100 : 0;
        return { totalWagons, totalOverload, rate };
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

    if (!selectedLoader) return null;

    return (
        <div className="rounded-xl border bg-card p-5">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <SectionHeader
                    icon={AlertTriangle}
                    title="Loader-wise overloading trends"
                    subtitle="Select a loader to view its performance"
                />
                <select
                    value={selectedLoaderId}
                    onChange={(e) => setSelectedLoaderId(Number(e.target.value))}
                    className="rounded-lg border bg-background px-3 py-1.5 text-sm font-medium"
                >
                    {loaders.map((l) => (
                        <option key={l.id} value={l.id}>
                            {l.name} — {l.siding}
                        </option>
                    ))}
                </select>
            </div>

            {/* Summary stats for selected loader */}
            <div className="mt-4 grid gap-3 sm:grid-cols-3">
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">Total wagons loaded</p>
                    <p className="mt-1 text-2xl font-bold tabular-nums">{stats.totalWagons}</p>
                </div>
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">Overloaded wagons</p>
                    <p className="mt-1 text-2xl font-bold tabular-nums text-red-600 dark:text-red-400">{stats.totalOverload}</p>
                </div>
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">Overload rate</p>
                    <p className="mt-1 text-2xl font-bold tabular-nums">
                        <span className={
                            stats.rate > 15
                                ? 'text-red-600 dark:text-red-400'
                                : stats.rate > 5
                                  ? 'text-amber-600 dark:text-amber-400'
                                  : 'text-green-600 dark:text-green-400'
                        }>
                            {stats.rate.toFixed(1)}%
                        </span>
                    </p>
                </div>
            </div>

            {/* Two charts side by side */}
            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <div>
                    <p className="mb-2 text-xs font-medium text-muted-foreground">Total wagons vs overloaded (monthly)</p>
                    <ComposedChart
                        data={trendData}
                        xKey="month"
                        barKey="total"
                        lineKey="overloaded"
                        barLabel="Total wagons"
                        lineLabel="Overloaded"
                        height={240}
                        barColor={DASHBOARD_PALETTE.steelBlue}
                        lineColor={DASHBOARD_PALETTE.successGreen}
                        formatTooltip={(v, name) =>
                            name === 'total' ? `${v} wagons` : `${v} overloaded`
                        }
                    />
                </div>
                <div>
                    <p className="mb-2 text-xs font-medium text-muted-foreground">Overloaded wagons per month</p>
                    <BarChart
                        data={barChartData}
                        xKey="month"
                        yKey="value"
                        yLabel="Overloaded"
                        height={240}
                        color={DASHBOARD_PALETTE.safetyYellow}
                        formatTooltip={(v) => `${v} wagons`}
                    />
                </div>
            </div>
        </div>
    );
}

const SIDING_COLORS = [DASHBOARD_PALETTE.steelBlue, DASHBOARD_PALETTE.successGreen, DASHBOARD_PALETTE.safetyYellow, DASHBOARD_PALETTE.steelBlueLight, DASHBOARD_PALETTE.successGreenLight];

function PowerPlantDispatchSection({ data }: { data: PowerPlantDispatchItem[] }) {
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

    if (data.length === 0) {
        return (
            <div className="rounded-xl border bg-card p-5">
                <SectionHeader icon={Factory} title="Power plant wise dispatch" subtitle="How many rakes sent to each power plant" />
                <div className="mt-6 flex flex-col items-center justify-center py-10 text-center text-muted-foreground">
                    <Factory className="mb-3 h-10 w-10 opacity-30" />
                    <p className="text-sm font-medium">No dispatch data available</p>
                    <p className="mt-1 text-xs">Weighment data with destination stations will appear here once rakes are dispatched.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="rounded-xl border bg-card p-5">
            <SectionHeader icon={Factory} title="Power plant wise dispatch" subtitle="How many rakes sent to each power plant" />
            <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div className="rounded-lg border bg-muted/20 p-3 text-center">
                    <div className="text-xs text-muted-foreground">Destinations</div>
                    <div className="mt-1 text-xl font-bold tabular-nums">{data.length}</div>
                </div>
                <div className="rounded-lg border bg-muted/20 p-3 text-center">
                    <div className="text-xs text-muted-foreground">Total rakes</div>
                    <div className="mt-1 text-xl font-bold tabular-nums">{totalRakes}</div>
                </div>
                <div className="rounded-lg border bg-muted/20 p-3 text-center">
                    <div className="text-xs text-muted-foreground">Total weight</div>
                    <div className="mt-1 text-xl font-bold tabular-nums">{formatWeight(totalWeight)}</div>
                </div>
                <div className="rounded-lg border bg-muted/20 p-3 text-center">
                    <div className="text-xs text-muted-foreground">Avg per destination</div>
                    <div className="mt-1 text-xl font-bold tabular-nums">{data.length > 0 ? formatWeight(totalWeight / data.length) : '—'}</div>
                </div>
            </div>

            <div className="mt-5 grid gap-4 lg:grid-cols-2">
                <div>
                    <h4 className="mb-2 text-sm font-semibold text-muted-foreground">Rakes sent to each power plant by siding</h4>
                    <ResponsiveContainer width="100%" height={Math.max(260, data.length * 48)}>
                        <RechartsBarChart data={stackedChartData} margin={{ left: 10, right: 20, top: 5, bottom: 5 }}>
                            <CartesianGrid strokeDasharray="3 3" opacity={0.3} />
                            <XAxis dataKey="name" tick={{ fontSize: 11 }} />
                            <YAxis allowDecimals={false} />
                            <Tooltip />
                            <Legend />
                            {allSidingNames.map((sn, i) => (
                                <Bar
                                    key={sn}
                                    dataKey={sn}
                                    fill={SIDING_COLORS[i % SIDING_COLORS.length]}
                                    name={sn}
                                />
                            ))}
                        </RechartsBarChart>
                    </ResponsiveContainer>
                </div>

                <div>
                    <h4 className="mb-2 text-sm font-semibold text-muted-foreground">Rakes dispatched per power plant</h4>
                    <BarChart
                        data={data as Record<string, unknown>[]}
                        xKey="name"
                        yKey="rakes"
                        yLabel="Rakes"
                        height={Math.max(260, data.length * 40)}
                        color={DASHBOARD_PALETTE.steelBlue}
                        formatY={(v) => v}
                        formatTooltip={(v) => `${v.toLocaleString()} rakes`}
                    />
                </div>
            </div>

            <div className="mt-5 space-y-2">
                <h4 className="text-sm font-semibold text-muted-foreground">Destination breakdown</h4>
                {data.map((pp, i) => (
                    <div key={pp.name} className="group rounded-lg border bg-muted/20 p-3 transition-colors hover:bg-muted/40">
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-semibold">{pp.name}</span>
                            <div className="flex items-center gap-3 text-xs text-muted-foreground">
                                <span className="tabular-nums">{pp.rakes} rakes</span>
                                <span className="tabular-nums">{formatWeight(pp.weight_mt)}</span>
                            </div>
                        </div>
                        <div className="mt-1.5 flex flex-wrap gap-2">
                            {Object.entries(pp.sidings).map(([sidingName, info], si) => (
                                <span
                                    key={sidingName}
                                    className="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs"
                                    style={{ backgroundColor: `color-mix(in srgb, ${SIDING_COLORS[si % SIDING_COLORS.length]} 15%, transparent)` }}
                                >
                                    <span className="h-2 w-2 rounded-full" style={{ backgroundColor: SIDING_COLORS[si % SIDING_COLORS.length] }} />
                                    {sidingName}: {info.rakes} rakes, {formatWeight(info.weight_mt)}
                                </span>
                            ))}
                        </div>
                        <div className="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                className="h-full rounded-full transition-all"
                                style={{
                                    width: `${Math.min(100, (pp.weight_mt / maxWeight) * 100)}%`,
                                    backgroundColor: SIDING_COLORS[i % SIDING_COLORS.length],
                                }}
                            />
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function DashboardFiltersBar({
    sidings,
    filters,
    filterOptions,
}: {
    sidings: SidingOption[];
    filters: DashboardFilters;
    filterOptions: FilterOptions;
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

    return (
        <div className="rounded-xl border bg-card p-4">
            <div className="flex flex-wrap items-center gap-3">
                <div className="flex items-center gap-1.5 text-sm font-medium text-muted-foreground">
                    <Filter className="size-4" />
                    <span>Filters</span>
                </div>

                <div className="flex flex-wrap items-center gap-1">
                    {PERIODS.map((p) => (
                        <button
                            key={p.key}
                            type="button"
                            onClick={() => applyFilters({ period: p.key })}
                            className={
                                'rounded-lg px-3 py-1.5 text-xs font-medium transition-colors ' +
                                (filters.period === p.key
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted/50 text-muted-foreground hover:bg-muted')
                            }
                        >
                            {p.label}
                        </button>
                    ))}
                </div>

                {filters.period === 'custom' && (
                    <div className="flex items-center gap-2">
                        <div className="flex items-center gap-1.5">
                            <Calendar className="size-3.5 text-muted-foreground" />
                            <input
                                type="date"
                                value={customFrom}
                                onChange={(e) => setCustomFrom(e.target.value)}
                                className="rounded-lg border bg-background px-2.5 py-1.5 text-xs"
                            />
                        </div>
                        <span className="text-xs text-muted-foreground">to</span>
                        <input
                            type="date"
                            value={customTo}
                            onChange={(e) => setCustomTo(e.target.value)}
                            className="rounded-lg border bg-background px-2.5 py-1.5 text-xs"
                        />
                        <Button
                            variant="outline"
                            size="sm"
                            className="h-7 text-xs"
                            onClick={() => applyFilters({ period: 'custom', from: customFrom, to: customTo })}
                        >
                            Apply
                        </Button>
                    </div>
                )}

                <Select
                    value={filters.power_plant ?? ALL_FILTER_VALUE}
                    onValueChange={(v) => applyFilters({ power_plant: v === ALL_FILTER_VALUE ? null : v })}
                >
                    <SelectTrigger className="h-8 w-[180px] text-xs">
                        <SelectValue placeholder="Power plant" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL_FILTER_VALUE} className="text-xs">All power plants</SelectItem>
                        {filterOptions.powerPlants.map((pp) => (
                            <SelectItem key={pp.value} value={pp.value} className="text-xs">
                                {pp.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <div className="flex items-center gap-1.5">
                    <input
                        type="text"
                        placeholder="Rake number"
                        value={rakeNumberInput}
                        onChange={(e) => setRakeNumberInput(e.target.value)}
                        onBlur={() => applyFilters({ rake_number: rakeNumberInput.trim() || null })}
                        onKeyDown={(e) => e.key === 'Enter' && applyFilters({ rake_number: rakeNumberInput.trim() || null })}
                        className="w-28 rounded-lg border bg-background px-2.5 py-1.5 text-xs"
                    />
                </div>

                <Select
                    value={filters.loader_id != null ? String(filters.loader_id) : ALL_FILTER_VALUE}
                    onValueChange={(v) => applyFilters({ loader_id: v === ALL_FILTER_VALUE ? null : Number(v) })}
                >
                    <SelectTrigger className="h-8 w-[180px] text-xs">
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
                    <SelectTrigger className="h-8 w-[100px] text-xs">
                        <SelectValue placeholder="Shift" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL_FILTER_VALUE} className="text-xs">All shifts</SelectItem>
                        {filterOptions.shifts.map((s) => (
                            <SelectItem key={s.value} value={s.value} className="text-xs">
                                {s.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <div className="relative ml-auto">
                    <button
                        type="button"
                        onClick={() => {
                            setPendingSidingIds(isAllSidingsSelected ? allSidingIds : filters.siding_ids);
                            setShowSidingDropdown(!showSidingDropdown);
                        }}
                        className="flex items-center gap-2 rounded-lg border bg-background px-3 py-1.5 text-xs font-medium transition-colors hover:bg-muted"
                    >
                        <span className="max-w-48 truncate">{selectedSidingNames}</span>
                        {!isAllSidingsSelected && (
                            <button
                                type="button"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    resetSidingFilter();
                                }}
                                className="rounded-full p-0.5 hover:bg-muted-foreground/20"
                            >
                                <X className="size-3" />
                            </button>
                        )}
                    </button>
                    {showSidingDropdown && (
                        <>
                            <div className="fixed inset-0 z-40" onClick={() => setShowSidingDropdown(false)} />
                            <div className="absolute right-0 top-full z-50 mt-1 w-56 rounded-xl border bg-card p-2 shadow-lg">
                                <button
                                    type="button"
                                    onClick={() => setPendingSidingIds(allSidingIds)}
                                    className={
                                        'flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs transition-colors hover:bg-muted ' +
                                        (isAllPendingSelected ? 'font-semibold text-primary' : 'text-muted-foreground')
                                    }
                                >
                                    <div className={
                                        'flex size-4 items-center justify-center rounded border ' +
                                        (isAllPendingSelected ? 'border-primary bg-primary' : 'border-muted-foreground/30')
                                    }>
                                        {isAllPendingSelected && <span className="text-[10px] text-primary-foreground">✓</span>}
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
                                            className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-xs transition-colors hover:bg-muted"
                                        >
                                            <div className={
                                                'flex size-4 items-center justify-center rounded border ' +
                                                (isSelected ? 'border-primary bg-primary' : 'border-muted-foreground/30')
                                            }>
                                                {isSelected && <span className="text-[10px] text-primary-foreground">✓</span>}
                                            </div>
                                            <span>{s.name}</span>
                                            <span className="ml-auto text-muted-foreground">{s.code}</span>
                                        </button>
                                    );
                                })}
                                <div className="mt-1 border-t pt-1">
                                    <Button
                                        size="sm"
                                        className="w-full text-xs"
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
            </div>

            {filters.period !== 'custom' && (
                <p className="mt-2 text-xs text-muted-foreground">
                    Showing data from {filters.from} to {filters.to}
                </p>
            )}
        </div>
    );
}

export default function Dashboard() {
    const props = usePage<DashboardProps>().props;
    const [activeSection, setActiveSection] = useState<string>(DEFAULT_DASHBOARD_SECTION);
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
    };
    const filters: DashboardFilters = {
        ...defaultFilters,
        ...(props.filters ?? {}),
        power_plant: props.filters?.power_plant ?? null,
        rake_number: props.filters?.rake_number ?? null,
        loader_id: props.filters?.loader_id ?? null,
        shift: props.filters?.shift ?? null,
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
        router.get(dashboardPath, params as Record<string, string>, { replace: true, preserveState: false });
    }, [dashboardPath, filters.period, filters.siding_ids, filters.power_plant, filters.rake_number, filters.loader_id, filters.shift, sidings.length]);

    const filterOptions = props.filterOptions ?? { powerPlants: [], loaders: [], shifts: [] };
    const kpis = props.kpis;
    const penaltyTrendDaily = props.penaltyTrendDaily ?? [];
    const penaltyByType = props.penaltyByType ?? [];
    const penaltyBySiding = props.penaltyBySiding ?? [];
    const alerts = props.alerts ?? [];
    const liveRakeStatus = props.liveRakeStatus ?? [];
    const truckReceiptTrend = props.truckReceiptTrend ?? [];
    const stockGauge = props.stockGauge;
    const predictedVsActualPenalty = props.predictedVsActualPenalty ?? { predicted: 0, actual: 0 };
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-1">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold tracking-tight">
                        Management Dashboard
                    </h2>
                    <Select value={activeSection} onValueChange={setActiveSection}>
                        <SelectTrigger className="w-full sm:w-64">
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
                </div>

                {sidings.length > 0 && (
                    <DashboardFiltersBar sidings={sidings} filters={filters} filterOptions={filterOptions} />
                )}

                {sidings.length > 0 && kpis && (
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                        <div className="rounded-xl border bg-card p-4 text-center">
                            <div className="text-xs font-medium text-muted-foreground">Rakes dispatched {periodLabel}</div>
                            <div className="mt-1 text-2xl font-bold tabular-nums">{kpis.rakesDispatchedToday}</div>
                        </div>
                        <div className="rounded-xl border bg-card p-4 text-center">
                            <div className="text-xs font-medium text-muted-foreground">Coal dispatched {periodLabel}</div>
                            <div className="mt-1 text-2xl font-bold tabular-nums">{formatWeight(kpis.coalDispatchedToday)}</div>
                        </div>
                        <div className="rounded-xl border bg-card p-4 text-center">
                            <div className="text-xs font-medium text-muted-foreground">Penalty {periodLabel}</div>
                            <div className="mt-1 text-2xl font-bold tabular-nums">{formatCurrency(kpis.totalPenaltyThisMonth)}</div>
                        </div>
                        <div className="rounded-xl border bg-card p-4 text-center">
                            <div className="text-xs font-medium text-muted-foreground">Predicted penalty risk</div>
                            <div className="mt-1 text-2xl font-bold tabular-nums">{formatCurrency(kpis.predictedPenaltyRisk)}</div>
                        </div>
                        <div className="rounded-xl border bg-card p-4 text-center">
                            <div className="text-xs font-medium text-muted-foreground">Avg loading time ({periodLabel})</div>
                            <div className="mt-1 text-2xl font-bold tabular-nums">
                                {kpis.avgLoadingTimeMinutes != null ? `${Math.floor(kpis.avgLoadingTimeMinutes / 60)}h ${kpis.avgLoadingTimeMinutes % 60}m` : '—'}
                            </div>
                        </div>
                        <div className="rounded-xl border bg-card p-4 text-center">
                            <div className="text-xs font-medium text-muted-foreground">Trucks received {periodLabel}</div>
                            <div className="mt-1 text-2xl font-bold tabular-nums">{kpis.trucksReceivedToday}</div>
                        </div>
                    </div>
                )}

                {sidings.length === 0 ? (
                    <div className="rounded-xl border bg-card p-8 text-center text-sm text-muted-foreground">
                        <p>No sidings assigned to your account. Contact your administrator to get access.</p>
                    </div>
                ) : (
                    <div className="grid gap-6 lg:grid-cols-[1fr_280px]">
                        <div className="min-w-0 space-y-6">
                        {activeSection === 'executive-overview' && (
                            <div className="space-y-6">
                                {sidingPerformance.length > 0 ? (
                                    <SidingPerformanceSection data={sidingPerformance} />
                                ) : (
                                    <div className="rounded-xl border bg-card p-5">
                                        <SectionHeader icon={BarChart3} title="Siding performance" subtitle="Rakes, coal & penalty by siding" />
                                        <div className="mt-4 py-8 text-center text-sm text-muted-foreground">No performance data for selected filters.</div>
                                    </div>
                                )}
                                <div className="rounded-xl border bg-card p-5">
                                    <SectionHeader icon={Calendar} title="Penalty trend" subtitle="Date / month vs penalty amount" />
                                    {penaltyTrendDaily.length > 0 ? (
                                        <ResponsiveContainer width="100%" height={280}>
                                            <RechartsBarChart data={penaltyTrendDaily} margin={{ top: 5, right: 5, left: -10, bottom: 0 }}>
                                                <CartesianGrid strokeDasharray="3 3" opacity={0.3} />
                                                <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                                                <YAxis tick={{ fontSize: 11 }} tickFormatter={(v) => formatCurrency(v)} />
                                                <Tooltip formatter={(v: number) => [formatCurrency(v), 'Penalty']} />
                                                <Bar dataKey="total" fill={DASHBOARD_PALETTE.alertRed} name="Penalty" radius={[4, 4, 0, 0]} />
                                            </RechartsBarChart>
                                        </ResponsiveContainer>
                                    ) : (
                                        <div className="mt-4 py-8 text-center text-sm text-muted-foreground">No penalty data for selected period.</div>
                                    )}
                                </div>
                                <div className="rounded-xl border bg-card p-5">
                                    <SectionHeader icon={Factory} title="Power plant dispatch distribution" subtitle="Coal supply by destination" />
                                    {powerPlantDispatch.length > 0 ? (
                                        <PieChart
                                            data={powerPlantDispatch.map((pp) => ({
                                                name: pp.name,
                                                value: Math.round((pp.weight_mt / Math.max(1, powerPlantDispatch.reduce((s, p) => s + p.weight_mt, 0))) * 100),
                                            }))}
                                            nameKey="name"
                                            valueKey="value"
                                            height={280}
                                            innerRadius={60}
                                            formatTooltip={(v) => `${v}%`}
                                        />
                                    ) : (
                                        <div className="mt-4 py-8 text-center text-sm text-muted-foreground">No power plant dispatch data.</div>
                                    )}
                                </div>
                            </div>
                        )}

                        {activeSection === 'operations' && (
                            <div className="space-y-6">
                                <div className="rounded-xl border bg-card p-5">
                                    <SectionHeader icon={Train} title="Live rake status" subtitle="Active rakes (not yet dispatched)" />
                                    {liveRakeStatus.length > 0 ? (
                                        <div className="mt-4 overflow-x-auto">
                                            <table className="w-full text-sm">
                                                <thead>
                                                    <tr className="border-b text-left text-muted-foreground">
                                                        <th className="pb-2 font-medium">Rake</th>
                                                        <th className="pb-2 font-medium">Siding</th>
                                                        <th className="pb-2 font-medium">Status</th>
                                                        <th className="pb-2 font-medium">Time elapsed</th>
                                                        <th className="pb-2 font-medium">Risk</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {liveRakeStatus.map((row, i) => (
                                                        <tr key={i} className="border-b last:border-0">
                                                            <td className="py-2 font-medium">{row.rake_number}</td>
                                                            <td className="py-2">{row.siding_name}</td>
                                                            <td className="py-2">{row.state}</td>
                                                            <td className="py-2 tabular-nums">{row.time_elapsed}</td>
                                                            <td className="py-2">
                                                                <span className={
                                                                    row.risk === 'penalty_risk' ? 'text-red-600 dark:text-red-400' :
                                                                    row.risk === 'attention' ? 'text-amber-600 dark:text-amber-400' : 'text-muted-foreground'
                                                                }>
                                                                    {row.risk === 'penalty_risk' ? 'Penalty risk' : row.risk === 'attention' ? 'Attention' : 'Normal'}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    ) : (
                                        <div className="mt-4 py-8 text-center text-sm text-muted-foreground">No active rakes.</div>
                                    )}
                                </div>
                                <div className="rounded-xl border bg-card p-5">
                                    <SectionHeader icon={BarChart3} title="Truck receipt trend" subtitle="Trips per hour (today)" />
                                    {truckReceiptTrend.length > 0 ? (
                                        <ResponsiveContainer width="100%" height={260}>
                                            <RechartsBarChart data={truckReceiptTrend} margin={{ top: 5, right: 5, left: -10, bottom: 0 }}>
                                                <CartesianGrid strokeDasharray="3 3" opacity={0.3} />
                                                <XAxis dataKey="label" tick={{ fontSize: 10 }} />
                                                <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                                <Tooltip formatter={(v: number) => [v, 'Trips']} />
                                                <Bar dataKey="count" fill={DASHBOARD_PALETTE.steelBlue} name="Trips" radius={[4, 4, 0, 0]} />
                                            </RechartsBarChart>
                                        </ResponsiveContainer>
                                    ) : (
                                        <div className="mt-4 py-8 text-center text-sm text-muted-foreground">No truck receipt data for today.</div>
                                    )}
                                </div>
                                <div className="rounded-xl border bg-card p-5">
                                    <SectionHeader
                                        icon={Zap}
                                        title="Stock vs requirement"
                                        subtitle="Minimum 3,800 MT per rake — side-wise"
                                    />
                                    {stockGauge && stockGauge.length > 0 ? (
                                        <div className="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                            {stockGauge.map((item) => (
                                                <SemiCircleGauge
                                                    key={item.siding_id}
                                                    title={item.siding_name}
                                                    value={item.stock_available_mt}
                                                    required={item.rake_required_mt}
                                                    status={item.status}
                                                    formatValue={formatWeight}
                                                    colors={{
                                                        redTrack: '#fecaca',
                                                        redFill: DASHBOARD_PALETTE.alertRed,
                                                        greenTrack: '#bbf7d0',
                                                        greenFill: DASHBOARD_PALETTE.successGreen,
                                                        blueTrack: '#bfdbfe',
                                                        blueFill: DASHBOARD_PALETTE.steelBlue,
                                                    }}
                                                />
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="mt-4 py-8 text-center text-sm text-muted-foreground">No stock data.</div>
                                    )}
                                </div>
                            </div>
                        )}

                        {activeSection === 'penalty-control' && (
                            <div className="space-y-6">
                                <div className="rounded-xl border bg-card p-5">
                                    <SectionHeader icon={BarChart3} title="Penalty by siding" subtitle="Which siding causes most penalties" />
                                    {penaltyBySiding.length > 0 ? (
                                        <div className="mt-4">
                                            <BarChart
                                                data={penaltyBySiding as Record<string, unknown>[]}
                                                xKey="name"
                                                yKey="total"
                                                yLabel="Penalty"
                                                height={260}
                                                color={DASHBOARD_PALETTE.alertRed}
                                                formatY={(v) => formatCurrency(v)}
                                                formatTooltip={(v) => formatCurrency(v)}
                                            />
                                        </div>
                                    ) : (
                                        <div className="mt-4 py-8 text-center text-sm text-muted-foreground">No penalty data for selected period.</div>
                                    )}
                                </div>
                                <div className="rounded-xl border bg-card p-5">
                                    <SectionHeader icon={AlertTriangle} title="Penalty type distribution" subtitle="Overloading, demurrage, wharfage, etc." />
                                    {penaltyByType.length > 0 ? (
                                        <PieChart
                                            data={penaltyByType}
                                            nameKey="name"
                                            valueKey="value"
                                            height={280}
                                            innerRadius={0}
                                            colors={[DASHBOARD_PALETTE.alertRed, DASHBOARD_PALETTE.safetyYellow, DASHBOARD_PALETTE.steelBlue, DASHBOARD_PALETTE.darkGrey]}
                                            formatTooltip={(v) => formatCurrency(v)}
                                        />
                                    ) : (
                                        <div className="mt-4 py-8 text-center text-sm text-muted-foreground">No penalty type data.</div>
                                    )}
                                </div>
                                <div className="rounded-xl border bg-card p-5">
                                    <SectionHeader icon={BarChart3} title="Predicted vs actual penalty" subtitle="System accuracy" />
                                    <div className="mt-4 flex justify-center gap-8">
                                        <div className="rounded-lg border bg-muted/30 px-6 py-4 text-center">
                                            <div className="text-xs text-muted-foreground">Predicted</div>
                                            <div className="text-xl font-bold tabular-nums">{formatCurrency(predictedVsActualPenalty.predicted)}</div>
                                        </div>
                                        <div className="rounded-lg border bg-muted/30 px-6 py-4 text-center">
                                            <div className="text-xs text-muted-foreground">Actual</div>
                                            <div className="text-xl font-bold tabular-nums">{formatCurrency(predictedVsActualPenalty.actual)}</div>
                                        </div>
                                    </div>
                                    <ResponsiveContainer width="100%" height={200} className="mt-4">
                                        <RechartsBarChart
                                            data={[
                                                { name: 'Predicted', value: predictedVsActualPenalty.predicted },
                                                { name: 'Actual', value: predictedVsActualPenalty.actual },
                                            ]}
                                            margin={{ top: 5, right: 5, left: -10, bottom: 0 }}
                                        >
                                            <CartesianGrid strokeDasharray="3 3" opacity={0.3} />
                                            <XAxis dataKey="name" tick={{ fontSize: 11 }} />
                                            <YAxis tickFormatter={(v) => formatCurrency(v)} />
                                            <Tooltip formatter={(v: number) => formatCurrency(v)} />
                                            <Bar dataKey="value" fill={DASHBOARD_PALETTE.alertRed} radius={[4, 4, 0, 0]} />
                                        </RechartsBarChart>
                                    </ResponsiveContainer>
                                </div>
                            </div>
                        )}

                        {activeSection === 'siding-performance' && (
                            sidingPerformance.length > 0 ? (
                                <SidingPerformanceSection data={sidingPerformance} />
                            ) : (
                                    <div className="rounded-xl border bg-card p-5">
                                        <SectionHeader icon={BarChart3} title="Siding performance" subtitle="Rakes, penalties & penalty rate" />
                                        <div className="mt-6 flex flex-col items-center justify-center py-10 text-center text-muted-foreground">
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
                                    <div className="rounded-xl border bg-card p-5">
                                        <SectionHeader icon={BarChart3} title="Siding stock" subtitle="Opening & closing balance with total rakes" />
                                        <div className="mt-6 flex flex-col items-center justify-center py-10 text-center text-muted-foreground">
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
                                    <div className="rounded-xl border bg-card p-5">
                                        <SectionHeader icon={Train} title="Rake-wise performance" subtitle="Top dispatched rakes" />
                                        <div className="mt-6 flex flex-col items-center justify-center py-10 text-center text-muted-foreground">
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
                                    <div className="rounded-xl border bg-card p-5">
                                        <SectionHeader icon={AlertTriangle} title="Loader-wise overloading trends" subtitle="Overload cases by loader" />
                                        <div className="mt-6 flex flex-col items-center justify-center py-10 text-center text-muted-foreground">
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
                        {sidings.length > 0 && (
                            <div className="space-y-3 lg:min-w-0">
                                <h3 className="text-sm font-semibold text-muted-foreground">Live alerts</h3>
                                <div className="max-h-[calc(100vh-12rem)] space-y-2 overflow-y-auto rounded-xl border bg-card p-3">
                                    {alerts.length === 0 ? (
                                        <p className="py-4 text-center text-xs text-muted-foreground">No active alerts.</p>
                                    ) : (
                                        alerts.map((a) => (
                                            <div
                                                key={a.id}
                                                className={`rounded-lg border p-2.5 text-xs ${
                                                    a.severity === 'critical' ? 'border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-950/30' :
                                                    a.severity === 'warning' ? 'border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/30' :
                                                    'border-border bg-muted/30'
                                                }`}
                                            >
                                                <span className="font-medium">⚠ {a.title}</span>
                                                <div className="mt-0.5 text-muted-foreground">{new Date(a.created_at).toLocaleString()}</div>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
