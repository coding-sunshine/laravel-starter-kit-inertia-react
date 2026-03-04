import {
    PieChart,
    Pie,
    Cell,
    ResponsiveContainer,
    Tooltip,
    Legend,
} from 'recharts';

interface DonutData {
    name: string;
    value: number;
    count?: number;
}

interface DonutChartProps {
    data: DonutData[];
    colors?: string[];
    className?: string;
}

const DEFAULT_COLORS = [
    'hsl(var(--primary))',
    'hsl(var(--secondary))',
    'hsl(220, 70%, 50%)',
    'hsl(160, 60%, 45%)',
    'hsl(30, 80%, 55%)',
    'hsl(280, 60%, 50%)',
    'hsl(340, 75%, 50%)',
    'hsl(200, 80%, 45%)',
];

export function DonutChart({ data, colors = DEFAULT_COLORS, className }: DonutChartProps) {
    return (
        <div className={className}>
            <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                    <Pie
                        data={data}
                        cx="50%"
                        cy="50%"
                        innerRadius={60}
                        outerRadius={100}
                        dataKey="value"
                        nameKey="name"
                    >
                        {data.map((entry, index) => (
                            <Cell
                                key={`cell-${index}`}
                                fill={colors[index % colors.length]}
                            />
                        ))}
                    </Pie>
                    <Tooltip
                        formatter={(value, name) => [
                            typeof value === 'number' ? value.toLocaleString() : value,
                            name
                        ]}
                        contentStyle={{
                            backgroundColor: 'hsl(var(--background))',
                            border: '1px solid hsl(var(--border))',
                            borderRadius: '6px'
                        }}
                    />
                    <Legend
                        verticalAlign="bottom"
                        height={36}
                        iconType="circle"
                        wrapperStyle={{ fontSize: '12px' }}
                    />
                </PieChart>
            </ResponsiveContainer>
        </div>
    );
}