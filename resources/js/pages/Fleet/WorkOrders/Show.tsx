import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Bot, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface DefectRecord { id: number; defect_number: string; title: string; severity?: string; }
interface WorkOrderLineRecord {
    id: number;
    line_type: string;
    description?: string | null;
    quantity: string | number;
    unit_price?: string | number | null;
    total?: string | number | null;
    sort_order: number;
    partsInventory?: { id: number; part_number: string } | null;
}
interface WorkOrderPartRecord {
    id: number;
    quantity_used: string | number;
    unit_cost?: string | number | null;
    total_cost?: string | number | null;
    notes?: string | null;
    partsInventory?: { id: number; part_number: string };
}
interface WarrantyClaimRecord { id: number; claim_number: string; status: string; }
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
    work_order_lines?: WorkOrderLineRecord[];
    work_order_parts?: WorkOrderPartRecord[];
    warranty_claims?: WarrantyClaimRecord[];
}
interface Props { workOrder: WorkOrderRecord; }

export default function FleetWorkOrdersShow({ workOrder }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/work-orders' },
        { title: 'Work orders', href: '/fleet/work-orders' },
        { title: workOrder.work_order_number, href: `/fleet/work-orders/${workOrder.id}` },
    ];
    const lines = workOrder.work_order_lines ?? [];
    const parts = workOrder.work_order_parts ?? [];
    const claims = workOrder.warranty_claims ?? [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${workOrder.work_order_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">{workOrder.work_order_number}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/fleet/assistant?context=work_order:${workOrder.id}`} prefetch="click">
                                <Bot className="mr-1.5 size-4" />
                                Ask assistant
                            </Link>
                        </Button>
                        <Button variant="outline" asChild><Link href="/fleet/work-orders">Back</Link></Button>
                    </div>
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
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-base">Lines</CardTitle>
                        <Button size="sm" asChild>
                            <Link href={`/fleet/work-orders/${workOrder.id}/work-order-lines/create`}><Plus className="mr-1 size-3.5" />Add line</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {lines.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No lines. <Link href={`/fleet/work-orders/${workOrder.id}/work-order-lines/create`} className="text-primary underline">Add line</Link></p>
                        ) : (
                            <div className="rounded-md border text-sm">
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="p-2 text-left font-medium">Type</th>
                                            <th className="p-2 text-left font-medium">Description</th>
                                            <th className="p-2 text-right font-medium">Qty</th>
                                            <th className="p-2 text-right font-medium">Unit price</th>
                                            <th className="p-2 text-right font-medium">Total</th>
                                            <th className="p-2 text-right font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {lines.map((line) => (
                                            <tr key={line.id} className="border-b last:border-0">
                                                <td className="p-2">{line.line_type}</td>
                                                <td className="p-2">{line.description ?? line.partsInventory?.part_number ?? '—'}</td>
                                                <td className="p-2 text-right">{line.quantity}</td>
                                                <td className="p-2 text-right">{line.unit_price ?? '—'}</td>
                                                <td className="p-2 text-right">{line.total ?? '—'}</td>
                                                <td className="p-2 text-right">
                                                    <Button variant="outline" size="sm" asChild><Link href={`/fleet/work-orders/${workOrder.id}/work-order-lines/${line.id}/edit`}><Pencil className="size-3.5" /></Link></Button>
                                                    <Form action={`/fleet/work-orders/${workOrder.id}/work-order-lines/${line.id}`} method="delete" className="ml-1 inline" onSubmit={(e) => { if (!confirm('Delete line?')) e.preventDefault(); }}>
                                                        <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                    </Form>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-base">Parts</CardTitle>
                        <Button size="sm" asChild>
                            <Link href={`/fleet/work-orders/${workOrder.id}/work-order-parts/create`}><Plus className="mr-1 size-3.5" />Add part</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {parts.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No parts. <Link href={`/fleet/work-orders/${workOrder.id}/work-order-parts/create`} className="text-primary underline">Add part</Link></p>
                        ) : (
                            <div className="rounded-md border text-sm">
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="p-2 text-left font-medium">Part</th>
                                            <th className="p-2 text-right font-medium">Qty used</th>
                                            <th className="p-2 text-right font-medium">Unit cost</th>
                                            <th className="p-2 text-right font-medium">Total cost</th>
                                            <th className="p-2 text-right font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {parts.map((part) => (
                                            <tr key={part.id} className="border-b last:border-0">
                                                <td className="p-2">{part.partsInventory?.part_number ?? '—'}</td>
                                                <td className="p-2 text-right">{part.quantity_used}</td>
                                                <td className="p-2 text-right">{part.unit_cost ?? '—'}</td>
                                                <td className="p-2 text-right">{part.total_cost ?? '—'}</td>
                                                <td className="p-2 text-right">
                                                    <Button variant="outline" size="sm" asChild><Link href={`/fleet/work-orders/${workOrder.id}/work-order-parts/${part.id}/edit`}><Pencil className="size-3.5" /></Link></Button>
                                                    <Form action={`/fleet/work-orders/${workOrder.id}/work-order-parts/${part.id}`} method="delete" className="ml-1 inline" onSubmit={(e) => { if (!confirm('Delete part?')) e.preventDefault(); }}>
                                                        <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                    </Form>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-base">Warranty claims</CardTitle>
                        <Button size="sm" asChild>
                            <Link href={`/fleet/warranty-claims/create?work_order_id=${workOrder.id}`}>New claim</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {claims.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No warranty claims. <Link href={`/fleet/warranty-claims?work_order_id=${workOrder.id}`} className="text-primary underline">View all claims</Link> or <Link href={`/fleet/warranty-claims/create?work_order_id=${workOrder.id}`} className="text-primary underline">create one</Link>.</p>
                        ) : (
                            <ul className="space-y-2 text-sm">
                                {claims.map((c) => (
                                    <li key={c.id} className="flex items-center justify-between rounded border p-2">
                                        <Link href={`/fleet/warranty-claims/${c.id}`} className="font-medium underline">{c.claim_number}</Link>
                                        <span className="text-muted-foreground">{c.status}</span>
                                    </li>
                                ))}
                                <li><Link href={`/fleet/warranty-claims?work_order_id=${workOrder.id}`} className="text-primary text-sm underline">View all warranty claims for this work order</Link></li>
                            </ul>
                        )}
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
