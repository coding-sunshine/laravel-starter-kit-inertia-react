import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { dashboard } from '@/routes';
import AppLayout from '@/layouts/app-layout';
import Heading from '@/components/heading';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import {
    Search,
    Filter,
    Download,
    Upload,
    Calendar as CalendarIcon,
    Clock,
    Truck,
    FileText,
    AlertCircle,
    CheckCircle,
    Pencil,
} from 'lucide-react';
import { format } from 'date-fns';
import { DateRange } from 'react-day-picker';

interface VehicleDispatch {
    id: number;
    siding_id: number;
    serial_no: number | null;
    ref_no: number | null;
    permit_no: string;
    pass_no: string;
    stack_do_no: string | null;
    issued_on: string | null;
    truck_regd_no: string;
    mineral: string;
    mineral_type: string | null;
    mineral_weight: number;
    source: string | null;
    destination: string | null;
    consignee: string | null;
    check_gate: string | null;
    distance_km: number | null;
    shift: string | null;
    created_at: string;
    updated_at: string;
    siding: {
        id: number;
        name: string;
        code: string;
    };
    creator: {
        id: number;
        name: string;
        email: string;
    } | null;
}

interface Filters {
    date_from?: string;
    date_to?: string;
    date?: string;
    shift?: string;
    permit_no?: string;
    truck_regd_no?: string;
}

