import {
    formatCurrency,
    formatWeight,
    SectionHeader,
    SidingPerformanceSection,
} from '@/pages/dashboard';
import {
    type DashboardFilters,
    type PenaltyControlRrCoverage,
    type PenaltyTrendChartPayload,
    type PowerPlantDispatchItem,
    type SidingPerformanceItem,
} from './types';
import { cn } from '@/lib/utils';
import { BarChart3, Calendar, Factory, FileCheck2 } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Legend,
    Line,
    LineChart,
    Pie,
    PieChart as RechartsPieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

/** Distinct strokes per siding (cycles for many sidings). */
const PENALTY_TREND_LINE_COLORS = [
    '#2563EB',
    '#16A34A',
    '#D97706',
    '#DC2626',
    '#9333EA',
    '#EA580C',
    '#DB2777',
    '#0D9488',
    '#4F46E5',
    '#64748B',
];

interface Props {
    canWidget: (name: string) => boolean;
    sidingPerformance: SidingPerformanceItem[];
    penaltyTrendDaily: PenaltyTrendChartPayload;
    penaltyControlRrCoverage?: PenaltyControlRrCoverage | null;
    powerPlantDispatch: PowerPlantDispatchItem[];
    filters: DashboardFilters;
}

export function SidingOverview({
    canWidget,
    sidingPerformance,
    penaltyTrendDaily,
    penaltyControlRrCoverage,
    powerPlantDispatch,
    filters,
}: Props) {
    const { series, points } = penaltyTrendDaily;
    const showPenaltyTrend =
        series.length > 0 && points.length > 0;
    const showRrCoverage = canWidget(
        'dashboard.widgets.siding_overview_rr_rake_coverage',
    );
    const showPowerPlant = canWidget(
        'dashboard.widgets.siding_overview_power_plant_distribution',
    );
    const showDispatchRow = showRrCoverage || showPowerPlant;
    const rrCoverage = penaltyControlRrCoverage ?? {
        total_rakes: 0,
        rakes_with_rr: 0,
        rakes_without_rr: 0,
        by_siding: [],
    };

    const rrBarRows =
        (rrCoverage.by_siding?.length ?? 0) > 0
            ? (rrCoverage.by_siding ?? []).map((s) => ({
                  name:
                      s.siding_name.length > 16
                          ? `${s.siding_name.slice(0, 14)}…`
                          : s.siding_name,
                  labelFull: s.siding_name,
                  Rakes: s.total_rakes,
                  'RR uploaded': s.rakes_with_rr,
              }))
            : rrCoverage.total_rakes > 0
              ? [
                    {
                        name: 'All sidings',
                        labelFull: 'Filtered sidings',
                        Rakes: rrCoverage.total_rakes,
                        'RR uploaded': rrCoverage.rakes_with_rr,
                    },
                ]
              : [];

    const rrChartHeight = Math.min(
        440,
        Math.max(220, 48 + rrBarRows.length * 40),
    );

    return (
        <div className="space-y-6">
            {canWidget('dashboard.widgets.siding_overview_performance') ? (
                sidingPerformance.length > 0 ? (
                    <SidingPerformanceSection
                        data={sidingPerformance}
                        filters={filters}
                    />
                ) : (
                    <div className="dashboard-card rounded-xl border-0 p-6">
                        <SectionHeader
                            icon={BarChart3}
                            title="Siding performance"
                            subtitle="Rakes, coal & penalty by siding"
                        />
                        <div className="mt-4 py-8 text-center text-sm text-gray-600">
                            No performance data for selected filters.
                        </div>
                    </div>
                )
            ) : null}
            {canWidget('dashboard.widgets.siding_overview_penalty_trend') ? (
            <div className="dashboard-card rounded-xl border-0 p-6">
                    <SectionHeader
                        icon={Calendar}
                        title="Penalty trend"
                        subtitle="Date / month vs penalty amount by siding"
                    />
                    {showPenaltyTrend ? (
                        <ResponsiveContainer width="100%" height={320}>
                            <LineChart
                                data={points}
                                margin={{
                                    top: 8,
                                    right: 16,
                                    left: 8,
                                    bottom: 8,
                                }}
                            >
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    strokeOpacity={0.3}
                                />
                                <XAxis
                                    dataKey="label"
                                    tick={{ fontSize: 11 }}
                                />
                                <YAxis
                                    tick={{ fontSize: 11 }}
                                    tickFormatter={(v) => formatCurrency(v)}
                                />
                                <Tooltip
                                    formatter={(
                                        v: number | string | undefined,
                                        name: string | undefined,
                                    ) => [
                                        formatCurrency(Number(v ?? 0)),
                                        name ?? '',
                                    ]}
                                />
                                <Legend
                                    layout="horizontal"
                                    align="center"
                                    verticalAlign="bottom"
                                    wrapperStyle={{ paddingTop: 16 }}
                                />
                                {series.map((s, i) => (
                                    <Line
                                        key={s.key}
                                        type="monotone"
                                        dataKey={s.key}
                                        name={s.label}
                                        stroke={
                                            PENALTY_TREND_LINE_COLORS[
                                                i %
                                                    PENALTY_TREND_LINE_COLORS.length
                                            ]
                                        }
                                        strokeWidth={2}
                                        dot={false}
                                        activeDot={{ r: 4 }}
                                        isAnimationActive
                                    />
                                ))}
                            </LineChart>
                    </ResponsiveContainer>
                ) : (
                        <div className="mt-4 py-8 text-center text-sm text-gray-600">
                            No penalty data for selected period.
                        </div>
                )}
            </div>
            ) : null}
            {showDispatchRow ? (
                <div
                    className={cn(
                        'grid gap-6 lg:items-stretch',
                        showRrCoverage && showPowerPlant
                            ? 'lg:grid-cols-2'
                            : 'grid-cols-1',
                    )}
                >
                    {showRrCoverage ? (
                        <div className="dashboard-card min-w-0 rounded-xl border-0 p-6">
                            <SectionHeader
                                icon={FileCheck2}
                                title="RR upload coverage"
                                subtitle="Total rakes vs RR uploaded by siding (loading date in range)"
                            />
                            {rrCoverage.total_rakes > 0 &&
                            rrBarRows.length > 0 ? (
                                <div className="mt-2">
                                    <ResponsiveContainer
                                        width="100%"
                                        height={rrChartHeight}
                                    >
                                        <BarChart
                                            data={rrBarRows}
                                            margin={{
                                                top: 8,
                                                right: 12,
                                                left: 4,
                                                bottom: 8,
                                            }}
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
                                                height={52}
                                            />
                                            <YAxis
                                                type="number"
                                                tick={{ fontSize: 11 }}
                                                allowDecimals={false}
                                                width={36}
                                            />
                                            <Tooltip
                                                formatter={(
                                                    value:
                                                        | number
                                                        | string
                                                        | undefined,
                                                    name:
                                                        | string
                                                        | undefined,
                                                ) => [
                                                    String(value ?? 0),
                                                    name ?? '',
                                                ]}
                                                labelFormatter={(
                                                    _label,
                                                    payload,
                                                ) =>
                                                    String(
                                                        (
                                                            payload?.[0]
                                                                ?.payload as {
                                                                labelFull?: string;
                                                            }
                                                        )?.labelFull ??
                                                            _label ??
                                                            '',
                                                    )
                                                }
                                            />
                                            <Legend
                                                wrapperStyle={{
                                                    paddingTop: 8,
                                                }}
                                            />
                                            <Bar
                                                dataKey="Rakes"
                                                fill="#64748b"
                                                radius={[
                                                    4, 4, 0, 0,
                                                ]}
                                                maxBarSize={32}
                                            />
                                            <Bar
                                                dataKey="RR uploaded"
                                                fill="#22c55e"
                                                radius={[
                                                    4, 4, 0, 0,
                                                ]}
                                                maxBarSize={32}
                                            />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                            ) : (
                                <div className="mt-4 py-8 text-center text-sm text-gray-600">
                                    No rakes in range for selected filters.
                                </div>
                            )}
                        </div>
                    ) : null}
                    {showPowerPlant ? (
                        <div className="dashboard-card min-w-0 rounded-xl border-0 p-6">
                            <SectionHeader
                                icon={Factory}
                                title="Power Plant Wise Dispatch"
                                subtitle="Coal supply by destination"
                            />
                            {powerPlantDispatch.length > 0 ? (
                                (() => {
                                    const totalWeight =
                                        powerPlantDispatch.reduce(
                                            (s, p) => s + p.weight_mt,
                                            0,
                                        );
                                    const donutData = powerPlantDispatch.map(
                                        (pp) => ({
                                            name: pp.name,
                                            value: Math.round(
                                                (pp.weight_mt /
                                                    Math.max(1, totalWeight)) *
                                                    100,
                                            ),
                                            weightMt: pp.weight_mt,
                                        }),
                                    );
                                    return (
                                        <div className="relative">
                                            <ResponsiveContainer
                                                width="100%"
                                                height={280}
                                            >
                                                <RechartsPieChart>
                                                    <Pie
                                                        data={donutData}
                                                        dataKey="value"
                                                        nameKey="name"
                                                        cx="50%"
                                                        cy="50%"
                                                        innerRadius="65%"
                                                        outerRadius="90%"
                                                        paddingAngle={2}
                                                        strokeWidth={0}
                                                    >
                                                        {donutData.map(
                                                            (_, i) => (
                                                                <Cell
                                                                    key={i}
                                                                    fill={
                                                                        [
                                                                            '#22c55e',
                                                                            '#3b82f6',
                                                                            '#f97316',
                                                                            '#eab308',
                                                                            '#a855f7',
                                                                        ][i % 5]
                                                                    }
                                                                />
                                                            ),
                                                        )}
                                                    </Pie>
                                                    <Tooltip
                                                        formatter={(
                                                            v:
                                                                | number
                                                                | undefined,
                                                            _: unknown,
                                                            props: {
                                                                payload?: {
                                                                    weightMt: number;
                                                                };
                                                            },
                                                        ) => [
                                                            `${v ?? 0}%`,
                                                            formatWeight(
                                                                props.payload
                                                                    ?.weightMt ??
                                                                    0,
                                                            ),
                                                        ]}
                                                    />
                                                    <Legend
                                                        layout="horizontal"
                                                        align="center"
                                                        verticalAlign="bottom"
                                                        wrapperStyle={{
                                                            paddingTop: 16,
                                                        }}
                                                        formatter={(
                                                            value,
                                                            entry,
                                                        ) =>
                                                            `${value} ${Number((entry as { payload?: { value: number } }).payload?.value ?? 0)}%`
                                                        }
                                                    />
                                                </RechartsPieChart>
                                            </ResponsiveContainer>
                                            <div className="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col items-center justify-center text-center">
                                                <span className="text-xs font-medium text-gray-600">
                                                    Total
                                                </span>
                                                <span className="text-lg font-bold tabular-nums text-gray-800">
                                                    {formatWeight(totalWeight)}
                                                </span>
                                            </div>
                                        </div>
                                    );
                                })()
                            ) : (
                                <div className="mt-4 py-8 text-center text-sm text-gray-600">
                                    No power plant dispatch data.
                                </div>
                            )}
                        </div>
                    ) : null}
                </div>
            ) : null}
        </div>
    );
}
