import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { HardHat, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface Row { id: number; permit_number: string; title: string; valid_from: string; valid_to: string; status: string; }
interface Props {
    permitToWorkList: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    statuses: { value: string; name: string }[];
}

export default function PermitToWorkIndex({ permitToWorkList, filters, statuses }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Permit to work', href: '/fleet/permit-to-work' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Permit to work" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Permit to work</h1>
                    <Button asChild><Link href="/fleet/permit-to-work/create"><Plus className="mr-2 size-4" />New</Link></Button>
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
                {permitToWorkList.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <HardHat className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No permits to work yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/permit-to-work/create">Add permit</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Permit number</th>
                                        <th className="p-3 text-left font-medium">Title</th>
                                        <th className="p-3 text-left font-medium">Valid to</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {permitToWorkList.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3 font-medium">{row.permit_number}</td>
                                            <td className="p-3">{row.title}</td>
                                            <td className="p-3">{row.valid_to}</td>
                                            <td className="p-3">{row.status}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/permit-to-work/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/permit-to-work/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/permit-to-work/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {permitToWorkList.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {permitToWorkList.links.map((link, i) => (
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
