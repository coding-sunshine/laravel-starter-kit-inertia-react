/* eslint-disable @eslint-react/hooks-extra/no-direct-set-state-in-use-effect */
import ShiftLockOverlay from '@/components/ShiftLockOverlay';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCan } from '@/hooks/use-can';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Calendar, Download, Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import ShiftTabs from './shift-tabs';
import VehicleEntryTable from './vehicle-entry-table';

function toIsoDate(d: Date): string {
    return d.toISOString().split('T')[0]!;
}

function parseTimeParts(time: string): { hour: number; minute: number } {
    const [h, m] = time.split(':');
    return { hour: Number(h ?? 0), minute: Number(m ?? 0) };
}

function shiftEndsAt(
    now: Date,
    shift: number,
    shiftTimes: Record<number, { start: string; end: string }>,
): Date | null {
    const range = shiftTimes[shift];
    if (!range) return null;

    const { hour: endH, minute: endM } = parseTimeParts(range.end);
    const { hour: startH, minute: startM } = parseTimeParts(range.start);

    const end = new Date(now);
    end.setHours(endH, endM, 0, 0);

    const start = new Date(now);
    start.setHours(startH, startM, 0, 0);

    // Overnight shift: end time is “earlier” than start time (e.g. 16:01 -> 00:00).
    const isOvernight = range.start > range.end;
    if (isOvernight) {
        // If we’re in the part of the day after start, end is tomorrow.
        if (now.getTime() >= start.getTime()) {
            end.setDate(end.getDate() + 1);
        }
    }

    return end;
}

function formatCountdown(totalSeconds: number): string {
    const s = Math.max(0, Math.floor(totalSeconds));
    const mm = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
    const ss = String(s % 60).padStart(2, '0');
    const hh = Math.floor(s / 3600);
    return hh > 0 ? `${hh}:${mm}:${ss}` : `${Number(mm)}:${ss}`;
}

interface DailyVehicleEntry {
    id: number;
    siding_id: number;
    entry_date: string;
    shift: number;
    e_challan_no: string | null;
    vehicle_no: string | null;
    trip_id_no: string | null;
    transport_name: string | null;
    gross_wt: number | null;
    tare_wt: number | null;
    tare_wt_two: number | null;
    reached_at: string;
    wb_no: string | null;
    d_challan_no: string | null;
    challan_mode: 'offline' | 'online' | null;
    status: 'draft' | 'completed';
    created_by: number;
    updated_by: number | null;
    created_at: string;
    updated_at: string;
}

interface Siding {
    id: number;
    name: string;
}

interface ShiftStatus {
    is_active: boolean;
    is_available: boolean;
    is_completed: boolean;
}

interface ShiftTime {
    start: string;
    end: string;
}

function getCsrfHeaders(): Record<string, string> {
    const cookieMatch = document.cookie.match(/\bXSRF-TOKEN=([^;]+)/);
    if (cookieMatch) {
        return { 'X-XSRF-TOKEN': decodeURIComponent(cookieMatch[1].trim()) };
    }
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta?.getAttribute('content')) {
        return { 'X-CSRF-TOKEN': meta.getAttribute('content')! };
    }
    return {};
}

interface Props {
    entries: DailyVehicleEntry[];
    date: string;
    activeShift: number;
    shiftSummary: Record<number, number>;
    shiftStatus?: Record<number, ShiftStatus>;
    shiftTimes: Record<number, ShiftTime>;
    sidings: Siding[];
    sidingId?: number | null;
    allowedShifts?: number[];
    /** When true, user only sees their assigned shift; hide shift/siding switchers. */
    restrictToAssignedShift?: boolean;
    canBypassShiftLock?: boolean;
    shiftLock?: {
        isLocked: boolean;
        message: string;
        nextShiftStartAt: string | null;
        now: string;
    } | null;
}

