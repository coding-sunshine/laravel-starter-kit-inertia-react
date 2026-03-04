import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface ParkingAllocation {
    id: number;
    vehicle_id: number;
    location_id: number;
    allocated_from: string;
    allocated_to?: string;
    spot_identifier?: string;
    cost?: number;
}
interface Props {
    parkingAllocation: ParkingAllocation;
    vehicles: { id: number; registration: string }[];
    locations: { id: number; name: string }[];
}

export default function FleetParkingAllocationsEdit({
    parkingAllocation,
    vehicles,
    locations,
}: Props) {
    const form = useForm({
        vehicle_id: parkingAllocation.vehicle_id,
        location_id: parkingAllocation.location_id,
        allocated_from: parkingAllocation.allocated_from?.slice(0, 16) ?? '',
        allocated_to: parkingAllocation.allocated_to?.slice(0, 16) ?? '',
        spot_identifier: parkingAllocation.spot_identifier ?? '',
        cost: parkingAllocation.cost ?? ('' as number | ''),
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Parking allocations', href: '/fleet/parking-allocations' },
        {
            title: 'Edit',
            href: `/fleet/parking-allocations/${parkingAllocation.id}/edit`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit parking allocation" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={`/fleet/parking-allocations/${parkingAllocation.id}`}
                        >
                            Back
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        Edit parking allocation
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put(
                            `/fleet/parking-allocations/${parkingAllocation.id}`,
                        );
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
                                    Number(e.target.value),
                                )
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {vehicles.map((v) => (
                                <option key={v.id} value={v.id}>
                                    {v.registration}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Location</Label>
                        <select
                            required
                            value={form.data.location_id}
                            onChange={(e) =>
                                form.setData(
                                    'location_id',
                                    Number(e.target.value),
                                )
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {locations.map((l) => (
                                <option key={l.id} value={l.id}>
                                    {l.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Allocated from</Label>
                            <Input
                                type="datetime-local"
                                required
                                value={form.data.allocated_from}
                                onChange={(e) =>
                                    form.setData(
                                        'allocated_from',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Allocated to</Label>
                            <Input
                                type="datetime-local"
                                value={form.data.allocated_to}
                                onChange={(e) =>
                                    form.setData('allocated_to', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Spot identifier</Label>
                        <Input
                            value={form.data.spot_identifier}
                            onChange={(e) =>
                                form.setData('spot_identifier', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Cost</Label>
                        <Input
                            type="number"
                            step="0.01"
                            min={0}
                            value={form.data.cost === '' ? '' : form.data.cost}
                            onChange={(e) =>
                                form.setData(
                                    'cost',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                        />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Update
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link
                                href={`/fleet/parking-allocations/${parkingAllocation.id}`}
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
