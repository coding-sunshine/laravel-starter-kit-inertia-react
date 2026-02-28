import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2, FileCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface Row {
    id: number;
    invoice_number: string;
    invoice_date: string;
    total_amount?: string;
    status?: string;
    contractor?: { id: number; name: string };
}
interface Props {
    contractorInvoices: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    contractors: { id: number; name: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetContractorInvoicesIndex({ contractorInvoices, contractors, statuses }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Contractor invoices', href: '/fleet/contractor-invoices' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Contractor invoices" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Contractor invoices</h1>
                    <Button asChild>
                        <Link href="/fleet/contractor-invoices/create"><Plus className="mr-2 size-4" />New</Link>
                    </Button>
                </div>
                {contractorInvoices.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <FileCheck className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No contractor invoices yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/contractor-invoices/create">Add</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Invoice number</th>
                                        <th className="p-3 text-left font-medium">Contractor</th>
                                        <th className="p-3 text-left font-medium">Date</th>
                                        <th className="p-3 text-left font-medium">Total</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {contractorInvoices.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.invoice_number}</td>
                                            <td className="p-3">{row.contractor?.name ?? '—'}</td>
                                            <td className="p-3">{new Date(row.invoice_date).toLocaleDateString()}</td>
                                            <td className="p-3">{row.total_amount ?? '—'}</td>
                                            <td className="p-3">{statuses.find(s => s.value === row.status)?.name ?? row.status ?? '—'}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/contractor-invoices/${row.id}`}>View</Link></Button>
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/contractor-invoices/${row.id}/edit`}><Pencil className="ml-1 size-3.5" /></Link></Button>
                                                <Form action={`/fleet/contractor-invoices/${row.id}`} method="delete" className="ml-2 inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                    <Button type="submit" variant="ghost" size="sm"><Trash2 className="size-3.5 text-destructive" /></Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {contractorInvoices.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {contractorInvoices.links.map((link, i) => (
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
