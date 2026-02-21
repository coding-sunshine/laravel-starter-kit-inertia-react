import { cn } from '@/lib/utils';
import {
    Bar,
    BarChart as RechartsBarChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface BarChartProps<T extends Record<string, unknown>> {
    data: T[];
    xKey: keyof T & string;
    yKey: keyof T & string;
    yLabel?: string;
    height?: number;
    color?: string;
    layout?: 'horizontal' | 'vertical';
    formatY?: (value: number) => string;
    formatTooltip?: (value: number) => string;
    barSize?: number;
    className?: string;
}

export function BarChart<T extends Record<string, unknown>>({
    data,
    xKey,
    yKey,
    yLabel,
    height = 280,
    color = 'var(--chart-2)',
    layout = 'horizontal',
    formatY,
    formatTooltip,
    barSize,
    className,
}: BarChartProps<T>) {
    const isVertical = layout === 'vertical';

    return (
        <div className={cn('w-full', className)}>
            <ResponsiveContainer width="100%" height={height}>
                <RechartsBarChart
                    data={data}
                    layout={layout === 'vertical' ? 'vertical' : 'horizontal'}
                    margin={
                        isVertical
                            ? { top: 4, right: 4, bottom: 0, left: 0 }
                            : { top: 4, right: 4, bottom: 0, left: -12 }
                    }
                >
                    <CartesianGrid
                        strokeDasharray="3 3"
                        className="stroke-border/50"
                    />
                    {isVertical ? (
                        <>
                            <XAxis
                                type="number"
                                tick={{ fontSize: 12 }}
                                className="fill-muted-foreground"
                                tickLine={false}
                                axisLine={false}
                                tickFormatter={formatY}
                            />
                            <YAxis
                                type="category"
                                dataKey={xKey}
                                tick={{ fontSize: 12 }}
                                className="fill-muted-foreground"
                                tickLine={false}
                                axisLine={false}
                                width={100}
                            />
                        </>
                    ) : (
                        <>
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
                                              className:
                                                  'fill-muted-foreground',
                                              style: { fontSize: 11 },
                                          }
                                        : undefined
                                }
                            />
                        </>
                    )}
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
                    <Bar
                        dataKey={yKey}
                        fill={color}
                        radius={[4, 4, 0, 0]}
                        barSize={barSize}
                    />
                </RechartsBarChart>
            </ResponsiveContainer>
        </div>
    );
}
