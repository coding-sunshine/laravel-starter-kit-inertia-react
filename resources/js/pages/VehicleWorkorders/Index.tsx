import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Heading from '@/components/heading';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Filter, Pencil } from 'lucide-react';
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

interface Filters {
    /** May be string or number from the server query string / PHP. */
    siding_id?: string | number;
    vehicle_no?: string;
    wo_no?: string;
    transport_name?: string;
    mobile?: string;
    model?: string;
    regd_date?: string;
    permit_validity_date?: string;
    tax_validity_date?: string;
    insurance_validity_date?: string;
}

interface Props {
    vehicleWorkorders: PaginatedWorkorders;
    sidings: Siding[];
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

function appendFilterParams(params: URLSearchParams, f: Filters): void {
    if (f.siding_id !== undefined && f.siding_id !== '') {
        params.set('siding_id', String(f.siding_id));
    }
    if (f.vehicle_no?.trim()) params.set('vehicle_no', f.vehicle_no.trim());
    if (f.wo_no?.trim()) params.set('wo_no', f.wo_no.trim());
    if (f.transport_name?.trim()) params.set('transport_name', f.transport_name.trim());
    if (f.mobile?.trim()) params.set('mobile', f.mobile.trim());
    if (f.model?.trim()) params.set('model', f.model.trim());
    if (f.regd_date) params.set('regd_date', f.regd_date);
    if (f.permit_validity_date) params.set('permit_validity_date', f.permit_validity_date);
    if (f.tax_validity_date) params.set('tax_validity_date', f.tax_validity_date);
    if (f.insurance_validity_date) params.set('insurance_validity_date', f.insurance_validity_date);
}

export default function VehicleWorkordersIndex({ vehicleWorkorders, sidings, filters }: Props) {
    const { flash } = usePage<Props & { flash?: { success?: string } }>().props;
    const [localFilters, setLocalFilters] = useState<Filters>(filters);

    useEffect(() => {
        setLocalFilters(filters);
    }, [filters]);

    const exportHref = useMemo(() => {
        const params = new URLSearchParams();
        appendFilterParams(params, filters);
        const qs = params.toString();
        return qs ? `/vehicle-workorders/export?${qs}` : '/vehicle-workorders/export';
    }, [filters]);

    const applyFilters = () => {
        const params: Record<string, string> = {};
        const usp = new URLSearchParams();
        appendFilterParams(usp, localFilters);
        usp.forEach((value, key) => {
            params[key] = value;
        });
        router.get('/vehicle-workorders', params, { preserveState: true });
    };

    const clearFilters = () => {
        setLocalFilters({});
        router.get('/vehicle-workorders');
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
                        <a
                            href={exportHref}
                            className="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium shadow-sm hover:bg-accent hover:text-accent-foreground"
                            data-pan="vehicle-workorders-export-xlsx"
                        >
                            Export XLSX
                        </a>
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
                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6">
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
                            <div className="space-y-1">
                                <Label htmlFor="wo_no" className="text-xs">
                                    WO No
                                </Label>
                                <Input
                                    id="wo_no"
                                    className="h-9"
                                    placeholder="Work order no"
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
                                <Label htmlFor="transport_name" className="text-xs">
                                    Transport Name
                                </Label>
                                <Input
                                    id="transport_name"
                                    className="h-9"
                                    placeholder="Transport name"
                                    value={localFilters.transport_name ?? ''}
                                    onChange={(e) =>
                                        setLocalFilters((f) => ({
                                            ...f,
                                            transport_name: e.target.value,
                                        }))
                                    }
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
                        <div className="flex flex-wrap gap-2 pt-1">
                            <Button onClick={applyFilters} size="sm">
                                Apply
                            </Button>
                            <Button onClick={clearFilters} variant="outline" size="sm">
                                Clear
                            </Button>
                        </div>
                    </CardContent>
                </Card>

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
                                            <TableHead>WO No</TableHead>
                                            <TableHead>WO No 2</TableHead>
                                            <TableHead>Work Order Date</TableHead>
                                            <TableHead>Issued Date</TableHead>
                                            <TableHead>Proprietor Name</TableHead>
                                            <TableHead>Represented By</TableHead>
                                            <TableHead>Place</TableHead>
                                            <TableHead>Address</TableHead>
                                            <TableHead>Tyres</TableHead>
                                            <TableHead>Tare Weight</TableHead>
                                            <TableHead>Mobile 1</TableHead>
                                            <TableHead>Mobile 2</TableHead>
                                            <TableHead>Owner Type</TableHead>
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
                                            <TableHead>PAN No</TableHead>
                                            <TableHead>GST No</TableHead>
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
                                                <TableCell className="whitespace-nowrap">{wo.wo_no ?? '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.wo_no_2 ?? '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{formatDate(wo.work_order_date)}</TableCell>
                                                <TableCell className="whitespace-nowrap">{formatDate(wo.issued_date)}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.proprietor_name ?? '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.represented_by ?? '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.place ?? '-'}</TableCell>
                                                <TableCell className="max-w-[200px] truncate" title={wo.address ?? undefined}>
                                                    {wo.address ?? '-'}
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.tyres != null ? wo.tyres : '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.tare_weight != null ? wo.tare_weight : '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.mobile_no_1 ?? '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.mobile_no_2 ?? '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.owner_type ?? '-'}</TableCell>
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
                                                <TableCell className="whitespace-nowrap">{wo.pan_no ?? '-'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{wo.gst_no ?? '-'}</TableCell>
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
            </div>
        </AppLayout>
    );
}
