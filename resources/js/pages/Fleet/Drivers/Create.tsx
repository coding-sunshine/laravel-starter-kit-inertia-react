import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function FleetDriversCreate() {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/drivers' }, { title: 'Drivers', href: '/fleet/drivers' }, { title: 'Create', href: '/fleet/drivers/create' }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New driver" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New driver</h1>
                <Button variant="outline" asChild><Link href="/fleet/drivers">Back to drivers</Link></Button>
            </div>
        </AppLayout>
    );
}
