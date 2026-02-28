import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2, Package } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface Row {
    id: number;
    part_number: string;
    description?: string;
    category?: string;
    quantity?: number;
    min_quantity?: number;
    unit?: string;
    unit_cost?: string;
    garage?: { id: number; name: string };
    supplier?: { id: number; name: string };
}
interface Props {
    partsInventory: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
}

export default function FleetPartsInventoryIndex({ partsInventory }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Parts inventory', href: '/fleet/parts-inventory' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Parts inventory" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Parts inventory</h1>
                    <Button asChild>
                        <Link href="/fleet/parts-inventory/create"><Plus className="mr-2 size-4" />New</Link>
                    </Button>
                </div>
                {partsInventory.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Package className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No parts inventory yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/parts-inventory/create">Add</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Part number</th>
                                        <th className="p-3 text-left font-medium">Description</th>
                                        <th className="p-3 text-left font-medium">Garage</th>
                                        <th className="p-3 text-left font-medium">Supplier</th>
                                        <th className="p-3 text-left font-medium">Qty</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {partsInventory.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.part_number}</td>
                                            <td className="p-3 max-w-[200px] truncate">{row.description ?? '—'}</td>
                                            <td className="p-3">{row.garage?.name ?? '—'}</td>
                                            <td className="p-3">{row.supplier?.name ?? '—'}</td>
                                            <td className="p-3">{row.quantity ?? '—'}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/parts-inventory/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/parts-inventory/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/parts-inventory/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {partsInventory.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {partsInventory.links.map((link, i) => (
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
