/* eslint-disable @eslint-react/hooks-extra/no-direct-set-state-in-use-effect */
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';

export interface ShiftReportSidingOption {
    id: number;
    name: string;
    code: string;
}

interface ShiftReportShiftRow {
    shift: number;
    total: number;
    completed: number;
    pending: number;
    /** Sum of gross_wt for non-completed rows (no net until tare). */
    in_progress_gross_mt: number;
    net_weight_mt: number;
}

interface ShiftReportDay {
    date: string;
    shifts: ShiftReportShiftRow[];
    day_total: {
        total: number;
        completed: number;
        pending: number;
        in_progress_gross_mt: number;
        net_weight_mt: number;
    };
}

interface ShiftReportPayload {
    siding: { id: number; name: string; code: string };
    from: string;
    to: string;
    days: ShiftReportDay[];
}

const CODE_TAB_LABELS: Record<string, string> = {
    PKUR: 'Pakur',
    KURWA: 'Kurwa',
    DUMK: 'Dumka',
};

function tabLabelForCode(code: string): string {
    const u = code.toUpperCase();
    return CODE_TAB_LABELS[u] ?? code;
}

/** First day of this calendar month through today (local), not end-of-month. */
function monthStartThroughTodayLocal(): { from: string; to: string } {
    const now = new Date();
    const y = now.getFullYear();
    const m = now.getMonth();
    const start = new Date(y, m, 1);
    const pad = (n: number) => String(n).padStart(2, '0');
    const from = `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`;
    const to = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
    return { from, to };
}

function formatDisplayDate(isoDate: string): string {
    const [y, mo, d] = isoDate.split('-').map(Number);
    if (y == null || mo == null || d == null) {
        return isoDate;
    }
    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
    }).format(new Date(y, mo - 1, d));
}

function formatNetWeightMt(mt: number): string {
    if (Number.isNaN(mt)) {
        return '—';
    }
    return new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(mt);
}

interface ShiftReportDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    sidings: ShiftReportSidingOption[];
}

