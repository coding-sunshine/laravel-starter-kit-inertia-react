import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface EmissionsRecord { id: number; record_date: string; scope: string; emissions_type: string; co2_kg: string; fuel_consumed_litres?: string; distance_km?: string; vehicle?: { id: number; registration: string }; driver?: { id: number; first_name: string; last_name: string }; trip?: { id: number }; }
interface Props { emissionsRecord: EmissionsRecord; }

export default function FleetEmissionsRecordsShow({ emissionsRecord }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Emissions records', href: '/fleet/emissions-records' },
        { title: 'View', href: `/fleet/emissions-records/${emissionsRecord.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Emissions record" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Emissions record</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/emissions-records/${emissionsRecord.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/emissions-records">Back to list</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Date:</span> {new Date(emissionsRecord.record_date).toLocaleDateString()}</p>
                        <p><span className="font-medium">Scope:</span> {emissionsRecord.scope}</p>
                        <p><span className="font-medium">Type:</span> {emissionsRecord.emissions_type}</p>
                        <p><span className="font-medium">CO₂ (kg):</span> {emissionsRecord.co2_kg}</p>
                        {emissionsRecord.vehicle && <p><span className="font-medium">Vehicle:</span> {emissionsRecord.vehicle.registration}</p>}
                        {emissionsRecord.driver && <p><span className="font-medium">Driver:</span> {emissionsRecord.driver.first_name} {emissionsRecord.driver.last_name}</p>}
                        {emissionsRecord.fuel_consumed_litres != null && <p><span className="font-medium">Fuel (L):</span> {emissionsRecord.fuel_consumed_litres}</p>}
                        {emissionsRecord.distance_km != null && <p><span className="font-medium">Distance (km):</span> {emissionsRecord.distance_km}</p>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
