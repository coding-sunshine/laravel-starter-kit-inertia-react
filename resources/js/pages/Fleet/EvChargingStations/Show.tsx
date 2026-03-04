import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Props {
    evChargingStation: {
        id: number;
        name: string;
        operator: string | null;
        status: string;
    };
}

export default function FleetEvChargingStationsShow({
    evChargingStation,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/ev-charging-stations' },
        { title: 'EV charging stations', href: '/fleet/ev-charging-stations' },
        {
            title: evChargingStation.name,
            href: `/fleet/ev-charging-stations/${evChargingStation.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${evChargingStation.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">
                    {evChargingStation.name}
                </h1>
                <p className="text-muted-foreground">
                    {evChargingStation.operator &&
                        `${evChargingStation.operator} · `}
                    Status: {evChargingStation.status}
                </p>
                <Button variant="outline" asChild>
                    <Link href="/fleet/ev-charging-stations">
                        Back to EV charging stations
                    </Link>
                </Button>
            </div>
        </AppLayout>
    );
}
