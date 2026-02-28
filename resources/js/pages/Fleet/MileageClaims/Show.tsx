import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface MileageClaim {
    id: number;
    claim_date: string;
    start_odometer?: number;
    end_odometer?: number;
    distance_km?: number;
    purpose?: string;
    destination?: string;
    amount_claimed?: string;
    amount_approved?: string;
    status?: string;
    rejection_reason?: string;
    grey_fleet_vehicle?: { id: number; registration?: string; make?: string; model?: string };
    user?: { id: number; name: string };
    approved_by_user?: { id: number; name: string };
}
interface Props { mileageClaim: MileageClaim; }

export default function FleetMileageClaimsShow({ mileageClaim }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Mileage claims', href: '/fleet/mileage-claims' },
        { title: 'View', href: `/fleet/mileage-claims/${mileageClaim.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Mileage claim" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Mileage claim – {new Date(mileageClaim.claim_date).toLocaleDateString()}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/mileage-claims/${mileageClaim.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/mileage-claims">Back to list</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Claim date:</span> {new Date(mileageClaim.claim_date).toLocaleDateString()}</p>
                        {mileageClaim.grey_fleet_vehicle && <p><span className="font-medium">Vehicle:</span> {mileageClaim.grey_fleet_vehicle.registration ?? [mileageClaim.grey_fleet_vehicle.make, mileageClaim.grey_fleet_vehicle.model].filter(Boolean).join(' ')}</p>}
                        {mileageClaim.user && <p><span className="font-medium">Claimant:</span> {mileageClaim.user.name}</p>}
                        {mileageClaim.distance_km != null && <p><span className="font-medium">Distance (km):</span> {mileageClaim.distance_km}</p>}
                        {mileageClaim.start_odometer != null && <p><span className="font-medium">Start odometer:</span> {mileageClaim.start_odometer}</p>}
                        {mileageClaim.end_odometer != null && <p><span className="font-medium">End odometer:</span> {mileageClaim.end_odometer}</p>}
                        {mileageClaim.purpose && <p><span className="font-medium">Purpose:</span> {mileageClaim.purpose}</p>}
                        {mileageClaim.destination && <p><span className="font-medium">Destination:</span> {mileageClaim.destination}</p>}
                        {mileageClaim.amount_claimed != null && <p><span className="font-medium">Amount claimed:</span> {mileageClaim.amount_claimed}</p>}
                        {mileageClaim.amount_approved != null && <p><span className="font-medium">Amount approved:</span> {mileageClaim.amount_approved}</p>}
                        {mileageClaim.status && <p><span className="font-medium">Status:</span> {mileageClaim.status}</p>}
                        {mileageClaim.rejection_reason && <p><span className="font-medium">Rejection reason:</span> {mileageClaim.rejection_reason}</p>}
                        {mileageClaim.approved_by_user && <p><span className="font-medium">Approved by:</span> {mileageClaim.approved_by_user.name}</p>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
