import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { FileCheck, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface ClaimRecord {
    id: number;
    claim_number: string;
    status: string;
    claim_amount?: string | number | null;
    work_order?: { id: number; work_order_number: string };
}
interface Props {
    warrantyClaims: { data: ClaimRecord[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    workOrders: { id: number; work_order_number: string; title: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetWarrantyClaimsIndex({ warrantyClaims, filters, workOrders, statuses }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Warranty claims', href: '/fleet/warranty-claims' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Warranty claims" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Warranty claims</h1>
                    <Button asChild>
                        <Link href="/fleet/warranty-claims/create"><Plus className="mr-2 size-4" />New</Link>
                    </Button>
                </div>
                <form method="get" className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Work order</Label>
                        <select name="work_order_id" defaultValue={filters.work_order_id ?? ''} className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {workOrders.map((wo) => <option key={wo.id} value={wo.id}>{wo.work_order_number}</option>)}
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
                {warrantyClaims.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <FileCheck className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No warranty claims.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/warranty-claims/create">Add claim</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Claim number</th>
                                        <th className="p-3 text-left font-medium">Work order</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-left font-medium">Claim amount</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {warrantyClaims.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3"><Link href={`/fleet/warranty-claims/${row.id}`} className="font-medium hover:underline">{row.claim_number}</Link></td>
                                            <td className="p-3">{row.work_order ? <Link href={`/fleet/work-orders/${row.work_order.id}`} className="underline">{row.work_order.work_order_number}</Link> : '—'}</td>
                                            <td className="p-3">{row.status}</td>
                                            <td className="p-3">{row.claim_amount ?? '—'}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/warranty-claims/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/warranty-claims/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/warranty-claims/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {warrantyClaims.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {warrantyClaims.links.map((link, i) => (
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
