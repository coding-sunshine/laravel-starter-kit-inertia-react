import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Database } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface Row {
    id: number;
    batch_id?: string;
    migration_type: string;
    status: string;
    total_records: number;
    processed_records: number;
    failed_records: number;
    started_at: string;
    completed_at?: string;
    organization?: { id: number; name: string } | null;
    triggered_by?: { id: number; name: string } | null;
}
interface Props {
    dataMigrationRuns: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
}

export default function FleetDataMigrationRunsIndex({ dataMigrationRuns }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Data migration runs', href: '/fleet/data-migration-runs' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Data migration runs" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Data migration runs</h1>
                {dataMigrationRuns.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Database className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">No data migration runs yet.</p>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">ID</th>
                                        <th className="p-3 text-left font-medium">Batch</th>
                                        <th className="p-3 text-left font-medium">Type</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-left font-medium">Processed</th>
                                        <th className="p-3 text-left font-medium">Started</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {dataMigrationRuns.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">{row.id}</td>
                                            <td className="p-3">{row.batch_id ?? '—'}</td>
                                            <td className="p-3">{row.migration_type}</td>
                                            <td className="p-3">{row.status}</td>
                                            <td className="p-3">{row.processed_records} / {row.total_records}{row.failed_records > 0 ? ` (${row.failed_records} failed)` : ''}</td>
                                            <td className="p-3">{new Date(row.started_at).toLocaleString()}</td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild><Link href={`/fleet/data-migration-runs/${row.id}`}>View</Link></Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {dataMigrationRuns.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {dataMigrationRuns.links.map((link, i) => (
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
