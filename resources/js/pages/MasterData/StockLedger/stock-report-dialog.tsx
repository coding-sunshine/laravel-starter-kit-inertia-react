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

export interface StockReportSidingOption {
    id: number;
    name: string;
    code: string;
}

interface StockReportDay {
    date: string;
    opening_mt: number;
    closing_mt: number;
    remarks: string | null;
}

interface StockReportPayload {
    siding: { id: number; name: string; code: string };
    from: string;
    to: string;
    days: StockReportDay[];
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

function formatMt(n: number): string {
    if (Number.isNaN(n)) {
        return '—';
    }
    return new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(n);
}

interface StockReportDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    sidings: StockReportSidingOption[];
}

export default function StockReportDialog({
    open,
    onOpenChange,
    sidings,
}: StockReportDialogProps) {
    const [activeSidingId, setActiveSidingId] = useState<number | null>(
        () => sidings[0]?.id ?? null,
    );
    const [dateRange, setDateRange] = useState(monthStartThroughTodayLocal);
    const { from, to } = dateRange;
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [payload, setPayload] = useState<StockReportPayload | null>(null);
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
                    `/master-data/stock-ledger/stock-report?${params.toString()}`,
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
                    | (StockReportPayload & { message?: string; errors?: Record<string, string[]> })
                    | null;

                if (!res.ok) {
                    setPayload(null);
                    const msgFromErrors =
                        data?.errors &&
                        typeof data.errors === 'object' &&
                        Object.values(data.errors).flat()[0];
                    setError(
                        (typeof data?.message === 'string' ? data.message : null) ??
                            (typeof msgFromErrors === 'string' ? msgFromErrors : null) ??
                            (res.status === 422
                                ? 'Invalid request or date range.'
                                : 'Failed to load stock report.'),
                    );
                    return;
                }

                if (data == null || !Array.isArray(data.days)) {
                    setPayload(null);
                    setError('Invalid response from server.');
                    return;
                }

                setPayload(data as StockReportPayload);
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
                    <DialogTitle>Stock report</DialogTitle>
                    <DialogDescription>
                        Daily opening and closing balance (MT) from the stock ledger. Days without
                        ledger lines carry the previous balance; remarks note when no transactions
                        occurred.
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
                                data-pan={`stock-ledger-stock-report-tab-${s.code.toLowerCase()}`}
                            >
                                {tabLabelForCode(s.code)}
                            </button>
                        ))}
                    </div>

                    <div className="flex flex-wrap items-end gap-3">
                        <div className="space-y-1">
                            <label
                                htmlFor="stock-report-from"
                                className="text-xs font-medium text-muted-foreground"
                            >
                                From
                            </label>
                            <Input
                                id="stock-report-from"
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
                                htmlFor="stock-report-to"
                                className="text-xs font-medium text-muted-foreground"
                            >
                                To
                            </label>
                            <Input
                                id="stock-report-to"
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
                            size="sm"
                            onClick={applyRangeAndRefetch}
                            disabled={loading}
                            data-pan="stock-ledger-stock-report-apply-range"
                        >
                            Apply range
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => void fetchReport()}
                            disabled={loading}
                            data-pan="stock-ledger-stock-report-refresh"
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
                                    <th className="px-3 py-2 text-left font-medium">Date</th>
                                    <th className="px-3 py-2 text-right font-medium">
                                        Opening (MT)
                                    </th>
                                    <th className="px-3 py-2 text-right font-medium">
                                        Closing (MT)
                                    </th>
                                    <th className="px-3 py-2 text-left font-medium">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                {payload == null || payload.days.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-3 py-8 text-center text-muted-foreground"
                                        >
                                            {loading
                                                ? 'Loading…'
                                                : 'No data in this range.'}
                                        </td>
                                    </tr>
                                ) : (
                                    payload.days.map((row) => (
                                        <tr
                                            key={row.date}
                                            className="border-b border-border/80"
                                        >
                                            <td className="px-3 py-2 text-muted-foreground">
                                                {formatDisplayDate(row.date)}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums">
                                                {formatMt(row.opening_mt)}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums">
                                                {formatMt(row.closing_mt)}
                                            </td>
                                            <td className="max-w-[min(24rem,40vw)] px-3 py-2 text-muted-foreground">
                                                {row.remarks ?? '—'}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <DialogFooter className="shrink-0 border-t px-6 py-4">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        data-pan="stock-ledger-stock-report-close"
                    >
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
