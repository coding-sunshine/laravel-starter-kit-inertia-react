import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Props {
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; name: string }[];
    incidents: { id: number; incident_number: string }[];
    eventTypes: { value: string; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetDashcamClipsCreate({
    vehicles,
    drivers,
    incidents: _incidents,
    eventTypes,
    statuses,
}: Props) {
    const form = useForm({
        vehicle_id: '' as number | '',
        driver_id: '' as number | '',
        incident_id: '' as number | '',
        event_type: 'other',
        status: 'available',
        recorded_at: new Date().toISOString().slice(0, 16),
        clip_url: '',
        thumbnail_url: '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Dashcam clips', href: '/fleet/dashcam-clips' },
        { title: 'New', href: '/fleet/dashcam-clips/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New dashcam clip" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet/dashcam-clips">Back</Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">New dashcam clip</h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/fleet/dashcam-clips');
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
                        <Label>Event type</Label>
                        <select
                            value={form.data.event_type}
                            onChange={(e) =>
                                form.setData('event_type', e.target.value)
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {eventTypes.map((t) => (
                                <option key={t.value} value={t.value}>
                                    {t.name}
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
                        <Label>Driver</Label>
                        <select
                            value={form.data.driver_id}
                            onChange={(e) =>
                                form.setData(
                                    'driver_id',
                                    e.target.value
                                        ? Number(e.target.value)
                                        : '',
                                )
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">—</option>
                            {drivers.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Status</Label>
                        <select
                            value={form.data.status}
                            onChange={(e) =>
                                form.setData('status', e.target.value)
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
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
                            <Link href="/fleet/dashcam-clips">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
