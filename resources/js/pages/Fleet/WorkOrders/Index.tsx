import {
    FleetActionIconButton,
    FleetActionIconLink,
    FleetEmptyState,
    FleetGlassCard,
    FleetGlassPill,
    FleetIndexSummaryBar,
    FleetPageHeader,
    FleetPageToolbar,
    FleetPageToolbarLeft,
    FleetPageToolbarRight,
    FleetPagination,
} from '@/components/fleet';
import type { SummaryStat } from '@/components/fleet';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    Bot,
    CheckCircle,
    ClipboardList,
    Clock,
    Download,
    Eye,
    Loader2,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-ring';

interface WorkOrderRecord {
    id: number;
    work_order_number: string;
    title: string;
    status: string;
    priority: string;
    scheduled_date: string | null;
    vehicle?: { id: number; registration: string };
}
interface Props {
    workOrders: {
        data: WorkOrderRecord[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: Record<string, string>;
    vehicles: { id: number; registration: string }[];
    statuses: { value: string; name: string }[];
    summary?: {
        open: number;
        overdue: number;
        completed_this_week: number;
        avg_resolution_days: number | null;
    };
}

export default function FleetWorkOrdersIndex({
    workOrders,
    filters,
    vehicles,
    statuses,
    summary,
}: Props) {
    const [deleteTarget, setDeleteTarget] = useState<WorkOrderRecord | null>(
        null,
    );
    const [nlQuery, setNlQuery] = useState('');
    const [nlLoading, setNlLoading] = useState(false);
    const [nlError, setNlError] = useState<string | null>(null);
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
    const allIds = useMemo(
        () => workOrders.data.map((r) => r.id),
        [workOrders.data],
    );
    const allSelected =
        allIds.length > 0 && allIds.every((id) => selectedIds.has(id));
    const toggleOne = useCallback((id: number) => {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    }, []);
    const toggleAll = useCallback(() => {
        if (allSelected) setSelectedIds(new Set());
        else setSelectedIds(new Set(allIds));
    }, [allSelected, allIds]);
    const applyNlFilters = useCallback(async () => {
        const q = nlQuery.trim();
        if (!q) return;
        setNlError(null);
        setNlLoading(true);
        try {
            const csrf =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '';
            const res = await fetch('/fleet/filters/interpret', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
                body: JSON.stringify({
                    list_type: 'work_orders',
                    natural_language_query: q,
                }),
            });
            const data = await res.json();
            if (!res.ok) {
                setNlError(data.error ?? 'Could not interpret filters');
                return;
            }
            const suggested = data.suggested as {
                filters?: Record<string, unknown>;
            } | null;
            if (
                suggested?.filters &&
                Object.keys(suggested.filters).length > 0
            ) {
                router.get(
                    '/fleet/work-orders',
                    suggested.filters as Record<string, string>,
                    { preserveState: false },
                );
            } else {
                setNlError(
                    'No filters suggested. Try e.g. "open work orders" or "by vehicle X".',
                );
            }
        } catch {
            setNlError('Request failed');
        } finally {
            setNlLoading(false);
        }
    }, [nlQuery]);
    const assistantPrompt = nlQuery.trim()
        ? `Find work orders: ${nlQuery.trim()}`
        : 'List and filter work orders.';
    const assistantHref = `/fleet/assistant?prompt=${encodeURIComponent(assistantPrompt)}`;
    const exportParams = new URLSearchParams();
    if (filters.vehicle_id) exportParams.set('vehicle_id', filters.vehicle_id);
    if (filters.status) exportParams.set('status', filters.status);
    const exportUrl = `/fleet/work-orders/export${exportParams.toString() ? `?${exportParams.toString()}` : ''}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Work orders', href: '/fleet/work-orders' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Work orders" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6">
                <FleetPageHeader
                    title="Work orders"
                    description="Manage maintenance and repair work orders."
                    action={
                        <div className="flex flex-wrap items-center gap-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href={assistantHref}>
                                    <Bot className="mr-2 size-4" />
                                    Ask assistant
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href="/fleet/work-orders/create">
                                    <Plus className="mr-2 size-4" />
                                    New work order
                                </Link>
                            </Button>
                        </div>
                    }
                />

                {summary && (
                    <FleetIndexSummaryBar
                        stats={
                            [
                                { label: 'Open', value: summary.open, icon: ClipboardList },
                                { label: 'Overdue', value: summary.overdue, icon: AlertTriangle, variant: summary.overdue > 0 ? 'danger' : 'default' },
                                { label: 'Completed This Week', value: summary.completed_this_week, icon: CheckCircle, variant: 'success' },
                                { label: 'Avg Resolution', value: summary.avg_resolution_days !== null ? `${summary.avg_resolution_days}d` : '—', icon: Clock },
                            ] satisfies SummaryStat[]
                        }
                    />
                )}

                <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <label
                        htmlFor="nl-filter"
                        className="text-sm text-muted-foreground"
                    >
                        Describe what you want:
                    </label>
                    <div className="flex flex-1 flex-wrap items-center gap-2">
                        <Input
                            id="nl-filter"
                            placeholder="e.g. open work orders, by vehicle"
                            value={nlQuery}
                            onChange={(e) => {
                                setNlQuery(e.target.value);
                                setNlError(null);
                            }}
                            onKeyDown={(e) =>
                                e.key === 'Enter' && applyNlFilters()
                            }
                            className="max-w-xs"
                            disabled={nlLoading}
                            aria-describedby={
                                nlError ? 'nl-filter-error' : undefined
                            }
                        />
                        <Button
                            size="sm"
                            variant="secondary"
                            onClick={applyNlFilters}
                            disabled={nlLoading || !nlQuery.trim()}
                        >
                            {nlLoading ? (
                                <Loader2 className="mr-1 size-3.5 animate-spin" />
                            ) : (
                                <Search className="mr-1 size-3.5" />
                            )}
                            Apply filters
                        </Button>
                        <Button size="sm" variant="ghost" asChild>
                            <Link href={assistantHref}>
                                <Bot className="mr-1 size-3.5" />
                                Ask assistant
                            </Link>
                        </Button>
                    </div>
                    {nlError && (
                        <p
                            id="nl-filter-error"
                            className="text-sm text-destructive"
                            role="alert"
                        >
                            {nlError}
                        </p>
                    )}
                </div>

                <FleetGlassCard className="p-3">
                    <Form method="get">
                        <FleetPageToolbar>
                            <FleetPageToolbarLeft className="flex flex-wrap items-end gap-3">
                                <div className="space-y-2">
                                    <Label htmlFor="wo-vehicle">Vehicle</Label>
                                    <select
                                        id="wo-vehicle"
                                        name="vehicle_id"
                                        defaultValue={filters.vehicle_id ?? ''}
                                        className={selectClass + ' w-[180px]'}
                                    >
                                        <option value="">All</option>
                                        {vehicles.map((v) => (
                                            <option key={v.id} value={v.id}>
                                                {v.registration}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="wo-status">Status</Label>
                                    <select
                                        id="wo-status"
                                        name="status"
                                        defaultValue={filters.status ?? ''}
                                        className={selectClass + ' w-[160px]'}
                                    >
                                        <option value="">All</option>
                                        {statuses.map((s) => (
                                            <option
                                                key={s.value}
                                                value={s.value}
                                            >
                                                {s.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <Button
                                    type="submit"
                                    variant="secondary"
                                    size="sm"
                                >
                                    Apply
                                </Button>
                            </FleetPageToolbarLeft>
                            <FleetPageToolbarRight>
                                <Button asChild size="sm">
                                    <Link href="/fleet/work-orders/create">
                                        <Plus className="mr-2 size-4" />
                                        New work order
                                    </Link>
                                </Button>
                            </FleetPageToolbarRight>
                        </FleetPageToolbar>
                    </Form>
                </FleetGlassCard>

                <FleetGlassCard className="min-w-0 overflow-hidden" noPadding>
                    <div className="mb-2 flex h-9 flex-wrap items-center justify-between gap-2 border-b border-white/30 px-4 pt-4 pb-2">
                        <h3 className="text-base font-medium text-[#5b638d]">
                            All work orders —{' '}
                            {workOrders.data.length === 0
                                ? 'None'
                                : `${workOrders.data.length} work order${workOrders.data.length === 1 ? '' : 's'}`}
                        </h3>
                        {selectedIds.size > 0 && (
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="text-sm text-muted-foreground">
                                    {selectedIds.size} selected
                                </span>
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="sm"
                                    onClick={() => {
                                        router.post(
                                            '/fleet/work-orders/bulk-destroy',
                                            { ids: Array.from(selectedIds) },
                                            {
                                                preserveScroll: true,
                                                onSuccess: () =>
                                                    setSelectedIds(new Set()),
                                            },
                                        );
                                    }}
                                >
                                    <Trash2 className="mr-2 size-4" />
                                    Delete selected
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setSelectedIds(new Set())}
                                >
                                    Clear
                                </Button>
                            </div>
                        )}
                        <FleetPageToolbarRight>
                            <Button size="sm" variant="outline" asChild>
                                <a href={exportUrl} download>
                                    <Download className="mr-2 size-4" />
                                    Export
                                </a>
                            </Button>
                            <Button asChild size="sm">
                                <Link href="/fleet/work-orders/create">
                                    <Plus className="mr-2 size-4" />
                                    New
                                </Link>
                            </Button>
                        </FleetPageToolbarRight>
                    </div>
                    {workOrders.data.length === 0 ? (
                        <div className="px-6 pb-8">
                            <FleetEmptyState
                                icon={ClipboardList}
                                title="No work orders yet"
                                description="Create a work order to schedule maintenance or repairs."
                                aiSuggestion="What maintenance should I schedule first?"
                                features={[
                                    'Schedule preventive and corrective maintenance',
                                    'Track parts, labour, and costs per job',
                                    'Set priority and assign to workshop bays',
                                    'AI predicts when vehicles need servicing',
                                ]}
                                action={
                                    <Button asChild>
                                        <Link href="/fleet/work-orders/create">
                                            <Plus className="mr-2 size-4" />
                                            Create work order
                                        </Link>
                                    </Button>
                                }
                            />
                        </div>
                    ) : (
                        <>
                            <div className="fleet-glass-table w-full overflow-x-auto">
                                <Table className="min-w-[700px]">
                                    <TableHeader>
                                        <TableRow className="border-0 bg-transparent hover:bg-transparent">
                                            <TableHead className="sticky-left-cell sticky left-0 z-10 h-11 w-10 px-4">
                                                <Checkbox
                                                    checked={allSelected}
                                                    onCheckedChange={toggleAll}
                                                    aria-label="Select all"
                                                />
                                            </TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">
                                                Number
                                            </TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">
                                                Title
                                            </TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">
                                                Vehicle
                                            </TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">
                                                Status
                                            </TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">
                                                Priority
                                            </TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">
                                                Scheduled
                                            </TableHead>
                                            <TableHead className="h-11 w-[120px] px-4 text-right font-semibold">
                                                Actions
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {workOrders.data.map((row) => (
                                            <TableRow
                                                key={row.id}
                                                className="group transition-colors"
                                            >
                                                <TableCell className="sticky-left-cell sticky left-0 z-10 px-4 py-3">
                                                    <Checkbox
                                                        checked={selectedIds.has(
                                                            row.id,
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleOne(row.id)
                                                        }
                                                        aria-label={`Select ${row.work_order_number}`}
                                                    />
                                                </TableCell>
                                                <TableCell className="px-4 py-3">
                                                    <Link
                                                        href={`/fleet/work-orders/${row.id}`}
                                                        className="font-medium text-foreground hover:underline"
                                                    >
                                                        {row.work_order_number}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">
                                                    {row.title}
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">
                                                    {row.vehicle
                                                        ?.registration ?? '—'}
                                                </TableCell>
                                                <TableCell className="px-4 py-3">
                                                    <FleetGlassPill variant="default">
                                                        {row.status}
                                                    </FleetGlassPill>
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">
                                                    {row.priority}
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">
                                                    {row.scheduled_date
                                                        ? new Date(
                                                              row.scheduled_date,
                                                          ).toLocaleDateString()
                                                        : '—'}
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <FleetActionIconLink
                                                            href={`/fleet/work-orders/${row.id}`}
                                                            label="View"
                                                            variant="view"
                                                        >
                                                            <Eye className="size-4" />
                                                        </FleetActionIconLink>
                                                        <FleetActionIconLink
                                                            href={`/fleet/work-orders/${row.id}/edit`}
                                                            label="Edit"
                                                            variant="edit"
                                                        >
                                                            <Pencil className="size-4" />
                                                        </FleetActionIconLink>
                                                        <FleetActionIconButton
                                                            label="Delete"
                                                            variant="delete"
                                                            onClick={() =>
                                                                setDeleteTarget(
                                                                    row,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </FleetActionIconButton>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                            <FleetPagination
                                links={workOrders.links ?? []}
                                showingLabel={
                                    workOrders.data.length > 0
                                        ? `Showing ${workOrders.data.length} work order${workOrders.data.length === 1 ? '' : 's'}`
                                        : undefined
                                }
                            />
                        </>
                    )}
                </FleetGlassCard>

                <Dialog
                    open={!!deleteTarget}
                    onOpenChange={(open) => !open && setDeleteTarget(null)}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete work order</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to delete{' '}
                                <strong>
                                    {deleteTarget?.work_order_number}
                                </strong>
                                ? This action cannot be undone.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button variant="outline">Cancel</Button>
                            </DialogClose>
                            {deleteTarget && (
                                <Form
                                    action={`/fleet/work-orders/${deleteTarget.id}`}
                                    method="delete"
                                    onSubmit={() => setDeleteTarget(null)}
                                >
                                    <Button type="submit" variant="destructive">
                                        Delete
                                    </Button>
                                </Form>
                            )}
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
