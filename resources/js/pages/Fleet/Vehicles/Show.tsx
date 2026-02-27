import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props { vehicle: { id: number; registration: string; make: string; model: string; status: string; fuel_type: string } }

export default function FleetVehiclesShow({ vehicle }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/vehicles' }, { title: 'Vehicles', href: '/fleet/vehicles' }, { title: vehicle.registration, href: `/fleet/vehicles/${vehicle.id}` }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${vehicle.registration}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">{vehicle.registration}</h1>
                <p className="text-muted-foreground">{vehicle.make} {vehicle.model} · {vehicle.fuel_type} · {vehicle.status}</p>
                <Button variant="outline" asChild><Link href="/fleet/vehicles">Back to vehicles</Link></Button>
            </div>
        </AppLayout>
    );
}
