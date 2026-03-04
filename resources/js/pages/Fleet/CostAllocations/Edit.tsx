import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface CostAllocation {
    id: number;
    cost_center_id: number;
    allocation_date: string;
    cost_type: string;
    source_type: string;
    amount: string;
    vat_amount: string;
    approval_status: string;
}
interface Props {
    costAllocation: CostAllocation;
    costCenters: { id: number; name: string }[];
    costTypes: { value: string; name: string }[];
    sourceTypes: { value: string; name: string }[];
    approvalStatuses: { value: string; name: string }[];
}

export default function FleetCostAllocationsEdit({
    costAllocation,
    costCenters,
    costTypes,
    sourceTypes,
    approvalStatuses,
}: Props) {
    const form = useForm({
        cost_center_id: costAllocation.cost_center_id,
        allocation_date: costAllocation.allocation_date?.slice(0, 10) ?? '',
        cost_type: costAllocation.cost_type,
        source_type: costAllocation.source_type,
        amount: costAllocation.amount,
        vat_amount: costAllocation.vat_amount ?? '0',
        approval_status: costAllocation.approval_status,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Cost allocations', href: '/fleet/cost-allocations' },
        {
            title: 'Edit',
            href: `/fleet/cost-allocations/${costAllocation.id}/edit`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit cost allocation" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={`/fleet/cost-allocations/${costAllocation.id}`}
                        >
                            Back
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        Edit cost allocation
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put(
                            `/fleet/cost-allocations/${costAllocation.id}`,
                        );
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="space-y-2">
                        <Label>Cost center</Label>
                        <select
                            required
                            value={form.data.cost_center_id}
                            onChange={(e) =>
                                form.setData(
                                    'cost_center_id',
                                    Number(e.target.value),
                                )
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {costCenters.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <Label>Allocation date</Label>
                        <Input
                            type="date"
                            value={form.data.allocation_date}
                            onChange={(e) =>
                                form.setData('allocation_date', e.target.value)
                            }
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Cost type</Label>
                            <select
                                value={form.data.cost_type}
                                onChange={(e) =>
                                    form.setData('cost_type', e.target.value)
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {costTypes.map((c) => (
                                    <option key={c.value} value={c.value}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Source type</Label>
                            <select
                                value={form.data.source_type}
                                onChange={(e) =>
                                    form.setData('source_type', e.target.value)
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {sourceTypes.map((s) => (
                                    <option key={s.value} value={s.value}>
                                        {s.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Amount</Label>
                            <Input
                                type="number"
                                step="0.01"
                                value={form.data.amount}
                                onChange={(e) =>
                                    form.setData('amount', e.target.value)
                                }
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>VAT amount</Label>
                            <Input
                                type="number"
                                step="0.01"
                                value={form.data.vat_amount}
                                onChange={(e) =>
                                    form.setData('vat_amount', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Approval status</Label>
                        <select
                            value={form.data.approval_status}
                            onChange={(e) =>
                                form.setData('approval_status', e.target.value)
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {approvalStatuses.map((a) => (
                                <option key={a.value} value={a.value}>
                                    {a.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Update
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link
                                href={`/fleet/cost-allocations/${costAllocation.id}`}
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
