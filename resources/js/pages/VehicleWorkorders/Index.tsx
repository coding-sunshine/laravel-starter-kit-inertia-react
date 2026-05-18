import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Heading from '@/components/heading';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SearchableSelect, type SearchableSelectOption } from '@/components/ui/searchable-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn } from '@/lib/utils';
import { Filter, Pencil, Trash2, Truck, CarFront } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { type BreadcrumbItem } from '@/types';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface VehicleWorkorder {
    id: number;
    siding_id: number;
    vehicle_no: string | null;
    rcd_pin_no: string | null;
    transport_name: string | null;
    wo_no: string | null;
    wo_no_2: string | null;
    work_order_date: string | null;
    issued_date: string | null;
    proprietor_name: string | null;
    represented_by: string | null;
    place: string | null;
    address: string | null;
    tyres: number | null;
    tare_weight: number | string | null;
    mobile_no_1: string | null;
    mobile_no_2: string | null;
    owner_type: string | null;
    regd_date: string | null;
    permit_validity_date: string | null;
    tax_validity_date: string | null;
    fitness_validity_date: string | null;
    insurance_validity_date: string | null;
    maker_model: string | null;
    make: string | null;
    model: string | null;
    remarks: string | null;
    recommended_by: string | null;
    referenced: string | null;
    local_or_non_local: string | null;
    pan_no: string | null;
    gst_no: string | null;
    siding?: Siding;
}

