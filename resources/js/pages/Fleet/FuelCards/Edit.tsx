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
interface FuelCardRecord {
    id: number;
    card_number: string;
    provider: string;
    card_type: string;
    status: string;
    issue_date: string | null;
    expiry_date: string | null;
    pin_required: boolean;
    assigned_vehicle_id?: number | null;
    assigned_driver_id?: number | null;
}
interface Props {
    fuelCard: FuelCardRecord;
    cardTypes: Option[];
    statuses: Option[];
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; first_name: string; last_name: string }[];
}

export default function FleetFuelCardsEdit({
    fuelCard,
    cardTypes,
    statuses,
    vehicles,
    drivers,
}: Props) {
    const form = useForm({
        card_number: fuelCard.card_number,
        provider: fuelCard.provider,
        card_type: fuelCard.card_type,
        status: fuelCard.status,
        issue_date: fuelCard.issue_date
            ? new Date(fuelCard.issue_date).toISOString().slice(0, 10)
            : '',
        expiry_date: fuelCard.expiry_date
            ? new Date(fuelCard.expiry_date).toISOString().slice(0, 10)
            : '',
        pin_required: fuelCard.pin_required,
        assigned_vehicle_id: (fuelCard.assigned_vehicle_id ?? '') as
            | number
            | '',
        assigned_driver_id: (fuelCard.assigned_driver_id ?? '') as number | '',
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/fuel-cards' },
        { title: 'Fuel cards', href: '/fleet/fuel-cards' },
        {
            title: fuelCard.card_number,
            href: `/fleet/fuel-cards/${fuelCard.id}`,
        },
        { title: 'Edit', href: `/fleet/fuel-cards/${fuelCard.id}/edit` },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            issue_date: d.issue_date || null,
            expiry_date: d.expiry_date || null,
            assigned_vehicle_id:
                d.assigned_vehicle_id === '' ? null : d.assigned_vehicle_id,
            assigned_driver_id:
                d.assigned_driver_id === '' ? null : d.assigned_driver_id,
        }));
        form.put(`/fleet/fuel-cards/${fuelCard.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${fuelCard.card_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit fuel card</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="card_number">Card number *</Label>
                        <Input
                            id="card_number"
                            value={data.card_number}
                            onChange={(e) =>
                                setData('card_number', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.card_number && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.card_number}
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
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="card_type">Card type</Label>
                            <select
                                id="card_type"
                                value={data.card_type}
                                onChange={(e) =>
                                    setData('card_type', e.target.value)
                                }
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {cardTypes.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <Label htmlFor="status">Status</Label>
                            <select
                                id="status"
                                value={data.status}
                                onChange={(e) =>
                                    setData('status', e.target.value)
                                }
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {statuses.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="assigned_vehicle_id">Vehicle</Label>
                        <select
                            id="assigned_vehicle_id"
                            value={
                                data.assigned_vehicle_id === ''
                                    ? ''
                                    : String(data.assigned_vehicle_id)
                            }
                            onChange={(e) =>
                                setData(
                                    'assigned_vehicle_id',
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
                        <Label htmlFor="assigned_driver_id">Driver</Label>
                        <select
                            id="assigned_driver_id"
                            value={
                                data.assigned_driver_id === ''
                                    ? ''
                                    : String(data.assigned_driver_id)
                            }
                            onChange={(e) =>
                                setData(
                                    'assigned_driver_id',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">None</option>
                            {drivers.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.last_name}, {d.first_name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Update
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/fleet/fuel-cards/${fuelCard.id}`}>
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
