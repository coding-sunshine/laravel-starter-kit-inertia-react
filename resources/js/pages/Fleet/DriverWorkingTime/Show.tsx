import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface DriverWorkingTimeRecord {
    id: number;
    date: string;
    driving_time_minutes?: number | null;
    total_duty_time_minutes?: number | null;
    wtd_compliant?: boolean | null;
    driver?: { id: number; first_name: string; last_name: string };
}
interface Props { driverWorkingTime: DriverWorkingTimeRecord; }

export default function FleetDriverWorkingTimeShow({ driverWorkingTime }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/driver-working-time' },
        { title: 'Driver working time', href: '/fleet/driver-working-time' },
        { title: `#${driverWorkingTime.id}`, href: `/fleet/driver-working-time/${driverWorkingTime.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Driver working time #${driverWorkingTime.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">{`Driver working time #${driverWorkingTime.id}`}</h1>
                    <Button variant="outline" asChild><Link href="/fleet/driver-working-time">Back</Link></Button>
                </div>
                <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-base">Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Date:</span> {new Date(driverWorkingTime.date).toLocaleDateString()}</p>
                        <p><span className="font-medium">Driving time (min):</span> {driverWorkingTime.driving_time_minutes ?? '—'}</p>
                        <p><span className="font-medium">Total duty time (min):</span> {driverWorkingTime.total_duty_time_minutes ?? '—'}</p>
                        <p><span className="font-medium">WTD compliant:</span> {driverWorkingTime.wtd_compliant == null ? '—' : driverWorkingTime.wtd_compliant ? 'Yes' : 'No'}</p>
                        {driverWorkingTime.driver && <p><span className="font-medium">Driver:</span> <Link href={`/fleet/drivers/${driverWorkingTime.driver.id}`} className="underline">{driverWorkingTime.driver.first_name} {driverWorkingTime.driver.last_name}</Link></p>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
