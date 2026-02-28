import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    costCenters: { id: number; name: string }[];
    costTypes: { value: string; name: string }[];
    sourceTypes: { value: string; name: string }[];
    approvalStatuses: { value: string; name: string }[];
}

export default function FleetCostAllocationsCreate({ costCenters, costTypes, sourceTypes, approvalStatuses }: Props) {
    const form = useForm({
        cost_center_id: '' as number | '',
        allocation_date: new Date().toISOString().slice(0, 10),
        cost_type: 'fuel',
        source_type: 'manual_entry',
        amount: '',
        vat_amount: '0',
        approval_status: 'pending',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Cost allocations', href: '/fleet/cost-allocations' },
        { title: 'New', href: '/fleet/cost-allocations/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New cost allocation" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href="/fleet/cost-allocations">Back</Link></Button>
                    <h1 className="text-2xl font-semibold">New cost allocation</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.post('/fleet/cost-allocations'); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Cost center</Label>
                        <select required value={form.data.cost_center_id} onChange={e => form.setData('cost_center_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {costCenters.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Allocation date</Label>
                        <Input type="date" value={form.data.allocation_date} onChange={e => form.setData('allocation_date', e.target.value)} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Cost type</Label>
                            <select value={form.data.cost_type} onChange={e => form.setData('cost_type', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                {costTypes.map((c) => <option key={c.value} value={c.value}>{c.name}</option>)}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Source type</Label>
                            <select value={form.data.source_type} onChange={e => form.setData('source_type', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                {sourceTypes.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                            </select>
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Amount</Label>
                            <Input type="number" step="0.01" value={form.data.amount} onChange={e => form.setData('amount', e.target.value)} required />
                        </div>
                        <div className="space-y-2">
                            <Label>VAT amount</Label>
                            <Input type="number" step="0.01" value={form.data.vat_amount} onChange={e => form.setData('vat_amount', e.target.value)} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Approval status</Label>
                        <select value={form.data.approval_status} onChange={e => form.setData('approval_status', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {approvalStatuses.map((a) => <option key={a.value} value={a.value}>{a.name}</option>)}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Save</Button>
                        <Button type="button" variant="outline" asChild><Link href="/fleet/cost-allocations">Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
