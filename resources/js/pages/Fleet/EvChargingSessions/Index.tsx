import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Battery, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface Row {
    id: number;
    session_id: string;
    start_timestamp: string;
    session_type: string;
    energy_delivered_kwh?: string;
    vehicle?: { id: number; registration: string };
}
interface Props {
    evChargingSessions: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    vehicles: { id: number; registration: string }[];
    evChargingStations: { id: number; name: string }[];
    sessionTypes: { value: string; name: string }[];
}

export default function FleetEvChargingSessionsIndex({ evChargingSessions, filters, vehicles, evChargingStations, sessionTypes }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'EV charging sessions', href: '/fleet/ev-charging-sessions' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – EV charging sessions" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">EV charging sessions</h1>
                    <Button asChild>
                        <Link href="/fleet/ev-charging-sessions/create"><Plus className="mr-2 size-4" />New</Link>
                    </Button>
                </div>
                <form method="get" className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Vehicle</Label>
                        <select name="vehicle_id" defaultValue={filters.vehicle_id ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {vehicles.map((v) => <option key={v.id} value={v.id}>{v.registration}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Station</Label>
                        <select name="charging_station_id" defaultValue={filters.charging_station_id ?? ''} className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {evChargingStations.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Type</Label>
                        <select name="session_type" defaultValue={filters.session_type ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {sessionTypes.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">Filter</Button>
                </form>
                {evChargingSessions.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Battery className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No EV charging sessions yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/ev-charging-sessions/create">Add session</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Session ID</th>
                                        <th className="p-3 text-left font-medium">Start</th>
                                        <th className="p-3 text-left font-medium">Type</th>
                                        <th className="p-3 text-left font-medium">Energy (kWh)</th>
                                        <th className="p-3 text-left font-medium">Vehicle</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {evChargingSessions.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.session_id}</td>
                                            <td className="p-3">{new Date(row.start_timestamp).toLocaleString()}</td>
                                            <td className="p-3">{row.session_type}</td>
                                            <td className="p-3">{row.energy_delivered_kwh ?? '—'}</td>
                                            <td className="p-3">{row.vehicle?.registration ?? '—'}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/ev-charging-sessions/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/ev-charging-sessions/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/ev-charging-sessions/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {evChargingSessions.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {evChargingSessions.links.map((link, i) => (
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
