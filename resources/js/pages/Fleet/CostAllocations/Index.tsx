import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { CreditCard, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface Row { id: number; allocation_date: string; cost_type: string; amount: string; approval_status: string; cost_center?: { name: string }; }
interface Props {
    costAllocations: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    costCenters: { id: number; name: string }[];
    costTypes: { value: string; name: string }[];
    sourceTypes: { value: string; name: string }[];
    approvalStatuses: { value: string; name: string }[];
}

export default function FleetCostAllocationsIndex({ costAllocations, filters, costCenters, costTypes, sourceTypes, approvalStatuses }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Cost allocations', href: '/fleet/cost-allocations' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Cost allocations" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Cost allocations</h1>
                    <Button asChild>
                        <Link href="/fleet/cost-allocations/create"><Plus className="mr-2 size-4" />New</Link>
                    </Button>
                </div>
                <form method="get" className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Cost center</Label>
                        <select name="cost_center_id" defaultValue={filters.cost_center_id ?? ''} className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {costCenters.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Cost type</Label>
                        <select name="cost_type" defaultValue={filters.cost_type ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {costTypes.map((c) => <option key={c.value} value={c.value}>{c.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Approval</Label>
                        <select name="approval_status" defaultValue={filters.approval_status ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {approvalStatuses.map((a) => <option key={a.value} value={a.value}>{a.name}</option>)}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">Filter</Button>
                </form>
                {costAllocations.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <CreditCard className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No cost allocations yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/cost-allocations/create">Add allocation</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Date</th>
                                        <th className="p-3 text-left font-medium">Cost center</th>
                                        <th className="p-3 text-left font-medium">Type</th>
                                        <th className="p-3 text-left font-medium">Amount</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {costAllocations.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{new Date(row.allocation_date).toLocaleDateString()}</td>
                                            <td className="p-3">{row.cost_center?.name ?? '—'}</td>
                                            <td className="p-3">{row.cost_type}</td>
                                            <td className="p-3">{row.amount}</td>
                                            <td className="p-3">{row.approval_status}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/cost-allocations/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/cost-allocations/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/cost-allocations/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {costAllocations.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {costAllocations.links.map((link, i) => (
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
