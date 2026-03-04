import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Lock, Search } from 'lucide-react';

interface Row {
    id: number;
    event_type: string;
    event_timestamp: string;
    lat?: number;
    lng?: number;
    device_id?: string;
    alert_sent: boolean;
    vehicle?: { id: number; registration: string };
}
interface Option {
    value: string;
    name: string;
}
interface Props {
    elockEvents: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: Record<string, string>;
    vehicles: { id: number; registration: string }[];
    eventTypes: Option[];
}

export default function FleetElockEventsIndex({
    elockEvents,
    filters,
    vehicles,
    eventTypes,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'E-lock events', href: '/fleet/e-lock-events' },
    ];

    const applyFilters = (e: React.FormEvent) => {
        e.preventDefault();
        const form = e.currentTarget as HTMLFormElement;
        const data = new FormData(form);
        router.get(
            '/fleet/e-lock-events',
            Object.fromEntries(data.entries()) as Record<string, string>,
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – E-lock events" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">E-lock events</h1>
                <form
                    onSubmit={applyFilters}
                    className="flex flex-wrap items-end gap-4 rounded-lg border p-4"
                >
                    <div className="space-y-1">
                        <Label>Vehicle</Label>
                        <select
                            name="vehicle_id"
                            defaultValue={filters.vehicle_id ?? ''}
                            className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
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
                        <Label>Event type</Label>
                        <select
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
                        <Label>From date</Label>
                        <Input
                            name="from_date"
                            type="date"
                            defaultValue={filters.from_date ?? ''}
                            className="h-9 w-40"
                        />
                    </div>
                    <div className="space-y-1">
                        <Label>To date</Label>
                        <Input
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
                {elockEvents.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Lock className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No e-lock events found.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">
                                            Event type
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Timestamp
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Vehicle
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Alert sent
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {elockEvents.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                {eventTypes.find(
                                                    (t) =>
                                                        t.value ===
                                                        row.event_type,
                                                )?.name ?? row.event_type}
                                            </td>
                                            <td className="p-3">
                                                {new Date(
                                                    row.event_timestamp,
                                                ).toLocaleString()}
                                            </td>
                                            <td className="p-3">
                                                {row.vehicle?.registration ??
                                                    '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.alert_sent ? 'Yes' : 'No'}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/e-lock-events/${row.id}`}
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
                        {elockEvents.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {elockEvents.links.map((link, i) => (
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
