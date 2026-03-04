import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Check {
    id: number;
    vehicle_id: number;
    vehicle_check_template_id: number;
    performed_by_driver_id?: number;
    performed_by_user_id?: number;
    defect_id?: number;
    check_date: string;
    status: string;
}
interface Props {
    vehicleCheck: Check;
    vehicles: { id: number; name: string }[];
    vehicleCheckTemplates: { id: number; name: string }[];
    drivers: { id: number; name: string }[];
    users: { id: number; name: string }[];
    defects: { id: number; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function VehicleChecksEdit({
    vehicleCheck,
    vehicles,
    vehicleCheckTemplates,
    drivers,
    users,
    defects,
    statuses,
}: Props) {
    const form = useForm({
        vehicle_id: vehicleCheck.vehicle_id,
        vehicle_check_template_id: vehicleCheck.vehicle_check_template_id,
        performed_by_driver_id: (vehicleCheck.performed_by_driver_id ?? '') as
            | number
            | '',
        performed_by_user_id: (vehicleCheck.performed_by_user_id ?? '') as
            | number
            | '',
        defect_id: (vehicleCheck.defect_id ?? '') as number | '',
        check_date: vehicleCheck.check_date,
        status: vehicleCheck.status,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicle checks', href: '/fleet/vehicle-checks' },
        {
            title: 'Edit',
            href: `/fleet/vehicle-checks/${vehicleCheck.id}/edit`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit vehicle check" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/fleet/vehicle-checks/${vehicleCheck.id}`}>
                            Back
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        Edit vehicle check
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put(`/fleet/vehicle-checks/${vehicleCheck.id}`);
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="space-y-2">
                        <Label>Vehicle *</Label>
                        <select
                            required
                            value={form.data.vehicle_id}
                            onChange={(e) =>
                                form.setData(
                                    'vehicle_id',
                                    Number(e.target.value),
                                )
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {vehicles.map((v) => (
                                <option key={v.id} value={v.id}>
                                    {v.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Template *</Label>
                        <select
                            required
                            value={form.data.vehicle_check_template_id}
                            onChange={(e) =>
                                form.setData(
                                    'vehicle_check_template_id',
                                    Number(e.target.value),
                                )
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {vehicleCheckTemplates.map((t) => (
                                <option key={t.id} value={t.id}>
                                    {t.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Check date *</Label>
                        <Input
                            type="date"
                            value={form.data.check_date}
                            onChange={(e) =>
                                form.setData('check_date', e.target.value)
                            }
                            required
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Performed by (driver)</Label>
                            <select
                                value={form.data.performed_by_driver_id || ''}
                                onChange={(e) =>
                                    form.setData(
                                        'performed_by_driver_id',
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
                            <Label>Performed by (user)</Label>
                            <select
                                value={form.data.performed_by_user_id || ''}
                                onChange={(e) =>
                                    form.setData(
                                        'performed_by_user_id',
                                        e.target.value
                                            ? Number(e.target.value)
                                            : '',
                                    )
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                <option value="">—</option>
                                {users.map((u) => (
                                    <option key={u.id} value={u.id}>
                                        {u.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Defect</Label>
                        <select
                            value={form.data.defect_id || ''}
                            onChange={(e) =>
                                form.setData(
                                    'defect_id',
                                    e.target.value
                                        ? Number(e.target.value)
                                        : '',
                                )
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">—</option>
                            {defects.map((d) => (
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
                            <Link
                                href={`/fleet/vehicle-checks/${vehicleCheck.id}`}
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
