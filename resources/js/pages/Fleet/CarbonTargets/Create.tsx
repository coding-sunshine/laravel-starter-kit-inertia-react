import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Option {
    value: string;
    name: string;
}
interface Props {
    periods: Option[];
}

export default function FleetCarbonTargetsCreate({ periods }: Props) {
    const form = useForm({
        name: '',
        description: '',
        period: periods[0]?.value ?? '',
        target_year: new Date().getFullYear(),
        target_co2_kg: '',
        baseline_co2_kg: '',
        is_active: true,
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Carbon targets', href: '/fleet/carbon-targets' },
        { title: 'Create', href: '/fleet/carbon-targets/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/fleet/carbon-targets');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New carbon target" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New carbon target</h1>
                <Card className="max-w-xl">
                    <CardHeader>
                        <CardTitle className="text-base">Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="name">Name *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
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
                                    className="mt-1 flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                />
                                {errors.description && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.description}
                                    </p>
                                )}
                            </div>
                            <div>
                                <Label htmlFor="period">Period *</Label>
                                <select
                                    id="period"
                                    value={data.period}
                                    onChange={(e) =>
                                        setData('period', e.target.value)
                                    }
                                    className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                >
                                    {periods.map((p) => (
                                        <option key={p.value} value={p.value}>
                                            {p.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.period && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.period}
                                    </p>
                                )}
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="target_year">
                                        Target year *
                                    </Label>
                                    <Input
                                        id="target_year"
                                        type="number"
                                        min={2000}
                                        max={2100}
                                        value={data.target_year}
                                        onChange={(e) =>
                                            setData(
                                                'target_year',
                                                e.target.value
                                                    ? Number(e.target.value)
                                                    : 0,
                                            )
                                        }
                                        className="mt-1"
                                    />
                                    {errors.target_year && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.target_year}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="target_co2_kg">
                                        Target CO₂ (kg) *
                                    </Label>
                                    <Input
                                        id="target_co2_kg"
                                        type="number"
                                        step="0.01"
                                        min={0}
                                        value={data.target_co2_kg}
                                        onChange={(e) =>
                                            setData(
                                                'target_co2_kg',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1"
                                    />
                                    {errors.target_co2_kg && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.target_co2_kg}
                                        </p>
                                    )}
                                </div>
                            </div>
                            <div>
                                <Label htmlFor="baseline_co2_kg">
                                    Baseline CO₂ (kg)
                                </Label>
                                <Input
                                    id="baseline_co2_kg"
                                    type="number"
                                    step="0.01"
                                    min={0}
                                    value={data.baseline_co2_kg}
                                    onChange={(e) =>
                                        setData(
                                            'baseline_co2_kg',
                                            e.target.value,
                                        )
                                    }
                                    className="mt-1"
                                />
                                {errors.baseline_co2_kg && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.baseline_co2_kg}
                                    </p>
                                )}
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    checked={data.is_active}
                                    onChange={(e) =>
                                        setData('is_active', e.target.checked)
                                    }
                                    className="rounded border-input"
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
                                    Create carbon target
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/fleet/carbon-targets">
                                        Cancel
                                    </Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
