import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Brain, Eye } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

interface AiAnalysisResultRecord {
    id: number;
    analysis_type: string;
    entity_type: string;
    entity_id: number;
    primary_finding: string | null;
    status: string;
    created_at: string;
}
interface Option { value: string; name: string; }
interface Props {
    aiAnalysisResults: { data: AiAnalysisResultRecord[]; links: { url: string | null; label: string; active: boolean }[] };
    analysisTypes: Option[];
    statuses: Option[];
}

function truncate(str: string | null, max: number): string {
    if (!str) return '—';
    return str.length <= max ? str : str.slice(0, max) + '…';
}

export default function FleetAiAnalysisResultsIndex({ aiAnalysisResults, analysisTypes, statuses }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'AI analysis results', href: '/fleet/ai-analysis-results' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – AI analysis results" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">AI analysis results</h1>
                </div>
                <form method="get" className="flex flex-wrap items-end gap-4 rounded-lg border p-4">
                    <div className="space-y-1">
                        <Label>Type</Label>
                        <select name="analysis_type" defaultValue="" className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {analysisTypes.map((a) => <option key={a.value} value={a.value}>{a.name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Status</Label>
                        <select name="status" defaultValue="" className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            <option value="">All</option>
                            {statuses.map((s) => <option key={s.value} value={s.value}>{s.name}</option>)}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">Filter</Button>
                </form>
                {aiAnalysisResults.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Brain className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No AI analysis results yet.</p>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">ID</th>
                                        <th className="p-3 text-left font-medium">Analysis type</th>
                                        <th className="p-3 text-left font-medium">Entity type</th>
                                        <th className="p-3 text-left font-medium">Entity ID</th>
                                        <th className="p-3 text-left font-medium">Primary finding</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-left font-medium">Created</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {aiAnalysisResults.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.id}</td>
                                            <td className="p-3">{row.analysis_type}</td>
                                            <td className="p-3">{row.entity_type}</td>
                                            <td className="p-3">{row.entity_id}</td>
                                            <td className="p-3 max-w-[200px] truncate" title={row.primary_finding ?? undefined}>{truncate(row.primary_finding, 60)}</td>
                                            <td className="p-3">{row.status}</td>
                                            <td className="p-3">{new Date(row.created_at).toLocaleString()}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/ai-analysis-results/${row.id}`}><Eye className="mr-1 size-3.5" />View</Link></Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {aiAnalysisResults.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {aiAnalysisResults.links.map((link, i) => (
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
