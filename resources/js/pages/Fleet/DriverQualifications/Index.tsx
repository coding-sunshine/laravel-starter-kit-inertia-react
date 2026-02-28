import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, ShieldCheck, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface Row { id: number; qualification_name: string; qualification_type: string; status: string; expiry_date?: string; driver?: { id: number; first_name: string; last_name: string }; }
interface Props {
    driverQualifications: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    drivers: { id: number; name: string }[];
    qualificationTypes: { value: string; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetDriverQualificationsIndex({ driverQualifications, filters, drivers, qualificationTypes, statuses }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Driver qualifications', href: '/fleet/driver-qualifications' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Driver qualifications" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Driver qualifications</h1>
                    <Button asChild>
                        <Link href="/fleet/driver-qualifications/create"><Plus className="mr-2 size-4" />New</Link>
                    </Button>
                </div>
                <form method="get" className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Driver</Label>
                        <select name="driver_id" defaultValue={filters.driver_id ?? ''} className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {drivers.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Type</Label>
                        <select name="qualification_type" defaultValue={filters.qualification_type ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {qualificationTypes.map((q) => <option key={q.value} value={q.value}>{q.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Status</Label>
                        <select name="status" defaultValue={filters.status ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">Filter</Button>
                </form>
                {driverQualifications.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <ShieldCheck className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No driver qualifications yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/driver-qualifications/create">Add qualification</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Qualification</th>
                                        <th className="p-3 text-left font-medium">Type</th>
                                        <th className="p-3 text-left font-medium">Driver</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-left font-medium">Expiry</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {driverQualifications.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.qualification_name}</td>
                                            <td className="p-3">{row.qualification_type}</td>
                                            <td className="p-3">{row.driver ? `${row.driver.first_name} ${row.driver.last_name}` : '—'}</td>
                                            <td className="p-3">{row.status}</td>
                                            <td className="p-3">{row.expiry_date ? new Date(row.expiry_date).toLocaleDateString() : '—'}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/driver-qualifications/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/driver-qualifications/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/driver-qualifications/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {driverQualifications.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {driverQualifications.links.map((link, i) => (
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
