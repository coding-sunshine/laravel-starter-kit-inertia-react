import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Observation { id: number; reported_by: number; location_id?: number; title: string; description?: string; category: string; location_description?: string; status: string; action_taken?: string; }
interface Props {
    safetyObservation: Observation;
    users: { id: number; name: string }[];
    locations: { id: number; name: string }[];
    categories: { value: string; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function SafetyObservationsEdit({ safetyObservation, users, locations, categories, statuses }: Props) {
    const form = useForm({
        reported_by: safetyObservation.reported_by,
        location_id: (safetyObservation.location_id ?? '') as number | '',
        title: safetyObservation.title,
        description: safetyObservation.description ?? '',
        category: safetyObservation.category,
        location_description: safetyObservation.location_description ?? '',
        status: safetyObservation.status,
        action_taken: safetyObservation.action_taken ?? '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Safety observations', href: '/fleet/safety-observations' },
        { title: 'Edit', href: `/fleet/safety-observations/${safetyObservation.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit safety observation" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href={`/fleet/safety-observations/${safetyObservation.id}`}>Back</Link></Button>
                    <h1 className="text-2xl font-semibold">Edit safety observation</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.put(`/fleet/safety-observations/${safetyObservation.id}`); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Title *</Label>
                        <Input value={form.data.title} onChange={e => form.setData('title', e.target.value)} required />
                    </div>
                    <div className="space-y-2">
                        <Label>Reported by *</Label>
                        <select required value={form.data.reported_by} onChange={e => form.setData('reported_by', Number(e.target.value))} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {users.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Category *</Label>
                        <select required value={form.data.category} onChange={e => form.setData('category', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {categories.map((c) => <option key={c.value} value={c.value}>{c.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Location</Label>
                        <select value={form.data.location_id || ''} onChange={e => form.setData('location_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">—</option>
                            {locations.map((l) => <option key={l.id} value={l.id}>{l.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Location description</Label>
                        <Input value={form.data.location_description} onChange={e => form.setData('location_description', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <Label>Description</Label>
                        <textarea value={form.data.description} onChange={e => form.setData('description', e.target.value)} className="min-h-[80px] w-full rounded-md border border-input px-3 py-2 text-sm" />
                    </div>
                    <div className="space-y-2">
                        <Label>Status</Label>
                        <select value={form.data.status} onChange={e => form.setData('status', e.target.value)} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Action taken</Label>
                        <textarea value={form.data.action_taken} onChange={e => form.setData('action_taken', e.target.value)} className="min-h-[80px] w-full rounded-md border border-input px-3 py-2 text-sm" />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Save</Button>
                        <Button type="button" variant="outline" asChild><Link href={`/fleet/safety-observations/${safetyObservation.id}`}>Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
