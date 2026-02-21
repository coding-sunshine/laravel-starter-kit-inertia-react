import { cn } from '@/lib/utils';
import {
    Cell,
    Legend,
    Pie,
    PieChart as RechartsPieChart,
    ResponsiveContainer,
    Tooltip,
} from 'recharts';

const CHART_COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

interface PieChartProps<T extends Record<string, unknown>> {
    data: T[];
    nameKey: keyof T & string;
    valueKey: keyof T & string;
    height?: number;
    colors?: string[];
    innerRadius?: number;
    formatTooltip?: (value: number) => string;
    showLegend?: boolean;
    className?: string;
}

export function PieChart<T extends Record<string, unknown>>({
    data,
    nameKey,
    valueKey,
    height = 280,
    colors = CHART_COLORS,
    innerRadius = 50,
    formatTooltip,
    showLegend = true,
    className,
}: PieChartProps<T>) {
    return (
        <div className={cn('w-full', className)}>
            <ResponsiveContainer width="100%" height={height}>
                <RechartsPieChart>
                    <Pie
                        data={data}
                        dataKey={valueKey}
                        nameKey={nameKey}
                        cx="50%"
                        cy="50%"
                        innerRadius={innerRadius}
                        outerRadius="80%"
                        paddingAngle={2}
                        strokeWidth={0}
                    >
                        {data.map((_entry, index) => (
                            <Cell
                                key={`cell-${index}`}
                                fill={colors[index % colors.length]}
                            />
                        ))}
                    </Pie>
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
                        ]}
                    />
                    {showLegend && (
                        <Legend
                            wrapperStyle={{ fontSize: 12 }}
                            iconType="circle"
                            iconSize={8}
                        />
                    )}
                </RechartsPieChart>
            </ResponsiveContainer>
        </div>
    );
}
