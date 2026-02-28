import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface ApiIntegration { id: number; integration_name: string; integration_type: string; provider_name: string; api_endpoint?: string; authentication_type: string; data_sync_frequency?: string; sync_status: string; is_active: boolean; }
interface Props {
    apiIntegration: ApiIntegration;
    integrationTypes: { value: string; name: string }[];
    syncStatuses: { value: string; name: string }[];
}

export default function FleetApiIntegrationsEdit({ apiIntegration, integrationTypes, syncStatuses }: Props) {
    const form = useForm({
        integration_name: apiIntegration.integration_name,
        integration_type: apiIntegration.integration_type,
        provider_name: apiIntegration.provider_name,
        api_endpoint: apiIntegration.api_endpoint ?? '',
        authentication_type: apiIntegration.authentication_type,
        data_sync_frequency: apiIntegration.data_sync_frequency ?? 'daily',
        sync_status: apiIntegration.sync_status,
        is_active: apiIntegration.is_active,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'API integrations', href: '/fleet/api-integrations' },
        { title: 'Edit', href: `/fleet/api-integrations/${apiIntegration.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit API integration" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href={`/fleet/api-integrations/${apiIntegration.id}`}>Back</Link></Button>
                    <h1 className="text-2xl font-semibold">Edit API integration</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.put(`/fleet/api-integrations/${apiIntegration.id}`); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Integration name</Label>
                        <Input value={form.data.integration_name} onChange={e => form.setData('integration_name', e.target.value)} required />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Type</Label>
                            <select value={form.data.integration_type} onChange={e => form.setData('integration_type', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                {integrationTypes.map((t) => <option key={t.value} value={t.value}>{t.name}</option>)}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Provider name</Label>
                            <Input value={form.data.provider_name} onChange={e => form.setData('provider_name', e.target.value)} required />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>API endpoint</Label>
                        <Input value={form.data.api_endpoint} onChange={e => form.setData('api_endpoint', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label>Authentication type</Label>
                        <Input value={form.data.authentication_type} onChange={e => form.setData('authentication_type', e.target.value)} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Sync frequency</Label>
                            <Input value={form.data.data_sync_frequency} onChange={e => form.setData('data_sync_frequency', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Sync status</Label>
                            <select value={form.data.sync_status} onChange={e => form.setData('sync_status', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                {syncStatuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                            </select>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <input type="checkbox" id="is_active" checked={form.data.is_active} onChange={e => form.setData('is_active', e.target.checked)} />
                        <Label htmlFor="is_active">Active</Label>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Update</Button>
                        <Button type="button" variant="outline" asChild><Link href={`/fleet/api-integrations/${apiIntegration.id}`}>Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
