import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Assignment {
    id: number;
    user_id?: number;
    driver_id?: number;
    ppe_type: string;
    item_reference?: string;
    issued_date: string;
    expiry_or_return_date?: string;
    status: string;
}
interface Props {
    ppeAssignment: Assignment;
    users: { id: number; name: string }[];
    drivers: { id: number; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function PpeAssignmentsEdit({
    ppeAssignment,
    users,
    drivers,
    statuses,
}: Props) {
    const form = useForm({
        user_id: (ppeAssignment.user_id ?? '') as number | '',
        driver_id: (ppeAssignment.driver_id ?? '') as number | '',
        ppe_type: ppeAssignment.ppe_type,
        item_reference: ppeAssignment.item_reference ?? '',
        issued_date: ppeAssignment.issued_date,
        expiry_or_return_date: ppeAssignment.expiry_or_return_date ?? '',
        status: ppeAssignment.status,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'PPE assignments', href: '/fleet/ppe-assignments' },
        {
            title: 'Edit',
            href: `/fleet/ppe-assignments/${ppeAssignment.id}/edit`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit PPE assignment" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={`/fleet/ppe-assignments/${ppeAssignment.id}`}
                        >
                            Back
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        Edit PPE assignment
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put(`/fleet/ppe-assignments/${ppeAssignment.id}`);
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="space-y-2">
                        <Label>PPE type *</Label>
                        <Input
                            value={form.data.ppe_type}
                            onChange={(e) =>
                                form.setData('ppe_type', e.target.value)
                            }
                            required
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>User</Label>
                            <select
                                value={form.data.user_id || ''}
                                onChange={(e) =>
                                    form.setData(
                                        'user_id',
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
                            <Label>Driver</Label>
                            <select
                                value={form.data.driver_id || ''}
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
                    </div>
                    <div className="space-y-2">
                        <Label>Item reference</Label>
                        <Input
                            value={form.data.item_reference}
                            onChange={(e) =>
                                form.setData('item_reference', e.target.value)
                            }
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Issued date *</Label>
                            <Input
                                type="date"
                                value={form.data.issued_date}
                                onChange={(e) =>
                                    form.setData('issued_date', e.target.value)
                                }
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Expiry / return date</Label>
                            <Input
                                type="date"
                                value={form.data.expiry_or_return_date}
                                onChange={(e) =>
                                    form.setData(
                                        'expiry_or_return_date',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
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
                            <Link
                                href={`/fleet/ppe-assignments/${ppeAssignment.id}`}
                            >
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
