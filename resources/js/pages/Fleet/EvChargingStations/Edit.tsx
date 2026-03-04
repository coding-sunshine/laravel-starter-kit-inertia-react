import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface LocationOption {
    id: number;
    name: string;
}
interface EvChargingStationRecord {
    id: number;
    name: string;
    operator: string | null;
    network: string | null;
    location_id: number | null;
    address: string | null;
    lat: number | null;
    lng: number | null;
    access_type: string;
    total_connectors: number;
    available_connectors: number;
    status: string;
}
interface Props {
    evChargingStation: EvChargingStationRecord;
    locations: LocationOption[];
}

export default function FleetEvChargingStationsEdit({
    evChargingStation,
    locations,
}: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: evChargingStation.name,
        operator: evChargingStation.operator ?? '',
        network: evChargingStation.network ?? '',
        location_id: (evChargingStation.location_id ?? '') as number | '',
        address: evChargingStation.address ?? '',
        lat: (evChargingStation.lat ?? '') as number | '',
        lng: (evChargingStation.lng ?? '') as number | '',
        access_type: evChargingStation.access_type,
        total_connectors: (evChargingStation.total_connectors ?? '') as
            | number
            | '',
        available_connectors: (evChargingStation.available_connectors ?? '') as
            | number
            | '',
        status: evChargingStation.status,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/ev-charging-stations' },
        { title: 'EV charging stations', href: '/fleet/ev-charging-stations' },
        {
            title: evChargingStation.name,
            href: `/fleet/ev-charging-stations/${evChargingStation.id}`,
        },
        {
            title: 'Edit',
            href: `/fleet/ev-charging-stations/${evChargingStation.id}/edit`,
        },
    ];
    const transform = (d: typeof data) => ({
        ...d,
        location_id: d.location_id === '' ? null : Number(d.location_id),
        lat: d.lat === '' ? null : Number(d.lat),
        lng: d.lng === '' ? null : Number(d.lng),
        total_connectors:
            d.total_connectors === '' ? null : Number(d.total_connectors),
        available_connectors:
            d.available_connectors === ''
                ? null
                : Number(d.available_connectors),
    });
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${evChargingStation.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">
                    Edit EV charging station
                </h1>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        put(
                            `/fleet/ev-charging-stations/${evChargingStation.id}`,
                            { transform },
                        );
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
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="operator">Operator</Label>
                            <Input
                                id="operator"
                                value={data.operator}
                                onChange={(e) =>
                                    setData('operator', e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.operator && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.operator}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="network">Network</Label>
                            <Input
                                id="network"
                                value={data.network}
                                onChange={(e) =>
                                    setData('network', e.target.value)
                                }
                                className="mt-1"
                            />
                            {errors.network && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.network}
                                </p>
                            )}
                        </div>
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
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="lat">Latitude</Label>
                            <Input
                                id="lat"
                                type="number"
                                step="any"
                                value={data.lat === '' ? '' : String(data.lat)}
                                onChange={(e) =>
                                    setData(
                                        'lat',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                                className="mt-1"
                            />
                            {errors.lat && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.lat}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="lng">Longitude</Label>
                            <Input
                                id="lng"
                                type="number"
                                step="any"
                                value={data.lng === '' ? '' : String(data.lng)}
                                onChange={(e) =>
                                    setData(
                                        'lng',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                                className="mt-1"
                            />
                            {errors.lng && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.lng}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="access_type">Access type *</Label>
                            <select
                                id="access_type"
                                value={data.access_type}
                                onChange={(e) =>
                                    setData('access_type', e.target.value)
                                }
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                            >
                                <option value="public">Public</option>
                                <option value="private">Private</option>
                                <option value="restricted">Restricted</option>
                            </select>
                            {errors.access_type && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.access_type}
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
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                            >
                                <option value="operational">Operational</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="out_of_service">
                                    Out of service
                                </option>
                            </select>
                            {errors.status && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.status}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="total_connectors">
                                Total connectors
                            </Label>
                            <Input
                                id="total_connectors"
                                type="number"
                                min={1}
                                value={
                                    data.total_connectors === ''
                                        ? ''
                                        : String(data.total_connectors)
                                }
                                onChange={(e) =>
                                    setData(
                                        'total_connectors',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                                className="mt-1"
                            />
                            {errors.total_connectors && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.total_connectors}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="available_connectors">
                                Available connectors
                            </Label>
                            <Input
                                id="available_connectors"
                                type="number"
                                min={0}
                                value={
                                    data.available_connectors === ''
                                        ? ''
                                        : String(data.available_connectors)
                                }
                                onChange={(e) =>
                                    setData(
                                        'available_connectors',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                                className="mt-1"
                            />
                            {errors.available_connectors && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.available_connectors}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Update EV charging station
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={`/fleet/ev-charging-stations/${evChargingStation.id}`}
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
