import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface BehaviorEventRecord {
    id: number;
    event_type: string;
    occurred_at: string;
    severity: string | null;
    details: Record<string, unknown> | null;
    vehicle?: { id: number; registration: string };
    driver?: { id: number; first_name: string; last_name: string };
    trip?: { id: number };
}
interface Props { behaviorEvent: BehaviorEventRecord; }

export default function FleetBehaviorEventsShow({ behaviorEvent }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/behavior-events' },
        { title: 'Behavior events', href: '/fleet/behavior-events' },
        { title: `Event #${behaviorEvent.id}`, href: `/fleet/behavior-events/${behaviorEvent.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Behavior event #${behaviorEvent.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Behavior event #{behaviorEvent.id}</h1>
                        <p className="text-muted-foreground">
                            {behaviorEvent.event_type}
                            {behaviorEvent.vehicle && <> · Vehicle: <Link href={`/fleet/vehicles/${behaviorEvent.vehicle.id}`} className="underline">{behaviorEvent.vehicle.registration}</Link></>}
                            {behaviorEvent.driver && <> · Driver: <Link href={`/fleet/drivers/${behaviorEvent.driver.id}`} className="underline">{behaviorEvent.driver.first_name} {behaviorEvent.driver.last_name}</Link></>}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/fleet/behavior-events">Back to behavior events</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-base">Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Occurred:</span> {new Date(behaviorEvent.occurred_at).toLocaleString()}</p>
                        <p><span className="font-medium">Severity:</span> {behaviorEvent.severity ?? '—'}</p>
                        {behaviorEvent.trip && (
                            <p><span className="font-medium">Trip:</span> <Link href={`/fleet/trips/${behaviorEvent.trip.id}`} className="underline">Trip #{behaviorEvent.trip.id}</Link></p>
                        )}
                        {behaviorEvent.details && Object.keys(behaviorEvent.details).length > 0 && (
                            <pre className="mt-2 rounded border bg-muted/50 p-3 text-xs">{JSON.stringify(behaviorEvent.details, null, 2)}</pre>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
