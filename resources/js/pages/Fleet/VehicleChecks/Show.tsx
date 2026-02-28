import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Check {
    id: number;
    check_date: string;
    status: string;
    vehicle?: { id: number; registration: string };
    vehicle_check_template?: { id: number; name: string };
    performed_by_driver?: { id: number; first_name: string; last_name: string };
    performed_by_user?: { id: number; name: string };
    defect?: { id: number; defect_number: string };
    vehicle_check_items?: { id: number; item_index: number; label: string; result_type: string; result?: string; value_text?: string }[];
}
interface Props { vehicleCheck: Check; }

export default function VehicleChecksShow({ vehicleCheck }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicle checks', href: '/fleet/vehicle-checks' },
        { title: 'Check', href: `/fleet/vehicle-checks/${vehicleCheck.id}` },
    ];
    const driverName = vehicleCheck.performed_by_driver ? vehicleCheck.performed_by_driver.first_name + ' ' + vehicleCheck.performed_by_driver.last_name : '—';
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Vehicle check" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Vehicle check</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/vehicle-checks/${vehicleCheck.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/vehicle-checks">Back</Link></Button>
                    </div>
                </div>
                <dl className="max-w-md space-y-2 rounded-lg border p-6">
                    <div><dt className="text-sm text-muted-foreground">Date</dt><dd className="font-medium">{vehicleCheck.check_date}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Vehicle</dt><dd>{vehicleCheck.vehicle?.registration ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Template</dt><dd>{vehicleCheck.vehicle_check_template?.name ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Status</dt><dd>{vehicleCheck.status}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Performed by (driver)</dt><dd>{driverName}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Performed by (user)</dt><dd>{vehicleCheck.performed_by_user?.name ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Defect</dt><dd>{vehicleCheck.defect?.defect_number ?? '—'}</dd></div>
                </dl>
                {vehicleCheck.vehicle_check_items && vehicleCheck.vehicle_check_items.length > 0 && (
                    <div>
                        <h2 className="mb-2 text-lg font-medium">Check items</h2>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">#</th>
                                        <th className="p-3 text-left font-medium">Label</th>
                                        <th className="p-3 text-left font-medium">Type</th>
                                        <th className="p-3 text-left font-medium">Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {vehicleCheck.vehicle_check_items.map((item) => (
                                        <tr key={item.id} className="border-b last:border-0">
                                            <td className="p-3">{item.item_index}</td>
                                            <td className="p-3">{item.label}</td>
                                            <td className="p-3">{item.result_type}</td>
                                            <td className="p-3">{item.result ?? item.value_text ?? '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
