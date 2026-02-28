import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Calendar, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface ScheduleRecord {
    id: number;
    service_type: string;
    next_service_due_date: string | null;
    next_service_due_mileage: number | null;
    is_active: boolean;
    vehicle?: { id: number; registration: string };
}
interface Props {
    serviceSchedules: { data: ScheduleRecord[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    vehicles: { id: number; registration: string }[];
    serviceTypes: { value: string; name: string }[];
}

export default function FleetServiceSchedulesIndex({ serviceSchedules, filters, vehicles, serviceTypes }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/service-schedules' },
        { title: 'Service schedules', href: '/fleet/service-schedules' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Service schedules" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Service schedules</h1>
                    <Button asChild>
                        <Link href="/fleet/service-schedules/create"><Plus className="mr-2 size-4" />New</Link>
                    </Button>
                </div>
                <form method="get" className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Vehicle</Label>
                        <select name="vehicle_id" defaultValue={filters.vehicle_id ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {vehicles.map((v) => <option key={v.id} value={v.id}>{v.registration}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Service type</Label>
                        <select name="service_type" defaultValue={filters.service_type ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {serviceTypes.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">Filter</Button>
                </form>
                {serviceSchedules.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Calendar className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No service schedules yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/service-schedules/create">Add schedule</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Vehicle</th>
                                        <th className="p-3 text-left font-medium">Service type</th>
                                        <th className="p-3 text-left font-medium">Next due (date)</th>
                                        <th className="p-3 text-left font-medium">Next due (mileage)</th>
                                        <th className="p-3 text-left font-medium">Active</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {serviceSchedules.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3"><Link href={`/fleet/service-schedules/${row.id}`} className="font-medium hover:underline">{row.vehicle?.registration ?? '—'}</Link></td>
                                            <td className="p-3">{row.service_type}</td>
                                            <td className="p-3">{row.next_service_due_date ? new Date(row.next_service_due_date).toLocaleDateString() : '—'}</td>
                                            <td className="p-3">{row.next_service_due_mileage ?? '—'}</td>
                                            <td className="p-3">{row.is_active ? 'Yes' : 'No'}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/service-schedules/${row.id}/edit`}><Pencil className="mr-1 size-3.5" />Edit</Link></Button>
                                                <Form action={`/fleet/service-schedules/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {serviceSchedules.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {serviceSchedules.links.map((link, i) => (
                                    <Link key={i} href={link.url ?? '#'} className={`rounded border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}>{link.label}</Link>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
