import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface DefectRecord {
    id: number;
    defect_number: string;
    title: string;
    description?: string;
    category?: string;
    severity?: string;
    reported_at: string;
    vehicle?: { id: number; registration: string };
    reportedByDriver?: { id: number; first_name: string; last_name: string };
    workOrder?: { id: number; work_order_number: string };
}
interface PhotoUrl { id: number; url: string; }
interface Props { defect: DefectRecord; photoUrls: PhotoUrl[]; }

export default function FleetDefectsShow({ defect, photoUrls }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/defects' },
        { title: 'Defects', href: '/fleet/defects' },
        { title: defect.defect_number, href: `/fleet/defects/${defect.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${defect.defect_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">{defect.defect_number}</h1>
                    <Button variant="outline" asChild><Link href="/fleet/defects">Back</Link></Button>
                </div>
                <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-base">Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Title:</span> {defect.title}</p>
                        {defect.description && <p><span className="font-medium">Description:</span> {defect.description}</p>}
                        {defect.category && <p><span className="font-medium">Category:</span> {defect.category}</p>}
                        {defect.severity && <p><span className="font-medium">Severity:</span> {defect.severity}</p>}
                        <p><span className="font-medium">Reported at:</span> {new Date(defect.reported_at).toLocaleString()}</p>
                        {defect.vehicle && <p><span className="font-medium">Vehicle:</span> <Link href={`/fleet/vehicles/${defect.vehicle.id}`} className="underline">{defect.vehicle.registration}</Link></p>}
                        {defect.reportedByDriver && <p><span className="font-medium">Reported by:</span> <Link href={`/fleet/drivers/${defect.reportedByDriver.id}`} className="underline">{defect.reportedByDriver.first_name} {defect.reportedByDriver.last_name}</Link></p>}
                        {defect.workOrder && <p><span className="font-medium">Work order:</span> <Link href={`/fleet/work-orders/${defect.workOrder.id}`} className="underline">{defect.workOrder.work_order_number}</Link></p>}
                    </CardContent>
                </Card>
                {photoUrls.length > 0 && (
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-base">Photos</CardTitle></CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-4">
                                {photoUrls.map((p) => (
                                    <a key={p.id} href={p.url} target="_blank" rel="noopener noreferrer" className="block">
                                        <img src={p.url} alt="" className="h-32 w-auto rounded border object-cover" />
                                    </a>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
