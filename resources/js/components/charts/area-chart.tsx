import { cn } from '@/lib/utils';
import {
    Area,
    AreaChart as RechartsAreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface AreaChartProps<T extends Record<string, unknown>> {
    data: T[];
    xKey: keyof T & string;
    yKey: keyof T & string;
    yLabel?: string;
    height?: number;
    color?: string;
    gradientOpacity?: number;
    formatY?: (value: number) => string;
    formatTooltip?: (value: number) => string;
    className?: string;
}

export function AreaChart<T extends Record<string, unknown>>({
    data,
    xKey,
    yKey,
    yLabel,
    height = 280,
    color = 'var(--chart-1)',
    gradientOpacity = 0.15,
    formatY,
    formatTooltip,
    className,
}: AreaChartProps<T>) {
    const gradientId = `area-gradient-${yKey}`;

    return (
        <div className={cn('w-full', className)}>
            <ResponsiveContainer width="100%" height={height}>
                <RechartsAreaChart
                    data={data}
                    margin={{ top: 4, right: 4, bottom: 0, left: -12 }}
                >
                    <defs>
                        <linearGradient
                            id={gradientId}
                            x1="0"
                            y1="0"
                            x2="0"
                            y2="1"
                        >
                            <stop
                                offset="0%"
                                stopColor={color}
                                stopOpacity={gradientOpacity}
                            />
                            <stop
                                offset="95%"
                                stopColor={color}
                                stopOpacity={0}
                            />
                        </linearGradient>
                    </defs>
                    <CartesianGrid
                        strokeDasharray="3 3"
                        className="stroke-border/50"
                    />
                    <XAxis
                        dataKey={xKey}
                        tick={{ fontSize: 12 }}
                        className="fill-muted-foreground"
                        tickLine={false}
                        axisLine={false}
                    />
                    <YAxis
                        tick={{ fontSize: 12 }}
                        className="fill-muted-foreground"
                        tickLine={false}
                        axisLine={false}
                        tickFormatter={formatY}
                        label={
                            yLabel
                                ? {
                                      value: yLabel,
                                      angle: -90,
                                      position: 'insideLeft',
                                      className: 'fill-muted-foreground',
                                      style: { fontSize: 11 },
                                  }
                                : undefined
                        }
                    />
                    <Tooltip
                        contentStyle={{
                            backgroundColor: 'var(--card)',
                            borderColor: 'var(--border)',
                            borderRadius: 8,
                            fontSize: 12,
                        }}
                        formatter={(value: number) => [
                            formatTooltip
                                ? formatTooltip(value)
                                : value.toLocaleString(),
                            yLabel ?? yKey,
                        ]}
                    />
                    <Area
                        type="monotone"
                        dataKey={yKey}
                        stroke={color}
                        strokeWidth={2}
                        fill={`url(#${gradientId})`}
                    />
                </RechartsAreaChart>
            </ResponsiveContainer>
        </div>
    );
}
