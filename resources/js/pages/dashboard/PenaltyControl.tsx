import {
    DashboardPenaltyBySidingChart,
    formatCurrency,
    SectionHeader,
    type ExecutiveChartPeriodKey,
} from '../dashboard';
import type {
    PenaltyByTypePoint,
    PenaltyBySidingPoint,
    SidingOption,
    YesterdayPredictedPenaltyItem,
} from './types';
import { AlertTriangle, BarChart3, Calendar, Check } from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    Bar,
    BarChart as RechartsBarChart,
    CartesianGrid,
    Cell,
    LabelList,
    Legend,
    Pie,
    PieChart as RechartsPieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { index as rakesIndex } from '@/routes/rakes';

interface ExecutiveYesterdayForPenalty {
    penaltyBySidingByPeriod?: Record<'yesterday' | 'today' | 'month' | 'fy', PenaltyBySidingPoint[]>;
}

interface Props {
    canWidget: (name: string) => boolean;
    penaltyByType: PenaltyByTypePoint[];
    penaltyBySiding: PenaltyBySidingPoint[];
    yesterdayPredictedPenalties: YesterdayPredictedPenaltyItem[];
    predictedVsActualPenalty: {
        predicted: number;
        actual: number;
        bySiding?: Array<{ name: string; predicted: number; actual: number }>;
    };
    filteredSidings: SidingOption[];
    executiveYesterday?: ExecutiveYesterdayForPenalty;
}

