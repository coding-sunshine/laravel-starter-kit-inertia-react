import type { WorkflowSteps } from '@/components/rake-workflow-progress';

export interface SidingOption {
    id: number;
    name: string;
    code: string;
}

export interface SidingStock {
    siding_id: number;
    opening_balance_mt: number;
    closing_balance_mt: number;
    total_rakes: number;
    received_mt: number;
    dispatched_mt: number;
    last_receipt_at: string | null;
    last_dispatch_at: string | null;
}

export interface SidingPerformanceItem {
    name: string;
    rakes: number;
    penalties: number;
    penalty_amount: number;
    penalty_rate: number;
}

export interface SidingWiseMonthlyPoint {
    month: string;
    [sidingName: string]: string | number;
}

export interface SidingComparisonItem {
    name: string;
    rakes_dispatched: number;
    on_time: number;
    vehicles: number;
    penalty_amount: number;
}

export interface SidingComparisonData {
    sidings: SidingComparisonItem[];
}

export interface DateWiseDateEntry {
    date: string;
    [sidingId: string]: string | number;
}

export interface DateWiseDispatchData {
    sidingNames: Record<number, string>;
    dates: DateWiseDateEntry[];
}

export interface RakePerformanceItem {
    id: number;
    siding_id?: number;
    rake_number: string;
    rake_serial_number?: string | null;
    siding: string;
    dispatch_date: string;
    wagon_count: number | null;
    net_weight: number | null;
    over_load: number | null;
    under_load: number | null;
    loading_minutes: number | null;
    predicted_penalty_amount: number;
    predicted_penalty_count: number;
    actual_penalty_amount: number;
    actual_penalty_count: number;
    wagon_overloads?: Array<{
        wagon_number: string;
        over_load_mt: number;
        under_load_mt?: number | null;
        cc_capacity_mt?: number | null;
        net_weight_mt?: number | null;
        loader_id?: number | null;
        loader_name?: string | null;
        loader_operator_name?: string | null;
    }>;
}

export type RakePerformanceSummaryItem = Omit<RakePerformanceItem, 'loading_minutes' | 'wagon_overloads'> & {
    siding_id: number;
};

export interface LoaderInfo {
    id: number;
    name: string;
    siding: string;
}

export interface LoaderMonthlyEntry {
    month: string;
    [loaderId: string]: string | number;
}

export interface LoaderOverloadTrends {
    loaders: LoaderInfo[];
    monthly: LoaderMonthlyEntry[];
}

export interface PowerPlantSidingBreakdown {
    rakes: number;
    weight_mt: number;
}

export interface PowerPlantDispatchItem {
    name: string;
    rakes: number;
    weight_mt: number;
    sidings: Record<string, PowerPlantSidingBreakdown>;
}

export interface DashboardFilters {
    period: string;
    from: string;
    to: string;
    siding_ids: number[];
    power_plant: string | null;
    rake_number: string | null;
    loader_id: number | null;
    loader_operator: string | null;
    underload_threshold: number;
    shift: string | null;
    penalty_type: number | null;
    rake_penalty_scope?: 'all' | 'with_penalties';
    daily_rake_date?: string;
    coal_transport_date?: string;
}

export interface FilterOptions {
    powerPlants: Array<{ value: string; label: string }>;
    loaders: Array<{ id: number; name: string; siding_name: string }>;
    shifts: Array<{ value: string; label: string }>;
    penaltyTypes: Array<{ value: string; label: string }>;
    loaderOperatorsByLoader?: Record<string, string[]>;
}

export interface DashboardKpis {
    rakesDispatchedToday: number;
    coalDispatchedToday: number;
    totalPenaltyThisMonth: number;
    predictedPenaltyRisk: number;
    avgLoadingTimeMinutes: number | null;
    trucksReceivedToday: number;
}

export interface PenaltyTrendPoint {
    date: string;
    label: string;
    total: number;
}

export interface PenaltyByTypePoint {
    name: string;
    value: number;
}

export interface PenaltyBySidingPoint {
    name: string;
    total: number;
}

export interface YesterdayPenaltyRow {
    type_code: string;
    type_name: string;
    amount: number;
}

export interface YesterdayPenaltyRake {
    rake_id: number;
    rake_number: string;
    total_penalty: number;
    penalties: YesterdayPenaltyRow[];
}

export interface YesterdayPredictedPenaltyItem {
    siding_id: number;
    siding_name: string;
    rakes: YesterdayPenaltyRake[];
}

export interface ExecutiveTimelineValue {
    trips: number | null;
    qty: number | null;
}

export interface ExecutiveTimelineSeries {
    dateWise: ExecutiveTimelineValue;
    monthWise: ExecutiveTimelineValue;
    fyWise: ExecutiveTimelineValue;
}

export type ExecutiveChartPeriodKey = 'yesterday' | 'today' | 'week' | 'month' | 'fy';

