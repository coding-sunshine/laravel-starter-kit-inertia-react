import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface TripRecord {
    id: number;
    status: string;
    planned_start_time: string | null;
    vehicle?: { id: number; registration: string };
    driver?: { id: number; first_name: string; last_name: string };
    route?: { id: number; name: string };
}
interface Props {
    trips: { data: TripRecord[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; first_name: string; last_name: string }[];
}

export default function FleetTripsIndex({ trips }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/trips' },
        { title: 'Trips', href: '/fleet/trips' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Trips" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Trips</h1>
                {trips.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <MapPin className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No trips yet.</p>
                    </div>
                ) : (
                    <div className="rounded-md border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="p-3 text-left font-medium">Vehicle</th>
                                    <th className="p-3 text-left font-medium">Driver</th>
                                    <th className="p-3 text-left font-medium">Route</th>
                                    <th className="p-3 text-left font-medium">Planned start</th>
                                    <th className="p-3 text-left font-medium">Status</th>
                                    <th className="p-3 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {trips.data.map((row) => (
                                    <tr key={row.id} className="border-b last:border-0">
                                        <td className="p-3">{row.vehicle?.registration ?? '—'}</td>
                                        <td className="p-3">{row.driver ? `${row.driver.first_name} ${row.driver.last_name}` : '—'}</td>
                                        <td className="p-3">{row.route?.name ?? '—'}</td>
                                        <td className="p-3">{row.planned_start_time ? new Date(row.planned_start_time).toLocaleString() : '—'}</td>
                                        <td className="p-3">{row.status}</td>
                                        <td className="p-3 text-right">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={`/fleet/trips/${row.id}`}>View</Link>
                                            </Button>
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