export function PenaltyControl({
    canWidget,
    penaltyByType,
    penaltyBySiding,
    yesterdayPredictedPenalties,
    predictedVsActualPenalty,
    filteredSidings,
    executiveYesterday,
}: Props) {
    const [sidingOverviewPenaltyPeriod, setSidingOverviewPenaltyPeriod] = useState<ExecutiveChartPeriodKey>('yesterday');

    const penaltyBySidingForSidingOverview = useMemo(() => {
        const slices = executiveYesterday?.penaltyBySidingByPeriod;
        if (slices) {
            return slices[sidingOverviewPenaltyPeriod] ?? [];
        }
        return penaltyBySiding;
    }, [executiveYesterday?.penaltyBySidingByPeriod, penaltyBySiding, sidingOverviewPenaltyPeriod]);

    return (
        <div className="space-y-6">
            {/* Penalty type distribution (left) + Yesterday predicted penalties by siding (right) */}
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {canWidget('dashboard.widgets.penalty_control_type_distribution') ? (
                <div className="dashboard-card min-w-0 rounded-xl border-0 p-6">
                    <SectionHeader icon={AlertTriangle} title="Penalty type distribution" subtitle="Overloading, demurrage, wharfage, etc." />
                    {penaltyByType.length > 0 ? (() => {
                        const typeColors: Record<string, string> = { Demurrage: '#DC2626', Overloading: '#F59E0B', Wharfage: '#8B5CF6' };
                        const totalType = penaltyByType.reduce((s, p) => s + p.value, 0);
                        const donutData = penaltyByType.map((p) => ({ ...p, pct: totalType > 0 ? ((p.value / totalType) * 100).toFixed(1) : '0' }));
                        const DonutTooltipContent = ({ active, payload }: { active?: boolean; payload?: Array<{ name: string; value: number; payload?: { pct: string } }> }) => {
                            if (!active || !payload?.length) return null;
                            const p = payload[0];
                            return (
                                <div className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm shadow-lg">
                                    <span className="font-medium text-gray-800">{p.name}</span>
                                    <span className="ml-2 tabular-nums text-gray-600">{formatCurrency(p.value)} ({p.payload?.pct ?? '0'}%)</span>
                                </div>
                            );
                        };
                        return (
                            <div className="mt-4 flex flex-col gap-4">
                                <div className="relative flex justify-center">
                                    <ResponsiveContainer width="100%" height={260}>
                                        <RechartsPieChart>
                                            <Pie
                                                data={donutData}
                                                dataKey="value"
                                                nameKey="name"
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={60}
                                                outerRadius={90}
                                                paddingAngle={2}
                                                strokeWidth={0}
                                                activeShape={{ scale: 1.05 }}
                                            >
                                                {donutData.map((entry, i) => (
                                                    <Cell key={i} fill={typeColors[entry.name] ?? ['#64748b', '#94a3b8'][i % 2]} />
                                                ))}
                                            </Pie>
                                            <Tooltip
                                                content={<DonutTooltipContent />}
                                                wrapperStyle={{ outline: 'none', transform: 'translate(90px, -50%)' }}
                                            />
                                        </RechartsPieChart>
                                    </ResponsiveContainer>
                                    <div className="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col items-center pointer-events-none">
                                        <span className="text-xs text-gray-600">Total</span>
                                        <span className="text-lg font-bold tabular-nums text-gray-800">{formatCurrency(totalType)}</span>
                                    </div>
                                </div>
                                <div className="flex flex-wrap justify-center gap-x-6 gap-y-2">
                                    {donutData.map((entry, i) => (
                                        <div key={entry.name} className="flex items-center gap-2 text-sm">
                                            <span className="size-2.5 rounded-full shrink-0" style={{ backgroundColor: typeColors[entry.name] ?? '#64748b' }} />
                                            <span className="text-gray-700">{entry.name}</span>
                                            <span className="tabular-nums font-medium text-gray-800">{formatCurrency(entry.value)} ({entry.pct}%)</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        );
                    })() : (
                        <div className="mt-4 py-8 text-center text-sm text-gray-600">No penalty type data.</div>
                    )}
                </div>
                ) : null}
                {canWidget('dashboard.widgets.penalty_control_yesterday_predicted') ? (
                <div className="dashboard-card min-w-0 rounded-xl border-0 p-5" style={{ padding: '1rem' }}>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <SectionHeader icon={Calendar} title="Yesterday predicted penalties" subtitle="Siding-wise total (all sidings listed; ₹0 when no data)" />
                        <Button variant="outline" size="sm" className="rounded-lg" asChild>
                            <Link href={rakesIndex().url} data-pan="dashboard-yesterday-penalties-view-all">View all rakes</Link>
                        </Button>
                    </div>
                    {(() => {
                        const seen = new Set<string>();
                        const sidingOrder: string[] = [];
                        for (const s of filteredSidings) {
                            if (!seen.has(s.name)) {
                                seen.add(s.name);
                                sidingOrder.push(s.name);
                            }
                        }
                        for (const block of yesterdayPredictedPenalties) {
                            if (!seen.has(block.siding_name)) {
                                seen.add(block.siding_name);
                                sidingOrder.push(block.siding_name);
                            }
                        }

                        const bySiding = new Map<string, { rakesWithPenalty: number; totalPenalty: number }>();
                        for (const s of yesterdayPredictedPenalties) {
                            const totalPenalty = s.rakes.reduce((sum, r) => sum + (r.total_penalty ?? 0), 0);
                            bySiding.set(s.siding_name, { rakesWithPenalty: s.rakes.length, totalPenalty });
                        }

                        const rows = sidingOrder.map((name) => {
                            const hit = bySiding.get(name);
                            return {
                                siding_name: name,
                                rakes_with_penalty: hit?.rakesWithPenalty ?? 0,
                                total_penalty: hit?.totalPenalty ?? 0,
                            };
                        });

                        const totalRakesWithPenalty = rows.reduce((sum, r) => sum + r.rakes_with_penalty, 0);
                        const grandTotal = rows.reduce((sum, r) => sum + r.total_penalty, 0);
                        const anyPenalty = totalRakesWithPenalty > 0 || grandTotal > 0;
                        const chartData = [...rows].sort((a, b) => b.total_penalty - a.total_penalty);
                        const chartHeight = Math.min(420, Math.max(220, chartData.length * 36));

                        if (rows.length === 0) {
                            return (
                                <div className="mt-4 py-8 text-center text-sm text-gray-600">
                                    No sidings in scope for this dashboard.
                                </div>
                            );
                        }

                        return (
                            <div className="mt-4 space-y-4">
                                <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-600">
                                    <span>
                                        {totalRakesWithPenalty} rake{totalRakesWithPenalty === 1 ? '' : 's'} with predicted penalties
                                    </span>
                                    <span className="font-medium text-gray-600">
                                        Total: {formatCurrency(grandTotal)}
                                    </span>
                                </div>

                                <div className="rounded-lg border bg-white p-3">
                                    {anyPenalty ? (
                                        <ResponsiveContainer width="100%" height={chartHeight}>
                                            <RechartsBarChart
                                                data={chartData}
                                                layout="vertical"
                                                margin={{ top: 8, right: 24, bottom: 8, left: 8 }}
                                            >
                                                <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.25} horizontal />
                                                <XAxis type="number" tick={{ fontSize: 11 }} tickFormatter={(v) => formatCurrency(v)} />
                                                <YAxis
                                                    type="category"
                                                    dataKey="siding_name"
                                                    width={100}
                                                    tick={{ fontSize: 11 }}
                                                />
                                                <Tooltip formatter={(v: number | string | undefined) => formatCurrency(Number(v ?? 0))} />
                                                <Bar dataKey="total_penalty" name="Predicted (₹)" fill="#DC2626" radius={[0, 4, 4, 0]} barSize={18} isAnimationActive>
                                                    <LabelList dataKey="total_penalty" position="right" formatter={(v: unknown) => formatCurrency(Number(v ?? 0))} />
                                                </Bar>
                                            </RechartsBarChart>
                                        </ResponsiveContainer>
                                    ) : (
                                        <div className="py-8 text-center text-sm text-gray-600">
                                            No predicted penalties for yesterday&apos;s loading date.
                                        </div>
                                    )}
                                </div>

                                <div className="grid grid-cols-1 gap-2">
                                    {rows.map((r) => (
                                        <div key={r.siding_name} className="flex items-center justify-between rounded-lg border bg-white px-3 py-2 text-sm">
                                            <span className="font-medium text-gray-800">{r.siding_name}</span>
                                            <span className="flex items-center gap-3 text-xs text-gray-600">
                                                <span className="tabular-nums">
                                                    {r.rakes_with_penalty} rake{r.rakes_with_penalty === 1 ? '' : 's'}
                                                </span>
                                                <span className={`tabular-nums font-semibold ${r.total_penalty > 0 ? 'text-red-700' : 'text-gray-600'}`}>
                                                    {formatCurrency(r.total_penalty)}
                                                </span>
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        );
                    })()}
                </div>
                ) : null}
            </div>
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {canWidget('dashboard.widgets.penalty_control_penalty_by_siding') ? (
                <DashboardPenaltyBySidingChart
                    data={penaltyBySidingForSidingOverview}
                    {...(executiveYesterday?.penaltyBySidingByPeriod
                        ? {
                              period: sidingOverviewPenaltyPeriod,
                              onPeriodChange: setSidingOverviewPenaltyPeriod,
                          }
                        : {})}
                />
                ) : null}
                <div className="grid grid-cols-1 gap-6">
                {canWidget('dashboard.widgets.penalty_control_applied_vs_rr') ? (
                <div className="dashboard-card min-w-0 rounded-xl border-0 p-6">
                    <SectionHeader icon={BarChart3} title="Applied vs RR penalty" subtitle="Applied penalties vs railway receipt snapshot, by siding" />
                    <div className="mt-3 flex flex-wrap items-center justify-center gap-4 rounded-lg border border-gray-200 bg-gray-50/80 px-4 py-2 text-sm">
                        <span className="flex items-center gap-2">
                            <span className="font-medium text-gray-600">Total applied:</span>
                            <span className="tabular-nums font-bold text-blue-700">{formatCurrency(predictedVsActualPenalty.predicted)}</span>
                        </span>
                        <span className="flex items-center gap-2">
                            <span className="font-medium text-gray-600">Total RR snapshot:</span>
                            <span className="tabular-nums font-bold text-red-700">{formatCurrency(predictedVsActualPenalty.actual)}</span>
                        </span>
                    </div>
                    {(predictedVsActualPenalty.bySiding?.length ?? 0) > 0 ? (
                        <div className="mt-4">
                            <ResponsiveContainer width="100%" height={320}>
                                <RechartsBarChart
                                    data={predictedVsActualPenalty.bySiding}
                                    margin={{ top: 8, right: 16, bottom: 80, left: 16 }}
                                >
                                    <CartesianGrid strokeDasharray="3 3" strokeOpacity={0.3} />
                                    <XAxis dataKey="name" type="category" tick={{ fontSize: 11 }} interval={0} height={70} />
                                    <YAxis type="number" tickFormatter={(v: number) => formatCurrency(v)} width={72} tick={{ fontSize: 11 }} label={{ value: 'Penalty amount (₹)', angle: -90, position: 'insideLeft', style: { fontSize: 11 } }} />
                                    <Tooltip formatter={(v: number | string | undefined) => formatCurrency(Number(v ?? 0))} />
                                    <Legend verticalAlign="bottom" height={28} />
                                    <Bar dataKey="predicted" name="Applied penalties" fill="#3B82F6" radius={[4, 4, 0, 0]} barSize={28} isAnimationActive />
                                    <Bar dataKey="actual" name="RR snapshot" fill="#DC2626" radius={[4, 4, 0, 0]} barSize={28} isAnimationActive />
                                </RechartsBarChart>
                            </ResponsiveContainer>
                        </div>
                    ) : (
                        <div className="mt-4 py-8 text-center text-sm text-gray-600">No predicted or actual penalty data for the selected period.</div>
                    )}
                    {(() => {
                        const pred = predictedVsActualPenalty.predicted || 1;
                        const pctDiff = ((predictedVsActualPenalty.actual - pred) / pred) * 100;
                        const over = predictedVsActualPenalty.actual > pred;
                        return (
                            <div className={`mt-4 flex items-center gap-2 rounded-xl border-0 p-4 ${over ? 'bg-amber-50' : 'bg-green-50'}`} style={{ boxShadow: '0 1px 3px rgba(0,0,0,0.06)' }}>
                                {over ? <AlertTriangle className="size-5 shrink-0 text-amber-600" /> : <Check className="size-5 shrink-0 text-green-600" />}
                                <p className={`text-sm font-medium ${over ? 'text-amber-800' : 'text-green-800'}`}>
                                    Penalty is {pctDiff >= 0 ? `${pctDiff.toFixed(1)}% above` : `${Math.abs(pctDiff).toFixed(1)}% below`} predicted.
                                </p>
                            </div>
                        );
                    })()}
                </div>
                ) : null}
                </div>
            </div>
        </div>
    );
}
