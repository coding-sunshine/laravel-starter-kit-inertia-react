import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Scale, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface Row {
    id: number;
    recorded_at: string;
    total_weight_kg?: number;
    overload_flag: boolean;
    legal_limit_kg?: number;
    vehicle?: { id: number; registration: string };
}
interface Props {
    axleLoadReadings: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    vehicles: { id: number; registration: string }[];
}

export default function FleetAxleLoadReadingsIndex({ axleLoadReadings, filters, vehicles }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Axle load readings', href: '/fleet/axle-load-readings' },
    ];

    const applyFilters = (e: React.FormEvent) => {
        e.preventDefault();
        const form = e.currentTarget as HTMLFormElement;
        const data = new FormData(form);
        router.get('/fleet/axle-load-readings', Object.fromEntries(data.entries()) as Record<string, string>, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Axle load readings" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Axle load readings</h1>
                <form onSubmit={applyFilters} className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Vehicle</Label>
                        <select name="vehicle_id" defaultValue={filters.vehicle_id ?? ''} className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {vehicles.map((v) => <option key={v.id} value={v.id}>{v.registration}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Date</Label>
                        <input type="date" name="date" defaultValue={filters.date ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm" />
                    </div>
                    <div className="space-y-1">
                        <Label>Overload</Label>
                        <select name="overload_flag" defaultValue={filters.overload_flag ?? ''} className="h-9 w-32 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm"><Search className="mr-2 size-4" />Filter</Button>
                </form>
                {axleLoadReadings.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Scale className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No axle load readings found.</p>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Recorded at</th>
                                        <th className="p-3 text-left font-medium">Vehicle</th>
                                        <th className="p-3 text-left font-medium">Total weight (kg)</th>
                                        <th className="p-3 text-left font-medium">Legal limit (kg)</th>
                                        <th className="p-3 text-left font-medium">Overload</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {axleLoadReadings.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{new Date(row.recorded_at).toLocaleString()}</td>
                                            <td className="p-3">{row.vehicle?.registration ?? '—'}</td>
                                            <td className="p-3">{row.total_weight_kg != null ? Number(row.total_weight_kg) : '—'}</td>
                                            <td className="p-3">{row.legal_limit_kg != null ? Number(row.legal_limit_kg) : '—'}</td>
                                            <td className="p-3">{row.overload_flag ? 'Yes' : 'No'}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/axle-load-readings/${row.id}`}>View</Link></Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {axleLoadReadings.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {axleLoadReadings.links.map((link, i) => (
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
