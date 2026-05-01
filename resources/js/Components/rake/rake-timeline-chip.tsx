import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { CheckCircle2, Circle, MinusCircle } from 'lucide-react';
import { formatDuration } from './format-duration';
import type {
    RakeTimelineChipProps,
    RakeTimelineKey,
    RakeTimelineStep,
} from './rake-timeline-chip.types';

const STEP_DEFINITIONS: {
    key: RakeTimelineKey;
    label: string;
    field: keyof RakeTimelineChipProps['rake'];
}[] = [
    { key: 'placement', label: 'Placement', field: 'placement_time' },
    {
        key: 'loading_start',
        label: 'Loading start',
        field: 'loading_start_time',
    },
    { key: 'loading_end', label: 'Loading end', field: 'loading_end_time' },
    { key: 'weighed', label: 'Weighed', field: 'weighment_end_time' },
    { key: 'drawn', label: 'Drawn out', field: 'drawn_out' },
    { key: 'rr', label: 'RR issued', field: 'rr_actual_date' },
];

function buildSteps(rake: RakeTimelineChipProps['rake']): RakeTimelineStep[] {
    let lastDoneIndex = -1;
    const raw = STEP_DEFINITIONS.map((def, idx) => {
        const ts = (rake[def.field] as string | null | undefined) ?? null;
        if (ts) {
            lastDoneIndex = idx;
        }
        return { def, ts };
    });

    return raw.map(({ def, ts }, idx) => {
        let state: RakeTimelineStep['state'];
        if (ts) {
            state = 'done';
        } else if (idx < lastDoneIndex) {
            state = 'skipped';
        } else {
            state = 'pending';
        }
        return { key: def.key, label: def.label, timestamp: ts, state };
    });
}

function formatTimestamp(iso: string | null): string {
    if (!iso) return '—';
    const d = new Date(iso);
    return new Intl.DateTimeFormat('en-IN', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(d);
}

const sizeMap = {
    compact: { dot: 'h-2 w-2', gap: 'gap-1.5', stroke: 'h-2 w-2' },
    default: { dot: 'h-2.5 w-2.5', gap: 'gap-2', stroke: 'h-2.5 w-2.5' },
    detailed: { dot: 'h-3 w-3', gap: 'gap-2.5', stroke: 'h-3 w-3' },
} as const;

export function RakeTimelineChip({
    rake,
    size = 'default',
    className,
}: RakeTimelineChipProps) {
    const steps = buildSteps(rake);
    const sizes = sizeMap[size];

    return (
        <TooltipProvider delayDuration={150}>
            <ol
                role="list"
                aria-label="Rake lifecycle"
                className={cn('flex items-center', sizes.gap, className)}
            >
                {steps.map((step, idx) => {
                    const prev = idx > 0 ? steps[idx - 1] : null;
                    const segmentDuration =
                        prev && prev.timestamp && step.timestamp
                            ? formatDuration(prev.timestamp, step.timestamp)
                            : null;

                    return (
                        <li key={step.key} className="flex items-center">
                            {idx > 0 && (
                                <span
                                    aria-hidden
                                    className={cn(
                                        'mx-1 h-px w-3 sm:w-5',
                                        step.state === 'done'
                                            ? 'bg-emerald-500'
                                            : 'bg-slate-300 dark:bg-slate-700',
                                    )}
                                />
                            )}
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <button
                                        type="button"
                                        className={cn(
                                            'inline-flex items-center justify-center rounded-full transition-colors',
                                            'focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600',
                                            step.state === 'done' &&
                                                'text-emerald-600',
                                            step.state === 'pending' &&
                                                'text-slate-400',
                                            step.state === 'skipped' &&
                                                'text-amber-500',
                                        )}
                                        aria-label={`${step.label}: ${step.state}`}
                                    >
                                        {step.state === 'done' && (
                                            <CheckCircle2
                                                className={sizes.stroke}
                                            />
                                        )}
                                        {step.state === 'pending' && (
                                            <Circle className={sizes.stroke} />
                                        )}
                                        {step.state === 'skipped' && (
                                            <MinusCircle
                                                className={sizes.stroke}
                                            />
                                        )}
                                    </button>
                                </TooltipTrigger>
                                <TooltipContent
                                    side="bottom"
                                    className="text-xs"
                                >
                                    <div className="font-medium">
                                        {step.label}
                                    </div>
                                    <div className="text-slate-500">
                                        {formatTimestamp(step.timestamp)}
                                    </div>
                                    {segmentDuration && (
                                        <div className="text-slate-500">
                                            + {segmentDuration} from previous
                                        </div>
                                    )}
                                </TooltipContent>
                            </Tooltip>
                            {size === 'detailed' && (
                                <span className="ml-1 text-[10px] tracking-wide text-slate-500 uppercase">
                                    {step.label}
                                </span>
                            )}
                        </li>
                    );
                })}
            </ol>
        </TooltipProvider>
    );
}
