import { motion, useReducedMotion } from 'framer-motion';

import type { StaleStatus } from '@/components/control-room/types';

export interface StaleIndicatorProps {
    status: StaleStatus;
    secondsSince: number | null;
}

interface StatusStyle {
    label: string;
    dotClass: string;
}

const STATUS_STYLES: Record<StaleStatus, StatusStyle> = {
    live: {
        label: 'Live',
        dotClass: 'bg-emerald-500',
    },
    lagging: {
        label: 'Lagging',
        dotClass: 'bg-amber-500',
    },
    stale: {
        label: 'Stale',
        dotClass: 'bg-red-500',
    },
};

function formatSecondsSince(seconds: number | null): string {
    if (seconds === null) {
        return 'Last update — ago';
    }
    const safe = Math.max(0, Math.floor(seconds));
    if (safe > 60) {
        const m = Math.floor(safe / 60);
        const s = safe % 60;
        return `Last update ${m}m ${s}s ago`;
    }
    return `Last update ${safe}s ago`;
}

export function StaleIndicator({ status, secondsSince }: StaleIndicatorProps) {
    const reduceMotion = useReducedMotion();
    const style = STATUS_STYLES[status];

    const isLive = status === 'live';

    return (
        <div
            className="inline-flex items-center gap-2 rounded-full border border-border bg-muted px-3 py-1 text-xs"
            role="status"
            aria-live="polite"
        >
            <span
                className="relative inline-flex size-2 items-center justify-center"
                aria-hidden="true"
            >
                <motion.span
                    className={`block size-2 rounded-full ${style.dotClass}`}
                    initial={false}
                    animate={
                        isLive && !reduceMotion
                            ? { scale: [1, 1.3, 1], opacity: [1, 0.5, 1] }
                            : { scale: 1, opacity: 1 }
                    }
                    transition={
                        isLive && !reduceMotion
                            ? {
                                  duration: 1.6,
                                  repeat: Infinity,
                                  ease: 'easeInOut',
                              }
                            : { duration: 0 }
                    }
                />
            </span>
            <span className="font-medium text-foreground">{style.label}</span>
            <span className="text-muted-foreground tabular-nums">
                {formatSecondsSince(secondsSince)}
            </span>
        </div>
    );
}

export default StaleIndicator;
