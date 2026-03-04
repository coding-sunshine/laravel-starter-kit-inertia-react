import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface ServiceScheduleRecord {
    id: number;
    service_type: string;
    interval_type?: string;
    interval_value?: number;
    interval_unit?: string;
    next_service_due_date: string | null;
    next_service_due_mileage: number | null;
    is_active: boolean;
    vehicle?: { id: number; registration: string };
    preferredGarage?: { id: number; name: string };
}
interface Props {
    serviceSchedule: ServiceScheduleRecord;
}

export default function FleetServiceSchedulesShow({ serviceSchedule }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/service-schedules' },
        { title: 'Service schedules', href: '/fleet/service-schedules' },
        {
            title: `#${serviceSchedule.id}`,
            href: `/fleet/service-schedules/${serviceSchedule.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Service schedule #${serviceSchedule.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">{`Service schedule #${serviceSchedule.id}`}</h1>
                    <Button variant="outline" asChild>
                        <Link href="/fleet/service-schedules">Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Service type:</span>{' '}
                            {serviceSchedule.service_type}
                        </p>
                        {serviceSchedule.interval_type != null && (
                            <p>
                                <span className="font-medium">Interval:</span>{' '}
                                {serviceSchedule.interval_value}{' '}
                                {serviceSchedule.interval_unit}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">Next due date:</span>{' '}
                            {serviceSchedule.next_service_due_date
                                ? new Date(
                                      serviceSchedule.next_service_due_date,
                                  ).toLocaleDateString()
                                : '—'}
                        </p>
                        <p>
                            <span className="font-medium">
                                Next due mileage:
                            </span>{' '}
                            {serviceSchedule.next_service_due_mileage ?? '—'}
                        </p>
                        <p>
                            <span className="font-medium">Active:</span>{' '}
                            {serviceSchedule.is_active ? 'Yes' : 'No'}
                        </p>
                        {serviceSchedule.vehicle && (
                            <p>
                                <span className="font-medium">Vehicle:</span>{' '}
                                <Link
                                    href={`/fleet/vehicles/${serviceSchedule.vehicle.id}`}
                                    className="underline"
                                >
                                    {serviceSchedule.vehicle.registration}
                                </Link>
                            </p>
                        )}
                        {serviceSchedule.preferredGarage && (
                            <p>
                                <span className="font-medium">
                                    Preferred garage:
                                </span>{' '}
                                {serviceSchedule.preferredGarage.name}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
