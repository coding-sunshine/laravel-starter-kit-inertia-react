import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';

interface LineRecord {
    id: number;
    line_type: string;
    description?: string | null;
    quantity: string | number;
    unit_price?: string | number | null;
    total?: string | number | null;
    sort_order: number;
    partsInventory?: { id: number; part_number: string } | null;
}
interface WorkOrderRecord {
    id: number;
    work_order_number: string;
}
interface Props {
    workOrder: WorkOrderRecord;
    workOrderLines: LineRecord[];
}

export default function FleetWorkOrderLinesIndex({
    workOrder,
    workOrderLines,
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
            title: 'Lines',
            href: `/fleet/work-orders/${workOrder.id}/work-order-lines`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${workOrder.work_order_number} – Lines`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">
                        Work order lines – {workOrder.work_order_number}
                    </h1>
                    <div className="flex gap-2">
                        <Button asChild>
                            <Link
                                href={`/fleet/work-orders/${workOrder.id}/work-order-lines/create`}
                            >
                                <Plus className="mr-2 size-4" />
                                Add line
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/fleet/work-orders/${workOrder.id}`}>
                                Back to work order
                            </Link>
                        </Button>
                    </div>
                </div>
                {workOrderLines.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No lines.{' '}
                        <Link
                            href={`/fleet/work-orders/${workOrder.id}/work-order-lines/create`}
                            className="text-primary underline"
                        >
                            Add line
                        </Link>
                    </p>
                ) : (
                    <div className="rounded-md border text-sm">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="p-3 text-left font-medium">
                                        Type
                                    </th>
                                    <th className="p-3 text-left font-medium">
                                        Description / Part
                                    </th>
                                    <th className="p-3 text-right font-medium">
                                        Qty
                                    </th>
                                    <th className="p-3 text-right font-medium">
                                        Unit price
                                    </th>
                                    <th className="p-3 text-right font-medium">
                                        Total
                                    </th>
                                    <th className="p-3 text-right font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {workOrderLines.map((line) => (
                                    <tr
                                        key={line.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="p-3">
                                            {line.line_type}
                                        </td>
                                        <td className="p-3">
                                            {line.description ??
                                                line.partsInventory
                                                    ?.part_number ??
                                                '—'}
                                        </td>
                                        <td className="p-3 text-right">
                                            {line.quantity}
                                        </td>
                                        <td className="p-3 text-right">
                                            {line.unit_price ?? '—'}
                                        </td>
                                        <td className="p-3 text-right">
                                            {line.total ?? '—'}
                                        </td>
                                        <td className="p-3 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/fleet/work-orders/${workOrder.id}/work-order-lines/${line.id}/edit`}
                                                >
                                                    <Pencil className="size-3.5" />
                                                </Link>
                                            </Button>
                                            <Form
                                                action={`/fleet/work-orders/${workOrder.id}/work-order-lines/${line.id}`}
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
