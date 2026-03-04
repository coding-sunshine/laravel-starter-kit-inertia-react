import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface WorkOrderRecord {
    id: number;
    work_order_number: string;
}
interface PartRecord {
    id: number;
    parts_inventory_id: number;
    quantity_used: string | number;
    unit_cost?: string | number | null;
    total_cost?: string | number | null;
    notes?: string | null;
}
interface PartInventoryRecord {
    id: number;
    part_number: string;
    description?: string | null;
    unit_cost?: string | number | null;
}
interface Props {
    workOrder: WorkOrderRecord;
    workOrderPart: PartRecord;
    partsInventory: PartInventoryRecord[];
}

export default function FleetWorkOrderPartsEdit({
    workOrder,
    workOrderPart,
    partsInventory,
}: Props) {
    const form = useForm({
        parts_inventory_id: workOrderPart.parts_inventory_id,
        quantity_used: String(workOrderPart.quantity_used),
        unit_cost:
            workOrderPart.unit_cost != null
                ? String(workOrderPart.unit_cost)
                : '',
        total_cost:
            workOrderPart.total_cost != null
                ? String(workOrderPart.total_cost)
                : '',
        notes: workOrderPart.notes ?? '',
    });
    const { data, setData, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/work-orders' },
        {
            title: workOrder.work_order_number,
            href: `/fleet/work-orders/${workOrder.id}`,
        },
        {
            title: 'Edit part',
            href: `/fleet/work-orders/${workOrder.id}/work-order-parts/${workOrderPart.id}/edit`,
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            unit_cost: d.unit_cost === '' ? null : d.unit_cost,
            total_cost: d.total_cost === '' ? null : d.total_cost,
            _method: 'PUT',
        }));
        form.post(
            `/fleet/work-orders/${workOrder.id}/work-order-parts/${workOrderPart.id}`,
            { forceFormData: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Fleet – Edit part – ${workOrder.work_order_number}`}
            />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit work order part</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label>Part *</Label>
                        <select
                            value={data.parts_inventory_id}
                            onChange={(e) =>
                                setData(
                                    'parts_inventory_id',
                                    Number(e.target.value),
                                )
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            required
                        >
                            {partsInventory.map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.part_number}
                                </option>
                            ))}
                        </select>
                        {errors.parts_inventory_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.parts_inventory_id}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label>Quantity used *</Label>
                        <Input
                            type="number"
                            step="0.01"
                            value={data.quantity_used}
                            onChange={(e) =>
                                setData('quantity_used', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.quantity_used && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.quantity_used}
                            </p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Update
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href={`/fleet/work-orders/${workOrder.id}`}>
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
