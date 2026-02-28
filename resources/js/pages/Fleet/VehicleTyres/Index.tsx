import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2, Car } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface Row {
    id: number;
    position: string;
    size?: string;
    brand?: string;
    fitted_at?: string;
    tread_depth_mm?: string;
    vehicle?: { id: number; registration: string };
    tyre_inventory?: { id: number; label: string };
}
interface Props {
    vehicleTyres: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    vehicles: { id: number; registration: string }[];
    tyreInventory: { id: number; label: string }[];
    positionOptions: { value: string; name: string }[];
}

export default function FleetVehicleTyresIndex({ vehicleTyres, vehicles, tyreInventory, positionOptions }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicle tyres', href: '/fleet/vehicle-tyres' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Vehicle tyres" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Vehicle tyres</h1>
                    <Button asChild>
                        <Link href="/fleet/vehicle-tyres/create"><Plus className="mr-2 size-4" />New</Link>
                    </Button>
                </div>
                {vehicleTyres.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Car className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No vehicle tyres yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/vehicle-tyres/create">Add</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Vehicle</th>
                                        <th className="p-3 text-left font-medium">Position</th>
                                        <th className="p-3 text-left font-medium">Size / Brand</th>
                                        <th className="p-3 text-left font-medium">Fitted at</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {vehicleTyres.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.vehicle?.registration ?? '—'}</td>
                                            <td className="p-3">{positionOptions.find(p => p.value === row.position)?.name ?? row.position}</td>
                                            <td className="p-3">{(row.size ?? '') + (row.brand ? ` ${row.brand}` : '') || (row.tyre_inventory?.label ?? '—')}</td>
                                            <td className="p-3">{row.fitted_at ? new Date(row.fitted_at).toLocaleDateString() : '—'}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/vehicle-tyres/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/vehicle-tyres/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/vehicle-tyres/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {vehicleTyres.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {vehicleTyres.links.map((link, i) => (
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
