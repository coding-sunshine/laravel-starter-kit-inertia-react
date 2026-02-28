import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface FuelCardRecord {
    id: number;
    card_number: string;
    provider: string;
    card_type: string;
    status: string;
    expiry_date: string | null;
    assigned_vehicle?: { id: number; registration: string };
    assigned_driver?: { id: number; first_name: string; last_name: string };
}
interface Props { fuelCard: FuelCardRecord; }

export default function FleetFuelCardsShow({ fuelCard }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/fuel-cards' },
        { title: 'Fuel cards', href: '/fleet/fuel-cards' },
        { title: fuelCard.card_number, href: `/fleet/fuel-cards/${fuelCard.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${fuelCard.card_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">{fuelCard.card_number}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild><Link href={`/fleet/fuel-cards/${fuelCard.id}/edit`}>Edit</Link></Button>
                        <Button variant="outline" asChild><Link href="/fleet/fuel-cards">Back to fuel cards</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-base">Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Provider:</span> {fuelCard.provider}</p>
                        <p><span className="font-medium">Type:</span> {fuelCard.card_type}</p>
                        <p><span className="font-medium">Status:</span> {fuelCard.status}</p>
                        <p><span className="font-medium">Expiry:</span> {fuelCard.expiry_date ? new Date(fuelCard.expiry_date).toLocaleDateString() : '—'}</p>
                        {fuelCard.assigned_vehicle && <p><span className="font-medium">Vehicle:</span> <Link href={`/fleet/vehicles/${fuelCard.assigned_vehicle.id}`} className="underline">{fuelCard.assigned_vehicle.registration}</Link></p>}
                        {fuelCard.assigned_driver && <p><span className="font-medium">Driver:</span> <Link href={`/fleet/drivers/${fuelCard.assigned_driver.id}`} className="underline">{fuelCard.assigned_driver.first_name} {fuelCard.assigned_driver.last_name}</Link></p>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
