import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { ClipboardCheck, Pencil, Plus, Trash2 } from 'lucide-react';

interface Row {
    id: number;
    name: string;
    code?: string;
    check_type: string;
    category?: string;
    is_active: boolean;
}
interface Props {
    vehicleCheckTemplates: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: Record<string, string>;
    checkTypes: { value: string; name: string }[];
}

export default function VehicleCheckTemplatesIndex({
    vehicleCheckTemplates,
    filters,
    checkTypes,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        {
            title: 'Vehicle check templates',
            href: '/fleet/vehicle-check-templates',
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Vehicle check templates" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Vehicle check templates
                    </h1>
                    <Button asChild>
                        <Link href="/fleet/vehicle-check-templates/create">
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
                        <Label>Check type</Label>
                        <select
                            name="check_type"
                            defaultValue={filters.check_type ?? ''}
                            className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {checkTypes.map((c) => (
                                <option key={c.value} value={c.value}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">
                        Filter
                    </Button>
                </form>
                {vehicleCheckTemplates.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <ClipboardCheck className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No vehicle check templates yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/vehicle-check-templates/create">
                                Add template
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
                                            Name
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Code
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Check type
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
                                    {vehicleCheckTemplates.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3 font-medium">
                                                {row.name}
                                            </td>
                                            <td className="p-3">
                                                {row.code ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.check_type}
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
                                                        href={`/fleet/vehicle-check-templates/${row.id}`}
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
                                                        href={`/fleet/vehicle-check-templates/${row.id}/edit`}
                                                    >
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/vehicle-check-templates/${row.id}`}
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
                        {vehicleCheckTemplates.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {vehicleCheckTemplates.links.map((link, i) => (
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
