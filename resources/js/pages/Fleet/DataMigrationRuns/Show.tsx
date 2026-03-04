import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface DataMigrationRun {
    id: number;
    batch_id?: string;
    migration_type: string;
    status: string;
    total_records: number;
    processed_records: number;
    failed_records: number;
    started_at: string;
    completed_at?: string;
    error_summary?: string;
    error_log?: unknown;
    organization?: { id: number; name: string } | null;
    triggered_by?: { id: number; name: string } | null;
}
interface Props {
    dataMigrationRun: DataMigrationRun;
}

export default function FleetDataMigrationRunsShow({
    dataMigrationRun,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Data migration runs', href: '/fleet/data-migration-runs' },
        {
            title: 'View',
            href: `/fleet/data-migration-runs/${dataMigrationRun.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Data migration run" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Data migration run #{dataMigrationRun.id}
                    </h1>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet/data-migration-runs">
                            Back to list
                        </Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Batch ID:</span>{' '}
                            {dataMigrationRun.batch_id ?? '—'}
                        </p>
                        <p>
                            <span className="font-medium">Migration type:</span>{' '}
                            {dataMigrationRun.migration_type}
                        </p>
                        <p>
                            <span className="font-medium">Status:</span>{' '}
                            {dataMigrationRun.status}
                        </p>
                        <p>
                            <span className="font-medium">Total records:</span>{' '}
                            {dataMigrationRun.total_records}
                        </p>
                        <p>
                            <span className="font-medium">Processed:</span>{' '}
                            {dataMigrationRun.processed_records}
                        </p>
                        <p>
                            <span className="font-medium">Failed:</span>{' '}
                            {dataMigrationRun.failed_records}
                        </p>
                        <p>
                            <span className="font-medium">Started at:</span>{' '}
                            {new Date(
                                dataMigrationRun.started_at,
                            ).toLocaleString()}
                        </p>
                        {dataMigrationRun.completed_at && (
                            <p>
                                <span className="font-medium">
                                    Completed at:
                                </span>{' '}
                                {new Date(
                                    dataMigrationRun.completed_at,
                                ).toLocaleString()}
                            </p>
                        )}
                        {dataMigrationRun.organization && (
                            <p>
                                <span className="font-medium">
                                    Organization:
                                </span>{' '}
                                {dataMigrationRun.organization.name}
                            </p>
                        )}
                        {dataMigrationRun.triggered_by && (
                            <p>
                                <span className="font-medium">
                                    Triggered by:
                                </span>{' '}
                                {dataMigrationRun.triggered_by.name}
                            </p>
                        )}
                        {dataMigrationRun.error_summary && (
                            <p>
                                <span className="font-medium">
                                    Error summary:
                                </span>{' '}
                                {dataMigrationRun.error_summary}
                            </p>
                        )}
                        {dataMigrationRun.error_log != null && (
                            <p>
                                <span className="font-medium">Error log:</span>{' '}
                                <pre className="mt-1 max-h-48 overflow-auto rounded bg-muted p-2 text-xs">
                                    {JSON.stringify(
                                        dataMigrationRun.error_log,
                                        null,
                                        2,
                                    )}
                                </pre>
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
