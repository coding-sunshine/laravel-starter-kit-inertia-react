import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { ShieldCheck, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface ItemRecord {
    id: number;
    entity_type: string;
    entity_id: number;
    compliance_type: string;
    title: string;
    expiry_date: string;
    status: string;
}
interface Props {
    complianceItems: { data: ItemRecord[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    entityTypes: { value: string; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetComplianceItemsIndex({ complianceItems, filters, entityTypes, statuses }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/compliance-items' },
        { title: 'Compliance items', href: '/fleet/compliance-items' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Compliance items" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Compliance items</h1>
                    <Button asChild>
                        <Link href="/fleet/compliance-items/create"><Plus className="mr-2 size-4" />New</Link>
                    </Button>
                </div>
                <form method="get" className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Entity type</Label>
                        <select name="entity_type" defaultValue={filters.entity_type ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {entityTypes.map((e) => <option key={e.value} value={e.value}>{e.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Status</Label>
                        <select name="status" defaultValue={filters.status ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">Filter</Button>
                </form>
                {complianceItems.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <ShieldCheck className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No compliance items yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/compliance-items/create">Add compliance item</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Entity</th>
                                        <th className="p-3 text-left font-medium">Type</th>
                                        <th className="p-3 text-left font-medium">Title</th>
                                        <th className="p-3 text-left font-medium">Expiry</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {complianceItems.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.entity_type} #{row.entity_id}</td>
                                            <td className="p-3">{row.compliance_type}</td>
                                            <td className="p-3"><Link href={`/fleet/compliance-items/${row.id}`} className="font-medium hover:underline">{row.title}</Link></td>
                                            <td className="p-3">{new Date(row.expiry_date).toLocaleDateString()}</td>
                                            <td className="p-3">{row.status}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/compliance-items/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/compliance-items/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/compliance-items/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {complianceItems.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {complianceItems.links.map((link, i) => (
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
