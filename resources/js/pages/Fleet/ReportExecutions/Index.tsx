import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface Row {
    id: number;
    execution_start: string;
    status: string;
    triggered_by: string;
    report?: { id: number; name: string };
}
interface Props {
    reportExecutions: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    downloadAvailableIds?: number[];
    filters: Record<string, string>;
    reports: { id: number; name: string }[];
}

export default function FleetReportExecutionsIndex({ reportExecutions, downloadAvailableIds = [], filters, reports }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Report executions', href: '/fleet/report-executions' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Report executions" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Report executions</h1>
                <form method="get" className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Report</Label>
                        <select name="report_id" defaultValue={filters.report_id ?? ''} className="h-9 w-56 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {reports.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">Filter</Button>
                </form>
                {reportExecutions.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <FileText className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No report executions yet.</p>
                        <Button asChild className="mt-4"><Link href="/fleet/reports">Go to reports</Link></Button>
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full min-w-[600px] text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Report</th>
                                        <th className="p-3 text-left font-medium">Started</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-left font-medium">Triggered by</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {reportExecutions.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.report?.name ?? '—'}</td>
                                            <td className="p-3">{new Date(row.execution_start).toLocaleString()}</td>
                                            <td className="p-3">{row.status}</td>
                                            <td className="p-3">{row.triggered_by}</td>
                                            <td className="p-3 text-right">
                                                {(downloadAvailableIds as number[]).includes(row.id) && (
                                                    <Button variant="outline" size="sm" asChild>
                                                        <a href={`/fleet/report-executions/${row.id}/download`} download>Download</a>
                                                    </Button>
                                                )}
                                                <Button variant="outline" size="sm" asChild className="ml-1"><Link href={`/fleet/report-executions/${row.id}`}>View</Link></Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {reportExecutions.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {reportExecutions.links.map((link, i) => (
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
