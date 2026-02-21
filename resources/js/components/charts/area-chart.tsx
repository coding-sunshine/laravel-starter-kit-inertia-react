import { cn } from '@/lib/utils';
import {
    Area,
    AreaChart as RechartsAreaChart,
    CartesianGrid,
    Line,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface AreaChartProps<T extends Record<string, unknown>> {
    data: T[];
    xKey: keyof T & string;
    yKey: keyof T & string;
    secondaryYKey?: keyof T & string;
    secondaryLabel?: string;
    yLabel?: string;
    height?: number;
    color?: string;
    secondaryColor?: string;
    gradientOpacity?: number;
    formatY?: (value: number) => string;
    formatTooltip?: (value: number) => string;
    className?: string;
}

export function AreaChart<T extends Record<string, unknown>>({
    data,
    xKey,
    yKey,
    secondaryYKey,
    secondaryLabel,
    yLabel,
    height = 280,
    color = 'var(--chart-1)',
    secondaryColor = 'var(--chart-3)',
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
                        tick={{ fontSize: 11 }}
                        className="fill-muted-foreground"
                        tickLine={false}
                        axisLine={false}
                        interval="preserveStartEnd"
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
                        formatter={(value: number, name: string) => [
                            formatTooltip
                                ? formatTooltip(value)
                                : value.toLocaleString(),
                            name === yKey
                                ? (yLabel ?? yKey)
                                : (secondaryLabel ?? name),
                        ]}
                    />
                    <Area
                        type="monotone"
                        dataKey={yKey}
                        stroke={color}
                        strokeWidth={2}
                        fill={`url(#${gradientId})`}
                        dot={{ r: 3, fill: color, strokeWidth: 0 }}
                        activeDot={{ r: 5, fill: color, strokeWidth: 2, stroke: 'var(--card)' }}
                    />
                    {secondaryYKey && (
                        <Line
                            type="monotone"
                            dataKey={secondaryYKey}
                            stroke={secondaryColor}
                            strokeWidth={1.5}
                            strokeDasharray="5 5"
                            dot={false}
                        />
                    )}
                </RechartsAreaChart>
            </ResponsiveContainer>
        </div>
    );
}
