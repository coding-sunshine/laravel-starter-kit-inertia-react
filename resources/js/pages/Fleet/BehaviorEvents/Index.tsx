import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, Search } from 'lucide-react';

interface EventRecord {
    id: number;
    event_type: string;
    occurred_at: string;
    severity: string | null;
    vehicle?: { id: number; registration: string };
    driver?: { id: number; first_name: string; last_name: string };
    trip?: { id: number };
}
interface Option {
    value: string;
    name: string;
}
interface Props {
    behaviorEvents: {
        data: EventRecord[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: Record<string, string>;
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; first_name: string; last_name: string }[];
    eventTypes: Option[];
}

export default function FleetBehaviorEventsIndex({
    behaviorEvents,
    filters,
    vehicles,
    drivers,
    eventTypes,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/behavior-events' },
        { title: 'Behavior events', href: '/fleet/behavior-events' },
    ];

    const applyFilters = (e: React.FormEvent) => {
        e.preventDefault();
        const form = e.currentTarget as HTMLFormElement;
        const data = new FormData(form);
        router.get(
            '/fleet/behavior-events',
            Object.fromEntries(data.entries()) as Record<string, string>,
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Behavior events" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Behavior events</h1>
                <form
                    onSubmit={applyFilters}
                    className="flex flex-wrap items-end gap-4 rounded-lg border p-4"
                >
                    <div className="space-y-1">
                        <Label htmlFor="vehicle_id">Vehicle</Label>
                        <select
                            id="vehicle_id"
                            name="vehicle_id"
                            defaultValue={filters.vehicle_id ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {vehicles.map((v) => (
                                <option key={v.id} value={v.id}>
                                    {v.registration}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="driver_id">Driver</Label>
                        <select
                            id="driver_id"
                            name="driver_id"
                            defaultValue={filters.driver_id ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {drivers.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.last_name}, {d.first_name}
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
                {behaviorEvents.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <AlertTriangle className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No behavior events found.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">
                                            Type
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Occurred
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Vehicle
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Driver
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Severity
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {behaviorEvents.data.map((row) => (
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
                                                {row.vehicle?.registration ??
                                                    '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.driver
                                                    ? `${row.driver.first_name} ${row.driver.last_name}`
                                                    : '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.severity ?? '—'}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/behavior-events/${row.id}`}
                                                    >
                                                        View
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {behaviorEvents.links &&
                            behaviorEvents.links.length > 1 && (
                                <div className="flex flex-wrap gap-2">
                                    {behaviorEvents.links.map((link, i) => (
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
