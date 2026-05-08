import {
    DashboardPenaltyBySidingChart,
    formatCurrency,
    PENALTY_CONTROL_PENALTY_BY_SIDING_PERIOD_OPTIONS,
    SectionHeader,
    type PenaltyBySidingChartPeriodKey,
} from '../dashboard';
import type { PenaltyByTypePoint, PenaltyBySidingPoint } from './types';
import { cn } from '@/lib/utils';
import { AlertTriangle } from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    Bar,
    BarChart as RechartsBarChart,
    CartesianGrid,
    Cell,
    LabelList,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface ExecutiveYesterdayForPenalty {
    penaltyBySidingByPeriod?: Record<
        'yesterday' | 'today' | 'month' | 'fy' | 'last_month',
        PenaltyBySidingPoint[]
    >;
}

interface Props {
    canWidget: (name: string) => boolean;
    penaltyByType: PenaltyByTypePoint[];
    penaltyBySiding: PenaltyBySidingPoint[];
    executiveYesterday?: ExecutiveYesterdayForPenalty;
}

const PENALTY_TYPE_COLORS: Record<string, string> = {
    Demurrage: '#DC2626',
    Overloading: '#F59E0B',
    Wharfage: '#8B5CF6',
};

const PENALTY_TYPE_FALLBACK_COLORS = [
    '#64748B',
    '#94A3B8',
    '#3B82F6',
    '#10B981',
];

function PenaltiesAndChargesChart({ data }: { data: PenaltyByTypePoint[] }) {
    const totalType = data.reduce((s, p) => s + p.value, 0);
    const barData = [...data].sort((a, b) => b.value - a.value);
    const chartHeight = Math.min(
        480,
        Math.max(260, barData.length * 52 + 80),
    );

    return (
        <div className="mt-4 space-y-4">
            <p className="text-xs text-gray-600">
                Total:{' '}
                <span className="font-semibold tabular-nums text-gray-900">
                    {formatCurrency(totalType)}
                </span>
            </p>
            <ResponsiveContainer width="100%" height={chartHeight}>
                <RechartsBarChart
                    data={barData}
                    margin={{ top: 28, right: 16, bottom: 8, left: 8 }}
                >
                    <CartesianGrid
                        strokeDasharray="3 3"
                        strokeOpacity={0.25}
                        vertical={false}
                    />
                    <XAxis
                        dataKey="name"
                        type="category"
                        tick={{ fontSize: 11 }}
                        interval={0}
                        height={48}
                    />
                    <YAxis
                        type="number"
                        tick={{ fontSize: 11 }}
                        width={72}
                        tickFormatter={(v) => formatCurrency(v)}
                    />
                    <Tooltip
                        formatter={(v: number | string | undefined) =>
                            formatCurrency(Number(v ?? 0))
                        }
                    />
                    <Bar
                        dataKey="value"
                        name="Amount"
                        radius={[4, 4, 0, 0]}
                        barSize={28}
                        isAnimationActive
                    >
                        {barData.map((entry, i) => (
                            <Cell
                                key={entry.name}
                                fill={
                                    PENALTY_TYPE_COLORS[entry.name] ??
                                    PENALTY_TYPE_FALLBACK_COLORS[
                                        i % PENALTY_TYPE_FALLBACK_COLORS.length
                                    ]
                                }
                            />
                        ))}
                        <LabelList
                            dataKey="value"
                            position="top"
                            formatter={(v: unknown) =>
                                formatCurrency(Number(v ?? 0))
                            }
                            style={{ fontSize: 11 }}
                        />
                    </Bar>
                </RechartsBarChart>
            </ResponsiveContainer>
            <div className="flex flex-wrap justify-center gap-x-6 gap-y-2">
                {barData.map((entry, i) => (
                    <div
                        key={entry.name}
                        className="flex items-center gap-2 text-sm"
                    >
                        <span
                            className="size-2.5 shrink-0 rounded-full"
                            style={{
                                backgroundColor:
                                    PENALTY_TYPE_COLORS[entry.name] ??
                                    PENALTY_TYPE_FALLBACK_COLORS[
                                        i % PENALTY_TYPE_FALLBACK_COLORS.length
                                    ],
                            }}
                        />
                        <span className="text-gray-700">{entry.name}</span>
                        <span className="font-medium tabular-nums text-gray-800">
                            {formatCurrency(entry.value)}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}

export function PenaltyControl({
    canWidget,
    penaltyByType,
    penaltyBySiding,
    executiveYesterday,
}: Props) {
    const [sidingOverviewPenaltyPeriod, setSidingOverviewPenaltyPeriod] =
        useState<PenaltyBySidingChartPeriodKey>('yesterday');

    const penaltyBySidingForSidingOverview = useMemo(() => {
        const slices = executiveYesterday?.penaltyBySidingByPeriod;
        if (slices) {
            return slices[sidingOverviewPenaltyPeriod] ?? [];
        }
        return penaltyBySiding;
    }, [
        executiveYesterday?.penaltyBySidingByPeriod,
        penaltyBySiding,
        sidingOverviewPenaltyPeriod,
    ]);

    const showCharges = canWidget(
        'dashboard.widgets.penalty_control_type_distribution',
    );
    const showSiding = canWidget(
        'dashboard.widgets.penalty_control_penalty_by_siding',
    );
    const bothColumns = showCharges && showSiding;

    return (
        <div className="space-y-6">
            {(showCharges || showSiding) && (
                <div
                    className={cn(
                        'grid gap-6 lg:items-stretch',
                        bothColumns ? 'lg:grid-cols-2' : 'grid-cols-1',
                    )}
                >
                    {showCharges ? (
                        <div className="dashboard-card min-w-0 rounded-xl border-0 p-6">
                            <SectionHeader
                                icon={AlertTriangle}
                                title="Penalties and Charges"
                                subtitle="Overloading, demurrage, wharfage, etc."
                            />
                            {penaltyByType.length > 0 ? (
                                <PenaltiesAndChargesChart data={penaltyByType} />
                            ) : (
                                <div className="mt-4 py-8 text-center text-sm text-gray-600">
                                    No penalty type data.
                                </div>
                            )}
                        </div>
                    ) : null}

                    {showSiding ? (
                        <div className="min-w-0">
                            <DashboardPenaltyBySidingChart
                                className="h-full"
                                data={penaltyBySidingForSidingOverview}
                                {...(executiveYesterday?.penaltyBySidingByPeriod
                                    ? {
                                          period: sidingOverviewPenaltyPeriod,
                                          onPeriodChange:
                                              setSidingOverviewPenaltyPeriod,
                                          periodOptions:
                                              PENALTY_CONTROL_PENALTY_BY_SIDING_PERIOD_OPTIONS,
                                      }
                                    : {})}
                            />
                        </div>
                    ) : null}
                </div>
            )}
        </div>
    );
}
