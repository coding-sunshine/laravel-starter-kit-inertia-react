import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { LayoutList, Trello } from 'lucide-react';
import { useState } from 'react';

interface KanbanReservation {
    id: number;
    stage: string;
    purchase_price: number | null;
    primary_contact_id: number | null;
    lot_id: number | null;
    project_id: number | null;
    deposit_status: string;
    days_in_stage: number;
    created_at: string | null;
}

interface KanbanColumn {
    stage: string;
    label: string;
    reservations: KanbanReservation[];
}

interface TableData {
    data: Record<string, unknown>[];
    [key: string]: unknown;
}

interface Props {
    kanbanColumns: KanbanColumn[];
    tableData: TableData;
    searchableColumns: string[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Deal Tracker', href: '/deal-tracker' }];

const STAGE_COLORS: Record<string, string> = {
    enquiry: 'bg-gray-100 text-gray-700',
    qualified: 'bg-blue-100 text-blue-700',
    reservation: 'bg-yellow-100 text-yellow-700',
    contract: 'bg-orange-100 text-orange-700',
    unconditional: 'bg-purple-100 text-purple-700',
    settled: 'bg-green-100 text-green-700',
};

function formatCurrency(value: number | null): string {
    if (value === null) {
        return '—';
    }

    return new Intl.NumberFormat('en-AU', { style: 'currency', currency: 'AUD', maximumFractionDigits: 0 }).format(value);
}

function DealCard({ reservation, onStageChange }: { reservation: KanbanReservation; onStageChange: (id: number, stage: string) => void }) {
    const [dragging, setDragging] = useState(false);

    return (
        <div
            className={`rounded-lg border bg-white p-3 shadow-sm transition-opacity ${dragging ? 'opacity-50' : ''}`}
            draggable
            onDragStart={(e) => {
                e.dataTransfer.setData('reservationId', String(reservation.id));
                e.dataTransfer.setData('currentStage', reservation.stage);
                setDragging(true);
            }}
            onDragEnd={() => setDragging(false)}
        >
            <div className="mb-2 flex items-start justify-between gap-1">
                <span className="text-sm font-medium text-gray-900">
                    {reservation.primary_contact_id ? `Contact #${reservation.primary_contact_id}` : 'No Contact'}
                </span>
                <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${STAGE_COLORS[reservation.stage] ?? 'bg-gray-100 text-gray-700'}`}>
                    {reservation.stage}
                </span>
            </div>

            <div className="space-y-1 text-xs text-gray-500">
                {reservation.project_id && <p>Project #{reservation.project_id}</p>}
                {reservation.lot_id && <p>Lot #{reservation.lot_id}</p>}
                <p className="font-medium text-gray-700">{formatCurrency(reservation.purchase_price)}</p>
                <p>{reservation.days_in_stage}d in stage</p>
                {reservation.deposit_status && reservation.deposit_status !== 'pending' && (
                    <p className="capitalize">Deposit: {reservation.deposit_status}</p>
                )}
            </div>
        </div>
    );
}

function KanbanBoard({ columns, onStageChange }: { columns: KanbanColumn[]; onStageChange: (id: number, stage: string) => void }) {
    const [dragOver, setDragOver] = useState<string | null>(null);

    function handleDrop(e: React.DragEvent, targetStage: string): void {
        e.preventDefault();
        const reservationId = parseInt(e.dataTransfer.getData('reservationId'), 10);
        const currentStage = e.dataTransfer.getData('currentStage');

        if (currentStage !== targetStage && !isNaN(reservationId)) {
            onStageChange(reservationId, targetStage);
        }

        setDragOver(null);
    }

    return (
        <div className="flex gap-4 overflow-x-auto pb-4">
            {columns.map((col) => (
                <div
                    key={col.stage}
                    className={`flex min-w-[220px] flex-col rounded-lg border-2 p-3 transition-colors ${
                        dragOver === col.stage ? 'border-blue-400 bg-blue-50' : 'border-transparent bg-gray-50'
                    }`}
                    onDragOver={(e) => {
                        e.preventDefault();
                        setDragOver(col.stage);
                    }}
                    onDragLeave={() => setDragOver(null)}
                    onDrop={(e) => handleDrop(e, col.stage)}
                >
                    <div className="mb-3 flex items-center justify-between">
                        <h3 className="text-sm font-semibold text-gray-700">{col.label}</h3>
                        <span className="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600">
                            {col.reservations.length}
                        </span>
                    </div>

                    <div className="flex flex-col gap-2">
                        {col.reservations.length === 0 ? (
                            <p className="py-4 text-center text-xs text-gray-400">No deals</p>
                        ) : (
                            col.reservations.map((r) => (
                                <DealCard key={r.id} reservation={r} onStageChange={onStageChange} />
                            ))
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}

function ListView({ tableData }: { tableData: TableData }) {
    const rows = tableData.data ?? [];

    return (
        <div className="overflow-x-auto rounded-lg border">
            <table className="min-w-full divide-y divide-gray-200 text-sm">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">ID</th>
                        <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Stage</th>
                        <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Contact</th>
                        <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Project</th>
                        <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Price</th>
                        <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Deposit</th>
                        <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Created</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-100 bg-white">
                    {rows.length === 0 ? (
                        <tr>
                            <td colSpan={7} className="px-4 py-8 text-center text-gray-400">
                                No reservations found.
                            </td>
                        </tr>
                    ) : (
                        rows.map((row) => (
                            <tr key={String(row.id)} className="hover:bg-gray-50">
                                <td className="px-4 py-2 font-mono text-xs text-gray-500">{String(row.id ?? '—')}</td>
                                <td className="px-4 py-2">
                                    <span
                                        className={`rounded-full px-2 py-0.5 text-xs font-medium capitalize ${STAGE_COLORS[String(row.stage ?? '')] ?? 'bg-gray-100 text-gray-700'}`}
                                    >
                                        {String(row.stage ?? '—')}
                                    </span>
                                </td>
                                <td className="px-4 py-2 text-gray-700">{row.primary_contact_id ? `#${String(row.primary_contact_id)}` : '—'}</td>
                                <td className="px-4 py-2 text-gray-700">{row.project_id ? `#${String(row.project_id)}` : '—'}</td>
                                <td className="px-4 py-2 text-gray-700">
                                    {row.purchase_price != null ? formatCurrency(Number(row.purchase_price)) : '—'}
                                </td>
                                <td className="px-4 py-2 capitalize text-gray-700">{String(row.deposit_status ?? '—')}</td>
                                <td className="px-4 py-2 text-gray-500">{String(row.created_at ?? '—')}</td>
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}

export default function DealTrackerIndexPage({ kanbanColumns, tableData }: Props) {
    const [view, setView] = useState<'kanban' | 'list'>('kanban');
    const [columns, setColumns] = useState<KanbanColumn[]>(kanbanColumns);

    function handleStageChange(reservationId: number, newStage: string): void {
        router.patch(
            `/deal-tracker/${reservationId}/stage`,
            { stage: newStage },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setColumns((prev) =>
                        prev.map((col) => {
                            const reservation = prev.flatMap((c) => c.reservations).find((r) => r.id === reservationId);

                            if (!reservation) {
                                return col;
                            }

                            if (col.stage === reservation.stage) {
                                return { ...col, reservations: col.reservations.filter((r) => r.id !== reservationId) };
                            }

                            if (col.stage === newStage) {
                                return { ...col, reservations: [...col.reservations, { ...reservation, stage: newStage }] };
                            }

                            return col;
                        }),
                    );
                },
            },
        );
    }

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Deal Tracker" />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold" data-pan="deal-tracker-tab">
                            Deal Tracker
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">Manage your reservation pipeline by stage.</p>
                    </div>

                    <div className="flex items-center gap-2 rounded-lg border p-1">
                        <button
                            onClick={() => setView('kanban')}
                            data-pan="deal-tracker-kanban"
                            className={`flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm transition-colors ${
                                view === 'kanban' ? 'bg-white shadow-sm' : 'text-gray-500 hover:text-gray-700'
                            }`}
                        >
                            <Trello className="h-4 w-4" />
                            Kanban
                        </button>
                        <button
                            onClick={() => setView('list')}
                            className={`flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm transition-colors ${
                                view === 'list' ? 'bg-white shadow-sm' : 'text-gray-500 hover:text-gray-700'
                            }`}
                        >
                            <LayoutList className="h-4 w-4" />
                            List
                        </button>
                    </div>
                </div>

                {view === 'kanban' ? (
                    <KanbanBoard columns={columns} onStageChange={handleStageChange} />
                ) : (
                    <ListView tableData={tableData} />
                )}
            </div>
        </AppSidebarLayout>
    );
}
