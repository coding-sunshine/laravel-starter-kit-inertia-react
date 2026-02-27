import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

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

interface DriverOption {
    id: number;
    first_name: string;
    last_name: string;
}

interface Props {
    fuelTypes: Option[];
    vehicleTypes: Option[];
    statuses: Option[];
    locations: LocationOption[];
    drivers: DriverOption[];
}

export default function FleetVehiclesCreate({
    fuelTypes,
    vehicleTypes,
    statuses,
    locations,
    drivers,
}: Props) {
    const form = useForm({
        registration: '',
        vin: '',
        fleet_number: '',
        make: '',
        model: '',
        year: '' as number | '',
        fuel_type: 'diesel',
        vehicle_type: 'van',
        home_location_id: '' as number | '',
        current_driver_id: '' as number | '',
        status: 'active',
        compliance_status: '' as string,
    });

    const { data, setData, processing, errors: formErrors } = form;
    const pageErrors = usePage().props.errors as Record<string, string> | undefined;
    const errors = { ...pageErrors, ...formErrors };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/vehicles' },
        { title: 'Vehicles', href: '/fleet/vehicles' },
        { title: 'Create', href: '/fleet/vehicles/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            year: d.year === '' ? null : Number(d.year),
            home_location_id: d.home_location_id === '' ? null : Number(d.home_location_id),
            current_driver_id: d.current_driver_id === '' ? null : Number(d.current_driver_id),
            compliance_status: d.compliance_status || null,
        }));
        form.post('/fleet/vehicles', {
            preserveScroll: true,
            onError: (errs) => {
                console.error('Vehicle create validation errors:', errs);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New vehicle" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New vehicle</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    {Object.keys(errors).length > 0 && (
                        <div className="rounded-md border border-destructive/50 bg-destructive/10 p-3 text-sm text-destructive">
                            <p className="font-medium">Please fix the following errors:</p>
                            <ul className="mt-1 list-inside list-disc">
                                {Object.entries(errors).map(([key, msg]) => (
                                    <li key={key}>{msg}</li>
                                ))}
                            </ul>
                        </div>
                    )}
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="registration">Registration *</Label>
                            <Input id="registration" value={data.registration} onChange={(e) => setData('registration', e.target.value)} className="mt-1" />
                            {errors.registration && <p className="mt-1 text-sm text-destructive">{errors.registration}</p>}
                        </div>
                        <div>
                            <Label htmlFor="vin">VIN</Label>
                            <Input id="vin" value={data.vin} onChange={(e) => setData('vin', e.target.value)} className="mt-1" maxLength={17} />
                            {errors.vin && <p className="mt-1 text-sm text-destructive">{errors.vin}</p>}
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="fleet_number">Fleet number</Label>
                        <Input id="fleet_number" value={data.fleet_number} onChange={(e) => setData('fleet_number', e.target.value)} className="mt-1" />
                        {errors.fleet_number && <p className="mt-1 text-sm text-destructive">{errors.fleet_number}</p>}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="make">Make *</Label>
                            <Input id="make" value={data.make} onChange={(e) => setData('make', e.target.value)} className="mt-1" />
                            {errors.make && <p className="mt-1 text-sm text-destructive">{errors.make}</p>}
                        </div>
                        <div>
                            <Label htmlFor="model">Model *</Label>
                            <Input id="model" value={data.model} onChange={(e) => setData('model', e.target.value)} className="mt-1" />
                            {errors.model && <p className="mt-1 text-sm text-destructive">{errors.model}</p>}
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="year">Year</Label>
                        <Input id="year" type="number" min={1900} max={2100} value={data.year === '' ? '' : String(data.year)} onChange={(e) => setData('year', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1" />
                        {errors.year && <p className="mt-1 text-sm text-destructive">{errors.year}</p>}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="fuel_type">Fuel type *</Label>
                            <select id="fuel_type" value={data.fuel_type} onChange={(e) => setData('fuel_type', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm">
                                {fuelTypes.map((o) => (<option key={o.value} value={o.value}>{o.name}</option>))}
                            </select>
                            {errors.fuel_type && <p className="mt-1 text-sm text-destructive">{errors.fuel_type}</p>}
                        </div>
                        <div>
                            <Label htmlFor="vehicle_type">Vehicle type *</Label>
                            <select id="vehicle_type" value={data.vehicle_type} onChange={(e) => setData('vehicle_type', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm">
                                {vehicleTypes.map((o) => (<option key={o.value} value={o.value}>{o.name}</option>))}
                            </select>
                            {errors.vehicle_type && <p className="mt-1 text-sm text-destructive">{errors.vehicle_type}</p>}
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="home_location_id">Home location</Label>
                            <select id="home_location_id" value={data.home_location_id === '' ? '' : String(data.home_location_id)} onChange={(e) => setData('home_location_id', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm">
                                <option value="">None</option>
                                {locations.map((l) => (<option key={l.id} value={l.id}>{l.name}</option>))}
                            </select>
                            {errors.home_location_id && <p className="mt-1 text-sm text-destructive">{errors.home_location_id}</p>}
                        </div>
                        <div>
                            <Label htmlFor="current_driver_id">Current driver</Label>
                            <select id="current_driver_id" value={data.current_driver_id === '' ? '' : String(data.current_driver_id)} onChange={(e) => setData('current_driver_id', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm">
                                <option value="">None</option>
                                {drivers.map((d) => (<option key={d.id} value={d.id}>{d.first_name} {d.last_name}</option>))}
                            </select>
                            {errors.current_driver_id && <p className="mt-1 text-sm text-destructive">{errors.current_driver_id}</p>}
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="status">Status *</Label>
                            <select id="status" value={data.status} onChange={(e) => setData('status', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm">
                                {statuses.map((o) => (<option key={o.value} value={o.value}>{o.name}</option>))}
                            </select>
                            {errors.status && <p className="mt-1 text-sm text-destructive">{errors.status}</p>}
                        </div>
                        <div>
                            <Label htmlFor="compliance_status">Compliance status</Label>
                            <select id="compliance_status" value={data.compliance_status} onChange={(e) => setData('compliance_status', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm">
                                <option value="">—</option>
                                <option value="compliant">Compliant</option>
                                <option value="expiring_soon">Expiring soon</option>
                                <option value="expired">Expired</option>
                            </select>
                            {errors.compliance_status && <p className="mt-1 text-sm text-destructive">{errors.compliance_status}</p>}
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>Create vehicle</Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/vehicles">Back to vehicles</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
