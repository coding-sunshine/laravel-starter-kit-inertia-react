import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface ApiLog {
    id: number;
    request_method: string;
    request_url: string;
    response_status_code?: number;
    response_time_ms?: number;
    error_message?: string;
    created_at: string;
    api_integration?: { id: number; integration_name: string };
}
interface Props {
    apiLog: ApiLog;
}

export default function FleetApiLogsShow({ apiLog }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'API logs', href: '/fleet/api-logs' },
        { title: 'View', href: `/fleet/api-logs/${apiLog.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – API log" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        API log #{apiLog.id}
                    </h1>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet/api-logs">Back to list</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Method:</span>{' '}
                            {apiLog.request_method}
                        </p>
                        <p>
                            <span className="font-medium">URL:</span>{' '}
                            <span className="break-all">
                                {apiLog.request_url}
                            </span>
                        </p>
                        <p>
                            <span className="font-medium">Status:</span>{' '}
                            {apiLog.response_status_code ?? '—'}
                        </p>
                        {apiLog.response_time_ms != null && (
                            <p>
                                <span className="font-medium">
                                    Response time:
                                </span>{' '}
                                {apiLog.response_time_ms} ms
                            </p>
                        )}
                        {apiLog.error_message && (
                            <p>
                                <span className="font-medium">Error:</span>{' '}
                                {apiLog.error_message}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">Created:</span>{' '}
                            {new Date(apiLog.created_at).toLocaleString()}
                        </p>
                        {apiLog.api_integration && (
                            <p>
                                <span className="font-medium">
                                    Integration:
                                </span>{' '}
                                {apiLog.api_integration.integration_name}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
