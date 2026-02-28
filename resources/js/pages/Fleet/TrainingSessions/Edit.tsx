import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface TrainingSession { id: number; training_course_id: number; session_name: string; scheduled_date: string; start_time: string; end_time: string; status: string; }
interface Props {
    trainingSession: TrainingSession;
    courses: { id: number; course_name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetTrainingSessionsEdit({ trainingSession, courses, statuses }: Props) {
    const form = useForm({
        training_course_id: trainingSession.training_course_id,
        session_name: trainingSession.session_name,
        scheduled_date: trainingSession.scheduled_date?.slice(0, 10) ?? '',
        start_time: trainingSession.start_time?.slice(0, 5) ?? '',
        end_time: trainingSession.end_time?.slice(0, 5) ?? '',
        status: trainingSession.status,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Training sessions', href: '/fleet/training-sessions' },
        { title: 'Edit', href: `/fleet/training-sessions/${trainingSession.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit training session" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href={`/fleet/training-sessions/${trainingSession.id}`}>Back</Link></Button>
                    <h1 className="text-2xl font-semibold">Edit training session</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.put(`/fleet/training-sessions/${trainingSession.id}`); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Course</Label>
                        <select required value={form.data.training_course_id} onChange={e => form.setData('training_course_id', Number(e.target.value))} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {courses.map((c) => <option key={c.id} value={c.id}>{c.course_name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Session name</Label>
                        <Input value={form.data.session_name} onChange={e => form.setData('session_name', e.target.value)} required />
                    </div>
                    <div className="space-y-2">
                        <Label>Date</Label>
                        <Input type="date" value={form.data.scheduled_date} onChange={e => form.setData('scheduled_date', e.target.value)} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Start time</Label>
                            <Input type="time" value={form.data.start_time} onChange={e => form.setData('start_time', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>End time</Label>
                            <Input type="time" value={form.data.end_time} onChange={e => form.setData('end_time', e.target.value)} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Status</Label>
                        <select value={form.data.status} onChange={e => form.setData('status', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Update</Button>
                        <Button type="button" variant="outline" asChild><Link href={`/fleet/training-sessions/${trainingSession.id}`}>Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
