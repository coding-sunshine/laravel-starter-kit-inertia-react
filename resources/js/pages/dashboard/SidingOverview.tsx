import { formatCurrency, formatWeight, SectionHeader, SidingPerformanceSection } from '../dashboard';
import type {
    DashboardFilters,
    DateWiseDispatchData,
    ExecutiveChartPeriodKey,
    PenaltyBySidingPoint,
    PowerPlantDispatchItem,
    SidingComparisonData,
    SidingOption,
    SidingPerformanceItem,
    SidingWiseMonthlyPoint,
} from './types';
import { BarChart3, Calendar, Factory } from 'lucide-react';
import {
    Area,
    AreaChart as RechartsAreaChart,
    CartesianGrid,
    Cell,
    Legend,
    Pie,
    PieChart as RechartsPieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface Props {
    canWidget: (name: string) => boolean;
    sidingPerformance: SidingPerformanceItem[];
    penaltyTrendDaily: Array<{ date: string; label: string; total: number }>;
    powerPlantDispatch: PowerPlantDispatchItem[];
    penaltyBySidingForSidingOverview: PenaltyBySidingPoint[];
    sidingWiseMonthly: SidingWiseMonthlyPoint[];
    sidingRadar: SidingComparisonData;
    dateWiseDispatch: DateWiseDispatchData;
    sidings: SidingOption[];
    sidingOverviewPenaltyPeriod: ExecutiveChartPeriodKey;
    setSidingOverviewPenaltyPeriod: (period: ExecutiveChartPeriodKey) => void;
    filters: DashboardFilters;
}

export function SidingOverview({
    canWidget,
    sidingPerformance,
    penaltyTrendDaily,
    powerPlantDispatch,
}: Props) {
    return (
        <div className="space-y-6">
            {canWidget('dashboard.widgets.siding_overview_performance') ? (
                sidingPerformance.length > 0 ? (
                    <SidingPerformanceSection data={sidingPerformance} />
                ) : (
                    <div className="dashboard-card rounded-xl border-0 p-6">
                        <SectionHeader icon={BarChart3} title="Siding performance" subtitle="Rakes, coal & penalty by siding" />
                        <div className="mt-4 py-8 text-center text-sm text-gray-600">No performance data for selected filters.</div>
                    </div>
                )
            ) : null}
            {canWidget('dashboard.widgets.siding_overview_penalty_trend') ? (
            <div className="dashboard-card rounded-xl border-0 p-6">
                <SectionHeader icon={Calendar} title="Penalty trend" subtitle="Date / month vs penalty amount" />
                {penaltyTrendDaily.length > 0 ? (
                    <ResponsiveContainer width="100%" height={280}>
                        <RechartsAreaChart data={penaltyTrendDaily} margin={{ top: 8, right: 16, left: 8, bottom: 0 }}>
                            <defs>
                                <linearGradient id="penalty-trend-gradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stopColor="#DC2626" stopOpacity={0.4} />
                                    <stop offset="100%" stopColor="#DC2626" stopOpacity={0} />
                                </linearGradient>
                            </defs>
                            <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                            <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                            <YAxis tick={{ fontSize: 11 }} tickFormatter={(v) => formatCurrency(v)} />
                            <Tooltip formatter={(v: number | string | undefined) => formatCurrency(Number(v ?? 0))} />
                            <Area type="monotone" dataKey="total" name="Penalty" stroke="#DC2626" strokeWidth={2} fill="url(#penalty-trend-gradient)" dot={false} activeDot={{ r: 4 }} isAnimationActive />
                        </RechartsAreaChart>
                    </ResponsiveContainer>
                ) : (
                    <div className="mt-4 py-8 text-center text-sm text-gray-600">No penalty data for selected period.</div>
                )}
            </div>
            ) : null}
            {canWidget('dashboard.widgets.siding_overview_power_plant_distribution') ? (
            <div className="dashboard-card rounded-xl border-0 p-6">
                <SectionHeader icon={Factory} title="Power plant dispatch distribution" subtitle="Coal supply by destination" />
                {powerPlantDispatch.length > 0 ? (() => {
                    const totalWeight = powerPlantDispatch.reduce((s, p) => s + p.weight_mt, 0);
                    const donutData = powerPlantDispatch.map((pp) => ({
                        name: pp.name,
                        value: Math.round((pp.weight_mt / Math.max(1, totalWeight)) * 100),
                        weightMt: pp.weight_mt,
                    }));
                    return (
                        <div className="relative">
                            <ResponsiveContainer width="100%" height={280}>
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
                                        {donutData.map((_, i) => (
                                            <Cell key={i} fill={['#22c55e', '#3b82f6', '#f97316', '#eab308', '#a855f7'][i % 5]} />
                                        ))}
                                    </Pie>
                                    <Tooltip formatter={(v: number | undefined, _: unknown, props: { payload?: { weightMt: number } }) => [`${v ?? 0}%`, formatWeight(props.payload?.weightMt ?? 0)]} />
                                    <Legend layout="horizontal" align="center" verticalAlign="bottom" wrapperStyle={{ paddingTop: 16 }} formatter={(value, entry) => `${value} ${Number((entry as { payload?: { value: number } }).payload?.value ?? 0)}%`} />
                                </RechartsPieChart>
                            </ResponsiveContainer>
                            <div className="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col items-center justify-center text-center">
                                <span className="text-xs font-medium text-gray-600">Total</span>
                                <span className="text-lg font-bold tabular-nums text-gray-800">{formatWeight(totalWeight)}</span>
                            </div>
                        </div>
                    );
                })() : (
                    <div className="mt-4 py-8 text-center text-sm text-gray-600">No power plant dispatch data.</div>
                )}
            </div>
            ) : null}
        </div>
    );
}
