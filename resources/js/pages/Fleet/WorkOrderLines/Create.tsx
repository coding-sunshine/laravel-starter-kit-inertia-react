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
interface PartRecord {
    id: number;
    part_number: string;
    description?: string | null;
    unit_cost?: string | number | null;
}
interface Props {
    workOrder: WorkOrderRecord;
    lineTypes: Option[];
    partsInventory: PartRecord[];
}

export default function FleetWorkOrderLinesCreate({
    workOrder,
    lineTypes,
    partsInventory,
}: Props) {
    const form = useForm({
        parts_inventory_id: '' as number | '',
        line_type: lineTypes[0]?.value ?? 'labour',
        description: '',
        quantity: '1',
        unit_price: '',
        total: '',
        sort_order: '0',
    });
    const { data, setData, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/work-orders' },
        { title: 'Work orders', href: '/fleet/work-orders' },
        {
            title: workOrder.work_order_number,
            href: `/fleet/work-orders/${workOrder.id}`,
        },
        {
            title: 'Add line',
            href: `/fleet/work-orders/${workOrder.id}/work-order-lines/create`,
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
            sort_order: d.sort_order === '' ? 0 : Number(d.sort_order),
        }));
        form.post(`/fleet/work-orders/${workOrder.id}/work-order-lines`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Fleet – Add line to ${workOrder.work_order_number}`}
            />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Add work order line</h1>
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
                                    {p.part_number}{' '}
                                    {p.description ? `– ${p.description}` : ''}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label>Description</Label>
                        <Input
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            className="mt-1"
                        />
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
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Unit price</Label>
                            <Input
                                type="number"
                                step="0.01"
                                value={data.unit_price}
                                onChange={(e) =>
                                    setData('unit_price', e.target.value)
                                }
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <Label>Total</Label>
                            <Input
                                type="number"
                                step="0.01"
                                value={data.total}
                                onChange={(e) =>
                                    setData('total', e.target.value)
                                }
                                className="mt-1"
                            />
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Save
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
