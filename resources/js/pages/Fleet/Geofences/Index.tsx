import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { MapPin, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface GeofenceRecord { id: number; name: string; geofence_type: string; is_active: boolean }
interface Props { geofences: { data: GeofenceRecord[]; last_page: number; links: { url: string | null; label: string; active: boolean }[] } }

export default function FleetGeofencesIndex({ geofences }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/geofences' }, { title: 'Geofences', href: '/fleet/geofences' }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Geofences" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Geofences</h1>
                    <Button asChild><Link href="/fleet/geofences/create"><Plus className="mr-2 size-4" />New</Link></Button>
                </div>
                {geofences.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <MapPin className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No geofences yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/geofences/create">Create geofence</Link></Button>
                    </div>
                ) : (
                    <div className="rounded-md border">
                        <table className="w-full text-sm">
                            <thead><tr className="border-b bg-muted/50"><th className="p-3 text-left font-medium">Name</th><th className="p-3 text-left font-medium">Type</th><th className="p-3 text-left font-medium">Status</th><th className="p-3 text-right font-medium">Actions</th></tr></thead>
                            <tbody>
                                {geofences.data.map((row) => (
                                    <tr key={row.id} className="border-b last:border-0">
                                        <td className="p-3"><Link href={`/fleet/geofences/${row.id}`} className="font-medium hover:underline">{row.name}</Link></td>
                                        <td className="p-3">{row.geofence_type}</td>
                                        <td className="p-3">{row.is_active ? 'Active' : 'Inactive'}</td>
                                        <td className="p-3 text-right">
                                            <Button variant="outline" size="sm" asChild><Link href={`/fleet/geofences/${row.id}/edit`}><Pencil className="mr-1 size-3.5" />Edit</Link></Button>
                                            <Form action={`/fleet/geofences/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}><Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button></Form>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
