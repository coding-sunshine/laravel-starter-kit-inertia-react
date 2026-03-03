import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { create as contactCreate } from '@/routes/contact';
import { exportPdf } from '@/routes/profile';
import { edit as editProfile } from '@/routes/user-profile';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    BarChart3,
    Bug,
    CalendarDays,
    FileText,
    GitBranch,
    LifeBuoy,
    Mail,
    Settings,
    Users,
    UserPen,
} from 'lucide-react';
import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    XAxis,
    YAxis,
} from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardKpis {
    contacts_total: number;
    contacts_by_stage: Record<string, number>;
    tasks_open: number;
    tasks_overdue: number;
    reservations_this_month: number;
    sales_this_month: number;
    sales_pipeline_value: number;
}

export default function Dashboard() {
    const { auth, features, kpis, insight, dashboard_role: dashboardRole } = usePage<SharedData>().props as SharedData & {
        kpis?: DashboardKpis;
        insight?: string | null;
        dashboard_role?: string | null;
    };
    const f = features ?? {};
    const kpi = kpis ?? {
        contacts_total: 0,
        contacts_by_stage: {},
        tasks_open: 0,
        tasks_overdue: 0,
        reservations_this_month: 0,
        sales_this_month: 0,
        sales_pipeline_value: 0,
    };
    const hasKpis = kpi.contacts_total > 0 || kpi.tasks_open > 0 || kpi.reservations_this_month > 0 || kpi.sales_this_month > 0;
    const showPdfExport = f.profile_pdf_export ?? false;
    const showApiDocs = f.scramble_api_docs ?? false;
    const showContact = f.contact ?? false;
    const isSuperAdmin = auth.roles?.includes('super-admin') ?? false;
    const canAccessAdmin =
        (auth.permissions?.includes('access admin panel') ?? false) ||
        auth.can_bypass === true;

    const quickActions = [
        {
            label: 'Edit profile',
            href: editProfile().url,
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
        {
            label: 'Horizon (queues)',
            href: '/horizon',
            icon: Activity,
            show: canAccessAdmin,
            external: true,
            dataPan: 'dashboard-quick-horizon',
        },
        {
            label: 'Waterline (workflows)',
            href: '/waterline',
            icon: GitBranch,
            show: canAccessAdmin,
            external: true,
            dataPan: 'dashboard-quick-waterline',
        },
        {
            label: 'Telescope (debug)',
            href: '/telescope',
            icon: Bug,
            show: canAccessAdmin,
            external: true,
            dataPan: 'dashboard-quick-telescope',
        },
    ].filter((a) => a.show);

    const chartData = [
        { name: 'Mon', value: 12 },
        { name: 'Tue', value: 19 },
        { name: 'Wed', value: 15 },
        { name: 'Thu', value: 22 },
        { name: 'Fri', value: 18 },
        { name: 'Sat', value: 25 },
        { name: 'Sun', value: 20 },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 className="text-lg font-medium">
                            Welcome back, {auth.user.name}
                        </h2>
                        {dashboardRole && dashboardRole !== 'member' && (
                            <p className="text-xs text-muted-foreground mt-0.5">
                                Viewing as: {dashboardRole === 'admin' ? 'Admin' : dashboardRole === 'bdm' ? 'BDM' : dashboardRole === 'sales_agent' ? 'Sales agent' : 'Member'}
                            </p>
                        )}
                    </div>
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

                {insight && (
                    <div
                        className="rounded-lg border border-primary/20 bg-primary/5 p-3 text-sm"
                        data-pan="dashboard-insight"
                    >
                        <p className="font-medium text-foreground">
                            {insight}
                        </p>
                    </div>
                )}

                {hasKpis && (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" data-pan="dashboard-kpis">
                        <div className="rounded-lg border bg-card p-4">
                            <div className="flex items-center gap-2 text-muted-foreground">
                                <Users className="size-4" />
                                <span className="text-sm">Contacts</span>
                            </div>
                            <p className="mt-1 text-2xl font-semibold">{kpi.contacts_total}</p>
                            {Object.keys(kpi.contacts_by_stage).length > 0 && (
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    by stage: {Object.entries(kpi.contacts_by_stage).slice(0, 3).map(([s, n]) => `${s}: ${n}`).join(', ')}
                                </p>
                            )}
                        </div>
                        <div className="rounded-lg border bg-card p-4">
                            <div className="flex items-center gap-2 text-muted-foreground">
                                <FileText className="size-4" />
                                <span className="text-sm">Open tasks</span>
                            </div>
                            <p className="mt-1 text-2xl font-semibold">{kpi.tasks_open}</p>
                            {kpi.tasks_overdue > 0 && (
                                <p className="mt-0.5 text-xs text-amber-600 dark:text-amber-400">
                                    {kpi.tasks_overdue} overdue
                                </p>
                            )}
                        </div>
                        <div className="rounded-lg border bg-card p-4">
                            <div className="flex items-center gap-2 text-muted-foreground">
                                <CalendarDays className="size-4" />
                                <span className="text-sm">Reservations (this month)</span>
                            </div>
                            <p className="mt-1 text-2xl font-semibold">{kpi.reservations_this_month}</p>
                        </div>
                        <div className="rounded-lg border bg-card p-4">
                            <div className="flex items-center gap-2 text-muted-foreground">
                                <BarChart3 className="size-4" />
                                <span className="text-sm">Sales (this month)</span>
                            </div>
                            <p className="mt-1 text-2xl font-semibold">{kpi.sales_this_month}</p>
                            {kpi.sales_pipeline_value > 0 && (
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    Pipeline: {new Intl.NumberFormat(undefined, { style: 'currency', currency: 'AUD', maximumFractionDigits: 0 }).format(kpi.sales_pipeline_value)}
                                </p>
                            )}
                        </div>
                    </div>
                )}

                {hasKpis && canAccessAdmin && (
                    <div className="rounded-lg border bg-card p-4" data-pan="dashboard-reports">
                        <h3 className="mb-2 font-medium">Reports</h3>
                        <p className="mb-3 text-sm text-muted-foreground">
                            View and export reservations, sales, and more with filters.
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" size="sm" asChild>
                                <a href="/admin/property-reservations">Reservations report</a>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <a href="/admin/sales">Sales / Commission report</a>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <a href="/admin/tasks">Task report</a>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <a href="/admin/contacts">Contacts / Leads report</a>
                            </Button>
                        </div>
                    </div>
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
                                <a href={action.href}>
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

                <div
                    className="rounded-lg border bg-card p-4"
                    data-pan="dashboard-chart"
                >
                    <h3 className="mb-2 font-medium">Activity (sample)</h3>
                    <div className="h-[200px] w-full">
                        <ResponsiveContainer
                            width="100%"
                            height={200}
                            minHeight={200}
                        >
                            <AreaChart data={chartData}>
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    className="stroke-muted"
                                />
                                <XAxis dataKey="name" className="text-xs" />
                                <YAxis className="text-xs" />
                                <Area
                                    type="monotone"
                                    dataKey="value"
                                    stroke="hsl(var(--primary))"
                                    fill="hsl(var(--primary))"
                                    fillOpacity={0.3}
                                />
                            </AreaChart>
                        </ResponsiveContainer>
                    </div>
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
