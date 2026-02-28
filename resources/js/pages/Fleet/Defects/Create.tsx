import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Option { value: string; name: string; }
interface Props {
    categories: Option[];
    severities: Option[];
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; first_name: string; last_name: string }[];
    workOrders: { id: number; work_order_number: string; title: string }[];
}

export default function FleetDefectsCreate({ categories, severities, vehicles, drivers, workOrders }: Props) {
    const form = useForm({
        vehicle_id: '' as number | '',
        defect_number: '',
        title: '',
        description: '',
        category: categories[0]?.value ?? '',
        severity: severities[0]?.value ?? '',
        reported_at: new Date().toISOString().slice(0, 16),
        work_order_id: '' as number | '',
        photos: [] as File[],
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/defects' },
        { title: 'Defects', href: '/fleet/defects' },
        { title: 'Create', href: '/fleet/defects/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            vehicle_id: d.vehicle_id === '' ? undefined : d.vehicle_id,
            work_order_id: d.work_order_id === '' ? null : d.work_order_id,
        }));
        form.post('/fleet/defects', { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New defect" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New defect</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="vehicle_id">Vehicle *</Label>
                        <select id="vehicle_id" value={data.vehicle_id === '' ? '' : String(data.vehicle_id)} onChange={(e) => setData('vehicle_id', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm" required>
                            <option value="">Select</option>
                            {vehicles.map((v) => <option key={v.id} value={v.id}>{v.registration}</option>)}
                        </select>
                        {errors.vehicle_id && <p className="mt-1 text-sm text-destructive">{errors.vehicle_id}</p>}
                    </div>
                    <div>
                        <Label htmlFor="defect_number">Defect number *</Label>
                        <Input id="defect_number" value={data.defect_number} onChange={(e) => setData('defect_number', e.target.value)} className="mt-1" />
                        {errors.defect_number && <p className="mt-1 text-sm text-destructive">{errors.defect_number}</p>}
                    </div>
                    <div>
                        <Label htmlFor="title">Title *</Label>
                        <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} className="mt-1" />
                        {errors.title && <p className="mt-1 text-sm text-destructive">{errors.title}</p>}
                    </div>
                    <div>
                        <Label htmlFor="description">Description *</Label>
                        <textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} className="mt-1 flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm" />
                        {errors.description && <p className="mt-1 text-sm text-destructive">{errors.description}</p>}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="category">Category *</Label>
                            <select id="category" value={data.category} onChange={(e) => setData('category', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                {categories.map((o) => <option key={o.value} value={o.value}>{o.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <Label htmlFor="severity">Severity *</Label>
                            <select id="severity" value={data.severity} onChange={(e) => setData('severity', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                {severities.map((o) => <option key={o.value} value={o.value}>{o.name}</option>)}
                            </select>
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="reported_at">Reported at *</Label>
                        <Input id="reported_at" type="datetime-local" value={data.reported_at} onChange={(e) => setData('reported_at', e.target.value)} className="mt-1" />
                        {errors.reported_at && <p className="mt-1 text-sm text-destructive">{errors.reported_at}</p>}
                    </div>
                    <div>
                        <Label htmlFor="work_order_id">Work order</Label>
                        <select id="work_order_id" value={data.work_order_id === '' ? '' : String(data.work_order_id)} onChange={(e) => setData('work_order_id', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">None</option>
                            {workOrders.map((wo) => <option key={wo.id} value={wo.id}>{wo.work_order_number} – {wo.title}</option>)}
                        </select>
                    </div>
                    <div>
                        <Label htmlFor="photos">Photos</Label>
                        <input
                            id="photos"
                            type="file"
                            accept="image/*"
                            multiple
                            className="mt-1 block w-full text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground file:transition-colors"
                            onChange={(e) => setData('photos', e.target.files ? Array.from(e.target.files) : [])}
                        />
                        {errors.photos && <p className="mt-1 text-sm text-destructive">{errors.photos}</p>}
                        {data.photos.length > 0 && <p className="mt-1 text-sm text-muted-foreground">{data.photos.length} file(s) selected</p>}
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>Create defect</Button>
                        <Button variant="outline" asChild><Link href="/fleet/defects">Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
