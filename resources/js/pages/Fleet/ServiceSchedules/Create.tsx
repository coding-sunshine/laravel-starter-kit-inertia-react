import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Option { value: string; name: string; }
interface Props {
    serviceTypes: Option[];
    intervalTypes: Option[];
    intervalUnits: Option[];
    vehicles: { id: number; registration: string }[];
    garages: { id: number; name: string }[];
}

export default function FleetServiceSchedulesCreate({ serviceTypes, intervalTypes, intervalUnits, vehicles, garages }: Props) {
    const form = useForm({
        vehicle_id: '' as number | '',
        service_type: serviceTypes[0]?.value ?? '',
        interval_type: intervalTypes[0]?.value ?? '',
        interval_value: '12',
        interval_unit: intervalUnits[0]?.value ?? 'months',
        is_active: true,
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/service-schedules' },
        { title: 'Service schedules', href: '/fleet/service-schedules' },
        { title: 'Create', href: '/fleet/service-schedules/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            vehicle_id: d.vehicle_id === '' ? undefined : d.vehicle_id,
            interval_value: d.interval_value === '' ? undefined : Number(d.interval_value),
        }));
        form.post('/fleet/service-schedules');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New service schedule" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New service schedule</h1>
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
                        <Label htmlFor="service_type">Service type *</Label>
                        <select id="service_type" value={data.service_type} onChange={(e) => setData('service_type', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {serviceTypes.map((o) => <option key={o.value} value={o.value}>{o.name}</option>)}
                        </select>
                        {errors.service_type && <p className="mt-1 text-sm text-destructive">{errors.service_type}</p>}
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                        <div>
                            <Label htmlFor="interval_type">Interval type</Label>
                            <select id="interval_type" value={data.interval_type} onChange={(e) => setData('interval_type', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                {intervalTypes.map((o) => <option key={o.value} value={o.value}>{o.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <Label htmlFor="interval_value">Interval value *</Label>
                            <Input id="interval_value" type="number" min="1" value={data.interval_value} onChange={(e) => setData('interval_value', e.target.value)} className="mt-1" />
                            {errors.interval_value && <p className="mt-1 text-sm text-destructive">{errors.interval_value}</p>}
                        </div>
                        <div>
                            <Label htmlFor="interval_unit">Interval unit *</Label>
                            <select id="interval_unit" value={data.interval_unit} onChange={(e) => setData('interval_unit', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                {intervalUnits.map((o) => <option key={o.value} value={o.value}>{o.name}</option>)}
                            </select>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <input type="checkbox" id="is_active" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="rounded border-input" />
                        <Label htmlFor="is_active">Active</Label>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>Create service schedule</Button>
                        <Button variant="outline" asChild><Link href="/fleet/service-schedules">Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
