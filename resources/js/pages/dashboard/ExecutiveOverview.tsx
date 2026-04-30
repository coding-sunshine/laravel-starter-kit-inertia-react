import { ActiveRakePipeline } from '@/components/dashboard/active-rake-pipeline';
import { AlertFeed } from '@/components/dashboard/alert-feed';
import { DispatchSummary } from '@/components/dashboard/dispatch-summary';
import { OperatorRakeWidget } from '@/components/dashboard/operator-rake-widget';
import { OverloadPatternsWidget } from '@/components/dashboard/overload-patterns-widget';
import { PenaltyExposureStrip } from '@/components/dashboard/penalty-exposure-strip';
import { PenaltyPredictionsWidget } from '@/components/dashboard/penalty-predictions-widget';
import { SidingCoalStock } from '@/components/dashboard/siding-coal-stock';
import { SidingRiskScoreWidget } from '@/components/dashboard/siding-risk-score';
import { SlidingNumber } from '@/components/SlidingNumber';
import { ExecutiveYesterdaySection } from '../dashboard';
import type {
    ExecutiveYesterdayData,
    PenaltyBySidingPoint,
    PowerPlantDispatchItem,
    SidingOption,
    SidingStock,
} from './types';

const MT_PER_RAKE_LOAD = 3500;

const SIDING_ACCENT: Record<string, string> = { Dumka: '#3B82F6', Kurwa: '#10B981', Pakur: '#F59E0B' };

interface PenaltySummary {
    today_rs: number;
    trend_7d: { date: string; rs: number }[];
    preventable_pct: number;
}

interface RakePipelineCard {
    rake_id: number;
    rake_number: string;
    siding_name: string;
    siding_code: string;
    wagon_count: number;
    overloaded_count: number;
    penalty_risk_rs: number;
    state: string;
    loading_date: string | null;
}

interface ActiveRakePipelineData {
    loading: RakePipelineCard[];
    awaiting_clearance: RakePipelineCard[];
    dispatched: RakePipelineCard[];
}

interface SidingRiskScoreData {
    siding_id: number;
    siding_name: string;
    siding_code: string;
    score: number;
    trend: string;
    risk_factors: string[];
    calculated_at: string | null;
}

interface AlertRecord {
    id: number;
    type: string;
    title: string;
    body: string;
    severity: string;
    siding_id: number | null;
    rake_id: number | null;
    created_at: string;
}

interface OperatorRake {
    rake_id: number;
    rake_number: string;
    siding_name: string;
    state: string;
    loaded: number;
    total: number;
    status: string;
    loading_date: string | null;
}

interface Props {
    isExecutive: boolean;
    operatorRake: OperatorRake | null;
    penaltySummary: PenaltySummary | undefined;
    activeRakePipeline: ActiveRakePipelineData | undefined;
    sidingStocksMap: Record<number, SidingStock>;
    riskScores: Record<number, SidingRiskScoreData>;
    alertsData: Record<string, AlertRecord[]>;
    penaltyPredictions: Array<{
        siding_name: string;
        risk_level: 'high' | 'medium' | 'low';
        predicted_amount_min: number;
        predicted_amount_max: number;
        top_recommendation: string | null;
    }>;
    overloadPatterns: Array<{
        siding_name: string;
        patterns: Array<{
            wagon_type: string;
            overload_rate_percent: number;
            overloaded_count: number;
            total_count: number;
        }>;
    }>;
    filteredSidings: SidingOption[];
    sidingStocks: Record<number, SidingStock>;
    canWidget: (name: string) => boolean;
    executiveYesterday: ExecutiveYesterdayData | undefined;
    executiveYesterdayViewMode: 'table' | 'charts';
    penaltyBySiding: PenaltyBySidingPoint[];
    powerPlantDispatch: PowerPlantDispatchItem[];
}

