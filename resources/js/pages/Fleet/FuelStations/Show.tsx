import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props {
    fuelStation: { id: number; name: string; brand: string | null; address: string };
}

export default function FleetFuelStationsShow({ fuelStation }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/fuel-stations' },
        { title: 'Fuel stations', href: '/fleet/fuel-stations' },
        { title: fuelStation.name, href: `/fleet/fuel-stations/${fuelStation.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${fuelStation.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">{fuelStation.name}</h1>
                <p className="text-muted-foreground">
                    {fuelStation.brand ? `${fuelStation.brand} · ` : ''}
                    {fuelStation.address}
                </p>
                <Button variant="outline" asChild>
                    <Link href="/fleet/fuel-stations">Back to fuel stations</Link>
                </Button>
            </div>
        </AppLayout>
    );
}
