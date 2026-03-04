import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface EvBatteryData {
    id: number;
    recorded_at: string;
    soc_percent: number;
    charging_status: string;
    vehicle?: { id: number; registration: string };
}
interface Props {
    evBatteryData: EvBatteryData;
}

export default function FleetEvBatteryDataShow({ evBatteryData }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'EV battery data', href: '/fleet/ev-battery-data' },
        { title: 'View', href: `/fleet/ev-battery-data/${evBatteryData.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – EV battery data" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">EV battery data</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/ev-battery-data/${evBatteryData.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/ev-battery-data">
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
                            <span className="font-medium">Recorded:</span>{' '}
                            {new Date(
                                evBatteryData.recorded_at,
                            ).toLocaleString()}
                        </p>
                        <p>
                            <span className="font-medium">SOC %:</span>{' '}
                            {evBatteryData.soc_percent}
                        </p>
                        <p>
                            <span className="font-medium">
                                Charging status:
                            </span>{' '}
                            {evBatteryData.charging_status}
                        </p>
                        {evBatteryData.vehicle && (
                            <p>
                                <span className="font-medium">Vehicle:</span>{' '}
                                {evBatteryData.vehicle.registration}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
