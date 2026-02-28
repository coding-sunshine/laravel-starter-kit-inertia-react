import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface Row {
    id: number;
    request_method: string;
    request_url: string;
    response_status_code?: number;
    created_at: string;
    api_integration?: { id: number; integration_name: string };
}
interface Props {
    apiLogs: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    apiIntegrations: { id: number; integration_name: string }[];
}

export default function FleetApiLogsIndex({ apiLogs, filters, apiIntegrations }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'API logs', href: '/fleet/api-logs' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – API logs" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">API logs</h1>
                <form method="get" className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Integration</Label>
                        <select name="integration_id" defaultValue={filters.integration_id ?? ''} className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {apiIntegrations.map((a) => <option key={a.id} value={a.id}>{a.integration_name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>From date</Label>
                        <input type="date" name="date_from" defaultValue={filters.date_from ?? ''} className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm" />
                    </div>
                    <div className="space-y-1">
                        <Label>To date</Label>
                        <input type="date" name="date_to" defaultValue={filters.date_to ?? ''} className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm" />
                    </div>
                    <Button type="submit" variant="secondary" size="sm">Filter</Button>
                </form>
                {apiLogs.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <FileText className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No API logs yet.</p>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Method</th>
                                        <th className="p-3 text-left font-medium">URL</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-left font-medium">Integration</th>
                                        <th className="p-3 text-left font-medium">Created</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {apiLogs.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.request_method}</td>
                                            <td className="max-w-xs truncate p-3" title={row.request_url}>{row.request_url}</td>
                                            <td className="p-3">{row.response_status_code ?? '—'}</td>
                                            <td className="p-3">{row.api_integration?.integration_name ?? '—'}</td>
                                            <td className="p-3">{new Date(row.created_at).toLocaleString()}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/api-logs/${row.id}`}>View</Link></Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {apiLogs.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {apiLogs.links.map((link, i) => (
                                    <Link key={i} href={link.url ?? '#'} className={`rounded border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}>{link.label}</Link>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
