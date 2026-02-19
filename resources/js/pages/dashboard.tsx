import { RrmcsGuidance } from '@/components/rrmcs-guidance';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { create as contactCreate } from '@/routes/contact';
import { index as rakes } from '@/routes/rakes';
import { exportPdf } from '@/routes/profile';
import { edit as editProfile } from '@/routes/user-profile';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    FileText,
    LifeBuoy,
    Mail,
    Settings,
    Train,
    Truck,
    UserPen,
} from 'lucide-react';
import { useEffect, useState } from 'react';

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
    sidings?: SidingOption[];
};

function formatRemainingMinutes(m: number): string {
    if (m <= 0) return '0m';
    const h = Math.floor(m / 60);
    const min = m % 60;
    if (h > 0) return `${h}h ${min}m`;
    return `${min}m`;
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
                        'tabular-nums font-semibold ' +
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

export default function Dashboard() {
    const props = usePage<DashboardProps>().props;
    const { auth, features } = props;
    const summary = props.summary ?? null;
    const sidingStocks = props.sidingStocks ?? {};
    const activeRakes = props.activeRakes ?? [];
    const alerts = props.alerts ?? [];
    const penaltyChartData = props.penaltyChartData ?? [];
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
    const maxPenalty = Math.max(
        ...penaltyChartData.map((d) => d.total),
        1,
    );

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
                                            { preserveScroll: true }
                                        )
                                    }
                                >
                                    Resolve
                                </Button>
                            </div>
                        ))}
                    </div>
                )}

                {hasRrmcs && summary && (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                            <div className="rounded-lg border bg-card p-4">
                                <div className="flex items-center gap-2 text-muted-foreground">
                                    <Train className="size-4" />
                                    <span className="text-sm">Active rakes</span>
                                </div>
                                <p className="mt-1 text-2xl font-semibold">
                                    {summary.totalRakes}
                                </p>
                            </div>
                            <div className="rounded-lg border bg-card p-4">
                                <div className="flex items-center gap-2 text-muted-foreground">
                                    <span className="text-sm">
                                        Pending indents
                                    </span>
                                </div>
                                <p className="mt-1 text-2xl font-semibold">
                                    {summary.indentsPending}
                                </p>
                            </div>
                            <div className="rounded-lg border bg-card p-4">
                                <div className="flex items-center gap-2 text-muted-foreground">
                                    <span className="text-sm">
                                        Penalties (this month)
                                    </span>
                                </div>
                                <p className="mt-1 text-2xl font-semibold">
                                    {summary.penaltiesThisMonth.toLocaleString(
                                        undefined,
                                        {
                                            maximumFractionDigits: 0,
                                        },
                                    )}
                                </p>
                            </div>
                            <div className="rounded-lg border bg-card p-4">
                                <div className="flex items-center gap-2 text-muted-foreground">
                                    <Truck className="size-4" />
                                    <span className="text-sm">
                                        Vehicles received today
                                    </span>
                                </div>
                                <p className="mt-1 text-2xl font-semibold">
                                    {summary.vehiclesReceivedToday}
                                </p>
                            </div>
                        </div>

                        {Object.keys(sidingStocks).length > 0 && (
                            <div className="rounded-lg border bg-card p-6">
                                <h3 className="font-medium">
                                    Siding stock (closing balance MT)
                                </h3>
                                <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    {sidings.map((s) => {
                                        const stock = sidingStocks[s.id];
                                        const balance = stock
                                            ? stock.closing_balance_mt
                                            : 0;
                                        return (
                                            <div
                                                key={s.id}
                                                className="rounded-md border bg-muted/30 p-3"
                                            >
                                                <div className="flex justify-between text-sm">
                                                    <span className="font-medium">
                                                        {s.name}
                                                    </span>
                                                    <span className="tabular-nums text-muted-foreground">
                                                        {balance.toLocaleString(
                                                            undefined,
                                                            {
                                                                maximumFractionDigits: 2,
                                                            },
                                                        )}{' '}
                                                        MT
                                                    </span>
                                                </div>
                                                <div className="mt-2 h-2 w-full overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className="h-full rounded-full bg-primary"
                                                        style={{
                                                            width: `${Math.min(
                                                                100,
                                                                (balance / 1000) * 10,
                                                            )}%`,
                                                        }}
                                                    />
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {activeRakes.length > 0 && (
                            <div className="rounded-lg border bg-card p-6">
                                <h3 className="font-medium">
                                    Live demurrage timers
                                </h3>
                                <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {activeRakes.map((rake) => (
                                        <DemurrageTimer
                                            key={rake.id}
                                            rake={rake}
                                        />
                                    ))}
                                </div>
                                <div className="mt-3">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={rakes().url}>View all rakes</Link>
                                    </Button>
                                </div>
                            </div>
                        )}

                        {penaltyChartData.length > 0 && (
                            <div className="rounded-lg border bg-card p-6">
                                <h3 className="font-medium">
                                    Penalties (last 12 months)
                                </h3>
                                <div className="mt-4 space-y-2">
                                    {penaltyChartData.map((d) => (
                                        <div
                                            key={d.month}
                                            className="flex items-center gap-3"
                                        >
                                            <span className="w-24 text-sm text-muted-foreground">
                                                {d.month}
                                            </span>
                                            <div className="h-6 flex-1 overflow-hidden rounded bg-muted">
                                                <div
                                                    className="h-full rounded bg-primary"
                                                    style={{
                                                        width: `${(d.total / maxPenalty) * 100}%`,
                                                    }}
                                                />
                                            </div>
                                            <span className="w-20 text-right text-sm tabular-nums">
                                                {d.total.toLocaleString(
                                                    undefined,
                                                    {
                                                        maximumFractionDigits: 0,
                                                    },
                                                )}
                                            </span>
                                        </div>
                                    ))}
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
                                    <span className="text-sm">
                                        {action.label}
                                    </span>
                                </a>
                            ) : (
                                <Link href={action.href}>
                                    <action.icon className="size-5 text-muted-foreground" />
                                    <span className="text-sm">
                                        {action.label}
                                    </span>
                                </Link>
                            )}
                        </Button>
                    ))}
                </div>

                {canAccessAdmin && (
                    <div className="rounded-lg border bg-card p-6">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 className="font-medium">
                                    Product analytics
                                </h3>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    App-wide impressions, hovers, and clicks for
                                    tracked UI elements. View full table and
                                    stats in the admin panel.
                                </p>
                            </div>
                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href="/admin/analytics/product"
                                    data-pan="dashboard-card-view-analytics"
                                >
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
