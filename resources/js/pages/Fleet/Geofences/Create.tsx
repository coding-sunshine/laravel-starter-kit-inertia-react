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

interface LocationOption {
    id: number;
    name: string;
}

interface Props {
    types: Option[];
    locations: LocationOption[];
}

export default function FleetGeofencesCreate({ types, locations }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        geofence_type: 'circle',
        location_id: '' as number | '',
        center_lat: '' as number | '',
        center_lng: '' as number | '',
        radius_meters: '' as number | '',
        is_active: true,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/geofences' },
        { title: 'Geofences', href: '/fleet/geofences' },
        { title: 'Create', href: '/fleet/geofences/create' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New geofence" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New geofence</h1>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        post('/fleet/geofences', {
                            transform: (d) => ({
                                ...d,
                                location_id:
                                    d.location_id === ''
                                        ? null
                                        : Number(d.location_id),
                                center_lat:
                                    d.center_lat === ''
                                        ? null
                                        : Number(d.center_lat),
                                center_lng:
                                    d.center_lng === ''
                                        ? null
                                        : Number(d.center_lng),
                                radius_meters:
                                    d.radius_meters === ''
                                        ? null
                                        : Number(d.radius_meters),
                            }),
                        });
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
                        <Label htmlFor="geofence_type">Type *</Label>
                        <select
                            id="geofence_type"
                            value={data.geofence_type}
                            onChange={(e) =>
                                setData('geofence_type', e.target.value)
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                        >
                            {types.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.name}
                                </option>
                            ))}
                        </select>
                        {errors.geofence_type && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.geofence_type}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="location_id">Location</Label>
                        <select
                            id="location_id"
                            value={
                                data.location_id === ''
                                    ? ''
                                    : String(data.location_id)
                            }
                            onChange={(e) =>
                                setData(
                                    'location_id',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                        >
                            <option value="">None</option>
                            {locations.map((l) => (
                                <option key={l.id} value={l.id}>
                                    {l.name}
                                </option>
                            ))}
                        </select>
                        {errors.location_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.location_id}
                            </p>
                        )}
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                        <div>
                            <Label htmlFor="center_lat">Center lat</Label>
                            <Input
                                id="center_lat"
                                type="number"
                                step="any"
                                value={
                                    data.center_lat === ''
                                        ? ''
                                        : String(data.center_lat)
                                }
                                onChange={(e) =>
                                    setData(
                                        'center_lat',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                                className="mt-1"
                            />
                            {errors.center_lat && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.center_lat}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="center_lng">Center lng</Label>
                            <Input
                                id="center_lng"
                                type="number"
                                step="any"
                                value={
                                    data.center_lng === ''
                                        ? ''
                                        : String(data.center_lng)
                                }
                                onChange={(e) =>
                                    setData(
                                        'center_lng',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                                className="mt-1"
                            />
                            {errors.center_lng && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.center_lng}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="radius_meters">Radius (m)</Label>
                            <Input
                                id="radius_meters"
                                type="number"
                                min={0}
                                value={
                                    data.radius_meters === ''
                                        ? ''
                                        : String(data.radius_meters)
                                }
                                onChange={(e) =>
                                    setData(
                                        'radius_meters',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                                className="mt-1"
                            />
                            {errors.radius_meters && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.radius_meters}
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
                            Create geofence
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/geofences">
                                Back to geofences
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
