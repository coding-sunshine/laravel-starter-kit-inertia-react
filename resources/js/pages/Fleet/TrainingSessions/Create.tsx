import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    courses: { id: number; course_name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetTrainingSessionsCreate({ courses, statuses }: Props) {
    const form = useForm({
        training_course_id: '' as number | '',
        session_name: '',
        scheduled_date: new Date().toISOString().slice(0, 10),
        start_time: '09:00',
        end_time: '17:00',
        status: 'scheduled',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Training sessions', href: '/fleet/training-sessions' },
        { title: 'New', href: '/fleet/training-sessions/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New training session" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href="/fleet/training-sessions">Back</Link></Button>
                    <h1 className="text-2xl font-semibold">New training session</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.post('/fleet/training-sessions'); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Course</Label>
                        <select required value={form.data.training_course_id} onChange={e => form.setData('training_course_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {courses.map((c) => <option key={c.id} value={c.id}>{c.course_name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Session name</Label>
                        <Input value={form.data.session_name} onChange={e => form.setData('session_name', e.target.value)} required />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Date</Label>
                            <Input type="date" value={form.data.scheduled_date} onChange={e => form.setData('scheduled_date', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Status</Label>
                            <select value={form.data.status} onChange={e => form.setData('status', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                            </select>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Save</Button>
                        <Button type="button" variant="outline" asChild><Link href="/fleet/training-sessions">Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