export default function ShiftReportDialog({
    open,
    onOpenChange,
    sidings,
}: ShiftReportDialogProps) {
    const [activeSidingId, setActiveSidingId] = useState<number | null>(
        () => sidings[0]?.id ?? null,
    );
    const [dateRange, setDateRange] = useState(monthStartThroughTodayLocal);
    const { from, to } = dateRange;
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [payload, setPayload] = useState<ShiftReportPayload | null>(null);
    const rangeRef = useRef({ from, to });
    rangeRef.current = dateRange;

    useEffect(() => {
        if (sidings.length > 0 && activeSidingId == null) {
            setActiveSidingId(sidings[0].id);
        }
    }, [sidings, activeSidingId]);

    useEffect(() => {
        if (!open) {
            return;
        }
        setDateRange(monthStartThroughTodayLocal());
    }, [open]);

    const fetchReport = useCallback(
        async (rangeOverride?: { from: string; to: string }) => {
            if (activeSidingId == null) {
                setError('No siding selected.');
                return;
            }

            const qFrom = rangeOverride?.from ?? rangeRef.current.from;
            const qTo = rangeOverride?.to ?? rangeRef.current.to;

            setLoading(true);
            setError(null);

            try {
                const params = new URLSearchParams({
                    siding_id: String(activeSidingId),
                    from: qFrom,
                    to: qTo,
                });
                const res = await fetch(
                    `/road-dispatch/daily-vehicle-entries/shift-report?${params.toString()}`,
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
                    | (ShiftReportPayload & { message?: string })
                    | null;

                if (!res.ok) {
                    setPayload(null);
                    setError(
                        (data && typeof data.message === 'string'
                            ? data.message
                            : null) ??
                            (res.status === 403
                                ? 'You do not have access to the shift report.'
                                : res.status === 422
                                  ? 'Invalid date range.'
                                  : 'Failed to load shift report.'),
                    );
                    return;
                }

                if (data == null || !Array.isArray(data.days)) {
                    setPayload(null);
                    setError('Invalid response from server.');
                    return;
                }

                setPayload(data as ShiftReportPayload);
            } catch {
                setPayload(null);
                setError('Network error. Please try again.');
            } finally {
                setLoading(false);
            }
        },
        [activeSidingId],
    );

    useEffect(() => {
        if (!open || activeSidingId == null) {
            return;
        }
        void fetchReport(monthStartThroughTodayLocal());
    }, [open, activeSidingId, fetchReport]);

    const applyRangeAndRefetch = (): void => {
        void fetchReport({ from, to });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex h-auto max-h-[92vh] w-[min(96rem,calc(100vw-1.5rem))] max-w-none flex-col gap-0 p-0 sm:max-w-none sm:w-[min(96rem,calc(100vw-2rem))]">
                <DialogHeader className="shrink-0 space-y-1 border-b px-6 py-4">
                    <DialogTitle>Shift report</DialogTitle>
                    <DialogDescription>
                        By day (newest first) and shift: entry counts, pending
                        rows, in-progress gross (MT) before tare, and net weight
                        (MT) from completed trips only.
                    </DialogDescription>
                </DialogHeader>

                <div className="min-h-0 flex-1 space-y-3 overflow-y-auto px-6 py-4">
                    <div className="flex flex-wrap items-center gap-2 border-b pb-3">
                        {sidings.map((s) => (
                            <button
                                key={s.id}
                                type="button"
                                className={cn(
                                    'rounded-md border px-3 py-1.5 text-sm font-medium transition-colors',
                                    activeSidingId === s.id
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-border bg-background hover:bg-muted/60',
                                )}
                                onClick={() => setActiveSidingId(s.id)}
                                data-pan={`daily-vehicle-entries-shift-report-tab-${s.code.toLowerCase()}`}
                            >
                                {tabLabelForCode(s.code)}
                            </button>
                        ))}
                    </div>

                    <div className="flex flex-wrap items-end gap-3">
                        <div className="space-y-1">
                            <label
                                htmlFor="shift-report-from"
                                className="text-xs font-medium text-muted-foreground"
                            >
                                From
                            </label>
                            <Input
                                id="shift-report-from"
                                type="date"
                                value={from}
                                onChange={(e) => setDateRange((r) => ({
                                    ...r,
                                    from: e.target.value,
                                }))}
                                className="w-[11rem]"
                            />
                        </div>
                        <div className="space-y-1">
                            <label
                                htmlFor="shift-report-to"
                                className="text-xs font-medium text-muted-foreground"
                            >
                                To
                            </label>
                            <Input
                                id="shift-report-to"
                                type="date"
                                value={to}
                                onChange={(e) => setDateRange((r) => ({
                                    ...r,
                                    to: e.target.value,
                                }))}
                                className="w-[11rem]"
                            />
                        </div>
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            onClick={() => applyRangeAndRefetch()}
                            disabled={loading}
                            data-pan="daily-vehicle-entries-shift-report-apply-range"
                        >
                            Apply range
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => void fetchReport()}
                            disabled={loading}
                            data-pan="daily-vehicle-entries-shift-report-refresh"
                        >
                            {loading ? 'Loading…' : 'Refresh'}
                        </Button>
                    </div>

                    {payload != null && (
                        <p className="text-xs text-muted-foreground">
                            Siding:{' '}
                            <span className="font-medium text-foreground">
                                {payload.siding.name}
                            </span>{' '}
                            · {payload.from} → {payload.to}
                        </p>
                    )}

                    {error != null && (
                        <div className="rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2 text-sm text-destructive">
                            {error}
                        </div>
                    )}

                    <div className="max-h-[min(32rem,55vh)] overflow-auto rounded-md border sm:max-h-[min(36rem,58vh)]">
                        <table className="w-full border-collapse text-sm">
                            <thead className="sticky top-0 bg-background">
                                <tr className="border-b">
                                    <th className="px-3 py-2 text-left font-medium">
                                        Date
                                    </th>
                                    <th className="px-3 py-2 text-left font-medium">
                                        Shift
                                    </th>
                                    <th className="px-3 py-2 text-right font-medium">
                                        Total entry
                                    </th>
                                    <th className="px-3 py-2 text-right font-medium">
                                        Completed
                                    </th>
                                    <th className="px-3 py-2 text-right font-medium">
                                        Pending
                                    </th>
                                    <th className="px-3 py-2 text-right font-medium">
                                        In progress (gross MT)
                                    </th>
                                    <th className="px-3 py-2 text-right font-medium">
                                        Net wt (MT)
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {payload == null || payload.days.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-3 py-8 text-center text-muted-foreground"
                                        >
                                            {loading
                                                ? 'Loading…'
                                                : 'No data in this range.'}
                                        </td>
                                    </tr>
                                ) : (
                                    payload.days.flatMap((day) => {
                                        const rows: ReactNode[] = [];
                                        const dateShown = formatDisplayDate(
                                            day.date,
                                        );
                                        day.shifts.forEach((s, idx) => {
                                            rows.push(
                                                <tr
                                                    key={`${day.date}-s${s.shift}`}
                                                    className="border-b border-border/80"
                                                >
                                                    {idx === 0 ? (
                                                        <td
                                                            className="px-3 py-2 align-top text-muted-foreground"
                                                            rowSpan={4}
                                                        >
                                                            {dateShown}
                                                        </td>
                                                    ) : null}
                                                    <td className="px-3 py-2 tabular-nums">
                                                        S{s.shift}
                                                    </td>
                                                    <td className="px-3 py-2 text-right tabular-nums">
                                                        {s.total}
                                                    </td>
                                                    <td className="px-3 py-2 text-right tabular-nums">
                                                        {s.completed}
                                                    </td>
                                                    <td className="px-3 py-2 text-right tabular-nums">
                                                        {s.pending}
                                                    </td>
                                                    <td className="px-3 py-2 text-right tabular-nums">
                                                        {formatNetWeightMt(
                                                            s.in_progress_gross_mt,
                                                        )}
                                                    </td>
                                                    <td className="px-3 py-2 text-right tabular-nums">
                                                        {formatNetWeightMt(
                                                            s.net_weight_mt,
                                                        )}
                                                    </td>
                                                </tr>,
                                            );
                                        });
                                        const t = day.day_total;
                                        rows.push(
                                            <tr
                                                key={`${day.date}-total`}
                                                className="border-b-2 border-border bg-muted/30 font-medium"
                                            >
                                                <td className="px-3 py-2 tabular-nums">
                                                    TOTAL
                                                </td>
                                                <td className="px-3 py-2 text-right tabular-nums">
                                                    {t.total}
                                                </td>
                                                <td className="px-3 py-2 text-right tabular-nums">
                                                    {t.completed}
                                                </td>
                                                <td className="px-3 py-2 text-right tabular-nums">
                                                    {t.pending}
                                                </td>
                                                <td className="px-3 py-2 text-right tabular-nums">
                                                    {formatNetWeightMt(
                                                        t.in_progress_gross_mt,
                                                    )}
                                                </td>
                                                <td className="px-3 py-2 text-right tabular-nums">
                                                    {formatNetWeightMt(
                                                        t.net_weight_mt,
                                                    )}
                                                </td>
                                            </tr>,
                                        );
                                        return rows;
                                    })
                                )}
                            </tbody>
                        </table>
                        {payload != null && payload.days.length > 0 && (
                            <div className="flex flex-col items-end gap-2 border-t bg-muted/20 px-3 py-2 text-sm sm:flex-row sm:flex-wrap sm:justify-end">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="text-muted-foreground">
                                        Total in progress (gross MT) — range:
                                    </span>
                                    <span className="font-semibold tabular-nums">
                                        {formatNetWeightMt(
                                            payload.days.reduce(
                                                (sum, d) =>
                                                    sum +
                                                    d.day_total
                                                        .in_progress_gross_mt,
                                                0,
                                            ),
                                        )}
                                    </span>
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="text-muted-foreground">
                                        Total net (MT) — range (completed only):
                                    </span>
                                    <span className="font-semibold tabular-nums">
                                        {formatNetWeightMt(
                                            payload.days.reduce(
                                                (sum, d) =>
                                                    sum +
                                                    d.day_total.net_weight_mt,
                                                0,
                                            ),
                                        )}
                                    </span>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <DialogFooter className="shrink-0 border-t px-6 py-4">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        data-pan="daily-vehicle-entries-shift-report-close"
                    >
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
