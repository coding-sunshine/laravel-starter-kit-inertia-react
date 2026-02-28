import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2, FileCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface Row {
    id: number;
    claim_date: string;
    distance_km?: number;
    amount_claimed?: string;
    amount_approved?: string;
    status: string;
    grey_fleet_vehicle?: { id: number; registration?: string; make?: string; model?: string };
    user?: { id: number; name: string };
}
interface Props {
    mileageClaims: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    greyFleetVehicles: { id: number; label: string }[];
    users: { id: number; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetMileageClaimsIndex({ mileageClaims, greyFleetVehicles, users, statuses }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Mileage claims', href: '/fleet/mileage-claims' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Mileage claims" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Mileage claims</h1>
                    <Button asChild>
                        <Link href="/fleet/mileage-claims/create"><Plus className="mr-2 size-4" />New</Link>
                    </Button>
                </div>
                {mileageClaims.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <FileCheck className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No mileage claims yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/mileage-claims/create">Add</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Claim date</th>
                                        <th className="p-3 text-left font-medium">Vehicle</th>
                                        <th className="p-3 text-left font-medium">User</th>
                                        <th className="p-3 text-left font-medium">Distance (km)</th>
                                        <th className="p-3 text-left font-medium">Amount claimed</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {mileageClaims.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{new Date(row.claim_date).toLocaleDateString()}</td>
                                            <td className="p-3">{row.grey_fleet_vehicle ? (row.grey_fleet_vehicle.registration ?? [row.grey_fleet_vehicle.make, row.grey_fleet_vehicle.model].filter(Boolean).join(' ')) : '—'}</td>
                                            <td className="p-3">{row.user?.name ?? '—'}</td>
                                            <td className="p-3">{row.distance_km ?? '—'}</td>
                                            <td className="p-3">{row.amount_claimed ?? '—'}</td>
                                            <td className="p-3">{statuses.find(s => s.value === row.status)?.name ?? row.status}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/mileage-claims/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/mileage-claims/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/mileage-claims/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {mileageClaims.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {mileageClaims.links.map((link, i) => (
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
