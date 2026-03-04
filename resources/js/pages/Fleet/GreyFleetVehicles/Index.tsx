import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2, UserCog } from 'lucide-react';

interface Row {
    id: number;
    registration?: string;
    make?: string;
    model?: string;
    year?: number;
    is_approved?: boolean;
    user?: { id: number; name: string };
    driver?: { id: number; first_name: string; last_name: string };
}
interface Props {
    greyFleetVehicles: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    users: { id: number; name: string }[];
    drivers: { id: number; name: string }[];
}

export default function FleetGreyFleetVehiclesIndex({
    greyFleetVehicles,
    users: _users,
    drivers: _drivers,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Grey fleet vehicles', href: '/fleet/grey-fleet-vehicles' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Grey fleet vehicles" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Grey fleet vehicles
                    </h1>
                    <Button asChild>
                        <Link href="/fleet/grey-fleet-vehicles/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>
                {greyFleetVehicles.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <UserCog className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No grey fleet vehicles yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/grey-fleet-vehicles/create">
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
                                            Registration
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Make / Model
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Owner (user)
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Driver
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Approved
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {greyFleetVehicles.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                {row.registration ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {[row.make, row.model]
                                                    .filter(Boolean)
                                                    .join(' ') || '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.user?.name ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.driver
                                                    ? `${row.driver.first_name} ${row.driver.last_name}`
                                                    : '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.is_approved ? 'Yes' : 'No'}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/grey-fleet-vehicles/${row.id}`}
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
                                                        href={`/fleet/grey-fleet-vehicles/${row.id}/edit`}
                                                    >
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/grey-fleet-vehicles/${row.id}`}
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
                        {greyFleetVehicles.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {greyFleetVehicles.links.map((link, i) => (
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
