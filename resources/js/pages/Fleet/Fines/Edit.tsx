import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Option { value: string; name: string; }
interface FineRecord { id: number; vehicle_id: number; driver_id?: number | null; fine_type: string; offence_description?: string | null; offence_date: string; amount: string | number; amount_paid: string | number; due_date?: string | null; appeal_deadline?: string | null; status: string; appeal_notes?: string | null; external_reference?: string | null; issuing_authority?: string | null; }
interface Props {
    fine: FineRecord;
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; first_name: string; last_name: string }[];
    fineTypes: Option[];
    statuses: Option[];
}

export default function FleetFinesEdit({ fine, vehicles, drivers, fineTypes, statuses }: Props) {
    const form = useForm({
        vehicle_id: fine.vehicle_id,
        driver_id: (fine.driver_id ?? '') as number | '',
        fine_type: fine.fine_type,
        offence_description: fine.offence_description ?? '',
        offence_date: fine.offence_date.slice(0, 10),
        amount: String(fine.amount),
        amount_paid: String(fine.amount_paid),
        due_date: fine.due_date?.slice(0, 10) ?? '',
        appeal_deadline: fine.appeal_deadline?.slice(0, 10) ?? '',
        status: fine.status,
        appeal_notes: fine.appeal_notes ?? '',
        external_reference: fine.external_reference ?? '',
        issuing_authority: fine.issuing_authority ?? '',
    });
    const { data, setData, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Fines', href: '/fleet/fines' },
        { title: `Fine #${fine.id}`, href: `/fleet/fines/${fine.id}` },
        { title: 'Edit', href: `/fleet/fines/${fine.id}/edit` },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            driver_id: d.driver_id === '' ? null : d.driver_id,
            due_date: d.due_date || null,
            appeal_deadline: d.appeal_deadline || null,
            _method: 'PUT',
        }));
        form.post(`/fleet/fines/${fine.id}`, { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit fine #${fine.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit fine</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label>Vehicle *</Label>
                        <select value={data.vehicle_id} onChange={(e) => setData('vehicle_id', Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm" required>
                            {vehicles.map((v) => <option key={v.id} value={v.id}>{v.registration}</option>)}
                        </select>
                        {errors.vehicle_id && <p className="mt-1 text-sm text-destructive">{errors.vehicle_id}</p>}
                    </div>
                    <div>
                        <Label>Driver</Label>
                        <select value={data.driver_id === '' ? '' : String(data.driver_id)} onChange={(e) => setData('driver_id', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">None</option>
                            {drivers.map((d) => <option key={d.id} value={d.id}>{d.first_name} {d.last_name}</option>)}
                        </select>
                    </div>
                    <div>
                        <Label>Fine type *</Label>
                        <select value={data.fine_type} onChange={(e) => setData('fine_type', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {fineTypes.map((t) => <option key={t.value} value={t.value}>{t.name}</option>)}
                        </select>
                    </div>
                    <div>
                        <Label>Offence date *</Label>
                        <Input type="date" value={data.offence_date} onChange={(e) => setData('offence_date', e.target.value)} className="mt-1" />
                        {errors.offence_date && <p className="mt-1 text-sm text-destructive">{errors.offence_date}</p>}
                    </div>
                    <div>
                        <Label>Amount *</Label>
                        <Input type="number" step="0.01" value={data.amount} onChange={(e) => setData('amount', e.target.value)} className="mt-1" />
                        {errors.amount && <p className="mt-1 text-sm text-destructive">{errors.amount}</p>}
                    </div>
                    <div>
                        <Label>Amount paid</Label>
                        <Input type="number" step="0.01" value={data.amount_paid} onChange={(e) => setData('amount_paid', e.target.value)} className="mt-1" />
                    </div>
                    <div>
                        <Label>Status *</Label>
                        <select value={data.status} onChange={(e) => setData('status', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Update</Button>
                        <Button type="button" variant="outline" asChild><Link href={`/fleet/fines/${fine.id}`}>Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
