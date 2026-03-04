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
interface ScheduleRecord {
    id: number;
    vehicle_id: number;
    service_type: string;
    interval_type: string;
    interval_value: number;
    interval_unit: string;
    is_active: boolean;
}
interface Props {
    serviceSchedule: ScheduleRecord;
    serviceTypes: Option[];
    intervalTypes: Option[];
    intervalUnits: Option[];
    vehicles: { id: number; registration: string }[];
    garages: { id: number; name: string }[];
}

export default function FleetServiceSchedulesEdit({
    serviceSchedule,
    serviceTypes,
    intervalTypes,
    intervalUnits,
    vehicles,
    garages: _garages,
}: Props) {
    const form = useForm({
        vehicle_id: serviceSchedule.vehicle_id,
        service_type: serviceSchedule.service_type,
        interval_type: serviceSchedule.interval_type,
        interval_value: String(serviceSchedule.interval_value),
        interval_unit: serviceSchedule.interval_unit,
        is_active: serviceSchedule.is_active,
    });
    const { data, setData, processing, errors: _errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/service-schedules' },
        { title: 'Service schedules', href: '/fleet/service-schedules' },
        {
            title: `Schedule #${serviceSchedule.id}`,
            href: `/fleet/service-schedules/${serviceSchedule.id}`,
        },
        {
            title: 'Edit',
            href: `/fleet/service-schedules/${serviceSchedule.id}/edit`,
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            interval_value: Number(d.interval_value),
        }));
        form.put(`/fleet/service-schedules/${serviceSchedule.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit schedule #${serviceSchedule.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">
                    Edit service schedule
                </h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
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
                        <Label>Service type</Label>
                        <select
                            value={data.service_type}
                            onChange={(e) =>
                                setData('service_type', e.target.value)
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
                        >
                            {serviceTypes.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Interval type</Label>
                            <select
                                value={data.interval_type}
                                onChange={(e) =>
                                    setData('interval_type', e.target.value)
                                }
                                className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
                            >
                                {intervalTypes.map((i) => (
                                    <option key={i.value} value={i.value}>
                                        {i.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <Label>Interval value</Label>
                            <Input
                                type="number"
                                min={1}
                                value={data.interval_value}
                                onChange={(e) =>
                                    setData('interval_value', e.target.value)
                                }
                                className="mt-1"
                            />
                        </div>
                    </div>
                    <div>
                        <Label>Interval unit</Label>
                        <select
                            value={data.interval_unit}
                            onChange={(e) =>
                                setData('interval_unit', e.target.value)
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
                        >
                            {intervalUnits.map((u) => (
                                <option key={u.value} value={u.value}>
                                    {u.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) =>
                                setData('is_active', e.target.checked)
                            }
                        />
                        <Label>Active</Label>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Update
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={`/fleet/service-schedules/${serviceSchedule.id}`}
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
