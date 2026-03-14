import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Report {
    id: string;
    title: string;
    description: string;
    icon: string;
    href: string;
}

interface Props {
    reports: Report[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
];

export default function ReportsIndexPage({ reports }: Props) {
    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Reports" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4" data-pan="reports-index">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Reports</h1>
                    <p className="text-muted-foreground">
                        Select a report to view detailed analytics and export data.
                    </p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {reports.map((report) => (
                        <Link
                            key={report.id}
                            href={report.href}
                            className="group rounded-lg border bg-card p-6 shadow-sm transition-shadow hover:shadow-md"
                        >
                            <div className="mb-3 flex items-center gap-3">
                                <div className="rounded-md bg-primary/10 p-2 text-primary">
                                    <span className="text-sm font-medium">{report.icon}</span>
                                </div>
                                <h2 className="font-semibold group-hover:text-primary">
                                    {report.title}
                                </h2>
                            </div>
                            <p className="text-sm text-muted-foreground">{report.description}</p>
                        </Link>
                    ))}
                </div>
            </div>
        </AppSidebarLayout>
    );
}
