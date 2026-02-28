import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface ReportExecution { id: number; execution_start: string; execution_end?: string; status: string; triggered_by: string; report?: { id: number; name: string }; }
interface Props { reportExecution: ReportExecution; }

export default function FleetReportExecutionsShow({ reportExecution }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Report executions', href: '/fleet/report-executions' },
        { title: 'View', href: `/fleet/report-executions/${reportExecution.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Report execution" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Report execution</h1>
                    <Button variant="ghost" size="sm" asChild><Link href="/fleet/report-executions">Back to list</Link></Button>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Report:</span> {reportExecution.report?.name ?? '—'}</p>
                        <p><span className="font-medium">Started:</span> {new Date(reportExecution.execution_start).toLocaleString()}</p>
                        {reportExecution.execution_end && <p><span className="font-medium">Ended:</span> {new Date(reportExecution.execution_end).toLocaleString()}</p>}
                        <p><span className="font-medium">Status:</span> {reportExecution.status}</p>
                        <p><span className="font-medium">Triggered by:</span> {reportExecution.triggered_by}</p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
