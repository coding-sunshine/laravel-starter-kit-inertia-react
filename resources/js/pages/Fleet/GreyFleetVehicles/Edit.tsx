import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface GreyFleetVehicle {
    id: number;
    user_id?: number;
    driver_id?: number;
    registration?: string;
    make?: string;
    model?: string;
    year?: number;
    colour?: string;
    fuel_type?: string;
    engine_cc?: number;
    is_approved?: boolean;
    approval_date?: string;
    notes?: string;
    is_active?: boolean;
}
interface Props {
    greyFleetVehicle: GreyFleetVehicle;
    users: { id: number; name: string }[];
    drivers: { id: number; name: string }[];
}

export default function FleetGreyFleetVehiclesEdit({ greyFleetVehicle, users, drivers }: Props) {
    const form = useForm({
        user_id: greyFleetVehicle.user_id ?? ('' as number | ''),
        driver_id: greyFleetVehicle.driver_id ?? ('' as number | ''),
        registration: greyFleetVehicle.registration ?? '',
        make: greyFleetVehicle.make ?? '',
        model: greyFleetVehicle.model ?? '',
        year: greyFleetVehicle.year ?? ('' as number | ''),
        colour: greyFleetVehicle.colour ?? '',
        fuel_type: greyFleetVehicle.fuel_type ?? '',
        engine_cc: greyFleetVehicle.engine_cc ?? ('' as number | ''),
        is_approved: greyFleetVehicle.is_approved ?? false,
        approval_date: greyFleetVehicle.approval_date?.slice(0, 10) ?? '',
        notes: greyFleetVehicle.notes ?? '',
        is_active: greyFleetVehicle.is_active ?? true,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Grey fleet vehicles', href: '/fleet/grey-fleet-vehicles' },
        { title: 'Edit', href: `/fleet/grey-fleet-vehicles/${greyFleetVehicle.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit grey fleet vehicle" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href={`/fleet/grey-fleet-vehicles/${greyFleetVehicle.id}`}>Back</Link></Button>
                    <h1 className="text-2xl font-semibold">Edit grey fleet vehicle</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.put(`/fleet/grey-fleet-vehicles/${greyFleetVehicle.id}`); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Owner (user)</Label>
                            <select value={form.data.user_id} onChange={e => form.setData('user_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                <option value="">—</option>
                                {users.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Driver</Label>
                            <select value={form.data.driver_id} onChange={e => form.setData('driver_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                <option value="">—</option>
                                {drivers.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                            </select>
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Registration</Label>
                        <Input value={form.data.registration} onChange={e => form.setData('registration', e.target.value)} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Make</Label>
                            <Input value={form.data.make} onChange={e => form.setData('make', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Model</Label>
                            <Input value={form.data.model} onChange={e => form.setData('model', e.target.value)} />
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Year</Label>
                            <Input type="number" min={1900} max={2100} value={form.data.year === '' ? '' : form.data.year} onChange={e => form.setData('year', e.target.value === '' ? '' : Number(e.target.value))} />
                        </div>
                        <div className="space-y-2">
                            <Label>Colour</Label>
                            <Input value={form.data.colour} onChange={e => form.setData('colour', e.target.value)} />
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Fuel type</Label>
                            <Input value={form.data.fuel_type} onChange={e => form.setData('fuel_type', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Engine (cc)</Label>
                            <Input type="number" min={0} value={form.data.engine_cc === '' ? '' : form.data.engine_cc} onChange={e => form.setData('engine_cc', e.target.value === '' ? '' : Number(e.target.value))} />
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <input type="checkbox" id="is_approved" checked={form.data.is_approved} onChange={e => form.setData('is_approved', e.target.checked)} />
                        <Label htmlFor="is_approved">Approved</Label>
                    </div>
                    <div className="space-y-2">
                        <Label>Approval date</Label>
                        <Input type="date" value={form.data.approval_date} onChange={e => form.setData('approval_date', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label>Notes</Label>
                        <textarea value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} className="min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm" />
                    </div>
                    <div className="flex items-center gap-2">
                        <input type="checkbox" id="is_active" checked={form.data.is_active} onChange={e => form.setData('is_active', e.target.checked)} />
                        <Label htmlFor="is_active">Active</Label>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Update</Button>
                        <Button type="button" variant="outline" asChild><Link href={`/fleet/grey-fleet-vehicles/${greyFleetVehicle.id}`}>Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
