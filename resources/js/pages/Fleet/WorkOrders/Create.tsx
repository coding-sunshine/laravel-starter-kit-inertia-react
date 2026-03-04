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
interface Props {
    workOrderNumber: string;
    workTypes: Option[];
    priorities: Option[];
    statuses: Option[];
    urgencies: Option[];
    vehicles: { id: number; registration: string }[];
    garages: { id: number; name: string }[];
}

export default function FleetWorkOrdersCreate({
    workOrderNumber,
    workTypes,
    priorities,
    statuses,
    vehicles,
    garages: _garages,
}: Props) {
    const form = useForm({
        work_order_number: workOrderNumber,
        title: '',
        vehicle_id: '' as number | '',
        work_type: workTypes[0]?.value ?? '',
        priority: priorities[0]?.value ?? '',
        status: statuses[0]?.value ?? '',
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/work-orders' },
        { title: 'Work orders', href: '/fleet/work-orders' },
        { title: 'Create', href: '/fleet/work-orders/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            vehicle_id: d.vehicle_id === '' ? undefined : d.vehicle_id,
        }));
        form.post('/fleet/work-orders');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New work order" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New work order</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="work_order_number">
                            Work order number *
                        </Label>
                        <Input
                            id="work_order_number"
                            value={data.work_order_number}
                            onChange={(e) =>
                                setData('work_order_number', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.work_order_number && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.work_order_number}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="title">Title *</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="mt-1"
                        />
                        {errors.title && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.title}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="vehicle_id">Vehicle *</Label>
                        <select
                            id="vehicle_id"
                            value={
                                data.vehicle_id === ''
                                    ? ''
                                    : String(data.vehicle_id)
                            }
                            onChange={(e) =>
                                setData(
                                    'vehicle_id',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            required
                        >
                            <option value="">Select</option>
                            {vehicles.map((v) => (
                                <option key={v.id} value={v.id}>
                                    {v.registration}
                                </option>
                            ))}
                        </select>
                        {errors.vehicle_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.vehicle_id}
                            </p>
                        )}
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <Label htmlFor="work_type">Work type *</Label>
                            <select
                                id="work_type"
                                value={data.work_type}
                                onChange={(e) =>
                                    setData('work_type', e.target.value)
                                }
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {workTypes.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <Label htmlFor="priority">Priority *</Label>
                            <select
                                id="priority"
                                value={data.priority}
                                onChange={(e) =>
                                    setData('priority', e.target.value)
                                }
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {priorities.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="status">Status</Label>
                        <select
                            id="status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {statuses.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Create work order
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/work-orders">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
