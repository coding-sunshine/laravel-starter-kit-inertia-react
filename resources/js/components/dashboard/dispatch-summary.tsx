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
} from '@/components/dashboard/types';
import { Truck } from 'lucide-react';
import { useMemo, useState } from 'react';

const mt = new Intl.NumberFormat('en-IN', { maximumFractionDigits: 0 });

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

const EMPTY_DISPATCH_SUMMARY: DispatchSummaryByPeriod = {
    default_period: 'today',
    periods: {
        today: { received_mt: 0, dispatched_mt: 0, mines_dispatch_mt: 0, opening_balance_mt: 0, from: '', to: '' },
        yesterday: { received_mt: 0, dispatched_mt: 0, mines_dispatch_mt: 0, opening_balance_mt: 0, from: '', to: '' },
        month: { received_mt: 0, dispatched_mt: 0, mines_dispatch_mt: 0, opening_balance_mt: 0, from: '', to: '' },
        last_month: { received_mt: 0, dispatched_mt: 0, mines_dispatch_mt: 0, opening_balance_mt: 0, from: '', to: '' },
        fy: { received_mt: 0, dispatched_mt: 0, mines_dispatch_mt: 0, opening_balance_mt: 0, from: '', to: '' },
    },
};

export function DispatchSummary({
    data,
    className,
}: {
    data?: DispatchSummaryByPeriod | null;
    className?: string;
}) {
    const summary = data ?? EMPTY_DISPATCH_SUMMARY;
    const [period, setPeriod] = useState<DispatchSummaryPeriodKey>(
        summary.default_period ?? 'today',
    );

    const slice = summary.periods[period] ?? summary.periods.today;

    const totalDispatched = slice?.dispatched_mt ?? 0;
    const totalReceived = slice?.received_mt ?? 0;
    const totalMinesDispatch = slice?.mines_dispatch_mt ?? 0;
    const openingBalance = slice?.opening_balance_mt ?? 0;
    // Net = opening_balance + received − rail_dispatch
    const variance = openingBalance + totalReceived - totalDispatched;
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
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <Stat
                        label="Mines Dispatch"
                        value={totalMinesDispatch}
                        className="hidden"
                    />
                    <Stat
                        label="Siding Received"
                        value={totalReceived}
                        className="hidden"
                    />
                    <Stat label="Rail Dispatch" value={totalDispatched} />
                </div>

                <div className="mt-auto">
                    <div className="mb-1 flex items-center justify-between text-[11px]">
                        <span className="text-muted-foreground">
                            Net (opening balance + received − rail dispatch)
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

function Stat({
    label,
    value,
    className,
}: {
    label: string;
    value: number;
    className?: string;
}) {
    return (
        <div className={cn('rounded-md border bg-card p-3', className)}>
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
