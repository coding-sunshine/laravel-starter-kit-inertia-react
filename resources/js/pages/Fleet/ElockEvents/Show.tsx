import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface ElockEvent {
    id: number;
    event_type: string;
    event_timestamp: string;
    lat?: number;
    lng?: number;
    device_id?: string;
    alert_sent: boolean;
    metadata?: Record<string, unknown>;
    vehicle?: { id: number; registration: string };
}
interface Props { elockEvent: ElockEvent; }

export default function FleetElockEventsShow({ elockEvent }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'E-lock events', href: '/fleet/e-lock-events' },
        { title: 'View', href: `/fleet/e-lock-events/${elockEvent.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – E-lock event" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">E-lock event #{elockEvent.id}</h1>
                    <Button variant="ghost" size="sm" asChild><Link href="/fleet/e-lock-events">Back to list</Link></Button>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Event type:</span> {elockEvent.event_type}</p>
                        <p><span className="font-medium">Timestamp:</span> {new Date(elockEvent.event_timestamp).toLocaleString()}</p>
                        {elockEvent.vehicle && <p><span className="font-medium">Vehicle:</span> {elockEvent.vehicle.registration}</p>}
                        {elockEvent.lat != null && <p><span className="font-medium">Lat:</span> {Number(elockEvent.lat)}</p>}
                        {elockEvent.lng != null && <p><span className="font-medium">Lng:</span> {Number(elockEvent.lng)}</p>}
                        {elockEvent.device_id && <p><span className="font-medium">Device ID:</span> {elockEvent.device_id}</p>}
                        <p><span className="font-medium">Alert sent:</span> {elockEvent.alert_sent ? 'Yes' : 'No'}</p>
                        {elockEvent.metadata && Object.keys(elockEvent.metadata).length > 0 && (
                            <p><span className="font-medium">Metadata:</span> <pre className="mt-1 rounded bg-muted p-2 text-xs">{JSON.stringify(elockEvent.metadata, null, 2)}</pre></p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