export function ExecutiveOverview({
    isExecutive,
    operatorRake,
    penaltySummary,
    activeRakePipeline,
    sidingStocksMap,
    riskScores,
    alertsData,
    penaltyPredictions,
    overloadPatterns,
    filteredSidings,
    sidingStocks,
    canWidget,
    executiveYesterday,
    executiveYesterdayViewMode,
    penaltyBySiding,
    powerPlantDispatch,
}: Props) {
    return (
        <div className="space-y-6">
            {/* ── AI Insights / Command Center ── */}
            <div className="flex flex-col gap-4">
                {!isExecutive && <OperatorRakeWidget rake={operatorRake} />}

                {isExecutive && (
                    <>
                        {penaltySummary && <PenaltyExposureStrip data={penaltySummary} />}

                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            {activeRakePipeline && (
                                <div className="lg:col-span-2">
                                    <ActiveRakePipeline data={activeRakePipeline} />
                                </div>
                            )}
                            <DispatchSummary stocks={sidingStocksMap} />
                        </div>

                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <SidingCoalStock stocks={sidingStocksMap} />
                            <SidingRiskScoreWidget scores={riskScores} />
                            <AlertFeed alerts={alertsData} />
                        </div>

                        <PenaltyPredictionsWidget predictions={penaltyPredictions} />
                        <OverloadPatternsWidget overloadPatterns={overloadPatterns} />
                    </>
                )}
            </div>

            {/* ── Coal stock strip (Executive-only) ── */}
            {canWidget('dashboard.widgets.global_coal_stock_strip') && filteredSidings.length > 0 && (
                <div className="space-y-1.5">
                    <p className="text-[10px] text-gray-500">Coal stock updates live from the ledger (and real-time events when connected).</p>
                    <div className="flex gap-3 overflow-x-auto pb-0.5 lg:grid lg:grid-cols-3 lg:gap-3 lg:overflow-visible">
                        {filteredSidings.map((s) => {
                            const stock = sidingStocks[s.id];
                            const stockMt = stock?.closing_balance_mt ?? 0;
                            const rakesLoadable = Math.floor(stockMt / MT_PER_RAKE_LOAD);
                            const accent = SIDING_ACCENT[s.name] ?? '#6B7280';
                            return (
                                <div
                                    key={s.id}
                                    className="dashboard-card flex min-w-[230px] flex-1 flex-col rounded-xl border-0 p-3 sm:min-w-0"
                                    style={{ borderTop: `4px solid ${accent}` }}
                                >
                                    <div className="text-xs font-bold uppercase tracking-wide text-gray-500">
                                        {s.name}
                                    </div>
                                    <div className="mt-2 flex items-end justify-between gap-3">
                                        <div>
                                            <p className="text-xl font-bold leading-none tabular-nums text-gray-900">
                                                <SlidingNumber
                                                    value={stockMt}
                                                    format={(v) => v.toLocaleString(undefined, { maximumFractionDigits: 0 })}
                                                />
                                            </p>
                                            <p className="mt-0.5 text-[11px] font-medium text-gray-500">MT available</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-xl font-bold leading-none tabular-nums" style={{ color: accent }}>
                                                {rakesLoadable}
                                            </p>
                                            <p className="mt-0.5 text-[11px] font-medium text-gray-500">rakes loadable</p>
                                        </div>
                                    </div>
                                    <div className="mt-3 space-y-1 rounded-lg bg-gray-50 px-2.5 py-2 text-[10px]">
                                        <div className="flex items-center justify-between">
                                            <span className="font-semibold text-green-700">Last receipt</span>
                                            <span className="tabular-nums text-gray-600">
                                                {stock?.last_receipt_at
                                                    ? new Date(stock.last_receipt_at).toLocaleString()
                                                    : '—'}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="font-semibold text-red-700">Last dispatch</span>
                                            <span className="tabular-nums text-gray-600">
                                                {stock?.last_dispatch_at
                                                    ? new Date(stock.last_dispatch_at).toLocaleString()
                                                    : '—'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* ── Executive charts / tables ── */}
            {executiveYesterday ? (
                <ExecutiveYesterdaySection
                    data={executiveYesterday}
                    viewMode={executiveYesterdayViewMode}
                    penaltyBySiding={penaltyBySiding}
                    powerPlantDispatch={powerPlantDispatch}
                    canWidget={canWidget}
                />
            ) : (
                <div className="dashboard-card rounded-xl border-0 p-6 text-sm text-gray-600">
                    Yesterday data is not available.
                </div>
            )}
        </div>
    );
}
