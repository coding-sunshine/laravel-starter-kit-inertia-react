import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type {
    DispatchSummaryByPeriod,
    DispatchSummaryPeriodKey,
    RoadTripSummaryByPeriod,
    RoadTripSummaryQty,
    RoadTripSummarySidingRow,
    RoadTripSummaryTrips,
    SidingOption,
} from '@/pages/dashboard/types';
import { Truck } from 'lucide-react';
import { useMemo, useState } from 'react';

const mt = new Intl.NumberFormat('en-IN', { maximumFractionDigits: 0 });
const qtyFmt = new Intl.NumberFormat('en-IN', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});
const intFmt = new Intl.NumberFormat('en-IN', { maximumFractionDigits: 0 });

const PERIOD_OPTIONS: {
    value: DispatchSummaryPeriodKey;
    label: string;
    title: string;
}[] = [
    { value: 'today', label: 'Today', title: "Today's dispatch" },
    { value: 'yesterday', label: 'Yesterday', title: "Yesterday's dispatch" },
    { value: 'month', label: 'This month', title: "This month's dispatch" },
    {
        value: 'last_month',
        label: 'Last month',
        title: "Last month's dispatch",
    },
    { value: 'fy', label: 'FY', title: 'FY dispatch' },
];

const SIDING_ACCENT: Record<string, string> = {
    Dumka: '#3B82F6',
    Kurwa: '#10B981',
    Pakur: '#F59E0B',
};

const EMPTY_DISPATCH_SUMMARY: DispatchSummaryByPeriod = {
    default_period: 'today',
    periods: {
        today: { received_mt: 0, dispatched_mt: 0, from: '', to: '' },
        yesterday: { received_mt: 0, dispatched_mt: 0, from: '', to: '' },
        month: { received_mt: 0, dispatched_mt: 0, from: '', to: '' },
        last_month: { received_mt: 0, dispatched_mt: 0, from: '', to: '' },
        fy: { received_mt: 0, dispatched_mt: 0, from: '', to: '' },
    },
};

const EMPTY_ROAD_TRIP_SLICE = {
    from: '',
    to: '',
    rows: [] as RoadTripSummarySidingRow[],
    totals: {
        trips: { total: 0, stock_added: 0, pending: 0, dispatched: 0 },
        qty: {
            total_mt: 0,
            stock_added_mt: 0,
            pending_mt: 0,
            dispatched_mt: 0,
        },
    },
};

type ViewMode = 'trips' | 'qty';

