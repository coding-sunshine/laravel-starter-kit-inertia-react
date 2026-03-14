import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Activity, BarChart2, DollarSign, Loader2, Users } from 'lucide-react';
import { useState } from 'react';

interface Stats {
    totalContacts: number;
    activeSales: number;
    openTasks: number;
    settledThisMonth: number;
}

interface Charts {
    contactsByStage: Record<string, number>;
    salesByStatus: Record<string, number>;
    reservationsByStage: Record<string, number>;
    tasksByType: Record<string, number>;
}

interface Props {
    stats: Stats;
    charts: Charts;
}

interface NlResult {
    answer: string;
    data: unknown[];
    chart_type: string | null;
    sql_hint: string | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Analytics', href: '/analytics' },
];

function StatCard({ label, value, icon: Icon, color }: { label: string; value: number; icon: React.ElementType; color: string }) {
    return (
        <div className="rounded-lg border p-4 shadow-sm">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-muted-foreground text-sm">{label}</p>
                    <p className="mt-1 text-2xl font-bold">{value}</p>
                </div>
                <div className={`rounded-full p-3 ${color}`}>
                    <Icon className="h-5 w-5" />
                </div>
            </div>
        </div>
    );
}

function SimpleBarChart({ data, title }: { data: Record<string, number>; title: string }) {
    const entries = Object.entries(data);
    const max = Math.max(...entries.map(([, v]) => v), 1);

    return (
        <div className="rounded-lg border p-4">
            <h3 className="mb-3 font-medium">{title}</h3>
            {entries.length === 0 ? (
                <p className="text-muted-foreground text-sm">No data yet.</p>
            ) : (
                <div className="space-y-2">
                    {entries.map(([label, count]) => (
                        <div key={label}>
                            <div className="mb-0.5 flex justify-between text-xs">
                                <span className="capitalize">{label.replace(/_/g, ' ')}</span>
                                <span className="font-medium">{count}</span>
                            </div>
                            <div className="h-2 rounded-full bg-gray-100">
                                <div
                                    className="h-2 rounded-full bg-blue-500"
                                    style={{ width: `${(count / max) * 100}%` }}
                                />
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

export default function AnalyticsIndexPage({ stats, charts }: Props) {
    const [nlQuery, setNlQuery] = useState('');
    const [nlResult, setNlResult] = useState<NlResult | null>(null);
    const [nlLoading, setNlLoading] = useState(false);
    const [nlError, setNlError] = useState<string | null>(null);

    async function handleNlQuery(e: React.FormEvent) {
        e.preventDefault();
        if (!nlQuery.trim()) return;

        setNlLoading(true);
        setNlError(null);
        setNlResult(null);

        try {
            const response = await fetch('/analytics/nl-query', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                },
                body: JSON.stringify({ query: nlQuery }),
            });

            if (!response.ok) throw new Error('Request failed');
            const data = await response.json() as NlResult;
            setNlResult(data);
        } catch {
            setNlError('Failed to process query. Please try again.');
        } finally {
            setNlLoading(false);
        }
    }

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Analytics" />

            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold" data-pan="analytics-tab">Analytics</h1>
                    <p className="text-muted-foreground mt-1 text-sm">CRM insights and performance metrics.</p>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <StatCard label="Total Contacts" value={stats.totalContacts} icon={Users} color="bg-blue-100 text-blue-600" />
                    <StatCard label="Active Sales" value={stats.activeSales} icon={BarChart2} color="bg-green-100 text-green-600" />
                    <StatCard label="Open Tasks" value={stats.openTasks} icon={Activity} color="bg-yellow-100 text-yellow-600" />
                    <StatCard label="Settled This Month" value={stats.settledThisMonth} icon={DollarSign} color="bg-purple-100 text-purple-600" />
                </div>

                {/* Charts */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <SimpleBarChart data={charts.contactsByStage} title="Contacts by Stage" />
                    <SimpleBarChart data={charts.salesByStatus} title="Sales Pipeline by Status" />
                </div>

                {/* NL Query Section */}
                <div className="rounded-lg border p-4">
                    <h2 className="mb-2 font-semibold">Ask Analytics</h2>
                    <p className="text-muted-foreground mb-3 text-sm">Ask questions about your CRM data in plain English.</p>

                    <form onSubmit={handleNlQuery} className="flex gap-2">
                        <input
                            type="text"
                            value={nlQuery}
                            onChange={(e) => setNlQuery(e.target.value)}
                            placeholder="e.g. How many contacts were added this month?"
                            className="flex-1 rounded border px-3 py-2 text-sm"
                        />
                        <button
                            type="submit"
                            disabled={nlLoading || !nlQuery.trim()}
                            className="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-60"
                        >
                            {nlLoading && <Loader2 className="h-4 w-4 animate-spin" />}
                            Ask
                        </button>
                    </form>

                    {nlError && (
                        <p className="mt-3 text-sm text-red-500">{nlError}</p>
                    )}

                    {nlResult && (
                        <div className="mt-4 rounded-md bg-blue-50 p-3 dark:bg-blue-950">
                            <p className="text-sm font-medium">{nlResult.answer}</p>
                            {nlResult.sql_hint && (
                                <code className="text-muted-foreground mt-2 block text-xs">{nlResult.sql_hint}</code>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AppSidebarLayout>
    );
}
