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
    FileText,
    GitBranch,
    LifeBuoy,
    Mail,
    Settings,
    Truck,
    UserPen,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

type FleetSummary = { vehicle_count: number; driver_count: number } | null;
type ActivityItem = { id: number; description: string; created_at: string };

export default function Dashboard() {
    const {
        auth,
        features,
        fleetSummary,
        activity = [],
        aiSummary,
    } = usePage<
        SharedData & {
            fleetSummary?: FleetSummary;
            activity?: ActivityItem[];
            aiSummary?: string | null;
        }
    >().props;
    const f = features ?? {};
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
            description: 'Update your name, email, and avatar',
            href: editProfile().url,
            icon: UserPen,
            show: true,
            dataPan: 'dashboard-quick-edit-profile',
        },
        {
            label: 'Settings',
            description: 'Profile, password, branding, and more',
            href: '/settings',
            icon: Settings,
            show: true,
            dataPan: 'dashboard-quick-settings',
        },
        {
            label: 'Export profile (PDF)',
            description: 'Download your personal data as PDF',
            href: exportPdf().url,
            icon: FileText,
            show: showPdfExport,
            external: true,
            dataPan: 'dashboard-quick-export-pdf',
        },
        {
            label: 'Contact support',
            description: 'Get help from the team',
            href: contactCreate().url,
            icon: LifeBuoy,
            show: showContact,
            dataPan: 'dashboard-quick-contact',
        },
        {
            label: 'Email templates',
            description: 'Manage database mail templates',
            href: '/admin/mail-templates',
            icon: Mail,
            show: isSuperAdmin,
            external: true,
            dataPan: 'dashboard-quick-email-templates',
        },
        {
            label: 'Product analytics',
            description: 'Impressions, hovers, and clicks',
            href: '/admin/analytics/product',
            icon: BarChart3,
            show: canAccessAdmin,
            external: true,
            dataPan: 'dashboard-quick-product-analytics',
        },
        {
            label: 'Horizon (queues)',
            description: 'Monitor queue jobs and workers',
            href: '/horizon',
            icon: Activity,
            show: canAccessAdmin,
            external: true,
            dataPan: 'dashboard-quick-horizon',
        },
        {
            label: 'Waterline (workflows)',
            description: 'View and manage durable workflows',
            href: '/waterline',
            icon: GitBranch,
            show: canAccessAdmin,
            external: true,
            dataPan: 'dashboard-quick-waterline',
        },
        {
            label: 'Telescope (debug)',
            description: 'Requests, queries, and logs',
            href: '/telescope',
            icon: Bug,
            show: canAccessAdmin,
            external: true,
            dataPan: 'dashboard-quick-telescope',
        },
    ].filter((a) => a.show);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="heading-4 text-foreground">
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

                {fleetSummary != null && (
                    <div
                        className="rounded-lg border border-border bg-card p-4 shadow-sm"
                        data-pan="dashboard-fleet-summary"
                    >
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-4">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <Truck className="size-5" />
                                </div>
                                <div>
                                    <h3 className="font-medium text-foreground">
                                        Fleet summary
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {fleetSummary.vehicle_count} vehicle
                                        {fleetSummary.vehicle_count !== 1
                                            ? 's'
                                            : ''}
                                        {' · '}
                                        {fleetSummary.driver_count} driver
                                        {fleetSummary.driver_count !== 1
                                            ? 's'
                                            : ''}
                                    </p>
                                </div>
                            </div>
                            <Button
                                size="sm"
                                asChild
                                data-pan="dashboard-view-fleet"
                            >
                                <Link href="/fleet">View fleet</Link>
                            </Button>
                        </div>
                    </div>
                )}

                {aiSummary != null && aiSummary !== '' && (
                    <div
                        className="rounded-lg border border-border bg-card p-4 shadow-sm"
                        data-pan="dashboard-ai-summary"
                    >
                        <h3 className="heading-5 mb-2 text-foreground">
                            AI summary
                        </h3>
                        <p className="body-sm whitespace-pre-wrap text-muted-foreground">
                            {aiSummary}
                        </p>
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {quickActions.map((action) => (
                        <Link
                            key={action.label}
                            href={action.href}
                            className="focus-visible-ring flex flex-col gap-2 rounded-lg border border-border bg-card p-4 shadow-sm transition-colors hover:bg-muted/50"
                            data-pan={action.dataPan}
                            {...(action.external
                                ? {
                                      target: '_blank',
                                      rel: 'noopener noreferrer',
                                  }
                                : {})}
                        >
                            <div className="flex items-center gap-3">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <action.icon className="size-4" />
                                </div>
                                <span className="font-medium text-foreground">
                                    {action.label}
                                </span>
                            </div>
                            {action.description && (
                                <p className="text-sm text-muted-foreground">
                                    {action.description}
                                </p>
                            )}
                        </Link>
                    ))}
                </div>

                <div
                    className="rounded-lg border border-border bg-card p-4 shadow-sm"
                    data-pan="dashboard-activity"
                >
                    <h3 className="heading-5 mb-2 text-foreground">Activity</h3>
                    {activity.length > 0 ? (
                        <ul className="space-y-2" aria-label="Recent activity">
                            {activity.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex items-center justify-between gap-2 border-b border-border/50 py-2 last:border-0 last:pb-0"
                                >
                                    <span className="text-sm text-foreground">
                                        {item.description}
                                    </span>
                                    <time
                                        className="shrink-0 text-xs text-muted-foreground"
                                        dateTime={item.created_at}
                                    >
                                        {new Date(
                                            item.created_at,
                                        ).toLocaleDateString(undefined, {
                                            month: 'short',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        })}
                                    </time>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="flex flex-col items-center justify-center gap-3 py-8 text-center">
                            <p className="body-sm text-muted-foreground">
                                No recent activity yet.
                            </p>
                            <Button
                                variant="outline"
                                size="sm"
                                asChild
                                data-pan="dashboard-connect-data"
                            >
                                <Link href="/settings">
                                    Connect data or update settings
                                </Link>
                            </Button>
                        </div>
                    )}
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
