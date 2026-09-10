import { cn } from '@/lib/utils';
import {
    PolarAngleAxis,
    RadialBar,
    RadialBarChart as RechartsRadialBarChart,
    ResponsiveContainer,
} from 'recharts';

const COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
    '#8b5cf6',
    '#ec4899',
    '#14b8a6',
];

interface RadialItem {
    name: string;
    value: number;
    fill?: string;
}

interface RadialChartProps {
    data: RadialItem[];
    maxValue?: number;
    height?: number;
    innerRadius?: number;
    outerRadius?: number;
    className?: string;
}

export function RadialChart({
    data,
    maxValue = 100,
    height = 200,
    innerRadius = 30,
    outerRadius = 100,
    className,
}: RadialChartProps) {
    const colored = data.map((d, i) => ({
        ...d,
        fill: d.fill ?? COLORS[i % COLORS.length],
    }));

    return (
        <div className={cn('w-full', className)}>
            <ResponsiveContainer width="100%" height={height}>
                <RechartsRadialBarChart
                    innerRadius={innerRadius}
                    outerRadius={outerRadius}
                    data={colored}
                    startAngle={180}
                    endAngle={0}
                    cx="50%"
                    cy="80%"
                >
                    <PolarAngleAxis
                        type="number"
                        domain={[0, maxValue]}
                        angleAxisId={0}
                        tick={false}
                    />
                    <RadialBar
                        background
                        dataKey="value"
                        cornerRadius={6}
                        angleAxisId={0}
                    />
                </RechartsRadialBarChart>
            </ResponsiveContainer>
            <div className="mt-1 flex flex-wrap justify-center gap-x-4 gap-y-1">
                {colored.map((d) => (
                    <div key={d.name} className="flex items-center gap-1.5 text-xs">
                        <span
                            className="inline-block size-2.5 rounded-full"
                            style={{ backgroundColor: d.fill }}
                        />
                        <span className="text-muted-foreground">{d.name}</span>
                        <span className="font-medium tabular-nums">{d.value}%</span>
                    </div>
                ))}
            </div>
        </div>
    );
}
