import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function FleetTrailersCreate() {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/trailers' }, { title: 'Trailers', href: '/fleet/trailers' }, { title: 'Create', href: '/fleet/trailers/create' }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New trailer" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New trailer</h1>
                <Button variant="outline" asChild><Link href="/fleet/trailers">Back to trailers</Link></Button>
            </div>
        </AppLayout>
    );
}
