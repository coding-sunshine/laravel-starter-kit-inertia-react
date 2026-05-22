import {
    Line,
    LineChart,
    ResponsiveContainer,
} from 'recharts';

import type { TrendMetric, TrendStripData } from './types';

interface Props {
    data: TrendStripData | null;
}

interface SparkProps {
    metric: TrendMetric;
    label: string;
    unit?: string;
}

function formatDelta(delta: number): string {
    const sign = delta > 0 ? '+' : '';
    return `${sign}${delta.toFixed(1)}%`;
}

function deltaColor(delta: number, higherIsBetter: boolean): string {
    if (Math.abs(delta) < 0.1) return 'text-slate-500 bg-slate-100';
    const good = higherIsBetter ? delta > 0 : delta < 0;
    return good ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100';
}

function Sparkline({ metric, label, unit }: SparkProps) {
    // recharts needs [{v: number}] shaped data
    const chartData = metric.spark.map((v) => ({ v }));

    const color =
        Math.abs(metric.delta_pct) < 0.1
            ? '#94a3b8'
            : metric.delta_pct > 0
              ? '#16a34a'
              : '#dc2626';

    // For on-time dispatch % and throughput higher is better; for penalty_rs lower is better
    const higherIsBetter = label !== 'Penalty ₹';

    return (
        <div className="flex flex-col gap-1">
            <p className="text-xs font-medium text-slate-500">{label}</p>
            <div className="h-12 w-full">
                <ResponsiveContainer width="100%" height="100%">
                    <LineChart data={chartData}>
                        <Line
                            type="monotone"
                            dataKey="v"
                            stroke={color}
                            strokeWidth={2}
                            dot={false}
                            isAnimationActive={false}
                        />
                    </LineChart>
                </ResponsiveContainer>
            </div>
            <div className="flex items-center justify-between gap-1">
                <span className="text-sm font-semibold tabular-nums text-slate-800">
                    {unit === '₹'
                        ? new Intl.NumberFormat('en-IN', {
                              style: 'currency',
                              currency: 'INR',
                              maximumFractionDigits: 0,
                          }).format(metric.current)
                        : unit === '%'
                          ? `${metric.current.toFixed(1)}%`
                          : `${metric.current.toFixed(1)} MT`}
                </span>
                <span
                    className={`rounded px-1.5 py-0.5 text-xs font-semibold tabular-nums ${deltaColor(metric.delta_pct, higherIsBetter)}`}
                >
                    {formatDelta(metric.delta_pct)}
                </span>
            </div>
        </div>
    );
}

export function TrendStrip({ data }: Props) {
    if (data === null) {
        return (
            <div
                className="flex items-center justify-center rounded-lg border border-slate-200 bg-white p-4 text-2xl text-slate-400"
                data-pan="manager-brief-widget-failed"
                data-pan-meta='{"widget":"trend_strip"}'
            >
                —
            </div>
        );
    }

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4">
            <p className="mb-3 text-xs font-medium uppercase tracking-wide text-slate-500">
                7-Day Trends
            </p>
            <div className="grid grid-cols-3 gap-4">
                <Sparkline
                    metric={data.penalty_rs}
                    label="Penalty ₹"
                    unit="₹"
                />
                <Sparkline
                    metric={data.throughput_mt}
                    label="Throughput MT"
                    unit="MT"
                />
                <Sparkline
                    metric={data.on_time_dispatch_pct}
                    label="On-Time Dispatch"
                    unit="%"
                />
            </div>
        </div>
    );
}
