import type { LoadingProgress } from '@/components/control-room/types';

export interface LoadingProgressDonutProps {
    progress: LoadingProgress;
    size?: number;
    strokeWidth?: number;
    showLabel?: boolean;
}

const ARC_COLOR = '#10B981'; // emerald-500

export function LoadingProgressDonut({
    progress,
    size = 160,
    strokeWidth = 16,
    showLabel = true,
}: LoadingProgressDonutProps) {
    const clampedPercent = Math.max(0, Math.min(100, progress.percent ?? 0));
    const targetLength = clampedPercent / 100;

    const radius = (size - strokeWidth) / 2;
    const center = size / 2;
    const circumference = 2 * Math.PI * radius;

    return (
        <div
            className="relative inline-flex shrink-0 items-center justify-center"
            style={{ width: size, height: size }}
            aria-label={`Loading progress: ${Math.round(clampedPercent)} percent, ${progress.loaded} of ${progress.total} wagons loaded`}
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
                {/* Foreground arc — static. Animation was retriggering every
                    parent re-render and causing visible flicker on every
                    second-tick. */}
                <circle
                    cx={center}
                    cy={center}
                    r={radius}
                    fill="none"
                    strokeWidth={strokeWidth}
                    strokeLinecap={targetLength > 0 ? 'round' : 'butt'}
                    stroke={ARC_COLOR}
                    strokeDasharray={circumference}
                    strokeDashoffset={circumference * (1 - targetLength)}
                />
            </svg>

            {showLabel && (
                <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <div className="text-3xl leading-none font-semibold text-foreground tabular-nums">
                        <span>{Math.round(clampedPercent)}</span>
                        <span className="text-xl">%</span>
                    </div>
                    <div className="mt-1.5 text-[11px] font-medium text-muted-foreground">
                        <span className="tabular-nums">{progress.loaded}</span>
                        <span className="mx-1">/</span>
                        <span className="tabular-nums">{progress.total}</span>
                        <span className="ml-1">wagons loaded</span>
                    </div>
                </div>
            )}
        </div>
    );
}

export default LoadingProgressDonut;
