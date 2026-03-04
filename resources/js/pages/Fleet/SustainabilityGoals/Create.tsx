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
    statuses: Option[];
}

export default function FleetSustainabilityGoalsCreate({ statuses }: Props) {
    const form = useForm({
        title: '',
        description: '',
        status: statuses[0]?.value ?? '',
        target_date: '',
        target_value: '',
        target_unit: '',
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Sustainability goals', href: '/fleet/sustainability-goals' },
        { title: 'Create', href: '/fleet/sustainability-goals/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/fleet/sustainability-goals');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New sustainability goal" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">
                    New sustainability goal
                </h1>
                <Card className="max-w-xl">
                    <CardHeader>
                        <CardTitle className="text-base">Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="title">Title *</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) =>
                                        setData('title', e.target.value)
                                    }
                                    className="mt-1"
                                />
                                {errors.title && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.title}
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
                                <Label htmlFor="status">Status *</Label>
                                <select
                                    id="status"
                                    value={data.status}
                                    onChange={(e) =>
                                        setData('status', e.target.value)
                                    }
                                    className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                >
                                    {statuses.map((s) => (
                                        <option key={s.value} value={s.value}>
                                            {s.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.status && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.status}
                                    </p>
                                )}
                            </div>
                            <div>
                                <Label htmlFor="target_date">Target date</Label>
                                <Input
                                    id="target_date"
                                    type="date"
                                    value={data.target_date}
                                    onChange={(e) =>
                                        setData('target_date', e.target.value)
                                    }
                                    className="mt-1"
                                />
                                {errors.target_date && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.target_date}
                                    </p>
                                )}
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="target_value">
                                        Target value
                                    </Label>
                                    <Input
                                        id="target_value"
                                        type="number"
                                        step="0.01"
                                        value={data.target_value}
                                        onChange={(e) =>
                                            setData(
                                                'target_value',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1"
                                    />
                                    {errors.target_value && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.target_value}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="target_unit">
                                        Target unit
                                    </Label>
                                    <Input
                                        id="target_unit"
                                        value={data.target_unit}
                                        onChange={(e) =>
                                            setData(
                                                'target_unit',
                                                e.target.value,
                                            )
                                        }
                                        className="mt-1"
                                        placeholder="e.g. kg, %"
                                    />
                                    {errors.target_unit && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.target_unit}
                                        </p>
                                    )}
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Create sustainability goal
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/fleet/sustainability-goals">
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
