import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function FleetGeofencesCreate() {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/geofences' }, { title: 'Geofences', href: '/fleet/geofences' }, { title: 'Create', href: '/fleet/geofences/create' }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New geofence" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New geofence</h1>
                <Button variant="outline" asChild><Link href="/fleet/geofences">Back to geofences</Link></Button>
            </div>
        </AppLayout>
    );
}
