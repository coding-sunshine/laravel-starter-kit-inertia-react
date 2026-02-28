import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface VehicleTyre {
    id: number;
    position: string;
    size?: string;
    brand?: string;
    fitted_at?: string;
    tread_depth_mm?: string;
    odometer_at_fit?: number;
    notes?: string;
    vehicle?: { id: number; registration: string };
    tyre_inventory?: { id: number; label: string };
}
interface Props { vehicleTyre: VehicleTyre; }

export default function FleetVehicleTyresShow({ vehicleTyre }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicle tyres', href: '/fleet/vehicle-tyres' },
        { title: 'View', href: `/fleet/vehicle-tyres/${vehicleTyre.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Vehicle tyre" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Vehicle tyre – {vehicleTyre.vehicle?.registration ?? '—'} ({vehicleTyre.position})</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/vehicle-tyres/${vehicleTyre.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/vehicle-tyres">Back to list</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        {vehicleTyre.vehicle && <p><span className="font-medium">Vehicle:</span> {vehicleTyre.vehicle.registration}</p>}
                        <p><span className="font-medium">Position:</span> {vehicleTyre.position}</p>
                        {(vehicleTyre.size || vehicleTyre.brand) && <p><span className="font-medium">Size / Brand:</span> {[vehicleTyre.size, vehicleTyre.brand].filter(Boolean).join(' – ')}</p>}
                        {vehicleTyre.tyre_inventory && <p><span className="font-medium">Tyre inventory:</span> {vehicleTyre.tyre_inventory.label}</p>}
                        {vehicleTyre.fitted_at && <p><span className="font-medium">Fitted at:</span> {new Date(vehicleTyre.fitted_at).toLocaleDateString()}</p>}
                        {vehicleTyre.tread_depth_mm != null && <p><span className="font-medium">Tread depth (mm):</span> {vehicleTyre.tread_depth_mm}</p>}
                        {vehicleTyre.odometer_at_fit != null && <p><span className="font-medium">Odometer at fit:</span> {vehicleTyre.odometer_at_fit}</p>}
                        {vehicleTyre.notes && <p><span className="font-medium">Notes:</span> {vehicleTyre.notes}</p>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
