import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface DriverQualification { id: number; qualification_name: string; qualification_type: string; status: string; issue_date?: string; expiry_date?: string; driver?: { first_name: string; last_name: string }; }
interface Props { driverQualification: DriverQualification; }

export default function FleetDriverQualificationsShow({ driverQualification }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Driver qualifications', href: '/fleet/driver-qualifications' },
        { title: 'View', href: `/fleet/driver-qualifications/${driverQualification.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Driver qualification" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{driverQualification.qualification_name}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/driver-qualifications/${driverQualification.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/driver-qualifications">Back to list</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Type:</span> {driverQualification.qualification_type}</p>
                        <p><span className="font-medium">Status:</span> {driverQualification.status}</p>
                        {driverQualification.driver && <p><span className="font-medium">Driver:</span> {driverQualification.driver.first_name} {driverQualification.driver.last_name}</p>}
                        {driverQualification.issue_date && <p><span className="font-medium">Issue date:</span> {new Date(driverQualification.issue_date).toLocaleDateString()}</p>}
                        {driverQualification.expiry_date && <p><span className="font-medium">Expiry date:</span> {new Date(driverQualification.expiry_date).toLocaleDateString()}</p>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
