import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface CostCenterTypeOption {
    value: string;
    name: string;
}

interface ParentOption {
    id: number;
    code: string;
    name: string;
}

interface UserOption {
    id: number;
    name: string;
}

interface CostCenterRecord {
    id: number;
    code: string;
    name: string;
    description: string | null;
    parent_cost_center_id: number | null;
    cost_center_type: string;
    manager_user_id: number | null;
    budget_annual: number | string | null;
    budget_monthly: number | string | null;
    is_active: boolean;
}

interface Props {
    costCenter: CostCenterRecord;
    costCenterTypes: CostCenterTypeOption[];
    parents: ParentOption[];
    users: UserOption[];
}

export default function FleetCostCentersEdit({
    costCenter,
    costCenterTypes,
    parents,
    users,
}: Props) {
    const { data, setData, put, processing, errors } = useForm({
        code: costCenter.code,
        name: costCenter.name,
        description: costCenter.description ?? '',
        parent_cost_center_id: (costCenter.parent_cost_center_id ?? '') as
            | number
            | '',
        cost_center_type: costCenter.cost_center_type,
        manager_user_id: (costCenter.manager_user_id ?? '') as number | '',
        budget_annual: (costCenter.budget_annual ?? '') as number | '',
        budget_monthly: (costCenter.budget_monthly ?? '') as number | '',
        is_active: costCenter.is_active,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/cost-centers' },
        { title: 'Cost centers', href: '/fleet/cost-centers' },
        {
            title: costCenter.name,
            href: `/fleet/cost-centers/${costCenter.id}`,
        },
        { title: 'Edit', href: `/fleet/cost-centers/${costCenter.id}/edit` },
    ];

    const transform = (d: typeof data) => ({
        ...d,
        parent_cost_center_id:
            d.parent_cost_center_id === ''
                ? null
                : Number(d.parent_cost_center_id),
        manager_user_id:
            d.manager_user_id === '' ? null : Number(d.manager_user_id),
        budget_annual: d.budget_annual === '' ? null : Number(d.budget_annual),
        budget_monthly:
            d.budget_monthly === '' ? null : Number(d.budget_monthly),
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${costCenter.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit cost center</h1>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        put(`/fleet/cost-centers/${costCenter.id}`, {
                            transform,
                        });
                    }}
                    className="max-w-xl space-y-4"
                >
                    <div>
                        <Label htmlFor="code">Code *</Label>
                        <Input
                            id="code"
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value)}
                            className="mt-1"
                        />
                        {errors.code && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.code}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="name">Name *</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1"
                        />
                        {errors.name && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.name}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            rows={2}
                            className="mt-1 flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                        {errors.description && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.description}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="cost_center_type">Type *</Label>
                        <select
                            id="cost_center_type"
                            value={data.cost_center_type}
                            onChange={(e) =>
                                setData('cost_center_type', e.target.value)
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                        >
                            {costCenterTypes.map((t) => (
                                <option key={t.value} value={t.value}>
                                    {t.name}
                                </option>
                            ))}
                        </select>
                        {errors.cost_center_type && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.cost_center_type}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="parent_cost_center_id">
                            Parent cost center
                        </Label>
                        <select
                            id="parent_cost_center_id"
                            value={
                                data.parent_cost_center_id === ''
                                    ? ''
                                    : String(data.parent_cost_center_id)
                            }
                            onChange={(e) =>
                                setData(
                                    'parent_cost_center_id',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                        >
                            <option value="">None</option>
                            {parents.map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.code} – {p.name}
                                </option>
                            ))}
                        </select>
                        {errors.parent_cost_center_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.parent_cost_center_id}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="manager_user_id">Manager</Label>
                        <select
                            id="manager_user_id"
                            value={
                                data.manager_user_id === ''
                                    ? ''
                                    : String(data.manager_user_id)
                            }
                            onChange={(e) =>
                                setData(
                                    'manager_user_id',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                        >
                            <option value="">None</option>
                            {users.map((u) => (
                                <option key={u.id} value={u.id}>
                                    {u.name}
                                </option>
                            ))}
                        </select>
                        {errors.manager_user_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.manager_user_id}
                            </p>
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="budget_annual">Annual budget</Label>
                            <Input
                                id="budget_annual"
                                type="number"
                                min={0}
                                step="0.01"
                                value={
                                    data.budget_annual === ''
                                        ? ''
                                        : String(data.budget_annual)
                                }
                                onChange={(e) =>
                                    setData(
                                        'budget_annual',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                                className="mt-1"
                            />
                            {errors.budget_annual && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.budget_annual}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="budget_monthly">
                                Monthly budget
                            </Label>
                            <Input
                                id="budget_monthly"
                                type="number"
                                min={0}
                                step="0.01"
                                value={
                                    data.budget_monthly === ''
                                        ? ''
                                        : String(data.budget_monthly)
                                }
                                onChange={(e) =>
                                    setData(
                                        'budget_monthly',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                                className="mt-1"
                            />
                            {errors.budget_monthly && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.budget_monthly}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="is_active"
                            checked={data.is_active}
                            onChange={(e) =>
                                setData('is_active', e.target.checked)
                            }
                            className="h-4 w-4 rounded border-input"
                        />
                        <Label htmlFor="is_active">Active</Label>
                    </div>
                    {errors.is_active && (
                        <p className="text-sm text-destructive">
                            {errors.is_active}
                        </p>
                    )}
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Update cost center
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/fleet/cost-centers/${costCenter.id}`}>
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
