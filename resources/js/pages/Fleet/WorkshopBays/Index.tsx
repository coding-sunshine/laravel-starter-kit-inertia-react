import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2, Wrench } from 'lucide-react';

interface Row {
    id: number;
    name: string;
    code?: string;
    status: string;
    is_active: boolean;
    garage?: { id: number; name: string };
}
interface Props {
    workshopBays: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    statuses: { value: string; name: string }[];
}

export default function FleetWorkshopBaysIndex({
    workshopBays,
    statuses,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Workshop bays', href: '/fleet/workshop-bays' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Workshop bays" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Workshop bays</h1>
                    <Button asChild>
                        <Link href="/fleet/workshop-bays/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>
                {workshopBays.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Wrench className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No workshop bays yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/workshop-bays/create">Add</Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">
                                            Name
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Code
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Garage
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Status
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
                                    {workshopBays.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">{row.name}</td>
                                            <td className="p-3">
                                                {row.code ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.garage?.name ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {statuses.find(
                                                    (s) =>
                                                        s.value === row.status,
                                                )?.name ?? row.status}
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
                                                        href={`/fleet/workshop-bays/${row.id}`}
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
                                                        href={`/fleet/workshop-bays/${row.id}/edit`}
                                                    >
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/workshop-bays/${row.id}`}
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
                        {workshopBays.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {workshopBays.links.map((link, i) => (
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
