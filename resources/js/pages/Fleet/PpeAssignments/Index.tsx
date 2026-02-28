import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { HardHat, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface Row { id: number; ppe_type: string; issued_date: string; status: string; user?: { id: number; name: string }; driver?: { id: number; first_name: string; last_name: string }; }
interface Props {
    ppeAssignments: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    statuses: { value: string; name: string }[];
}

export default function PpeAssignmentsIndex({ ppeAssignments, filters, statuses }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'PPE assignments', href: '/fleet/ppe-assignments' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – PPE assignments" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">PPE assignments</h1>
                    <Button asChild><Link href="/fleet/ppe-assignments/create"><Plus className="mr-2 size-4" />New</Link></Button>
                </div>
                <form method="get" className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Status</Label>
                        <select name="status" defaultValue={filters.status ?? ''} className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">Filter</Button>
                </form>
                {ppeAssignments.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <HardHat className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No PPE assignments yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/ppe-assignments/create">Add assignment</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">PPE type</th>
                                        <th className="p-3 text-left font-medium">Assigned to</th>
                                        <th className="p-3 text-left font-medium">Issued date</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {ppeAssignments.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3 font-medium">{row.ppe_type}</td>
                                            <td className="p-3">{row.user?.name ?? (row.driver ? row.driver.first_name + ' ' + row.driver.last_name : '—')}</td>
                                            <td className="p-3">{row.issued_date}</td>
                                            <td className="p-3">{row.status}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/ppe-assignments/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/ppe-assignments/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/ppe-assignments/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {ppeAssignments.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {ppeAssignments.links.map((link, i) => (
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
