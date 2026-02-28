import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Record {
    id: number;
    record_date: string;
    fatigue_level?: number;
    rest_hours?: string;
    sleep_quality?: string;
    mood?: string;
    notes?: string;
    driver?: { id: number; first_name: string; last_name: string };
}
interface Props { driverWellnessRecord: Record; }

export default function FleetDriverWellnessRecordsShow({ driverWellnessRecord }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Driver wellness records', href: '/fleet/driver-wellness-records' },
        { title: 'Record', href: `/fleet/driver-wellness-records/${driverWellnessRecord.id}` },
    ];
    const driverName = driverWellnessRecord.driver ? driverWellnessRecord.driver.first_name + ' ' + driverWellnessRecord.driver.last_name : '—';
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Wellness record" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Wellness record</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/driver-wellness-records/${driverWellnessRecord.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/driver-wellness-records">Back</Link></Button>
                    </div>
                </div>
                <dl className="max-w-md space-y-2 rounded-lg border p-6">
                    <div><dt className="text-sm text-muted-foreground">Date</dt><dd className="font-medium">{driverWellnessRecord.record_date}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Driver</dt><dd className="font-medium">{driverName}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Fatigue level</dt><dd>{driverWellnessRecord.fatigue_level ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Rest hours</dt><dd>{driverWellnessRecord.rest_hours ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Sleep quality</dt><dd>{driverWellnessRecord.sleep_quality ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Mood</dt><dd>{driverWellnessRecord.mood ?? '—'}</dd></div>
                    {driverWellnessRecord.notes && <div><dt className="text-sm text-muted-foreground">Notes</dt><dd className="whitespace-pre-wrap">{driverWellnessRecord.notes}</dd></div>}
                </dl>
            </div>
        </AppLayout>
    );
}
