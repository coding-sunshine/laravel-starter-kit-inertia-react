import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface MileageClaim {
    id: number;
    grey_fleet_vehicle_id: number;
    user_id: number;
    claim_date: string;
    start_odometer?: number;
    end_odometer?: number;
    distance_km?: number;
    purpose?: string;
    destination?: string;
    amount_claimed?: string;
    amount_approved?: string;
    status?: string;
    rejection_reason?: string;
}
interface Props {
    mileageClaim: MileageClaim;
    greyFleetVehicles: { id: number; label: string }[];
    users: { id: number; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetMileageClaimsEdit({
    mileageClaim,
    greyFleetVehicles,
    users,
    statuses,
}: Props) {
    const form = useForm({
        grey_fleet_vehicle_id: mileageClaim.grey_fleet_vehicle_id,
        user_id: mileageClaim.user_id,
        claim_date: mileageClaim.claim_date?.slice(0, 10) ?? '',
        start_odometer: mileageClaim.start_odometer ?? ('' as number | ''),
        end_odometer: mileageClaim.end_odometer ?? ('' as number | ''),
        distance_km: mileageClaim.distance_km ?? ('' as number | ''),
        purpose: mileageClaim.purpose ?? '',
        destination: mileageClaim.destination ?? '',
        amount_claimed: mileageClaim.amount_claimed ?? '',
        amount_approved: mileageClaim.amount_approved ?? '',
        status: mileageClaim.status ?? 'pending',
        rejection_reason: mileageClaim.rejection_reason ?? '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Mileage claims', href: '/fleet/mileage-claims' },
        {
            title: 'Edit',
            href: `/fleet/mileage-claims/${mileageClaim.id}/edit`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit mileage claim" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/fleet/mileage-claims/${mileageClaim.id}`}>
                            Back
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        Edit mileage claim
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put(`/fleet/mileage-claims/${mileageClaim.id}`);
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="space-y-2">
                        <Label>Grey fleet vehicle</Label>
                        <select
                            required
                            value={form.data.grey_fleet_vehicle_id}
                            onChange={(e) =>
                                form.setData(
                                    'grey_fleet_vehicle_id',
                                    Number(e.target.value),
                                )
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {greyFleetVehicles.map((v) => (
                                <option key={v.id} value={v.id}>
                                    {v.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>User (claimant)</Label>
                        <select
                            required
                            value={form.data.user_id}
                            onChange={(e) =>
                                form.setData('user_id', Number(e.target.value))
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {users.map((u) => (
                                <option key={u.id} value={u.id}>
                                    {u.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Claim date</Label>
                        <Input
                            type="date"
                            required
                            value={form.data.claim_date}
                            onChange={(e) =>
                                form.setData('claim_date', e.target.value)
                            }
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Start odometer</Label>
                            <Input
                                type="number"
                                min={0}
                                value={
                                    form.data.start_odometer === ''
                                        ? ''
                                        : form.data.start_odometer
                                }
                                onChange={(e) =>
                                    form.setData(
                                        'start_odometer',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>End odometer</Label>
                            <Input
                                type="number"
                                min={0}
                                value={
                                    form.data.end_odometer === ''
                                        ? ''
                                        : form.data.end_odometer
                                }
                                onChange={(e) =>
                                    form.setData(
                                        'end_odometer',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Distance (km)</Label>
                        <Input
                            type="number"
                            min={0}
                            value={
                                form.data.distance_km === ''
                                    ? ''
                                    : form.data.distance_km
                            }
                            onChange={(e) =>
                                form.setData(
                                    'distance_km',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Purpose</Label>
                        <Input
                            value={form.data.purpose}
                            onChange={(e) =>
                                form.setData('purpose', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Destination</Label>
                        <Input
                            value={form.data.destination}
                            onChange={(e) =>
                                form.setData('destination', e.target.value)
                            }
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Amount claimed</Label>
                            <Input
                                type="number"
                                step="any"
                                min={0}
                                value={form.data.amount_claimed}
                                onChange={(e) =>
                                    form.setData(
                                        'amount_claimed',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Amount approved</Label>
                            <Input
                                type="number"
                                step="any"
                                min={0}
                                value={form.data.amount_approved}
                                onChange={(e) =>
                                    form.setData(
                                        'amount_approved',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
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
                    <div className="space-y-2">
                        <Label>Rejection reason</Label>
                        <Input
                            value={form.data.rejection_reason}
                            onChange={(e) =>
                                form.setData('rejection_reason', e.target.value)
                            }
                        />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Update
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link
                                href={`/fleet/mileage-claims/${mileageClaim.id}`}
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
