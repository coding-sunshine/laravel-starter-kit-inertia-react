import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface DefectRecord { id: number; defect_number: string; title: string; severity?: string; }
interface WorkOrderRecord {
    id: number;
    work_order_number: string;
    title: string;
    status: string;
    priority: string;
    scheduled_date: string | null;
    vehicle?: { id: number; registration: string };
    assignedGarage?: { id: number; name: string };
    defects?: DefectRecord[];
}
interface Props { workOrder: WorkOrderRecord; }

export default function FleetWorkOrdersShow({ workOrder }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/work-orders' },
        { title: 'Work orders', href: '/fleet/work-orders' },
        { title: workOrder.work_order_number, href: `/fleet/work-orders/${workOrder.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${workOrder.work_order_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">{workOrder.work_order_number}</h1>
                    <Button variant="outline" asChild><Link href="/fleet/work-orders">Back</Link></Button>
                </div>
                <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-base">Work order</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Title:</span> {workOrder.title}</p>
                        <p><span className="font-medium">Status:</span> {workOrder.status}</p>
                        <p><span className="font-medium">Priority:</span> {workOrder.priority}</p>
                        {workOrder.scheduled_date && <p><span className="font-medium">Scheduled:</span> {new Date(workOrder.scheduled_date).toLocaleDateString()}</p>}
                        {workOrder.vehicle && <p><span className="font-medium">Vehicle:</span> <Link href={`/fleet/vehicles/${workOrder.vehicle.id}`} className="underline">{workOrder.vehicle.registration}</Link></p>}
                        {workOrder.assignedGarage && <p><span className="font-medium">Garage:</span> {workOrder.assignedGarage.name}</p>}
                    </CardContent>
                </Card>
                {workOrder.defects && workOrder.defects.length > 0 && (
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-base">Defects</CardTitle></CardHeader>
                        <CardContent>
                            <ul className="space-y-2 text-sm">
                                {workOrder.defects.map((d) => (
                                    <li key={d.id} className="flex items-center justify-between rounded border p-2">
                                        <span><Link href={`/fleet/defects/${d.id}`} className="font-medium underline">{d.defect_number}</Link> – {d.title}</span>
                                        {d.severity && <span className="text-muted-foreground">{d.severity}</span>}
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
