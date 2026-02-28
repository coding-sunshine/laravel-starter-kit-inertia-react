import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2, CircleDot } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface Row {
    id: number;
    size: string;
    brand?: string;
    pattern?: string;
    category?: string;
    quantity?: number;
    unit_cost?: string;
    is_active?: boolean;
}
interface Props {
    tyreInventory: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
}

export default function FleetTyreInventoryIndex({ tyreInventory }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Tyre inventory', href: '/fleet/tyre-inventory' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Tyre inventory" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Tyre inventory</h1>
                    <Button asChild>
                        <Link href="/fleet/tyre-inventory/create"><Plus className="mr-2 size-4" />New</Link>
                    </Button>
                </div>
                {tyreInventory.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <CircleDot className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No tyre inventory yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/tyre-inventory/create">Add</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Size</th>
                                        <th className="p-3 text-left font-medium">Brand</th>
                                        <th className="p-3 text-left font-medium">Quantity</th>
                                        <th className="p-3 text-left font-medium">Unit cost</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {tyreInventory.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.size}</td>
                                            <td className="p-3">{row.brand ?? '—'}</td>
                                            <td className="p-3">{row.quantity ?? '—'}</td>
                                            <td className="p-3">{row.unit_cost ?? '—'}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/tyre-inventory/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/tyre-inventory/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/tyre-inventory/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {tyreInventory.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {tyreInventory.links.map((link, i) => (
                                    <Link key={i} href={link.url ?? '#'} className={`rounded border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}>{link.label}</Link>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
