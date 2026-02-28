import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    greyFleetVehicles: { id: number; label: string }[];
    users: { id: number; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetMileageClaimsCreate({ greyFleetVehicles, users, statuses }: Props) {
    const form = useForm({
        grey_fleet_vehicle_id: '' as number | '',
        user_id: '' as number | '',
        claim_date: new Date().toISOString().slice(0, 10),
        start_odometer: '' as number | '',
        end_odometer: '' as number | '',
        distance_km: '' as number | '',
        purpose: '',
        destination: '',
        amount_claimed: '',
        amount_approved: '',
        status: 'pending',
        rejection_reason: '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Mileage claims', href: '/fleet/mileage-claims' },
        { title: 'New', href: '/fleet/mileage-claims/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New mileage claim" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href="/fleet/mileage-claims">Back</Link></Button>
                    <h1 className="text-2xl font-semibold">New mileage claim</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.post('/fleet/mileage-claims'); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Grey fleet vehicle</Label>
                        <select required value={form.data.grey_fleet_vehicle_id} onChange={e => form.setData('grey_fleet_vehicle_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {greyFleetVehicles.map((v) => <option key={v.id} value={v.id}>{v.label}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>User (claimant)</Label>
                        <select required value={form.data.user_id} onChange={e => form.setData('user_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {users.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Claim date</Label>
                        <Input type="date" required value={form.data.claim_date} onChange={e => form.setData('claim_date', e.target.value)} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Start odometer</Label>
                            <Input type="number" min={0} value={form.data.start_odometer === '' ? '' : form.data.start_odometer} onChange={e => form.setData('start_odometer', e.target.value === '' ? '' : Number(e.target.value))} />
                        </div>
                        <div className="space-y-2">
                            <Label>End odometer</Label>
                            <Input type="number" min={0} value={form.data.end_odometer === '' ? '' : form.data.end_odometer} onChange={e => form.setData('end_odometer', e.target.value === '' ? '' : Number(e.target.value))} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Distance (km)</Label>
                        <Input type="number" min={0} value={form.data.distance_km === '' ? '' : form.data.distance_km} onChange={e => form.setData('distance_km', e.target.value === '' ? '' : Number(e.target.value))} />
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
                            <Label>Amount claimed</Label>
                            <Input type="number" step="any" min={0} value={form.data.amount_claimed} onChange={e => form.setData('amount_claimed', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Amount approved</Label>
                            <Input type="number" step="any" min={0} value={form.data.amount_approved} onChange={e => form.setData('amount_approved', e.target.value)} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Status</Label>
                        <select value={form.data.status} onChange={e => form.setData('status', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Rejection reason</Label>
                        <Input value={form.data.rejection_reason} onChange={e => form.setData('rejection_reason', e.target.value)} />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Save</Button>
                        <Button type="button" variant="outline" asChild><Link href="/fleet/mileage-claims">Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
