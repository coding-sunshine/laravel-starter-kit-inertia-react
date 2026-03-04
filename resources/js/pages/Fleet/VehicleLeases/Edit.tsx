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
interface LeaseRecord {
    id: number;
    vehicle_id: number;
    contract_id?: string | null;
    lessor_name: string;
    start_date: string;
    end_date: string;
    monthly_payment?: string | number | null;
    p11d_list_price?: string | number | null;
    status: string;
}
interface Props {
    vehicleLease: LeaseRecord;
    vehicles: { id: number; registration: string }[];
    statuses: Option[];
}

export default function FleetVehicleLeasesEdit({
    vehicleLease,
    vehicles,
    statuses,
}: Props) {
    const form = useForm({
        vehicle_id: vehicleLease.vehicle_id,
        contract_id: vehicleLease.contract_id ?? '',
        lessor_name: vehicleLease.lessor_name,
        start_date: vehicleLease.start_date.slice(0, 10),
        end_date: vehicleLease.end_date.slice(0, 10),
        monthly_payment:
            vehicleLease.monthly_payment != null
                ? String(vehicleLease.monthly_payment)
                : '',
        p11d_list_price:
            vehicleLease.p11d_list_price != null
                ? String(vehicleLease.p11d_list_price)
                : '',
        status: vehicleLease.status,
    });
    const { data, setData, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicle leases', href: '/fleet/vehicle-leases' },
        {
            title: vehicleLease.lessor_name,
            href: `/fleet/vehicle-leases/${vehicleLease.id}`,
        },
        {
            title: 'Edit',
            href: `/fleet/vehicle-leases/${vehicleLease.id}/edit`,
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            monthly_payment:
                d.monthly_payment === '' ? null : d.monthly_payment,
            p11d_list_price:
                d.p11d_list_price === '' ? null : d.p11d_list_price,
            _method: 'PUT',
        }));
        form.post(`/fleet/vehicle-leases/${vehicleLease.id}`, {
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${vehicleLease.lessor_name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit vehicle lease</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label>Vehicle *</Label>
                        <select
                            value={data.vehicle_id}
                            onChange={(e) =>
                                setData('vehicle_id', Number(e.target.value))
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            required
                        >
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
                        <Label>Lessor name *</Label>
                        <Input
                            value={data.lessor_name}
                            onChange={(e) =>
                                setData('lessor_name', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.lessor_name && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.lessor_name}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label>Contract ID</Label>
                        <Input
                            value={data.contract_id}
                            onChange={(e) =>
                                setData('contract_id', e.target.value)
                            }
                            className="mt-1"
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Start date *</Label>
                            <Input
                                type="date"
                                value={data.start_date}
                                onChange={(e) =>
                                    setData('start_date', e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.start_date && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.start_date}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label>End date *</Label>
                            <Input
                                type="date"
                                value={data.end_date}
                                onChange={(e) =>
                                    setData('end_date', e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.end_date && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.end_date}
                                </p>
                            )}
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
                            Update
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link
                                href={`/fleet/vehicle-leases/${vehicleLease.id}`}
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
