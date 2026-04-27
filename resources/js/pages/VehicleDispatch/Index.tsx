import { Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef, useMemo, useCallback } from 'react';
import AppLayout from '@/layouts/app-layout';
import Heading from '@/components/heading';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Filter, Upload, Calendar as CalendarIcon, AlertCircle, CheckCircle, FileSpreadsheet, Loader2 } from 'lucide-react';
import { format, subMonths } from 'date-fns';
import { DateRange } from 'react-day-picker';
import type { VehicleDispatch, Filters, ImportBatchSummary } from './types';
import { toDatetimeLocal } from './utils';
import MainDataTab from './MainDataTab';
import DPRTab from './DPRTab';
import VehicleDispatchTabs, { type VehicleDispatchTabValue } from './VehicleDispatchTabs';

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
    sidings: Array<{
        id: number;
        name: string;
        code: string;
    }>;
    preview_data?: Record<string, unknown>[];
    import_target_date?: string;
    vehicle_dispatch_import_skipped?: number | null;
    vehicle_dispatch_import_total_rows?: number | null;
    flash?: { success?: string };
    tab?: string;
}

export interface DispatchReport {
    id: number;
    /** Set for rows created after dispatch_reports.vehicle_dispatch_id migration; null for legacy rows. */
    vehicle_dispatch_id?: number | null;
    siding_id: number;
    siding?: { id: number; name: string; code: string };
    ref_no: number | null;
    e_challan_no: string;
    issued_on: string | null;
    truck_no: string | null;
    shift: string | null;
    date: string | null;
    trips: number | null;
    wo_no: string | null;
    transport_name: string | null;
    mineral_wt: number | string | null;
    gross_wt_siding_rec_wt: number | string | null;
    tare_wt: number | string | null;
    net_wt_siding_rec_wt: number | string | null;
    tyres: number | null;
    coal_ton_variation: number | string | null;
    reached_datetime: string | null;
    time_taken_trip: string | null;
    remarks: string | null;
    wb: string | null;
    trip_id_no: string | null;
}

