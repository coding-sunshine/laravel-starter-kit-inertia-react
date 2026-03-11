import { cn } from '@/lib/utils';
import {
    Legend,
    PolarAngleAxis,
    PolarGrid,
    PolarRadiusAxis,
    Radar,
    RadarChart as RechartsRadarChart,
    ResponsiveContainer,
    Tooltip,
} from 'recharts';

const COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

interface RadarChartProps<T extends Record<string, unknown>> {
    data: T[];
    axisKey: keyof T & string;
    radarKeys: string[];
    radarLabels?: Record<string, string>;
    radarColors?: Record<string, string>;
    height?: number;
    formatTooltip?: (value: number) => string;
    className?: string;
}

export function RadarChart<T extends Record<string, unknown>>({
    data,
    axisKey,
    radarKeys,
    radarLabels,
    radarColors,
    height = 300,
    formatTooltip,
    className,
}: RadarChartProps<T>) {
    return (
        <div className={cn('w-full', className)}>
            <ResponsiveContainer width="100%" height={height}>
                <RechartsRadarChart
                    data={data}
                    cx="50%"
                    cy="50%"
                    outerRadius="70%"
                >
                    <PolarGrid className="stroke-border/50" />
                    <PolarAngleAxis
                        dataKey={axisKey}
                        tick={{ fontSize: 11 }}
                        className="fill-muted-foreground"
                    />
                    <PolarRadiusAxis
                        tick={{ fontSize: 10 }}
                        className="fill-muted-foreground"
                        axisLine={false}
                    />
                    <Tooltip
                        contentStyle={{
                            backgroundColor: 'var(--card)',
                            borderColor: 'var(--border)',
                            borderRadius: 8,
                            fontSize: 12,
                        }}
                        formatter={(value: number, name: string) => [
                            formatTooltip ? formatTooltip(value) : value.toLocaleString(),
                            radarLabels?.[name] ?? name,
                        ]}
                    />
                    <Legend
                        formatter={(value: string) => radarLabels?.[value] ?? value}
                        wrapperStyle={{ fontSize: 12 }}
                    />
                    {radarKeys.map((key, i) => (
                        <Radar
                            key={key}
                            name={key}
                            dataKey={key}
                            stroke={radarColors?.[key] ?? COLORS[i % COLORS.length]}
                            fill={radarColors?.[key] ?? COLORS[i % COLORS.length]}
                            fillOpacity={0.15}
                            strokeWidth={2}
                        />
                    ))}
                </RechartsRadarChart>
            </ResponsiveContainer>
        </div>
    );
}
