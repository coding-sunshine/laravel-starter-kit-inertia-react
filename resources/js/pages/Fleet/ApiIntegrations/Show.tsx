import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface ApiIntegration {
    id: number;
    integration_name: string;
    integration_type: string;
    provider_name: string;
    api_endpoint?: string;
    sync_status: string;
    is_active: boolean;
    last_sync_timestamp?: string;
}
interface Props { apiIntegration: ApiIntegration; }

export default function FleetApiIntegrationsShow({ apiIntegration }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'API integrations', href: '/fleet/api-integrations' },
        { title: 'View', href: `/fleet/api-integrations/${apiIntegration.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – API integration" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{apiIntegration.integration_name}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/api-integrations/${apiIntegration.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/api-integrations">Back to list</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Type:</span> {apiIntegration.integration_type}</p>
                        <p><span className="font-medium">Provider:</span> {apiIntegration.provider_name}</p>
                        <p><span className="font-medium">Status:</span> {apiIntegration.sync_status}</p>
                        <p><span className="font-medium">Active:</span> {apiIntegration.is_active ? 'Yes' : 'No'}</p>
                        {apiIntegration.api_endpoint && <p><span className="font-medium">Endpoint:</span> {apiIntegration.api_endpoint}</p>}
                        {apiIntegration.last_sync_timestamp && <p><span className="font-medium">Last sync:</span> {new Date(apiIntegration.last_sync_timestamp).toLocaleString()}</p>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
