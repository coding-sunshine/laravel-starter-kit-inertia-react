import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link, router } from '@inertiajs/react';
import { Cpu, Pencil, Plus, Search, Trash2 } from 'lucide-react';

interface DeviceRecord {
    id: number;
    device_id: string;
    provider: string;
    status: string;
    is_active: boolean;
    vehicle?: { id: number; registration: string };
}
interface Props {
    telematicsDevices: {
        data: DeviceRecord[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: Record<string, string>;
}

export default function FleetTelematicsDevicesIndex({
    telematicsDevices,
    filters,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/telematics-devices' },
        { title: 'Telematics devices', href: '/fleet/telematics-devices' },
    ];

    const applyFilters = (e: React.FormEvent) => {
        e.preventDefault();
        const form = e.currentTarget as HTMLFormElement;
        const data = new FormData(form);
        const params: Record<string, string> = {};
        if (data.get('is_active'))
            params.is_active = data.get('is_active') as string;
        if (data.get('status')) params.status = data.get('status') as string;
        router.get('/fleet/telematics-devices', params, {
            preserveState: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Telematics devices" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Telematics devices
                    </h1>
                    <Button asChild>
                        <Link href="/fleet/telematics-devices/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>
                <form
                    onSubmit={applyFilters}
                    className="flex flex-wrap items-end gap-4 rounded-lg border p-4"
                >
                    <div className="space-y-1">
                        <Label htmlFor="status">Status</Label>
                        <select
                            id="status"
                            name="status"
                            defaultValue={filters.status ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                            <option value="decommissioned">
                                Decommissioned
                            </option>
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label htmlFor="is_active">Active</Label>
                        <select
                            id="is_active"
                            name="is_active"
                            defaultValue={filters.is_active ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">
                        <Search className="mr-2 size-4" />
                        Filter
                    </Button>
                </form>
                {telematicsDevices.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Cpu className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No telematics devices yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/telematics-devices/create">
                                Add device
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">
                                            Device ID
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Provider
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Status
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Vehicle
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Active
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {telematicsDevices.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                <Link
                                                    href={`/fleet/telematics-devices/${row.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {row.device_id}
                                                </Link>
                                            </td>
                                            <td className="p-3">
                                                {row.provider}
                                            </td>
                                            <td className="p-3">
                                                {row.status}
                                            </td>
                                            <td className="p-3">
                                                {row.vehicle?.registration ??
                                                    '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.is_active ? 'Yes' : 'No'}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/telematics-devices/${row.id}/edit`}
                                                    >
                                                        <Pencil className="mr-1 size-3.5" />
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/telematics-devices/${row.id}`}
                                                    method="delete"
                                                    className="ml-2 inline"
                                                    onSubmit={(e) => {
                                                        if (
                                                            !confirm(
                                                                'Delete this device?',
                                                            )
                                                        )
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
                        {telematicsDevices.links &&
                            telematicsDevices.links.length > 1 && (
                                <div className="flex flex-wrap gap-2">
                                    {telematicsDevices.links.map((link, i) => (
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
