import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props { driver: { id: number; first_name: string; last_name: string } }

export default function FleetDriversEdit({ driver }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/drivers' }, { title: 'Drivers', href: '/fleet/drivers' }, { title: `${driver.first_name} ${driver.last_name}`, href: `/fleet/drivers/${driver.id}` }, { title: 'Edit', href: `/fleet/drivers/${driver.id}/edit` }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${driver.first_name} ${driver.last_name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit driver</h1>
                <Button variant="outline" asChild><Link href={`/fleet/drivers/${driver.id}`}>Back</Link></Button>
            </div>
        </AppLayout>
    );
}
