import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Option { value: string; name: string; }
interface DefectRecord { id: number; defect_number: string; title: string; description: string; vehicle_id: number; category: string; severity: string; status: string; reported_at: string; work_order_id?: number | null; }
interface Props {
    defect: DefectRecord;
    categories: Option[];
    severities: Option[];
    statuses: Option[];
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; first_name: string; last_name: string }[];
    workOrders: { id: number; work_order_number: string; title: string }[];
}

export default function FleetDefectsEdit({ defect, categories, severities, statuses, vehicles, workOrders }: Props) {
    const form = useForm({
        defect_number: defect.defect_number,
        title: defect.title,
        description: defect.description,
        vehicle_id: defect.vehicle_id,
        category: defect.category,
        severity: defect.severity,
        status: defect.status,
        reported_at: defect.reported_at.slice(0, 16),
        work_order_id: (defect.work_order_id ?? '') as number | '',
        photos: [] as File[],
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/defects' },
        { title: 'Defects', href: '/fleet/defects' },
        { title: defect.defect_number, href: `/fleet/defects/${defect.id}` },
        { title: 'Edit', href: `/fleet/defects/${defect.id}/edit` },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            work_order_id: d.work_order_id === '' ? null : d.work_order_id,
            _method: 'PUT',
        }));
        form.post(`/fleet/defects/${defect.id}`, { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${defect.defect_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit defect</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div><Label>Defect number *</Label><Input value={data.defect_number} onChange={(e) => setData('defect_number', e.target.value)} className="mt-1" /></div>
                    <div><Label>Title *</Label><Input value={data.title} onChange={(e) => setData('title', e.target.value)} className="mt-1" /></div>
                    <div><Label>Description *</Label><textarea value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} className="mt-1 flex w-full rounded-md border border-input px-3 py-2 text-sm" /></div>
                    <div><Label>Vehicle *</Label><select value={data.vehicle_id} onChange={(e) => setData('vehicle_id', Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm">{vehicles.map((v) => <option key={v.id} value={v.id}>{v.registration}</option>)}</select></div>
                    <div className="grid grid-cols-2 gap-4">
                        <div><Label>Category</Label><select value={data.category} onChange={(e) => setData('category', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm">{categories.map((c) => <option key={c.value} value={c.value}>{c.name}</option>)}</select></div>
                        <div><Label>Severity</Label><select value={data.severity} onChange={(e) => setData('severity', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm">{severities.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}</select></div>
                    </div>
                    <div><Label>Status</Label><select value={data.status} onChange={(e) => setData('status', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm">{statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}</select></div>
                    <div><Label>Reported at</Label><Input type="datetime-local" value={data.reported_at} onChange={(e) => setData('reported_at', e.target.value)} className="mt-1" /></div>
                    <div><Label>Work order</Label><select value={data.work_order_id === '' ? '' : String(data.work_order_id)} onChange={(e) => setData('work_order_id', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"><option value="">None</option>{workOrders.map((w) => <option key={w.id} value={w.id}>{w.work_order_number} – {w.title}</option>)}</select></div>
                    <div>
                        <Label>Add more photos</Label>
                        <input type="file" accept="image/*" multiple className="mt-1 block w-full text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground" onChange={(e) => setData('photos', e.target.files ? Array.from(e.target.files) : [])} />
                        {data.photos.length > 0 && <p className="mt-1 text-sm text-muted-foreground">{data.photos.length} new file(s) selected</p>}
                    </div>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Update</Button><Button variant="outline" asChild><Link href={`/fleet/defects/${defect.id}`}>Cancel</Link></Button></div>
                </form>
            </div>
        </AppLayout>
    );
}
