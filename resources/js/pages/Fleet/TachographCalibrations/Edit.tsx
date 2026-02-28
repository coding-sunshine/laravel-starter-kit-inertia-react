import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Calibration { id: number; vehicle_id?: number; telematics_device_id?: number; calibration_date: string; due_date?: string; certificate_reference?: string; status: string; }
interface Props {
    tachographCalibration: Calibration;
    vehicles: { id: number; name: string }[];
    telematicsDevices: { id: number; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function TachographCalibrationsEdit({ tachographCalibration, vehicles, telematicsDevices, statuses }: Props) {
    const form = useForm({
        vehicle_id: (tachographCalibration.vehicle_id ?? '') as number | '',
        telematics_device_id: (tachographCalibration.telematics_device_id ?? '') as number | '',
        calibration_date: tachographCalibration.calibration_date,
        due_date: tachographCalibration.due_date ?? '',
        certificate_reference: tachographCalibration.certificate_reference ?? '',
        status: tachographCalibration.status,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Tachograph calibrations', href: '/fleet/tachograph-calibrations' },
        { title: 'Edit', href: `/fleet/tachograph-calibrations/${tachographCalibration.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit tachograph calibration" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href={`/fleet/tachograph-calibrations/${tachographCalibration.id}`}>Back</Link></Button>
                    <h1 className="text-2xl font-semibold">Edit tachograph calibration</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.put(`/fleet/tachograph-calibrations/${tachographCalibration.id}`); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Vehicle</Label>
                        <select value={form.data.vehicle_id || ''} onChange={e => form.setData('vehicle_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {vehicles.map((v) => <option key={v.id} value={v.id}>{v.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Telematics device</Label>
                        <select value={form.data.telematics_device_id || ''} onChange={e => form.setData('telematics_device_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {telematicsDevices.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                        </select>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Calibration date *</Label>
                            <Input type="date" value={form.data.calibration_date} onChange={e => form.setData('calibration_date', e.target.value)} required />
                        </div>
                        <div className="space-y-2">
                            <Label>Due date</Label>
                            <Input type="date" value={form.data.due_date} onChange={e => form.setData('due_date', e.target.value)} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Certificate reference</Label>
                        <Input value={form.data.certificate_reference} onChange={e => form.setData('certificate_reference', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label>Status</Label>
                        <select value={form.data.status} onChange={e => form.setData('status', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Save</Button>
                        <Button type="button" variant="outline" asChild><Link href={`/fleet/tachograph-calibrations/${tachographCalibration.id}`}>Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
