import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props { driver: { id: number; first_name: string; last_name: string; status: string; license_number: string } }

export default function FleetDriversShow({ driver }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/drivers' }, { title: 'Drivers', href: '/fleet/drivers' }, { title: `${driver.first_name} ${driver.last_name}`, href: `/fleet/drivers/${driver.id}` }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${driver.first_name} ${driver.last_name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">{driver.first_name} {driver.last_name}</h1>
                <p className="text-muted-foreground">License: {driver.license_number} · Status: {driver.status}</p>
                <Button variant="outline" asChild><Link href="/fleet/drivers">Back to drivers</Link></Button>
            </div>
        </AppLayout>
    );
}
