import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    drivers: { id: number; name: string }[];
    sleepQualities: { value: string; name: string }[];
}

export default function FleetDriverWellnessRecordsCreate({ drivers, sleepQualities }: Props) {
    const form = useForm({
        driver_id: '' as number | '',
        record_date: new Date().toISOString().slice(0, 10),
        fatigue_level: '' as number | '',
        rest_hours: '',
        sleep_quality: '',
        mood: '',
        notes: '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Driver wellness records', href: '/fleet/driver-wellness-records' },
        { title: 'New', href: '/fleet/driver-wellness-records/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New wellness record" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href="/fleet/driver-wellness-records">Back</Link></Button>
                    <h1 className="text-2xl font-semibold">New wellness record</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.post('/fleet/driver-wellness-records'); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Driver *</Label>
                        <select required value={form.data.driver_id} onChange={e => form.setData('driver_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {drivers.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Record date *</Label>
                        <Input type="date" value={form.data.record_date} onChange={e => form.setData('record_date', e.target.value)} required />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Fatigue level (1–5)</Label>
                            <Input type="number" min={1} max={5} value={form.data.fatigue_level || ''} onChange={e => form.setData('fatigue_level', e.target.value ? Number(e.target.value) : '')} />
                        </div>
                        <div className="space-y-2">
                            <Label>Rest hours</Label>
                            <Input type="number" min={0} max={24} step={0.5} value={form.data.rest_hours} onChange={e => form.setData('rest_hours', e.target.value)} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Sleep quality</Label>
                        <select value={form.data.sleep_quality} onChange={e => form.setData('sleep_quality', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {sleepQualities.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Mood</Label>
                        <Input value={form.data.mood} onChange={e => form.setData('mood', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label>Notes</Label>
                        <textarea value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} className="min-h-[80px] w-full rounded-md border border-input px-3 py-2 text-sm" />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Save</Button>
                        <Button type="button" variant="outline" asChild><Link href="/fleet/driver-wellness-records">Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
