import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface GreyFleetVehicle {
    id: number;
    registration?: string;
    make?: string;
    model?: string;
    year?: number;
    colour?: string;
    fuel_type?: string;
    engine_cc?: number;
    is_approved?: boolean;
    approval_date?: string;
    notes?: string;
    is_active?: boolean;
    user?: { id: number; name: string };
    driver?: { id: number; first_name: string; last_name: string };
}
interface Props {
    greyFleetVehicle: GreyFleetVehicle;
}

export default function FleetGreyFleetVehiclesShow({
    greyFleetVehicle,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Grey fleet vehicles', href: '/fleet/grey-fleet-vehicles' },
        {
            title: 'View',
            href: `/fleet/grey-fleet-vehicles/${greyFleetVehicle.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Grey fleet vehicle" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {greyFleetVehicle.registration ?? 'Grey fleet vehicle'}
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/grey-fleet-vehicles/${greyFleetVehicle.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/grey-fleet-vehicles">
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
                        {greyFleetVehicle.registration && (
                            <p>
                                <span className="font-medium">
                                    Registration:
                                </span>{' '}
                                {greyFleetVehicle.registration}
                            </p>
                        )}
                        {(greyFleetVehicle.make || greyFleetVehicle.model) && (
                            <p>
                                <span className="font-medium">
                                    Make / Model:
                                </span>{' '}
                                {[greyFleetVehicle.make, greyFleetVehicle.model]
                                    .filter(Boolean)
                                    .join(' ')}
                            </p>
                        )}
                        {greyFleetVehicle.year && (
                            <p>
                                <span className="font-medium">Year:</span>{' '}
                                {greyFleetVehicle.year}
                            </p>
                        )}
                        {greyFleetVehicle.colour && (
                            <p>
                                <span className="font-medium">Colour:</span>{' '}
                                {greyFleetVehicle.colour}
                            </p>
                        )}
                        {greyFleetVehicle.fuel_type && (
                            <p>
                                <span className="font-medium">Fuel type:</span>{' '}
                                {greyFleetVehicle.fuel_type}
                            </p>
                        )}
                        {greyFleetVehicle.engine_cc != null && (
                            <p>
                                <span className="font-medium">
                                    Engine (cc):
                                </span>{' '}
                                {greyFleetVehicle.engine_cc}
                            </p>
                        )}
                        {greyFleetVehicle.user && (
                            <p>
                                <span className="font-medium">Owner:</span>{' '}
                                {greyFleetVehicle.user.name}
                            </p>
                        )}
                        {greyFleetVehicle.driver && (
                            <p>
                                <span className="font-medium">Driver:</span>{' '}
                                {greyFleetVehicle.driver.first_name}{' '}
                                {greyFleetVehicle.driver.last_name}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">Approved:</span>{' '}
                            {greyFleetVehicle.is_approved ? 'Yes' : 'No'}
                        </p>
                        {greyFleetVehicle.approval_date && (
                            <p>
                                <span className="font-medium">
                                    Approval date:
                                </span>{' '}
                                {new Date(
                                    greyFleetVehicle.approval_date,
                                ).toLocaleDateString()}
                            </p>
                        )}
                        {greyFleetVehicle.notes && (
                            <p>
                                <span className="font-medium">Notes:</span>{' '}
                                {greyFleetVehicle.notes}
                            </p>
                        )}
                        {greyFleetVehicle.is_active != null && (
                            <p>
                                <span className="font-medium">Active:</span>{' '}
                                {greyFleetVehicle.is_active ? 'Yes' : 'No'}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
