import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Flame, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface Record {
    id: number;
    record_date: string;
    scope: string;
    emissions_type: string;
    co2_kg: string;
    vehicle?: { id: number; registration: string };
}
interface Props {
    emissionsRecords: { data: Record[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    vehicles: { id: number; registration: string }[];
    scopes: { value: string; name: string }[];
    emissionsTypes: { value: string; name: string }[];
}

export default function FleetEmissionsRecordsIndex({ emissionsRecords, filters, vehicles, scopes, emissionsTypes }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Emissions records', href: '/fleet/emissions-records' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Emissions records" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Emissions records</h1>
                    <Button asChild>
                        <Link href="/fleet/emissions-records/create"><Plus className="mr-2 size-4" />New</Link>
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
                        <Label>Scope</Label>
                        <select name="scope" defaultValue={filters.scope ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {scopes.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Date</Label>
                        <input type="date" name="record_date" defaultValue={filters.record_date ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm" />
                    </div>
                    <Button type="submit" variant="secondary" size="sm">Filter</Button>
                </form>
                {emissionsRecords.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Flame className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No emissions records yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/emissions-records/create">Add emissions record</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Date</th>
                                        <th className="p-3 text-left font-medium">Scope</th>
                                        <th className="p-3 text-left font-medium">Type</th>
                                        <th className="p-3 text-left font-medium">CO₂ (kg)</th>
                                        <th className="p-3 text-left font-medium">Vehicle</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {emissionsRecords.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{new Date(row.record_date).toLocaleDateString()}</td>
                                            <td className="p-3">{row.scope}</td>
                                            <td className="p-3">{row.emissions_type}</td>
                                            <td className="p-3">{row.co2_kg}</td>
                                            <td className="p-3">{row.vehicle?.registration ?? '—'}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/emissions-records/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/emissions-records/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/emissions-records/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {emissionsRecords.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {emissionsRecords.links.map((link, i) => (
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
