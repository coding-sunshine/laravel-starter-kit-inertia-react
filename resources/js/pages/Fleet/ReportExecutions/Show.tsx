import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Download } from 'lucide-react';

interface ReportExecution {
    id: number;
    execution_start: string;
    execution_end?: string;
    status: string;
    triggered_by: string;
    report?: { id: number; name: string };
}
interface Props {
    reportExecution: ReportExecution;
    downloadUrl?: string | null;
}

const statusVariant = (status: string) =>
    status === 'completed'
        ? 'default'
        : status === 'failed'
          ? 'destructive'
          : 'secondary';

export default function FleetReportExecutionsShow({
    reportExecution,
    downloadUrl,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Report executions', href: '/fleet/report-executions' },
        {
            title: `Execution #${reportExecution.id}`,
            href: `/fleet/report-executions/${reportExecution.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Report execution" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h1 className="text-2xl font-semibold">Report execution</h1>
                    <div className="flex flex-wrap items-center gap-2">
                        {downloadUrl && (
                            <Button size="sm" asChild>
                                <a href={downloadUrl} download>
                                    <Download className="mr-2 size-4" />
                                    Download
                                </a>
                            </Button>
                        )}
                        {reportExecution.report?.id && (
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={`/fleet/reports/${reportExecution.report.id}`}
                                >
                                    Back to report
                                </Link>
                            </Button>
                        )}
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/report-executions">
                                Back to list
                            </Link>
                        </Button>
                    </div>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Report:</span>{' '}
                            {reportExecution.report ? (
                                <Link
                                    href={`/fleet/reports/${reportExecution.report.id}`}
                                    className="text-primary hover:underline"
                                >
                                    {reportExecution.report.name}
                                </Link>
                            ) : (
                                '—'
                            )}
                        </p>
                        <p>
                            <span className="font-medium">Started:</span>{' '}
                            {new Date(
                                reportExecution.execution_start,
                            ).toLocaleString()}
                        </p>
                        {reportExecution.execution_end && (
                            <p>
                                <span className="font-medium">Ended:</span>{' '}
                                {new Date(
                                    reportExecution.execution_end,
                                ).toLocaleString()}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">Status:</span>{' '}
                            <Badge
                                variant={statusVariant(reportExecution.status)}
                            >
                                {reportExecution.status}
                            </Badge>
                        </p>
                        <p>
                            <span className="font-medium">Triggered by:</span>{' '}
                            {reportExecution.triggered_by}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
