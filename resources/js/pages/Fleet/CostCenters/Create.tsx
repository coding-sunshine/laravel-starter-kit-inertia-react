import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function FleetCostCentersCreate() {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/cost-centers' },
        { title: 'Cost centers', href: '/fleet/cost-centers' },
        { title: 'Create', href: '/fleet/cost-centers/create' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New cost center" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New cost center</h1>
                <p className="text-muted-foreground">Use the form to add a cost center (form fields can be expanded).</p>
                <Button variant="outline" asChild><Link href="/fleet/cost-centers">Back to cost centers</Link></Button>
            </div>
        </AppLayout>
    );
}
