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
    Award,
    Bot,
    CheckCircle,
    Download,
    Eye,
    Loader2,
    Pencil,
    Plus,
    Search,
    Trash2,
    Users,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

const COLUMN_STORAGE_KEY = 'fleet-drivers-columns';

function getStoredColumns(): { licenseNumber: boolean; status: boolean } {
    if (typeof window === 'undefined')
        return { licenseNumber: true, status: true };
    try {
        const s = localStorage.getItem(COLUMN_STORAGE_KEY);
        if (s)
            return {
                ...{ licenseNumber: true, status: true },
                ...JSON.parse(s),
            };
    } catch {
        /* ignore */
    }
    return { licenseNumber: true, status: true };
}

interface DriverRecord {
    id: number;
    first_name: string;
    last_name: string;
    status: string;
    license_number: string;
    license_expiry_date: string;
}
interface Props {
    drivers: {
        data: DriverRecord[];
        last_page: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters?: { status?: string };
    summary?: {
        total: number;
        active: number;
        low_safety: number;
        qualifications_expiring: number;
    };
}

export default function FleetDriversIndex({ drivers, filters = {}, summary }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<DriverRecord | null>(null);
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
                    list_type: 'drivers',
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
                    '/fleet/drivers',
                    suggested.filters as Record<string, string>,
                    { preserveState: false },
                );
            } else {
                setNlError(
                    'No filters suggested. Try e.g. "active drivers" or "expiring license".',
                );
            }
        } catch {
            setNlError('Request failed');
        } finally {
            setNlLoading(false);
        }
    }, [nlQuery]);

    const allIds = useMemo(() => drivers.data.map((r) => r.id), [drivers.data]);
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
        (key: 'licenseNumber' | 'status', value: boolean) => {
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

    const exportUrl = `/fleet/drivers/export${filters.status ? `?status=${encodeURIComponent(filters.status)}` : ''}`;
    const assistantPrompt = nlQuery.trim()
        ? `Find drivers: ${nlQuery.trim()}`
        : 'List and filter drivers.';
    const assistantHref = `/fleet/assistant?prompt=${encodeURIComponent(assistantPrompt)}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Drivers', href: '/fleet/drivers' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Drivers" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6">
                <FleetPageHeader
                    title="Drivers"
                    description="Manage drivers, licenses, and assignments."
                    action={
                        <div className="flex flex-wrap items-center gap-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href={assistantHref}>
                                    <Bot className="mr-2 size-4" />
                                    Ask assistant
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href="/fleet/drivers/create">
                                    <Plus className="mr-2 size-4" />
                                    New driver
                                </Link>
                            </Button>
                        </div>
                    }
                />

                {summary && (
                    <FleetIndexSummaryBar
                        stats={
                            [
                                { label: 'Total', value: summary.total, icon: Users },
                                { label: 'Active', value: summary.active, icon: CheckCircle, variant: 'success' },
                                { label: 'Low Safety (<70)', value: summary.low_safety, icon: AlertTriangle, variant: summary.low_safety > 0 ? 'danger' : 'default' },
                                { label: 'Quals Expiring', value: summary.qualifications_expiring, icon: Award, variant: summary.qualifications_expiring > 0 ? 'warning' : 'default' },
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
                            placeholder="e.g. active drivers, expiring license"
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
                            <Label
                                htmlFor="d-status"
                                className="text-xs font-medium text-muted-foreground"
                            >
                                Status
                            </Label>
                            <select
                                id="d-status"
                                name="status"
                                defaultValue={filters.status ?? ''}
                                className="flex h-9 w-32 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                            >
                                <option value="">All</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        <Button type="submit" variant="secondary" size="sm">
                            Apply filters
                        </Button>
                        {filters.status && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                asChild
                            >
                                <Link href="/fleet/drivers">Clear filters</Link>
                            </Button>
                        )}
                    </Form>
                </FleetGlassCard>

                <FleetGlassCard className="min-w-0 overflow-hidden" noPadding>
                    <div className="mb-2 flex h-9 flex-wrap items-center justify-between gap-2 border-b border-white/30 px-4 pt-4 pb-2">
                        <h3 className="text-base font-medium text-[#5b638d]">
                            All drivers —{' '}
                            {drivers.data.length === 0
                                ? 'No drivers yet'
                                : `${drivers.data.length} driver${drivers.data.length === 1 ? '' : 's'}`}
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
                                            '/fleet/drivers/bulk-destroy',
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
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button size="sm" variant="outline">
                                        Columns
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuCheckboxItem
                                        checked={columns.licenseNumber}
                                        onCheckedChange={(v) =>
                                            setColumn(
                                                'licenseNumber',
                                                v === true,
                                            )
                                        }
                                    >
                                        License number
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
                                <Link href="/fleet/drivers/create">
                                    <Plus className="mr-2 size-4" />
                                    New driver
                                </Link>
                            </Button>
                        </FleetPageToolbarRight>
                    </div>
                    {drivers.data.length === 0 ? (
                        <div className="px-6 pb-8">
                            <FleetEmptyState
                                icon={Users}
                                title="No drivers yet"
                                description="Add your first driver to manage assignments and compliance."
                                action={
                                    <Button asChild>
                                        <Link href="/fleet/drivers/create">
                                            <Plus className="mr-2 size-4" />
                                            Add driver
                                        </Link>
                                    </Button>
                                }
                            />
                        </div>
                    ) : (
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
                                            Name
                                        </TableHead>
                                        {columns.licenseNumber && (
                                            <TableHead className="h-11 px-4 font-semibold">
                                                License
                                            </TableHead>
                                        )}
                                        {columns.status && (
                                            <TableHead className="h-11 px-4 font-semibold">
                                                Status
                                            </TableHead>
                                        )}
                                        <TableHead className="h-11 w-[120px] px-4 text-right font-semibold">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {drivers.data.map((row) => (
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
                                                    aria-label={`Select ${row.first_name} ${row.last_name}`}
                                                />
                                            </TableCell>
                                            <TableCell className="px-4 py-3">
                                                <Link
                                                    href={`/fleet/drivers/${row.id}`}
                                                    className="font-medium text-foreground hover:underline"
                                                >
                                                    {row.first_name}{' '}
                                                    {row.last_name}
                                                </Link>
                                            </TableCell>
                                            {columns.licenseNumber && (
                                                <TableCell className="px-4 py-3 text-muted-foreground">
                                                    {row.license_number || '—'}
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
                                                        href={`/fleet/drivers/${row.id}`}
                                                        label="View"
                                                        variant="view"
                                                    >
                                                        <Eye className="size-4" />
                                                    </FleetActionIconLink>
                                                    <FleetActionIconLink
                                                        href={`/fleet/drivers/${row.id}/edit`}
                                                        label="Edit"
                                                        variant="edit"
                                                    >
                                                        <Pencil className="size-4" />
                                                    </FleetActionIconLink>
                                                    <FleetActionIconButton
                                                        label="Delete"
                                                        variant="delete"
                                                        onClick={() =>
                                                            setDeleteTarget(row)
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
                            <FleetPagination links={drivers.links ?? []} />
                        </div>
                    )}
                </FleetGlassCard>

                <Dialog
                    open={!!deleteTarget}
                    onOpenChange={(open) => !open && setDeleteTarget(null)}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete driver</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to delete{' '}
                                <strong>
                                    {deleteTarget?.first_name}{' '}
                                    {deleteTarget?.last_name}
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
                                    action={`/fleet/drivers/${deleteTarget.id}`}
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
