import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Car, Pencil, Plus, Trash2 } from 'lucide-react';

interface Row {
    id: number;
    booking_start: string;
    booking_end: string;
    status: string;
    purpose?: string;
    vehicle?: { id: number; registration: string };
    user?: { id: number; name: string };
}
interface Props {
    poolVehicleBookings: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    vehicles: { id: number; registration: string }[];
    users: { id: number; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetPoolVehicleBookingsIndex({
    poolVehicleBookings,
    vehicles: _vehicles,
    users: _users,
    statuses,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        {
            title: 'Pool vehicle bookings',
            href: '/fleet/pool-vehicle-bookings',
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Pool vehicle bookings" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Pool vehicle bookings
                    </h1>
                    <Button asChild>
                        <Link href="/fleet/pool-vehicle-bookings/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>
                {poolVehicleBookings.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Car className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No pool vehicle bookings yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/pool-vehicle-bookings/create">
                                Add
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">
                                            Vehicle
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            User
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Start
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            End
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Status
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {poolVehicleBookings.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                {row.vehicle?.registration ??
                                                    '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.user?.name ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {new Date(
                                                    row.booking_start,
                                                ).toLocaleString()}
                                            </td>
                                            <td className="p-3">
                                                {new Date(
                                                    row.booking_end,
                                                ).toLocaleString()}
                                            </td>
                                            <td className="p-3">
                                                {statuses.find(
                                                    (s) =>
                                                        s.value === row.status,
                                                )?.name ?? row.status}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/pool-vehicle-bookings/${row.id}`}
                                                    >
                                                        View
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/pool-vehicle-bookings/${row.id}/edit`}
                                                    >
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/pool-vehicle-bookings/${row.id}`}
                                                    method="delete"
                                                    className="ml-2 inline"
                                                    onSubmit={(e) => {
                                                        if (!confirm('Delete?'))
                                                            e.preventDefault();
                                                    }}
                                                >
                                                    <Button
                                                        type="submit"
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        <Trash2 className="size-3.5 text-destructive" />
                                                    </Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {poolVehicleBookings.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {poolVehicleBookings.links.map((link, i) => (
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
