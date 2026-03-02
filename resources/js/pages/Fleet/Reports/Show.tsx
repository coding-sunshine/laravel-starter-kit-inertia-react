import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface ReportExecutionRow { id: number; execution_start: string; status: string; }
interface Report {
    id: number;
    name: string;
    description?: string;
    report_type: string;
    format: string;
    is_active: boolean;
    report_executions?: ReportExecutionRow[];
}
interface Props { report: Report; }

export default function FleetReportsShow({ report }: Props) {
    const { post, processing } = useForm();
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Reports', href: '/fleet/reports' },
        { title: report.name, href: `/fleet/reports/${report.id}` },
    ];
    const recentExecutions = (report.report_executions ?? []).slice(0, 10);

    const runReport = () => {
        post(`/fleet/reports/${report.id}/run`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${report.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h1 className="text-2xl font-semibold">{report.name}</h1>
                    <div className="flex flex-wrap gap-2">
                        <Button size="sm" onClick={runReport} disabled={processing}>
                            {processing ? 'Running…' : 'Run report'}
                        </Button>
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/reports/${report.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/reports">Back to list</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Type:</span> {report.report_type}</p>
                        <p><span className="font-medium">Format:</span> {report.format}</p>
                        <p><span className="font-medium">Active:</span> {report.is_active ? 'Yes' : 'No'}</p>
                        {report.description && <p className="mt-2"><span className="font-medium">Description:</span> {report.description}</p>}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-base">Recent executions</CardTitle>
                        <Button variant="link" size="sm" className="h-auto p-0 text-primary" asChild>
                            <Link href={`/fleet/report-executions?report_id=${report.id}`}>View all</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {recentExecutions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No executions yet. Run the report to generate one.</p>
                        ) : (
                            <ul className="space-y-2 text-sm">
                                {recentExecutions.map((ex) => (
                                    <li key={ex.id} className="flex items-center justify-between border-b border-dashed pb-2 last:border-0">
                                        <Link href={`/fleet/report-executions/${ex.id}`} className="font-medium hover:underline">
                                            Execution #{ex.id}
                                        </Link>
                                        <span className="text-muted-foreground">
                                            {new Date(ex.execution_start).toLocaleString()} · {ex.status}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
