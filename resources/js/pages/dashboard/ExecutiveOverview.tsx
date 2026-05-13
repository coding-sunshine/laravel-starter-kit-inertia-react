import { DispatchSummary } from '@/components/dashboard/dispatch-summary';
import { OperatorRakeWidget } from '@/components/dashboard/operator-rake-widget';
import { PenaltyPredictionsWidget } from '@/components/dashboard/penalty-predictions-widget';
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

const SIDING_ACCENT: Record<string, string> = {
    Dumka: '#3B82F6',
    Kurwa: '#10B981',
    Pakur: '#F59E0B',
};

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
    penaltyPredictions: Array<{
        siding_name: string;
        risk_level: 'high' | 'medium' | 'low';
        predicted_amount_min: number;
        predicted_amount_max: number;
        top_recommendation: string | null;
    }>;
    filteredSidings: SidingOption[];
    sidingStocks: Record<number, SidingStock>;
    canWidget: (name: string) => boolean;
    executiveYesterday: ExecutiveYesterdayData | undefined;
    executiveYesterdayViewMode: 'table' | 'charts';
    onExecutiveYesterdayViewModeChange?: (mode: 'table' | 'charts') => void;
    showExecutiveYesterdayViewToggle?: boolean;
    penaltyBySiding: PenaltyBySidingPoint[];
    powerPlantDispatch: PowerPlantDispatchItem[];
}

export function ExecutiveOverview({
    isExecutive,
    operatorRake,
    penaltyPredictions,
    filteredSidings,
    sidingStocks,
    canWidget,
    executiveYesterday,
    executiveYesterdayViewMode,
    onExecutiveYesterdayViewModeChange,
    showExecutiveYesterdayViewToggle = false,
    penaltyBySiding,
    powerPlantDispatch,
}: Props) {
    return (
        <div className="space-y-6">
            {/* ── AI Insights / Command Center ── */}
            <div className="flex flex-col gap-4">
                {!isExecutive && <OperatorRakeWidget rake={operatorRake} />}

                {isExecutive && (
                    <PenaltyPredictionsWidget
                        predictions={penaltyPredictions}
                    />
                )}
            </div>

            {/* ── Coal stock strip (Executive-only) ── */}
            {canWidget('dashboard.widgets.global_coal_stock_strip') &&
                filteredSidings.length > 0 && (
                    <div className="space-y-1.5">
                        <p className="text-[10px] text-gray-500">
                            Coal stock updates live from the ledger (and
                            real-time events when connected).
                        </p>
                        <div className="flex gap-3 overflow-x-auto pb-0.5 lg:grid lg:grid-cols-3 lg:gap-3 lg:overflow-visible">
                            {filteredSidings.map((s) => {
                                const stock = sidingStocks[s.id];
                                const stockMt = stock?.closing_balance_mt ?? 0;
                                const rakesLoadable = Math.floor(
                                    stockMt / MT_PER_RAKE_LOAD,
                                );
                                const eDemandRaised =
                                    stock?.e_demand_raised ?? 0;
                                const accent =
                                    SIDING_ACCENT[s.name] ?? '#6B7280';
                                return (
                                    <div
                                        key={s.id}
                                        className="dashboard-card flex min-w-[230px] flex-1 flex-col rounded-xl border-0 p-3 sm:min-w-0"
                                        style={{
                                            borderTop: `4px solid ${accent}`,
                                        }}
                                    >
                                        <div className="text-xs font-semibold text-muted-foreground">
                                            {s.name}
                                        </div>
                                        <div className="mt-2 flex items-start justify-between gap-3">
                                            <div className="shrink-0">
                                                <p className="text-xl leading-none font-bold text-gray-900 tabular-nums">
                                                    <SlidingNumber
                                                        value={stockMt}
                                                        format={(v) =>
                                                            v.toLocaleString(
                                                                undefined,
                                                                {
                                                                    maximumFractionDigits: 0,
                                                                },
                                                            )
                                                        }
                                                    />
                                                </p>
                                                <p className="mt-0.5 text-[11px] font-medium text-gray-500">
                                                    MT available
                                                </p>
                                            </div>
                                            <div className="grid shrink-0 grid-cols-2 gap-x-5 gap-y-0.5 text-right">
                                                <p
                                                    className="text-xl leading-none font-bold tabular-nums"
                                                    style={{ color: accent }}
                                                >
                                                    <SlidingNumber
                                                        value={eDemandRaised}
                                                    />
                                                </p>
                                                <p
                                                    className="text-xl leading-none font-bold tabular-nums"
                                                    style={{ color: accent }}
                                                >
                                                    <SlidingNumber
                                                        value={rakesLoadable}
                                                    />
                                                </p>
                                                <p className="text-[11px] leading-none font-medium whitespace-nowrap text-gray-500">
                                                    E-Demand Raised
                                                </p>
                                                <p className="text-[11px] leading-none font-medium whitespace-nowrap text-gray-500">
                                                    Rakes Loadable
                                                </p>
                                            </div>
                                        </div>
                                        <div className="mt-3 space-y-1 rounded-lg bg-gray-50 px-2.5 py-2 text-[10px]">
                                            <div className="flex items-center justify-between">
                                                <span className="font-semibold text-green-700">
                                                    Last receipt
                                                </span>
                                                <span className="text-gray-600 tabular-nums">
                                                    {stock?.last_receipt_at
                                                        ? new Date(
                                                              stock.last_receipt_at,
                                                          ).toLocaleString()
                                                        : '—'}
                                                </span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <span className="font-semibold text-red-700">
                                                    Last dispatch
                                                </span>
                                                <span className="text-gray-600 tabular-nums">
                                                    {stock?.last_dispatch_at
                                                        ? new Date(
                                                              stock.last_dispatch_at,
                                                          ).toLocaleString()
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

            {isExecutive && !executiveYesterday && (
                <DispatchSummary stocks={sidingStocks} />
            )}

            {/* ── Executive charts / tables ── */}
            {executiveYesterday ? (
                <ExecutiveYesterdaySection
                    data={executiveYesterday}
                    viewMode={executiveYesterdayViewMode}
                    onViewModeChange={onExecutiveYesterdayViewModeChange}
                    showViewToggle={showExecutiveYesterdayViewToggle}
                    penaltyBySiding={penaltyBySiding}
                    powerPlantDispatch={powerPlantDispatch}
                    sidingStocks={sidingStocks}
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
