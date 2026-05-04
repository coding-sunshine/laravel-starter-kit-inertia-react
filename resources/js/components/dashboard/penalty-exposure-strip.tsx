import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { ShieldCheck, TrendingDown, TrendingUp } from 'lucide-react';
import { Area, AreaChart, ResponsiveContainer, Tooltip } from 'recharts';

interface PenaltySummary {
    today_rs: number;
    trend_7d: { date: string; rs: number }[];
    preventable_pct: number;
}

const inr = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 });

export function PenaltyExposureStrip({ data }: { data: PenaltySummary }) {
    const isZero = !data.today_rs || data.today_rs === 0;
    const formatted = inr.format(data.today_rs);

    const lastTwo = data.trend_7d.slice(-2);
    const diff = lastTwo.length === 2 ? lastTwo[1].rs - lastTwo[0].rs : 0;
    const diffSign = diff > 0 ? '+' : '';
    const diffFormatted = inr.format(Math.abs(diff));
    const diffUp = diff > 0;

    const preventableBadgeVariant =
        data.preventable_pct >= 50 ? 'destructive' : data.preventable_pct >= 25 ? 'secondary' : 'outline';

    return (
        <Card className="border-l-4 border-l-emerald-600 shadow-sm">
            <CardContent className="flex flex-wrap items-center gap-4 p-3 sm:gap-6 sm:p-4">
                {/* Headline */}
                <div className="flex min-w-0 flex-1 items-center gap-3">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-900/60">
                        <ShieldCheck className="h-4 w-4" aria-hidden="true" />
                    </div>
                    <div className="min-w-0">
                        <p className="text-xs font-medium text-muted-foreground">
                            Today's penalty exposure
                        </p>
                        <p className="font-mono text-xl font-bold tabular-nums sm:text-2xl">
                            {isZero ? <span className="text-emerald-700 dark:text-emerald-400">₹0</span> : formatted}
                        </p>
                        {isZero && (
                            <p className="mt-0.5 text-xs text-muted-foreground">
                                No predicted penalty so far today.
                            </p>
                        )}
                    </div>
                </div>

                {/* Preventable badge */}
                {data.preventable_pct > 0 && (
                    <Badge variant={preventableBadgeVariant} className="shrink-0 text-[10px]">
                        {data.preventable_pct}% preventable
                    </Badge>
                )}

                {/* Sparkline + delta */}
                {data.trend_7d.length > 1 && (
                    <div className="flex shrink-0 items-center gap-3">
                        <div className="h-10 w-28 sm:w-32">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={data.trend_7d} margin={{ top: 2, right: 0, bottom: 0, left: 0 }}>
                                    <defs>
                                        <linearGradient id="penGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor="#fbbf24" stopOpacity={0.55} />
                                            <stop offset="95%" stopColor="#fbbf24" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <Area
                                        type="monotone"
                                        dataKey="rs"
                                        stroke="#fbbf24"
                                        strokeWidth={1.5}
                                        fill="url(#penGrad)"
                                        dot={false}
                                        isAnimationActive={false}
                                    />
                                    <Tooltip
                                        formatter={(v: number) => inr.format(v)}
                                        contentStyle={{
                                            background: '#022c22',
                                            border: '1px solid rgba(16,185,129,0.3)',
                                            color: '#d1fae5',
                                            fontSize: 11,
                                            borderRadius: 6,
                                        }}
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>

                        {lastTwo.length === 2 && (
                            <div className="text-right">
                                <p className="text-xs text-muted-foreground">7-day trend</p>
                                <p
                                    className={`flex items-center justify-end gap-1 font-mono text-sm font-semibold tabular-nums ${diffUp ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-400'}`}
                                >
                                    {diffUp ? (
                                        <TrendingUp className="h-3 w-3" aria-hidden="true" />
                                    ) : (
                                        <TrendingDown className="h-3 w-3" aria-hidden="true" />
                                    )}
                                    {diffSign}
                                    {diffFormatted}
                                </p>
                            </div>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
