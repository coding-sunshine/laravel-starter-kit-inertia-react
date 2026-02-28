import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface AiJobRunRecord {
    id: number;
    job_type: string;
    entity_type?: string | null;
    status: string;
    priority?: string | number | null;
    scheduled_at?: string | null;
    started_at?: string | null;
    completed_at?: string | null;
    progress_percentage?: number | null;
    error_message?: string | null;
    error_code?: string | null;
    created_at: string;
    result_data?: unknown;
}
interface Props {
    aiJobRun: AiJobRunRecord;
    statuses?: { value: string; name: string }[];
}

export default function FleetAiJobRunsShow({ aiJobRun }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'AI job runs', href: '/fleet/ai-job-runs' },
        { title: `Run #${aiJobRun.id}`, href: `/fleet/ai-job-runs/${aiJobRun.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – AI job run #${aiJobRun.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">AI job run #{aiJobRun.id}</h1>
                    <Button variant="ghost" size="sm" asChild><Link href="/fleet/ai-job-runs">Back to list</Link></Button>
                </div>
                <Card>
                    <CardHeader><CardTitle className="text-base">Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">ID:</span> {aiJobRun.id}</p>
                        <p><span className="font-medium">Job type:</span> {aiJobRun.job_type}</p>
                        {aiJobRun.entity_type && <p><span className="font-medium">Entity type:</span> {aiJobRun.entity_type}</p>}
                        <p><span className="font-medium">Status:</span> {aiJobRun.status}</p>
                        {aiJobRun.priority != null && <p><span className="font-medium">Priority:</span> {String(aiJobRun.priority)}</p>}
                        <p><span className="font-medium">Created:</span> {new Date(aiJobRun.created_at).toLocaleString()}</p>
                        {aiJobRun.scheduled_at && <p><span className="font-medium">Scheduled at:</span> {new Date(aiJobRun.scheduled_at).toLocaleString()}</p>}
                        {aiJobRun.started_at && <p><span className="font-medium">Started at:</span> {new Date(aiJobRun.started_at).toLocaleString()}</p>}
                        {aiJobRun.completed_at && <p><span className="font-medium">Completed at:</span> {new Date(aiJobRun.completed_at).toLocaleString()}</p>}
                        {aiJobRun.progress_percentage != null && <p><span className="font-medium">Progress:</span> {aiJobRun.progress_percentage}%</p>}
                        {aiJobRun.error_message && <p><span className="font-medium text-destructive">Error:</span> {aiJobRun.error_message}</p>}
                        {aiJobRun.error_code && <p><span className="font-medium">Error code:</span> {aiJobRun.error_code}</p>}
                    </CardContent>
                </Card>
                {aiJobRun.result_data != null && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">Result data</CardTitle></CardHeader>
                        <CardContent>
                            <pre className="overflow-auto rounded bg-muted p-2 text-xs">{JSON.stringify(aiJobRun.result_data, null, 2)}</pre>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
