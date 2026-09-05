export type LiveWagonStatus =
    | 'empty'
    | 'loading'
    | 'loaded'
    | 'overload'
    | 'unfit';

export type WeightSource = 'loadrite' | 'manual' | 'weighbridge';

export interface WagonCard {
    wagon_id: number;
    wagon_number: string | null;
    wagon_sequence: number;
    wagon_type: string | null;
    cc_mt: number | null;
    loaded_mt: number | null;
    overload_mt: number | null;
    status: LiveWagonStatus;
    status_label: string;
    status_color: string;
    loader_id: number | null;
    last_updated_at: string | null;
    weight_source: WeightSource | null;
    loadrite_override: boolean;
}

export interface SourceCounts {
    loadrite: number;
    manual: number;
    weighbridge: number;
    none: number;
    override: number;
}

export interface LoaderActivity {
    loader_id: number;
    loader_name: string;
    loader_code: string | null;
    status: 'active' | 'idle';
    current_wagon_id: number | null;
    current_wagon_number: string | null;
    last_loaded_mt: number | null;
    last_active_at: string | null;
    wagons_completed: number;
}

export interface TimeStatus {
    anchor: 'placement' | 'loading_start' | 'first_loading_event' | 'unknown';
    anchor_at: string | null;
    elapsed_minutes: number | null;
    allowed_minutes: number;
    remaining_minutes: number | null;
    overrun_minutes: number | null;
}

export interface LoadingProgress {
    loaded: number;
    total: number;
    percent: number;
}

export interface AlertRow {
    id: number;
    type: string;
    title: string | null;
    body: string | null;
    severity: string;
    status: string;
    rake_id: number | null;
    created_at: string | null;
}

export interface RakeKpis {
    total_loaded_mt: number;
    total_overload_mt: number;
    total_underload_mt: number;
    avg_net_mt: number | null;
    placement_time: string | null;
    loading_start_time: string | null;
    loading_end_time: string | null;
    loading_elapsed_minutes: number | null;
}

export interface RakeIdentity {
    id: number;
    rake_number: string | null;
    rake_serial_number: string | null;
    rake_type: string | null;
    wagon_count: number;
    state: string | null;
    loading_date: string | null;
    siding: { id: number; name: string; code: string | null } | null;
}

export type StatusCounts = Record<LiveWagonStatus, number>;

export interface RakeData {
    rake: RakeIdentity;
    kpis: RakeKpis;
    status_counts: StatusCounts;
    source_counts: SourceCounts;
    wagons: WagonCard[];
    loaders: LoaderActivity[];
    time_status: TimeStatus;
    loading_progress: LoadingProgress;
    alerts: AlertRow[];
    last_event_at: string | null;
}

export interface OverviewSiding {
    siding_id: number;
    siding_name: string;
    siding_code: string | null;
    has_active_rake: boolean;
    rake: {
        id: number;
        rake_number: string | null;
        rake_serial_number: string | null;
        wagon_count: number;
        state: string | null;
    } | null;
    kpis: {
        total_loaded_mt: number;
        total_overload_mt: number;
        total_underload_mt: number;
    } | null;
    wagons: WagonCard[];
    status_counts: StatusCounts | [];
    loaders: LoaderActivity[];
    time_status: TimeStatus | null;
    loading_progress: LoadingProgress | null;
    alerts_count: number;
    last_event_at: string | null;
}

export interface OverviewPayload {
    sidings: OverviewSiding[];
    totals: { sidings: number; rakes_active: number; alerts_active: number };
    last_event_at: string | null;
}

export type StaleStatus = 'live' | 'lagging' | 'stale';
