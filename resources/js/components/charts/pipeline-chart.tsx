import {
    Bar,
    BarChart,
    ResponsiveContainer,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
} from 'recharts';

interface PipelineData {
    stage: string;
    count: number;
    average_value: number;
}

interface PipelineChartProps {
    data: PipelineData[];
    className?: string;
}

export function PipelineChart({ data, className }: PipelineChartProps) {
    return (
        <div className={className}>
            <ResponsiveContainer width="100%" height="100%">
                <BarChart data={data} layout="horizontal" margin={{ top: 5, right: 30, left: 40, bottom: 5 }}>
                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                    <XAxis type="number" className="text-xs" />
                    <YAxis
                        type="category"
                        dataKey="stage"
                        className="text-xs"
                        width={80}
                    />
                    <Tooltip
                        formatter={(value, name) => [
                            name === 'count' ? `${value} projects` : `$${Number(value).toLocaleString()}`,
                            name === 'count' ? 'Projects' : 'Avg Value'
                        ]}
                        labelStyle={{ color: 'hsl(var(--foreground))' }}
                        contentStyle={{
                            backgroundColor: 'hsl(var(--background))',
                            border: '1px solid hsl(var(--border))',
                            borderRadius: '6px'
                        }}
                    />
                    <Bar
                        dataKey="count"
                        fill="hsl(var(--primary))"
                        radius={[0, 4, 4, 0]}
                    />
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}