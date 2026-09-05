import { StackedBarChart } from '@/components/charts/stacked-bar-chart';
import {
    formatCurrency,
    SectionHeader,
} from '@/components/dashboard/shared';
import { Train } from 'lucide-react';
import { useMemo } from 'react';

const DISPATCH_COLORS = [
    '#4682b4',
    '#2d6a4f',
    '#e6b800',
    '#6b9bc4',
    '#40916c',
];
const PENALTY_COLORS = ['#c41e3a', '#e6b800', '#dc3545', '#64748b'];

export interface DateWiseDispatchSectionData {
    sidingNames: Record<number, string>;
    dates: Record<string, unknown>[];
}

export function DateWiseDispatchSection({
    data,
}: {
    data: DateWiseDispatchSectionData;
}) {
    const { sidingNames, dates } = data;
    const sidingIds = useMemo(
        () => Object.keys(sidingNames).map(Number),
        [sidingNames],
    );

    const dispatchKeys = useMemo(
        () => sidingIds.map((id) => `dispatched_${id}`),
        [sidingIds],
    );
    const penaltyKeys = useMemo(
        () => sidingIds.map((id) => `penalty_${id}`),
        [sidingIds],
    );

    const dispatchLabels = useMemo(() => {
        const labels: Record<string, string> = {};
        for (const id of sidingIds) {
            labels[`dispatched_${id}`] = sidingNames[id];
        }
        return labels;
    }, [sidingIds, sidingNames]);

    const penaltyLabels = useMemo(() => {
        const labels: Record<string, string> = {};
        for (const id of sidingIds) {
            labels[`penalty_${id}`] = sidingNames[id];
        }
        return labels;
    }, [sidingIds, sidingNames]);

    const dispatchColors = useMemo(() => {
        const c: Record<string, string> = {};
        sidingIds.forEach((id, i) => {
            c[`dispatched_${id}`] = DISPATCH_COLORS[i % DISPATCH_COLORS.length];
        });
        return c;
    }, [sidingIds]);

    const penaltyColors = useMemo(() => {
        const c: Record<string, string> = {};
        sidingIds.forEach((id, i) => {
            c[`penalty_${id}`] = PENALTY_COLORS[i % PENALTY_COLORS.length];
        });
        return c;
    }, [sidingIds]);

    const totals = useMemo(() => {
        let dispatched = 0;
        let penalty = 0;
        for (const row of dates) {
            dispatched += (row.total_dispatched as number) ?? 0;
            penalty += (row.total_penalty as number) ?? 0;
        }
        return { dispatched, penalty: Math.round(penalty) };
    }, [dates]);

    if (dates.length === 0 || sidingIds.length === 0) {
        return null;
    }

    return (
        <div className="rounded-xl border bg-card p-5">
            <SectionHeader
                icon={Train}
                title="Date-wise rail dispatch & penalties"
                subtitle="Siding-wise breakdown by date"
            />

            <div className="mt-4 grid gap-3 sm:grid-cols-3">
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">
                        Total rakes dispatched
                    </p>
                    <p className="mt-1 text-2xl font-bold tabular-nums">
                        {totals.dispatched}
                    </p>
                </div>
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">
                        Total penalty amount
                    </p>
                    <p className="mt-1 text-2xl font-bold tabular-nums text-red-600 dark:text-red-400">
                        {formatCurrency(totals.penalty)}
                    </p>
                </div>
                <div className="rounded-lg border bg-muted/20 p-3.5">
                    <p className="text-xs font-medium text-muted-foreground">
                        Sidings
                    </p>
                    <p className="mt-1 text-2xl font-bold tabular-nums">
                        {sidingIds.length}
                    </p>
                    <p className="mt-0.5 text-xs text-muted-foreground">
                        {Object.values(sidingNames).join(', ')}
                    </p>
                </div>
            </div>

            <div className="mt-5">
                <p className="mb-2 text-sm font-semibold">
                    Rakes dispatched (siding-wise)
                </p>
                <StackedBarChart
                    data={dates}
                    xKey="date"
                    stackKeys={dispatchKeys}
                    stackLabels={dispatchLabels}
                    stackColors={dispatchColors}
                    yLabel="Rakes"
                    height={300}
                    allowDecimals={false}
                    formatTooltip={(v) => `${v} rakes`}
                />
            </div>

            <div className="mt-5">
                <p className="mb-2 text-sm font-semibold">
                    Penalty amount (siding-wise)
                </p>
                <StackedBarChart
                    data={dates}
                    xKey="date"
                    stackKeys={penaltyKeys}
                    stackLabels={penaltyLabels}
                    stackColors={penaltyColors}
                    yLabel="₹"
                    height={300}
                    formatTooltip={(v) => `₹${v.toLocaleString()}`}
                />
            </div>
        </div>
    );
}
