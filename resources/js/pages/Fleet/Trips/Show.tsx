import {
    FleetMap,
    FleetMapMarker,
    FleetMapPolyline,
} from '@/components/fleet/FleetMap';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { useMemo } from 'react';

interface StopRecord {
    id: number;
    name: string | null;
    sort_order: number;
    location?: { id: number; name: string };
}
interface TripRecord {
    id: number;
    status: string;
    planned_start_time: string | null;
    planned_end_time: string | null;
    started_at: string | null;
    ended_at: string | null;
    distance_km: number | null;
    duration_minutes: number | null;
    notes: string | null;
    vehicle?: { id: number; registration: string };
    driver?: { id: number; first_name: string; last_name: string };
    route?: { id: number; name: string; stops?: StopRecord[] };
    start_location?: { id: number; name: string };
    end_location?: { id: number; name: string };
    waypoints?: {
        id: number;
        sequence: number;
        lat: number;
        lng: number;
        recorded_at: string | null;
    }[];
    behavior_events?: {
        id: number;
        event_type: string;
        occurred_at: string;
        severity: string | null;
    }[];
    behaviorEvents?: {
        id: number;
        event_type: string;
        occurred_at: string;
        severity: string | null;
    }[];
}
interface Props {
    trip: TripRecord;
}

