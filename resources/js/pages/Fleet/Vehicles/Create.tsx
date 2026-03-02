import AppLayout from '@/layouts/app-layout';
import { FleetPageHeader } from '@/components/fleet';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

const inputClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-0 disabled:opacity-50';

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
    const hasErrors = Object.keys(errors).length > 0;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
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
            onError: () => {},
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New vehicle" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6">
                <FleetPageHeader
                    title="New vehicle"
                    description="Add a new vehicle to your fleet."
                    action={
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/fleet/vehicles">
                                <ArrowLeft className="mr-2 size-4" />
                                Back to vehicles
                            </Link>
                        </Button>
                    }
                />

                <form onSubmit={handleSubmit} className="space-y-6">
                    {hasErrors && (
                        <Card className="border-destructive/50 bg-destructive/5">
                            <CardContent className="pt-6">
                                <p className="font-medium text-destructive">Please fix the following errors:</p>
                                <ul className="mt-2 list-inside list-disc space-y-0.5 text-sm text-destructive">
                                    {Object.entries(errors).map(([key, msg]) => (
                                        <li key={key}>{msg}</li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    )}

                    <Card className="border border-border shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-base">Identity</CardTitle>
                            <CardDescription>Registration, VIN, and fleet number.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="registration">Registration *</Label>
                                    <Input
                                        id="registration"
                                        value={data.registration}
                                        onChange={(e) => setData('registration', e.target.value)}
                                        className={errors.registration ? 'border-destructive' : ''}
                                    />
                                    {errors.registration && (
                                        <p className="text-sm text-destructive">{errors.registration}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="vin">VIN</Label>
                                    <Input
                                        id="vin"
                                        value={data.vin}
                                        onChange={(e) => setData('vin', e.target.value)}
                                        maxLength={17}
                                        className={errors.vin ? 'border-destructive' : ''}
                                    />
                                    {errors.vin && <p className="text-sm text-destructive">{errors.vin}</p>}
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="fleet_number">Fleet number</Label>
                                <Input
                                    id="fleet_number"
                                    value={data.fleet_number}
                                    onChange={(e) => setData('fleet_number', e.target.value)}
                                    className={errors.fleet_number ? 'border-destructive' : ''}
                                />
                                {errors.fleet_number && (
                                    <p className="text-sm text-destructive">{errors.fleet_number}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-border shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-base">Vehicle details</CardTitle>
                            <CardDescription>Make, model, year, fuel and type.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="make">Make *</Label>
                                    <Input
                                        id="make"
                                        value={data.make}
                                        onChange={(e) => setData('make', e.target.value)}
                                        className={errors.make ? 'border-destructive' : ''}
                                    />
                                    {errors.make && <p className="text-sm text-destructive">{errors.make}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="model">Model *</Label>
                                    <Input
                                        id="model"
                                        value={data.model}
                                        onChange={(e) => setData('model', e.target.value)}
                                        className={errors.model ? 'border-destructive' : ''}
                                    />
                                    {errors.model && <p className="text-sm text-destructive">{errors.model}</p>}
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="year">Year</Label>
                                <Input
                                    id="year"
                                    type="number"
                                    min={1900}
                                    max={2100}
                                    value={data.year === '' ? '' : String(data.year)}
                                    onChange={(e) =>
                                        setData('year', e.target.value === '' ? '' : Number(e.target.value))
                                    }
                                    className={errors.year ? 'border-destructive' : ''}
                                />
                                {errors.year && <p className="text-sm text-destructive">{errors.year}</p>}
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="fuel_type">Fuel type *</Label>
                                    <select
                                        id="fuel_type"
                                        value={data.fuel_type}
                                        onChange={(e) => setData('fuel_type', e.target.value)}
                                        className={inputClass}
                                    >
                                        {fuelTypes.map((o) => (
                                            <option key={o.value} value={o.value}>
                                                {o.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.fuel_type && (
                                        <p className="text-sm text-destructive">{errors.fuel_type}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="vehicle_type">Vehicle type *</Label>
                                    <select
                                        id="vehicle_type"
                                        value={data.vehicle_type}
                                        onChange={(e) => setData('vehicle_type', e.target.value)}
                                        className={inputClass}
                                    >
                                        {vehicleTypes.map((o) => (
                                            <option key={o.value} value={o.value}>
                                                {o.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.vehicle_type && (
                                        <p className="text-sm text-destructive">{errors.vehicle_type}</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-border shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-base">Assignment & status</CardTitle>
                            <CardDescription>Home location, current driver, and status.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="home_location_id">Home location</Label>
                                    <select
                                        id="home_location_id"
                                        value={data.home_location_id === '' ? '' : String(data.home_location_id)}
                                        onChange={(e) =>
                                            setData(
                                                'home_location_id',
                                                e.target.value === '' ? '' : Number(e.target.value),
                                            )
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">None</option>
                                        {locations.map((l) => (
                                            <option key={l.id} value={l.id}>
                                                {l.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.home_location_id && (
                                        <p className="text-sm text-destructive">{errors.home_location_id}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="current_driver_id">Current driver</Label>
                                    <select
                                        id="current_driver_id"
                                        value={data.current_driver_id === '' ? '' : String(data.current_driver_id)}
                                        onChange={(e) =>
                                            setData(
                                                'current_driver_id',
                                                e.target.value === '' ? '' : Number(e.target.value),
                                            )
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">None</option>
                                        {drivers.map((d) => (
                                            <option key={d.id} value={d.id}>
                                                {d.first_name} {d.last_name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.current_driver_id && (
                                        <p className="text-sm text-destructive">{errors.current_driver_id}</p>
                                    )}
                                </div>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="status">Status *</Label>
                                    <select
                                        id="status"
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                        className={inputClass}
                                    >
                                        {statuses.map((o) => (
                                            <option key={o.value} value={o.value}>
                                                {o.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.status && (
                                        <p className="text-sm text-destructive">{errors.status}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="compliance_status">Compliance status</Label>
                                    <select
                                        id="compliance_status"
                                        value={data.compliance_status}
                                        onChange={(e) => setData('compliance_status', e.target.value)}
                                        className={inputClass}
                                    >
                                        <option value="">—</option>
                                        <option value="compliant">Compliant</option>
                                        <option value="expiring_soon">Expiring soon</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                    {errors.compliance_status && (
                                        <p className="text-sm text-destructive">{errors.compliance_status}</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex flex-wrap gap-3">
                        <Button type="submit" disabled={processing}>
                            Create vehicle
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/vehicles">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
