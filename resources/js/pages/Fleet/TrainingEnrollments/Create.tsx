import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Props {
    trainingSessions: { id: number; name: string }[];
    drivers: { id: number; name: string }[];
    enrollmentStatuses: { value: string; name: string }[];
    passFailOptions: { value: string; name: string }[];
}

export default function FleetTrainingEnrollmentsCreate({
    trainingSessions,
    drivers,
    enrollmentStatuses,
    passFailOptions,
}: Props) {
    const form = useForm({
        training_session_id: '' as number | '',
        driver_id: '' as number | '',
        enrollment_date: new Date().toISOString().slice(0, 10),
        enrollment_status: 'enrolled',
        pass_fail: 'pending',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Training enrollments', href: '/fleet/training-enrollments' },
        { title: 'New', href: '/fleet/training-enrollments/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New training enrollment" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet/training-enrollments">Back</Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        New training enrollment
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/fleet/training-enrollments');
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="space-y-2">
                        <Label>Session</Label>
                        <select
                            required
                            value={form.data.training_session_id}
                            onChange={(e) =>
                                form.setData(
                                    'training_session_id',
                                    e.target.value
                                        ? Number(e.target.value)
                                        : '',
                                )
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">—</option>
                            {trainingSessions.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Driver</Label>
                        <select
                            required
                            value={form.data.driver_id}
                            onChange={(e) =>
                                form.setData(
                                    'driver_id',
                                    e.target.value
                                        ? Number(e.target.value)
                                        : '',
                                )
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">—</option>
                            {drivers.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Enrollment date</Label>
                            <Input
                                type="date"
                                value={form.data.enrollment_date}
                                onChange={(e) =>
                                    form.setData(
                                        'enrollment_date',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Status</Label>
                            <select
                                value={form.data.enrollment_status}
                                onChange={(e) =>
                                    form.setData(
                                        'enrollment_status',
                                        e.target.value,
                                    )
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {enrollmentStatuses.map((s) => (
                                    <option key={s.value} value={s.value}>
                                        {s.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Pass/Fail</Label>
                        <select
                            value={form.data.pass_fail}
                            onChange={(e) =>
                                form.setData('pass_fail', e.target.value)
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {passFailOptions.map((p) => (
                                <option key={p.value} value={p.value}>
                                    {p.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/fleet/training-enrollments">
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
