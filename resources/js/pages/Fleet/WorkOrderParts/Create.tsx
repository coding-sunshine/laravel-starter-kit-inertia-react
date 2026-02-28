import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface WorkOrderRecord { id: number; work_order_number: string; }
interface PartRecord { id: number; part_number: string; description?: string | null; unit_cost?: string | number | null; }
interface Props {
    workOrder: WorkOrderRecord;
    partsInventory: PartRecord[];
}

export default function FleetWorkOrderPartsCreate({ workOrder, partsInventory }: Props) {
    const form = useForm({
        parts_inventory_id: '' as number | '',
        quantity_used: '1',
        unit_cost: '',
        total_cost: '',
        notes: '',
    });
    const { data, setData, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/work-orders' },
        { title: workOrder.work_order_number, href: `/fleet/work-orders/${workOrder.id}` },
        { title: 'Add part', href: `/fleet/work-orders/${workOrder.id}/work-order-parts/create` },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            parts_inventory_id: d.parts_inventory_id === '' ? undefined : d.parts_inventory_id,
            unit_cost: d.unit_cost === '' ? null : d.unit_cost,
            total_cost: d.total_cost === '' ? null : d.total_cost,
        }));
        form.post(`/fleet/work-orders/${workOrder.id}/work-order-parts`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Add part to ${workOrder.work_order_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Add work order part</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label>Part *</Label>
                        <select value={data.parts_inventory_id === '' ? '' : String(data.parts_inventory_id)} onChange={(e) => setData('parts_inventory_id', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm" required>
                            <option value="">Select</option>
                            {partsInventory.map((p) => <option key={p.id} value={p.id}>{p.part_number} {p.description ? `– ${p.description}` : ''}</option>)}
                        </select>
                        {errors.parts_inventory_id && <p className="mt-1 text-sm text-destructive">{errors.parts_inventory_id}</p>}
                    </div>
                    <div>
                        <Label>Quantity used *</Label>
                        <Input type="number" step="0.01" value={data.quantity_used} onChange={(e) => setData('quantity_used', e.target.value)} className="mt-1" />
                        {errors.quantity_used && <p className="mt-1 text-sm text-destructive">{errors.quantity_used}</p>}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Unit cost</Label>
                            <Input type="number" step="0.01" value={data.unit_cost} onChange={(e) => setData('unit_cost', e.target.value)} className="mt-1" />
                        </div>
                        <div>
                            <Label>Total cost</Label>
                            <Input type="number" step="0.01" value={data.total_cost} onChange={(e) => setData('total_cost', e.target.value)} className="mt-1" />
                        </div>
                    </div>
                    <div>
                        <Label>Notes</Label>
                        <textarea value={data.notes} onChange={(e) => setData('notes', e.target.value)} className="mt-1 flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm" />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Save</Button>
                        <Button type="button" variant="outline" asChild><Link href={`/fleet/work-orders/${workOrder.id}`}>Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
