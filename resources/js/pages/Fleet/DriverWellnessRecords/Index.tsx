import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Heart, Pencil, Plus, Trash2 } from 'lucide-react';

interface Row {
    id: number;
    record_date: string;
    fatigue_level?: number;
    rest_hours?: string;
    sleep_quality?: string;
    driver?: { id: number; first_name: string; last_name: string };
}
interface Props {
    driverWellnessRecords: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: Record<string, string>;
    drivers: { id: number; name: string }[];
    sleepQualities: { value: string; name: string }[];
}

export default function FleetDriverWellnessRecordsIndex(props: Props) {
    const {
        driverWellnessRecords,
        filters,
        drivers,
        sleepQualities: _sleepQualities,
    } = props;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        {
            title: 'Driver wellness records',
            href: '/fleet/driver-wellness-records',
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Driver wellness records" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Driver wellness records
                    </h1>
                    <Button asChild>
                        <Link href="/fleet/driver-wellness-records/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>
                <form
                    method="get"
                    className="flex flex-wrap items-end gap-4 rounded-lg border p-4"
                >
                    <div className="space-y-1">
                        <Label>Driver</Label>
                        <select
                            name="driver_id"
                            defaultValue={filters.driver_id ?? ''}
                            className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {drivers.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">
                        Filter
                    </Button>
                </form>
                {driverWellnessRecords.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Heart className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No wellness records yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/driver-wellness-records/create">
                                Add record
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
                                            Date
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Driver
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Fatigue
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Rest (h)
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {driverWellnessRecords.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                {row.record_date
                                                    ? new Date(row.record_date).toLocaleDateString()
                                                    : '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.driver
                                                    ? row.driver.first_name +
                                                      ' ' +
                                                      row.driver.last_name
                                                    : '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.fatigue_level ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.rest_hours ?? '—'}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/driver-wellness-records/${row.id}`}
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
                                                        href={`/fleet/driver-wellness-records/${row.id}/edit`}
                                                    >
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/driver-wellness-records/${row.id}`}
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
                        {driverWellnessRecords.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {driverWellnessRecords.links.map((link, i) => (
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
