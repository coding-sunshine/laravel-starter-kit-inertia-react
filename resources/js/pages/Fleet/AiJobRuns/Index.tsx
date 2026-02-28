import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Play, Eye } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface AiJobRunRecord {
    id: number;
    job_type: string;
    status: string;
    started_at: string | null;
    completed_at: string | null;
}
interface Option { value: string; name: string; }
interface Props {
    aiJobRuns: { data: AiJobRunRecord[]; links: { url: string | null; label: string; active: boolean }[] };
    statuses: Option[];
}

export default function FleetAiJobRunsIndex({ aiJobRuns, statuses }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'AI job runs', href: '/fleet/ai-job-runs' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – AI job runs" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">AI job runs</h1>
                </div>
                <form method="get" className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Status</Label>
                        <select name="status" defaultValue="" className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">Filter</Button>
                </form>
                {aiJobRuns.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Play className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No AI job runs yet.</p>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">ID</th>
                                        <th className="p-3 text-left font-medium">Job type</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-left font-medium">Started at</th>
                                        <th className="p-3 text-left font-medium">Completed at</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {aiJobRuns.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.id}</td>
                                            <td className="p-3">{row.job_type}</td>
                                            <td className="p-3">{row.status}</td>
                                            <td className="p-3">{row.started_at ? new Date(row.started_at).toLocaleString() : '—'}</td>
                                            <td className="p-3">{row.completed_at ? new Date(row.completed_at).toLocaleString() : '—'}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/ai-job-runs/${row.id}`}><Eye className="mr-1 size-3.5" />View</Link></Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {aiJobRuns.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {aiJobRuns.links.map((link, i) => (
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
