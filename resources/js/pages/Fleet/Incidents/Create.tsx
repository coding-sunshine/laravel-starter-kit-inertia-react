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
interface Props {
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; first_name: string; last_name: string }[];
    incidentTypes: Option[];
    severities: Option[];
    statuses: Option[];
    faultDeterminations: Option[];
}

export default function FleetIncidentsCreate({
    vehicles,
    drivers,
    incidentTypes,
    severities,
    statuses,
    faultDeterminations,
}: Props) {
    const form = useForm({
        vehicle_id: '' as number | '',
        driver_id: '' as number | '',
        incident_number: '',
        incident_date: new Date().toISOString().slice(0, 10),
        incident_time: new Date().toTimeString().slice(0, 5),
        incident_type: incidentTypes[0]?.value ?? '',
        severity: severities[0]?.value ?? '',
        description: '',
        location_description: '',
        fault_determination: faultDeterminations[0]?.value ?? '',
        photos: [] as File[],
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/incidents' },
        { title: 'Incidents', href: '/fleet/incidents' },
        { title: 'Create', href: '/fleet/incidents/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            vehicle_id: d.vehicle_id === '' ? undefined : d.vehicle_id,
            driver_id: d.driver_id === '' ? undefined : d.driver_id,
        }));
        form.post('/fleet/incidents', { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New incident" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New incident</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="vehicle_id">Vehicle *</Label>
                        <select
                            id="vehicle_id"
                            value={data.vehicle_id === '' ? '' : String(data.vehicle_id)}
                            onChange={(e) =>
                                setData('vehicle_id', e.target.value === '' ? '' : Number(e.target.value))
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            required
                        >
                            <option value="">Select</option>
                            {vehicles.map((v) => (
                                <option key={v.id} value={v.id}>
                                    {v.registration}
                                </option>
                            ))}
                        </select>
                        {errors.vehicle_id && (
                            <p className="mt-1 text-sm text-destructive">{errors.vehicle_id}</p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="driver_id">Driver</Label>
                        <select
                            id="driver_id"
                            value={data.driver_id === '' ? '' : String(data.driver_id)}
                            onChange={(e) =>
                                setData('driver_id', e.target.value === '' ? '' : Number(e.target.value))
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">None</option>
                            {drivers.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.first_name} {d.last_name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label htmlFor="incident_number">Incident number *</Label>
                        <Input
                            id="incident_number"
                            value={data.incident_number}
                            onChange={(e) => setData('incident_number', e.target.value)}
                            className="mt-1"
                        />
                        {errors.incident_number && (
                            <p className="mt-1 text-sm text-destructive">{errors.incident_number}</p>
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="incident_date">Incident date *</Label>
                            <Input
                                id="incident_date"
                                type="date"
                                value={data.incident_date}
                                onChange={(e) => setData('incident_date', e.target.value)}
                                className="mt-1"
                            />
                            {errors.incident_date && (
                                <p className="mt-1 text-sm text-destructive">{errors.incident_date}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="incident_time">Incident time *</Label>
                            <Input
                                id="incident_time"
                                type="time"
                                value={data.incident_time}
                                onChange={(e) => setData('incident_time', e.target.value)}
                                className="mt-1"
                            />
                            {errors.incident_time && (
                                <p className="mt-1 text-sm text-destructive">{errors.incident_time}</p>
                            )}
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="incident_type">Incident type *</Label>
                            <select
                                id="incident_type"
                                value={data.incident_type}
                                onChange={(e) => setData('incident_type', e.target.value)}
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {incidentTypes.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <Label htmlFor="severity">Severity *</Label>
                            <select
                                id="severity"
                                value={data.severity}
                                onChange={(e) => setData('severity', e.target.value)}
                                className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                {severities.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="description">Description *</Label>
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="mt-1 flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                        {errors.description && (
                            <p className="mt-1 text-sm text-destructive">{errors.description}</p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="location_description">Location</Label>
                        <Input
                            id="location_description"
                            value={data.location_description}
                            onChange={(e) => setData('location_description', e.target.value)}
                            className="mt-1"
                        />
                    </div>
                    <div>
                        <Label htmlFor="fault_determination">Fault determination</Label>
                        <select
                            id="fault_determination"
                            value={data.fault_determination}
                            onChange={(e) => setData('fault_determination', e.target.value)}
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {faultDeterminations.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label htmlFor="photos">Photos</Label>
                        <input
                            id="photos"
                            type="file"
                            accept="image/*"
                            multiple
                            className="mt-1 block w-full text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground file:transition-colors"
                            onChange={(e) =>
                                setData('photos', e.target.files ? Array.from(e.target.files) : [])
                            }
                        />
                        {errors.photos && (
                            <p className="mt-1 text-sm text-destructive">{errors.photos}</p>
                        )}
                        {data.photos.length > 0 && (
                            <p className="mt-1 text-sm text-muted-foreground">
                                {data.photos.length} file(s) selected
                            </p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Create incident
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/incidents">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
