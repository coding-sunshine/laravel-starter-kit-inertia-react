import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    vehicles: { id: number; registration: string }[];
    tyreInventory: { id: number; label: string }[];
    positionOptions: { value: string; name: string }[];
}

export default function FleetVehicleTyresCreate({ vehicles, tyreInventory, positionOptions }: Props) {
    const form = useForm({
        vehicle_id: '' as number | '',
        tyre_inventory_id: '' as number | '',
        position: 'front_left',
        size: '',
        brand: '',
        fitted_at: '',
        tread_depth_mm: '',
        odometer_at_fit: '' as number | '',
        notes: '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicle tyres', href: '/fleet/vehicle-tyres' },
        { title: 'New', href: '/fleet/vehicle-tyres/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New vehicle tyre" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href="/fleet/vehicle-tyres">Back</Link></Button>
                    <h1 className="text-2xl font-semibold">New vehicle tyre</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.post('/fleet/vehicle-tyres'); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Vehicle</Label>
                        <select required value={form.data.vehicle_id} onChange={e => form.setData('vehicle_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {vehicles.map((v) => <option key={v.id} value={v.id}>{v.registration}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Position</Label>
                        <select required value={form.data.position} onChange={e => form.setData('position', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {positionOptions.map((p) => <option key={p.value} value={p.value}>{p.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Tyre (from inventory)</Label>
                        <select value={form.data.tyre_inventory_id} onChange={e => form.setData('tyre_inventory_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {tyreInventory.map((t) => <option key={t.id} value={t.id}>{t.label}</option>)}
                        </select>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Size</Label>
                            <Input value={form.data.size} onChange={e => form.setData('size', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Brand</Label>
                            <Input value={form.data.brand} onChange={e => form.setData('brand', e.target.value)} />
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Fitted at</Label>
                            <Input type="date" value={form.data.fitted_at} onChange={e => form.setData('fitted_at', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Tread depth (mm)</Label>
                            <Input type="number" step="any" min={0} value={form.data.tread_depth_mm} onChange={e => form.setData('tread_depth_mm', e.target.value)} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Odometer at fit</Label>
                        <Input type="number" min={0} value={form.data.odometer_at_fit === '' ? '' : form.data.odometer_at_fit} onChange={e => form.setData('odometer_at_fit', e.target.value === '' ? '' : Number(e.target.value))} />
                    </div>
                    <div className="space-y-2">
                        <Label>Notes</Label>
                        <textarea value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} className="min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm" />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Save</Button>
                        <Button type="button" variant="outline" asChild><Link href="/fleet/vehicle-tyres">Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
