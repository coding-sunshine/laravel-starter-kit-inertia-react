import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface PoolVehicleBooking {
    id: number;
    booking_start: string;
    booking_end: string;
    status?: string;
    purpose?: string;
    destination?: string;
    odometer_start?: number;
    odometer_end?: number;
    vehicle?: { id: number; registration: string };
    user?: { id: number; name: string };
}
interface Props {
    poolVehicleBooking: PoolVehicleBooking;
}

export default function FleetPoolVehicleBookingsShow({
    poolVehicleBooking,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        {
            title: 'Pool vehicle bookings',
            href: '/fleet/pool-vehicle-bookings',
        },
        {
            title: 'View',
            href: `/fleet/pool-vehicle-bookings/${poolVehicleBooking.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Pool vehicle booking" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Pool booking –{' '}
                        {poolVehicleBooking.vehicle?.registration ?? '—'}
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/pool-vehicle-bookings/${poolVehicleBooking.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/pool-vehicle-bookings">
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
                        {poolVehicleBooking.vehicle && (
                            <p>
                                <span className="font-medium">Vehicle:</span>{' '}
                                {poolVehicleBooking.vehicle.registration}
                            </p>
                        )}
                        {poolVehicleBooking.user && (
                            <p>
                                <span className="font-medium">User:</span>{' '}
                                {poolVehicleBooking.user.name}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">Start:</span>{' '}
                            {new Date(
                                poolVehicleBooking.booking_start,
                            ).toLocaleString()}
                        </p>
                        <p>
                            <span className="font-medium">End:</span>{' '}
                            {new Date(
                                poolVehicleBooking.booking_end,
                            ).toLocaleString()}
                        </p>
                        {poolVehicleBooking.status && (
                            <p>
                                <span className="font-medium">Status:</span>{' '}
                                {poolVehicleBooking.status}
                            </p>
                        )}
                        {poolVehicleBooking.purpose && (
                            <p>
                                <span className="font-medium">Purpose:</span>{' '}
                                {poolVehicleBooking.purpose}
                            </p>
                        )}
                        {poolVehicleBooking.destination && (
                            <p>
                                <span className="font-medium">
                                    Destination:
                                </span>{' '}
                                {poolVehicleBooking.destination}
                            </p>
                        )}
                        {poolVehicleBooking.odometer_start != null && (
                            <p>
                                <span className="font-medium">
                                    Odometer start:
                                </span>{' '}
                                {poolVehicleBooking.odometer_start}
                            </p>
                        )}
                        {poolVehicleBooking.odometer_end != null && (
                            <p>
                                <span className="font-medium">
                                    Odometer end:
                                </span>{' '}
                                {poolVehicleBooking.odometer_end}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