interface PaginatedWorkorders {
    data: VehicleWorkorder[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface TransportRegistrationRow {
    id: number;
    siding_id: number | null;
    work_order_no_1: string | null;
    work_order_no_2: string | null;
    work_order_date: string | null;
    transporter_name: string | null;
    /** Omitted or true = active when older payloads omit the flag. */
    is_active?: boolean | null;
    siding?: Siding | null;
}

interface PaginatedTransportRegistrations {
    data: TransportRegistrationRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface TransportRegistrationPermissions {
    canCreate: boolean;
    canUpdate: boolean;
    canDelete: boolean;
}

interface Filters {
    view?: 'vehicles' | 'transporters';
    page?: string | number;
    siding_id?: string | number;
    transport_name?: string;
    vehicle_no?: string;
    regd_date?: string;
}

interface Props {
    view: 'vehicles' | 'transporters';
    vehicleWorkorders: PaginatedWorkorders | null;
    transportWorkOrderRegistrations: PaginatedTransportRegistrations | null;
    transportRegistrationPermissions: TransportRegistrationPermissions;
    sidings: Siding[];
    /** Distinct transporter names for transport name filter. */
    transportNames?: string[];
    filters: Filters;
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '-';
    try {
        return new Date(dateStr).toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
}

/** Append pagination when preserving page > 1. */
function appendPageParam(params: URLSearchParams, f: Filters): void {
    const pageNum = f.page !== undefined && f.page !== '' ? Number(f.page) : 1;
    if (Number.isFinite(pageNum) && pageNum > 1) {
        params.set('page', String(pageNum));
    }
}

/** Vehicles tab list + XLSX export query. */
function appendVehicleFilterParams(params: URLSearchParams, f: Filters): void {
    if (f.siding_id !== undefined && f.siding_id !== '') {
        params.set('siding_id', String(f.siding_id));
    }
    if (f.transport_name?.trim()) {
        params.set('transport_name', f.transport_name.trim());
    }
    if (f.vehicle_no?.trim()) {
        params.set('vehicle_no', f.vehicle_no.trim());
    }
    if (f.regd_date) {
        params.set('regd_date', f.regd_date);
    }
    appendPageParam(params, f);
}

/** Transporters tab list + export query. */
function appendTransporterFilterParams(params: URLSearchParams, f: Filters): void {
    if (f.siding_id !== undefined && f.siding_id !== '') {
        params.set('siding_id', String(f.siding_id));
    }
    if (f.transport_name?.trim()) {
        params.set('transport_name', f.transport_name.trim());
    }
    appendPageParam(params, f);
}

function filtersToRouterParams(view: 'vehicles' | 'transporters', f: Filters): Record<string, string> {
    const usp = new URLSearchParams();
    usp.set('view', view);
    if (view === 'vehicles') {
        appendVehicleFilterParams(usp, f);
    } else {
        appendTransporterFilterParams(usp, f);
    }
    const params: Record<string, string> = {};
    usp.forEach((value, key) => {
        params[key] = value;
    });

    return params;
}

const TRANSPORT_NAME_ALL = '__all__';

export default function VehicleWorkordersIndex({
    view,
    vehicleWorkorders,
    transportWorkOrderRegistrations,
    transportRegistrationPermissions = {
        canCreate: false,
        canUpdate: false,
        canDelete: false,
    },
    sidings,
    transportNames = [],
    filters,
}: Props) {
    const { flash } = usePage<Props & { flash?: { success?: string } }>().props;
    const [localFilters, setLocalFilters] = useState<Filters>(filters);

    useEffect(() => {
        setLocalFilters(filters);
    }, [filters]);

    const transporterNameSelectOptions = useMemo(() => {
        const names = [...transportNames];
        const cur = localFilters.transport_name?.trim();
        if (cur && !names.includes(cur)) {
            names.push(cur);
            names.sort((a, b) => a.localeCompare(b));
        }
        return names;
    }, [transportNames, localFilters.transport_name]);

    const transportNameSelectValue =
        localFilters.transport_name?.trim() ? localFilters.transport_name : TRANSPORT_NAME_ALL;

    const transportNameSearchOptions = useMemo((): SearchableSelectOption[] => {
        return [
            { value: TRANSPORT_NAME_ALL, label: 'All transporters' },
            ...transporterNameSelectOptions.map((name) => ({ value: name, label: name })),
        ];
    }, [transporterNameSelectOptions]);

    function deleteTransportRegistration(id: number): void {
        if (
            !window.confirm(
                'Delete this transporter registration? Attached documents will be removed. This cannot be undone.',
            )
        ) {
            return;
        }
        router.delete(`/vehicle-workorders/transport-registrations/${id}`, { preserveScroll: true });
    }

    const vehicleExportHref = useMemo(() => {
        const params = new URLSearchParams();
        appendVehicleFilterParams(params, filters);
        const qs = params.toString();

        return qs ? `/vehicle-workorders/export?${qs}` : '/vehicle-workorders/export';
    }, [filters]);

    const transporterExportHref = useMemo(() => {
        const params = new URLSearchParams();
        appendTransporterFilterParams(params, filters);
        const qs = params.toString();

        return qs
            ? `/vehicle-workorders/export-transporters?${qs}`
            : '/vehicle-workorders/export-transporters';
    }, [filters]);

    const applyFilters = () => {
        const payload = { ...localFilters };
        delete payload.page;
        router.get('/vehicle-workorders', filtersToRouterParams(view, payload), { preserveState: true });
    };

    const clearFilters = () => {
        setLocalFilters({ view: view === 'transporters' ? 'transporters' : 'vehicles' });
        router.get('/vehicle-workorders', view === 'transporters' ? { view: 'transporters' } : {});
    };

    const setViewTab = (next: 'vehicles' | 'transporters') => {
        router.get('/vehicle-workorders', filtersToRouterParams(next, localFilters), { preserveState: true });
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Vehicle Work Orders', href: '/vehicle-workorders' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vehicle Work Orders" />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Vehicle Work Orders"
                        description="Manage vehicle work order records from workload data"
                    />
                    <div className="flex shrink-0 flex-wrap items-center justify-end gap-2">
                        {view === 'transporters' ? (
                            transportRegistrationPermissions.canCreate ? (
                                <Link href="/vehicle-workorders/transport-registrations/create">
                                    <Button data-pan="vehicle-workorders-transport-registrations-create-header">
                                        New transporter
                                    </Button>
                                </Link>
                            ) : null
                        ) : (
                            <Link href="/vehicle-workorders/create">
                                <Button>Add Work Order</Button>
                            </Link>
                        )}
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                        {flash.success}
                    </div>
                )}

                <Card data-pan="vehicle-workorders-filters">
                    <CardHeader className="space-y-1 pb-2">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Filter className="h-4 w-4" />
                            Filters
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 pt-0">
                        {view === 'vehicles' ? (
                            <div
                                className="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-4"
                                data-pan="vehicle-workorders-filters-vehicles"
                            >
                                <div className="space-y-1">
                                    <Label htmlFor="siding_id" className="text-xs">
                                        Siding
                                    </Label>
                                    <Select
                                        value={
                                            localFilters.siding_id !== undefined && localFilters.siding_id !== ''
                                                ? String(localFilters.siding_id)
                                                : ''
                                        }
                                        onValueChange={(v) =>
                                            setLocalFilters((f) => ({ ...f, siding_id: v || undefined }))
                                        }
                                    >
                                        <SelectTrigger id="siding_id" className="h-9">
                                            <SelectValue placeholder="All sidings" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {sidings.map((s) => (
                                                <SelectItem key={s.id} value={s.id.toString()}>
                                                    {s.name} ({s.code})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div
                                    className="space-y-1"
                                    data-pan="vehicle-workorders-filter-transport-name-search-vehicles"
                                >
                                    <Label className="text-xs">Transport name</Label>
                                    <SearchableSelect
                                        options={transportNameSearchOptions}
                                        value={transportNameSelectValue}
                                        onValueChange={(v) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                transport_name: v === TRANSPORT_NAME_ALL ? undefined : v,
                                            }))
                                        }
                                        placeholder="All transporters"
                                        searchPlaceholder="Search transporters..."
                                        emptyMessage="No transporters match your search."
                                        className="h-9 min-h-9"
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="vehicle_no" className="text-xs">
                                        Vehicle no.
                                    </Label>
                                    <Input
                                        id="vehicle_no"
                                        className="h-9"
                                        placeholder="e.g. JH16H9464"
                                        value={localFilters.vehicle_no ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                vehicle_no: e.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="regd_date" className="text-xs">
                                        Regd date
                                    </Label>
                                    <Input
                                        id="regd_date"
                                        type="date"
                                        className="h-9"
                                        value={localFilters.regd_date ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                regd_date: e.target.value || undefined,
                                            }))
                                        }
                                    />
                                </div>
                            </div>
                        ) : (
                            <div
                                className="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-2"
                                data-pan="vehicle-workorders-filters-transporters"
                            >
                                <div className="space-y-1">
                                    <Label htmlFor="siding_id_tr" className="text-xs">
                                        Siding
                                    </Label>
                                    <Select
                                        value={
                                            localFilters.siding_id !== undefined && localFilters.siding_id !== ''
                                                ? String(localFilters.siding_id)
                                                : ''
                                        }
                                        onValueChange={(v) =>
                                            setLocalFilters((f) => ({ ...f, siding_id: v || undefined }))
                                        }
                                    >
                                        <SelectTrigger id="siding_id_tr" className="h-9">
                                            <SelectValue placeholder="All sidings" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {sidings.map((s) => (
                                                <SelectItem key={s.id} value={s.id.toString()}>
                                                    {s.name} ({s.code})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div
                                    className="space-y-1"
                                    data-pan="vehicle-workorders-filter-transport-name-search"
                                >
                                    <Label className="text-xs">Transport name</Label>
                                    <SearchableSelect
                                        options={transportNameSearchOptions}
                                        value={transportNameSelectValue}
                                        onValueChange={(v) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                transport_name: v === TRANSPORT_NAME_ALL ? undefined : v,
                                            }))
                                        }
                                        placeholder="All transporters"
                                        searchPlaceholder="Search transporters..."
                                        emptyMessage="No transporters match your search."
                                        className="h-9 min-h-9"
                                    />
                                </div>
                            </div>
                        )}
                        <div className="flex flex-wrap items-center gap-2 pt-1">
                            <Button onClick={applyFilters} size="sm">
                                Apply
                            </Button>
                            <Button onClick={clearFilters} variant="outline" size="sm">
                                Clear
                            </Button>
                            {view === 'vehicles' && (
                                <a
                                    href={vehicleExportHref}
                                    className="inline-flex h-8 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium shadow-sm hover:bg-accent hover:text-accent-foreground"
                                    data-pan="vehicle-workorders-export-xlsx"
                                >
                                    Export XLSX
                                </a>
                            )}
                            {view === 'transporters' && (
                                <a
                                    href={transporterExportHref}
                                    className="inline-flex h-8 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium shadow-sm hover:bg-accent hover:text-accent-foreground"
                                    data-pan="vehicle-workorders-export-transporters-xlsx"
                                >
                                    Export registrations XLSX
                                </a>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <div className="space-y-4">
                    <ToggleGroup
                        type="single"
                        value={view}
                        onValueChange={(value) => {
                            if (value === 'vehicles' || value === 'transporters') {
                                setViewTab(value);
                            }
                        }}
                        className={cn('inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800')}
                    >
                        <ToggleGroupItem
                            value="vehicles"
                            aria-label="Vehicles"
                            data-pan="vehicle-workorders-tab-vehicles"
                            className={cn(
                                'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                                view === 'vehicles'
                                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                            )}
                        >
                            <CarFront className="-ml-1 h-4 w-4" />
                            <span className="ml-1.5 text-sm">Vehicles</span>
                        </ToggleGroupItem>
                        <ToggleGroupItem
                            value="transporters"
                            aria-label="Transporters"
                            data-pan="vehicle-workorders-tab-transporters"
                            className={cn(
                                'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                                view === 'transporters'
                                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                            )}
                        >
                            <Truck className="-ml-1 h-4 w-4" />
                            <span className="ml-1.5 text-sm">Transporters</span>
                        </ToggleGroupItem>
                    </ToggleGroup>

                    {view === 'vehicles' && vehicleWorkorders && (
                <Card data-pan="vehicle-workorders-table">
                    <CardHeader>
                        <CardTitle>Work Orders</CardTitle>
                        <CardDescription>
                            {vehicleWorkorders.total} record
                            {vehicleWorkorders.total !== 1 ? 's' : ''} found
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {vehicleWorkorders.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Siding</TableHead>
                                            <TableHead>Vehicle No</TableHead>
                                            <TableHead>RCD PIN No</TableHead>
                                            <TableHead>Transport Name</TableHead>
                                            <TableHead>Tyres</TableHead>
                                            <TableHead>Tare Weight</TableHead>
                                            <TableHead>Regd Date</TableHead>
                                            <TableHead>Permit Validity</TableHead>
                                            <TableHead>Tax Validity</TableHead>
                                            <TableHead>Fitness Validity</TableHead>
                                            <TableHead>Insurance Validity</TableHead>
                                            <TableHead>Maker Model</TableHead>
                                            <TableHead>Make</TableHead>
                                            <TableHead>Model</TableHead>
                                            <TableHead>Remarks</TableHead>
                                            <TableHead>Recommended By</TableHead>
                                            <TableHead>Referenced</TableHead>
                                            <TableHead>Local/Non-local</TableHead>
                                            <TableHead className="w-[100px]">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {vehicleWorkorders.data.map((wo) => (
                                            <TableRow key={wo.id}>
                                                <TableCell className="whitespace-nowrap">
                                                    {wo.siding?.name ?? '-'} ({wo.siding?.code ?? '-'})
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap font-medium">
                                                    {wo.vehicle_no ?? '-'}
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.rcd_pin_no ?? '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.transport_name ?? '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.tyres != null ? wo.tyres : '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.tare_weight != null ? wo.tare_weight : '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{formatDate(wo.regd_date)}</TableCell>
                                                <TableCell className="whitespace-nowrap">{formatDate(wo.permit_validity_date)}</TableCell>
                                                <TableCell className="whitespace-nowrap">{formatDate(wo.tax_validity_date)}</TableCell>
                                                <TableCell className="whitespace-nowrap">{formatDate(wo.fitness_validity_date)}</TableCell>
                                                <TableCell className="whitespace-nowrap">{formatDate(wo.insurance_validity_date)}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.maker_model ?? '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.make ?? '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.model ?? '-'}</TableCell>
                                                <TableCell className="max-w-[150px] truncate" title={wo.remarks ?? undefined}>
                                                    {wo.remarks ?? '-'}
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.recommended_by ?? '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.referenced ?? '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.local_or_non_local ?? '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    <Link
                                                        href={`/vehicle-workorders/${wo.id}/edit`}
                                                        data-pan="vehicle-workorder-edit"
                                                    >
                                                        <Button variant="outline" size="sm">
                                                            <Pencil className="mr-1 h-4 w-4" />
                                                            Edit
                                                        </Button>
                                                    </Link>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                                {vehicleWorkorders.last_page > 1 && (
                                    <div className="mt-4 flex flex-wrap gap-2">
                                        {vehicleWorkorders.links.map((link, index) => (
                                            <Link
                                                key={`${link.url ?? 'null'}-${link.label}-${index}`}
                                                href={link.url ?? '#'}
                                                className={
                                                    link.active
                                                        ? 'rounded border bg-muted px-2 py-1 text-sm font-medium'
                                                        : 'rounded border px-2 py-1 text-sm'
                                                }
                                            >
                                                {link.label}
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="rounded-lg border border-dashed p-8 text-center">
                                <p className="text-sm text-muted-foreground">
                                    No work orders found. Try adjusting your filters.
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
                    )}

                    {view === 'transporters' && transportWorkOrderRegistrations && (
                        <Card data-pan="vehicle-workorders-transport-registrations-table">
                            <CardHeader>
                                <CardTitle>Transporters</CardTitle>
                                <CardDescription>
                                    {transportWorkOrderRegistrations.total}{' '}
                                    {transportWorkOrderRegistrations.total !== 1 ? 'records' : 'record'} from
                                    transporter registrations
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {transportWorkOrderRegistrations.data.length > 0 ? (
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Siding</TableHead>
                                                    <TableHead>WO no 1</TableHead>
                                                    <TableHead>WO no 2</TableHead>
                                                    <TableHead>Work order date</TableHead>
                                                    <TableHead>Transporter</TableHead>
                                                    <TableHead className="whitespace-nowrap">Active</TableHead>
                                                    <TableHead className="w-[88px] text-right">Actions</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {transportWorkOrderRegistrations.data.map((row) => (
                                                    <TableRow key={row.id}>
                                                        <TableCell className="whitespace-nowrap">
                                                            {row.siding
                                                                ? `${row.siding.name} (${row.siding.code})`
                                                                : '-'}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {row.work_order_no_1 ?? '-'}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {row.work_order_no_2 ?? '-'}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatDate(row.work_order_date)}
                                                        </TableCell>
                                                        <TableCell className="max-w-[220px] truncate">
                                                            {row.transporter_name ?? '-'}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {row.is_active === false ? (
                                                                <span className="rounded-md bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                                                    Inactive
                                                                </span>
                                                            ) : (
                                                                <span className="rounded-md bg-emerald-500/15 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:text-emerald-200">
                                                                    Active
                                                                </span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-right whitespace-nowrap">
                                                            <div className="flex flex-wrap justify-end gap-1">
                                                                {transportRegistrationPermissions.canUpdate ? (
                                                                    <Button variant="outline" size="icon-sm" asChild>
                                                                        <Link
                                                                            href={`/vehicle-workorders/transport-registrations/${row.id}/edit`}
                                                                            aria-label="Edit transporter registration"
                                                                            title="Edit"
                                                                            data-pan="vehicle-workorders-transport-registrations-edit"
                                                                        >
                                                                            <Pencil className="h-4 w-4" />
                                                                        </Link>
                                                                    </Button>
                                                                ) : null}
                                                                {transportRegistrationPermissions.canDelete ? (
                                                                    <Button
                                                                        variant="outline"
                                                                        size="icon-sm"
                                                                        className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                                        data-pan="vehicle-workorders-transport-registrations-delete"
                                                                        type="button"
                                                                        aria-label="Delete transporter registration"
                                                                        title="Delete"
                                                                        onClick={() =>
                                                                            deleteTransportRegistration(row.id)
                                                                        }
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </Button>
                                                                ) : null}
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                        {transportWorkOrderRegistrations.last_page > 1 ? (
                                            <div className="mt-4 flex flex-wrap gap-2">
                                        {transportWorkOrderRegistrations.links.map((link, index) => (
                                            <Link
                                                key={`${link.url ?? 'null'}-${link.label}-tr-${index}`}
                                                        href={link.url ?? '#'}
                                                        className={
                                                            link.active
                                                                ? 'rounded border bg-muted px-2 py-1 text-sm font-medium'
                                                                : 'rounded border px-2 py-1 text-sm'
                                                        }
                                                    >
                                                        {link.label}
                                                    </Link>
                                                ))}
                                            </div>
                                        ) : null}
                                    </div>
                                ) : (
                                    <div className="rounded-lg border border-dashed p-8 text-center">
                                        <p className="text-sm text-muted-foreground">
                                            No transporter registrations yet.{` `}
                                            {transportRegistrationPermissions.canCreate ? (
                                                <>
                                                    Use <span className="font-medium">New transporter</span> to add
                                                    one.
                                                </>
                                            ) : null}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
