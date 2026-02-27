import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function FleetVehiclesCreate() {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/vehicles' }, { title: 'Vehicles', href: '/fleet/vehicles' }, { title: 'Create', href: '/fleet/vehicles/create' }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New vehicle" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New vehicle</h1>
                <Button variant="outline" asChild><Link href="/fleet/vehicles">Back to vehicles</Link></Button>
            </div>
        </AppLayout>
    );
}
