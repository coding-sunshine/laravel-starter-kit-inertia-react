import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface AxleLoadReading {
    id: number;
    recorded_at: string;
    axle_weights_kg?: number[];
    total_weight_kg?: number;
    overload_flag: boolean;
    legal_limit_kg?: number;
    metadata?: Record<string, unknown>;
    vehicle?: { id: number; registration: string };
    trip?: { id: number } | null;
}
interface Props {
    axleLoadReading: AxleLoadReading;
}

export default function FleetAxleLoadReadingsShow({ axleLoadReading }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Axle load readings', href: '/fleet/axle-load-readings' },
        {
            title: 'View',
            href: `/fleet/axle-load-readings/${axleLoadReading.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Axle load reading" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Axle load reading #{axleLoadReading.id}
                    </h1>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet/axle-load-readings">
                            Back to list
                        </Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Recorded at:</span>{' '}
                            {new Date(
                                axleLoadReading.recorded_at,
                            ).toLocaleString()}
                        </p>
                        {axleLoadReading.vehicle && (
                            <p>
                                <span className="font-medium">Vehicle:</span>{' '}
                                {axleLoadReading.vehicle.registration}
                            </p>
                        )}
                        {axleLoadReading.trip && (
                            <p>
                                <span className="font-medium">Trip:</span> #
                                {axleLoadReading.trip.id}
                            </p>
                        )}
                        {axleLoadReading.total_weight_kg != null && (
                            <p>
                                <span className="font-medium">
                                    Total weight (kg):
                                </span>{' '}
                                {Number(axleLoadReading.total_weight_kg)}
                            </p>
                        )}
                        {axleLoadReading.legal_limit_kg != null && (
                            <p>
                                <span className="font-medium">
                                    Legal limit (kg):
                                </span>{' '}
                                {Number(axleLoadReading.legal_limit_kg)}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">Overload:</span>{' '}
                            {axleLoadReading.overload_flag ? 'Yes' : 'No'}
                        </p>
                        {axleLoadReading.axle_weights_kg &&
                            axleLoadReading.axle_weights_kg.length > 0 && (
                                <p>
                                    <span className="font-medium">
                                        Axle weights (kg):
                                    </span>{' '}
                                    {axleLoadReading.axle_weights_kg.join(', ')}
                                </p>
                            )}
                        {axleLoadReading.metadata &&
                            Object.keys(axleLoadReading.metadata).length >
                                0 && (
                                <p>
                                    <span className="font-medium">
                                        Metadata:
                                    </span>{' '}
                                    <pre className="mt-1 rounded bg-muted p-2 text-xs">
                                        {JSON.stringify(
                                            axleLoadReading.metadata,
                                            null,
                                            2,
                                        )}
                                    </pre>
                                </p>
                            )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
