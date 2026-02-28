import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Clock, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface RecordType {
    id: number;
    date: string;
    driving_time_minutes: number;
    total_duty_time_minutes: number;
    wtd_compliant: boolean;
    driver?: { id: number; first_name: string; last_name: string };
}
interface Props {
    driverWorkingTime: { data: RecordType[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    drivers: { id: number; first_name: string; last_name: string }[];
}

export default function FleetDriverWorkingTimeIndex({ driverWorkingTime, filters, drivers }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/driver-working-time' },
        { title: 'Driver working time', href: '/fleet/driver-working-time' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Driver working time" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Driver working time</h1>
                    <Button asChild>
                        <Link href="/fleet/driver-working-time/create"><Plus className="mr-2 size-4" />New</Link>
                    </Button>
                </div>
                <form method="get" className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Driver</Label>
                        <select name="driver_id" defaultValue={filters.driver_id ?? ''} className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {drivers.map((d) => <option key={d.id} value={d.id}>{d.last_name}, {d.first_name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>From date</Label>
                        <Input name="from_date" type="date" defaultValue={filters.from_date ?? ''} className="h-9 w-40" />
                    </div>
                    <div className="space-y-1">
                        <Label>To date</Label>
                        <Input name="to_date" type="date" defaultValue={filters.to_date ?? ''} className="h-9 w-40" />
                    </div>
                    <Button type="submit" variant="secondary" size="sm">Filter</Button>
                </form>
                {driverWorkingTime.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Clock className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No driver working time records yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/driver-working-time/create">Add record</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Driver</th>
                                        <th className="p-3 text-left font-medium">Date</th>
                                        <th className="p-3 text-left font-medium">Driving (min)</th>
                                        <th className="p-3 text-left font-medium">Duty (min)</th>
                                        <th className="p-3 text-left font-medium">WTD compliant</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {driverWorkingTime.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.driver ? `${row.driver.first_name} ${row.driver.last_name}` : '—'}</td>
                                            <td className="p-3">{new Date(row.date).toLocaleDateString()}</td>
                                            <td className="p-3">{row.driving_time_minutes}</td>
                                            <td className="p-3">{row.total_duty_time_minutes}</td>
                                            <td className="p-3">{row.wtd_compliant ? 'Yes' : 'No'}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/driver-working-time/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/driver-working-time/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/driver-working-time/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {driverWorkingTime.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {driverWorkingTime.links.map((link, i) => (
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
