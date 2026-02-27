import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props {
    fuelStation: { id: number; name: string };
}

export default function FleetFuelStationsEdit({ fuelStation }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/fuel-stations' },
        { title: 'Fuel stations', href: '/fleet/fuel-stations' },
        { title: fuelStation.name, href: `/fleet/fuel-stations/${fuelStation.id}` },
        { title: 'Edit', href: `/fleet/fuel-stations/${fuelStation.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${fuelStation.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit fuel station</h1>
                <Button variant="outline" asChild>
                    <Link href={`/fleet/fuel-stations/${fuelStation.id}`}>Back</Link>
                </Button>
            </div>
        </AppLayout>
    );
}
