export type RakeTimelineKey =
    | 'placement'
    | 'loading_start'
    | 'loading_end'
    | 'weighed'
    | 'drawn'
    | 'rr';

export interface RakeTimelineInput {
    placement_time?: string | null;
    loading_start_time?: string | null;
    loading_end_time?: string | null;
    weighment_end_time?: string | null;
    drawn_out?: string | null;
    rr_actual_date?: string | null;
}

export interface RakeTimelineStep {
    key: RakeTimelineKey;
    label: string;
    timestamp: string | null;
    state: 'done' | 'pending' | 'skipped';
}

export interface RakeTimelineChipProps {
    rake: RakeTimelineInput;
    size?: 'compact' | 'default' | 'detailed';
    className?: string;
}