export default function FleetTripsShow({ trip }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/trips' },
        { title: 'Trips', href: '/fleet/trips' },
        { title: `Trip #${trip.id}`, href: `/fleet/trips/${trip.id}` },
    ];
    const waypoints = trip.waypoints ?? [];
    const events = trip.behaviorEvents ?? trip.behavior_events ?? [];
    const path = useMemo(() => {
        return waypoints.map((w) => ({
            lat: Number(w.lat),
            lng: Number(w.lng),
        }));
    }, [waypoints]);
    const mapCenter = useMemo(() => {
        if (path.length === 0) return { lat: 51.5, lng: -0.1 };
        const sum = path.reduce(
            (a, p) => ({ lat: a.lat + p.lat, lng: a.lng + p.lng }),
            { lat: 0, lng: 0 },
        );
        return { lat: sum.lat / path.length, lng: sum.lng / path.length };
    }, [path]);
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Trip #${trip.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Trip #{trip.id}
                        </h1>
                        <p className="text-muted-foreground">
                            Status: {trip.status}
                            {trip.vehicle && (
                                <>
                                    {' '}
                                    · Vehicle:{' '}
                                    <Link
                                        href={`/fleet/vehicles/${trip.vehicle.id}`}
                                        className="underline"
                                    >
                                        {trip.vehicle.registration}
                                    </Link>
                                </>
                            )}
                            {trip.driver && (
                                <>
                                    {' '}
                                    · Driver:{' '}
                                    <Link
                                        href={`/fleet/drivers/${trip.driver.id}`}
                                        className="underline"
                                    >
                                        {trip.driver.first_name}{' '}
                                        {trip.driver.last_name}
                                    </Link>
                                </>
                            )}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/fleet/trips">Back to trips</Link>
                    </Button>
                </div>
                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Planned</CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm">
                            <p>
                                Start:{' '}
                                {trip.planned_start_time
                                    ? new Date(
                                          trip.planned_start_time,
                                      ).toLocaleString()
                                    : '—'}
                            </p>
                            <p>
                                End:{' '}
                                {trip.planned_end_time
                                    ? new Date(
                                          trip.planned_end_time,
                                      ).toLocaleString()
                                    : '—'}
                            </p>
                            {trip.start_location && (
                                <p>From: {trip.start_location.name}</p>
                            )}
                            {trip.end_location && (
                                <p>To: {trip.end_location.name}</p>
                            )}
                            {trip.route && (
                                <p>
                                    Route:{' '}
                                    <Link
                                        href={`/fleet/routes/${trip.route.id}`}
                                        className="underline"
                                    >
                                        {trip.route.name}
                                    </Link>
                                    {trip.route.stops &&
                                        trip.route.stops.length > 0 && (
                                            <>
                                                {' '}
                                                ·{' '}
                                                <Link
                                                    href={`/fleet/routes/${trip.route.id}`}
                                                    className="text-primary hover:underline"
                                                >
                                                    See stops
                                                </Link>
                                            </>
                                        )}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Actual</CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm">
                            <p>
                                Started:{' '}
                                {trip.started_at
                                    ? new Date(trip.started_at).toLocaleString()
                                    : '—'}
                            </p>
                            <p>
                                Ended:{' '}
                                {trip.ended_at
                                    ? new Date(trip.ended_at).toLocaleString()
                                    : '—'}
                            </p>
                            <p>
                                Distance:{' '}
                                {trip.distance_km != null
                                    ? `${trip.distance_km} km`
                                    : '—'}
                            </p>
                            <p>
                                Duration:{' '}
                                {trip.duration_minutes != null
                                    ? `${trip.duration_minutes} min`
                                    : '—'}
                            </p>
                        </CardContent>
                    </Card>
                </div>
                {trip.notes && (
                    <p className="text-sm text-muted-foreground">
                        {trip.notes}
                    </p>
                )}
                {trip.route?.stops && trip.route.stops.length > 0 && (
                    <Card className="border border-border">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center justify-between text-base font-semibold text-foreground">
                                Route stops
                                <Link
                                    href={`/fleet/routes/${trip.route.id}`}
                                    className="flex items-center gap-1 text-sm font-normal text-primary hover:underline"
                                >
                                    <MapPin className="size-3.5" />
                                    See full route
                                </Link>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ol className="list-inside list-decimal space-y-1 text-sm text-muted-foreground">
                                {trip.route.stops
                                    .slice()
                                    .sort((a, b) => a.sort_order - b.sort_order)
                                    .map((s) => (
                                        <li key={s.id}>
                                            {s.name ||
                                                s.location?.name ||
                                                `Stop ${s.sort_order + 1}`}
                                        </li>
                                    ))}
                            </ol>
                        </CardContent>
                    </Card>
                )}
                <Card className="overflow-hidden border border-border">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base font-semibold text-foreground">
                            Trip path
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="relative p-0">
                        <FleetMap
                            center={mapCenter}
                            zoom={path.length >= 2 ? 10 : 12}
                            mapContainerStyle={{
                                width: '100%',
                                height: '320px',
                            }}
                            className="rounded-b-lg"
                        >
                            {path.length >= 2 && (
                                <FleetMapPolyline path={path} />
                            )}
                            {path.length > 0 && (
                                <>
                                    <FleetMapMarker
                                        position={path[0]}
                                        title="Start"
                                        label={
                                            path.length === 1 ? 'S' : undefined
                                        }
                                    />
                                    {path.length > 1 && (
                                        <FleetMapMarker
                                            position={path[path.length - 1]}
                                            title="End"
                                            label="E"
                                        />
                                    )}
                                </>
                            )}
                        </FleetMap>
                        {path.length === 0 && (
                            <p className="absolute bottom-4 left-1/2 z-10 -translate-x-1/2 rounded bg-card/95 px-3 py-1.5 text-center text-sm text-muted-foreground shadow-sm">
                                No path recorded for this trip.
                            </p>
                        )}
                    </CardContent>
                </Card>
                {waypoints.length > 0 && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">
                                Waypoints
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto rounded-md border">
                                <table className="w-full min-w-[400px] text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="p-3 text-left font-medium">
                                                #
                                            </th>
                                            <th className="p-3 text-left font-medium">
                                                Lat
                                            </th>
                                            <th className="p-3 text-left font-medium">
                                                Lng
                                            </th>
                                            <th className="p-3 text-left font-medium">
                                                Recorded
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {waypoints.map((w) => (
                                            <tr
                                                key={w.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="p-3">
                                                    {w.sequence}
                                                </td>
                                                <td className="p-3">{w.lat}</td>
                                                <td className="p-3">{w.lng}</td>
                                                <td className="p-3">
                                                    {w.recorded_at
                                                        ? new Date(
                                                              w.recorded_at,
                                                          ).toLocaleString()
                                                        : '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
                {events.length > 0 && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">
                                Behavior events
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto rounded-md border">
                                <table className="w-full min-w-[400px] text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="p-3 text-left font-medium">
                                                Type
                                            </th>
                                            <th className="p-3 text-left font-medium">
                                                Severity
                                            </th>
                                            <th className="p-3 text-left font-medium">
                                                Occurred
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {events.map((ev) => (
                                            <tr
                                                key={ev.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="p-3">
                                                    {ev.event_type}
                                                </td>
                                                <td className="p-3">
                                                    {ev.severity ?? '—'}
                                                </td>
                                                <td className="p-3">
                                                    {new Date(
                                                        ev.occurred_at,
                                                    ).toLocaleString()}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
