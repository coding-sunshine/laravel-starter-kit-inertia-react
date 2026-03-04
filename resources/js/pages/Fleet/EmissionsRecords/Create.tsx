import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Props {
    vehicles: { id: number; registration: string }[];
    scopes: { value: string; name: string }[];
    emissionsTypes: { value: string; name: string }[];
}

export default function FleetEmissionsRecordsCreate({
    vehicles,
    scopes,
    emissionsTypes,
}: Props) {
    const form = useForm({
        scope: 'vehicle',
        emissions_type: 'fuel_combustion',
        record_date: new Date().toISOString().slice(0, 10),
        co2_kg: '0',
        vehicle_id: '' as number | '',
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Emissions records', href: '/fleet/emissions-records' },
        { title: 'New', href: '/fleet/emissions-records/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New emissions record" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet/emissions-records">Back</Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        New emissions record
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/fleet/emissions-records');
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Scope</Label>
                            <select
                                name="scope"
                                value={form.data.scope}
                                onChange={(e) =>
                                    form.setData('scope', e.target.value)
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {scopes.map((s) => (
                                    <option key={s.value} value={s.value}>
                                        {s.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Type</Label>
                            <select
                                name="emissions_type"
                                value={form.data.emissions_type}
                                onChange={(e) =>
                                    form.setData(
                                        'emissions_type',
                                        e.target.value,
                                    )
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {emissionsTypes.map((t) => (
                                    <option key={t.value} value={t.value}>
                                        {t.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Record date</Label>
                        <Input
                            type="date"
                            value={form.data.record_date}
                            onChange={(e) =>
                                form.setData('record_date', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>CO₂ (kg)</Label>
                        <Input
                            type="number"
                            step="0.001"
                            value={form.data.co2_kg}
                            onChange={(e) =>
                                form.setData('co2_kg', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Vehicle</Label>
                        <select
                            name="vehicle_id"
                            value={form.data.vehicle_id}
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
                                    {v.registration}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/fleet/emissions-records">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