interface Props {
    vehicleDispatches: {
        data: VehicleDispatch[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: Filters;
    shifts: string[];
    availableDates: string[];
    currentSiding: {
        id: number;
        name: string;
        code: string;
    } | null;
    sidings: Array<{
        id: number;
        name: string;
        code: string;
    }>;
    preview_data?: any[];
    import_siding_id?: number;
    import_target_date?: string;
    flash?: { success?: string };
}

export default function VehicleDispatchIndex({
    vehicleDispatches,
    filters,
    shifts,
    availableDates,
    currentSiding,
    sidings,
    preview_data,
    import_siding_id,
    import_target_date,
    flash,
}: Props) {
    const pageProps = usePage<Props>().props;
    const [searchFilters, setSearchFilters] = useState<Filters>(filters);
    const [dateRange, setDateRange] = useState<DateRange | undefined>({
        from: filters.date_from ? new Date(filters.date_from) : undefined,
        to: filters.date_to ? new Date(filters.date_to) : undefined,
    });
    const [importData, setImportData] = useState('');
    const [isImporting, setIsImporting] = useState(false);
    const [importErrors, setImportErrors] = useState<string[]>([]);
    const [importSuccess, setImportSuccess] = useState<string | null>(null);
    const successMessage =
        flash?.success ?? pageProps.flash?.success ?? importSuccess;
    const [showImportDialog, setShowImportDialog] = useState(false);
    const [selectedSidingId, setSelectedSidingId] = useState<number>(
        import_siding_id ?? currentSiding?.id ?? sidings[0]?.id ?? 0,
    );
    const [targetDate, setTargetDate] = useState<string>(
        import_target_date ?? new Date().toISOString().split('T')[0]
    );
    const [previewData, setPreviewData] = useState<any[]>([]);
    const [editingDispatch, setEditingDispatch] = useState<VehicleDispatch | null>(null);
    const [editForm, setEditForm] = useState<Record<string, string | number | null>>({});
    const [isUpdating, setIsUpdating] = useState(false);
    const timeoutRef = useRef<NodeJS.Timeout | null>(null);
    const isInitialMount = useRef(true);

    // Sync selectedSidingId when sidings load or currentSiding changes (e.g. initial load)
    useEffect(() => {
        const effectiveId =
            import_siding_id ?? currentSiding?.id ?? sidings[0]?.id;
        if (effectiveId && selectedSidingId === 0) {
            setSelectedSidingId(effectiveId);
        }
    }, [import_siding_id, currentSiding, sidings]);

    // Handle preview data from props (after import redirect)
    useEffect(() => {
        if (preview_data && Array.isArray(preview_data) && preview_data.length > 0) {
            setPreviewData(preview_data);
            setShowImportDialog(false);
            if (import_siding_id) {
                setSelectedSidingId(import_siding_id);
            }
            if (import_target_date) {
                setTargetDate(import_target_date);
            }
        }
    }, [preview_data, import_siding_id, import_target_date]);

    // Only update searchFilters when props change, but don't trigger useEffect on initial mount
    useEffect(() => {
        if (isInitialMount.current) {
            isInitialMount.current = false;
            return;
        }
        setSearchFilters(filters);
    }, [filters]);

    useEffect(() => {
        // Skip on initial mount
        if (isInitialMount.current) {
            return;
        }

        // Clear previous timeout
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        // Only make request if filters have actually changed from initial values
        const hasChanges = Object.keys(searchFilters).some(key => {
            const filterValue = searchFilters[key as keyof Filters];
            const propValue = filters[key as keyof Filters];
            return filterValue !== propValue;
        });

        if (!hasChanges) {
            return;
        }

        // Set new timeout to debounce filter changes
        timeoutRef.current = setTimeout(() => {
            const params = new URLSearchParams();
            Object.entries(searchFilters).forEach(([key, value]) => {
                if (value) params.append(key, value);
            });
            
            const queryString = params.toString();
            router.get(
                '/vehicle-dispatch',
                queryString ? { ...searchFilters } : {},
                { preserveState: true, preserveScroll: true }
            );
        }, 500); // 500ms debounce

        // Cleanup
        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, [searchFilters, filters]);

    const handleImport = () => {
        setIsImporting(true);
        setImportErrors([]);
        setImportSuccess(null);

        if (!selectedSidingId) {
            setImportErrors(['Please select a siding for the import.']);
            setIsImporting(false);
            return;
        }

        router.post(
            '/vehicle-dispatch/import',
            { data: importData, siding_id: selectedSidingId, target_date: targetDate },
            {
                onSuccess: (page) => {
                    const preview = page.props.preview_data;
                    if (preview && Array.isArray(preview) && preview.length > 0) {
                        setPreviewData(preview);
                        setImportData('');
                    } else {
                        setImportSuccess(page.props.success as string);
                        setShowImportDialog(false);
                    }
                    setIsImporting(false);
                },
                onError: (errors) => {
                    if (errors?.import_errors) {
                        const errs = errors.import_errors;
                        setImportErrors(Array.isArray(errs) ? errs : [String(errs)]);
                    } else {
                        setImportErrors(['Import failed. Please check your data format.']);
                    }
                    setIsImporting(false);
                },
                preserveScroll: true,
                preserveState: false,
            },
        );
    };

    const handleSaveImport = () => {
        setIsImporting(true);
        setImportErrors([]);

        router.post(
            '/vehicle-dispatch/save',
            { data: previewData, siding_id: selectedSidingId, target_date: targetDate },
            {
                onSuccess: (page) => {
                    setImportSuccess(
                        (page.props as { flash?: { success?: string } }).flash?.success ?? '',
                    );
                    setPreviewData([]);
                    setShowImportDialog(false);
                    setIsImporting(false);
                },
                onError: (errors) => {
                    if (errors.save_errors) {
                        setImportErrors(Array.isArray(errors.save_errors) ? errors.save_errors : [errors.save_errors]);
                    } else {
                        setImportErrors(['Save failed. Please try again.']);
                    }
                    setIsImporting(false);
                },
                preserveScroll: true,
            }
        );
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'N/A';
        try {
            let date: Date;
            
            // Check if it's a Unix timestamp (number)
            if (/^\d+$/.test(dateString)) {
                date = new Date(parseInt(dateString) * 1000); // Convert Unix timestamp to milliseconds
            } else {
                // Handle Laravel timestamp format and timezone issues
                date = new Date(dateString);
                // Check if date is invalid (1970 indicates parsing issue)
                if (date.getFullYear() === 1970 && dateString.includes('2026')) {
                    // Try to parse the string manually for Laravel timestamps
                    const match = dateString.match(/(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})/);
                    if (match) {
                        const [, year, month, day, hour, minute, second] = match;
                        date = new Date(`${year}-${month}-${day}T${hour}:${minute}:${second}`);
                    }
                }
            }
            
            return format(date, 'dd MMM yyyy HH:mm');
        } catch {
            return dateString;
        }
    };

    /** Derive shift from issued_on: 1st (00:00-08:00), 2nd (08:01-16:00), 3rd (16:01-23:59) */
    const getShiftFromIssuedOn = (issuedOn: string | null): string | null => {
        if (!issuedOn) return null;
        try {
            let d: Date;
            
            // Check if it's a Unix timestamp (number)
            if (/^\d+$/.test(issuedOn)) {
                d = new Date(parseInt(issuedOn) * 1000); // Convert Unix timestamp to milliseconds
            } else {
                d = new Date(issuedOn);
                
                // Check if date parsing failed (1970 indicates issue)
                if (d.getFullYear() === 1970 && issuedOn.includes('2026')) {
                    // Try to parse the string manually for Laravel timestamps
                    const match = issuedOn.match(/(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})/);
                    if (match) {
                        const [, year, month, day, hour, minute, second] = match;
                        d = new Date(`${year}-${month}-${day}T${hour}:${minute}:${second}`);
                    }
                }
            }
            
            const minutes = d.getHours() * 60 + d.getMinutes();
            if (minutes <= 480) return '1st';
            if (minutes <= 960) return '2nd';
            return '3rd';
        } catch {
            return null;
        }
    };

    const formatWeight = (weight: number) => {
        return `${weight.toLocaleString()} MT`;
    };

    const toDatetimeLocal = (dateString: string | null): string => {
        if (!dateString) return '';
        try {
            const d = new Date(dateString);
            const pad = (n: number) => n.toString().padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
        } catch {
            return '';
        }
    };

    const openEditModal = (dispatch: VehicleDispatch) => {
        setEditingDispatch(dispatch);
        setEditForm({
            serial_no: dispatch.serial_no,
            ref_no: dispatch.ref_no,
            permit_no: dispatch.permit_no,
            pass_no: dispatch.pass_no,
            stack_do_no: dispatch.stack_do_no ?? '',
            issued_on: toDatetimeLocal(dispatch.issued_on),
            truck_regd_no: dispatch.truck_regd_no,
            mineral: dispatch.mineral,
            mineral_type: dispatch.mineral_type ?? '',
            mineral_weight: dispatch.mineral_weight,
            source: dispatch.source ?? '',
            destination: dispatch.destination ?? '',
            consignee: dispatch.consignee ?? '',
            check_gate: dispatch.check_gate ?? '',
            distance_km: dispatch.distance_km ?? '',
            shift: dispatch.shift ?? '',
        });
    };

    const handleUpdate = () => {
        if (!editingDispatch) return;
        setIsUpdating(true);
        const payload = {
            serial_no: editForm.serial_no === '' || editForm.serial_no === null ? null : Number(editForm.serial_no),
            ref_no: editForm.ref_no === '' || editForm.ref_no === null ? null : Number(editForm.ref_no),
            permit_no: String(editForm.permit_no ?? ''),
            pass_no: String(editForm.pass_no ?? ''),
            stack_do_no: editForm.stack_do_no || null,
            issued_on: editForm.issued_on || null,
            truck_regd_no: String(editForm.truck_regd_no ?? ''),
            mineral: String(editForm.mineral ?? ''),
            mineral_type: editForm.mineral_type || null,
            mineral_weight: Number(editForm.mineral_weight ?? 0),
            source: editForm.source || null,
            destination: editForm.destination || null,
            consignee: editForm.consignee || null,
            check_gate: editForm.check_gate || null,
            distance_km: editForm.distance_km === '' || editForm.distance_km === null ? null : Number(editForm.distance_km),
            shift: editForm.shift || null,
        };
        const filters = Object.fromEntries(
            Object.entries(searchFilters).filter(([, v]) => v != null && v !== ''),
        );
        router.put(`/vehicle-dispatch/${editingDispatch.id}`, { ...payload, _filters: filters }, {
            onSuccess: () => {
                setEditingDispatch(null);
                setIsUpdating(false);
            },
            onError: () => setIsUpdating(false),
            preserveScroll: true,
        });
    };

    const breadcrumbs = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Vehicle Dispatch Register', href: '/vehicle-dispatch' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vehicle Dispatch Register" />

            <div className="space-y-6">
                <Heading
                    title={
                        <>
                            Vehicle Dispatch Register
                            <span className="text-sm font-normal text-gray-500 ml-2">
                                {(() => {
                                    if (searchFilters.date_from && searchFilters.date_to) {
                                        const from = format(new Date(searchFilters.date_from), 'dd MMM yyyy');
                                        const to = format(new Date(searchFilters.date_to), 'dd MMM yyyy');
                                        return ` (${from} - ${to})`;
                                    } else if (searchFilters.date) {
                                        return ` (${format(new Date(searchFilters.date), 'dd MMM yyyy')})`;
                                    } else {
                                        return '';
                                    }
                                })()}
                            </span>
                        </>
                    }
                    description="Manage vehicle dispatch entries for sidings"
                />

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Filter className="h-5 w-5" />
                            Filters
                        </CardTitle>
                        <CardDescription>
                            Filter vehicle dispatch records by date, shift, and other criteria
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div className="lg:col-span-2">
                                <Label htmlFor="date-range">Date Range</Label>
                                <Popover>
                                    <PopoverTrigger asChild>
                                        <Button
                                            id="date-range"
                                            variant="outline"
                                            className="w-full justify-start text-left font-normal"
                                        >
                                            <CalendarIcon className="mr-2 h-4 w-4" />
                                            {dateRange?.from ? (
                                                dateRange.to ? (
                                                    <>
                                                        {format(dateRange.from, "LLL dd, y")} -{" "}
                                                        {format(dateRange.to, "LLL dd, y")}
                                                    </>
                                                ) : (
                                                    format(dateRange.from, "LLL dd, y")
                                                )
                                            ) : (
                                                <span>Pick a date range</span>
                                            )}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent className="w-auto p-0" align="start">
                                        <Calendar
                                            initialFocus
                                            mode="range"
                                            defaultMonth={dateRange?.from}
                                            selected={dateRange}
                                            onSelect={(range) => {
                                                setDateRange(range);
                                                setSearchFilters(prev => ({
                                                    ...prev,
                                                    date_from: range?.from ? format(range.from, 'yyyy-MM-dd') : undefined,
                                                    date_to: range?.to ? format(range.to, 'yyyy-MM-dd') : undefined,
                                                }));
                                            }}
                                            numberOfMonths={2}
                                        />
                                    </PopoverContent>
                                </Popover>
                            </div>

                            <div>
                                <Label htmlFor="shift">Shift</Label>
                                <Select
                                    value={searchFilters.shift || ''}
                                    onValueChange={(value) => setSearchFilters(prev => ({ ...prev, shift: value }))}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select shift" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Shifts</SelectItem>
                                        <SelectItem value="1st">1st Shift (00:00-08:00)</SelectItem>
                                        <SelectItem value="2nd">2nd Shift (08:01-16:00)</SelectItem>
                                        <SelectItem value="3rd">3rd Shift (16:01-23:59)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Actions */}
                <div className="flex justify-between items-center">
                    <div className="flex items-center gap-2">
                        <Badge variant="outline">
                            {vehicleDispatches.total} records
                        </Badge>
                        {currentSiding && (
                            <Badge variant="secondary">
                                {currentSiding.name} ({currentSiding.code})
                            </Badge>
                        )}
                    </div>

