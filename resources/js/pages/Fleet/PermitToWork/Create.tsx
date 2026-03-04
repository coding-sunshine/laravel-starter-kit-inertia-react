import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Props {
    users: { id: number; name: string }[];
    locations: { id: number; name: string }[];
    vehicles: { id: number; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function PermitToWorkCreate({
    users,
    locations,
    vehicles,
    statuses,
}: Props) {
    const form = useForm({
        issued_by: '' as number | '',
        issued_to: '' as number | '',
        location_id: '' as number | '',
        vehicle_id: '' as number | '',
        permit_number: '',
        title: '',
        description: '',
        valid_from: '',
        valid_to: '',
        status: 'active',
        conditions: '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Permit to work', href: '/fleet/permit-to-work' },
        { title: 'New', href: '/fleet/permit-to-work/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New permit to work" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet/permit-to-work">Back</Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        New permit to work
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/fleet/permit-to-work');
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="space-y-2">
                        <Label>Permit number *</Label>
                        <Input
                            value={form.data.permit_number}
                            onChange={(e) =>
                                form.setData('permit_number', e.target.value)
                            }
                            required
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Title *</Label>
                        <Input
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                            required
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Issued by *</Label>
                        <select
                            required
                            value={form.data.issued_by}
                            onChange={(e) =>
                                form.setData(
                                    'issued_by',
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
                        <Label>Issued to</Label>
                        <select
                            value={form.data.issued_to || ''}
                            onChange={(e) =>
                                form.setData(
                                    'issued_to',
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
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Location</Label>
                            <select
                                value={form.data.location_id || ''}
                                onChange={(e) =>
                                    form.setData(
                                        'location_id',
                                        e.target.value
                                            ? Number(e.target.value)
                                            : '',
                                    )
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                <option value="">—</option>
                                {locations.map((l) => (
                                    <option key={l.id} value={l.id}>
                                        {l.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Vehicle</Label>
                            <select
                                value={form.data.vehicle_id || ''}
                                onChange={(e) =>
                                    form.setData(
                                        'vehicle_id',
                                        e.target.value
                                            ? Number(e.target.value)
                                            : '',
                                    )
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                <option value="">—</option>
                                {vehicles.map((v) => (
                                    <option key={v.id} value={v.id}>
                                        {v.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Valid from *</Label>
                            <Input
                                type="datetime-local"
                                value={form.data.valid_from}
                                onChange={(e) =>
                                    form.setData('valid_from', e.target.value)
                                }
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Valid to *</Label>
                            <Input
                                type="datetime-local"
                                value={form.data.valid_to}
                                onChange={(e) =>
                                    form.setData('valid_to', e.target.value)
                                }
                                required
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Description</Label>
                        <textarea
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                            className="min-h-[80px] w-full rounded-md border border-input px-3 py-2 text-sm"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Conditions</Label>
                        <textarea
                            value={form.data.conditions}
                            onChange={(e) =>
                                form.setData('conditions', e.target.value)
                            }
                            className="min-h-[80px] w-full rounded-md border border-input px-3 py-2 text-sm"
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
                            <Link href="/fleet/permit-to-work">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
