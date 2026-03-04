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
    vehicles: { id: number; registration: string }[];
    statuses: Option[];
}

export default function FleetVehicleRecallsCreate({
    vehicles,
    statuses,
}: Props) {
    const form = useForm({
        vehicle_id: '' as number | '',
        recall_reference: '',
        make: '',
        model: '',
        title: '',
        description: '',
        issued_date: '' as string,
        due_date: '' as string,
        status: 'pending',
        completed_at: '' as string,
        completion_notes: '',
    });
    const { data, setData, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicle recalls', href: '/fleet/vehicle-recalls' },
        { title: 'Create', href: '/fleet/vehicle-recalls/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            vehicle_id: d.vehicle_id === '' ? null : d.vehicle_id,
            issued_date: d.issued_date || null,
            due_date: d.due_date || null,
            completed_at: d.completed_at || null,
        }));
        form.post('/fleet/vehicle-recalls');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New vehicle recall" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New vehicle recall</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label>Recall reference *</Label>
                        <Input
                            value={data.recall_reference}
                            onChange={(e) =>
                                setData('recall_reference', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.recall_reference && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.recall_reference}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label>Vehicle</Label>
                        <select
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
                        >
                            <option value="">None</option>
                            {vehicles.map((v) => (
                                <option key={v.id} value={v.id}>
                                    {v.registration}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label>Title</Label>
                        <Input
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="mt-1"
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Make</Label>
                            <Input
                                value={data.make}
                                onChange={(e) =>
                                    setData('make', e.target.value)
                                }
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <Label>Model</Label>
                            <Input
                                value={data.model}
                                onChange={(e) =>
                                    setData('model', e.target.value)
                                }
                                className="mt-1"
                            />
                        </div>
                    </div>
                    <div>
                        <Label>Description</Label>
                        <textarea
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            className="mt-1 flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Issued date</Label>
                            <Input
                                type="date"
                                value={data.issued_date}
                                onChange={(e) =>
                                    setData('issued_date', e.target.value)
                                }
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <Label>Due date</Label>
                            <Input
                                type="date"
                                value={data.due_date}
                                onChange={(e) =>
                                    setData('due_date', e.target.value)
                                }
                                className="mt-1"
                            />
                        </div>
                    </div>
                    <div>
                        <Label>Status *</Label>
                        <select
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {statuses.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/fleet/vehicle-recalls">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
