import { AreaChart } from '@/components/charts/area-chart';
import { BarChart } from '@/components/charts/bar-chart';
import { PieChart } from '@/components/charts/pie-chart';
import Heading from '@/components/heading';
import { RrmcsGuidance } from '@/components/rrmcs-guidance';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Deferred, Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowDown,
    ArrowUp,
    BarChart3,
    Scale,
    ShieldCheck,
    Sparkles,
    TrendingDown,
} from 'lucide-react';

interface StatusBreakdown {
    status: string;
    count: number;
    total: number;
}

interface SummaryCards {
    total_penalties: number;
    total_amount: number;
    by_status: StatusBreakdown[];
    disputed_count: number;
    waived_count: number;
    dispute_success_rate: number;
    avg_penalty: number;
}

interface NameValueCount {
    name: string;
    value: number;
    count: number;
}

interface SidingBreakdown {
    name: string;
    total: number;
    count: number;
    types: Record<string, number>;
}

interface MonthlyPoint {
    month: string;
    total: number;
    count: number;
}

interface TopOffender {
    rake_number: string;
    siding_name: string;
    total: number;
    count: number;
    types: string;
}

interface WeekdayPoint {
    day: number;
    count: number;
    total: number;
}

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface AiInsight {
    title: string;
    description: string;
    severity: 'high' | 'medium' | 'low';
}

interface Props {
    summaryCards: SummaryCards;
    byResponsibleParty: NameValueCount[];
    byType: NameValueCount[];
    bySiding: SidingBreakdown[];
    monthlyTrend: MonthlyPoint[];
    topOffenders: TopOffender[];
    weekdayHeatmap: WeekdayPoint[];
    sidings: Siding[];
    aiInsights?: AiInsight[] | null;
}

const SEVERITY_COLORS: Record<string, string> = {
    high: 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/30',
    medium: 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30',
    low: 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/30',
};

const SEVERITY_DOT: Record<string, string> = {
    high: 'bg-red-500',
    medium: 'bg-amber-500',
    low: 'bg-blue-500',
};

