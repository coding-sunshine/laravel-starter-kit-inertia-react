import { cn } from '@/lib/utils';
import {
    Bar,
    BarChart as RechartsBarChart,
    CartesianGrid,
    Legend,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

const DEFAULT_COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
    '#8b5cf6',
    '#ec4899',
    '#14b8a6',
];

interface StackedBarChartProps<T extends Record<string, unknown>> {
    data: T[];
    xKey: keyof T & string;
    stackKeys: string[];
    stackLabels?: Record<string, string>;
    stackColors?: Record<string, string>;
    yLabel?: string;
    height?: number;
    layout?: 'horizontal' | 'vertical';
    formatY?: (value: number) => string;
    formatTooltip?: (value: number) => string;
    className?: string;
}

export function StackedBarChart<T extends Record<string, unknown>>({
    data,
    xKey,
    stackKeys,
    stackLabels,
    stackColors,
    yLabel,
    height = 280,
    layout = 'horizontal',
    formatY,
    formatTooltip,
    className,
}: StackedBarChartProps<T>) {
    const isVertical = layout === 'vertical';

    return (
        <div className={cn('w-full', className)}>
            <ResponsiveContainer width="100%" height={height}>
                <RechartsBarChart
                    data={data}
                    layout={isVertical ? 'vertical' : 'horizontal'}
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
                                              className: 'fill-muted-foreground',
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
                        formatter={(value: number, name: string) => [
                            formatTooltip
                                ? formatTooltip(value)
                                : value.toLocaleString(),
                            stackLabels?.[name] ?? name,
                        ]}
                    />
                    <Legend
                        formatter={(value: string) => stackLabels?.[value] ?? value}
                        wrapperStyle={{ fontSize: 12 }}
                    />
                    {stackKeys.map((key, i) => (
                        <Bar
                            key={key}
                            dataKey={key}
                            stackId="a"
                            fill={
                                stackColors?.[key] ??
                                DEFAULT_COLORS[i % DEFAULT_COLORS.length]
                            }
                            radius={
                                i === stackKeys.length - 1
                                    ? [4, 4, 0, 0]
                                    : [0, 0, 0, 0]
                            }
                        />
                    ))}
                </RechartsBarChart>
            </ResponsiveContainer>
        </div>
    );
}
