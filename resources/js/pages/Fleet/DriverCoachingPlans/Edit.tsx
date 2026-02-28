import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Plan { id: number; driver_id: number; plan_type: string; title?: string; objectives?: string; status: string; due_date?: string; assigned_coach_id?: number; notes?: string; }
interface Props {
    driverCoachingPlan: Plan;
    drivers: { id: number; name: string }[];
    planTypes: { value: string; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetDriverCoachingPlansEdit({ driverCoachingPlan, drivers, planTypes, statuses }: Props) {
    const form = useForm({
        driver_id: driverCoachingPlan.driver_id,
        plan_type: driverCoachingPlan.plan_type,
        title: driverCoachingPlan.title ?? '',
        objectives: driverCoachingPlan.objectives ?? '',
        status: driverCoachingPlan.status,
        due_date: driverCoachingPlan.due_date ?? '',
        assigned_coach_id: (driverCoachingPlan.assigned_coach_id ?? '') as number | '',
        notes: driverCoachingPlan.notes ?? '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Driver coaching plans', href: '/fleet/driver-coaching-plans' },
        { title: 'Edit', href: `/fleet/driver-coaching-plans/${driverCoachingPlan.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit coaching plan" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href={`/fleet/driver-coaching-plans/${driverCoachingPlan.id}`}>Back</Link></Button>
                    <h1 className="text-2xl font-semibold">Edit coaching plan</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.put(`/fleet/driver-coaching-plans/${driverCoachingPlan.id}`); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Driver *</Label>
                        <select required value={form.data.driver_id} onChange={e => form.setData('driver_id', Number(e.target.value))} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {drivers.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                        </select>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Plan type *</Label>
                            <select value={form.data.plan_type} onChange={e => form.setData('plan_type', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                {planTypes.map((p) => <option key={p.value} value={p.value}>{p.name}</option>)}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Status</Label>
                            <select value={form.data.status} onChange={e => form.setData('status', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                            </select>
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Title</Label>
                        <Input value={form.data.title} onChange={e => form.setData('title', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label>Due date</Label>
                        <Input type="date" value={form.data.due_date} onChange={e => form.setData('due_date', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label>Objectives</Label>
                        <textarea value={form.data.objectives} onChange={e => form.setData('objectives', e.target.value)} className="min-h-[80px] w-full rounded-md border border-input px-3 py-2 text-sm" />
                    </div>
                    <div className="space-y-2">
                        <Label>Notes</Label>
                        <textarea value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} className="min-h-[60px] w-full rounded-md border border-input px-3 py-2 text-sm" />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Save</Button>
                        <Button type="button" variant="outline" asChild><Link href={`/fleet/driver-coaching-plans/${driverCoachingPlan.id}`}>Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
