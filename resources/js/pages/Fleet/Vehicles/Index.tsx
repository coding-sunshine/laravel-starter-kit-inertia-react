import {
    FleetActionIconButton,
    FleetActionIconLink,
    FleetEmptyState,
    FleetGlassCard,
    FleetGlassPill,
    FleetIndexSummaryBar,
    FleetPageHeader,
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
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
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
    Car,
    CheckCircle,
    Download,
    Eye,
    Loader2,
    Pencil,
    Plus,
    Search,
    Trash2,
    Wrench,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

const COLUMN_STORAGE_KEY = 'fleet-vehicles-columns';

function getStoredColumns(): { makeModel: boolean; status: boolean } {
    if (typeof window === 'undefined') return { makeModel: true, status: true };
    try {
        const s = localStorage.getItem(COLUMN_STORAGE_KEY);
        if (s)
            return { ...{ makeModel: true, status: true }, ...JSON.parse(s) };
    } catch {
        /* ignore */
    }
    return { makeModel: true, status: true };
}

interface VehicleRecord {
    id: number;
    registration: string;
    make: string;
    model: string;
    status: string;
}
interface Props {
    vehicles: {
        data: VehicleRecord[];
        last_page: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters?: {
        status?: string;
        search?: string;
        odometer_min?: string;
        odometer_max?: string;
    };
    summary?: {
        total: number;
        active: number;
        in_maintenance: number;
        due_for_service: number;
    };
}

export default function FleetVehiclesIndex({ vehicles, filters = {}, summary }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<VehicleRecord | null>(
        null,
    );
    const [nlQuery, setNlQuery] = useState('');
    const [nlLoading, setNlLoading] = useState(false);
    const [nlError, setNlError] = useState<string | null>(null);
    const [columns, setColumns] = useState(getStoredColumns);
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());

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
                    list_type: 'vehicles',
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
                description?: string;
            } | null;
            if (
                suggested?.filters &&
                Object.keys(suggested.filters).length > 0
            ) {
                router.get(
                    '/fleet/vehicles',
                    suggested.filters as Record<string, string>,
                    { preserveState: false },
                );
            } else {
                setNlError(
                    'No filters suggested. Try e.g. "active vehicles" or "over 100k miles".',
                );
            }
        } catch {
            setNlError('Request failed');
        } finally {
            setNlLoading(false);
        }
    }, [nlQuery]);

    const allIds = useMemo(
        () => vehicles.data.map((r) => r.id),
        [vehicles.data],
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

    const setColumn = useCallback(
        (key: 'makeModel' | 'status', value: boolean) => {
            setColumns((prev) => {
                const next = { ...prev, [key]: value };
                try {
                    localStorage.setItem(
                        COLUMN_STORAGE_KEY,
                        JSON.stringify(next),
                    );
                } catch {
                    /* ignore */
                }
                return next;
            });
        },
        [],
    );

    const exportParams = new URLSearchParams();
    if (filters.status) exportParams.set('status', filters.status);
    if (filters.search) exportParams.set('search', filters.search);
    if (filters.odometer_min)
        exportParams.set('odometer_min', filters.odometer_min);
    if (filters.odometer_max)
        exportParams.set('odometer_max', filters.odometer_max);
    const exportUrl = `/fleet/vehicles/export${exportParams.toString() ? `?${exportParams.toString()}` : ''}`;
    const hasAdvancedFilters = Boolean(
        filters.status ||
        filters.search ||
        filters.odometer_min ||
        filters.odometer_max,
    );
    const assistantPrompt = nlQuery.trim()
        ? `Find vehicles: ${nlQuery.trim()}`
        : 'List and filter vehicles.';
    const assistantHref = `/fleet/assistant?prompt=${encodeURIComponent(assistantPrompt)}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicles', href: '/fleet/vehicles' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Vehicles" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6">
                <FleetPageHeader
                    title="Vehicles"
                    description="Manage fleet vehicles, registration, and assignment."
                    action={
                        <div className="flex flex-wrap items-center gap-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href={assistantHref}>
                                    <Bot className="mr-2 size-4" />
                                    Ask assistant
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href="/fleet/vehicles/create">
                                    <Plus className="mr-2 size-4" />
                                    New vehicle
                                </Link>
                            </Button>
                        </div>
                    }
                />

                {summary && (
                    <FleetIndexSummaryBar
                        stats={
                            [
                                { label: 'Total', value: summary.total, icon: Car },
                                { label: 'Active', value: summary.active, icon: CheckCircle, variant: 'success' },
                                { label: 'In Maintenance', value: summary.in_maintenance, icon: Wrench, variant: 'warning' },
                                { label: 'Due for Service', value: summary.due_for_service, icon: AlertTriangle, variant: summary.due_for_service > 0 ? 'danger' : 'default' },
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
                            placeholder="e.g. vehicles over 100k miles, active only"
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
                    <Form
                        method="get"
                        className="flex flex-wrap items-end gap-3"
                    >
                        <div className="space-y-1">
                            <label
                                htmlFor="v-status"
                                className="text-xs font-medium text-muted-foreground"
                            >
                                Status
                            </label>
                            <select
                                id="v-status"
                                name="status"
                                defaultValue={filters.status ?? ''}
                                className="flex h-9 w-32 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                <option value="">All</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="retired">Retired</option>
                            </select>
                        </div>
                        <div className="space-y-1">
                            <label
                                htmlFor="v-search"
                                className="text-xs font-medium text-muted-foreground"
                            >
                                Search
                            </label>
                            <Input
                                id="v-search"
                                name="search"
                                placeholder="Reg, make, model"
                                defaultValue={filters.search}
                                className="h-9 w-40"
                            />
                        </div>
                        <div className="space-y-1">
                            <label
                                htmlFor="v-odo-min"
                                className="text-xs font-medium text-muted-foreground"
                            >
                                Odometer min
                            </label>
                            <Input
                                id="v-odo-min"
                                name="odometer_min"
                                type="number"
                                min={0}
                                placeholder="0"
                                defaultValue={filters.odometer_min}
                                className="h-9 w-28"
                            />
                        </div>
                        <div className="space-y-1">
                            <label
                                htmlFor="v-odo-max"
                                className="text-xs font-medium text-muted-foreground"
                            >
                                Odometer max
                            </label>
                            <Input
                                id="v-odo-max"
                                name="odometer_max"
                                type="number"
                                min={0}
                                placeholder="—"
                                defaultValue={filters.odometer_max}
                                className="h-9 w-28"
                            />
                        </div>
                        <Button type="submit" variant="secondary" size="sm">
                            Apply filters
                        </Button>
                        {hasAdvancedFilters && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                asChild
                            >
                                <Link href="/fleet/vehicles">
                                    Clear filters
                                </Link>
                            </Button>
                        )}
                    </Form>
                </FleetGlassCard>

                <FleetGlassCard className="min-w-0 overflow-hidden" noPadding>
                    <div className="mb-2 flex h-9 flex-wrap items-center justify-between gap-2 border-b border-white/30 px-4 pt-4 pb-2">
                        <h3 className="text-base font-medium text-[#5b638d]">
                            All vehicles —{' '}
                            {vehicles.data.length === 0
                                ? 'No vehicles yet'
                                : `${vehicles.data.length} vehicle${vehicles.data.length === 1 ? '' : 's'}`}
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
                                            '/fleet/vehicles/bulk-destroy',
                                            {
                                                ids: Array.from(selectedIds),
                                            },
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
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button size="sm" variant="outline">
                                        Columns
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuCheckboxItem
                                        checked={columns.makeModel}
                                        onCheckedChange={(v) =>
                                            setColumn('makeModel', v === true)
                                        }
                                    >
                                        Make / Model
                                    </DropdownMenuCheckboxItem>
                                    <DropdownMenuCheckboxItem
                                        checked={columns.status}
                                        onCheckedChange={(v) =>
                                            setColumn('status', v === true)
                                        }
                                    >
                                        Status
                                    </DropdownMenuCheckboxItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                            <Button size="sm" variant="outline" asChild>
                                <a href={exportUrl} download>
                                    <Download className="mr-2 size-4" />
                                    Export
                                </a>
                            </Button>
                            <Button asChild size="sm">
                                <Link href="/fleet/vehicles/create">
                                    <Plus className="mr-2 size-4" />
                                    New vehicle
                                </Link>
                            </Button>
                        </FleetPageToolbarRight>
                    </div>
                    {vehicles.data.length === 0 ? (
                        <div className="px-6 pb-8">
                            <FleetEmptyState
                                icon={Car}
                                illustration="/images/empty/vehicles.svg"
                                title="No vehicles yet"
                                description="Add your first vehicle to start managing your fleet."
                                action={
                                    <Button asChild>
                                        <Link href="/fleet/vehicles/create">
                                            <Plus className="mr-2 size-4" />
                                            Add vehicle
                                        </Link>
                                    </Button>
                                }
                            />
                        </div>
                    ) : (
                        <>
                            <div className="fleet-glass-table w-full overflow-x-auto">
                                <Table className="min-w-[600px]">
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
                                                Registration
                                            </TableHead>
                                            {columns.makeModel && (
                                                <TableHead className="h-11 px-4 font-semibold">
                                                    Make / Model
                                                </TableHead>
                                            )}
                                            {columns.status && (
                                                <TableHead className="h-11 px-4 font-semibold">
                                                    Status
                                                </TableHead>
                                            )}
                                            <TableHead className="h-11 w-[80px] px-4 text-right font-semibold">
                                                Actions
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {vehicles.data.map((row) => (
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
                                                        aria-label={`Select ${row.registration}`}
                                                    />
                                                </TableCell>
                                                <TableCell className="px-4 py-3">
                                                    <Link
                                                        href={`/fleet/vehicles/${row.id}`}
                                                        className="font-medium text-foreground hover:underline"
                                                    >
                                                        {row.registration}
                                                    </Link>
                                                </TableCell>
                                                {columns.makeModel && (
                                                    <TableCell className="px-4 py-3 text-muted-foreground">
                                                        {row.make} {row.model}
                                                    </TableCell>
                                                )}
                                                {columns.status && (
                                                    <TableCell className="px-4 py-3">
                                                        <FleetGlassPill
                                                            variant={
                                                                row.status ===
                                                                'active'
                                                                    ? 'success'
                                                                    : 'default'
                                                            }
                                                        >
                                                            {row.status}
                                                        </FleetGlassPill>
                                                    </TableCell>
                                                )}
                                                <TableCell className="px-4 py-3 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <FleetActionIconLink
                                                            href={`/fleet/vehicles/${row.id}`}
                                                            label="View details"
                                                            variant="view"
                                                        >
                                                            <Eye className="size-4" />
                                                        </FleetActionIconLink>
                                                        <FleetActionIconLink
                                                            href={`/fleet/vehicles/${row.id}/edit`}
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
                            <FleetPagination links={vehicles.links ?? []} />
                        </>
                    )}
                </FleetGlassCard>

                <Dialog
                    open={!!deleteTarget}
                    onOpenChange={(open) => !open && setDeleteTarget(null)}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete vehicle</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to delete{' '}
                                <strong>{deleteTarget?.registration}</strong>?
                                This action cannot be undone.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button variant="outline">Cancel</Button>
                            </DialogClose>
                            {deleteTarget && (
                                <Form
                                    action={`/fleet/vehicles/${deleteTarget.id}`}
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
