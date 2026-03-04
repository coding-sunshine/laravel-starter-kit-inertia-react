import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface LocationTypeOption {
    value: string;
    name: string;
}

interface LocationRecord {
    id: number;
    name: string;
    type: string;
    address: string;
    postcode: string | null;
    city: string | null;
    country: string;
    contact_name: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    notes: string | null;
    is_active: boolean;
}

interface Props {
    location: LocationRecord;
    locationTypes: LocationTypeOption[];
}

export default function FleetLocationsEdit({ location, locationTypes }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: location.name,
        type: location.type,
        address: location.address,
        postcode: location.postcode ?? '',
        city: location.city ?? '',
        country: location.country,
        contact_name: location.contact_name ?? '',
        contact_phone: location.contact_phone ?? '',
        contact_email: location.contact_email ?? '',
        notes: location.notes ?? '',
        is_active: location.is_active,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/locations' },
        { title: 'Locations', href: '/fleet/locations' },
        { title: location.name, href: `/fleet/locations/${location.id}` },
        { title: 'Edit', href: `/fleet/locations/${location.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${location.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit location</h1>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        put(`/fleet/locations/${location.id}`);
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
                            {locationTypes.map((t) => (
                                <option key={t.value} value={t.value}>
                                    {t.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label htmlFor="address">Address *</Label>
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
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="city">City</Label>
                            <Input
                                id="city"
                                value={data.city}
                                onChange={(e) =>
                                    setData('city', e.target.value)
                                }
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <Label htmlFor="postcode">Postcode</Label>
                            <Input
                                id="postcode"
                                value={data.postcode}
                                onChange={(e) =>
                                    setData('postcode', e.target.value)
                                }
                                className="mt-1"
                            />
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
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Update location
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <a href={`/fleet/locations/${location.id}`}>
                                Cancel
                            </a>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
