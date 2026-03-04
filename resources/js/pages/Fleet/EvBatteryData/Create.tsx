import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Props {
    vehicles: { id: number; registration: string }[];
    chargingStatuses: { value: string; name: string }[];
}

export default function FleetEvBatteryDataCreate({
    vehicles,
    chargingStatuses,
}: Props) {
    const form = useForm({
        vehicle_id: '' as number | '',
        recorded_at: new Date().toISOString().slice(0, 16),
        soc_percent: '100',
        charging_status: 'not_charging',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'EV battery data', href: '/fleet/ev-battery-data' },
        { title: 'New', href: '/fleet/ev-battery-data/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New EV battery data" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet/ev-battery-data">Back</Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        New EV battery data
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/fleet/ev-battery-data');
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="space-y-2">
                        <Label>Vehicle</Label>
                        <select
                            required
                            value={form.data.vehicle_id}
                            onChange={(e) =>
                                form.setData(
                                    'vehicle_id',
                                    e.target.value
                                        ? Number(e.target.value)
                                        : '',
                                )
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">—</option>
                            {vehicles.map((v) => (
                                <option key={v.id} value={v.id}>
                                    {v.registration}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Recorded at</Label>
                        <Input
                            type="datetime-local"
                            value={form.data.recorded_at}
                            onChange={(e) =>
                                form.setData('recorded_at', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>SOC %</Label>
                        <Input
                            type="number"
                            min={0}
                            max={100}
                            value={form.data.soc_percent}
                            onChange={(e) =>
                                form.setData('soc_percent', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Charging status</Label>
                        <select
                            value={form.data.charging_status}
                            onChange={(e) =>
                                form.setData('charging_status', e.target.value)
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {chargingStatuses.map((s) => (
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
                            <Link href="/fleet/ev-battery-data">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
