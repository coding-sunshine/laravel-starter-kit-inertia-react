import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function FleetGaragesCreate() {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/garages' }, { title: 'Garages', href: '/fleet/garages' }, { title: 'Create', href: '/fleet/garages/create' }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New garage" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New garage</h1>
                <Button variant="outline" asChild><Link href="/fleet/garages">Back to garages</Link></Button>
            </div>
        </AppLayout>
    );
}
