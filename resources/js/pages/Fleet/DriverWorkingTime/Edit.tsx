import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface RecordType {
    id: number;
    driver_id: number;
    date: string;
    driving_time_minutes: number;
    total_duty_time_minutes: number;
    wtd_compliant: boolean;
}
interface Props {
    driverWorkingTime: RecordType;
    drivers: { id: number; first_name: string; last_name: string }[];
}

export default function FleetDriverWorkingTimeEdit({
    driverWorkingTime,
    drivers,
}: Props) {
    const form = useForm({
        driver_id: driverWorkingTime.driver_id,
        date: driverWorkingTime.date.slice(0, 10),
        driving_time_minutes: driverWorkingTime.driving_time_minutes,
        total_duty_time_minutes: driverWorkingTime.total_duty_time_minutes,
        wtd_compliant: driverWorkingTime.wtd_compliant,
    });
    const { data, setData, processing } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/driver-working-time' },
        { title: 'Driver working time', href: '/fleet/driver-working-time' },
        {
            title: `Record #${driverWorkingTime.id}`,
            href: `/fleet/driver-working-time/${driverWorkingTime.id}`,
        },
        {
            title: 'Edit',
            href: `/fleet/driver-working-time/${driverWorkingTime.id}/edit`,
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            driving_time_minutes: Number(d.driving_time_minutes),
            total_duty_time_minutes: Number(d.total_duty_time_minutes),
        }));
        form.put(`/fleet/driver-working-time/${driverWorkingTime.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Fleet – Edit driver working time #${driverWorkingTime.id}`}
            />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">
                    Edit driver working time
                </h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label>Driver *</Label>
                        <select
                            value={data.driver_id}
                            onChange={(e) =>
                                setData('driver_id', Number(e.target.value))
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
                        >
                            {drivers.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.last_name}, {d.first_name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label>Date *</Label>
                        <Input
                            type="date"
                            value={data.date}
                            onChange={(e) => setData('date', e.target.value)}
                            className="mt-1"
                        />
                    </div>
                    <div>
                        <Label>Driving (minutes)</Label>
                        <Input
                            type="number"
                            min={0}
                            value={data.driving_time_minutes}
                            onChange={(e) =>
                                setData('driving_time_minutes', e.target.value)
                            }
                            className="mt-1"
                        />
                    </div>
                    <div>
                        <Label>Total duty (minutes)</Label>
                        <Input
                            type="number"
                            min={0}
                            value={data.total_duty_time_minutes}
                            onChange={(e) =>
                                setData(
                                    'total_duty_time_minutes',
                                    e.target.value,
                                )
                            }
                            className="mt-1"
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            checked={data.wtd_compliant}
                            onChange={(e) =>
                                setData('wtd_compliant', e.target.checked)
                            }
                        />
                        <Label>WTD compliant</Label>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Update
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={`/fleet/driver-working-time/${driverWorkingTime.id}`}
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
