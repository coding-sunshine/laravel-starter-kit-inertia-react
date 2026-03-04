import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface EvChargingSession {
    id: number;
    session_id: string;
    start_timestamp: string;
    end_timestamp?: string;
    session_type: string;
    energy_delivered_kwh?: string;
    cost?: string;
    vehicle?: { id: number; registration: string };
    driver?: { id: number; first_name: string; last_name: string };
    charging_station?: { id: number; name: string };
}
interface Props {
    evChargingSession: EvChargingSession;
}

export default function FleetEvChargingSessionsShow({
    evChargingSession,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'EV charging sessions', href: '/fleet/ev-charging-sessions' },
        {
            title: 'View',
            href: `/fleet/ev-charging-sessions/${evChargingSession.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – EV charging session" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        EV charging session
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/ev-charging-sessions/${evChargingSession.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/ev-charging-sessions">
                                Back to list
                            </Link>
                        </Button>
                    </div>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Session ID:</span>{' '}
                            {evChargingSession.session_id}
                        </p>
                        <p>
                            <span className="font-medium">Start:</span>{' '}
                            {new Date(
                                evChargingSession.start_timestamp,
                            ).toLocaleString()}
                        </p>
                        {evChargingSession.end_timestamp && (
                            <p>
                                <span className="font-medium">End:</span>{' '}
                                {new Date(
                                    evChargingSession.end_timestamp,
                                ).toLocaleString()}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">Type:</span>{' '}
                            {evChargingSession.session_type}
                        </p>
                        {evChargingSession.energy_delivered_kwh != null && (
                            <p>
                                <span className="font-medium">
                                    Energy (kWh):
                                </span>{' '}
                                {evChargingSession.energy_delivered_kwh}
                            </p>
                        )}
                        {evChargingSession.cost != null && (
                            <p>
                                <span className="font-medium">Cost:</span>{' '}
                                {evChargingSession.cost}
                            </p>
                        )}
                        {evChargingSession.vehicle && (
                            <p>
                                <span className="font-medium">Vehicle:</span>{' '}
                                {evChargingSession.vehicle.registration}
                            </p>
                        )}
                        {evChargingSession.driver && (
                            <p>
                                <span className="font-medium">Driver:</span>{' '}
                                {evChargingSession.driver.first_name}{' '}
                                {evChargingSession.driver.last_name}
                            </p>
                        )}
                        {evChargingSession.charging_station && (
                            <p>
                                <span className="font-medium">Station:</span>{' '}
                                {evChargingSession.charging_station.name}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
