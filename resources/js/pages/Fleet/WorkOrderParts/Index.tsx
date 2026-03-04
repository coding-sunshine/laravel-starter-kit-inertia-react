import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';

interface PartRecord {
    id: number;
    quantity_used: string | number;
    unit_cost?: string | number | null;
    total_cost?: string | number | null;
    notes?: string | null;
    partsInventory?: { id: number; part_number: string };
}
interface WorkOrderRecord {
    id: number;
    work_order_number: string;
}
interface Props {
    workOrder: WorkOrderRecord;
    workOrderParts: PartRecord[];
}

export default function FleetWorkOrderPartsIndex({
    workOrder,
    workOrderParts,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/work-orders' },
        { title: 'Work orders', href: '/fleet/work-orders' },
        {
            title: workOrder.work_order_number,
            href: `/fleet/work-orders/${workOrder.id}`,
        },
        {
            title: 'Parts',
            href: `/fleet/work-orders/${workOrder.id}/work-order-parts`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${workOrder.work_order_number} – Parts`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">
                        Work order parts – {workOrder.work_order_number}
                    </h1>
                    <div className="flex gap-2">
                        <Button asChild>
                            <Link
                                href={`/fleet/work-orders/${workOrder.id}/work-order-parts/create`}
                            >
                                <Plus className="mr-2 size-4" />
                                Add part
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/fleet/work-orders/${workOrder.id}`}>
                                Back to work order
                            </Link>
                        </Button>
                    </div>
                </div>
                {workOrderParts.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No parts.{' '}
                        <Link
                            href={`/fleet/work-orders/${workOrder.id}/work-order-parts/create`}
                            className="text-primary underline"
                        >
                            Add part
                        </Link>
                    </p>
                ) : (
                    <div className="rounded-md border text-sm">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="p-3 text-left font-medium">
                                        Part
                                    </th>
                                    <th className="p-3 text-right font-medium">
                                        Qty used
                                    </th>
                                    <th className="p-3 text-right font-medium">
                                        Unit cost
                                    </th>
                                    <th className="p-3 text-right font-medium">
                                        Total cost
                                    </th>
                                    <th className="p-3 text-right font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {workOrderParts.map((part) => (
                                    <tr
                                        key={part.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="p-3">
                                            {part.partsInventory?.part_number ??
                                                '—'}
                                        </td>
                                        <td className="p-3 text-right">
                                            {part.quantity_used}
                                        </td>
                                        <td className="p-3 text-right">
                                            {part.unit_cost ?? '—'}
                                        </td>
                                        <td className="p-3 text-right">
                                            {part.total_cost ?? '—'}
                                        </td>
                                        <td className="p-3 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/fleet/work-orders/${workOrder.id}/work-order-parts/${part.id}/edit`}
                                                >
                                                    <Pencil className="size-3.5" />
                                                </Link>
                                            </Button>
                                            <Form
                                                action={`/fleet/work-orders/${workOrder.id}/work-order-parts/${part.id}`}
                                                method="delete"
                                                className="ml-2 inline"
                                                onSubmit={(e) => {
                                                    if (!confirm('Delete?'))
                                                        e.preventDefault();
                                                }}
                                            >
                                                <Button
                                                    type="submit"
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <Trash2 className="size-3.5 text-destructive" />
                                                </Button>
                                            </Form>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
