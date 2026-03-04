import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { MapPin, Search } from 'lucide-react';

interface EventRecord {
    id: number;
    event_type: string;
    occurred_at: string;
    geofence?: { id: number; name: string };
    vehicle?: { id: number; registration: string };
    driver?: { id: number; first_name: string; last_name: string };
}
interface Option {
    value: string;
    name: string;
}
interface Props {
    geofenceEvents: {
        data: EventRecord[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: Record<string, string>;
    geofences: { id: number; name: string }[];
    eventTypes: Option[];
}

export default function FleetGeofenceEventsIndex({
    geofenceEvents,
    filters,
    geofences,
    eventTypes,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/geofence-events' },
        { title: 'Geofence events', href: '/fleet/geofence-events' },
    ];

    const applyFilters = (e: React.FormEvent) => {
        e.preventDefault();
        const form = e.currentTarget as HTMLFormElement;
        const data = new FormData(form);
        router.get(
            '/fleet/geofence-events',
            Object.fromEntries(data.entries()) as Record<string, string>,
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Geofence events" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Geofence events</h1>
                <form
                    onSubmit={applyFilters}
                    className="flex flex-wrap items-end gap-4 rounded-lg border p-4"
                >
                    <div className="space-y-1">
                        <Label htmlFor="geofence_id">Geofence</Label>
                        <select
                            id="geofence_id"
                            name="geofence_id"
                            defaultValue={filters.geofence_id ?? ''}
                            className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {geofences.map((g) => (
                                <option key={g.id} value={g.id}>
                                    {g.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="event_type">Event type</Label>
                        <select
                            id="event_type"
                            name="event_type"
                            defaultValue={filters.event_type ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {eventTypes.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="from_date">From date</Label>
                        <Input
                            id="from_date"
                            name="from_date"
                            type="date"
                            defaultValue={filters.from_date ?? ''}
                            className="h-9 w-40"
                        />
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="to_date">To date</Label>
                        <Input
                            id="to_date"
                            name="to_date"
                            type="date"
                            defaultValue={filters.to_date ?? ''}
                            className="h-9 w-40"
                        />
                    </div>
                    <Button type="submit" variant="secondary" size="sm">
                        <Search className="mr-2 size-4" />
                        Filter
                    </Button>
                </form>
                {geofenceEvents.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <MapPin className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No geofence events found.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">
                                            Event type
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Occurred
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Geofence
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Vehicle
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Driver
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {geofenceEvents.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                {row.event_type}
                                            </td>
                                            <td className="p-3">
                                                {new Date(
                                                    row.occurred_at,
                                                ).toLocaleString()}
                                            </td>
                                            <td className="p-3">
                                                {row.geofence ? (
                                                    <Link
                                                        href={`/fleet/geofences/${row.geofence.id}`}
                                                        className="hover:underline"
                                                    >
                                                        {row.geofence.name}
                                                    </Link>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className="p-3">
                                                {row.vehicle?.registration ??
                                                    '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.driver
                                                    ? `${row.driver.first_name} ${row.driver.last_name}`
                                                    : '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {geofenceEvents.links &&
                            geofenceEvents.links.length > 1 && (
                                <div className="flex flex-wrap gap-2">
                                    {geofenceEvents.links.map((link, i) => (
                                        <Link
                                            key={i}
                                            href={link.url ?? '#'}
                                            className={`rounded border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                        >
                                            {link.label}
                                        </Link>
                                    ))}
                                </div>
                            )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