export default function DailyVehicleEntriesIndex({
    entries: entriesProp,
    date,
    activeShift,
    shiftSummary,
    shiftStatus,
    shiftTimes,
    sidings,
    sidingId: sidingIdProp,
    allowedShifts = [1, 2, 3],
    restrictToAssignedShift = false,
    canBypassShiftLock = false,
    shiftLock = null,
}: Props) {
    const canCreate = useCan('sections.railway_siding_record_data.create');
    const canUpdate = useCan('sections.railway_siding_record_data.update');
    const canDelete = useCan('sections.railway_siding_record_data.delete');
    const canExport = useCan('sections.railway_siding_record_data.view');

    const isShiftLocked = !!shiftLock?.isLocked && !canBypassShiftLock;

    const [entries, setEntries] = useState(() =>
        Array.isArray(entriesProp) ? entriesProp : [],
    );
    const [selectedDate, setSelectedDate] = useState(date);
    const [activeShiftState, setActiveShiftState] = useState(activeShift);
    const firstSidingId = sidings[0]?.id ?? null;
    const [selectedSidingId, setSelectedSidingId] = useState<number | null>(
        sidingIdProp ?? firstSidingId,
    );
    const [exportShift, setExportShift] = useState<string>(() =>
        String(activeShift),
    );
    const [isExporting, setIsExporting] = useState(false);
    const [isAddingRow, setIsAddingRow] = useState(false);
    const [addRowError, setAddRowError] = useState<string | null>(null);
    const addingRowRef = useRef(false);

    const [shiftCountdown, setShiftCountdown] = useState<{
        secondsLeft: number;
        endsAtIso: string | null;
        warning: boolean;
    } | null>(null);

    const effectiveSidingId = selectedSidingId ?? firstSidingId;
    const entriesForSiding =
        effectiveSidingId == null
            ? entries
            : entries.filter((e) => e.siding_id === effectiveSidingId);

    useEffect(() => {
        setEntries(Array.isArray(entriesProp) ? entriesProp : []);
    }, [entriesProp]);

    useEffect(() => {
        if (sidingIdProp !== undefined && sidingIdProp !== null) {
            setSelectedSidingId(sidingIdProp);
        } else {
            setSelectedSidingId(firstSidingId);
        }
    }, [sidingIdProp, firstSidingId]);

    useEffect(() => {
        if (!allowedShifts.includes(activeShiftState)) {
            const fallbackShift = allowedShifts[0] ?? 1;
            setActiveShiftState(fallbackShift);
            setExportShift(String(fallbackShift));
        }
    }, [allowedShifts, activeShiftState]);

    useEffect(() => {
        // Auto shift transition for users with 1–2 shifts (no “all shifts” bypass).
        if (canBypassShiftLock) return;
        const now = new Date();
        if (toIsoDate(now) !== selectedDate) {
            setShiftCountdown(null);
            return;
        }

        const end = shiftEndsAt(now, activeShiftState, shiftTimes);
        if (!end) {
            setShiftCountdown(null);
            return;
        }

        const tick = () => {
            const current = new Date();
            const secondsLeft = Math.floor(
                (end.getTime() - current.getTime()) / 1000,
            );
            const warning = secondsLeft <= 5 * 60 && secondsLeft > 0;
            setShiftCountdown({
                secondsLeft,
                endsAtIso: end.toISOString(),
                warning,
            });

            if (secondsLeft <= 0) {
                const sorted = [...allowedShifts]
                    .map(Number)
                    .sort((a, b) => a - b);
                const idx = sorted.indexOf(activeShiftState);
                const next = idx >= 0 ? sorted[idx + 1] : null;

                if (typeof next === 'number') {
                    setActiveShiftState(next);
                    setExportShift(String(next));
                    const params: Record<string, string | number> = {
                        date: selectedDate,
                        shift: next,
                    };
                    if (effectiveSidingId != null)
                        params.siding_id = effectiveSidingId;
                    router.get('/road-dispatch/daily-vehicle-entries', params, {
                        preserveState: false,
                        preserveScroll: true,
                    });
                    return;
                }

                router.reload({ preserveState: false, preserveScroll: true });
            }
        };

        tick();
        const id = window.setInterval(tick, 1000);
        return () => window.clearInterval(id);
    }, [
        allowedShifts,
        activeShiftState,
        canBypassShiftLock,
        effectiveSidingId,
        selectedDate,
        shiftTimes,
    ]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        {
            title: 'Road Dispatch',
            href: '/road-dispatch/daily-vehicle-entries',
        },
        { title: 'Daily Vehicle Entries', href: '' },
    ];

    const handleDateChange = (newDate: string) => {
        setSelectedDate(newDate);
        const params: Record<string, string | number> = {
            date: newDate,
            shift: activeShiftState,
        };
        if (effectiveSidingId != null) params.siding_id = effectiveSidingId;
        router.get('/road-dispatch/daily-vehicle-entries', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleShiftChange = (shift: number) => {
        // Check if shift is available for today
        if (
            shiftStatus &&
            selectedDate === new Date().toISOString().split('T')[0]
        ) {
            if (!shiftStatus[shift]?.is_available) {
                // Show alert or message instead of changing shift
                const messages = {
                    2: `2nd shift will be available after 1st shift completion (after ${shiftTimes[1]?.end ?? '08:00'})`,
                    3: `3rd shift will be available after 2nd shift completion (after ${shiftTimes[2]?.end ?? '16:00'})`,
                };
                alert(
                    messages[shift as keyof typeof messages] ||
                        'This shift is not available at the current time.',
                );
                return;
            }
        }

        setActiveShiftState(shift);
        setExportShift(String(shift));
        const params: Record<string, string | number> = {
            date: selectedDate,
            shift,
        };
        if (effectiveSidingId != null) params.siding_id = effectiveSidingId;
        router.get('/road-dispatch/daily-vehicle-entries', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleAddRow = async (count: number = 1) => {
        if (addingRowRef.current) return;
        addingRowRef.current = true;
        if (isShiftLocked) {
            alert(
                shiftLock?.message ||
                    'Your shift is not active yet (or has ended).',
            );
            addingRowRef.current = false;
            return;
        }
        if (
            shiftStatus &&
            selectedDate === new Date().toISOString().split('T')[0]
        ) {
            if (!shiftStatus[activeShiftState]?.is_available) {
                const messages = {
                    1: `1st shift is only available between ${shiftTimes[1]?.start ?? '00:01'} - ${shiftTimes[1]?.end ?? '08:00'}`,
                    2: `2nd shift will be available after 1st shift completion (after ${shiftTimes[1]?.end ?? '08:00'})`,
                    3: `3rd shift will be available after 2nd shift completion (after ${shiftTimes[2]?.end ?? '16:00'})`,
                };
                alert(
                    messages[activeShiftState as keyof typeof messages] ||
                        'This shift is not available at the current time.',
                );
                addingRowRef.current = false;
                return;
            }
        }

        setAddRowError(null);
        setIsAddingRow(true);
        const newEntries: DailyVehicleEntry[] = [];
        const payload = {
            siding_id: effectiveSidingId ?? sidings[0]?.id ?? 1,
            entry_date: selectedDate,
            shift: activeShiftState,
        };
        try {
            for (let i = 0; i < count; i++) {
                const res = await fetch(
                    '/road-dispatch/daily-vehicle-entries',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            ...getCsrfHeaders(),
                        },
                        body: JSON.stringify(payload),
                        credentials: 'include',
                    },
                );
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const msg =
                        ((data as { message?: string }).message ??
                        (data as { errors?: Record<string, string[]> }).errors)
                            ? Object.values(
                                  (data as { errors: Record<string, string[]> })
                                      .errors,
                              )
                                  .flat()
                                  .join(', ')
                            : res.statusText;
                    setAddRowError(msg ?? 'Failed to add row');
                    if (res.status === 419) {
                        setAddRowError(
                            'Session expired. Please refresh the page.',
                        );
                    }
                    addingRowRef.current = false;
                    return;
                }
                const newEntry = (data as { entry?: DailyVehicleEntry }).entry;
                if (newEntry) {
                    newEntries.push(newEntry);
                }
            }
            if (newEntries.length > 0) {
                setEntries((prev) => [...prev, ...newEntries]);
            }
        } catch {
            setAddRowError('Network error. Please try again.');
        } finally {
            setIsAddingRow(false);
            addingRowRef.current = false;
        }
    };

    const handleEntryUpdated = (entry: DailyVehicleEntry) => {
        setEntries((prev) =>
            prev.some((e) => e.id === entry.id)
                ? prev.map((e) => (e.id === entry.id ? entry : e))
                : prev,
        );
    };

    const handleEntryDeleted = (id: number) => {
        setEntries((prev) => prev.filter((e) => e.id !== id));
    };

    const handleExport = async () => {
        setIsExporting(true);

        try {
            // Create export URL with parameters
            const exportUrl = `/road-dispatch/daily-vehicle-entries/export?date=${selectedDate}&siding=${effectiveSidingId}&shift=${exportShift}`;

            // Use fetch to get the file with authentication cookies
            const response = await fetch(exportUrl, {
                method: 'GET',
                headers: {
                    Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin', // Include cookies
            });

            if (!response.ok) {
                throw new Error(`Export failed: ${response.statusText}`);
            }

            // Get the filename from the Content-Disposition header or use a default
            const contentDisposition = response.headers.get(
                'Content-Disposition',
            );
            let filename = 'export.xlsx';
            if (contentDisposition) {
                const filenameMatch =
                    contentDisposition.match(/filename="(.+)"/);
                if (filenameMatch) {
                    filename = filenameMatch[1];
                }
            }

            // Convert response to blob
            const blob = await response.blob();

            // Create download link
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();

            // Cleanup
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        } catch (error) {
            console.error('Export error:', error);
            // Show error message to user
            const errorMessage =
                error instanceof Error
                    ? error.message
                    : 'Unknown error occurred';
            alert('Export failed: ' + errorMessage);
        } finally {
            setIsExporting(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daily Vehicle Entries" />

            <ShiftLockOverlay
                shiftLock={shiftLock}
                canBypass={canBypassShiftLock}
                onUnlock={() => {
                    router.reload({
                        preserveState: true,
                        preserveScroll: true,
                    });
                }}
            />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">
                            Daily Vehicle Entries
                        </h1>
                        <p className="mt-1 text-gray-600">
                            Excel-style shift-based vehicle entry management
                        </p>
                    </div>
                    <div className="flex gap-3">
                        {/* Siding / Export: hidden when restricted to assigned shift (single siding + shift) */}
                        {!restrictToAssignedShift && (
                            <div className="flex items-center gap-2">
                                <Select
                                    value={
                                        effectiveSidingId == null
                                            ? ''
                                            : effectiveSidingId.toString()
                                    }
                                    onValueChange={(value) => {
                                        const id = Number(value);
                                        if (Number.isNaN(id)) return;
                                        setSelectedSidingId(id);
                                        router.get(
                                            '/road-dispatch/daily-vehicle-entries',
                                            {
                                                date: selectedDate,
                                                shift: activeShiftState,
                                                siding_id: id,
                                            },
                                            {
                                                preserveState: true,
                                                preserveScroll: true,
                                            },
                                        );
                                    }}
                                >
                                    <SelectTrigger className="w-40">
                                        <SelectValue placeholder="Select siding" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {sidings.map((siding) => (
                                            <SelectItem
                                                key={siding.id}
                                                value={siding.id.toString()}
                                            >
                                                {siding.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select
                                    value={exportShift}
                                    onValueChange={setExportShift}
                                >
                                    <SelectTrigger className="w-32">
                                        <SelectValue placeholder="Export" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {allowedShifts.map((shift) => (
                                            <SelectItem
                                                key={shift}
                                                value={String(shift)}
                                            >
                                                Shift {shift}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {canExport && (
                                    <Button
                                        onClick={handleExport}
                                        disabled={isExporting}
                                        variant="outline"
                                        className="flex items-center gap-2"
                                    >
                                        <Download className="h-4 w-4" />
                                        {isExporting
                                            ? 'Exporting...'
                                            : 'Export'}
                                    </Button>
                                )}
                            </div>
                        )}
                        {restrictToAssignedShift && (
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-muted-foreground">
                                    Your shift:{' '}
                                    {sidings.find(
                                        (s) => s.id === effectiveSidingId,
                                    )?.name ?? '—'}{' '}
                                    · Shift {activeShiftState}
                                </span>
                                {canExport && (
                                    <Button
                                        onClick={handleExport}
                                        disabled={isExporting}
                                        variant="outline"
                                        className="flex items-center gap-2"
                                    >
                                        <Download className="h-4 w-4" />
                                        {isExporting
                                            ? 'Exporting...'
                                            : 'Export'}
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>
                </div>

                {!canBypassShiftLock &&
                    shiftCountdown &&
                    shiftCountdown.secondsLeft > 0 && (
                        <div
                            className={`flex flex-wrap items-center gap-2 rounded-md border px-3 py-2 text-xs ${
                                shiftCountdown.warning
                                    ? 'border-amber-300 bg-amber-50 text-amber-900'
                                    : 'border-muted bg-background text-muted-foreground'
                            }`}
                        >
                            <span className="font-medium">Shift ends in</span>
                            <span className="font-semibold tabular-nums">
                                {formatCountdown(shiftCountdown.secondsLeft)}
                            </span>
                            {shiftCountdown.warning && (
                                <span className="ml-auto text-amber-800">
                                    Please finish your entries. Page will
                                    switch/reload when shift completes.
                                </span>
                            )}
                        </div>
                    )}

                {/* Date filter, shift summary, shift times, shift tabs: only for superadmin / dispatch-manage-admin */}
                {!restrictToAssignedShift && (
                    <>
                        <Card className="gap-3 p-3">
                            <CardContent className="mx-0 px-0 pt-0 pb-0">
                                <div className="flex flex-wrap items-center gap-3">
                                    <div className="flex items-center gap-2">
                                        <Calendar className="h-4 w-4 text-muted-foreground" />
                                        <Input
                                            type="date"
                                            value={selectedDate}
                                            onChange={(e) =>
                                                handleDateChange(e.target.value)
                                            }
                                            className="h-8 w-auto"
                                        />
                                    </div>
                                    <div className="ml-auto flex flex-wrap items-center gap-2">
                                        {allowedShifts.map((shift) => (
                                            <Badge
                                                key={shift}
                                                variant={
                                                    activeShiftState === shift
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                                className="cursor-pointer px-2 py-0.5 text-[11px] leading-none"
                                            >
                                                {shift === 1
                                                    ? '1ST'
                                                    : shift === 2
                                                      ? '2ND'
                                                      : '3RD'}
                                                : {shiftSummary[shift] || 0}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                            <span>
                                1st: {shiftTimes[1]?.start ?? '00:01'}–
                                {shiftTimes[1]?.end ?? '08:00'}
                            </span>
                            <span>
                                2nd: {shiftTimes[2]?.start ?? '08:01'}–
                                {shiftTimes[2]?.end ?? '16:00'}
                            </span>
                            <span>
                                3rd: {shiftTimes[3]?.start ?? '16:01'}–
                                {shiftTimes[3]?.end ?? '00:00'}
                            </span>
                        </div>

                        <ShiftTabs
                            activeShift={activeShiftState}
                            onShiftChange={handleShiftChange}
                            shiftSummary={shiftSummary}
                            shiftStatus={shiftStatus}
                            shiftTimes={shiftTimes}
                            allowedShifts={allowedShifts}
                        />
                    </>
                )}

                {/* Draft rows are completed automatically when required fields are filled, so no extra warning is needed */}

                {/* Vehicle Entry Table (filtered by selected siding) */}
                <VehicleEntryTable
                    key={`${selectedDate}-${activeShiftState}`}
                    entries={entriesForSiding}
                    date={selectedDate}
                    shift={activeShiftState}
                    canCreate={canCreate && !isShiftLocked}
                    canUpdate={canUpdate && !isShiftLocked}
                    canDelete={canDelete && !isShiftLocked}
                    onEntryUpdated={handleEntryUpdated}
                    onEntryDeleted={handleEntryDeleted}
                    onAddRow={handleAddRow}
                    isAddingRow={isAddingRow}
                    addRowButton={
                        <>
                            {canCreate && !isShiftLocked && (
                                <Button
                                    onClick={() => handleAddRow(5)}
                                    disabled={isAddingRow}
                                    className="flex items-center gap-2"
                                >
                                    <Plus className="h-4 w-4" />
                                    {isAddingRow ? 'Adding...' : 'Add 5 Rows'}
                                </Button>
                            )}
                            {addRowError && (
                                <span className="text-sm text-destructive">
                                    {addRowError}
                                </span>
                            )}
                        </>
                    }
                />
            </div>
        </AppLayout>
    );
}
