import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props { geofence: { id: number; name: string; geofence_type: string; is_active: boolean } }

export default function FleetGeofencesShow({ geofence }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/geofences' }, { title: 'Geofences', href: '/fleet/geofences' }, { title: geofence.name, href: `/fleet/geofences/${geofence.id}` }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${geofence.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">{geofence.name}</h1>
                <p className="text-muted-foreground">Type: {geofence.geofence_type} · {geofence.is_active ? 'Active' : 'Inactive'}</p>
                <Button variant="outline" asChild><Link href="/fleet/geofences">Back to geofences</Link></Button>
            </div>
        </AppLayout>
    );
}
