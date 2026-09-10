import { motion } from 'framer-motion';
import { useMemo } from 'react';

export interface ThroughputPoint {
    ts: number;
    cumulativeMt: number;
}

interface Props {
    points: ThroughputPoint[];
    width?: number;
    height?: number;
}

/**
 * Cumulative tonnage over last 60 minutes. Driven by client-side ring buffer
 * fed by the .loadrite.event Reverb channel — no backend endpoint required.
 */
export function ThroughputSparkline({
    points,
    width = 320,
    height = 80,
}: Props) {
    const path = useMemo(() => {
        if (points.length < 2) return null;
        const now = Date.now();
        const windowMs = 60 * 60 * 1000;
        const x0 = now - windowMs;

        const maxCum = points.reduce(
            (acc, p) => Math.max(acc, p.cumulativeMt),
            1,
        );

        const xScale = (ts: number) =>
            ((ts - x0) / windowMs) * (width - 4) + 2;
        const yScale = (mt: number) =>
            height - 4 - (mt / maxCum) * (height - 12);

        const d = points
            .map((p, i) => {
                const x = xScale(p.ts).toFixed(1);
                const y = yScale(p.cumulativeMt).toFixed(1);
                return `${i === 0 ? 'M' : 'L'} ${x} ${y}`;
            })
            .join(' ');
        const last = points[points.length - 1];
        const lastX = xScale(last.ts);
        const lastY = yScale(last.cumulativeMt);
        return { d, lastX, lastY, max: maxCum, latest: last.cumulativeMt };
    }, [points, width, height]);

    return (
        <div className="rounded-md border border-slate-200 bg-white p-3">
            {!path ? (
                <div className="flex h-[80px] items-center justify-center text-xs text-slate-400">
                    Waiting for events…
                </div>
            ) : (
                <>
                    <div className="mb-1 flex items-baseline justify-between">
                        <span className="text-xs text-slate-500">
                            Cumulative MT
                        </span>
                        <span className="text-sm font-semibold tabular-nums text-slate-900">
                            {path.latest.toFixed(2)} MT
                        </span>
                    </div>
                    <svg
                        width={width}
                        height={height}
                        viewBox={`0 0 ${width} ${height}`}
                        className="block"
                        role="img"
                        aria-label="Throughput last 60 minutes"
                    >
                        <motion.path
                            d={path.d}
                            fill="none"
                            stroke="#0EA5E9"
                            strokeWidth={2}
                            strokeLinecap="round"
                            initial={false}
                            animate={{ pathLength: 1 }}
                        />
                        <motion.circle
                            cx={path.lastX}
                            cy={path.lastY}
                            r={3.5}
                            fill="#0EA5E9"
                            initial={{ scale: 0 }}
                            animate={{ scale: [1, 1.4, 1] }}
                            transition={{
                                duration: 1.2,
                                repeat: Infinity,
                                ease: 'easeInOut',
                            }}
                        />
                    </svg>
                </>
            )}
        </div>
    );
}