export interface ExecutiveYesterdayData {
    anchorDate: string;
    fyLabel: string;
    periods: Record<'yesterday' | 'today' | 'week' | 'month' | 'fy', { from: string; to: string }>;
    roadDispatch: {
        totals: Record<'yesterday' | 'today' | 'week' | 'month' | 'fy', { trips: number; qty: number }>;
        bySiding: Array<{
            sidingId: number;
            sidingName: string;
            totals: Record<'yesterday' | 'today' | 'week' | 'month' | 'fy', { trips: number; qty: number }>;
        }>;
    };
    railDispatch: {
        totals: Record<'yesterday' | 'today' | 'week' | 'month' | 'fy', { rakes: number; qty: number }>;
        bySiding: Array<{
            sidingId: number;
            sidingName: string;
            totals: Record<'yesterday' | 'today' | 'week' | 'month' | 'fy', { rakes: number; qty: number }>;
        }>;
    };
    obProduction: Record<'yesterday' | 'today' | 'week' | 'month' | 'fy', { trips: number; qty: number }>;
    coalProduction: Record<'yesterday' | 'today' | 'week' | 'month' | 'fy', { trips: number; qty: number }>;
    customRanges: {
        roadDispatch: {
            from: string;
            to: string;
            totals: { trips: number; qty: number };
            bySiding: Array<{ sidingId: number; sidingName: string; trips: number; qty: number }>;
            summary: { granularity: string; columns: string[]; data: Record<string, { trips: number; qty: number }> };
        };
        railDispatch: {
            from: string;
            to: string;
            totals: { rakes: number; qty: number };
            bySiding: Array<{ sidingId: number; sidingName: string; rakes: number; qty: number }>;
            summary: { granularity: string; columns: string[]; data: Record<string, { rakes: number; qty: number }> };
        };
        obProduction: {
            from: string;
            to: string;
            totals: { trips: number; qty: number };
            summary: { granularity: string; columns: string[]; data: Record<string, { trips: number; qty: number }> };
        };
        coalProduction: {
            from: string;
            to: string;
            totals: { trips: number; qty: number };
            summary: { granularity: string; columns: string[]; data: Record<string, { trips: number; qty: number }> };
        };
    };
    fySummary: {
        rows: Array<{
            fy: string;
            production: { obQty: number; coalQty: number };
            roadDispatch: { trips: number; qty: number };
            railDispatch: { rakes: number; qty: number };
        }>;
    };
    fyCharts: {
        cutoverDate: string;
        rows: Array<{
            fy: string;
            production: { obQty: number; coalQty: number };
            dispatch: { roadQty: number; railQty: number };
        }>;
    };
    penaltyBySidingByPeriod?: Record<'yesterday' | 'today' | 'month' | 'fy', PenaltyBySidingPoint[]>;
    powerPlantDispatchByPeriod?: Record<'yesterday' | 'today' | 'month' | 'fy', PowerPlantDispatchItem[]>;
}

export interface DashboardAlert {
    id: number;
    type: string;
    title: string;
    severity: string;
    rake_id: number | null;
    siding_id: number | null;
    created_at: string;
}

export interface LiveRakeStatusRow {
    rake_number: string;
    rake_serial_number?: string | null;
    siding_name: string;
    state: string;
    workflow_steps?: WorkflowSteps;
    time_elapsed: string;
    loading_date?: string;
    risk: string;
}

export interface DailyRakeDetailsRow {
    sl_no: number;
    siding_name: string;
    year: string;
    day_rakes: number;
    day_qty: number;
    month_rakes: number;
    month_qty: number;
    year_rakes: number;
    year_qty: number;
}

export interface DailyRakeDetailsData {
    date: string;
    rows: DailyRakeDetailsRow[];
    totals: {
        day_rakes: number;
        day_qty: number;
        month_rakes: number;
        month_qty: number;
        year_rakes: number;
        year_qty: number;
    };
}

export interface CoalTransportSidingMetric {
    siding_name: string;
    trips: number;
    qty: number;
}

export interface CoalTransportReportRow {
    sl_no: number;
    shift_label: string;
    siding_metrics: CoalTransportSidingMetric[];
    total_trips: number;
    total_qty: number;
}

export interface CoalTransportReportData {
    date: string;
    sidings: Array<{ id: number; name: string }>;
    rows: CoalTransportReportRow[];
    totals: { siding_metrics: CoalTransportSidingMetric[]; total_trips: number; total_qty: number };
}

export interface TruckReceiptHour {
    hour: string;
    label: string;
    count: number;
}

export interface ShiftWiseVehicleReceiptPoint {
    shift_label: string;
    [sidingName: string]: string | number;
}

export interface StockGaugeSidingItem {
    siding_id: number;
    siding_name: string;
    stock_available_mt: number;
    rake_required_mt: number;
    status: string;
}

export type StockGaugeData = StockGaugeSidingItem[];

export interface PenaltySummary {
    today_rs: number;
    trend_7d: { date: string; rs: number }[];
    preventable_pct: number;
}

export interface RakePipelineCard {
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

export interface ActiveRakePipelineData {
    loading: RakePipelineCard[];
    awaiting_clearance: RakePipelineCard[];
    dispatched: RakePipelineCard[];
}

export interface SidingRiskScoreData {
    siding_id: number;
    siding_name: string;
    siding_code: string;
    score: number;
    trend: string;
    risk_factors: string[];
    calculated_at: string | null;
}

export interface AlertRecord {
    id: number;
    type: string;
    title: string;
    body: string;
    severity: string;
    siding_id: number | null;
    rake_id: number | null;
    created_at: string;
}

export interface OperatorRake {
    rake_id: number;
    rake_number: string;
    siding_name: string;
    state: string;
    loaded: number;
    total: number;
    status: string;
    loading_date: string | null;
}

