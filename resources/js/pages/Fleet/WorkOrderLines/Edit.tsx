import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Option {
    value: string;
    name: string;
}
interface WorkOrderRecord {
    id: number;
    work_order_number: string;
}
interface LineRecord {
    id: number;
    line_type: string;
    description?: string | null;
    quantity: string | number;
    unit_price?: string | number | null;
    total?: string | number | null;
    sort_order: number;
    parts_inventory_id?: number | null;
}
interface PartRecord {
    id: number;
    part_number: string;
    description?: string | null;
    unit_cost?: string | number | null;
}
interface Props {
    workOrder: WorkOrderRecord;
    workOrderLine: LineRecord;
    lineTypes: Option[];
    partsInventory: PartRecord[];
}

export default function FleetWorkOrderLinesEdit({
    workOrder,
    workOrderLine,
    lineTypes,
    partsInventory,
}: Props) {
    const form = useForm({
        parts_inventory_id: (workOrderLine.parts_inventory_id ?? '') as
            | number
            | '',
        line_type: workOrderLine.line_type,
        description: workOrderLine.description ?? '',
        quantity: String(workOrderLine.quantity),
        unit_price:
            workOrderLine.unit_price != null
                ? String(workOrderLine.unit_price)
                : '',
        total: workOrderLine.total != null ? String(workOrderLine.total) : '',
        sort_order: String(workOrderLine.sort_order),
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
            title: 'Edit line',
            href: `/fleet/work-orders/${workOrder.id}/work-order-lines/${workOrderLine.id}/edit`,
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            parts_inventory_id:
                d.parts_inventory_id === '' ? null : d.parts_inventory_id,
            unit_price: d.unit_price === '' ? null : d.unit_price,
            total: d.total === '' ? null : d.total,
            sort_order: Number(d.sort_order),
            _method: 'PUT',
        }));
        form.post(
            `/fleet/work-orders/${workOrder.id}/work-order-lines/${workOrderLine.id}`,
            { forceFormData: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Fleet – Edit line – ${workOrder.work_order_number}`}
            />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit work order line</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label>Line type *</Label>
                        <select
                            value={data.line_type}
                            onChange={(e) =>
                                setData('line_type', e.target.value)
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {lineTypes.map((t) => (
                                <option key={t.value} value={t.value}>
                                    {t.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label>Part (optional)</Label>
                        <select
                            value={
                                data.parts_inventory_id === ''
                                    ? ''
                                    : String(data.parts_inventory_id)
                            }
                            onChange={(e) =>
                                setData(
                                    'parts_inventory_id',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">None</option>
                            {partsInventory.map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.part_number}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label>Quantity *</Label>
                        <Input
                            type="number"
                            step="0.01"
                            value={data.quantity}
                            onChange={(e) =>
                                setData('quantity', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.quantity && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.quantity}
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