function AiInsightsCard() {
    const { aiInsights } = usePage<Props>().props;

    if (!aiInsights || aiInsights.length === 0) return null;

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Sparkles className="h-5 w-5 text-primary" />
                    AI Penalty Insights
                </CardTitle>
                <CardDescription>
                    AI-generated recommendations based on penalty patterns (last 3 months)
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="space-y-3">
                    {aiInsights.map((insight, i) => (
                        <div
                            key={`${insight.title}-${i}`}
                            className={`rounded-lg border p-3 ${SEVERITY_COLORS[insight.severity] ?? ''}`}
                        >
                            <div className="flex items-center gap-2">
                                <span className={`inline-block size-2 rounded-full ${SEVERITY_DOT[insight.severity] ?? 'bg-muted'}`} />
                                <span className="text-sm font-medium">{insight.title}</span>
                            </div>
                            <p className="mt-1 pl-4 text-xs text-muted-foreground">
                                {insight.description}
                            </p>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

function formatCurrency(n: number): string {
    if (n >= 100000) return `₹${(n / 100000).toFixed(1)}L`;
    if (n >= 1000) return `₹${(n / 1000).toFixed(1)}K`;
    return `₹${n.toFixed(0)}`;
}

function StatusBadge({ status }: { status: string }) {
    const colors: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        incurred: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        disputed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        waived: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    };
    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${colors[status] ?? 'bg-muted text-muted-foreground'}`}>
            {status}
        </span>
    );
}

export default function PenaltyAnalytics({
    summaryCards,
    byResponsibleParty,
    byType,
    bySiding,
    monthlyTrend,
    topOffenders,
    weekdayHeatmap,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Penalties', href: '/penalties' },
        { title: 'Analytics', href: '/penalties/analytics' },
    ];

    // Compute month-over-month change
    const lastTwo = monthlyTrend.slice(-2);
    const currentMonth = lastTwo.length === 2 ? lastTwo[1].total : 0;
    const previousMonth = lastTwo.length === 2 ? lastTwo[0].total : 0;
    const momChange = previousMonth > 0
        ? ((currentMonth - previousMonth) / previousMonth) * 100
        : 0;

    // Compute 3-month rolling average for trend
    const trendWithAvg = monthlyTrend.map((point, idx) => {
        const start = Math.max(0, idx - 2);
        const window = monthlyTrend.slice(start, idx + 1);
        const avg = window.reduce((sum, p) => sum + p.total, 0) / window.length;
        return { ...point, rollingAvg: Math.round(avg * 100) / 100 };
    });

    // Prepare weekday bar data
    const weekdayData = DAY_NAMES.map((name, i) => {
        const found = weekdayHeatmap.find((w) => w.day === i);
        return { name, count: found?.count ?? 0, total: found?.total ?? 0 };
    });

    // Prepare bySiding data for horizontal bar chart
    const sidingBarData = bySiding.slice(0, 10).map((s) => ({
        name: s.name,
        total: s.total,
    }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Penalty Analytics" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Penalty Analytics"
                        description="Root-cause analysis, trends, and responsibility tracking (last 12 months)"
                    />
                    <Link href="/penalties">
                        <Button variant="outline" size="sm" data-pan="penalty-analytics-tab">
                            <Scale className="mr-1.5 h-4 w-4" />
                            Penalty Register
                        </Button>
                    </Link>
                </div>

                <RrmcsGuidance
                    title="Why penalty analytics matters"
                    before="Penalties discovered after the fact — no way to see patterns, root causes, or who is responsible."
                    after="Real-time visibility into penalty trends, root-cause attribution, and dispute tracking helps reduce future penalties."
                />

                {/* AI Insights (deferred) */}
                <Deferred data="aiInsights" fallback={
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Sparkles className="h-5 w-5 text-primary/50" />
                                <span className="text-muted-foreground">Loading AI insights…</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="animate-pulse space-y-3">
                                {[1, 2, 3].map((i) => (
                                    <div key={i} className="rounded-lg border p-3">
                                        <div className="h-4 w-3/4 rounded bg-muted" />
                                        <div className="mt-2 h-3 w-full rounded bg-muted" />
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                }>
                    <AiInsightsCard />
                </Deferred>

                {/* Summary Cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Total Penalties (12M)</CardDescription>
                            <CardTitle className="text-2xl">
                                {summaryCards.total_penalties}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-xs text-muted-foreground">
                                {formatCurrency(summaryCards.total_amount)} total amount
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Avg Penalty Amount</CardDescription>
                            <CardTitle className="text-2xl">
                                {formatCurrency(summaryCards.avg_penalty)}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-1.5 text-xs">
                                {momChange > 0 ? (
                                    <span className="flex items-center text-red-600 dark:text-red-400">
                                        <ArrowUp className="h-3 w-3" />
                                        {Math.abs(momChange).toFixed(1)}%
                                    </span>
                                ) : momChange < 0 ? (
                                    <span className="flex items-center text-green-600 dark:text-green-400">
                                        <ArrowDown className="h-3 w-3" />
                                        {Math.abs(momChange).toFixed(1)}%
                                    </span>
                                ) : null}
                                <span className="text-muted-foreground">vs last month</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Dispute Success Rate</CardDescription>
                            <CardTitle className="flex items-center gap-2 text-2xl">
                                <ShieldCheck className="h-5 w-5 text-green-600 dark:text-green-400" />
                                {summaryCards.dispute_success_rate}%
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-xs text-muted-foreground">
                                {summaryCards.waived_count} waived of {summaryCards.disputed_count + summaryCards.waived_count} disputed
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>By Status</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-2">
                                {summaryCards.by_status.map((s) => (
                                    <Link
                                        key={s.status}
                                        href={`/penalties?status=${s.status}`}
                                        data-pan="penalty-drill-down"
                                    >
                                        <StatusBadge status={s.status} />
                                        <span className="ml-1 text-xs font-medium">{s.count}</span>
                                    </Link>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Penalty Trend Chart */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <TrendingDown className="h-5 w-5" />
                            Penalty Trend (12 Months)
                        </CardTitle>
                        <CardDescription>
                            Monthly penalty amounts with 3-month rolling average
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <AreaChart
                            data={trendWithAvg}
                            xKey="month"
                            yKey="total"
                            secondaryYKey="rollingAvg"
                            secondaryLabel="3-month avg"
                            yLabel="Amount (₹)"
                            formatY={formatCurrency}
                            formatTooltip={(v) => formatCurrency(v)}
                            height={300}
                        />
                    </CardContent>
                </Card>

                {/* Two-column: Type Distribution + Responsible Party */}
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>By Penalty Type</CardTitle>
                            <CardDescription>
                                Distribution by type — click to filter
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {byType.length === 0 ? (
                                <p className="py-8 text-center text-sm text-muted-foreground">No data</p>
                            ) : (
                                <>
                                    <PieChart
                                        data={byType}
                                        nameKey="name"
                                        valueKey="value"
                                        formatTooltip={formatCurrency}
                                        height={260}
                                    />
                                    <div className="mt-3 space-y-1">
                                        {byType.map((t) => (
                                            <Link
                                                key={t.name}
                                                href={`/penalties?type=${t.name}`}
                                                className="flex items-center justify-between rounded px-2 py-1 text-xs hover:bg-muted/50"
                                                data-pan="penalty-drill-down"
                                            >
                                                <span className="font-medium">{t.name}</span>
                                                <span className="text-muted-foreground">
                                                    {t.count} &middot; {formatCurrency(t.value)}
                                                </span>
                                            </Link>
                                        ))}
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>By Responsible Party</CardTitle>
                            <CardDescription>
                                Who caused the penalties — attribution breakdown
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {byResponsibleParty.length === 0 ? (
                                <div className="flex flex-col items-center gap-2 py-8 text-center">
                                    <AlertTriangle className="h-8 w-8 text-muted-foreground" />
                                    <p className="text-sm text-muted-foreground">
                                        No responsibility data yet. Assign responsible parties on the{' '}
                                        <Link href="/penalties" className="underline underline-offset-4">
                                            penalty register
                                        </Link>
                                        .
                                    </p>
                                </div>
                            ) : (
                                <>
                                    <BarChart
                                        data={byResponsibleParty}
                                        xKey="name"
                                        yKey="value"
                                        yLabel="Amount (₹)"
                                        formatY={formatCurrency}
                                        formatTooltip={formatCurrency}
                                        color="var(--chart-3)"
                                        height={260}
                                    />
                                    <div className="mt-3 space-y-1">
                                        {byResponsibleParty.map((r) => (
                                            <Link
                                                key={r.name}
                                                href={`/penalties?responsible_party=${r.name.toLowerCase()}`}
                                                className="flex items-center justify-between rounded px-2 py-1 text-xs hover:bg-muted/50"
                                                data-pan="penalty-drill-down"
                                            >
                                                <span className="font-medium">{r.name}</span>
                                                <span className="text-muted-foreground">
                                                    {r.count} penalties &middot; {formatCurrency(r.value)}
                                                </span>
                                            </Link>
                                        ))}
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Two-column: Siding Comparison + Weekday Heatmap */}
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>By Siding</CardTitle>
                            <CardDescription>
                                Penalty amounts per siding — click to filter
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {sidingBarData.length === 0 ? (
                                <p className="py-8 text-center text-sm text-muted-foreground">No data</p>
                            ) : (
                                <BarChart
                                    data={sidingBarData}
                                    xKey="name"
                                    yKey="total"
                                    layout="vertical"
                                    formatY={formatCurrency}
                                    formatTooltip={formatCurrency}
                                    color="var(--chart-4)"
                                    height={Math.max(200, sidingBarData.length * 40)}
                                />
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>
                                <BarChart3 className="mr-1.5 inline h-5 w-5" />
                                Penalties by Day of Week
                            </CardTitle>
                            <CardDescription>
                                When do penalties cluster?
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <BarChart
                                data={weekdayData}
                                xKey="name"
                                yKey="count"
                                yLabel="Count"
                                color="var(--chart-5)"
                                height={260}
                                formatTooltip={(v) => `${v} penalties`}
                            />
                        </CardContent>
                    </Card>
                </div>

                {/* Top Offenders Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Top Offenders</CardTitle>
                        <CardDescription>
                            Rakes with highest cumulative penalties (12 months)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {topOffenders.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                No penalty data found.
                            </p>
                        ) : (
                            <div className="overflow-x-auto rounded-md border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="px-4 py-3 text-left font-medium">#</th>
                                            <th className="px-4 py-3 text-left font-medium">Rake</th>
                                            <th className="px-4 py-3 text-left font-medium">Siding</th>
                                            <th className="px-4 py-3 text-left font-medium">Types</th>
                                            <th className="px-4 py-3 text-right font-medium">Incidents</th>
                                            <th className="px-4 py-3 text-right font-medium">Total Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {topOffenders.map((o, i) => (
                                            <tr key={`${o.rake_number}-${i}`} className="border-b last:border-0 hover:bg-muted/30">
                                                <td className="px-4 py-3 text-muted-foreground">{i + 1}</td>
                                                <td className="px-4 py-3 font-medium">
                                                    <Link
                                                        href={`/penalties?rake_id=${o.rake_number}`}
                                                        className="underline underline-offset-4"
                                                        data-pan="penalty-drill-down"
                                                    >
                                                        {o.rake_number}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3">{o.siding_name}</td>
                                                <td className="px-4 py-3">
                                                    <div className="flex flex-wrap gap-1">
                                                        {o.types.split(',').map((t) => (
                                                            <span
                                                                key={t}
                                                                className="inline-flex rounded bg-muted px-1.5 py-0.5 text-xs"
                                                            >
                                                                {t}
                                                            </span>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-right">{o.count}</td>
                                                <td className="px-4 py-3 text-right font-medium text-red-600 dark:text-red-400">
                                                    {formatCurrency(o.total)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
