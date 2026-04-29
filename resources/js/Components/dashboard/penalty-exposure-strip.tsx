import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Area, AreaChart, ResponsiveContainer, Tooltip } from 'recharts';

interface PenaltySummary {
    today_rs: number;
    trend_7d: { date: string; rs: number }[];
    preventable_pct: number;
}

export function PenaltyExposureStrip({ data }: { data: PenaltySummary }) {
    const formatted = new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(data.today_rs);

    const preventableBadgeVariant =
        data.preventable_pct >= 50 ? 'destructive' : data.preventable_pct >= 25 ? 'secondary' : 'outline';

    const lastTwo = data.trend_7d.slice(-2);
    const diff = lastTwo.length === 2 ? lastTwo[1].rs - lastTwo[0].rs : 0;
    const sign = diff >= 0 ? '+' : '';
    const diffFormatted = new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(diff);

    return (
        <Card className="border-0 shadow-sm" style={{ backgroundColor: 'oklch(0.22 0.06 150)' }}>
            <CardContent className="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p className="mb-1 text-xs font-semibold uppercase tracking-widest text-white/50">
                        Today's Penalty Exposure
                    </p>
                    <p className="font-mono text-3xl font-bold tabular-nums text-white">{formatted}</p>
                </div>

                <div className="flex items-center gap-4">
                    {data.preventable_pct > 0 && (
                        <Badge variant={preventableBadgeVariant} className="text-xs">
                            {data.preventable_pct}% preventable
                        </Badge>
                    )}

                    {data.trend_7d.length > 1 && (
                        <div className="h-12 w-32">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={data.trend_7d}>
                                    <defs>
                                        <linearGradient id="penGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#C8A84B" stopOpacity={0.6} />
                                            <stop offset="95%" stopColor="#C8A84B" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <Area
                                        type="monotone"
                                        dataKey="rs"
                                        stroke="#C8A84B"
                                        strokeWidth={1.5}
                                        fill="url(#penGrad)"
                                        dot={false}
                                    />
                                    <Tooltip
                                        formatter={(v: number) =>
                                            new Intl.NumberFormat('en-IN', {
                                                style: 'currency',
                                                currency: 'INR',
                                                maximumFractionDigits: 0,
                                            }).format(v)
                                        }
                                        contentStyle={{
                                            background: '#1E3A2F',
                                            border: 'none',
                                            color: '#fff',
                                            fontSize: 11,
                                        }}
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>
                    )}

                    {lastTwo.length === 2 && (
                        <div className="text-right">
                            <p className="text-[10px] uppercase tracking-wide text-white/40">7-day trend</p>
                            <p
                                className={`font-mono text-sm font-semibold tabular-nums ${diff > 0 ? 'text-red-400' : 'text-green-400'}`}
                            >
                                {sign}
                                {diffFormatted}
                            </p>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
