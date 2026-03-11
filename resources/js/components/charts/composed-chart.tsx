import { cn } from '@/lib/utils';
import {
    Bar,
    CartesianGrid,
    ComposedChart as RechartsComposedChart,
    Legend,
    Line,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface ComposedChartProps<T extends Record<string, unknown>> {
    data: T[];
    xKey: keyof T & string;
    barKey: keyof T & string;
    lineKey: keyof T & string;
    barLabel?: string;
    lineLabel?: string;
    yLabel?: string;
    height?: number;
    barColor?: string;
    lineColor?: string;
    formatY?: (value: number) => string;
    formatTooltip?: (value: number, name: string) => string;
    className?: string;
}

export function ComposedChart<T extends Record<string, unknown>>({
    data,
    xKey,
    barKey,
    lineKey,
    barLabel,
    lineLabel,
    yLabel,
    height = 280,
    barColor = 'var(--chart-1)',
    lineColor = 'var(--chart-4)',
    formatY,
    formatTooltip,
    className,
}: ComposedChartProps<T>) {
    return (
        <div className={cn('w-full', className)}>
            <ResponsiveContainer width="100%" height={height}>
                <RechartsComposedChart
                    data={data}
                    margin={{ top: 4, right: 4, bottom: 0, left: -12 }}
                >
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
                                ? formatTooltip(value, name)
                                : value.toLocaleString(),
                            name === barKey
                                ? (barLabel ?? barKey)
                                : (lineLabel ?? lineKey),
                        ]}
                    />
                    <Legend
                        formatter={(value: string) =>
                            value === barKey
                                ? (barLabel ?? barKey)
                                : (lineLabel ?? lineKey)
                        }
                        wrapperStyle={{ fontSize: 12 }}
                    />
                    <Bar
                        dataKey={barKey}
                        fill={barColor}
                        radius={[4, 4, 0, 0]}
                        barSize={20}
                        opacity={0.85}
                    />
                    <Line
                        type="monotone"
                        dataKey={lineKey}
                        stroke={lineColor}
                        strokeWidth={2.5}
                        dot={{ r: 3, fill: lineColor, strokeWidth: 0 }}
                        activeDot={{
                            r: 5,
                            fill: lineColor,
                            strokeWidth: 2,
                            stroke: 'var(--card)',
                        }}
                    />
                </RechartsComposedChart>
            </ResponsiveContainer>
        </div>
    );
}
