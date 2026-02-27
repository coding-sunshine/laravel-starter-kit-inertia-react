import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface FuelStationRecord {
    id: number; name: string; brand: string | null; address: string; postcode: string | null; city: string | null; country: string;
    lat: number | null; lng: number | null; phone: string | null; website: string | null; is_active: boolean;
}
interface Props { fuelStation: FuelStationRecord; }

export default function FleetFuelStationsEdit({ fuelStation }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: fuelStation.name, brand: fuelStation.brand ?? '', address: fuelStation.address, postcode: fuelStation.postcode ?? '', city: fuelStation.city ?? '', country: fuelStation.country,
        lat: (fuelStation.lat ?? '') as number | '', lng: (fuelStation.lng ?? '') as number | '', phone: fuelStation.phone ?? '', website: fuelStation.website ?? '', is_active: fuelStation.is_active,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/fuel-stations' }, { title: 'Fuel stations', href: '/fleet/fuel-stations' },
        { title: fuelStation.name, href: `/fleet/fuel-stations/${fuelStation.id}` }, { title: 'Edit', href: `/fleet/fuel-stations/${fuelStation.id}/edit` },
    ];
    const transform = (d: typeof data) => ({ ...d, lat: d.lat === '' ? null : Number(d.lat), lng: d.lng === '' ? null : Number(d.lng) });
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${fuelStation.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit fuel station</h1>
                <form onSubmit={(e) => { e.preventDefault(); put(`/fleet/fuel-stations/${fuelStation.id}`, { transform }); }} className="max-w-xl space-y-4">
                    <div><Label htmlFor="name">Name *</Label><Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1" />{errors.name && <p className="mt-1 text-sm text-destructive">{errors.name}</p>}</div>
                    <div><Label htmlFor="brand">Brand</Label><Input id="brand" value={data.brand} onChange={(e) => setData('brand', e.target.value)} className="mt-1" />{errors.brand && <p className="mt-1 text-sm text-destructive">{errors.brand}</p>}</div>
                    <div><Label htmlFor="address">Address *</Label><textarea id="address" value={data.address} onChange={(e) => setData('address', e.target.value)} rows={2} className="mt-1 flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm" />{errors.address && <p className="mt-1 text-sm text-destructive">{errors.address}</p>}</div>
                    <div className="grid grid-cols-2 gap-4">
                        <div><Label htmlFor="postcode">Postcode</Label><Input id="postcode" value={data.postcode} onChange={(e) => setData('postcode', e.target.value)} className="mt-1" />{errors.postcode && <p className="mt-1 text-sm text-destructive">{errors.postcode}</p>}</div>
                        <div><Label htmlFor="city">City</Label><Input id="city" value={data.city} onChange={(e) => setData('city', e.target.value)} className="mt-1" />{errors.city && <p className="mt-1 text-sm text-destructive">{errors.city}</p>}</div>
                    </div>
                    <div><Label htmlFor="country">Country</Label><Input id="country" value={data.country} onChange={(e) => setData('country', e.target.value)} className="mt-1" />{errors.country && <p className="mt-1 text-sm text-destructive">{errors.country}</p>}</div>
                    <div className="grid grid-cols-2 gap-4">
                        <div><Label htmlFor="lat">Latitude</Label><Input id="lat" type="number" step="any" value={data.lat === '' ? '' : String(data.lat)} onChange={(e) => setData('lat', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1" />{errors.lat && <p className="mt-1 text-sm text-destructive">{errors.lat}</p>}</div>
                        <div><Label htmlFor="lng">Longitude</Label><Input id="lng" type="number" step="any" value={data.lng === '' ? '' : String(data.lng)} onChange={(e) => setData('lng', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1" />{errors.lng && <p className="mt-1 text-sm text-destructive">{errors.lng}</p>}</div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div><Label htmlFor="phone">Phone</Label><Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} className="mt-1" />{errors.phone && <p className="mt-1 text-sm text-destructive">{errors.phone}</p>}</div>
                        <div><Label htmlFor="website">Website</Label><Input id="website" value={data.website} onChange={(e) => setData('website', e.target.value)} className="mt-1" />{errors.website && <p className="mt-1 text-sm text-destructive">{errors.website}</p>}</div>
                    </div>
                    <div className="flex items-center gap-2"><input type="checkbox" id="is_active" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="h-4 w-4 rounded border-input" /><Label htmlFor="is_active">Active</Label></div>
                    {errors.is_active && <p className="text-sm text-destructive">{errors.is_active}</p>}
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>Update fuel station</Button><Button variant="outline" asChild><Link href={`/fleet/fuel-stations/${fuelStation.id}`}>Cancel</Link></Button></div>
                </form>
            </div>
        </AppLayout>
    );
}