export default function VehicleDispatchIndex({
    vehicleDispatches,
    filters,
    sidings,
    preview_data,
    import_target_date,
    flash,
    tab = 'main-data',
}: Props) {
    const pageProps = usePage<Props>().props;
    const [searchFilters, setSearchFilters] = useState<Filters>(() => filters);
    const [dateRange, setDateRange] = useState<DateRange | undefined>(() => {
        if (filters.date_from || filters.date_to) {
            return {
                from: filters.date_from ? new Date(filters.date_from) : undefined,
                to: filters.date_to ? new Date(filters.date_to) : undefined,
            };
        }
        if (filters.date) {
            const d = new Date(filters.date);
            return { from: d, to: d };
        }
        return undefined;
    });
    const [tempDateRange, setTempDateRange] = useState<DateRange | undefined>(() => {
        if (filters.date_from || filters.date_to) {
            return {
                from: filters.date_from ? new Date(filters.date_from) : undefined,
                to: filters.date_to ? new Date(filters.date_to) : undefined,
            };
        }
        if (filters.date) {
            const d = new Date(filters.date);
            return { from: d, to: d };
        }
        return undefined;
    });
    const [importData, setImportData] = useState('');
    const [isImporting, setIsImporting] = useState(false);
    const [importErrors, setImportErrors] = useState<string[]>([]);
    const [importSuccess, setImportSuccess] = useState<string | null>(null);
    const successMessage =
        flash?.success ?? pageProps.flash?.success ?? importSuccess;
    const [showImportDialog, setShowImportDialog] = useState(false);
    const [targetDate, setTargetDate] = useState<string>(
        import_target_date ?? new Date().toISOString().split('T')[0]
    );
    const [previewData, setPreviewData] = useState<Record<string, unknown>[]>([]);
    const [importBatchSummary, setImportBatchSummary] = useState<ImportBatchSummary | null>(null);
    const [editingDispatch, setEditingDispatch] = useState<VehicleDispatch | null>(null);
    const [editForm, setEditForm] = useState<Record<string, string | number | null>>({});
    const [isUpdating, setIsUpdating] = useState(false);
    const [activeTab, setActiveTab] = useState<VehicleDispatchTabValue>(
        (tab === 'dpr' ? 'dpr' : 'main-data') as VehicleDispatchTabValue
    );
    const [dprExportSidingId, setDprExportSidingId] = useState<string>('all');
    const timeoutRef = useRef<NodeJS.Timeout | null>(null);
    const prevFiltersRef = useRef<Filters>(filters);
    const [calendarDaysByMonth, setCalendarDaysByMonth] = useState<Record<string, string[]>>({});
    const [calendarLoadingCount, setCalendarLoadingCount] = useState(0);
    const calendarInFlightRef = useRef<Set<string>>(new Set());
    const calendarLoadedRef = useRef<Set<string>>(new Set());
    const [isFilterNavigating, setIsFilterNavigating] = useState(false);

    const fetchCalendarMonth = useCallback(async (monthKey: string) => {
        if (calendarLoadedRef.current.has(monthKey) || calendarInFlightRef.current.has(monthKey)) {
            return;
        }
        calendarInFlightRef.current.add(monthKey);
        setCalendarLoadingCount((c) => c + 1);
        try {
            const res = await fetch(
                `/vehicle-dispatch/calendar-days?month=${encodeURIComponent(monthKey)}`,
                {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );
            if (!res.ok) {
                return;
            }
            const body = (await res.json()) as { days?: string[] };
            const days = Array.isArray(body.days) ? body.days : [];
            calendarLoadedRef.current.add(monthKey);
            setCalendarDaysByMonth((prev) => ({ ...prev, [monthKey]: days }));
        } finally {
            calendarInFlightRef.current.delete(monthKey);
            setCalendarLoadingCount((c) => Math.max(0, c - 1));
        }
    }, []);

    const availableCalendarDates = useMemo(() => {
        const out: Date[] = [];
        for (const days of Object.values(calendarDaysByMonth)) {
            for (const d of days) {
                const [y, mo, day] = d.split('-').map(Number);
                out.push(new Date(y, mo - 1, day));
            }
        }
        return out;
    }, [calendarDaysByMonth]);

    useEffect(() => {
        const anchor = new Date();
        const monthKeys = [
            format(subMonths(anchor, 2), 'yyyy-MM'),
            format(subMonths(anchor, 1), 'yyyy-MM'),
            format(anchor, 'yyyy-MM'),
        ];
        void Promise.all(monthKeys.map((m) => fetchCalendarMonth(m)));
    }, [fetchCalendarMonth]);

    const coalTransportReportDate = useMemo((): string | null => {
        const sf = searchFilters;
        if (sf.date && !sf.date_from && !sf.date_to) {
            return sf.date;
        }
        if (sf.date_from && sf.date_to && sf.date_from === sf.date_to) {
            return sf.date_from;
        }

        return null;
    }, [searchFilters.date, searchFilters.date_from, searchFilters.date_to]);

    const dprExportHref = useMemo((): string | null => {
        if (filters.date_from && filters.date_to) {
            let qs = `date_from=${encodeURIComponent(filters.date_from)}&date_to=${encodeURIComponent(filters.date_to)}`;
            if (dprExportSidingId !== 'all') {
                qs += `&siding_id=${encodeURIComponent(dprExportSidingId)}`;
            }

            return `/vehicle-dispatch/dpr-export?${qs}`;
        }

        if (filters.date && !filters.date_from && !filters.date_to) {
            let qs = `date=${encodeURIComponent(filters.date)}`;
            if (dprExportSidingId !== 'all') {
                qs += `&siding_id=${encodeURIComponent(dprExportSidingId)}`;
            }

            return `/vehicle-dispatch/dpr-export?${qs}`;
        }

        return null;
    }, [filters.date, filters.date_from, filters.date_to, dprExportSidingId]);

    // Sync activeTab when tab prop changes (e.g. after Generate DPR redirect)
    useEffect(() => {
        setActiveTab((tab === 'dpr' ? 'dpr' : 'main-data') as VehicleDispatchTabValue);
    }, [tab]);

    // Handle preview data and import batch stats from props (after import redirect)
    useEffect(() => {
        if (import_target_date) {
            setTargetDate(import_target_date);
        }

        const totalRowsProp = pageProps.vehicle_dispatch_import_total_rows;
        if (typeof totalRowsProp === 'number') {
            const skipped = pageProps.vehicle_dispatch_import_skipped ?? 0;
            const rows = Array.isArray(preview_data) ? preview_data : [];
            setPreviewData(rows);
            setImportBatchSummary({
                skipped,
                totalRows: totalRowsProp,
                newCount: rows.length,
            });
            setShowImportDialog(false);

            return;
        }

        if (preview_data && Array.isArray(preview_data) && preview_data.length > 0) {
            setPreviewData(preview_data);
            setShowImportDialog(false);
        }
    }, [
        preview_data,
        import_target_date,
        pageProps.vehicle_dispatch_import_skipped,
        pageProps.vehicle_dispatch_import_total_rows,
    ]);

    // Handle import errors from session
    useEffect(() => {
        const sessionErrors = pageProps.flash?.import_errors;
        if (sessionErrors && Array.isArray(sessionErrors) && sessionErrors.length > 0) {
            setImportErrors(sessionErrors);
            setShowImportDialog(true);
        }
    }, [pageProps.flash]);

    const clearImportErrors = () => {
        setImportErrors([]);
        setShowImportDialog(false);
    };

    // Sync searchFilters when backend filters change (e.g. after navigation)
    useEffect(() => {
        setSearchFilters(filters);
    }, [filters]);

    // Update searchFilters when URL parameters change for DPR tab
    useEffect(() => {
        if (tab === 'dpr' && filters.date && !filters.date_from && !filters.date_to) {
            setSearchFilters(prev => ({
                ...prev,
                date: filters.date,
            }));
        }
    }, [tab, filters.date]);

    // Sync dateRange and tempDateRange when filters change
    useEffect(() => {
        let newDateRange: DateRange | undefined;
        if (filters.date_from || filters.date_to) {
            newDateRange = {
                from: filters.date_from ? new Date(filters.date_from) : undefined,
                to: filters.date_to ? new Date(filters.date_to) : undefined,
            };
        } else if (filters.date) {
            const d = new Date(filters.date);
            newDateRange = { from: d, to: d };
        } else {
            newDateRange = undefined;
        }
        setDateRange(newDateRange);
        setTempDateRange(newDateRange);
    }, [filters.date_from, filters.date_to, filters.date]);

    // Debounced navigation when user changes filters (not when props sync from server)
    useEffect(() => {
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        // If filters came from server (props changed), skip - sync effect handles it
        const filtersChanged = JSON.stringify(filters) !== JSON.stringify(prevFiltersRef.current);
        if (filtersChanged) {
            prevFiltersRef.current = filters;
            return;
        }

        // Only request when searchFilters differs from filters (user made a change)
        const hasUserChanges = Object.keys(searchFilters).some(key => {
            const filterValue = searchFilters[key as keyof Filters];
            const propValue = filters[key as keyof Filters];
            return filterValue !== propValue;
        });

        if (!hasUserChanges) {
            return;
        }

        timeoutRef.current = setTimeout(() => {
            const params: Record<string, string> = {};
            Object.entries(searchFilters).forEach(([key, value]) => {
                if (value != null && value !== '') {
                    params[key] = String(value);
                }
            });
            if (activeTab === 'dpr') {
                params.tab = 'dpr';
            }
            router.get('/vehicle-dispatch', params, {
                preserveState: true,
                preserveScroll: true,
                onStart: () => setIsFilterNavigating(true),
                onFinish: () => setIsFilterNavigating(false),
            });
        }, 500);

        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, [searchFilters, filters, activeTab]);

    const handleImport = () => {
        setIsImporting(true);
        setImportErrors([]);
        setImportSuccess(null);

        router.post(
            '/vehicle-dispatch/import',
            { data: importData, target_date: targetDate },
            {
                onSuccess: (page) => {
                    const p = page.props as Props;
                    const preview = Array.isArray(p.preview_data) ? p.preview_data : [];
                    const totalRows = p.vehicle_dispatch_import_total_rows;
                    setImportData('');
                    if (typeof totalRows === 'number') {
                        setPreviewData(preview);
                        setImportBatchSummary({
                            skipped: p.vehicle_dispatch_import_skipped ?? 0,
                            totalRows,
                            newCount: preview.length,
                        });
                    } else if (preview.length > 0) {
                        setPreviewData(preview);
                    }
                    setShowImportDialog(false);
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
            { data: previewData, target_date: targetDate },
            {
                onSuccess: (page) => {
                    setImportSuccess(
                        (page.props as { flash?: { success?: string } }).flash?.success ?? '',
                    );
                    setPreviewData([]);
                    setImportBatchSummary(null);
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

                {successMessage && !showImportDialog && (
                    <div className="flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-100">
                        <CheckCircle className="mt-0.5 h-4 w-4 shrink-0" />
                        <span>{successMessage}</span>
                    </div>
                )}

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Filter className="h-5 w-5" />
                            Filters
                            {(isFilterNavigating || calendarLoadingCount > 0) && (
                                <Loader2
                                    className="h-4 w-4 animate-spin text-muted-foreground"
                                    aria-label="Loading"
                                />
                            )}
                        </CardTitle>
                        <CardDescription>
                            Filter vehicle dispatch records by date, shift, and other criteria
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4">
                            <div>
                                <Label htmlFor="date-range">Date Range</Label>
                                <Popover
                                    onOpenChange={(open) => {
                                        if (open) {
                                            const anchor = tempDateRange?.from ?? new Date();
                                            void fetchCalendarMonth(format(anchor, 'yyyy-MM'));
                                        }
                                    }}
                                >
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
                                            defaultMonth={tempDateRange?.from}
                                            selected={tempDateRange}
                                            onSelect={(range) => setTempDateRange(range)}
                                            onMonthChange={(month) => {
                                                void fetchCalendarMonth(format(month, 'yyyy-MM'));
                                            }}
                                            numberOfMonths={2}
                                            modifiers={{
                                                available: availableCalendarDates,
                                            }}
                                            modifiersStyles={{
                                                available: {
                                                    backgroundColor: '#10b981',
                                                    color: 'white',
                                                    borderRadius: '4px',
                                                    fontWeight: 'bold',
                                                },
                                            }}
                                        />
                                        <div className="mt-2 text-xs text-gray-500 px-3 pb-2">
                                            <div className="flex items-center gap-2">
                                                <div className="w-3 h-3 bg-green-500 rounded"></div>
                                                <span>Dates with available data</span>
                                            </div>
                                        </div>
                                        <div className="flex gap-2 p-3 border-t">
                                            <Button 
                                                variant="default" 
                                                onClick={() => {
                                                    const from = tempDateRange?.from ? format(tempDateRange.from, 'yyyy-MM-dd') : undefined;
                                                    let to = tempDateRange?.to ? format(tempDateRange.to, 'yyyy-MM-dd') : undefined;
                                                    if (from !== undefined && to === undefined) {
                                                        to = from;
                                                    }
                                                    setDateRange(
                                                        from !== undefined
                                                            ? { from: tempDateRange!.from!, to: tempDateRange?.to ?? tempDateRange!.from! }
                                                            : tempDateRange,
                                                    );
                                                    setSearchFilters((prev) => ({
                                                        ...prev,
                                                        date_from: from,
                                                        date_to: to,
                                                        date: undefined,
                                                    }));
                                                }}
                                                className="flex-1"
                                            >
                                                Apply
                                            </Button>
                                            <Button 
                                                variant="outline" 
                                                onClick={() => {
                                                    setTempDateRange(dateRange);
                                                }}
                                                className="flex-1"
                                            >
                                                Cancel
                                            </Button>
                                        </div>
                                    </PopoverContent>
                                </Popover>
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
                        {sidings.length > 0 && (
                            <Badge
                                variant="secondary"
                                title="Vehicle dispatch and DPR include every siding you can access (not the global siding switcher)."
                            >
                                {sidings.length === 1
                                    ? `${sidings[0].name} (${sidings[0].code})`
                                    : `All sidings (${sidings.length})`}
                            </Badge>
                        )}
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {coalTransportReportDate ? (
                            <Button variant="outline" asChild>
                                <a
                                    href={`/exports/coal-transport-report?date=${encodeURIComponent(coalTransportReportDate)}`}
                                    data-pan="vehicle-dispatch-coal-transport-export"
                                >
                                    <FileSpreadsheet className="mr-2 h-4 w-4" />
                                    Coal Transport Report (Excel)
                                </a>
                            </Button>
                        ) : (
                            <Button
                                variant="outline"
                                disabled
                                title="Set the date range to a single day to export the coal transport report"
                            >
                                <FileSpreadsheet className="mr-2 h-4 w-4" />
                                Coal Transport Report (Excel)
                            </Button>
                        )}
                        {activeTab === 'dpr' && (
                            <>
                                <div className="w-full sm:w-56">
                                    <Select value={dprExportSidingId} onValueChange={setDprExportSidingId}>
                                        <SelectTrigger aria-label="Siding for DPR export">
                                            <SelectValue placeholder="Export siding" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All sidings</SelectItem>
                                            {sidings.map((s) => (
                                                <SelectItem key={s.id} value={String(s.id)}>
                                                    {s.name} ({s.code})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                {dprExportHref ? (
                                    <Button variant="outline" asChild>
                                        <a href={dprExportHref} data-pan="vehicle-dispatch-dpr-export">
                                            <FileSpreadsheet className="mr-2 h-4 w-4" />
                                            Export DPR (Excel)
                                        </a>
                                    </Button>
                                ) : (
                                    <Button
                                        variant="outline"
                                        disabled
                                        title="Set either date or date_from/date_to to export DPR"
                                    >
                                        <FileSpreadsheet className="mr-2 h-4 w-4" />
                                        Export DPR (Excel)
                                    </Button>
                                )}
                            </>
                        )}
                        <Button onClick={() => setShowImportDialog(true)}>
                            <Upload className="h-4 w-4 mr-2" />
                            Bulk Import
                        </Button>
                    </div>
                </div>

                {/* Tabbed Content: JIMMS Data | DPR */}
                <VehicleDispatchTabs activeTab={activeTab} onTabChange={setActiveTab}>
                    {activeTab === 'main-data' && (
                        <MainDataTab
                            vehicleDispatches={vehicleDispatches}
                            searchFilters={searchFilters}
                            previewData={previewData}
                            importBatchSummary={importBatchSummary}
                            importErrors={importErrors}
                            isImporting={isImporting}
                            onEditDispatch={openEditModal}
                            onClearPreview={() => {
                                setPreviewData([]);
                                setImportErrors([]);
                                setImportBatchSummary(null);
                            }}
                            onSaveImport={handleSaveImport}
                        />
                    )}
                    {activeTab === 'dpr' && (
                        <DPRTab
                            filters={filters}
                            flashSuccess={flash?.success ?? pageProps.flash?.success}
                        />
                    )}
                </VehicleDispatchTabs>
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
                                onClick={clearImportErrors}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                ×
                            </button>
                        </div>

                        <div className="space-y-4">
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
                            <div className="bg-blue-50 border border-blue-200 rounded-md p-3">
                                <p className="text-sm text-blue-800">
                                    <strong>Note:</strong> Siding is inferred from fixed coal-mine-to-siding distances (km) in each row:
                                    55 Pakur (PKUR), 71 Dumka (DUMK), 73 Kurwa (KURWA). Values within 1 km of these resolve to the nearest siding.
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
                                    onClick={clearImportErrors}
                                    disabled={isImporting}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    onClick={handleImport}
                                    disabled={!importData.trim() || isImporting}
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
