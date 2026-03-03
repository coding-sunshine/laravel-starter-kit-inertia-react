import { router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Pencil } from 'lucide-react';
import { format } from 'date-fns';
import type { VehicleDispatch, VehicleDispatchPagination, Filters } from './types';
import { formatVehicleDispatchDate, formatWeight, getShiftFromIssuedOn } from './utils';

interface VehicleDispatchTableProps {
    vehicleDispatches: VehicleDispatchPagination;
    searchFilters: Filters;
    onEditDispatch: (dispatch: VehicleDispatch) => void;
}

export default function VehicleDispatchTable({
    vehicleDispatches,
    searchFilters,
    onEditDispatch,
}: VehicleDispatchTableProps) {
    const dateRangeLabel = (() => {
        if (searchFilters.date_from && searchFilters.date_to) {
            const from = format(new Date(searchFilters.date_from), 'dd MMM yyyy');
            const to = format(new Date(searchFilters.date_to), 'dd MMM yyyy');
            return `(${from} - ${to})`;
        }
        if (searchFilters.date) {
            return `(${format(new Date(searchFilters.date), 'dd MMM yyyy')})`;
        }
        return '';
    })();

    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    Vehicle Dispatch Records
                    {dateRangeLabel && (
                        <span className="text-sm font-normal text-gray-500 ml-2">
                            {dateRangeLabel}
                        </span>
                    )}
                </CardTitle>
                <CardDescription>
                    Showing {vehicleDispatches.data.length} of {vehicleDispatches.total} records
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="rounded-md border overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="sticky left-0 bg-muted/95 z-10">
                                    Actions
                                </TableHead>
                                <TableHead>Serial No</TableHead>
                                <TableHead>Ref No</TableHead>
                                <TableHead>Permit No</TableHead>
                                <TableHead>Pass No</TableHead>
                                <TableHead>Stack DO No</TableHead>
                                <TableHead>Issued On</TableHead>
                                <TableHead>Truck Regd No</TableHead>
                                <TableHead>Mineral</TableHead>
                                <TableHead>Mineral Type</TableHead>
                                <TableHead>Weight (MT)</TableHead>
                                <TableHead>Source</TableHead>
                                <TableHead>Destination</TableHead>
                                <TableHead>Consignee</TableHead>
                                <TableHead>Check Gate</TableHead>
                                <TableHead>Distance (KM)</TableHead>
                                <TableHead>Shift</TableHead>
                                <TableHead>Siding (Name - Code)</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {vehicleDispatches.data.map((dispatch) => (
                                <TableRow key={dispatch.id}>
                                    <TableCell className="sticky left-0 bg-background z-10">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => onEditDispatch(dispatch)}
                                            className="h-8 w-8"
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                    <TableCell>{dispatch.serial_no ?? '-'}</TableCell>
                                    <TableCell>{dispatch.ref_no ?? '-'}</TableCell>
                                    <TableCell className="font-medium">{dispatch.permit_no}</TableCell>
                                    <TableCell>{dispatch.pass_no}</TableCell>
                                    <TableCell
                                        className="max-w-[120px] truncate"
                                        title={dispatch.stack_do_no ?? undefined}
                                    >
                                        {dispatch.stack_do_no ?? '-'}
                                    </TableCell>
                                    <TableCell>
                                        {formatVehicleDispatchDate(dispatch.issued_on)}
                                    </TableCell>
                                    <TableCell>{dispatch.truck_regd_no}</TableCell>
                                    <TableCell>{dispatch.mineral}</TableCell>
                                    <TableCell>{dispatch.mineral_type ?? '-'}</TableCell>
                                    <TableCell>{formatWeight(dispatch.mineral_weight)}</TableCell>
                                    <TableCell
                                        className="max-w-[180px] truncate"
                                        title={dispatch.source ?? undefined}
                                    >
                                        {dispatch.source ?? '-'}
                                    </TableCell>
                                    <TableCell
                                        className="max-w-[180px] truncate"
                                        title={dispatch.destination ?? undefined}
                                    >
                                        {dispatch.destination ?? '-'}
                                    </TableCell>
                                    <TableCell
                                        className="max-w-[180px] truncate"
                                        title={dispatch.consignee ?? undefined}
                                    >
                                        {dispatch.consignee ?? '-'}
                                    </TableCell>
                                    <TableCell
                                        className="max-w-[120px] truncate"
                                        title={dispatch.check_gate ?? undefined}
                                    >
                                        {dispatch.check_gate ?? '-'}
                                    </TableCell>
                                    <TableCell>{dispatch.distance_km ?? '-'}</TableCell>
                                    <TableCell>
                                        {(getShiftFromIssuedOn(dispatch.issued_on) ||
                                            dispatch.shift) && (
                                            <Badge variant="outline">
                                                {getShiftFromIssuedOn(dispatch.issued_on) ||
                                                    dispatch.shift}
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {dispatch.siding.name} ({dispatch.siding.code})
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {vehicleDispatches.last_page > 1 && (
                    <div className="mt-4 flex justify-center">
                        <div className="flex gap-2">
                            {vehicleDispatches.links.map((link, index) => {
                                const pageMatch = link.url?.match(/page=(\d+)/);
                                const pageNumber = pageMatch ? parseInt(pageMatch[1], 10) : null;

                                return (
                                    <button
                                        key={index}
                                        onClick={() => {
                                            if (link.url && pageNumber) {
                                                const filtersWithPage = {
                                                    ...searchFilters,
                                                    page: pageNumber,
                                                };
                                                router.get('/vehicle-dispatch', filtersWithPage, {
                                                    preserveScroll: true,
                                                });
                                            }
                                        }}
                                        disabled={!link.url}
                                        className={`px-3 py-2 rounded ${
                                            link.active
                                                ? 'bg-blue-500 text-white'
                                                : link.url
                                                  ? 'bg-gray-200 hover:bg-gray-300'
                                                  : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        }`}
                                    >
                                        {link.label
                                            .replace('&laquo;', '«')
                                            .replace('&raquo;', '»')}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
