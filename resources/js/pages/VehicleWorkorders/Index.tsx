import { Head, Link, router, usePage } from '@inertiajs/react';
import { dashboard } from '@/routes';
import AppLayout from '@/layouts/app-layout';
import Heading from '@/components/heading';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Filter, Pencil } from 'lucide-react';
import { useState } from 'react';
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
    siding_id?: string;
    vehicle_no?: string;
    wo_no?: string;
    transport_name?: string;
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

export default function VehicleWorkordersIndex({
    vehicleWorkorders,
    sidings,
    filters,
}: Props) {
    const { flash } = usePage<Props & { flash?: { success?: string } }>().props;
    const [localFilters, setLocalFilters] = useState<Filters>(filters);

    const applyFilters = () => {
        const params: Record<string, string> = {};
        if (localFilters.siding_id) params.siding_id = localFilters.siding_id;
        if (localFilters.vehicle_no?.trim()) params.vehicle_no = localFilters.vehicle_no.trim();
        if (localFilters.wo_no?.trim()) params.wo_no = localFilters.wo_no.trim();
        if (localFilters.transport_name?.trim())
            params.transport_name = localFilters.transport_name.trim();
        router.get('/vehicle-workorders', params, { preserveState: true });
    };

    const clearFilters = () => {
        setLocalFilters({});
        router.get('/vehicle-workorders');
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Vehicle Work Orders', href: '/vehicle-workorders' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vehicle Work Orders" />

            <div className="space-y-6">
                <Heading
                    title="Vehicle Work Orders"
                    description="Manage vehicle work order records from workload data"
                />

                <div className="flex justify-end">
                    <Link href="/vehicle-workorders/create">
                        <Button>Add Work Order</Button>
                    </Link>
                </div>

                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                        {flash.success}
                    </div>
                )}

                {/* Filters */}
                <Card data-pan="vehicle-workorders-filters">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Filter className="h-5 w-5" />
                            Filters
                        </CardTitle>
                        <CardDescription>
                            Filter work orders by siding, vehicle number, WO no, or transport name
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                            <div>
                                <Label htmlFor="siding_id">Siding</Label>
                                <Select
                                    value={localFilters.siding_id ?? ''}
                                    onValueChange={(v) =>
                                        setLocalFilters((f) => ({ ...f, siding_id: v || undefined }))
                                    }
                                >
                                    <SelectTrigger id="siding_id">
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
                            <div>
                                <Label htmlFor="vehicle_no">Vehicle No</Label>
                                <Input
                                    id="vehicle_no"
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
                            <div>
                                <Label htmlFor="wo_no">WO No</Label>
                                <Input
                                    id="wo_no"
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
                            <div>
                                <Label htmlFor="transport_name">Transport Name</Label>
                                <Input
                                    id="transport_name"
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
                            <div className="flex items-end gap-2">
                                <Button onClick={applyFilters} size="sm">
                                    Apply
                                </Button>
                                <Button onClick={clearFilters} variant="outline" size="sm">
                                    Clear
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
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
