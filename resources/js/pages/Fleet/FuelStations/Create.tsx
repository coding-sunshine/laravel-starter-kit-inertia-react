import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function FleetFuelStationsCreate() {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/fuel-stations' }, { title: 'Fuel stations', href: '/fleet/fuel-stations' }, { title: 'Create', href: '/fleet/fuel-stations/create' }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New fuel station" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New fuel station</h1>
                <Button variant="outline" asChild><Link href="/fleet/fuel-stations">Back to fuel stations</Link></Button>
            </div>
        </AppLayout>
    );
}
