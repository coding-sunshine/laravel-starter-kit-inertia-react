/* eslint-disable @eslint-react/hooks-extra/no-direct-set-state-in-use-effect */
import ShiftLockOverlay from '@/components/ShiftLockOverlay';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { Head, router, usePage } from '@inertiajs/react';
import { Calendar, Download, Plus } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import ShiftTabs from './shift-tabs';
import VehicleEntryTable from './vehicle-entry-table';

/** “Add 5 rows” adds one editable draft plus this many non-interactive rows under it. */
const PLAIN_ROWS_AFTER_ADD_FIVE = 4;

/** YYYY-MM-DD in local time (matches server `date` better than UTC `toISOString().slice(0,10)`). */
function toLocalYmd(d: Date): string {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
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

/** Nominal shift end plus grace when server does not send {@see shiftGraceEndsAtIso}. */
function shiftGraceEndAt(
    now: Date,
    shift: number,
    shiftTimes: Record<number, ShiftTime>,
): Date | null {
    const end = shiftEndsAt(now, shift, shiftTimes);
    if (!end) {
        return null;
    }
    return new Date(end.getTime() + 5 * 60 * 1000);
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
    siding?: {
        id: number;
        name: string;
        station_code?: string | null;
    };
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
    remarks?: string | null;
    net_wt?: number | null;
    created_by: number;
    updated_by: number | null;
    created_at: string;
    updated_at: string;
    inline_submitted_at: string | null;
    creator?: {
        id: number;
        name: string;
    } | null;
}

function buildLocalDraftEntry(
    id: number,
    siding: Siding,
    entryDate: string,
    shift: number,
    userId: number,
    creator: { id: number; name: string } | null,
): DailyVehicleEntry {
    return {
        id,
        siding_id: siding.id,
        siding: {
            id: siding.id,
            name: siding.name,
            station_code: siding.station_code,
        },
        entry_date: entryDate,
        shift,
        e_challan_no: null,
        vehicle_no: null,
        trip_id_no: null,
        transport_name: null,
        gross_wt: null,
        tare_wt: null,
        tare_wt_two: null,
        reached_at: new Date().toISOString(),
        wb_no: null,
        d_challan_no: null,
        challan_mode: 'online',
        status: 'draft',
        remarks: null,
        net_wt: null,
        created_by: userId,
        updated_by: null,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
        inline_submitted_at: null,
        ...(creator != null ? { creator } : {}),
    };
}

interface Siding {
    id: number;
    name: string;
    station_code?: string | null;
}

function sidingDisplayLabel(siding: Siding): string {
    const code = siding.station_code?.trim();
    return code && code.length > 0 ? code : siding.name;
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
    /** Today’s time-editable shift for non-admins; null when outside all windows or not applicable. */
    timeEditableShift?: number | null;
    /** ISO end of extended window (nominal end + grace) for {@see timeEditableShift}. */
    shiftGraceEndsAtIso?: string | null;
}

interface InertiaAuthPageProps {
    auth?: {
        user?: {
            id?: number;
            name?: string;
        } | null;
    };
}

function draftCreatorFromAuth(
    user: InertiaAuthPageProps['auth']['user'],
): { id: number; name: string } | null {
    if (user?.id == null || user.name == null || user.name === '') {
        return null;
    }
    return { id: user.id, name: user.name };
}

export default function DailyVehicleEntriesIndex({
    entries: entriesProp,
    date,
    activeShift,
    shiftSummary: shiftSummaryFromServer,
    shiftStatus,
    shiftTimes,
    sidings,
    sidingId: sidingIdProp,
    allowedShifts = [1, 2, 3],
    restrictToAssignedShift = false,
    showCreatedByColumn = false,
    canBypassShiftLock = false,
    shiftLock = null,
    timeEditableShift = null,
    shiftGraceEndsAtIso = null,
}: Props) {
    const page = usePage<InertiaAuthPageProps>();
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
    const [hourlyOpen, setHourlyOpen] = useState(false);
    const [hourlyLoading, setHourlyLoading] = useState(false);
    const [hourlyError, setHourlyError] = useState<string | null>(null);
    const [hourlyLastUpdatedIso, setHourlyLastUpdatedIso] = useState<
        string | null
    >(null);
    const [hourlyRows, setHourlyRows] = useState<
        { hour: string; label: string; count: number }[]
    >([]);
    const [addRowError, setAddRowError] = useState<string | null>(null);
    /** Plain spacer rows directly under the last table row (after “Add 5 rows”). */
    const [plainRowsAfterLastEntry, setPlainRowsAfterLastEntry] = useState(0);
    const localDraftIdRef = useRef(0);

    const [shiftCountdown, setShiftCountdown] = useState<{
        secondsLeft: number;
        endsAtIso: string | null;
        warning: boolean;
    } | null>(null);

    const [shiftSummaryState, setShiftSummaryState] = useState(
        shiftSummaryFromServer,
    );

    const effectiveSidingId = selectedSidingId ?? firstSidingId;
    const entriesForSiding =
        effectiveSidingId == null
            ? entries
            : entries.filter((e) => e.siding_id === effectiveSidingId);

    const lastEntryForSiding =
        entriesForSiding.length > 0
            ? entriesForSiding[entriesForSiding.length - 1]
            : undefined;
    const canAddAnotherRow =
        entriesForSiding.length === 0 ||
        lastEntryForSiding?.inline_submitted_at != null;

    const isSelectedToday = selectedDate === toLocalYmd(new Date());
    const lockTabsByTime = !canBypassShiftLock && isSelectedToday;
    const tableLockedByWrongShift =
        lockTabsByTime &&
        typeof timeEditableShift === 'number' &&
        activeShiftState !== timeEditableShift;
    const effectiveTableLocked = isShiftLocked || tableLockedByWrongShift;

    useEffect(() => {
        const raw = Array.isArray(entriesProp) ? entriesProp : [];
        setEntries(
            raw.map((e) => ({
                ...e,
                inline_submitted_at: e.inline_submitted_at ?? null,
                creator: e.creator ?? null,
            })),
        );
        setPlainRowsAfterLastEntry(0);
    }, [entriesProp]);

    useEffect(() => {
        setPlainRowsAfterLastEntry(0);
    }, [effectiveSidingId]);

    useEffect(() => {
        if (plainRowsAfterLastEntry === 0) {
            return;
        }
        const forSiding =
            effectiveSidingId == null
                ? entries
                : entries.filter((e) => e.siding_id === effectiveSidingId);
        const last = forSiding[forSiding.length - 1];
        if (last != null && last.inline_submitted_at != null) {
            setPlainRowsAfterLastEntry(0);
        }
    }, [entries, effectiveSidingId, plainRowsAfterLastEntry]);

    useEffect(() => {
        setShiftSummaryState(shiftSummaryFromServer);
    }, [shiftSummaryFromServer]);

    useEffect(() => {
        setActiveShiftState(activeShift);
        setExportShift(String(activeShift));
    }, [activeShift]);

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
        if (canBypassShiftLock) return;
        const now = new Date();
        if (toLocalYmd(now) !== selectedDate) {
            setShiftCountdown(null);
            return;
        }
        if (timeEditableShift == null) {
            setShiftCountdown(null);
            return;
        }

        const end = shiftGraceEndsAtIso
            ? new Date(shiftGraceEndsAtIso)
            : shiftGraceEndAt(now, timeEditableShift, shiftTimes);
        if (!end || Number.isNaN(end.getTime())) {
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
                router.reload({ preserveState: false, preserveScroll: true });
            }
        };

        tick();
        const id = window.setInterval(tick, 1000);
        return () => window.clearInterval(id);
    }, [
        activeShiftState,
        canBypassShiftLock,
        selectedDate,
        shiftGraceEndsAtIso,
        shiftTimes,
        timeEditableShift,
    ]);

    const breadcrumbs: BreadcrumbItem[] = [
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
        if (lockTabsByTime) {
            if (timeEditableShift === null) {
                alert(
                    'No shift is active for your assignment at the current time.',
                );
                return;
            }
            if (shift !== timeEditableShift) {
                alert(
                    'You can only open the shift that is currently active for your assignment (including the grace period).',
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

    const handleAddRow = useCallback(
        (count: number = 1) => {
            if (!canAddAnotherRow) {
                alert(
                    'Submit the last vehicle entry row before adding another row.',
                );
                return;
            }
            if (effectiveTableLocked) {
                alert(
                    shiftLock?.message ||
                        'Your shift is not active yet (or has ended).',
                );
                return;
            }

            const targetSidingId = effectiveSidingId ?? sidings[0]?.id;
            const siding =
                sidings.find((s) => s.id === targetSidingId) ?? sidings[0];
            if (siding == null) {
                setAddRowError('No siding selected.');
                return;
            }

            setAddRowError(null);
            setPlainRowsAfterLastEntry(0);
            const userId = page.props.auth?.user?.id ?? 0;
            const creator = draftCreatorFromAuth(page.props.auth?.user);
            const drafts: DailyVehicleEntry[] = [];
            for (let i = 0; i < count; i++) {
                localDraftIdRef.current -= 1;
                drafts.push(
                    buildLocalDraftEntry(
                        localDraftIdRef.current,
                        siding,
                        selectedDate,
                        activeShiftState,
                        userId,
                        creator,
                    ),
                );
            }
            setEntries((prev) => [...prev, ...drafts]);
        },
        [
            activeShiftState,
            canAddAnotherRow,
            effectiveSidingId,
            effectiveTableLocked,
            page.props.auth?.user,
            selectedDate,
            shiftLock?.message,
            sidings,
        ],
    );

    const handleAddFiveRows = useCallback(() => {
        if (!canAddAnotherRow) {
            alert(
                'Submit the last vehicle entry row before adding another row.',
            );
            return;
        }
        if (effectiveTableLocked) {
            alert(
                shiftLock?.message ||
                    'Your shift is not active yet (or has ended).',
            );
            return;
        }

        const targetSidingId = effectiveSidingId ?? sidings[0]?.id;
        const siding =
            sidings.find((s) => s.id === targetSidingId) ?? sidings[0];
        if (siding == null) {
            setAddRowError('No siding selected.');
            return;
        }

        setAddRowError(null);
        const userId = page.props.auth?.user?.id ?? 0;
        localDraftIdRef.current -= 1;
        const draft = buildLocalDraftEntry(
            localDraftIdRef.current,
            siding,
            selectedDate,
            activeShiftState,
            userId,
        );
        setEntries((prev) => [...prev, draft]);
        setPlainRowsAfterLastEntry(PLAIN_ROWS_AFTER_ADD_FIVE);
    }, [
        activeShiftState,
        canAddAnotherRow,
        effectiveSidingId,
        effectiveTableLocked,
        page.props.auth?.user,
        selectedDate,
        shiftLock?.message,
        sidings,
    ]);

    const handleEntryUpdated = useCallback(
        (
            entry: DailyVehicleEntry,
            context?: {
                replaceClientId?: number;
                inlineSubmitted?: boolean;
                wasLocalDraft?: boolean;
            },
        ) => {
            const replaceId = context?.replaceClientId ?? entry.id;
            const shouldAutoAddDraft =
                context?.wasLocalDraft === true &&
                context?.inlineSubmitted === true &&
                !effectiveTableLocked;

            const targetSidingId = effectiveSidingId ?? sidings[0]?.id;
            const siding =
                sidings.find((s) => s.id === targetSidingId) ?? sidings[0];
            const userId = page.props.auth?.user?.id ?? 0;
            const creator = draftCreatorFromAuth(page.props.auth?.user);
            const newDraftId = shouldAutoAddDraft ? localDraftIdRef.current - 1 : null;

            setEntries((prev) => {
                if (!prev.some((e) => e.id === replaceId)) {
                    return prev;
                }
                const next = prev.map((e) =>
                    e.id === replaceId
                        ? {
                              ...entry,
                              inline_submitted_at:
                                  entry.inline_submitted_at ?? null,
                              creator: entry.creator ?? null,
                          }
                        : e,
                );

                if (!shouldAutoAddDraft || siding == null || newDraftId == null) {
                    return next;
                }

                localDraftIdRef.current = newDraftId;
                const draft = buildLocalDraftEntry(
                    newDraftId,
                    siding,
                    selectedDate,
                    activeShiftState,
                    userId,
                    creator,
                );
                return [...next, draft];
            });
            if (
                context?.replaceClientId != null &&
                context.replaceClientId < 0
            ) {
                setShiftSummaryState((s) => ({
                    ...s,
                    [entry.shift]: (s[entry.shift] ?? 0) + 1,
                }));
            }

            if (shouldAutoAddDraft) {
                setPlainRowsAfterLastEntry(0);
                const focusSelector = `[data-field="e_challan_no"][data-entry-id="${newDraftId}"]`;
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        const el = document.querySelector<HTMLInputElement>(focusSelector);
                        el?.focus();
                        el?.select?.();
                    });
                });
            }
        },
        [
            activeShiftState,
            effectiveSidingId,
            effectiveTableLocked,
            page.props.auth?.user,
            selectedDate,
            sidings,
        ],
    );

    const handleEntryDeleted = useCallback((id: number) => {
        setPlainRowsAfterLastEntry(0);
        const isLocalOnly = id < 0;
        let removedShift: number | undefined;
        setEntries((prev) => {
            const removed = prev.find((e) => e.id === id);
            if (removed != null) {
                removedShift = removed.shift;
            }
            return prev.filter((e) => e.id !== id);
        });
        if (!isLocalOnly && removedShift !== undefined) {
            setShiftSummaryState((s) => ({
                ...s,
                [removedShift]: Math.max(0, (s[removedShift] ?? 0) - 1),
            }));
        }
    }, []);

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

    const fetchHourlySummary = useCallback(async () => {
        if (effectiveSidingId == null) {
            setHourlyError('No siding selected.');
            return;
        }

        setHourlyLoading(true);
        setHourlyError(null);

        try {
            const params = new URLSearchParams({
                date: selectedDate,
                shift: String(activeShiftState),
                siding_id: String(effectiveSidingId),
            });

            const res = await fetch(
                `/road-dispatch/daily-vehicle-entries/hourly-summary?${params.toString()}`,
                {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'include',
                },
            );

            const data = (await res.json().catch(() => null)) as
                | {
                      now?: string;
                      rows?: { hour: string; label: string; count: number }[];
                      message?: string;
                  }
                | null;

            if (!res.ok) {
                setHourlyError(
                    data?.message ??
                        (res.status === 403
                            ? 'Not allowed for this siding/shift.'
                            : 'Failed to load hourly summary.'),
                );
                return;
            }

            setHourlyRows(Array.isArray(data?.rows) ? data!.rows : []);
            setHourlyLastUpdatedIso(data?.now ?? new Date().toISOString());
        } catch {
            setHourlyError('Network error. Please try again.');
        } finally {
            setHourlyLoading(false);
        }
    }, [activeShiftState, effectiveSidingId, selectedDate]);

    useEffect(() => {
        if (!hourlyOpen) {
            return;
        }

        void fetchHourlySummary();
        const id = window.setInterval(() => {
            void fetchHourlySummary();
        }, 10_000);

        return () => window.clearInterval(id);
    }, [fetchHourlySummary, hourlyOpen]);

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

                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setHourlyOpen(true)}
                                    className="flex items-center gap-2"
                                    data-pan="daily-vehicle-entries-hourly-record"
                                >
                                    Hourly record
                                </Button>
                            </div>
                        )}
                        {restrictToAssignedShift && (
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-muted-foreground">
                                    Your shift:{' '}
                                    {(() => {
                                        const s = sidings.find(
                                            (x) =>
                                                x.id === effectiveSidingId,
                                        );
                                        return s != null
                                            ? sidingDisplayLabel(s)
                                            : '—';
                                    })()}{' '}
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

                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setHourlyOpen(true)}
                                    className="flex items-center gap-2"
                                    data-pan="daily-vehicle-entries-hourly-record"
                                >
                                    Hourly record
                                </Button>
                            </div>
                        )}
                    </div>
                </div>

                {!canBypassShiftLock &&
                    shiftCountdown &&
                    shiftCountdown.secondsLeft > 0 && (
                        <div
                            className={`sticky top-0 z-30 flex flex-wrap items-center gap-2 rounded-md border px-3 py-2 text-xs backdrop-blur-sm ${
                                shiftCountdown.warning
                                    ? 'border-amber-300 bg-amber-50/95 text-amber-900'
                                    : 'border-muted bg-background/95 text-muted-foreground'
                            }`}
                        >
                            <span className="font-medium">Shift ends in</span>
                            <span className="font-semibold tabular-nums">
                                {formatCountdown(shiftCountdown.secondsLeft)}
                            </span>
                            {shiftCountdown.warning && (
                                <span className="ml-auto text-amber-800">
                                    Please finish your entries. The page will
                                    reload when this window ends.
                                </span>
                            )}
                        </div>
                    )}

                {!restrictToAssignedShift && (
                    <>
                        <Card className="gap-3 p-3">
                            <CardContent className="mx-0 px-0 pt-0 pb-0">
                                <div className="flex flex-wrap items-center gap-3">
                                    {canBypassShiftLock && (
                                        <div className="flex items-center gap-2">
                                            <Calendar className="h-4 w-4 text-muted-foreground" />
                                            <Input
                                                type="date"
                                                value={selectedDate}
                                                onChange={(e) =>
                                                    handleDateChange(
                                                        e.target.value,
                                                    )
                                                }
                                                className="h-8 w-auto"
                                            />
                                        </div>
                                    )}
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
                                                : {shiftSummaryState[shift] || 0}
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
                            shiftSummary={shiftSummaryState}
                            shiftStatus={shiftStatus}
                            shiftTimes={shiftTimes}
                            allowedShifts={allowedShifts}
                            lockTabsByTime={lockTabsByTime}
                            timeEditableShift={timeEditableShift}
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
                    canCreate={canCreate && !effectiveTableLocked}
                    canUpdate={canUpdate && !effectiveTableLocked}
                    canDelete={canDelete && !effectiveTableLocked}
                    canAddAnotherRow={canAddAnotherRow}
                    onEntryUpdated={handleEntryUpdated}
                    onEntryDeleted={handleEntryDeleted}
                    onAddRow={handleAddRow}
                    plainRowsAfterLastEntry={plainRowsAfterLastEntry}
                    showCreatedByColumn={showCreatedByColumn}
                    addRowButton={
                        <>
                            {canCreate && !effectiveTableLocked && (
                                <Button
                                    type="button"
                                    onClick={handleAddFiveRows}
                                    disabled={!canAddAnotherRow}
                                    className="flex items-center gap-2"
                                    data-pan="daily-vehicle-entries-add-five-rows-pack"
                                >
                                    <Plus className="h-4 w-4" />
                                    Add 5 Rows
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

            <Dialog open={hourlyOpen} onOpenChange={setHourlyOpen}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Hourly record</DialogTitle>
                        <DialogDescription>
                            Hour-wise trips recorded for the selected date,
                            shift and siding.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-3">
                        <div className="flex items-center justify-between gap-2">
                            <div className="text-xs text-muted-foreground">
                                {hourlyLastUpdatedIso
                                    ? `Last updated: ${new Date(hourlyLastUpdatedIso).toLocaleTimeString('en-IN')}`
                                    : '—'}
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => void fetchHourlySummary()}
                                disabled={hourlyLoading}
                            >
                                {hourlyLoading ? 'Refreshing…' : 'Refresh'}
                            </Button>
                        </div>

                        {hourlyError && (
                            <div className="rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2 text-sm text-destructive">
                                {hourlyError}
                            </div>
                        )}

                        <div className="max-h-[55vh] overflow-auto rounded-md border">
                            <table className="w-full border-collapse text-sm">
                                <thead className="sticky top-0 bg-background">
                                    <tr className="border-b">
                                        <th className="px-3 py-2 text-left font-medium">
                                            Hour
                                        </th>
                                        <th className="px-3 py-2 text-right font-medium">
                                            Trips
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {hourlyRows.length === 0 ? (
                                        <tr>
                                            <td
                                                colSpan={2}
                                                className="px-3 py-6 text-center text-muted-foreground"
                                            >
                                                {hourlyLoading
                                                    ? 'Loading…'
                                                    : 'No records.'}
                                            </td>
                                        </tr>
                                    ) : (
                                        hourlyRows.map((r) => (
                                            <tr
                                                key={r.hour}
                                                className="border-b last:border-b-0"
                                            >
                                                <td className="px-3 py-2 tabular-nums">
                                                    {r.label}
                                                </td>
                                                <td className="px-3 py-2 text-right tabular-nums">
                                                    {r.count}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setHourlyOpen(false)}
                        >
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
