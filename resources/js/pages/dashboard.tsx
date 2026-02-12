import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { create as contactCreate } from '@/routes/contact';
import { exportPdf } from '@/routes/profile';
import { edit as editProfile } from '@/routes/user-profile';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { FileText, LifeBuoy, Settings, UserPen } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    const { auth, features } = usePage<SharedData>().props;
    const f = features ?? {};
    const showPdfExport = f.profile_pdf_export ?? false;
    const showApiDocs = f.scramble_api_docs ?? false;
    const showContact = f.contact ?? false;

    const quickActions = [
        { label: 'Edit profile', href: editProfile(), icon: UserPen, show: true },
        { label: 'Settings', href: '/settings', icon: Settings, show: true },
        { label: 'Export profile (PDF)', href: exportPdf().url, icon: FileText, show: showPdfExport, external: true },
        { label: 'Contact support', href: contactCreate().url, icon: LifeBuoy, show: showContact },
    ].filter((a) => a.show);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
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

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {quickActions.map((action) => (
                        <Button
                            key={action.label}
                            variant="outline"
                            className="h-auto flex-col items-center gap-2 py-6"
                            asChild
                        >
                            {action.external ? (
                                <a href={typeof action.href === 'string' ? action.href : action.href.url}>
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
