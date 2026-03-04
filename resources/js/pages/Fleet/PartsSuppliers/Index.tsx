import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Package, Pencil, Plus, Trash2 } from 'lucide-react';

interface Row {
    id: number;
    name: string;
    code?: string;
    contact_name?: string;
    contact_email?: string;
    is_active?: boolean;
}
interface Props {
    partsSuppliers: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}

export default function FleetPartsSuppliersIndex({ partsSuppliers }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Parts suppliers', href: '/fleet/parts-suppliers' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Parts suppliers" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Parts suppliers</h1>
                    <Button asChild>
                        <Link href="/fleet/parts-suppliers/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>
                {partsSuppliers.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Package className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No parts suppliers yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/parts-suppliers/create">
                                Add
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
                                            Contact
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {partsSuppliers.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">{row.name}</td>
                                            <td className="p-3">
                                                {row.code ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.contact_name ??
                                                    row.contact_email ??
                                                    '—'}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/parts-suppliers/${row.id}`}
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
                                                        href={`/fleet/parts-suppliers/${row.id}/edit`}
                                                    >
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/parts-suppliers/${row.id}`}
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
                        {partsSuppliers.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {partsSuppliers.links.map((link, i) => (
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
