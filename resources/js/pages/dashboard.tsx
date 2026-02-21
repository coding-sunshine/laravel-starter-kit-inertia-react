import { AreaChart } from '@/components/charts/area-chart';
import { BarChart } from '@/components/charts/bar-chart';
import { PieChart } from '@/components/charts/pie-chart';
import { RrmcsGuidance } from '@/components/rrmcs-guidance';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { create as contactCreate } from '@/routes/contact';
import { exportPdf } from '@/routes/profile';
import { index as rakes } from '@/routes/rakes';
import { edit as editProfile } from '@/routes/user-profile';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Deferred, Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowDown,
    ArrowUp,
    BarChart3,
    ClipboardList,
    DollarSign,
    FileText,
    LifeBuoy,
    Mail,
    Minus,
    Settings,
    ShieldCheck,
    Sparkles,
    Train,
    Truck,
    UserPen,
    Wallet,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardSummary {
    rakesByState: Record<string, number>;
    totalRakes: number;
    penaltiesThisMonth: number;
    indentsPending: number;
    indentsAcknowledged: number;
    vehiclesReceivedToday: number;
}

interface SidingStock {
    siding_id: number;
    closing_balance_mt: number;
}

interface ActiveRake {
    id: number;
    rake_number: string;
    siding: { id: number; name: string; code: string } | null;
    loading_start_time: string;
    free_time_minutes: number;
    remaining_minutes: number;
}

interface AlertItem {
    id: number;
    type: string;
    title: string;
    severity: string;
    rake_id?: number;
    siding_id?: number;
    created_at: string;
}

interface PenaltyChartPoint {
    month: string;
    total: number;
    count: number;
    rollingAvg?: number;
}

interface NameValuePoint {
    name: string;
    value: number;
}

interface NameValueCountPoint {
    name: string;
    value: number;
    count: number;
}

interface PenaltyBySidingPoint {
    name: string;
    total: number;
}

interface CostAvoidance {
    rakes_within_free_time: number;
    rakes_with_penalties: number;
    money_saved: number;
    money_lost: number;
}

interface DisputeOpportunity {
    potential_savings: number;
    undisputed_count: number;
}

interface FinancialImpact {
    ytd_total: number;
    projected_annual: number;
    cost_per_rake: number;
    worst_siding: string | null;
    trend_direction: 'up' | 'down' | 'flat';
}

interface SidingPerformanceItem {
    name: string;
    rakes: number;
    penalties: number;
    penalty_amount: number;
    penalty_rate: number;
}

interface SidingOption {
    id: number;
    name: string;
    code: string;
}

type DashboardProps = SharedData & {
    summary?: DashboardSummary;
    sidingStocks?: Record<number, SidingStock>;
    activeRakes?: ActiveRake[];
    alerts?: AlertItem[];
    penaltyChartData?: PenaltyChartPoint[];
    penaltyByType?: NameValuePoint[];
    penaltyBySiding?: PenaltyBySidingPoint[];
    costAvoidance?: CostAvoidance;
    financialImpact?: FinancialImpact;
    rakeStateChart?: NameValuePoint[];
    indentPipeline?: NameValuePoint[];
    penaltyStatusBreakdown?: NameValueCountPoint[];
    responsiblePartyBreakdown?: NameValueCountPoint[];
    sidingPerformance?: SidingPerformanceItem[];
    disputeOpportunity?: DisputeOpportunity;
    sidings?: SidingOption[];
    aiBriefing?: string | null;
};

function formatRemainingMinutes(m: number): string {
    if (m <= 0) return '0m';
    const h = Math.floor(m / 60);
    const min = m % 60;
    if (h > 0) return `${h}h ${min}m`;
    return `${min}m`;
}

