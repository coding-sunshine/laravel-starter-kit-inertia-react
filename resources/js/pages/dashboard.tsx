import { BarChart } from '@/components/charts/bar-chart';
import { ComposedChart } from '@/components/charts/composed-chart';
import { StackedBarChart } from '@/components/charts/stacked-bar-chart';
import { Button } from '@/components/ui/button';
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
import { useCallback, useMemo, useState } from 'react';
import {
    Bar,
    BarChart as RechartsBarChart,
    CartesianGrid,
    Legend,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

const PERIODS = [
    { key: 'today', label: 'Today' },
    { key: 'week', label: 'This week' },
    { key: 'month', label: 'This month' },
    { key: 'quarter', label: 'Quarter' },
    { key: 'year', label: 'Year' },
    { key: 'custom', label: 'Custom' },
] as const;

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

interface PowerPlantDispatchItem {
    [key: string]: unknown;
    name: string;
    rakes: number;
    weight_mt: number;
    avg_variance_pct: number;
}

interface DashboardFilters {
    period: string;
    from: string;
    to: string;
    siding_ids: number[];
}

type DashboardProps = SharedData & {
    sidings?: SidingOption[];
    filters?: DashboardFilters;
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

const SIDING_BAR_COLORS = ['bg-blue-500', 'bg-emerald-500', 'bg-amber-500', 'bg-violet-500', 'bg-rose-500'];
const SIDING_DOT_COLORS = ['bg-blue-500', 'bg-emerald-500', 'bg-amber-500', 'bg-violet-500', 'bg-rose-500'];

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
                        <span className={`inline-block size-2.5 rounded-full ${SIDING_DOT_COLORS[i % SIDING_DOT_COLORS.length]}`} />
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
                                                        className={`w-full rounded-t-md transition-all duration-500 ${SIDING_BAR_COLORS[i % SIDING_BAR_COLORS.length]} ${isHovered ? 'opacity-100' : 'opacity-80'}`}
                                                        style={{ height: `${pct}%`, minHeight: 8 }}
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
    const totalRakes = useMemo(() => sidings.reduce((sum, s) => sum + (stocks[s.id]?.total_rakes ?? 0), 0), [sidings, stocks]);

    const barData = useMemo(
        () =>
            sidings.map((s) => {
                const st = stocks[s.id];
                return {
                    name: s.name,
                    opening: Math.round(st?.opening_balance_mt ?? 0),
                    closing: Math.round(st?.closing_balance_mt ?? 0),
                };
            }),
        [sidings, stocks],
    );

    const yDomain = useMemo(() => {
        const allValues = barData.flatMap((d) => [d.opening, d.closing]);
        const minVal = Math.min(...allValues);
        const maxVal = Math.max(...allValues);
        const padding = Math.max(50, Math.round((maxVal - minVal) * 0.15));
        const floor = Math.max(0, Math.floor((minVal - padding) / 50) * 50);
        const ceil = Math.ceil((maxVal + padding) / 50) * 50;
        return [floor, ceil] as [number, number];
    }, [barData]);

    return (
        <div className="rounded-xl border bg-card p-5">
            <SectionHeader icon={BarChart3} title="Siding stock" subtitle="Opening & closing balance with total rakes" />

            {/* Total rakes summary */}
            <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">Total rakes dispatched</p>
                    <p className="mt-1 text-2xl font-bold tabular-nums">{totalRakes}</p>
                </div>
                {sidings.map((s, i) => {
                    const st = stocks[s.id];
                    return (
                        <div key={s.id} className="rounded-lg border bg-muted/20 p-3.5">
                            <p className="text-xs font-medium text-muted-foreground">{s.name}</p>
                            <p className="mt-1 text-xl font-bold tabular-nums">{st?.total_rakes ?? 0} <span className="text-xs font-normal text-muted-foreground">rakes</span></p>
                            <div className="mt-1.5 flex items-center gap-3 text-xs text-muted-foreground">
                                <span className="flex items-center gap-1">
                                    <span className="inline-block size-2 rounded-full" style={{ backgroundColor: `var(--chart-${(i % 5) + 1})` }} />
                                    Open: {(st?.opening_balance_mt ?? 0).toLocaleString(undefined, { maximumFractionDigits: 0 })} MT
                                </span>
                                <span className="flex items-center gap-1">
                                    <span className="inline-block size-2 rounded-full bg-foreground/50" />
                                    Close: {(st?.closing_balance_mt ?? 0).toLocaleString(undefined, { maximumFractionDigits: 0 })} MT
                                </span>
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Opening vs Closing balance bar chart */}
            <div className="mt-5">
                <p className="mb-2 text-sm font-semibold">Opening vs closing balance (MT)</p>
                <div className="w-full">
                    <ResponsiveContainer width="100%" height={280}>
                        <RechartsBarChart data={barData} margin={{ top: 4, right: 4, bottom: 0, left: -12 }}>
                            <CartesianGrid strokeDasharray="3 3" className="stroke-border/50" />
                            <XAxis
                                dataKey="name"
                                tick={{ fontSize: 12 }}
                                className="fill-muted-foreground"
                                tickLine={false}
                                axisLine={false}
                            />
                            <YAxis
                                domain={yDomain}
                                tick={{ fontSize: 12 }}
                                className="fill-muted-foreground"
                                tickLine={false}
                                axisLine={false}
                                allowDecimals={false}
                                label={{
                                    value: 'MT',
                                    angle: -90,
                                    position: 'insideLeft',
                                    className: 'fill-muted-foreground',
                                    style: { fontSize: 11 },
                                }}
                            />
                            <Tooltip
                                contentStyle={{
                                    backgroundColor: 'var(--card)',
                                    borderColor: 'var(--border)',
                                    borderRadius: 8,
                                    fontSize: 12,
                                }}
                                formatter={(value: number, name: string) => [
                                    `${value.toLocaleString()} MT`,
                                    name === 'opening' ? 'Opening balance' : 'Closing balance',
                                ]}
                            />
                            <Legend
                                formatter={(value: string) => (value === 'opening' ? 'Opening balance' : 'Closing balance')}
                                wrapperStyle={{ fontSize: 12 }}
                            />
                            <Bar dataKey="opening" fill="var(--chart-1)" radius={[4, 4, 0, 0]} barSize={28} />
                            <Bar dataKey="closing" fill="var(--chart-3)" radius={[4, 4, 0, 0]} barSize={28} />
                        </RechartsBarChart>
                    </ResponsiveContainer>
                </div>
            </div>

            {/* Per-siding stock bars */}
            <div className="mt-5">
                <p className="mb-3 text-sm font-semibold">Stock balance per siding</p>
                <div className="space-y-3">
                    {sidings.map((s, i) => {
                        const st = stocks[s.id];
                        const opening = st?.opening_balance_mt ?? 0;
                        const closing = st?.closing_balance_mt ?? 0;
                        const maxBal = Math.max(opening, closing, 1);
                        const diff = closing - opening;
                        const diffColor = diff >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                        const diffSign = diff >= 0 ? '+' : '';
                        return (
                            <div key={s.id} className="group rounded-lg border bg-muted/20 p-3.5">
                                <div className="flex items-center justify-between">
                                    <span className="font-semibold">{s.name}</span>
                                    <span className={`text-sm font-bold tabular-nums ${diffColor}`}>
                                        {diffSign}{diff.toLocaleString(undefined, { maximumFractionDigits: 0 })} MT
                                    </span>
                                </div>
                                <div className="mt-2 space-y-1.5">
                                    <div className="flex items-center gap-2 text-xs">
                                        <span className="w-14 text-muted-foreground">Opening</span>
                                        <div className="h-2.5 flex-1 overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full rounded-full transition-all"
                                                style={{
                                                    width: `${Math.min(100, (opening / maxBal) * 100)}%`,
                                                    backgroundColor: `var(--chart-${(i % 5) + 1})`,
                                                }}
                                            />
                                        </div>
                                        <span className="w-16 text-right font-medium tabular-nums">{opening.toLocaleString(undefined, { maximumFractionDigits: 0 })} MT</span>
                                    </div>
                                    <div className="flex items-center gap-2 text-xs">
                                        <span className="w-14 text-muted-foreground">Closing</span>
                                        <div className="h-2.5 flex-1 overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full rounded-full transition-all"
                                                style={{
                                                    width: `${Math.min(100, (closing / maxBal) * 100)}%`,
                                                    backgroundColor: `var(--chart-${(i % 5) + 1})`,
                                                    opacity: 0.6,
                                                }}
                                            />
                                        </div>
                                        <span className="w-16 text-right font-medium tabular-nums">{closing.toLocaleString(undefined, { maximumFractionDigits: 0 })} MT</span>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

function SidingPerformanceSection({ data }: { data: SidingPerformanceItem[] }) {
    const chartData = useMemo(
        () => data.map((s) => ({ ...s, name: s.name, rakes: s.rakes, penalties: s.penalties, penalty_amount: s.penalty_amount, penalty_rate: s.penalty_rate })),
        [data],
    );

    return (
        <div className="rounded-xl border bg-card p-5">
            <SectionHeader icon={BarChart3} title="Siding performance" subtitle="Rakes, penalties & penalty rate" />

            {/* Charts grid */}
            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                {/* Rakes vs Penalties grouped bar */}
                <div>
                    <p className="mb-2 text-xs font-medium text-muted-foreground">Rakes dispatched vs penalties</p>
                    <StackedBarChart
                        data={chartData as Record<string, unknown>[]}
                        xKey="name"
                        stackKeys={['rakes', 'penalties']}
                        stackLabels={{ rakes: 'Rakes dispatched', penalties: 'Penalties' }}
                        stackColors={{ rakes: 'var(--chart-1)', penalties: '#ef4444' }}
                        yLabel="Count"
                        height={260}
                        allowDecimals={false}
                        formatTooltip={(v) => `${v}`}
                    />
                </div>

                {/* Penalty amount per siding */}
                <div>
                    <p className="mb-2 text-xs font-medium text-muted-foreground">Penalty amount by siding</p>
                    <BarChart
                        data={chartData as Record<string, unknown>[]}
                        xKey="name"
                        yKey="penalty_amount"
                        yLabel="₹"
                        height={260}
                        color="#ef4444"
                        formatTooltip={(v) => `₹${v.toLocaleString()}`}
                    />
                </div>
            </div>

            {/* Penalty rate visual bars */}
            <div className="mt-5">
                <p className="mb-3 text-xs font-medium text-muted-foreground">Penalty rate by siding</p>
                <div className="space-y-3">
                    {data.map((s) => {
                        const rateColor =
                            s.penalty_rate > 50
                                ? 'bg-red-500'
                                : s.penalty_rate > 25
                                  ? 'bg-amber-500'
                                  : 'bg-green-500';
                        const rateTextColor =
                            s.penalty_rate > 50
                                ? 'text-red-600 dark:text-red-400'
                                : s.penalty_rate > 25
                                  ? 'text-amber-600 dark:text-amber-400'
                                  : 'text-green-600 dark:text-green-400';
                        return (
                            <div key={s.name} className="group">
                                <div className="mb-1 flex items-center justify-between text-sm">
                                    <span className="font-medium">{s.name}</span>
                                    <span className={`font-bold tabular-nums ${rateTextColor}`}>{s.penalty_rate}%</span>
                                </div>
                                <div className="h-3 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        className={`h-full rounded-full transition-all duration-500 ${rateColor}`}
                                        style={{ width: `${Math.min(s.penalty_rate, 100)}%` }}
                                    />
                                </div>
                                <div className="mt-0.5 flex justify-between text-xs text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100">
                                    <span>{s.rakes} rakes, {s.penalties} penalties</span>
                                    <span>{formatCurrency(s.penalty_amount)}</span>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

const DISPATCH_COLORS = ['var(--chart-1)', 'var(--chart-2)', 'var(--chart-3)', 'var(--chart-4)', 'var(--chart-5)', '#8b5cf6', '#ec4899', '#14b8a6'];
const PENALTY_COLORS = ['#ef4444', '#f97316', '#eab308', '#f43f5e', '#d946ef', '#a855f7', '#e11d48', '#c2410c'];

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
                            color="var(--chart-2)"
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
                        barColor="var(--chart-1)"
                        lineColor="var(--chart-4)"
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
                        color="var(--chart-4)"
                        formatTooltip={(v) => `${v} wagons`}
                    />
                </div>
            </div>
        </div>
    );
}

function DashboardFiltersBar({ sidings, filters }: { sidings: SidingOption[]; filters: DashboardFilters }) {
    const [customFrom, setCustomFrom] = useState(filters.from);
    const [customTo, setCustomTo] = useState(filters.to);
    const [showSidingDropdown, setShowSidingDropdown] = useState(false);

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
            period: filters.period,
            ...overrides,
        };

        if (params.period === 'custom') {
            params.from = overrides.from ?? customFrom;
            params.to = overrides.to ?? customTo;
        }

        const sidingIds = (overrides.siding_ids as number[] | undefined) ?? filters.siding_ids;
        if (sidingIds.length > 0 && sidingIds.length < allSidingIds.length) {
            params.siding_ids = sidingIds;
        }

        router.get(dashboard().url, params as Record<string, string>, {
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
    const sidings = props.sidings ?? [];
    const filters = props.filters ?? { period: 'month', from: '', to: '', siding_ids: [] };
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
                <h2 className="text-xl font-semibold tracking-tight">
                    Management Dashboard
                </h2>

                {sidings.length > 0 && (
                    <DashboardFiltersBar sidings={sidings} filters={filters} />
                )}

                {/* ═══════════════════════════════════════════
                    1. SIDING-WISE DASHBOARD (Pakur / Dumka / Kurwa)
                    ═══════════════════════════════════════════ */}

                {sidings.length > 0 && (
                    <div className="space-y-4">
                        {Object.keys(sidingStocks).length > 0 && (
                            <SidingStockSection sidings={filteredSidings} stocks={sidingStocks} />
                        )}

                        {sidingRadar.sidings.length > 0 && (
                            <SidingComparisonVertical data={sidingRadar.sidings} />
                        )}

                        {sidingWiseMonthly.length > 0 && sidingStackKeys.length > 0 && (
                            <div className="rounded-xl border bg-card p-5">
                                <SectionHeader icon={BarChart3} title="Siding-wise monthly dispatch" subtitle="Rakes dispatched per siding" />
                                <div className="mt-4">
                                    <StackedBarChart
                                        data={sidingWiseMonthly}
                                        xKey="month"
                                        stackKeys={sidingStackKeys}
                                        yLabel="Rakes"
                                        height={300}
                                        allowDecimals={false}
                                        formatTooltip={(v) => `${v} rakes`}
                                    />
                                </div>
                            </div>
                        )}

                        {sidingPerformance.length > 0 && (
                            <SidingPerformanceSection data={sidingPerformance} />
                        )}
                    </div>
                )}

                {/* ═══════════════════════════════════════════
                    2. DATE-WISE RAIL DISPATCH & PENALTIES
                    ═══════════════════════════════════════════ */}

                {dateWiseDispatch.dates.length > 0 && (
                    <DateWiseDispatchSection data={dateWiseDispatch} />
                )}

                {/* ═══════════════════════════════════════════
                    3. RAKE-WISE PERFORMANCE
                    ═══════════════════════════════════════════ */}

                {rakePerformance.length > 0 && (
                    <RakePerformanceSection rakes={rakePerformance} />
                )}

                {/* ═══════════════════════════════════════════
                    4. LOADER-WISE OVERLOADING TRENDS
                    ═══════════════════════════════════════════ */}

                {loaderOverloadTrends.loaders.length > 0 && (
                    <LoaderOverloadSection
                        loaders={loaderOverloadTrends.loaders}
                        monthly={loaderOverloadTrends.monthly}
                    />
                )}

                {/* ═══════════════════════════════════════════
                    5. POWER PLANT WISE DISPATCH
                    ═══════════════════════════════════════════ */}

                {powerPlantDispatch.length > 0 && (
                    <div className="rounded-xl border bg-card p-5">
                        <SectionHeader
                            icon={Factory}
                            title="Power plant wise dispatch"
                            subtitle="Summary by destination power plant"
                        />
                        <div className="mt-4 grid gap-4 lg:grid-cols-3">
                            <div className="lg:col-span-2">
                                <BarChart
                                    data={powerPlantDispatch}
                                    xKey="name"
                                    yKey="weight_mt"
                                    yLabel="Weight dispatched"
                                    height={280}
                                    color="var(--chart-2)"
                                    formatY={(v) => formatWeight(v)}
                                    formatTooltip={(v) => `${v.toLocaleString()} MT`}
                                />
                            </div>
                            <div className="space-y-2">
                                {powerPlantDispatch.map((pp, i) => (
                                    <div key={pp.name} className="rounded-lg border bg-muted/20 p-3">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm font-semibold">{pp.name}</span>
                                            <span className={
                                                'rounded-full px-2 py-0.5 text-xs font-semibold ' +
                                                (Math.abs(pp.avg_variance_pct) > 2
                                                    ? 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-400'
                                                    : 'bg-green-100 text-green-700 dark:bg-green-950/50 dark:text-green-400')
                                            }>
                                                {pp.avg_variance_pct > 0 ? '+' : ''}{pp.avg_variance_pct}% variance
                                            </span>
                                        </div>
                                        <div className="mt-2 flex items-center gap-4 text-xs text-muted-foreground">
                                            <span className="tabular-nums">{pp.rakes} rakes</span>
                                            <span className="tabular-nums">{formatWeight(pp.weight_mt)}</span>
                                        </div>
                                        <div className="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full rounded-full"
                                                style={{
                                                    width: `${Math.min(100, (pp.weight_mt / Math.max(...powerPlantDispatch.map((p) => p.weight_mt))) * 100)}%`,
                                                    backgroundColor: `var(--chart-${(i % 5) + 1})`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}

                {sidings.length === 0 && (
                    <div className="rounded-xl border bg-card p-8 text-center text-sm text-muted-foreground">
                        <p>No sidings assigned to your account. Contact your administrator to get access.</p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
