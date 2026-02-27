import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function FleetOperatorLicencesCreate() {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/operator-licences' }, { title: 'Operator licences', href: '/fleet/operator-licences' }, { title: 'Create', href: '/fleet/operator-licences/create' }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New operator licence" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New operator licence</h1>
                <Button variant="outline" asChild><Link href="/fleet/operator-licences">Back to operator licences</Link></Button>
            </div>
        </AppLayout>
    );
}
