import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Disc { id: number; vehicle_id: number; operator_licence_id: number; disc_number: string; valid_from: string; valid_to: string; status: string; }
interface Props {
    vehicleDisc: Disc;
    vehicles: { id: number; name: string }[];
    operatorLicences: { id: number; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function VehicleDiscsEdit({ vehicleDisc, vehicles, operatorLicences, statuses }: Props) {
    const form = useForm({
        vehicle_id: vehicleDisc.vehicle_id,
        operator_licence_id: vehicleDisc.operator_licence_id,
        disc_number: vehicleDisc.disc_number,
        valid_from: vehicleDisc.valid_from,
        valid_to: vehicleDisc.valid_to,
        status: vehicleDisc.status,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicle discs', href: '/fleet/vehicle-discs' },
        { title: 'Edit', href: `/fleet/vehicle-discs/${vehicleDisc.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit vehicle disc" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href={`/fleet/vehicle-discs/${vehicleDisc.id}`}>Back</Link></Button>
                    <h1 className="text-2xl font-semibold">Edit vehicle disc</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.put(`/fleet/vehicle-discs/${vehicleDisc.id}`); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Vehicle *</Label>
                        <select required value={form.data.vehicle_id} onChange={e => form.setData('vehicle_id', Number(e.target.value))} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {vehicles.map((v) => <option key={v.id} value={v.id}>{v.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Operator licence *</Label>
                        <select required value={form.data.operator_licence_id} onChange={e => form.setData('operator_licence_id', Number(e.target.value))} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {operatorLicences.map((o) => <option key={o.id} value={o.id}>{o.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Disc number *</Label>
                        <Input value={form.data.disc_number} onChange={e => form.setData('disc_number', e.target.value)} required />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Valid from *</Label>
                            <Input type="date" value={form.data.valid_from} onChange={e => form.setData('valid_from', e.target.value)} required />
                        </div>
                        <div className="space-y-2">
                            <Label>Valid to *</Label>
                            <Input type="date" value={form.data.valid_to} onChange={e => form.setData('valid_to', e.target.value)} required />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Status</Label>
                        <select value={form.data.status} onChange={e => form.setData('status', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Save</Button>
                        <Button type="button" variant="outline" asChild><Link href={`/fleet/vehicle-discs/${vehicleDisc.id}`}>Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
