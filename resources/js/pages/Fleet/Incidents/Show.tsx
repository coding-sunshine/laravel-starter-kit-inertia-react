import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface IncidentRecord {
    id: number;
    incident_number: string;
    incident_date?: string;
    incident_timestamp?: string;
    incident_type: string;
    severity: string;
    status: string;
    description?: string;
    location_description?: string | null;
    fault_determination?: string | null;
    vehicle?: { id: number; registration: string };
    driver?: { id: number; first_name: string; last_name: string };
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
}

function isImage(mimeType: string): boolean {
    return (mimeType || '').startsWith('image/');
}

export default function FleetIncidentsShow({ incident, mediaItems }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/incidents' },
        { title: 'Incidents', href: '/fleet/incidents' },
        { title: incident.incident_number, href: `/fleet/incidents/${incident.id}` },
    ];
    const dateDisplay = incident.incident_timestamp
        ? new Date(incident.incident_timestamp).toLocaleString()
        : incident.incident_date
          ? new Date(incident.incident_date).toLocaleDateString()
          : '—';
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${incident.incident_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">{incident.incident_number}</h1>
                    <Button variant="outline" asChild>
                        <Link href="/fleet/incidents">Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Incident details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Number:</span> {incident.incident_number}
                        </p>
                        <p>
                            <span className="font-medium">Date:</span> {dateDisplay}
                        </p>
                        <p>
                            <span className="font-medium">Type:</span> {incident.incident_type}
                        </p>
                        <p>
                            <span className="font-medium">Severity:</span> {incident.severity}
                        </p>
                        <p>
                            <span className="font-medium">Status:</span> {incident.status}
                        </p>
                        {incident.vehicle && (
                            <p>
                                <span className="font-medium">Vehicle:</span>{' '}
                                <Link
                                    href={`/fleet/vehicles/${incident.vehicle.id}`}
                                    className="underline"
                                >
                                    {incident.vehicle.registration}
                                </Link>
                            </p>
                        )}
                        {incident.driver && (
                            <p>
                                <span className="font-medium">Driver:</span>{' '}
                                <Link
                                    href={`/fleet/drivers/${incident.driver.id}`}
                                    className="underline"
                                >
                                    {incident.driver.first_name} {incident.driver.last_name}
                                </Link>
                            </p>
                        )}
                        {incident.description && (
                            <p>
                                <span className="font-medium">Description:</span> {incident.description}
                            </p>
                        )}
                        {incident.location_description && (
                            <p>
                                <span className="font-medium">Location:</span>{' '}
                                {incident.location_description}
                            </p>
                        )}
                        {incident.fault_determination && (
                            <p>
                                <span className="font-medium">Fault determination:</span>{' '}
                                {incident.fault_determination}
                            </p>
                        )}
                    </CardContent>
                </Card>
                {mediaItems.length > 0 && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Photos & documents</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-4">
                                {mediaItems.map((item) =>
                                    isImage(item.mime_type) ? (
                                        <a
                                            key={item.id}
                                            href={item.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="block"
                                        >
                                            <img
                                                src={item.url}
                                                alt=""
                                                className="h-32 w-auto rounded border object-cover"
                                            />
                                        </a>
                                    ) : (
                                        <a
                                            key={item.id}
                                            href={item.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="flex h-32 min-w-[10rem] items-center justify-center rounded border bg-muted/50 px-4 py-2 text-sm font-medium text-foreground hover:bg-muted"
                                        >
                                            📄 {item.file_name}
                                        </a>
                                    )
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
