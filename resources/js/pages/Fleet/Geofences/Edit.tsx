import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props { geofence: { id: number; name: string } }

export default function FleetGeofencesEdit({ geofence }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/geofences' }, { title: 'Geofences', href: '/fleet/geofences' }, { title: geofence.name, href: `/fleet/geofences/${geofence.id}` }, { title: 'Edit', href: `/fleet/geofences/${geofence.id}/edit` }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${geofence.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit geofence</h1>
                <Button variant="outline" asChild><Link href={`/fleet/geofences/${geofence.id}`}>Back</Link></Button>
            </div>
        </AppLayout>
    );
}
