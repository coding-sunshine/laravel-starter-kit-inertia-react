import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface RecallRecord {
    id: number;
    recall_reference: string;
    make?: string | null;
    model?: string | null;
    title?: string | null;
    description?: string | null;
    issued_date?: string | null;
    due_date?: string | null;
    status: string;
    completed_at?: string | null;
    completion_notes?: string | null;
    vehicle?: { id: number; registration: string } | null;
}
interface Props { vehicleRecall: RecallRecord; }

export default function FleetVehicleRecallsShow({ vehicleRecall }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicle recalls', href: '/fleet/vehicle-recalls' },
        { title: vehicleRecall.recall_reference, href: `/fleet/vehicle-recalls/${vehicleRecall.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${vehicleRecall.recall_reference}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">{vehicleRecall.recall_reference}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild><Link href={`/fleet/vehicle-recalls/${vehicleRecall.id}/edit`}>Edit</Link></Button>
                        <Button variant="outline" asChild><Link href="/fleet/vehicle-recalls">Back</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-base">Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Status:</span> {vehicleRecall.status}</p>
                        {vehicleRecall.title && <p><span className="font-medium">Title:</span> {vehicleRecall.title}</p>}
                        {vehicleRecall.vehicle && <p><span className="font-medium">Vehicle:</span> <Link href={`/fleet/vehicles/${vehicleRecall.vehicle.id}`} className="underline">{vehicleRecall.vehicle.registration}</Link></p>}
                        {vehicleRecall.issued_date && <p><span className="font-medium">Issued date:</span> {new Date(vehicleRecall.issued_date).toLocaleDateString()}</p>}
                        {vehicleRecall.due_date && <p><span className="font-medium">Due date:</span> {new Date(vehicleRecall.due_date).toLocaleDateString()}</p>}
                        {vehicleRecall.completed_at && <p><span className="font-medium">Completed at:</span> {new Date(vehicleRecall.completed_at).toLocaleDateString()}</p>}
                        {vehicleRecall.description && <p><span className="font-medium">Description:</span> {vehicleRecall.description}</p>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