                    <Button onClick={() => setShowImportDialog(true)}>
                        <Upload className="h-4 w-4 mr-2" />
                        Bulk Import
                    </Button>
                </div>

                {/* Preview Data Section */}
                {previewData.length > 0 && (
                    <Card className="mt-6">
                        <CardHeader>
                            <CardTitle>Import Preview - {previewData.length} Records</CardTitle>
                            <CardDescription>
                                Review the parsed data below. Click "Save to Database" to store these records or "Clear Preview" to remove them.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="min-w-full border border-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permit No</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pass No</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stack DO No</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issued On</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Truck Regd No</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mineral</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mineral Type</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consignee</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check Gate</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Distance</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {previewData.map((row, index) => (
                                            <tr key={index} className="hover:bg-gray-50">
                                                <td className="px-4 py-2 text-sm text-gray-900">{row.serial_no || '-'}</td>
                                                <td className="px-4 py-2 text-sm text-gray-900">{row.permit_no || '-'}</td>
                                                <td className="px-4 py-2 text-sm text-gray-900">{row.pass_no || '-'}</td>
                                                <td className="px-4 py-2 text-sm text-gray-900">{row.stack_do_no || '-'}</td>
                                                <td className="px-4 py-2 text-sm text-gray-900">{formatDate(row.issued_on)}</td>
                                                <td className="px-4 py-2 text-sm text-gray-900">{row.truck_regd_no || '-'}</td>
                                                <td className="px-4 py-2 text-sm text-gray-900">{row.mineral || '-'}</td>
                                                <td className="px-4 py-2 text-sm text-gray-900">{row.mineral_type || '-'}</td>
                                                <td className="px-4 py-2 text-sm text-gray-900">{row.mineral_weight || '-'}</td>
                                                <td className="px-4 py-2 text-sm text-gray-900 max-w-xs truncate" title={row.source}>{row.source || '-'}</td>
                                                <td className="px-4 py-2 text-sm text-gray-900 max-w-xs truncate" title={row.destination}>{row.destination || '-'}</td>
                                                <td className="px-4 py-2 text-sm text-gray-900 max-w-xs truncate" title={row.consignee}>{row.consignee || '-'}</td>
                                                <td className="px-4 py-2 text-sm text-gray-900">{row.check_gate || '-'}</td>
                                                <td className="px-4 py-2 text-sm text-gray-900">{row.distance_km || '-'}</td>
                                                <td className="px-4 py-2 text-sm text-gray-900">{row.shift || '-'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {importErrors.length > 0 && (
                                <div className="mt-4 bg-red-50 border border-red-200 rounded p-3">
                                    <div className="flex items-center gap-2 text-red-800">
                                        <AlertCircle className="h-4 w-4" />
                                        <span className="font-medium">Save Errors:</span>
                                    </div>
                                    <div className="mt-2 space-y-1">
                                        {importErrors.map((error, index) => (
                                            <div key={index} className="text-sm text-red-700">{error}</div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            <div className="flex justify-end gap-2 mt-4">
                                <Button
                                    variant="outline"
                                    onClick={() => {
                                        setPreviewData([]);
                                        setImportErrors([]);
                                    }}
                                    disabled={isImporting}
                                >
                                    Clear Preview
                                </Button>
                                <Button
                                    onClick={handleSaveImport}
                                    disabled={isImporting}
                                >
                                    {isImporting ? 'Saving...' : 'Save to Database'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Data Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>
                            Vehicle Dispatch Records
                            <span className="text-sm font-normal text-gray-500 ml-2">
                                {(() => {
                                    if (searchFilters.date_from && searchFilters.date_to) {
                                        const from = format(new Date(searchFilters.date_from), 'dd MMM yyyy');
                                        const to = format(new Date(searchFilters.date_to), 'dd MMM yyyy');
                                        return `(${from} - ${to})`;
                                    } else if (searchFilters.date) {
                                        return `(${format(new Date(searchFilters.date), 'dd MMM yyyy')})`;
                                    } else {
                                        return '';
                                    }
                                })()}
                            </span>
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
                                        <TableHead className="sticky left-0 bg-muted/95 z-10">Actions</TableHead>
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
                                        <TableHead>Distance KM</TableHead>
                                        <TableHead>Shift</TableHead>
                                        <TableHead>Siding</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {vehicleDispatches.data.map((dispatch) => (
                                        <TableRow key={dispatch.id}>
                                            <TableCell className="sticky left-0 bg-background z-10">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => openEditModal(dispatch)}
                                                    className="h-8 w-8"
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </Button>
                                            </TableCell>
                                            <TableCell>{dispatch.serial_no ?? '-'}</TableCell>
                                            <TableCell>{dispatch.ref_no ?? '-'}</TableCell>
                                            <TableCell className="font-medium">{dispatch.permit_no}</TableCell>
                                            <TableCell>{dispatch.pass_no}</TableCell>
                                            <TableCell className="max-w-[120px] truncate" title={dispatch.stack_do_no ?? undefined}>
                                                {dispatch.stack_do_no ?? '-'}
                                            </TableCell>
                                            <TableCell>{formatDate(dispatch.issued_on)}</TableCell>
                                            <TableCell>{dispatch.truck_regd_no}</TableCell>
                                            <TableCell>{dispatch.mineral}</TableCell>
                                            <TableCell>{dispatch.mineral_type ?? '-'}</TableCell>
                                            <TableCell>{formatWeight(dispatch.mineral_weight)}</TableCell>
                                            <TableCell className="max-w-[180px] truncate" title={dispatch.source ?? undefined}>
                                                {dispatch.source ?? '-'}
                                            </TableCell>
                                            <TableCell className="max-w-[180px] truncate" title={dispatch.destination ?? undefined}>
                                                {dispatch.destination ?? '-'}
                                            </TableCell>
                                            <TableCell className="max-w-[180px] truncate" title={dispatch.consignee ?? undefined}>
                                                {dispatch.consignee ?? '-'}
                                            </TableCell>
                                            <TableCell className="max-w-[120px] truncate" title={dispatch.check_gate ?? undefined}>
                                                {dispatch.check_gate ?? '-'}
                                            </TableCell>
                                            <TableCell>{dispatch.distance_km ?? '-'}</TableCell>
                                            <TableCell>
                                                {(getShiftFromIssuedOn(dispatch.issued_on) || dispatch.shift) && (
                                                    <Badge variant="outline">
                                                        {getShiftFromIssuedOn(dispatch.issued_on) || dispatch.shift}
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">{dispatch.siding.code}</Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Simple Pagination */}
                        {vehicleDispatches.last_page > 1 && (
                            <div className="mt-4 flex justify-center">
                                <div className="flex gap-2">
                                    {vehicleDispatches.links.map((link, index) => {
                                        // Extract page number from URL
                                        const pageMatch = link.url?.match(/page=(\d+)/);
                                        const pageNumber = pageMatch ? parseInt(pageMatch[1]) : null;
                                        
                                        return (
                                            <button
                                                key={index}
                                                onClick={() => {
                                                    if (link.url && pageNumber) {
                                                        // Preserve all current filters when changing page
                                                        const filtersWithPage = { ...searchFilters, page: pageNumber };
                                                        router.get('/vehicle-dispatch', filtersWithPage, { preserveScroll: true });
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
                                                {link.label.replace('&laquo;', '«').replace('&raquo;', '»')}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Edit Record Dialog */}
            <Dialog open={!!editingDispatch} onOpenChange={(open) => !open && setEditingDispatch(null)}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Edit Vehicle Dispatch</DialogTitle>
                    </DialogHeader>
                    {editingDispatch && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 py-4">
                            <div className="space-y-2">
                                <Label>Serial No</Label>
                                <Input
                                    type="number"
                                    min={0}
                                    value={editForm.serial_no ?? ''}
                                    onChange={(e) =>
                                        setEditForm((f) => ({ ...f, serial_no: e.target.value ? parseInt(e.target.value, 10) : null }))
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Ref No</Label>
                                <Input
                                    type="number"
                                    min={0}
                                    value={editForm.ref_no ?? ''}
                                    onChange={(e) =>
                                        setEditForm((f) => ({ ...f, ref_no: e.target.value ? parseInt(e.target.value, 10) : null }))
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Permit No *</Label>
                                <Input
                                    value={editForm.permit_no ?? ''}
                                    onChange={(e) => setEditForm((f) => ({ ...f, permit_no: e.target.value }))}
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Pass No *</Label>
                                <Input
                                    value={editForm.pass_no ?? ''}
                                    onChange={(e) => setEditForm((f) => ({ ...f, pass_no: e.target.value }))}
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Stack DO No</Label>
                                <Input
                                    value={editForm.stack_do_no ?? ''}
                                    onChange={(e) => setEditForm((f) => ({ ...f, stack_do_no: e.target.value }))}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Issued On</Label>
                                <Input
                                    type="datetime-local"
                                    value={editForm.issued_on ?? ''}
                                    onChange={(e) => setEditForm((f) => ({ ...f, issued_on: e.target.value }))}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Truck Regd No *</Label>
                                <Input
                                    value={editForm.truck_regd_no ?? ''}
                                    onChange={(e) => setEditForm((f) => ({ ...f, truck_regd_no: e.target.value }))}
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Mineral *</Label>
                                <Input
                                    value={editForm.mineral ?? ''}
                                    onChange={(e) => setEditForm((f) => ({ ...f, mineral: e.target.value }))}
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Mineral Type</Label>
                                <Input
                                    value={editForm.mineral_type ?? ''}
                                    onChange={(e) => setEditForm((f) => ({ ...f, mineral_type: e.target.value }))}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Mineral Weight (MT) *</Label>
                                <Input
                                    type="number"
                                    step={0.01}
                                    min={0}
                                    value={editForm.mineral_weight ?? ''}
                                    onChange={(e) =>
                                        setEditForm((f) => ({ ...f, mineral_weight: e.target.value ? parseFloat(e.target.value) : null }))
                                    }
                                    required
                                />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label>Source</Label>
                                <Input
                                    value={editForm.source ?? ''}
                                    onChange={(e) => setEditForm((f) => ({ ...f, source: e.target.value }))}
                                />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label>Destination</Label>
                                <Input
                                    value={editForm.destination ?? ''}
                                    onChange={(e) => setEditForm((f) => ({ ...f, destination: e.target.value }))}
                                />
                            </div>
                            <div className="space-y-2 md:col-span-2">
                                <Label>Consignee</Label>
                                <Input
                                    value={editForm.consignee ?? ''}
                                    onChange={(e) => setEditForm((f) => ({ ...f, consignee: e.target.value }))}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Check Gate</Label>
                                <Input
                                    value={editForm.check_gate ?? ''}
                                    onChange={(e) => setEditForm((f) => ({ ...f, check_gate: e.target.value }))}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Distance (KM)</Label>
                                <Input
                                    type="number"
                                    min={0}
                                    value={editForm.distance_km ?? ''}
                                    onChange={(e) =>
                                        setEditForm((f) => ({
                                            ...f,
                                            distance_km: e.target.value ? parseInt(e.target.value, 10) : null,
                                        }))
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Shift</Label>
                                <Input
                                    value={editForm.shift ?? ''}
                                    onChange={(e) => setEditForm((f) => ({ ...f, shift: e.target.value }))}
                                    placeholder="e.g. 1st, Morning, Evening"
                                />
                            </div>
                        </div>
                    )}
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setEditingDispatch(null)} disabled={isUpdating}>
                            Cancel
                        </Button>
                        <Button onClick={handleUpdate} disabled={isUpdating}>
                            {isUpdating ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Custom Import Modal */}
            {showImportDialog && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                        <div className="flex justify-between items-center mb-4">
                            <div>
                                <h2 className="text-lg font-semibold">Bulk Import Vehicle Dispatches</h2>
                                <p className="text-sm text-gray-600 mt-1">
                                    <strong>Paste raw Excel/HTML table data</strong> - tab, comma, or pipe separated.<br/>
                                    <strong>Multiple column layouts auto-detected.</strong> Common formats:<br/>
                                    <strong>Format D (14 cols):</strong> Sl.No|Permit|Pass|StackDO|IssuedOn|Truck|Mineral|MinType|Weight|Source|Dest|Consignee|Gate|Dist<br/>
                                    <strong>Format A (16 cols):</strong> Serial|Ref|Permit|Pass|StackDO|IssuedOn|Truck|Mineral|MinType|Weight|Source|Dest|Consignee|Gate|Dist|Shift<br/>
                                    <strong>Format B (Truck last):</strong> Serial|Ref|Permit|Pass|StackDO|IssuedOn|Mineral|MinType|Weight|Source|...|Truck<br/>
                                    <strong>Format C (9 cols):</strong> Serial|Ref|Permit|Pass|StackDO|IssuedOn|Truck|Mineral|Weight<br/><br/>
                                    <strong>Required:</strong> Permit No, Pass No, Truck Regd No, Mineral, Mineral Weight (numeric)<br/>
                                    <strong>Dates:</strong> DD/MM/YY, MM/DD/YY, YYYY-MM-DD supported. <strong>Weights:</strong> commas removed automatically.
                                </p>
                            </div>
                            <button
                                onClick={() => setShowImportDialog(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                ×
                            </button>
                        </div>

                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="siding-select">Select Siding</Label>
                                <Select
                                    value={selectedSidingId ? selectedSidingId.toString() : ''}
                                    onValueChange={(value) => setSelectedSidingId(parseInt(value, 10))}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a siding" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {sidings.map((siding) => (
                                            <SelectItem key={siding.id} value={siding.id.toString()}>
                                                {siding.name} ({siding.code})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="target-date">Target Date for Import</Label>
                                <Input
                                    id="target-date"
                                    type="date"
                                    value={targetDate}
                                    onChange={(e) => setTargetDate(e.target.value)}
                                    className="w-full"
                                />
                                <p className="text-sm text-gray-600 mt-1">
                                    Select the date for which this data will be stored. If no date is provided in the data, this date will be used.
                                </p>
                            </div>
                            <div>
                                <Label htmlFor="import-data">Paste Data</Label>
                                <textarea
                                    id="import-data"
                                    placeholder="Paste your Excel data here..."
                                    value={importData}
                                    onChange={(e) => setImportData(e.target.value)}
                                    rows={10}
                                    className="w-full font-mono text-sm border rounded p-2"
                                />
                            </div>

                            {importErrors.length > 0 && (
                                <div className="bg-red-50 border border-red-200 rounded p-3">
                                    <div className="flex items-center gap-2 text-red-800">
                                        <AlertCircle className="h-4 w-4" />
                                        <span className="font-medium">Import Errors:</span>
                                    </div>
                                    <div className="mt-2 space-y-1">
                                        {importErrors.map((error, index) => (
                                            <div key={index} className="text-sm text-red-700">{error}</div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {successMessage && (
                                <div className="bg-green-50 border border-green-200 rounded p-3">
                                    <div className="flex items-center gap-2 text-green-800">
                                        <CheckCircle className="h-4 w-4" />
                                        <span className="font-medium">Success!</span>
                                    </div>
                                    <div className="text-sm text-green-700 mt-1">{successMessage}</div>
                                </div>
                            )}

                            <div className="flex justify-end gap-2">
                                <Button
                                    variant="outline"
                                    onClick={() => setShowImportDialog(false)}
                                    disabled={isImporting}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    onClick={handleImport}
                                    disabled={!importData.trim() || !selectedSidingId || isImporting}
                                >
                                    {isImporting ? 'Importing...' : 'Import'}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
