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
interface RecallRecord {
    id: number;
    vehicle_id?: number | null;
    recall_reference: string;
    make?: string | null;
    model?: string | null;
    title?: string | null;
    description?: string | null;
    issued_date?: string | null;
    due_date?: string | null;
    status: string;
    completed_at?: string | null;
    completion_notes?: string | null;
}
interface Props {
    vehicleRecall: RecallRecord;
    vehicles: { id: number; registration: string }[];
    statuses: Option[];
}

export default function FleetVehicleRecallsEdit({
    vehicleRecall,
    vehicles,
    statuses,
}: Props) {
    const form = useForm({
        vehicle_id: (vehicleRecall.vehicle_id ?? '') as number | '',
        recall_reference: vehicleRecall.recall_reference,
        make: vehicleRecall.make ?? '',
        model: vehicleRecall.model ?? '',
        title: vehicleRecall.title ?? '',
        description: vehicleRecall.description ?? '',
        issued_date: vehicleRecall.issued_date?.slice(0, 10) ?? '',
        due_date: vehicleRecall.due_date?.slice(0, 10) ?? '',
        status: vehicleRecall.status,
        completed_at: vehicleRecall.completed_at?.slice(0, 10) ?? '',
        completion_notes: vehicleRecall.completion_notes ?? '',
    });
    const { data, setData, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicle recalls', href: '/fleet/vehicle-recalls' },
        {
            title: vehicleRecall.recall_reference,
            href: `/fleet/vehicle-recalls/${vehicleRecall.id}`,
        },
        {
            title: 'Edit',
            href: `/fleet/vehicle-recalls/${vehicleRecall.id}/edit`,
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            vehicle_id: d.vehicle_id === '' ? null : d.vehicle_id,
            issued_date: d.issued_date || null,
            due_date: d.due_date || null,
            completed_at: d.completed_at || null,
            _method: 'PUT',
        }));
        form.post(`/fleet/vehicle-recalls/${vehicleRecall.id}`, {
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${vehicleRecall.recall_reference}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit vehicle recall</h1>
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
                            Update
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link
                                href={`/fleet/vehicle-recalls/${vehicleRecall.id}`}
                            >
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
