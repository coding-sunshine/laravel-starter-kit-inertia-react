import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface DashcamClip {
    id: number;
    event_type: string;
    status: string;
    recorded_at: string;
    clip_url?: string;
    vehicle?: { id: number; registration: string };
    driver?: { id: number; first_name: string; last_name: string };
    incident?: { id: number; incident_number: string };
}
interface Props { dashcamClip: DashcamClip; }

export default function FleetDashcamClipsShow({ dashcamClip }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Dashcam clips', href: '/fleet/dashcam-clips' },
        { title: 'View', href: `/fleet/dashcam-clips/${dashcamClip.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Dashcam clip" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Dashcam clip</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/dashcam-clips/${dashcamClip.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/dashcam-clips">Back to list</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Event type:</span> {dashcamClip.event_type}</p>
                        <p><span className="font-medium">Status:</span> {dashcamClip.status}</p>
                        <p><span className="font-medium">Recorded:</span> {new Date(dashcamClip.recorded_at).toLocaleString()}</p>
                        {dashcamClip.vehicle && <p><span className="font-medium">Vehicle:</span> {dashcamClip.vehicle.registration}</p>}
                        {dashcamClip.driver && <p><span className="font-medium">Driver:</span> {dashcamClip.driver.first_name} {dashcamClip.driver.last_name}</p>}
                        {dashcamClip.incident && <p><span className="font-medium">Incident:</span> {dashcamClip.incident.incident_number}</p>}
                        {dashcamClip.clip_url && <p><span className="font-medium">Clip URL:</span> <a href={dashcamClip.clip_url} target="_blank" rel="noopener noreferrer" className="text-primary underline">View</a></p>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