export function DispatchSummary({
    data,
    roadTripData,
    roadTripLoading = false,
    roadTripError = null,
    loadRoadTrip = false,
    sidings = [],
    className,
}: {
    data?: DispatchSummaryByPeriod | null;
    roadTripData?: RoadTripSummaryByPeriod | null;
    roadTripLoading?: boolean;
    roadTripError?: string | null;
    loadRoadTrip?: boolean;
    sidings?: SidingOption[];
    className?: string;
}) {
    const summary = data ?? EMPTY_DISPATCH_SUMMARY;
    const roadTrip = roadTripData;
    const [period, setPeriod] = useState<DispatchSummaryPeriodKey>(
        summary.default_period ?? 'today',
    );
    const [viewMode, setViewMode] = useState<ViewMode>('trips');

    const slice = summary.periods[period] ?? summary.periods.today;
    const roadSlice =
        roadTrip?.periods[period] ?? roadTrip?.periods.today ?? EMPTY_ROAD_TRIP_SLICE;

    const totalDispatched = slice?.dispatched_mt ?? 0;
    const totalReceived = slice?.received_mt ?? 0;
    const variance = totalDispatched - totalReceived;
    const balancePct =
        totalDispatched > 0 || totalReceived > 0
            ? Math.min(
                  100,
                  (Math.min(totalDispatched, totalReceived) /
                      Math.max(totalDispatched, totalReceived)) *
                      100,
              )
            : 0;

    const title = useMemo(() => {
        const opt = PERIOD_OPTIONS.find((o) => o.value === period);
        if (period === 'fy' && slice?.from) {
            const startYear = slice.from.slice(0, 4);
            const endYear = String(Number(startYear) + 1).slice(-2);

            return `FY ${startYear}-${endYear} dispatch`;
        }

        return opt?.title ?? "Today's dispatch";
    }, [period, slice?.from]);

    const roadRows = roadSlice.rows ?? [];
    const showRoadTripSection =
        loadRoadTrip &&
        sidings.length > 0 &&
        (roadTripLoading || roadTrip != null || roadTripError != null);

    return (
        <Card className={cn('h-full shadow-sm', className)}>
            <CardHeader className="flex flex-row items-start justify-between gap-3 space-y-0 pb-3">
                <CardTitle className="flex items-center gap-2 text-sm font-semibold text-foreground">
                    <Truck
                        className="h-4 w-4 text-emerald-700 dark:text-emerald-400"
                        aria-hidden="true"
                    />
                    {title}
                </CardTitle>
                <Select
                    value={period}
                    onValueChange={(v) =>
                        setPeriod(v as DispatchSummaryPeriodKey)
                    }
                >
                    <SelectTrigger className="h-8 w-[8.5rem] text-xs">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {PERIOD_OPTIONS.map((opt) => (
                            <SelectItem key={opt.value} value={opt.value}>
                                {opt.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </CardHeader>
            <CardContent className="flex flex-1 flex-col space-y-4">
                <div className="grid grid-cols-2 gap-3">
                    <Stat label="Dispatched" value={totalDispatched} />
                    <Stat label="Received" value={totalReceived} />
                </div>

                {showRoadTripSection && (
                    <div className="space-y-3 border-t pt-4">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <p className="text-xs font-semibold text-muted-foreground">
                                Road trips
                            </p>
                            {roadTrip != null && !roadTripLoading ? (
                                <div className="inline-flex rounded-lg border bg-muted/40 p-0.5">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant={
                                            viewMode === 'trips'
                                                ? 'default'
                                                : 'ghost'
                                        }
                                        className="h-7 px-3 text-xs"
                                        onClick={() => setViewMode('trips')}
                                    >
                                        Trips
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant={
                                            viewMode === 'qty'
                                                ? 'default'
                                                : 'ghost'
                                        }
                                        className="h-7 px-3 text-xs"
                                        onClick={() => setViewMode('qty')}
                                    >
                                        Qty
                                    </Button>
                                </div>
                            ) : null}
                        </div>

                        {roadTripLoading ? (
                            <div className="space-y-2">
                                {sidings.slice(0, 3).map((siding) => (
                                    <div
                                        key={siding.value}
                                        className="animate-pulse rounded-lg border bg-card p-3"
                                    >
                                        <div className="mb-2 h-3 w-24 rounded bg-muted" />
                                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                            {[0, 1, 2, 3].map((i) => (
                                                <div
                                                    key={i}
                                                    className="h-10 rounded bg-muted"
                                                />
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : roadTripError ? (
                            <p className="text-xs text-destructive">
                                {roadTripError}
                            </p>
                        ) : (
                        <div className="space-y-2">
                            {roadRows.map((row) => {
                                const accent =
                                    SIDING_ACCENT[row.siding_name] ?? '#6B7280';

                                return (
                                    <div
                                        key={row.siding_id}
                                        className="rounded-lg border bg-card p-3"
                                    >
                                        <p
                                            className="mb-2 text-xs font-semibold"
                                            style={{ color: accent }}
                                        >
                                            {row.siding_name}
                                        </p>
                                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                            <TripKpi
                                                label="Total"
                                                viewMode={viewMode}
                                                trips={row.trips}
                                                qty={row.qty}
                                                kind="total"
                                                compact
                                            />
                                            <TripKpi
                                                label="Stock added"
                                                viewMode={viewMode}
                                                trips={row.trips}
                                                qty={row.qty}
                                                kind="stock_added"
                                                variant="success"
                                                compact
                                            />
                                            <TripKpi
                                                label="Pending"
                                                viewMode={viewMode}
                                                trips={row.trips}
                                                qty={row.qty}
                                                kind="pending"
                                                variant="warning"
                                                compact
                                            />
                                            <TripKpi
                                                label="Dispatched"
                                                viewMode={viewMode}
                                                trips={row.trips}
                                                qty={row.qty}
                                                kind="dispatched"
                                                variant="info"
                                                compact
                                            />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                        )}
                    </div>
                )}

                <div className="mt-auto">
                    <div className="mb-1 flex items-center justify-between text-[11px]">
                        <span className="text-muted-foreground">
                            Net (dispatched − received)
                        </span>
                        <span
                            className={`font-mono font-semibold tabular-nums ${variance > 0 ? 'text-amber-600 dark:text-amber-400' : variance < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'}`}
                        >
                            {variance >= 0 ? '' : '−'}
                            {mt.format(Math.abs(variance))} MT
                        </span>
                    </div>
                    <div
                        className="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        aria-valuenow={Math.round(balancePct)}
                        aria-valuemin={0}
                        aria-valuemax={100}
                    >
                        <div
                            className="h-full rounded-full bg-emerald-500 transition-[width] duration-300"
                            style={{ width: `${balancePct}%` }}
                        />
                    </div>
                    {slice?.from && slice?.to && (
                    <p className="mt-1 text-[11px] text-muted-foreground">
                            {slice.from === slice.to
                                ? slice.from
                                : `${slice.from} – ${slice.to}`}
                    </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

function Stat({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-md border bg-card p-3">
            <p className="mb-1 text-xs font-medium text-muted-foreground">
                {label}
            </p>
            <p className="font-mono text-xl font-bold tabular-nums text-foreground">
                {mt.format(value)}
                <span className="ml-1 text-[11px] font-normal text-muted-foreground">
                    MT
                </span>
            </p>
        </div>
    );
}

function TripKpi({
    label,
    viewMode,
    trips,
    qty,
    kind,
    variant = 'default',
    compact = false,
}: {
    label: string;
    viewMode: ViewMode;
    trips: RoadTripSummaryTrips;
    qty: RoadTripSummaryQty;
    kind: 'total' | 'stock_added' | 'pending' | 'dispatched';
    variant?: 'default' | 'success' | 'warning' | 'info';
    compact?: boolean;
}) {
    const tripValue =
        kind === 'total'
            ? trips.total
            : kind === 'stock_added'
              ? trips.stock_added
              : kind === 'pending'
                ? trips.pending
                : trips.dispatched;
    const qtyValue =
        kind === 'total'
            ? qty.total_mt
            : kind === 'stock_added'
              ? qty.stock_added_mt
              : kind === 'pending'
                ? qty.pending_mt
                : qty.dispatched_mt;

    const valueClass =
        variant === 'success'
            ? 'text-emerald-700 dark:text-emerald-400'
            : variant === 'warning'
              ? 'text-amber-700 dark:text-amber-400'
              : variant === 'info'
                ? 'text-blue-700 dark:text-blue-400'
                : 'text-foreground';

    return (
        <div
            className={cn(
                'rounded-md border bg-muted/20 p-2.5',
                compact && 'p-2',
            )}
        >
            <p className="mb-0.5 text-[10px] font-medium text-muted-foreground">
                {label}
            </p>
            <p
                className={cn(
                    'font-mono font-bold tabular-nums',
                    compact ? 'text-base' : 'text-lg',
                    valueClass,
                )}
            >
                {viewMode === 'trips'
                    ? intFmt.format(tripValue)
                    : `${qtyFmt.format(qtyValue)} MT`}
            </p>
        </div>
    );
}
