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
interface DeviceRecord {
    id: number;
    device_id: string;
    provider: string;
    status: string;
    vehicle_id?: number | null;
    installed_at: string | null;
    is_active: boolean;
}
interface Props {
    telematicsDevice: DeviceRecord;
    statuses: Option[];
    vehicles: { id: number; registration: string }[];
}

export default function FleetTelematicsDevicesEdit({
    telematicsDevice,
    statuses,
    vehicles,
}: Props) {
    const installedAt = telematicsDevice.installed_at
        ? new Date(telematicsDevice.installed_at).toISOString().slice(0, 16)
        : '';
    const form = useForm({
        vehicle_id: (telematicsDevice.vehicle_id ?? '') as number | '',
        device_id: telematicsDevice.device_id,
        provider: telematicsDevice.provider,
        status: telematicsDevice.status,
        installed_at: installedAt,
        is_active: telematicsDevice.is_active,
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/telematics-devices' },
        { title: 'Telematics devices', href: '/fleet/telematics-devices' },
        {
            title: telematicsDevice.device_id,
            href: `/fleet/telematics-devices/${telematicsDevice.id}`,
        },
        {
            title: 'Edit',
            href: `/fleet/telematics-devices/${telematicsDevice.id}/edit`,
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            vehicle_id: d.vehicle_id === '' ? null : d.vehicle_id,
            installed_at: d.installed_at || null,
        }));
        form.put(`/fleet/telematics-devices/${telematicsDevice.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${telematicsDevice.device_id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">
                    Edit telematics device
                </h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="device_id">Device ID *</Label>
                        <Input
                            id="device_id"
                            value={data.device_id}
                            onChange={(e) =>
                                setData('device_id', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.device_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.device_id}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="provider">Provider *</Label>
                        <Input
                            id="provider"
                            value={data.provider}
                            onChange={(e) =>
                                setData('provider', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.provider && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.provider}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="status">Status *</Label>
                        <select
                            id="status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                        >
                            {statuses.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.name}
                                </option>
                            ))}
                        </select>
                        {errors.status && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.status}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="vehicle_id">Vehicle</Label>
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
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                        >
                            <option value="">None</option>
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
                    <div>
                        <Label htmlFor="installed_at">Installed at</Label>
                        <Input
                            id="installed_at"
                            type="datetime-local"
                            value={data.installed_at}
                            onChange={(e) =>
                                setData('installed_at', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.installed_at && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.installed_at}
                            </p>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="is_active"
                            checked={data.is_active}
                            onChange={(e) =>
                                setData('is_active', e.target.checked)
                            }
                            className="h-4 w-4 rounded border-input"
                        />
                        <Label htmlFor="is_active">Active</Label>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Update device
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={`/fleet/telematics-devices/${telematicsDevice.id}`}
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
