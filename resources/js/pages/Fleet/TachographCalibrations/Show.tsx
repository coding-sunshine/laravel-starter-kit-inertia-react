import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Calibration {
    id: number;
    calibration_date: string;
    due_date?: string;
    certificate_reference?: string;
    status: string;
    vehicle?: { id: number; registration: string };
    telematics_device?: { id: number; device_id: string };
}
interface Props {
    tachographCalibration: Calibration;
}

export default function TachographCalibrationsShow({
    tachographCalibration,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        {
            title: 'Tachograph calibrations',
            href: '/fleet/tachograph-calibrations',
        },
        {
            title: 'Calibration',
            href: `/fleet/tachograph-calibrations/${tachographCalibration.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Tachograph calibration" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        Tachograph calibration
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/tachograph-calibrations/${tachographCalibration.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/tachograph-calibrations">
                                Back
                            </Link>
                        </Button>
                    </div>
                </div>
                <dl className="max-w-md space-y-2 rounded-lg border p-6">
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Calibration date
                        </dt>
                        <dd className="font-medium">
                            {tachographCalibration.calibration_date}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Due date
                        </dt>
                        <dd>{tachographCalibration.due_date ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Vehicle
                        </dt>
                        <dd>
                            {tachographCalibration.vehicle?.registration ?? '—'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Telematics device
                        </dt>
                        <dd>
                            {tachographCalibration.telematics_device
                                ?.device_id ?? '—'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Certificate reference
                        </dt>
                        <dd>
                            {tachographCalibration.certificate_reference ?? '—'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Status
                        </dt>
                        <dd>{tachographCalibration.status}</dd>
                    </div>
                </dl>
            </div>
        </AppLayout>
    );
}
