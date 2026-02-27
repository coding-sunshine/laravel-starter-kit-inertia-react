import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function FleetEvChargingStationsCreate() {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/ev-charging-stations' }, { title: 'EV charging stations', href: '/fleet/ev-charging-stations' }, { title: 'Create', href: '/fleet/ev-charging-stations/create' }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New EV charging station" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New EV charging station</h1>
                <Button variant="outline" asChild><Link href="/fleet/ev-charging-stations">Back to EV charging stations</Link></Button>
            </div>
        </AppLayout>
    );
}
