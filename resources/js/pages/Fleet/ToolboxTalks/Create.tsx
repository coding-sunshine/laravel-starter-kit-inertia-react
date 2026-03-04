import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Props {
    users: { id: number; name: string }[];
    drivers: { id: number; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function ToolboxTalksCreate({
    users,
    drivers: _drivers,
    statuses,
}: Props) {
    const form = useForm({
        presenter_id: '' as number | '',
        topic: '',
        content: '',
        scheduled_date: '',
        scheduled_time: '',
        location: '',
        attendee_driver_ids: [] as number[],
        attendee_user_ids: [] as number[],
        attendance_count: 0,
        status: 'scheduled',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Toolbox talks', href: '/fleet/toolbox-talks' },
        { title: 'New', href: '/fleet/toolbox-talks/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New toolbox talk" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet/toolbox-talks">Back</Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">New toolbox talk</h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/fleet/toolbox-talks');
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="space-y-2">
                        <Label>Topic *</Label>
                        <Input
                            value={form.data.topic}
                            onChange={(e) =>
                                form.setData('topic', e.target.value)
                            }
                            required
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Presenter</Label>
                        <select
                            value={form.data.presenter_id || ''}
                            onChange={(e) =>
                                form.setData(
                                    'presenter_id',
                                    e.target.value
                                        ? Number(e.target.value)
                                        : '',
                                )
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">—</option>
                            {users.map((u) => (
                                <option key={u.id} value={u.id}>
                                    {u.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Content</Label>
                        <textarea
                            value={form.data.content}
                            onChange={(e) =>
                                form.setData('content', e.target.value)
                            }
                            className="min-h-[100px] w-full rounded-md border border-input px-3 py-2 text-sm"
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Scheduled date</Label>
                            <Input
                                type="date"
                                value={form.data.scheduled_date}
                                onChange={(e) =>
                                    form.setData(
                                        'scheduled_date',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Scheduled time</Label>
                            <Input
                                type="time"
                                value={form.data.scheduled_time}
                                onChange={(e) =>
                                    form.setData(
                                        'scheduled_time',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Location</Label>
                        <Input
                            value={form.data.location}
                            onChange={(e) =>
                                form.setData('location', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Attendance count</Label>
                        <Input
                            type="number"
                            min={0}
                            value={form.data.attendance_count}
                            onChange={(e) =>
                                form.setData(
                                    'attendance_count',
                                    Number(e.target.value) || 0,
                                )
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Status</Label>
                        <select
                            value={form.data.status}
                            onChange={(e) =>
                                form.setData('status', e.target.value)
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {statuses.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/fleet/toolbox-talks">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
