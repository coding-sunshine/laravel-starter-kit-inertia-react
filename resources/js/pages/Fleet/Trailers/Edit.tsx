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

interface TrailerRecord {
    id: number;
    registration: string | null;
    fleet_number: string | null;
    type: string;
    make: string | null;
    model: string | null;
    year: number | null;
    home_location_id: number | null;
    status: string;
    compliance_status: string | null;
}

interface Props {
    trailer: TrailerRecord;
    types: Option[];
    statuses: Option[];
    locations: LocationOption[];
}

export default function FleetTrailersEdit({
    trailer,
    types,
    statuses,
    locations,
}: Props) {
    const { data, setData, put, processing, errors } = useForm({
        registration: trailer.registration ?? '',
        fleet_number: trailer.fleet_number ?? '',
        type: trailer.type,
        make: trailer.make ?? '',
        model: trailer.model ?? '',
        year: (trailer.year ?? '') as number | '',
        home_location_id: (trailer.home_location_id ?? '') as number | '',
        status: trailer.status,
        compliance_status: (trailer.compliance_status ?? '') as string,
    });

    const name =
        trailer.registration ||
        trailer.fleet_number ||
        `Trailer #${trailer.id}`;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/trailers' },
        { title: 'Trailers', href: '/fleet/trailers' },
        { title: name, href: `/fleet/trailers/${trailer.id}` },
        { title: 'Edit', href: `/fleet/trailers/${trailer.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit trailer</h1>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        put(`/fleet/trailers/${trailer.id}`, {
                            transform: (d) => ({
                                ...d,
                                year: d.year === '' ? null : Number(d.year),
                                home_location_id:
                                    d.home_location_id === ''
                                        ? null
                                        : Number(d.home_location_id),
                                compliance_status: d.compliance_status || null,
                            }),
                        });
                    }}
                    className="max-w-xl space-y-4"
                >
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="registration">Registration</Label>
                            <Input
                                id="registration"
                                value={data.registration}
                                onChange={(e) =>
                                    setData('registration', e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.registration && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.registration}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="fleet_number">Fleet number</Label>
                            <Input
                                id="fleet_number"
                                value={data.fleet_number}
                                onChange={(e) =>
                                    setData('fleet_number', e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.fleet_number && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.fleet_number}
                                </p>
                            )}
                        </div>
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
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="make">Make</Label>
                            <Input
                                id="make"
                                value={data.make}
                                onChange={(e) =>
                                    setData('make', e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.make && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.make}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="model">Model</Label>
                            <Input
                                id="model"
                                value={data.model}
                                onChange={(e) =>
                                    setData('model', e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.model && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.model}
                                </p>
                            )}
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="year">Year</Label>
                        <Input
                            id="year"
                            type="number"
                            min={1900}
                            max={2100}
                            value={data.year === '' ? '' : String(data.year)}
                            onChange={(e) =>
                                setData(
                                    'year',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                            className="mt-1"
                        />
                        {errors.year && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.year}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="home_location_id">Home location</Label>
                        <select
                            id="home_location_id"
                            value={
                                data.home_location_id === ''
                                    ? ''
                                    : String(data.home_location_id)
                            }
                            onChange={(e) =>
                                setData(
                                    'home_location_id',
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
                        {errors.home_location_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.home_location_id}
                            </p>
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="status">Status *</Label>
                            <select
                                id="status"
                                value={data.status}
                                onChange={(e) =>
                                    setData('status', e.target.value)
                                }
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                            >
                                {statuses.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.name}
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
                            <Label htmlFor="compliance_status">
                                Compliance status
                            </Label>
                            <select
                                id="compliance_status"
                                value={data.compliance_status}
                                onChange={(e) =>
                                    setData('compliance_status', e.target.value)
                                }
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                            >
                                <option value="">—</option>
                                <option value="compliant">Compliant</option>
                                <option value="expiring_soon">
                                    Expiring soon
                                </option>
                                <option value="expired">Expired</option>
                            </select>
                            {errors.compliance_status && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.compliance_status}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Update trailer
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/fleet/trailers/${trailer.id}`}>
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
