import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link, router } from '@inertiajs/react';
import { MapPin, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface Row {
    id: number;
    allocated_from: string;
    allocated_to?: string;
    spot_identifier?: string;
    cost?: number;
    vehicle?: { id: number; registration: string };
    location?: { id: number; name: string };
}
interface Props {
    parkingAllocations: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    vehicles: { id: number; registration: string }[];
    locations: { id: number; name: string }[];
}

export default function FleetParkingAllocationsIndex({ parkingAllocations, filters, vehicles, locations }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Parking allocations', href: '/fleet/parking-allocations' },
    ];

    const applyFilters = (e: React.FormEvent) => {
        e.preventDefault();
        const form = e.currentTarget as HTMLFormElement;
        const data = new FormData(form);
        router.get('/fleet/parking-allocations', Object.fromEntries(data.entries()) as Record<string, string>, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Parking allocations" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Parking allocations</h1>
                    <Button asChild>
                        <Link href="/fleet/parking-allocations/create"><Plus className="mr-2 size-4" />New</Link>
                    </Button>
                </div>
                <form onSubmit={applyFilters} className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Vehicle</Label>
                        <select name="vehicle_id" defaultValue={filters.vehicle_id ?? ''} className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {vehicles.map((v) => <option key={v.id} value={v.id}>{v.registration}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Location</Label>
                        <select name="location_id" defaultValue={filters.location_id ?? ''} className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {locations.map((l) => <option key={l.id} value={l.id}>{l.name}</option>)}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">Filter</Button>
                </form>
                {parkingAllocations.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <MapPin className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No parking allocations yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/parking-allocations/create">Add</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Vehicle</th>
                                        <th className="p-3 text-left font-medium">Location</th>
                                        <th className="p-3 text-left font-medium">From</th>
                                        <th className="p-3 text-left font-medium">To</th>
                                        <th className="p-3 text-left font-medium">Spot</th>
                                        <th className="p-3 text-left font-medium">Cost</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {parkingAllocations.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.vehicle?.registration ?? '—'}</td>
                                            <td className="p-3">{row.location?.name ?? '—'}</td>
                                            <td className="p-3">{new Date(row.allocated_from).toLocaleString()}</td>
                                            <td className="p-3">{row.allocated_to ? new Date(row.allocated_to).toLocaleString() : '—'}</td>
                                            <td className="p-3">{row.spot_identifier ?? '—'}</td>
                                            <td className="p-3">{row.cost != null ? Number(row.cost) : '—'}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/parking-allocations/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/parking-allocations/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/parking-allocations/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {parkingAllocations.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {parkingAllocations.links.map((link, i) => (
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
