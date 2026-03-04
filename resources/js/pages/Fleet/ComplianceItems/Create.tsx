import { Button } from '@/components/ui/button';
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
    entityTypes: Option[];
    statuses: Option[];
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; first_name: string; last_name: string }[];
}

export default function FleetComplianceItemsCreate({
    entityTypes,
    statuses: _statuses,
    vehicles: _vehicles,
    drivers: _drivers,
}: Props) {
    const form = useForm({
        entity_type: entityTypes[0]?.value ?? 'vehicle',
        entity_id: '' as number | '',
        compliance_type: '',
        title: '',
        expiry_date: '',
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/compliance-items' },
        { title: 'Compliance items', href: '/fleet/compliance-items' },
        { title: 'Create', href: '/fleet/compliance-items/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            entity_id: d.entity_id === '' ? undefined : Number(d.entity_id),
        }));
        form.post('/fleet/compliance-items');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New compliance item" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New compliance item</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="entity_type">Entity type *</Label>
                        <select
                            id="entity_type"
                            value={data.entity_type}
                            onChange={(e) =>
                                setData('entity_type', e.target.value)
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {entityTypes.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label htmlFor="entity_id">Entity ID *</Label>
                        <Input
                            id="entity_id"
                            type="number"
                            min="1"
                            value={data.entity_id === '' ? '' : data.entity_id}
                            onChange={(e) =>
                                setData(
                                    'entity_id',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                            className="mt-1"
                        />
                        <p className="mt-1 text-xs text-muted-foreground">
                            Vehicle or driver ID depending on entity type
                        </p>
                        {errors.entity_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.entity_id}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="compliance_type">
                            Compliance type *
                        </Label>
                        <Input
                            id="compliance_type"
                            value={data.compliance_type}
                            onChange={(e) =>
                                setData('compliance_type', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.compliance_type && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.compliance_type}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="title">Title *</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="mt-1"
                        />
                        {errors.title && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.title}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="expiry_date">Expiry date *</Label>
                        <Input
                            id="expiry_date"
                            type="date"
                            value={data.expiry_date}
                            onChange={(e) =>
                                setData('expiry_date', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.expiry_date && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.expiry_date}
                            </p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Create compliance item
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/compliance-items">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
