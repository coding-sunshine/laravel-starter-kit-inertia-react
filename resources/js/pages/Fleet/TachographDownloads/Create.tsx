import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Option { value: string; name: string; }
interface Props {
    drivers: { id: number; first_name: string; last_name: string }[];
    statuses: Option[];
}

export default function FleetTachographDownloadsCreate({ drivers, statuses }: Props) {
    const form = useForm({
        driver_id: '' as number | '',
        download_date: new Date().toISOString().slice(0, 10),
        status: statuses[0]?.value ?? '',
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/tachograph-downloads' },
        { title: 'Tachograph downloads', href: '/fleet/tachograph-downloads' },
        { title: 'Create', href: '/fleet/tachograph-downloads/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            driver_id: d.driver_id === '' ? undefined : d.driver_id,
        }));
        form.post('/fleet/tachograph-downloads');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New tachograph download" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New tachograph download</h1>
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
                        <Label htmlFor="download_date">Download date *</Label>
                        <Input id="download_date" type="date" value={data.download_date} onChange={(e) => setData('download_date', e.target.value)} className="mt-1" />
                        {errors.download_date && <p className="mt-1 text-sm text-destructive">{errors.download_date}</p>}
                    </div>
                    <div>
                        <Label htmlFor="status">Status</Label>
                        <select id="status" value={data.status} onChange={(e) => setData('status', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {statuses.map((o) => <option key={o.value} value={o.value}>{o.name}</option>)}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>Create</Button>
                        <Button variant="outline" asChild><Link href="/fleet/tachograph-downloads">Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
