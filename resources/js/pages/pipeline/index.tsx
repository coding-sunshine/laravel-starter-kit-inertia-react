import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { List, Kanban } from 'lucide-react';
import { useState } from 'react';

export interface SalePipelineItem {
    id: number;
    status: string;
    client_contact_id: number | null;
    lot_id: number | null;
    project_id: number | null;
    comms_in_total: number | null;
    comms_out_total: number | null;
    settled_at: string | null;
    created_at: string | null;
    status_updated_at: string | null;
}

interface Props {
    grouped: Record<string, SalePipelineItem[]>;
    statuses: string[];
    total: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/sales' },
    { title: 'Pipeline', href: '/pipeline' },
];

const STATUS_COLORS: Record<string, string> = {
    lead: 'bg-gray-100 border-gray-300',
    prospect: 'bg-blue-50 border-blue-300',
    active: 'bg-green-50 border-green-300',
    settling_soon: 'bg-yellow-50 border-yellow-300',
    settled: 'bg-purple-50 border-purple-300',
    lost: 'bg-red-50 border-red-300',
};

function StatusBadge({ status }: { status: string }) {
    const colorClass = STATUS_COLORS[status] ?? 'bg-gray-100 border-gray-300';
    return (
        <span
            className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium capitalize ${colorClass}`}
        >
            {status.replace(/_/g, ' ')}
        </span>
    );
}

function SaleCard({ sale }: { sale: SalePipelineItem }) {
    return (
        <div className="rounded-lg border bg-card p-3 shadow-sm">
            <div className="flex items-center justify-between">
                <span className="text-sm font-medium">Sale #{sale.id}</span>
                <StatusBadge status={sale.status} />
            </div>
            {sale.lot_id && (
                <p className="mt-1 text-xs text-muted-foreground">
                    Lot #{sale.lot_id}
                </p>
            )}
            {sale.comms_in_total != null && (
                <p className="mt-1 text-xs text-muted-foreground">
                    Comms In: ${sale.comms_in_total.toLocaleString()}
                </p>
            )}
            {sale.created_at && (
                <p className="mt-1 text-xs text-muted-foreground">
                    {sale.created_at}
                </p>
            )}
        </div>
    );
}

export default function PipelineIndexPage({ grouped, statuses, total }: Props) {
    const [view, setView] = useState<'kanban' | 'list'>('kanban');

    const allStatuses = statuses.length > 0 ? statuses : Object.keys(grouped);

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Pipeline" />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="pipeline-index"
            >
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Sales Pipeline
                        </h1>
                        <p className="text-muted-foreground">
                            {total} total sales
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <button
                            onClick={() => setView('kanban')}
                            className={`inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium transition-colors ${
                                view === 'kanban'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'border bg-background hover:bg-accent'
                            }`}
                        >
                            <Kanban className="size-4" />
                            Kanban
                        </button>
                        <button
                            onClick={() => setView('list')}
                            className={`inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium transition-colors ${
                                view === 'list'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'border bg-background hover:bg-accent'
                            }`}
                        >
                            <List className="size-4" />
                            List
                        </button>
                    </div>
                </div>

                {view === 'kanban' ? (
                    <div className="flex gap-4 overflow-x-auto pb-4">
                        {allStatuses.map((status) => {
                            const cards = grouped[status] ?? [];
                            return (
                                <div
                                    key={status}
                                    className="min-w-72 flex-shrink-0"
                                >
                                    <div className="mb-2 flex items-center justify-between">
                                        <h3 className="font-semibold capitalize">
                                            {status.replace(/_/g, ' ')}
                                        </h3>
                                        <span className="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                            {cards.length}
                                        </span>
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        {cards.map((sale) => (
                                            <SaleCard
                                                key={sale.id}
                                                sale={sale}
                                            />
                                        ))}
                                        {cards.length === 0 && (
                                            <div className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                                                No sales
                                            </div>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    <div className="rounded-lg border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-3 text-left font-medium">
                                        ID
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Lot
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Comms In
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Created
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {allStatuses.flatMap((status) =>
                                    (grouped[status] ?? []).map((sale) => (
                                        <tr
                                            key={sale.id}
                                            className="border-b last:border-0 hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3">
                                                #{sale.id}
                                            </td>
                                            <td className="px-4 py-3">
                                                <StatusBadge
                                                    status={sale.status}
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {sale.lot_id
                                                    ? `#${sale.lot_id}`
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {sale.comms_in_total != null
                                                    ? `$${sale.comms_in_total.toLocaleString()}`
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {sale.created_at ?? '—'}
                                            </td>
                                        </tr>
                                    )),
                                )}
                            </tbody>
                        </table>
                        {total === 0 && (
                            <div className="py-16 text-center text-muted-foreground">
                                No sales found
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AppSidebarLayout>
    );
}
