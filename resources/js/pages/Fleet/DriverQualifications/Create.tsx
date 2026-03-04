import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Props {
    drivers: { id: number; name: string }[];
    qualificationTypes: { value: string; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetDriverQualificationsCreate({
    drivers,
    qualificationTypes,
    statuses,
}: Props) {
    const form = useForm({
        driver_id: '' as number | '',
        qualification_type: 'license',
        qualification_name: '',
        status: 'valid',
        issue_date: '',
        expiry_date: '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        {
            title: 'Driver qualifications',
            href: '/fleet/driver-qualifications',
        },
        { title: 'New', href: '/fleet/driver-qualifications/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New driver qualification" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet/driver-qualifications">Back</Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        New driver qualification
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/fleet/driver-qualifications');
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
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
                    <div className="space-y-2">
                        <Label>Qualification name</Label>
                        <Input
                            value={form.data.qualification_name}
                            onChange={(e) =>
                                form.setData(
                                    'qualification_name',
                                    e.target.value,
                                )
                            }
                            required
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Type</Label>
                            <select
                                value={form.data.qualification_type}
                                onChange={(e) =>
                                    form.setData(
                                        'qualification_type',
                                        e.target.value,
                                    )
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {qualificationTypes.map((q) => (
                                    <option key={q.value} value={q.value}>
                                        {q.name}
                                    </option>
                                ))}
                            </select>
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
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Issue date</Label>
                            <Input
                                type="date"
                                value={form.data.issue_date}
                                onChange={(e) =>
                                    form.setData('issue_date', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Expiry date</Label>
                            <Input
                                type="date"
                                value={form.data.expiry_date}
                                onChange={(e) =>
                                    form.setData('expiry_date', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/fleet/driver-qualifications">
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
