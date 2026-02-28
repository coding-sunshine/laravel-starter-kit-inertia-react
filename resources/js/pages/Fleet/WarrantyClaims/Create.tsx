import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Option { value: string; name: string; }
interface Props {
    workOrders: { id: number; work_order_number: string; title: string }[];
    statuses: Option[];
    presetWorkOrderId?: number | null;
}

export default function FleetWarrantyClaimsCreate({ workOrders, statuses, presetWorkOrderId }: Props) {
    const form = useForm({
        work_order_id: (presetWorkOrderId ?? '') as number | '',
        claim_number: '',
        status: 'submitted',
        claim_amount: '' as string,
        settlement_amount: '' as string,
        submitted_date: '' as string,
        settled_at: '' as string,
    });
    const { data, setData, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Warranty claims', href: '/fleet/warranty-claims' },
        { title: 'Create', href: '/fleet/warranty-claims/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            work_order_id: d.work_order_id === '' ? undefined : d.work_order_id,
            claim_amount: d.claim_amount === '' ? null : d.claim_amount,
            settlement_amount: d.settlement_amount === '' ? null : d.settlement_amount,
            submitted_date: d.submitted_date || null,
            settled_at: d.settled_at || null,
        }));
        form.post('/fleet/warranty-claims');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New warranty claim" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New warranty claim</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label>Work order *</Label>
                        <select value={data.work_order_id === '' ? '' : String(data.work_order_id)} onChange={(e) => setData('work_order_id', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm" required>
                            <option value="">Select</option>
                            {workOrders.map((wo) => <option key={wo.id} value={wo.id}>{wo.work_order_number} – {wo.title}</option>)}
                        </select>
                        {errors.work_order_id && <p className="mt-1 text-sm text-destructive">{errors.work_order_id}</p>}
                    </div>
                    <div>
                        <Label>Claim number *</Label>
                        <Input value={data.claim_number} onChange={(e) => setData('claim_number', e.target.value)} className="mt-1" />
                        {errors.claim_number && <p className="mt-1 text-sm text-destructive">{errors.claim_number}</p>}
                    </div>
                    <div>
                        <Label>Status *</Label>
                        <select value={data.status} onChange={(e) => setData('status', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Claim amount</Label>
                            <Input type="number" step="0.01" value={data.claim_amount} onChange={(e) => setData('claim_amount', e.target.value)} className="mt-1" />
                        </div>
                        <div>
                            <Label>Settlement amount</Label>
                            <Input type="number" step="0.01" value={data.settlement_amount} onChange={(e) => setData('settlement_amount', e.target.value)} className="mt-1" />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Submitted date</Label>
                            <Input type="date" value={data.submitted_date} onChange={(e) => setData('submitted_date', e.target.value)} className="mt-1" />
                        </div>
                        <div>
                            <Label>Settled at</Label>
                            <Input type="date" value={data.settled_at} onChange={(e) => setData('settled_at', e.target.value)} className="mt-1" />
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Save</Button>
                        <Button type="button" variant="outline" asChild><Link href="/fleet/warranty-claims">Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
