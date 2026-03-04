import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface ParkingAllocation {
    id: number;
    allocated_from: string;
    allocated_to?: string;
    spot_identifier?: string;
    cost?: number;
    vehicle?: { id: number; registration: string };
    location?: { id: number; name: string };
}
interface Props {
    parkingAllocation: ParkingAllocation;
}

export default function FleetParkingAllocationsShow({
    parkingAllocation,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Parking allocations', href: '/fleet/parking-allocations' },
        {
            title: 'View',
            href: `/fleet/parking-allocations/${parkingAllocation.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Parking allocation" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Parking allocation –{' '}
                        {parkingAllocation.vehicle?.registration ?? '—'}
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/parking-allocations/${parkingAllocation.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/parking-allocations">
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
                        {parkingAllocation.vehicle && (
                            <p>
                                <span className="font-medium">Vehicle:</span>{' '}
                                {parkingAllocation.vehicle.registration}
                            </p>
                        )}
                        {parkingAllocation.location && (
                            <p>
                                <span className="font-medium">Location:</span>{' '}
                                {parkingAllocation.location.name}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">From:</span>{' '}
                            {new Date(
                                parkingAllocation.allocated_from,
                            ).toLocaleString()}
                        </p>
                        <p>
                            <span className="font-medium">To:</span>{' '}
                            {parkingAllocation.allocated_to
                                ? new Date(
                                      parkingAllocation.allocated_to,
                                  ).toLocaleString()
                                : '—'}
                        </p>
                        {parkingAllocation.spot_identifier && (
                            <p>
                                <span className="font-medium">Spot:</span>{' '}
                                {parkingAllocation.spot_identifier}
                            </p>
                        )}
                        {parkingAllocation.cost != null && (
                            <p>
                                <span className="font-medium">Cost:</span>{' '}
                                {Number(parkingAllocation.cost)}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
