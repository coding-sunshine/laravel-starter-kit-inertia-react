import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Option {
    value: string;
    name: string;
}

interface GarageRecord {
    id: number;
    name: string;
    type: string;
    address: string | null;
    contact_name: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    is_active: boolean;
}

interface Props {
    garage: GarageRecord;
    types: Option[];
}

export default function FleetGaragesEdit({ garage, types }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: garage.name,
        type: garage.type,
        address: garage.address ?? '',
        contact_name: garage.contact_name ?? '',
        contact_phone: garage.contact_phone ?? '',
        contact_email: garage.contact_email ?? '',
        is_active: garage.is_active,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/garages' },
        { title: 'Garages', href: '/fleet/garages' },
        { title: garage.name, href: `/fleet/garages/${garage.id}` },
        { title: 'Edit', href: `/fleet/garages/${garage.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${garage.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit garage</h1>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        put(`/fleet/garages/${garage.id}`);
                    }}
                    className="max-w-xl space-y-4"
                >
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
                        <Label htmlFor="type">Type *</Label>
                        <select
                            id="type"
                            value={data.type}
                            onChange={(e) => setData('type', e.target.value)}
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                        >
                            {types.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.name}
                                </option>
                            ))}
                        </select>
                        {errors.type && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.type}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="address">Address</Label>
                        <textarea
                            id="address"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            rows={2}
                            className="mt-1 flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                        {errors.address && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.address}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="contact_name">Contact name</Label>
                        <Input
                            id="contact_name"
                            value={data.contact_name}
                            onChange={(e) =>
                                setData('contact_name', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.contact_name && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.contact_name}
                            </p>
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="contact_phone">Contact phone</Label>
                            <Input
                                id="contact_phone"
                                value={data.contact_phone}
                                onChange={(e) =>
                                    setData('contact_phone', e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.contact_phone && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.contact_phone}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="contact_email">Contact email</Label>
                            <Input
                                id="contact_email"
                                type="email"
                                value={data.contact_email}
                                onChange={(e) =>
                                    setData('contact_email', e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.contact_email && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.contact_email}
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
                            Update garage
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/fleet/garages/${garage.id}`}>
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
