export type Severity = 'critical' | 'high' | 'medium' | 'low';
export type AiStatus = 'ok' | 'failed' | 'pending';

export interface ActionCard {
    severity: Severity;
    title: string;
    why: string;
    rs_at_stake: number;
    deep_link: string;
    deadline: string | null;
    prevention?: string | null;
}

export interface Operator {
    name: string;
    wagons: number;
    accuracy_pct: number;
    rs_caused: number;
}

export interface TrendMetric {
    current: number;
    prior: number;
    delta_pct: number;
    spark: number[];
}

export interface LiveExposureBreakdownItem {
    rake_id: number;
    rake_number: string;
    overload_mt: number;
    rs: number;
}

export interface LiveExposureData {
    total_rs: number;
    breakdown: LiveExposureBreakdownItem[];
}

export interface OperatorScoreboardData {
    top: Operator[];
    bottom: Operator[];
}

export interface PendingQueueData {
    overrides_pending: number;
    overrides_oldest_minutes: number | null;
    disputes_ready: number;
    disputes_estimated_rs: number;
}

export interface TrendStripData {
    penalty_rs: TrendMetric;
    throughput_mt: TrendMetric;
    on_time_dispatch_pct: TrendMetric;
}

export interface SidingInfo {
    id: number;
    name: string;
    code: string;
}

export interface ManagerBriefPageProps {
    actions: ActionCard[];
    generated_at: string;
    ai_status: AiStatus;
    model_used: string | null;
    live_exposure: LiveExposureData | null;
    operator_scoreboard: OperatorScoreboardData | null;
    pending_queue: PendingQueueData | null;
    trend_strip: TrendStripData | null;
    widget_errors: string[];
    can_refresh: boolean;
    siding: SidingInfo;
}