function formatCurrency(n: number): string {
    if (n >= 100000) return `₹${(n / 100000).toFixed(1)}L`;
    if (n >= 1000) return `₹${(n / 1000).toFixed(1)}K`;
    return `₹${n.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
}

function DemurrageTimer({ rake }: { rake: ActiveRake }) {
    const [remaining, setRemaining] = useState(rake.remaining_minutes);

    useEffect(() => {
        const t = setInterval(() => {
            setRemaining((r) => Math.max(0, r - 1));
        }, 60_000);
        return () => clearInterval(t);
    }, []);

    const isLow = remaining <= 30;
    const isCritical = remaining <= 0;

    return (
        <div
            className={
                'rounded-md border p-3 ' +
                (isCritical
                    ? 'border-red-500 bg-red-50 dark:bg-red-950/30'
                    : isLow
                      ? 'border-amber-500 bg-amber-50 dark:bg-amber-950/30'
                      : 'border-border bg-muted/50')
            }
        >
            <div className="flex items-center justify-between gap-2">
                <div>
                    <span className="font-medium">{rake.rake_number}</span>
                    {rake.siding && (
                        <span className="ml-2 text-sm text-muted-foreground">
                            {rake.siding.name}
                        </span>
                    )}
                </div>
                <span
                    className={
                        'font-semibold tabular-nums ' +
                        (isCritical
                            ? 'text-red-600 dark:text-red-400'
                            : isLow
                              ? 'text-amber-600 dark:text-amber-400'
                              : 'text-foreground')
                    }
                >
                    {formatRemainingMinutes(remaining)} left
                </span>
            </div>
        </div>
    );
}

function TrendIcon({ direction }: { direction: string }) {
    if (direction === 'up')
        return <ArrowUp className="size-4 text-red-500" />;
    if (direction === 'down')
        return <ArrowDown className="size-4 text-green-500" />;
    return <Minus className="size-4 text-muted-foreground" />;
}

function AiBriefingCard() {
    const { aiBriefing } = usePage<DashboardProps>().props;

    return (
        <div className="rounded-lg border bg-card p-5">
            <div className="flex items-center gap-2.5">
                <div className="flex size-8 items-center justify-center rounded-lg bg-primary/10">
                    <Sparkles className="size-4 text-primary" />
                </div>
                <h3 className="text-sm font-medium">AI Daily Briefing</h3>
            </div>
            {aiBriefing ? (
                <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                    {aiBriefing}
                </p>
            ) : (
                <p className="mt-3 text-sm text-muted-foreground/60">
                    AI briefing is temporarily unavailable. It will retry automatically.
                </p>
            )}
        </div>
    );
}

export default function Dashboard() {
    const props = usePage<DashboardProps>().props;
    const { auth, features } = props;
    const summary = props.summary ?? null;
    const sidingStocks = props.sidingStocks ?? {};
    const activeRakes = props.activeRakes ?? [];
    const alerts = props.alerts ?? [];
    const penaltyChartData = props.penaltyChartData ?? [];
    const penaltyByType = props.penaltyByType ?? [];
    const penaltyBySiding = props.penaltyBySiding ?? [];
    const costAvoidance = props.costAvoidance ?? null;
    const financialImpact = props.financialImpact ?? null;
    const rakeStateChart = props.rakeStateChart ?? [];
    const indentPipeline = props.indentPipeline ?? [];
    const penaltyStatusBreakdown = props.penaltyStatusBreakdown ?? [];
    const responsiblePartyBreakdown = props.responsiblePartyBreakdown ?? [];
    const sidingPerformance = props.sidingPerformance ?? [];
    const disputeOpportunity = props.disputeOpportunity ?? null;
    const sidings = props.sidings ?? [];

    const f = features ?? {};
    const showPdfExport = f.profile_pdf_export ?? false;
    const showApiDocs = f.scramble_api_docs ?? false;
    const showContact = f.contact ?? false;
    const isSuperAdmin = auth.roles?.includes('super-admin') ?? false;
    const canAccessAdmin =
        (auth.permissions?.includes('access admin panel') ?? false) ||
        auth.can_bypass === true;

    const hasRrmcs = sidings.length > 0;

    const penaltyTrendWithAvg = useMemo(() => {
        if (penaltyChartData.length === 0) return [];
        return penaltyChartData.map((point, i, arr) => {
            const window = arr.slice(Math.max(0, i - 2), i + 1);
            const avg =
                window.reduce((sum, p) => sum + p.total, 0) / window.length;
            return { ...point, rollingAvg: Math.round(avg) };
        });
    }, [penaltyChartData]);

    const quickActions = [
        {
            label: 'Edit profile',
            href: editProfile(),
            icon: UserPen,
            show: true,
            dataPan: 'dashboard-quick-edit-profile',
        },
        {
            label: 'Settings',
            href: '/settings',
            icon: Settings,
            show: true,
            dataPan: 'dashboard-quick-settings',
        },
        {
            label: 'Export profile (PDF)',
            href: exportPdf().url,
            icon: FileText,
            show: showPdfExport,
            external: true,
            dataPan: 'dashboard-quick-export-pdf',
        },
        {
            label: 'Contact support',
            href: contactCreate().url,
            icon: LifeBuoy,
            show: showContact,
            dataPan: 'dashboard-quick-contact',
        },
        {
            label: 'Email templates',
            href: '/admin/mail-templates',
            icon: Mail,
            show: isSuperAdmin,
            external: true,
            dataPan: 'dashboard-quick-email-templates',
        },
        {
            label: 'Product analytics',
            href: '/admin/analytics/product',
            icon: BarChart3,
            show: canAccessAdmin,
            external: true,
            dataPan: 'dashboard-quick-product-analytics',
        },
    ].filter((a) => a.show);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="text-lg font-medium">
                        Welcome back, {auth.user.name}
                    </h2>
                    <div className="flex flex-wrap items-center gap-2">
                        {showApiDocs && (
                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href="/docs/api"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    API documentation
                                </a>
                            </Button>
                        )}
                    </div>
                </div>

                {alerts.length > 0 && (
                    <div className="flex flex-col gap-2">
                        {alerts.map((alert) => (
                            <div
                                key={alert.id}
                                className={
                                    'flex items-center justify-between gap-2 rounded-lg border px-4 py-2 ' +
                                    (alert.severity === 'critical'
                                        ? 'border-red-500 bg-red-50 dark:bg-red-950/30'
                                        : alert.severity === 'warning'
                                          ? 'border-amber-500 bg-amber-50 dark:bg-amber-950/30'
                                          : 'border-border bg-muted/50')
                                }
                            >
                                <div className="flex items-center gap-2">
                                    <AlertTriangle className="size-4 shrink-0" />
                                    <span className="text-sm font-medium">
                                        {alert.title}
                                    </span>
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        router.put(
                                            `/alerts/${alert.id}/resolve`,
                                            { redirect: '/dashboard' },
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    Resolve
                                </Button>
                            </div>
                        ))}
                    </div>
                )}

                {hasRrmcs && (
                    <Deferred data="aiBriefing" fallback={
                        <div className="animate-pulse rounded-lg border bg-card p-5">
                            <div className="flex items-center gap-2.5">
                                <div className="flex size-8 items-center justify-center rounded-lg bg-primary/10">
                                    <Sparkles className="size-4 text-primary/50" />
                                </div>
                                <div className="h-4 w-24 rounded bg-muted" />
                            </div>
                            <div className="mt-3 space-y-2">
                                <div className="h-3 w-full rounded bg-muted" />
                                <div className="h-3 w-4/5 rounded bg-muted" />
                                <div className="h-3 w-3/5 rounded bg-muted" />
                            </div>
                        </div>
                    }>
                        <AiBriefingCard />
                    </Deferred>
                )}

                {hasRrmcs && summary && (
                    <>
                        {/* KPI stat cards */}
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                            <div className="rounded-lg border bg-card p-4">
                                <div className="flex items-center gap-2 text-muted-foreground">
                                    <Train className="size-4" />
                                    <span className="text-xs">Active rakes</span>
                                </div>
                                <p className="mt-1 text-2xl font-semibold">
                                    {summary.totalRakes}
                                </p>
                            </div>
                            <div className="rounded-lg border bg-card p-4">
                                <div className="flex items-center gap-2 text-muted-foreground">
                                    <ClipboardList className="size-4" />
                                    <span className="text-xs">Pending indents</span>
                                </div>
                                <p className="mt-1 text-2xl font-semibold">
                                    {summary.indentsPending}
                                </p>
                            </div>
                            <div className="rounded-lg border bg-card p-4">
                                <div className="flex items-center gap-2 text-muted-foreground">
                                    <AlertTriangle className="size-4" />
                                    <span className="text-xs">Penalties (month)</span>
                                </div>
                                <p className="mt-1 text-2xl font-semibold">
                                    {formatCurrency(summary.penaltiesThisMonth)}
                                </p>
                            </div>
                            <div className="rounded-lg border bg-card p-4">
                                <div className="flex items-center gap-2 text-muted-foreground">
                                    <Truck className="size-4" />
                                    <span className="text-xs">Vehicles today</span>
                                </div>
                                <p className="mt-1 text-2xl font-semibold">
                                    {summary.vehiclesReceivedToday}
                                </p>
                            </div>
                            {costAvoidance && (
                                <div className="rounded-lg border bg-card p-4">
                                    <div className="flex items-center gap-2 text-muted-foreground">
                                        <ShieldCheck className="size-4" />
                                        <span className="text-xs">Money saved</span>
                                    </div>
                                    <p className="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                                        {formatCurrency(costAvoidance.money_saved)}
                                    </p>
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        {costAvoidance.rakes_within_free_time} rakes in free time
                                    </p>
                                </div>
                            )}
                            {costAvoidance && (
                                <div className="rounded-lg border bg-card p-4">
                                    <div className="flex items-center gap-2 text-muted-foreground">
                                        <Wallet className="size-4" />
                                        <span className="text-xs">Money lost</span>
                                    </div>
                                    <p className="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">
                                        {formatCurrency(costAvoidance.money_lost)}
                                    </p>
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        {costAvoidance.rakes_with_penalties} penalised rakes
                                    </p>
                                </div>
                            )}
                            {financialImpact && (
                                <div className="rounded-lg border bg-card p-4">
                                    <div className="flex items-center gap-2 text-muted-foreground">
                                        <span className="text-xs">Avg cost/rake</span>
                                        <TrendIcon direction={financialImpact.trend_direction} />
                                    </div>
                                    <p className="mt-1 text-2xl font-semibold">
                                        {formatCurrency(financialImpact.cost_per_rake)}
                                    </p>
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        {financialImpact.trend_direction === 'down'
                                            ? 'Improving'
                                            : financialImpact.trend_direction === 'up'
                                              ? 'Worsening'
                                              : 'Stable'}{' '}
                                        vs last month
                                    </p>
                                </div>
                            )}
                            {financialImpact && financialImpact.ytd_total > 0 && (
                                <div className="rounded-lg border bg-card p-4">
                                    <div className="flex items-center gap-2 text-muted-foreground">
                                        <span className="text-xs">YTD penalties</span>
                                    </div>
                                    <p className="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">
                                        {formatCurrency(financialImpact.ytd_total)}
                                    </p>
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        Proj. {formatCurrency(financialImpact.projected_annual)}/yr
                                    </p>
                                </div>
                            )}
                            {disputeOpportunity && disputeOpportunity.potential_savings > 0 && (
                                <Link href="/penalties/analytics" className="rounded-lg border bg-card p-4 transition-colors hover:bg-muted/50" data-pan="penalty-cost-savings">
                                    <div className="flex items-center gap-2 text-muted-foreground">
                                        <DollarSign className="size-4" />
                                        <span className="text-xs">Potential savings</span>
                                    </div>
                                    <p className="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                                        {formatCurrency(disputeOpportunity.potential_savings)}
                                    </p>
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        {disputeOpportunity.undisputed_count} undisputed
                                    </p>
                                </Link>
                            )}
                        </div>

                        {/* Operational overview: Rake state + Indent pipeline */}
                        {(rakeStateChart.length > 0 || indentPipeline.length > 0) && (
                            <div className="grid gap-4 lg:grid-cols-2">
                                {rakeStateChart.length > 0 && (
                                    <div className="rounded-lg border bg-card p-6">
                                        <h3 className="font-medium">Rake distribution by state</h3>
                                        <div className="mt-4">
                                            <PieChart
                                                data={rakeStateChart}
                                                nameKey="name"
                                                valueKey="value"
                                                height={240}
                                                formatTooltip={(v) => `${v} rakes`}
                                            />
                                        </div>
                                    </div>
                                )}
                                {indentPipeline.length > 0 && (
                                    <div className="rounded-lg border bg-card p-6">
                                        <h3 className="font-medium">Indent pipeline</h3>
                                        <div className="mt-4">
                                            <PieChart
                                                data={indentPipeline}
                                                nameKey="name"
                                                valueKey="value"
                                                height={240}
                                                formatTooltip={(v) => `${v} indents`}
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Penalty trend: amount + count + 3-month rolling avg */}
                        <div className="rounded-lg border bg-card p-6">
                            <div className="flex items-center justify-between">
                                <Link href="/penalties/analytics" className="font-medium hover:underline" data-pan="penalty-drill-down">
                                    Penalty trend (last 12 months)
                                </Link>
                                <span className="text-xs text-muted-foreground">
                                    Dashed line = 3-month rolling average
                                </span>
                            </div>
                            <div className="mt-4">
                                <AreaChart
                                    data={penaltyTrendWithAvg}
                                    xKey="month"
                                    yKey="total"
                                    secondaryYKey="rollingAvg"
                                    secondaryLabel="3-month avg"
                                    yLabel="Amount (₹)"
                                    height={280}
                                    formatY={formatCurrency}
                                    formatTooltip={(v) => `₹${v.toLocaleString()}`}
                                />
                            </div>
                        </div>

                        {/* Penalty by type + Penalty status */}
                        {(penaltyByType.length > 0 || penaltyStatusBreakdown.length > 0) && (
                            <div className="grid gap-4 lg:grid-cols-2">
                                {penaltyByType.length > 0 && (
                                    <div className="rounded-lg border bg-card p-6">
                                        <Link href="/penalties/analytics" className="font-medium hover:underline" data-pan="penalty-drill-down">
                                            Penalties by type
                                        </Link>
                                        <div className="mt-4">
                                            <PieChart
                                                data={penaltyByType}
                                                nameKey="name"
                                                valueKey="value"
                                                height={260}
                                                formatTooltip={(v) => `₹${v.toLocaleString()}`}
                                            />
                                        </div>
                                    </div>
                                )}
                                {penaltyStatusBreakdown.length > 0 && (
                                    <div className="rounded-lg border bg-card p-6">
                                        <h3 className="font-medium">Penalty resolution status</h3>
                                        <div className="mt-4">
                                            <PieChart
                                                data={penaltyStatusBreakdown}
                                                nameKey="name"
                                                valueKey="value"
                                                height={260}
                                                formatTooltip={(v) => `₹${v.toLocaleString()}`}
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Penalty by siding + Responsible party */}
                        {(penaltyBySiding.length > 0 || responsiblePartyBreakdown.length > 0) && (
                            <div className="grid gap-4 lg:grid-cols-2">
                                {penaltyBySiding.length > 0 && (
                                    <div className="rounded-lg border bg-card p-6">
                                        <Link href="/penalties/analytics" className="font-medium hover:underline" data-pan="penalty-drill-down">
                                            Penalties by siding
                                        </Link>
                                        <div className="mt-4">
                                            <BarChart
                                                data={penaltyBySiding}
                                                xKey="name"
                                                yKey="total"
                                                height={260}
                                                layout="vertical"
                                                formatY={formatCurrency}
                                                formatTooltip={(v) => `₹${v.toLocaleString()}`}
                                            />
                                        </div>
                                    </div>
                                )}
                                {responsiblePartyBreakdown.length > 0 && (
                                    <div className="rounded-lg border bg-card p-6">
                                        <h3 className="font-medium">Penalties by responsible party</h3>
                                        <div className="mt-4">
                                            <BarChart
                                                data={responsiblePartyBreakdown}
                                                xKey="name"
                                                yKey="value"
                                                height={260}
                                                formatY={formatCurrency}
                                                formatTooltip={(v) => `₹${v.toLocaleString()}`}
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Siding performance comparison */}
                        {sidingPerformance.length > 0 && (
                            <div className="rounded-lg border bg-card p-6">
                                <div className="flex items-center justify-between">
                                    <h3 className="font-medium">Siding performance (last 12 months)</h3>
                                    {financialImpact?.worst_siding && (
                                        <span className="rounded-md bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-950/50 dark:text-red-400">
                                            Worst: {financialImpact.worst_siding}
                                        </span>
                                    )}
                                </div>
                                <div className="mt-4 overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b text-left text-muted-foreground">
                                                <th className="pb-2 pr-4 font-medium">Siding</th>
                                                <th className="pb-2 pr-4 text-right font-medium">Rakes</th>
                                                <th className="pb-2 pr-4 text-right font-medium">Penalties</th>
                                                <th className="pb-2 pr-4 text-right font-medium">Amount</th>
                                                <th className="pb-2 text-right font-medium">Penalty rate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {sidingPerformance.map((s) => (
                                                <tr key={s.name} className="border-b border-border/50">
                                                    <td className="py-2.5 pr-4 font-medium">{s.name}</td>
                                                    <td className="py-2.5 pr-4 text-right tabular-nums">{s.rakes}</td>
                                                    <td className="py-2.5 pr-4 text-right tabular-nums">{s.penalties}</td>
                                                    <td className="py-2.5 pr-4 text-right tabular-nums text-red-600 dark:text-red-400">
                                                        {formatCurrency(s.penalty_amount)}
                                                    </td>
                                                    <td className="py-2.5 text-right">
                                                        <span
                                                            className={
                                                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium ' +
                                                                (s.penalty_rate > 50
                                                                    ? 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-400'
                                                                    : s.penalty_rate > 25
                                                                      ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400'
                                                                      : 'bg-green-100 text-green-700 dark:bg-green-950/50 dark:text-green-400')
                                                            }
                                                        >
                                                            {s.penalty_rate}%
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* Financial impact summary */}
                        {financialImpact && financialImpact.ytd_total > 0 && (
                            <div className="rounded-lg border bg-card p-6">
                                <h3 className="font-medium">Financial impact (YTD)</h3>
                                <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                    <div className="rounded-md border bg-muted/30 p-3">
                                        <p className="text-sm text-muted-foreground">Total penalties YTD</p>
                                        <p className="mt-1 text-xl font-semibold text-red-600 dark:text-red-400">
                                            {formatCurrency(financialImpact.ytd_total)}
                                        </p>
                                    </div>
                                    <div className="rounded-md border bg-muted/30 p-3">
                                        <p className="text-sm text-muted-foreground">Projected annual</p>
                                        <p className="mt-1 text-xl font-semibold">
                                            {formatCurrency(financialImpact.projected_annual)}
                                        </p>
                                    </div>
                                    <div className="rounded-md border bg-muted/30 p-3">
                                        <p className="text-sm text-muted-foreground">Cost per rake</p>
                                        <p className="mt-1 flex items-center gap-1.5 text-xl font-semibold">
                                            {formatCurrency(financialImpact.cost_per_rake)}
                                            <TrendIcon direction={financialImpact.trend_direction} />
                                        </p>
                                    </div>
                                    <div className="rounded-md border bg-muted/30 p-3">
                                        <p className="text-sm text-muted-foreground">Worst siding</p>
                                        <p className="mt-1 text-xl font-semibold">
                                            {financialImpact.worst_siding ?? '—'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Siding stock */}
                        {Object.keys(sidingStocks).length > 0 && (
                            <div className="rounded-lg border bg-card p-6">
                                <h3 className="font-medium">Siding stock (closing balance MT)</h3>
                                <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    {sidings.map((s) => {
                                        const stock = sidingStocks[s.id];
                                        const balance = stock ? stock.closing_balance_mt : 0;
                                        return (
                                            <div key={s.id} className="rounded-md border bg-muted/30 p-3">
                                                <div className="flex justify-between text-sm">
                                                    <span className="font-medium">{s.name}</span>
                                                    <span className="text-muted-foreground tabular-nums">
                                                        {balance.toLocaleString(undefined, { maximumFractionDigits: 2 })} MT
                                                    </span>
                                                </div>
                                                <div className="mt-2 h-2 w-full overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className="h-full rounded-full bg-primary"
                                                        style={{
                                                            width: `${Math.min(100, (balance / 1000) * 10)}%`,
                                                        }}
                                                    />
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {/* Live demurrage timers */}
                        {activeRakes.length > 0 && (
                            <div className="rounded-lg border bg-card p-6">
                                <h3 className="font-medium">Live demurrage timers</h3>
                                <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {activeRakes.map((rake) => (
                                        <DemurrageTimer key={rake.id} rake={rake} />
                                    ))}
                                </div>
                                <div className="mt-3">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={rakes().url}>View all rakes</Link>
                                    </Button>
                                </div>
                            </div>
                        )}
                    </>
                )}

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {quickActions.map((action) => (
                        <Button
                            key={action.label}
                            variant="outline"
                            className="h-auto flex-col items-center gap-2 py-6"
                            asChild
                            data-pan={action.dataPan}
                        >
                            {action.external ? (
                                <a
                                    href={
                                        typeof action.href === 'string'
                                            ? action.href
                                            : action.href.url
                                    }
                                >
                                    <action.icon className="size-5 text-muted-foreground" />
                                    <span className="text-sm">{action.label}</span>
                                </a>
                            ) : (
                                <Link href={action.href}>
                                    <action.icon className="size-5 text-muted-foreground" />
                                    <span className="text-sm">{action.label}</span>
                                </Link>
                            )}
                        </Button>
                    ))}
                </div>

                {canAccessAdmin && (
                    <div className="rounded-lg border bg-card p-6">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 className="font-medium">Product analytics</h3>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    App-wide impressions, hovers, and clicks for tracked UI elements.
                                    View full table and stats in the admin panel.
                                </p>
                            </div>
                            <Button variant="outline" size="sm" asChild>
                                <a href="/admin/analytics/product" data-pan="dashboard-card-view-analytics">
                                    View analytics
                                </a>
                            </Button>
                        </div>
                    </div>
                )}

                <RrmcsGuidance
                    title="How RRMCS replaces your Excel workflow"
                    before="Rake status and timers in Excel and stopwatch; indent requests on paper; penalties discovered only after RR (24+ hours late)."
                    after="One place for Rakes (with live demurrage timer), Indents, Stock, and Penalties. Real-time alerts at 60/30/0 min and overload detection before RR."
                    className="rounded-lg border-border"
                />

                <div className="rounded-lg border bg-card p-6 text-sm text-muted-foreground">
                    <p>
                        This is your dashboard. Use the sidebar to navigate to
                        different sections, or the quick actions above to get
                        started.
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
