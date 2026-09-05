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

export interface ReconciliationReportSidingOption {
    id: number;
    name: string;
    code: string;
}

interface ReconciliationShiftRow {
    shift: number;
    dispatch_trips: number;
    dispatch_qty: number;
    received_trips: number;
    received_qty: number;
    in_transit_trips: number;
    in_transit_qty: number;
    stock_updated_mt: number;
    in_progress_gross_mt: number;
}

interface ReconciliationDay {
    date: string;
    shifts: ReconciliationShiftRow[];
    day_total: Omit<ReconciliationShiftRow, 'shift'>;
}

type ReconciliationMetrics = Omit<ReconciliationShiftRow, 'shift'>;

interface ReconciliationRangeTotal extends ReconciliationMetrics {}

interface ReconciliationPayload {
    siding: { id: number; name: string; code: string };
    from: string;
    to: string;
    days: ReconciliationDay[];
    range_total: ReconciliationRangeTotal;
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

/** Monday of the current calendar week through today (local). */
function weekStartThroughTodayLocal(): { from: string; to: string } {
    const now = new Date();
    const day = now.getDay();
    const diffToMonday = day === 0 ? 6 : day - 1;
    const start = new Date(now);
    start.setDate(now.getDate() - diffToMonday);
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

function formatMt(mt: number): string {
    if (Number.isNaN(mt)) {
        return '—';
    }
    return new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(mt);
}

function formatTrips(n: number): string {
    if (Number.isNaN(n)) {
        return '—';
    }
    return n.toLocaleString('en-IN');
}

const TABLE_COLUMN_COUNT = 10;

function MetricsCells({ m }: { m: ReconciliationMetrics }) {
    return (
        <>
            <td className="px-2 py-2 text-right tabular-nums">
                {formatTrips(m.dispatch_trips)}
            </td>
            <td className="px-2 py-2 text-right tabular-nums">
                {formatMt(m.dispatch_qty)}
            </td>
            <td className="px-2 py-2 text-right tabular-nums">
                {formatTrips(m.received_trips)}
            </td>
            <td className="px-2 py-2 text-right tabular-nums">
                {formatMt(m.received_qty)}
            </td>
            <td className="px-2 py-2 text-right tabular-nums">
                {formatTrips(m.in_transit_trips)}
            </td>
            <td className="px-2 py-2 text-right tabular-nums">
                {formatMt(m.in_transit_qty)}
            </td>
            <td className="px-2 py-2 text-right tabular-nums">
                {formatMt(m.stock_updated_mt)}
            </td>
            <td className="px-2 py-2 text-right tabular-nums">
                {formatMt(m.in_progress_gross_mt)}
            </td>
        </>
    );
}

interface DispatchReconciliationDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    sidings: ReconciliationReportSidingOption[];
}

export default function DispatchReconciliationDialog({
    open,
    onOpenChange,
    sidings,
}: DispatchReconciliationDialogProps) {
    const [activeSidingId, setActiveSidingId] = useState<number | null>(
        () => sidings[0]?.id ?? null,
    );
    const [dateRange, setDateRange] = useState(weekStartThroughTodayLocal);
    const { from, to } = dateRange;
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [payload, setPayload] = useState<ReconciliationPayload | null>(null);
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
        setDateRange(weekStartThroughTodayLocal());
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
                    `/vehicle-dispatch/reconciliation-report?${params.toString()}`,
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
                    | (ReconciliationPayload & { message?: string })
                    | null;

                if (!res.ok) {
                    setPayload(null);
                    setError(
                        (data && typeof data.message === 'string'
                            ? data.message
                            : null) ??
                            (res.status === 403
                                ? 'You do not have access to this report.'
                                : res.status === 422
                                  ? 'Invalid date range.'
                                  : 'Failed to load dispatch reconciliation report.'),
                    );
                    return;
                }

                if (data == null || !Array.isArray(data.days)) {
                    setPayload(null);
                    setError('Invalid response from server.');
                    return;
                }

                setPayload(data as ReconciliationPayload);
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
        void fetchReport(weekStartThroughTodayLocal());
    }, [open, activeSidingId, fetchReport]);

    const applyRangeAndRefetch = (): void => {
        void fetchReport({ from, to });
    };

    const rangeTotal = payload?.range_total;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex h-auto max-h-[92vh] w-[min(96rem,calc(100vw-1.5rem))] max-w-none flex-col gap-0 p-0 sm:max-w-none sm:w-[min(96rem,calc(100vw-2rem))]">
                <DialogHeader className="shrink-0 space-y-1 border-b px-6 py-4">
                    <DialogTitle>Dispatch vs received</DialogTitle>
                    <DialogDescription>
                        Coal-site dispatch vs trucks received at the railway
                        siding. In transit = dispatched − received. Stock
                        updated and in progress follow the same rules as the
                        shift report on daily vehicle entries.
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
                                data-pan={`vehicle-dispatch-reconciliation-report-tab-${s.code.toLowerCase()}`}
                            >
                                {tabLabelForCode(s.code)}
                            </button>
                        ))}
                    </div>

                    <div className="flex flex-wrap items-end gap-3">
                        <div className="space-y-1">
                            <label
                                htmlFor="reconciliation-report-from"
                                className="text-xs font-medium text-muted-foreground"
                            >
                                From
                            </label>
                            <Input
                                id="reconciliation-report-from"
                                type="date"
                                value={from}
                                onChange={(e) =>
                                    setDateRange((r) => ({
                                        ...r,
                                        from: e.target.value,
                                    }))
                                }
                                className="w-[11rem]"
                            />
                        </div>
                        <div className="space-y-1">
                            <label
                                htmlFor="reconciliation-report-to"
                                className="text-xs font-medium text-muted-foreground"
                            >
                                To
                            </label>
                            <Input
                                id="reconciliation-report-to"
                                type="date"
                                value={to}
                                onChange={(e) =>
                                    setDateRange((r) => ({
                                        ...r,
                                        to: e.target.value,
                                    }))
                                }
                                className="w-[11rem]"
                            />
                        </div>
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            onClick={() => applyRangeAndRefetch()}
                            disabled={loading}
                            data-pan="vehicle-dispatch-reconciliation-report-apply-range"
                        >
                            Apply range
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => void fetchReport()}
                            disabled={loading}
                            data-pan="vehicle-dispatch-reconciliation-report-refresh"
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
                                    <th className="px-2 py-2 text-left font-medium">
                                        Date
                                    </th>
                                    <th className="px-2 py-2 text-left font-medium">
                                        Shift
                                    </th>
                                    <th className="px-2 py-2 text-right font-medium">
                                        Disp. trips
                                    </th>
                                    <th className="px-2 py-2 text-right font-medium">
                                        Disp. MT
                                    </th>
                                    <th className="px-2 py-2 text-right font-medium">
                                        Rec. trips
                                    </th>
                                    <th className="px-2 py-2 text-right font-medium">
                                        Rec. MT
                                    </th>
                                    <th className="px-2 py-2 text-right font-medium">
                                        Transit trips
                                    </th>
                                    <th className="px-2 py-2 text-right font-medium">
                                        Transit MT
                                    </th>
                                    <th className="px-2 py-2 text-right font-medium">
                                        Stock updated
                                    </th>
                                    <th className="px-2 py-2 text-right font-medium">
                                        In progress
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {payload == null || payload.days.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={TABLE_COLUMN_COUNT}
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
                                                            className="px-2 py-2 align-top text-muted-foreground"
                                                            rowSpan={4}
                                                        >
                                                            {dateShown}
                                                        </td>
                                                    ) : null}
                                                    <td className="px-2 py-2 tabular-nums">
                                                        S{s.shift}
                                                    </td>
                                                    <MetricsCells m={s} />
                                                </tr>,
                                            );
                                        });
                                        const t = day.day_total;
                                        rows.push(
                                            <tr
                                                key={`${day.date}-total`}
                                                className="border-b-2 border-border bg-muted/30 font-medium"
                                            >
                                                <td className="px-2 py-2 tabular-nums">
                                                    Day total
                                                </td>
                                                <MetricsCells m={t} />
                                            </tr>,
                                        );
                                        return rows;
                                    })
                                )}
                            </tbody>
                            {rangeTotal != null &&
                                payload != null &&
                                payload.days.length > 0 && (
                                    <tfoot className="sticky bottom-0 border-t-2 border-border bg-muted/50 font-semibold">
                                        <tr>
                                            <td
                                                className="px-2 py-2"
                                                colSpan={2}
                                            >
                                                Range total
                                            </td>
                                            <MetricsCells m={rangeTotal} />
                                        </tr>
                                    </tfoot>
                                )}
                        </table>
                    </div>
                </div>

                <DialogFooter className="shrink-0 border-t px-6 py-4">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        data-pan="vehicle-dispatch-reconciliation-report-close"
                    >
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
