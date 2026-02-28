import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    vehicles: { id: number; registration: string }[];
    users: { id: number; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetPoolVehicleBookingsCreate({ vehicles, users, statuses }: Props) {
    const form = useForm({
        vehicle_id: '' as number | '',
        user_id: '' as number | '',
        booking_start: '',
        booking_end: '',
        status: 'confirmed',
        purpose: '',
        destination: '',
        odometer_start: '' as number | '',
        odometer_end: '' as number | '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Pool vehicle bookings', href: '/fleet/pool-vehicle-bookings' },
        { title: 'New', href: '/fleet/pool-vehicle-bookings/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New pool vehicle booking" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href="/fleet/pool-vehicle-bookings">Back</Link></Button>
                    <h1 className="text-2xl font-semibold">New pool vehicle booking</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.post('/fleet/pool-vehicle-bookings'); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Vehicle</Label>
                        <select required value={form.data.vehicle_id} onChange={e => form.setData('vehicle_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {vehicles.map((v) => <option key={v.id} value={v.id}>{v.registration}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>User</Label>
                        <select required value={form.data.user_id} onChange={e => form.setData('user_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {users.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                        </select>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Booking start</Label>
                            <Input type="datetime-local" required value={form.data.booking_start} onChange={e => form.setData('booking_start', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Booking end</Label>
                            <Input type="datetime-local" required value={form.data.booking_end} onChange={e => form.setData('booking_end', e.target.value)} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Status</Label>
                        <select value={form.data.status} onChange={e => form.setData('status', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Purpose</Label>
                        <Input value={form.data.purpose} onChange={e => form.setData('purpose', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label>Destination</Label>
                        <Input value={form.data.destination} onChange={e => form.setData('destination', e.target.value)} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Odometer start</Label>
                            <Input type="number" min={0} value={form.data.odometer_start === '' ? '' : form.data.odometer_start} onChange={e => form.setData('odometer_start', e.target.value === '' ? '' : Number(e.target.value))} />
                        </div>
                        <div className="space-y-2">
                            <Label>Odometer end</Label>
                            <Input type="number" min={0} value={form.data.odometer_end === '' ? '' : form.data.odometer_end} onChange={e => form.setData('odometer_end', e.target.value === '' ? '' : Number(e.target.value))} />
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Save</Button>
                        <Button type="button" variant="outline" asChild><Link href="/fleet/pool-vehicle-bookings">Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
