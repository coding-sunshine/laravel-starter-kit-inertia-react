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
    title: string;
    vehicle_id: number;
    work_type: string;
    priority: string;
    status: string;
}
interface Props {
    workOrder: WorkOrderRecord;
    workTypes: Option[];
    priorities: Option[];
    statuses: Option[];
    urgencies: Option[];
    vehicles: { id: number; registration: string }[];
    garages: { id: number; name: string }[];
}

export default function FleetWorkOrdersEdit({
    workOrder,
    workTypes,
    priorities,
    statuses,
    vehicles,
    garages: _garages,
}: Props) {
    const form = useForm({
        work_order_number: workOrder.work_order_number,
        title: workOrder.title,
        vehicle_id: workOrder.vehicle_id,
        work_type: workOrder.work_type,
        priority: workOrder.priority,
        status: workOrder.status,
    });
    const { data, setData, processing, errors: _errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/work-orders' },
        { title: 'Work orders', href: '/fleet/work-orders' },
        {
            title: workOrder.work_order_number,
            href: `/fleet/work-orders/${workOrder.id}`,
        },
        { title: 'Edit', href: `/fleet/work-orders/${workOrder.id}/edit` },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/fleet/work-orders/${workOrder.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${workOrder.work_order_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit work order</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label>Work order number *</Label>
                        <Input
                            value={data.work_order_number}
                            onChange={(e) =>
                                setData('work_order_number', e.target.value)
                            }
                            className="mt-1"
                        />
                    </div>
                    <div>
                        <Label>Title *</Label>
                        <Input
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="mt-1"
                        />
                    </div>
                    <div>
                        <Label>Vehicle *</Label>
                        <select
                            value={data.vehicle_id}
                            onChange={(e) =>
                                setData('vehicle_id', Number(e.target.value))
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
                        >
                            {vehicles.map((v) => (
                                <option key={v.id} value={v.id}>
                                    {v.registration}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label>Work type</Label>
                        <select
                            value={data.work_type}
                            onChange={(e) =>
                                setData('work_type', e.target.value)
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
                        >
                            {workTypes.map((w) => (
                                <option key={w.value} value={w.value}>
                                    {w.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Priority</Label>
                            <select
                                value={data.priority}
                                onChange={(e) =>
                                    setData('priority', e.target.value)
                                }
                                className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
                            >
                                {priorities.map((p) => (
                                    <option key={p.value} value={p.value}>
                                        {p.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <Label>Status</Label>
                            <select
                                value={data.status}
                                onChange={(e) =>
                                    setData('status', e.target.value)
                                }
                                className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
                            >
                                {statuses.map((s) => (
                                    <option key={s.value} value={s.value}>
                                        {s.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Update
                        </Button>
                        <Button variant="outline" asChild>
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
