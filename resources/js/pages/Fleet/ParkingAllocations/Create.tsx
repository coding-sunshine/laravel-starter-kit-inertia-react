import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    vehicles: { id: number; registration: string }[];
    locations: { id: number; name: string }[];
}

export default function FleetParkingAllocationsCreate({ vehicles, locations }: Props) {
    const form = useForm({
        vehicle_id: '' as number | '',
        location_id: '' as number | '',
        allocated_from: '',
        allocated_to: '' as string,
        spot_identifier: '',
        cost: '' as number | '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Parking allocations', href: '/fleet/parking-allocations' },
        { title: 'New', href: '/fleet/parking-allocations/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New parking allocation" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href="/fleet/parking-allocations">Back</Link></Button>
                    <h1 className="text-2xl font-semibold">New parking allocation</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.post('/fleet/parking-allocations'); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Vehicle</Label>
                        <select required value={form.data.vehicle_id} onChange={e => form.setData('vehicle_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {vehicles.map((v) => <option key={v.id} value={v.id}>{v.registration}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Location</Label>
                        <select required value={form.data.location_id} onChange={e => form.setData('location_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {locations.map((l) => <option key={l.id} value={l.id}>{l.name}</option>)}
                        </select>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Allocated from</Label>
                            <Input type="datetime-local" required value={form.data.allocated_from} onChange={e => form.setData('allocated_from', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Allocated to</Label>
                            <Input type="datetime-local" value={form.data.allocated_to} onChange={e => form.setData('allocated_to', e.target.value)} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Spot identifier</Label>
                        <Input value={form.data.spot_identifier} onChange={e => form.setData('spot_identifier', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label>Cost</Label>
                        <Input type="number" step="0.01" min={0} value={form.data.cost === '' ? '' : form.data.cost} onChange={e => form.setData('cost', e.target.value === '' ? '' : Number(e.target.value))} />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Save</Button>
                        <Button type="button" variant="outline" asChild><Link href="/fleet/parking-allocations">Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
