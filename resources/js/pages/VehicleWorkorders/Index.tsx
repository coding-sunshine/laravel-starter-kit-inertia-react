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
import { Filter, Pencil, Truck, CarFront } from 'lucide-react';
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

interface TransporterWorkorderRow {
    siding_id: number;
    siding_name: string | null;
    transport_name: string | null;
    wo_no: string | null;
    wo_no_2: string | null;
    work_order_date: string | null;
    issued_date: string | null;
    proprietor_name: string | null;
    address: string | null;
    mobile_no_1: string | null;
    mobile_no_2: string | null;
    owner_type: string | null;
    pan_no: string | null;
    gst_no: string | null;
    vehicle_count: number;
}

interface PaginatedTransporterWorkorders {
    data: TransporterWorkorderRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Filters {
    view?: 'vehicles' | 'transporters';
    /** May be string or number from the server query string / PHP. */
    siding_id?: string | number;
    vehicle_no?: string;
    wo_no?: string;
    wo_no_2?: string;
    transport_name?: string;
    mobile?: string;
    mobile_no_1?: string;
    mobile_no_2?: string;
    model?: string;
    work_order_date?: string;
    issued_date?: string;
    proprietor_name?: string;
    address?: string;
    owner_type?: string;
    pan_no?: string;
    gst_no?: string;
    min_vehicles?: string | number;
    max_vehicles?: string | number;
    regd_date?: string;
    permit_validity_date?: string;
    tax_validity_date?: string;
    insurance_validity_date?: string;
}

interface Props {
    view: 'vehicles' | 'transporters';
    vehicleWorkorders: PaginatedWorkorders | null;
    transporterWorkorders: PaginatedTransporterWorkorders | null;
    sidings: Siding[];
    /** Distinct transporter names for dropdown; omitted only if page props are stale. */
    transportNames?: string[];
    /** Distinct proprietor names for dropdown (transporters tab). */
    proprietorNames?: string[];
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

/** Vehicle tab / vehicle XLSX export: siding, vehicle identity, transport, proprietor, vehicle document dates. */
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
    if (f.mobile?.trim()) {
        params.set('mobile', f.mobile.trim());
    }
    if (f.model?.trim()) {
        params.set('model', f.model.trim());
    }
    if (f.proprietor_name?.trim()) {
        params.set('proprietor_name', f.proprietor_name.trim());
    }
    if (f.regd_date) {
        params.set('regd_date', f.regd_date);
    }
    if (f.permit_validity_date) {
        params.set('permit_validity_date', f.permit_validity_date);
    }
    if (f.tax_validity_date) {
        params.set('tax_validity_date', f.tax_validity_date);
    }
    if (f.insurance_validity_date) {
        params.set('insurance_validity_date', f.insurance_validity_date);
    }
}

/** Transporter tab / grouped transporter XLSX export. */
function appendTransporterFilterParams(params: URLSearchParams, f: Filters): void {
    if (f.siding_id !== undefined && f.siding_id !== '') {
        params.set('siding_id', String(f.siding_id));
    }
    if (f.transport_name?.trim()) {
        params.set('transport_name', f.transport_name.trim());
    }
    if (f.wo_no?.trim()) {
        params.set('wo_no', f.wo_no.trim());
    }
    if (f.wo_no_2?.trim()) {
        params.set('wo_no_2', f.wo_no_2.trim());
    }
    if (f.work_order_date) {
        params.set('work_order_date', f.work_order_date);
    }
    if (f.issued_date) {
        params.set('issued_date', f.issued_date);
    }
    if (f.proprietor_name?.trim()) {
        params.set('proprietor_name', f.proprietor_name.trim());
    }
    if (f.address?.trim()) {
        params.set('address', f.address.trim());
    }
    if (f.mobile_no_1?.trim()) {
        params.set('mobile_no_1', f.mobile_no_1.trim());
    }
    if (f.mobile_no_2?.trim()) {
        params.set('mobile_no_2', f.mobile_no_2.trim());
    }
    if (f.owner_type?.trim()) {
        params.set('owner_type', f.owner_type.trim());
    }
    if (f.pan_no?.trim()) {
        params.set('pan_no', f.pan_no.trim());
    }
    if (f.gst_no?.trim()) {
        params.set('gst_no', f.gst_no.trim());
    }
    if (f.min_vehicles !== undefined && f.min_vehicles !== '') {
        params.set('min_vehicles', String(f.min_vehicles));
    }
    if (f.max_vehicles !== undefined && f.max_vehicles !== '') {
        params.set('max_vehicles', String(f.max_vehicles));
    }
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

function transporterRowKey(row: TransporterWorkorderRow, index: number): string {
    return [
        row.siding_id,
        row.transport_name ?? '',
        row.wo_no ?? '',
        row.wo_no_2 ?? '',
        row.work_order_date ?? '',
        row.issued_date ?? '',
        index,
    ].join(':');
}

const TRANSPORT_NAME_ALL = '__all__';

const PROPRIETOR_NAME_ALL = '__all_proprietor__';

export default function VehicleWorkordersIndex({
    view,
    vehicleWorkorders,
    transporterWorkorders,
    sidings,
    transportNames = [],
    proprietorNames = [],
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

    const proprietorNameSelectOptions = useMemo(() => {
        const names = [...proprietorNames];
        const cur = localFilters.proprietor_name?.trim();
        if (cur && !names.includes(cur)) {
            names.push(cur);
            names.sort((a, b) => a.localeCompare(b));
        }
        return names;
    }, [proprietorNames, localFilters.proprietor_name]);

    const proprietorNameSelectValue = localFilters.proprietor_name?.trim()
        ? localFilters.proprietor_name
        : PROPRIETOR_NAME_ALL;

    const proprietorNameSearchOptions = useMemo((): SearchableSelectOption[] => {
        return [
            { value: PROPRIETOR_NAME_ALL, label: 'All proprietors' },
            ...proprietorNameSelectOptions.map((name) => ({ value: name, label: name })),
        ];
    }, [proprietorNameSelectOptions]);

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
        router.get('/vehicle-workorders', filtersToRouterParams(view, localFilters), { preserveState: true });
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
                        <Link href="/vehicle-workorders/create">
                            <Button>Add Work Order</Button>
                        </Link>
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
                                className="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6"
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
                                        Vehicle No
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
                                <div
                                    className="space-y-1"
                                    data-pan="vehicle-workorders-filter-proprietor-name-search-vehicles"
                                >
                                    <Label className="text-xs">Proprietor name</Label>
                                    <SearchableSelect
                                        options={proprietorNameSearchOptions}
                                        value={proprietorNameSelectValue}
                                        onValueChange={(v) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                proprietor_name: v === PROPRIETOR_NAME_ALL ? undefined : v,
                                            }))
                                        }
                                        placeholder="All proprietors"
                                        searchPlaceholder="Search proprietors..."
                                        emptyMessage="No proprietors match your search."
                                        className="h-9 min-h-9"
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="mobile" className="text-xs">
                                        Mobile (1 or 2)
                                    </Label>
                                    <Input
                                        id="mobile"
                                        className="h-9"
                                        placeholder="Search mobile"
                                        value={localFilters.mobile ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                mobile: e.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="model" className="text-xs">
                                        Model
                                    </Label>
                                    <Input
                                        id="model"
                                        className="h-9"
                                        placeholder="Model"
                                        value={localFilters.model ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                model: e.target.value,
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
                                <div className="space-y-1">
                                    <Label htmlFor="permit_validity_date" className="text-xs">
                                        Permit validity
                                    </Label>
                                    <Input
                                        id="permit_validity_date"
                                        type="date"
                                        className="h-9"
                                        value={localFilters.permit_validity_date ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                permit_validity_date: e.target.value || undefined,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="tax_validity_date" className="text-xs">
                                        Tax validity
                                    </Label>
                                    <Input
                                        id="tax_validity_date"
                                        type="date"
                                        className="h-9"
                                        value={localFilters.tax_validity_date ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                tax_validity_date: e.target.value || undefined,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="insurance_validity_date" className="text-xs">
                                        Insurance validity
                                    </Label>
                                    <Input
                                        id="insurance_validity_date"
                                        type="date"
                                        className="h-9"
                                        value={localFilters.insurance_validity_date ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                insurance_validity_date: e.target.value || undefined,
                                            }))
                                        }
                                    />
                                </div>
                            </div>
                        ) : (
                            <div
                                className="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6"
                                data-pan="vehicle-workorders-filters-transporters"
                            >
                                <div className="space-y-1">
                                    <Label htmlFor="siding_id_tr" className="text-xs">
                                        Siding name
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
                                <div className="space-y-1">
                                    <Label htmlFor="wo_no_tr" className="text-xs">
                                        WO no
                                    </Label>
                                    <Input
                                        id="wo_no_tr"
                                        className="h-9"
                                        value={localFilters.wo_no ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                wo_no: e.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="wo_no_2_tr" className="text-xs">
                                        WO no 2
                                    </Label>
                                    <Input
                                        id="wo_no_2_tr"
                                        className="h-9"
                                        value={localFilters.wo_no_2 ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                wo_no_2: e.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="work_order_date_tr" className="text-xs">
                                        Work order date
                                    </Label>
                                    <Input
                                        id="work_order_date_tr"
                                        type="date"
                                        className="h-9"
                                        value={localFilters.work_order_date ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                work_order_date: e.target.value || undefined,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="issued_date_tr" className="text-xs">
                                        Issue date
                                    </Label>
                                    <Input
                                        id="issued_date_tr"
                                        type="date"
                                        className="h-9"
                                        value={localFilters.issued_date ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                issued_date: e.target.value || undefined,
                                            }))
                                        }
                                    />
                                </div>
                                <div
                                    className="space-y-1"
                                    data-pan="vehicle-workorders-filter-proprietor-name-search"
                                >
                                    <Label className="text-xs">Proprietor name</Label>
                                    <SearchableSelect
                                        options={proprietorNameSearchOptions}
                                        value={proprietorNameSelectValue}
                                        onValueChange={(v) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                proprietor_name: v === PROPRIETOR_NAME_ALL ? undefined : v,
                                            }))
                                        }
                                        placeholder="All proprietors"
                                        searchPlaceholder="Search proprietors..."
                                        emptyMessage="No proprietors match your search."
                                        className="h-9 min-h-9"
                                    />
                                </div>
                                <div className="space-y-1 sm:col-span-2">
                                    <Label htmlFor="address_tr" className="text-xs">
                                        Address
                                    </Label>
                                    <Input
                                        id="address_tr"
                                        className="h-9"
                                        value={localFilters.address ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                address: e.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="mobile_no_1_tr" className="text-xs">
                                        Mobile
                                    </Label>
                                    <Input
                                        id="mobile_no_1_tr"
                                        className="h-9"
                                        value={localFilters.mobile_no_1 ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                mobile_no_1: e.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="mobile_no_2_tr" className="text-xs">
                                        Mobile 2
                                    </Label>
                                    <Input
                                        id="mobile_no_2_tr"
                                        className="h-9"
                                        value={localFilters.mobile_no_2 ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                mobile_no_2: e.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="owner_type_tr" className="text-xs">
                                        Owner type
                                    </Label>
                                    <Input
                                        id="owner_type_tr"
                                        className="h-9"
                                        value={localFilters.owner_type ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                owner_type: e.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="pan_no_tr" className="text-xs">
                                        PAN no
                                    </Label>
                                    <Input
                                        id="pan_no_tr"
                                        className="h-9"
                                        value={localFilters.pan_no ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                pan_no: e.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="gst_no_tr" className="text-xs">
                                        GST no
                                    </Label>
                                    <Input
                                        id="gst_no_tr"
                                        className="h-9"
                                        value={localFilters.gst_no ?? ''}
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                gst_no: e.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="min_vehicles_tr" className="text-xs">
                                        Total vehicles (min)
                                    </Label>
                                    <Input
                                        id="min_vehicles_tr"
                                        type="number"
                                        min={0}
                                        className="h-9"
                                        value={
                                            localFilters.min_vehicles === undefined || localFilters.min_vehicles === ''
                                                ? ''
                                                : String(localFilters.min_vehicles)
                                        }
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                min_vehicles: e.target.value === '' ? undefined : e.target.value,
                                            }))
                                        }
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="max_vehicles_tr" className="text-xs">
                                        Total vehicles (max)
                                    </Label>
                                    <Input
                                        id="max_vehicles_tr"
                                        type="number"
                                        min={0}
                                        className="h-9"
                                        value={
                                            localFilters.max_vehicles === undefined || localFilters.max_vehicles === ''
                                                ? ''
                                                : String(localFilters.max_vehicles)
                                        }
                                        onChange={(e) =>
                                            setLocalFilters((f) => ({
                                                ...f,
                                                max_vehicles: e.target.value === '' ? undefined : e.target.value,
                                            }))
                                        }
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
                                    Export transporters XLSX
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

                    {view === 'transporters' && transporterWorkorders && (
                        <Card data-pan="vehicle-workorders-transporters-table">
                            <CardHeader>
                                <CardTitle>Transporters</CardTitle>
                                <CardDescription>
                                    {transporterWorkorders.total} transporter work order
                                    {transporterWorkorders.total !== 1 ? 's' : ''} found
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {transporterWorkorders.data.length > 0 ? (
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Siding name</TableHead>
                                                    <TableHead>Transport name</TableHead>
                                                    <TableHead>WO no</TableHead>
                                                    <TableHead>WO no 2</TableHead>
                                                    <TableHead>Work order date</TableHead>
                                                    <TableHead>Issue date</TableHead>
                                                    <TableHead>Proprietor name</TableHead>
                                                    <TableHead>Address</TableHead>
                                                    <TableHead>Mobile</TableHead>
                                                    <TableHead>Mobile 2</TableHead>
                                                    <TableHead>Owner type</TableHead>
                                                    <TableHead>PAN no</TableHead>
                                                    <TableHead>GST no</TableHead>
                                                    <TableHead>No. of vehicles</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {transporterWorkorders.data.map((row, index) => (
                                                    <TableRow key={transporterRowKey(row, index)}>
                                                        <TableCell className="whitespace-nowrap">
                                                            {row.siding_name ?? '-'}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {row.transport_name ?? '-'}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {row.wo_no ?? '-'}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {row.wo_no_2 ?? '-'}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatDate(row.work_order_date)}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatDate(row.issued_date)}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {row.proprietor_name ?? '-'}
                                                        </TableCell>
                                                        <TableCell
                                                            className="max-w-[200px] truncate"
                                                            title={row.address ?? undefined}
                                                        >
                                                            {row.address ?? '-'}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {row.mobile_no_1 ?? '-'}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {row.mobile_no_2 ?? '-'}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {row.owner_type ?? '-'}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {row.pan_no ?? '-'}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {row.gst_no ?? '-'}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap font-medium tabular-nums">
                                                            {row.vehicle_count}
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                        {transporterWorkorders.last_page > 1 && (
                                            <div className="mt-4 flex flex-wrap gap-2">
                                                {transporterWorkorders.links.map((link, index) => (
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
                                            No transporter work orders found. Try adjusting your filters.
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
