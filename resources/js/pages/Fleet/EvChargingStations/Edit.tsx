import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props { evChargingStation: { id: number; name: string } }

export default function FleetEvChargingStationsEdit({ evChargingStation }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/ev-charging-stations' }, { title: 'EV charging stations', href: '/fleet/ev-charging-stations' }, { title: evChargingStation.name, href: `/fleet/ev-charging-stations/${evChargingStation.id}` }, { title: 'Edit', href: `/fleet/ev-charging-stations/${evChargingStation.id}/edit` }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${evChargingStation.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit EV charging station</h1>
                <Button variant="outline" asChild><Link href={`/fleet/ev-charging-stations/${evChargingStation.id}`}>Back</Link></Button>
            </div>
        </AppLayout>
    );
}
