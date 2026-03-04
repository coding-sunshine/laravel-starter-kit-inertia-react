import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface LeaseRecord {
    id: number;
    contract_id?: string | null;
    lessor_name: string;
    start_date: string;
    end_date: string;
    monthly_payment?: string | number | null;
    p11d_list_price?: string | number | null;
    status: string;
    vehicle?: { id: number; registration: string };
}
interface Props {
    vehicleLease: LeaseRecord;
}

export default function FleetVehicleLeasesShow({ vehicleLease }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicle leases', href: '/fleet/vehicle-leases' },
        {
            title: vehicleLease.lessor_name,
            href: `/fleet/vehicle-leases/${vehicleLease.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${vehicleLease.lessor_name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">
                        {vehicleLease.lessor_name}
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={`/fleet/vehicle-leases/${vehicleLease.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/vehicle-leases">Back</Link>
                        </Button>
                    </div>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Status:</span>{' '}
                            {vehicleLease.status}
                        </p>
                        <p>
                            <span className="font-medium">Start date:</span>{' '}
                            {new Date(
                                vehicleLease.start_date,
                            ).toLocaleDateString()}
                        </p>
                        <p>
                            <span className="font-medium">End date:</span>{' '}
                            {new Date(
                                vehicleLease.end_date,
                            ).toLocaleDateString()}
                        </p>
                        {vehicleLease.vehicle && (
                            <p>
                                <span className="font-medium">Vehicle:</span>{' '}
                                <Link
                                    href={`/fleet/vehicles/${vehicleLease.vehicle.id}`}
                                    className="underline"
                                >
                                    {vehicleLease.vehicle.registration}
                                </Link>
                            </p>
                        )}
                        {vehicleLease.contract_id && (
                            <p>
                                <span className="font-medium">
                                    Contract ID:
                                </span>{' '}
                                {vehicleLease.contract_id}
                            </p>
                        )}
                        {vehicleLease.monthly_payment != null && (
                            <p>
                                <span className="font-medium">
                                    Monthly payment:
                                </span>{' '}
                                {vehicleLease.monthly_payment}
                            </p>
                        )}
                        {vehicleLease.p11d_list_price != null && (
                            <p>
                                <span className="font-medium">
                                    P11D list price:
                                </span>{' '}
                                {vehicleLease.p11d_list_price}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
