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
}

export default function SafetyPolicyAcknowledgmentsCreate({
    users,
    drivers,
}: Props) {
    const form = useForm({
        user_id: '' as number | '',
        driver_id: '' as number | '',
        policy_type: '',
        policy_reference: '',
        policy_version: '',
        acknowledged_at: new Date()
            .toISOString()
            .slice(0, 19)
            .replace('T', ' '),
        ip_address: '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        {
            title: 'Safety policy acknowledgments',
            href: '/fleet/safety-policy-acknowledgments',
        },
        { title: 'New', href: '/fleet/safety-policy-acknowledgments/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New safety policy acknowledgment" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet/safety-policy-acknowledgments">
                            Back
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        New safety policy acknowledgment
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/fleet/safety-policy-acknowledgments');
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="space-y-2">
                        <Label>Policy type *</Label>
                        <Input
                            value={form.data.policy_type}
                            onChange={(e) =>
                                form.setData('policy_type', e.target.value)
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
                        <Label>Policy reference</Label>
                        <Input
                            value={form.data.policy_reference}
                            onChange={(e) =>
                                form.setData('policy_reference', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Policy version</Label>
                        <Input
                            value={form.data.policy_version}
                            onChange={(e) =>
                                form.setData('policy_version', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Acknowledged at *</Label>
                        <Input
                            type="datetime-local"
                            value={form.data.acknowledged_at}
                            onChange={(e) =>
                                form.setData('acknowledged_at', e.target.value)
                            }
                            required
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>IP address</Label>
                        <Input
                            value={form.data.ip_address}
                            onChange={(e) =>
                                form.setData('ip_address', e.target.value)
                            }
                        />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/fleet/safety-policy-acknowledgments">
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
