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
interface IncidentRecord {
    id: number;
    vehicle_id: number;
    driver_id?: number | null;
    incident_number: string;
    incident_date?: string;
    incident_time?: string;
    incident_timestamp?: string;
    incident_type: string;
    severity: string;
    description: string;
    location_description?: string | null;
    fault_determination?: string | null;
    status: string;
}
interface MediaItem {
    id: number;
    url: string;
    mime_type: string;
    file_name: string;
}
interface Props {
    incident: IncidentRecord;
    mediaItems: MediaItem[];
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; first_name: string; last_name: string }[];
    incidentTypes: Option[];
    severities: Option[];
    statuses: Option[];
    faultDeterminations: Option[];
}

function getIncidentDate(incident: IncidentRecord): string {
    if (incident.incident_date) {
        const d = incident.incident_date;
        return d.slice(0, 10);
    }
    if (incident.incident_timestamp) {
        return incident.incident_timestamp.slice(0, 10);
    }
    return new Date().toISOString().slice(0, 10);
}

function getIncidentTime(incident: IncidentRecord): string {
    if (incident.incident_time) {
        const t = incident.incident_time;
        return t.length === 5 ? t : t.slice(0, 5);
    }
    if (incident.incident_timestamp) {
        return incident.incident_timestamp.slice(11, 16);
    }
    return '00:00';
}

export default function FleetIncidentsEdit({
    incident,
    mediaItems: _mediaItems,
    vehicles,
    drivers,
    incidentTypes,
    severities,
    statuses,
    faultDeterminations,
}: Props) {
    const form = useForm({
        vehicle_id: incident.vehicle_id,
        driver_id: (incident.driver_id ?? '') as number | '',
        incident_number: incident.incident_number,
        incident_date: getIncidentDate(incident),
        incident_time: getIncidentTime(incident),
        incident_type: incident.incident_type,
        severity: incident.severity,
        description: incident.description,
        location_description: incident.location_description ?? '',
        fault_determination:
            incident.fault_determination ?? faultDeterminations[0]?.value ?? '',
        status: incident.status,
        photos: [] as File[],
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/incidents' },
        { title: 'Incidents', href: '/fleet/incidents' },
        {
            title: incident.incident_number,
            href: `/fleet/incidents/${incident.id}`,
        },
        { title: 'Edit', href: `/fleet/incidents/${incident.id}/edit` },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            driver_id: d.driver_id === '' ? undefined : d.driver_id,
            _method: 'PUT',
        }));
        form.post(`/fleet/incidents/${incident.id}`, { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${incident.incident_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit incident</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label>Vehicle *</Label>
                        <select
                            value={data.vehicle_id}
                            onChange={(e) =>
                                setData('vehicle_id', Number(e.target.value))
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
                        >
                            {vehicles.map((v) => (
                                <option key={v.id} value={v.id}>
                                    {v.registration}
                                </option>
                            ))}
                        </select>
                        {errors.vehicle_id && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.vehicle_id}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label>Driver</Label>
                        <select
                            value={
                                data.driver_id === ''
                                    ? ''
                                    : String(data.driver_id)
                            }
                            onChange={(e) =>
                                setData(
                                    'driver_id',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
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
                        <Label>Incident number *</Label>
                        <Input
                            value={data.incident_number}
                            onChange={(e) =>
                                setData('incident_number', e.target.value)
                            }
                            className="mt-1"
                        />
                        {errors.incident_number && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.incident_number}
                            </p>
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Incident date *</Label>
                            <Input
                                type="date"
                                value={data.incident_date}
                                onChange={(e) =>
                                    setData('incident_date', e.target.value)
                                }
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <Label>Incident time *</Label>
                            <Input
                                type="time"
                                value={data.incident_time}
                                onChange={(e) =>
                                    setData('incident_time', e.target.value)
                                }
                                className="mt-1"
                            />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label>Incident type</Label>
                            <select
                                value={data.incident_type}
                                onChange={(e) =>
                                    setData('incident_type', e.target.value)
                                }
                                className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
                            >
                                {incidentTypes.map((o) => (
                                    <option key={o.value} value={o.value}>
                                        {o.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <Label>Severity</Label>
                            <select
                                value={data.severity}
                                onChange={(e) =>
                                    setData('severity', e.target.value)
                                }
                                className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
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
                        <Label>Description *</Label>
                        <textarea
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            rows={3}
                            className="mt-1 flex w-full rounded-md border border-input px-3 py-2 text-sm"
                        />
                        {errors.description && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.description}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label>Location</Label>
                        <Input
                            value={data.location_description}
                            onChange={(e) =>
                                setData('location_description', e.target.value)
                            }
                            className="mt-1"
                        />
                    </div>
                    <div>
                        <Label>Fault determination</Label>
                        <select
                            value={data.fault_determination}
                            onChange={(e) =>
                                setData('fault_determination', e.target.value)
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
                        >
                            {faultDeterminations.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label>Status *</Label>
                        <select
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="mt-1 flex h-9 w-full rounded-md border border-input px-3 py-1 text-sm"
                        >
                            {statuses.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label>Add more photos or documents</Label>
                        <input
                            type="file"
                            accept="image/*,.txt,.pdf,.docx,text/plain,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            multiple
                            className="mt-1 block w-full text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground"
                            onChange={(e) =>
                                setData(
                                    'photos',
                                    e.target.files
                                        ? Array.from(e.target.files)
                                        : [],
                                )
                            }
                        />
                        {data.photos.length > 0 && (
                            <p className="mt-1 text-sm text-muted-foreground">
                                {data.photos.length} new file(s) selected
                            </p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Update
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/fleet/incidents/${incident.id}`}>
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
