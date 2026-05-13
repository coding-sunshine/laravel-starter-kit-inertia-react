import { useEffect, useMemo, useState } from 'react';

import type { TimeStatus } from '@/components/control-room/types';

export interface TimeStatusDonutProps {
    timeStatus: TimeStatus;
    size?: number;
    strokeWidth?: number;
}

const COLOR_SAFE = '#10B981'; // emerald-500
const COLOR_WARN = '#F59E0B'; // amber-500
const COLOR_HIGH = '#F97316'; // orange-500
const COLOR_OVER = '#EF4444'; // red-500

function pickColor(usage: number): string {
    if (usage > 1.0) {
        return COLOR_OVER;
    }
    if (usage >= 0.75) {
        return COLOR_HIGH;
    }
    if (usage >= 0.5) {
        return COLOR_WARN;
    }
    return COLOR_SAFE;
}

function formatHHMMSS(totalSeconds: number): string {
    const safe = Math.max(0, Math.floor(totalSeconds));
    const h = Math.floor(safe / 3600);
    const m = Math.floor((safe % 3600) / 60);
    const s = safe % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

function formatHHMM(totalMinutes: number): string {
    const safe = Math.max(0, Math.floor(totalMinutes));
    const h = Math.floor(safe / 60);
    const m = safe % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}

function formatOverrun(minutes: number): string {
    const safe = Math.max(0, Math.floor(minutes));
    const h = Math.floor(safe / 60);
    const m = safe % 60;
    if (h > 0) {
        return `+${h}h ${m}m overrun`;
    }
    return `+${m}m overrun`;
}

export function TimeStatusDonut({
    timeStatus,
    size = 160,
    strokeWidth = 16,
}: TimeStatusDonutProps) {
    const radius = (size - strokeWidth) / 2;
    const center = size / 2;
    const circumference = 2 * Math.PI * radius;

    // Live tick: re-render every second when anchor_at is present.
    const [now, setNow] = useState<number>(() => Date.now());
    const anchorMs = useMemo(
        () =>
            timeStatus.anchor_at
                ? new Date(timeStatus.anchor_at).getTime()
                : null,
        [timeStatus.anchor_at],
    );

    useEffect(() => {
        if (anchorMs === null) {
            return;
        }
        const id = window.setInterval(() => setNow(Date.now()), 1000);
        return () => window.clearInterval(id);
    }, [anchorMs]);

    // Compute live elapsed seconds from anchor_at, falling back to elapsed_minutes.
    const elapsedSeconds = useMemo(() => {
        if (anchorMs !== null) {
            return Math.max(0, Math.floor((now - anchorMs) / 1000));
        }
        if (timeStatus.elapsed_minutes !== null) {
            return Math.max(0, Math.floor(timeStatus.elapsed_minutes * 60));
        }
        return null;
    }, [anchorMs, now, timeStatus.elapsed_minutes]);

    const allowedMinutes = Math.max(0, timeStatus.allowed_minutes);
    const allowedSeconds = allowedMinutes * 60;

    const elapsedMinutes = elapsedSeconds !== null ? elapsedSeconds / 60 : 0;
    const usageRaw = allowedMinutes > 0 ? elapsedMinutes / allowedMinutes : 0;
    const usage = Math.max(0, Math.min(1.5, usageRaw));

    const arcColor = pickColor(usage);
    const isOverrun = (timeStatus.overrun_minutes ?? 0) > 0 || usage > 1.0;

    // The donut visually overflows past 12 o'clock when usage > 1: we render
    // a primary arc up to min(usage, 1) and a second "overflow" arc on top
    // representing the portion beyond 100%.
    const primaryLength = Math.min(usage, 1);
    const overflowLength = Math.max(0, Math.min(usage - 1, 0.5));

    const remainingMinutes = timeStatus.remaining_minutes;
    const noAnchor = anchorMs === null;
    // "Loading complete" only when we actually have an anchor AND remaining is
    // null AND not in overrun. Without an anchor the timer never started — say so.
    const showCompleteLabel =
        !noAnchor && remainingMinutes === null && !isOverrun;

    const timerLabel =
        elapsedSeconds === null ? '—:—:—' : formatHHMMSS(elapsedSeconds);
    const remainingSecondsLive =
        anchorMs !== null
            ? Math.max(0, allowedSeconds - (elapsedSeconds ?? 0))
            : remainingMinutes !== null
              ? Math.max(0, Math.floor(remainingMinutes * 60))
              : null;

    const subRemaining = (() => {
        if (noAnchor) {
            return 'Awaiting placement';
        }
        if (isOverrun) {
            const overrunMin =
                timeStatus.overrun_minutes ??
                Math.max(0, Math.floor(elapsedMinutes - allowedMinutes));
            return formatOverrun(overrunMin);
        }
        if (showCompleteLabel) {
            return 'Loading complete';
        }
        if (remainingSecondsLive === null) {
            return '— remaining';
        }
        const mins = Math.ceil(remainingSecondsLive / 60);
        return `${mins} min remaining`;
    })();

    // No framer-motion: every 1s tick re-renders with a new primaryLength, and
    // motion.circle would restart its 0.8s tween every second causing visible
    // jitter. Just render the arc at its current offset; the per-second update
    // is a smooth visual progression on its own.
    return (
        <div
            className="relative inline-flex shrink-0 items-center justify-center"
            style={{ width: size, height: size }}
            aria-label={`Time status: ${timerLabel} elapsed, ${subRemaining}`}
            role="img"
        >
            <svg
                width={size}
                height={size}
                viewBox={`0 0 ${size} ${size}`}
                className="-rotate-90"
                aria-hidden="true"
            >
                {/* Background ring */}
                <circle
                    cx={center}
                    cy={center}
                    r={radius}
                    fill="none"
                    strokeWidth={strokeWidth}
                    className="stroke-muted"
                />
                {/* Primary usage arc (0..100%) */}
                <circle
                    cx={center}
                    cy={center}
                    r={radius}
                    fill="none"
                    strokeWidth={strokeWidth}
                    strokeLinecap={primaryLength > 0 ? 'round' : 'butt'}
                    strokeDasharray={circumference}
                    strokeDashoffset={circumference * (1 - primaryLength)}
                    stroke={arcColor}
                />
                {/* Overflow arc (>100%) layered on top in red */}
                {overflowLength > 0 && (
                    <circle
                        cx={center}
                        cy={center}
                        r={radius}
                        fill="none"
                        strokeWidth={strokeWidth}
                        strokeLinecap="round"
                        stroke={COLOR_OVER}
                        strokeDasharray={circumference}
                        strokeDashoffset={circumference * (1 - overflowLength)}
                    />
                )}
            </svg>

            <div className="absolute inset-0 flex flex-col items-center justify-center px-2 text-center">
                <div className="text-2xl leading-none font-semibold text-foreground tabular-nums">
                    {timerLabel}
                </div>
                <div
                    className={`mt-1.5 text-[11px] font-medium ${
                        isOverrun
                            ? 'text-red-600 dark:text-red-400'
                            : 'text-muted-foreground'
                    }`}
                >
                    {subRemaining}
                </div>
                <div className="mt-0.5 text-[10px] text-muted-foreground">
                    Total allowed:{' '}
                    <span className="tabular-nums">
                        {formatHHMM(allowedMinutes)}
                    </span>
                </div>
            </div>
        </div>
    );
}

export default TimeStatusDonut;
