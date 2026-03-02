import AppLayout from '@/layouts/app-layout';
import { FleetPageHeader } from '@/components/fleet';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Bell, Settings } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface Row {
    id: number;
    title: string;
    alert_type: string;
    severity: string;
    status: string;
    triggered_at: string;
    entity_type?: string | null;
    entity_id?: number | null;
    entity_label?: string | null;
}
interface Props {
    alerts: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    statuses: { value: string; name: string }[];
    severities: { value: string; name: string }[];
    alertTypes: { value: string; name: string }[];
}

export default function FleetAlertsIndex({ alerts, filters, statuses, severities, alertTypes }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Alerts', href: '/fleet/alerts' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Alerts" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <FleetPageHeader
                        title="Alerts"
                        description="Status, severity, and entity. Acknowledge or filter by status/type."
                    />
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/fleet/alert-preferences" className="gap-2">
                            <Settings className="size-4" />
                            Alert preferences
                        </Link>
                    </Button>
                </div>
                <form method="get" className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Status</Label>
                        <select name="status" defaultValue={filters.status ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Type</Label>
                        <select name="alert_type" defaultValue={filters.alert_type ?? ''} className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {alertTypes.map((t) => <option key={t.value} value={t.value}>{t.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Severity</Label>
                        <select name="severity" defaultValue={filters.severity ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {severities.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">Filter</Button>
                </form>
                {alerts.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Bell className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No alerts.</p>
                        <Button variant="outline" asChild className="mt-4"><Link href="/fleet/alert-preferences">Alert preferences</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full min-w-[700px] text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Title</th>
                                        <th className="p-3 text-left font-medium">Type</th>
                                        <th className="p-3 text-left font-medium">Severity</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-left font-medium">Entity</th>
                                        <th className="p-3 text-left font-medium">Triggered</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {alerts.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.title}</td>
                                            <td className="p-3">{row.alert_type}</td>
                                            <td className="p-3">{row.severity}</td>
                                            <td className="p-3">{row.status}</td>
                                            <td className="p-3 text-muted-foreground">{row.entity_label ?? '—'}</td>
                                            <td className="p-3">{new Date(row.triggered_at).toLocaleString()}</td>
                                            <td className="p-3 text-right">
                                                {row.status === 'active' && (
                                                    <Button
                                                        variant="secondary"
                                                        size="sm"
                                                        className="mr-1"
                                                        onClick={() => router.post(`/fleet/alerts/${row.id}/acknowledge`)}
                                                    >
                                                        Acknowledge
                                                    </Button>
                                                )}
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/alerts/${row.id}`}>View</Link></Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {alerts.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {alerts.links.map((link, i) => (
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
