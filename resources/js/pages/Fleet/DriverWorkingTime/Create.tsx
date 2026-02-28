import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    drivers: { id: number; first_name: string; last_name: string }[];
}

export default function FleetDriverWorkingTimeCreate({ drivers }: Props) {
    const form = useForm({
        driver_id: '' as number | '',
        date: new Date().toISOString().slice(0, 10),
        driving_time_minutes: '' as number | '',
        total_duty_time_minutes: '' as number | '',
        wtd_compliant: true,
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/driver-working-time' },
        { title: 'Driver working time', href: '/fleet/driver-working-time' },
        { title: 'Create', href: '/fleet/driver-working-time/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            driver_id: d.driver_id === '' ? undefined : d.driver_id,
            driving_time_minutes: d.driving_time_minutes === '' ? null : Number(d.driving_time_minutes),
            total_duty_time_minutes: d.total_duty_time_minutes === '' ? null : Number(d.total_duty_time_minutes),
        }));
        form.post('/fleet/driver-working-time');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New driver working time" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New driver working time</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="driver_id">Driver *</Label>
                        <select id="driver_id" value={data.driver_id === '' ? '' : String(data.driver_id)} onChange={(e) => setData('driver_id', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm" required>
                            <option value="">Select</option>
                            {drivers.map((d) => <option key={d.id} value={d.id}>{d.last_name}, {d.first_name}</option>)}
                        </select>
                        {errors.driver_id && <p className="mt-1 text-sm text-destructive">{errors.driver_id}</p>}
                    </div>
                    <div>
                        <Label htmlFor="date">Date *</Label>
                        <Input id="date" type="date" value={data.date} onChange={(e) => setData('date', e.target.value)} className="mt-1" />
                        {errors.date && <p className="mt-1 text-sm text-destructive">{errors.date}</p>}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="driving_time_minutes">Driving time (minutes)</Label>
                            <Input id="driving_time_minutes" type="number" min="0" value={data.driving_time_minutes === '' ? '' : data.driving_time_minutes} onChange={(e) => setData('driving_time_minutes', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1" />
                        </div>
                        <div>
                            <Label htmlFor="total_duty_time_minutes">Total duty time (minutes)</Label>
                            <Input id="total_duty_time_minutes" type="number" min="0" value={data.total_duty_time_minutes === '' ? '' : data.total_duty_time_minutes} onChange={(e) => setData('total_duty_time_minutes', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1" />
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <input type="checkbox" id="wtd_compliant" checked={data.wtd_compliant} onChange={(e) => setData('wtd_compliant', e.target.checked)} className="rounded border-input" />
                        <Label htmlFor="wtd_compliant">WTD compliant</Label>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>Create</Button>
                        <Button variant="outline" asChild><Link href="/fleet/driver-working-time">Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
