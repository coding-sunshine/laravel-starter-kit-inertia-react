import type { LiveWagonStatus } from '@/components/control-room/types';

const STATUS_COLORS: Record<LiveWagonStatus, string> = {
    loaded: '#10B981',
    loading: '#F59E0B',
    overload: '#EF4444',
    unfit: '#3B82F6',
    empty: '#9CA3AF',
};

const STATUS_LABELS: Record<LiveWagonStatus, string> = {
    loaded: 'Loaded',
    loading: 'Loading',
    overload: 'Over Load',
    unfit: 'Unfit / Skip',
    empty: 'Empty / Not Started',
};

const STATUS_ORDER: LiveWagonStatus[] = [
    'loaded',
    'loading',
    'overload',
    'unfit',
    'empty',
];

export interface LegendStripProps {
    counts?: Partial<Record<LiveWagonStatus, number>>;
    compact?: boolean;
}

export function LegendStrip({ counts, compact = false }: LegendStripProps) {
    const dotSize = compact ? 'size-2' : 'size-2.5';
    const labelSize = compact ? 'text-[11px]' : 'text-xs';
    const badgeSize = compact
        ? 'text-[10px] px-1.5 py-0'
        : 'text-[11px] px-1.5 py-0.5';
    const gap = compact ? 'gap-x-3 gap-y-1.5' : 'gap-x-4 gap-y-2';
    const itemGap = compact ? 'gap-1.5' : 'gap-2';

    return (
        <div
            className={`flex flex-wrap items-center ${gap}`}
            role="list"
            aria-label="Wagon status legend"
        >
            {STATUS_ORDER.map((status) => {
                const count = counts?.[status];
                return (
                    <div
                        key={status}
                        role="listitem"
                        className={`flex items-center ${itemGap} text-foreground`}
                    >
                        <span
                            className={`${dotSize} shrink-0 rounded-full`}
                            style={{ backgroundColor: STATUS_COLORS[status] }}
                            aria-hidden="true"
                        />
                        <span className={`${labelSize} font-medium`}>
                            {STATUS_LABELS[status]}
                        </span>
                        {typeof count === 'number' && (
                            <span
                                className={`${badgeSize} rounded-full bg-muted font-semibold text-muted-foreground tabular-nums`}
                            >
                                {count}
                            </span>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

export default LegendStrip;
