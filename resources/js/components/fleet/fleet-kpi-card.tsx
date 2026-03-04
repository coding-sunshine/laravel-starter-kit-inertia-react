'use client';

import { Card, CardContent } from '@/components/ui/card';
import { ChartContainer } from '@/components/ui/chart';
import { cn } from '@/lib/utils';
import { ArrowDown, ArrowRight, ArrowUp } from 'lucide-react';
import * as React from 'react';
import { Area, AreaChart } from 'recharts';

interface FleetKpiCardProps {
    title: string;
    value: number;
    trend?: 'up' | 'down' | 'flat';
    trendValue?: string;
    sparklineData?: number[];
    icon?: React.ComponentType<{ className?: string }>;
    subtitle?: string;
    className?: string;
}

const TREND_CONFIG = {
    up: { icon: ArrowUp, className: 'text-emerald-600 dark:text-emerald-400' },
    down: { icon: ArrowDown, className: 'text-red-600 dark:text-red-400' },
    flat: { icon: ArrowRight, className: 'text-muted-foreground' },
} as const;

const sparklineChartConfig = {
    value: { label: 'Value', color: 'var(--chart-1)' },
} as const;

export function FleetKpiCard({
    title,
    value,
    trend,
    trendValue,
    sparklineData,
    icon: Icon,
    subtitle,
    className,
}: FleetKpiCardProps) {
    const chartData = React.useMemo(
        () => sparklineData?.map((v, i) => ({ day: i, value: v })) ?? [],
        [sparklineData],
    );

    const TrendIcon = trend ? TREND_CONFIG[trend].icon : null;

    return (
        <Card className={cn('relative overflow-hidden', className)}>
            <CardContent className="flex items-start justify-between gap-4 pt-0">
                <div className="flex min-w-0 flex-1 flex-col gap-1">
                    {/* Header: icon + title */}
                    <div className="flex items-center gap-2">
                        {Icon && (
                            <Icon className="size-4 shrink-0 text-muted-foreground" />
                        )}
                        <span className="truncate text-sm font-medium text-muted-foreground">
                            {title}
                        </span>
                    </div>

                    {/* Value + trend */}
                    <div className="flex items-baseline gap-2">
                        <span className="text-3xl font-bold tabular-nums tracking-tight">
                            {value.toLocaleString()}
                        </span>
                        {trend && TrendIcon && (
                            <span
                                className={cn(
                                    'inline-flex items-center gap-0.5 text-sm font-medium',
                                    TREND_CONFIG[trend].className,
                                )}
                            >
                                <TrendIcon className="size-3.5" />
                                {trendValue}
                            </span>
                        )}
                    </div>

                    {/* Subtitle */}
                    {subtitle && (
                        <span className="text-xs text-muted-foreground">
                            {subtitle}
                        </span>
                    )}
                </div>

                {/* Sparkline */}
                {chartData.length > 0 && (
                    <div className="h-12 w-24 shrink-0">
                        <ChartContainer
                            config={sparklineChartConfig}
                            className="h-full w-full [&_[data-slot=chart]]:!aspect-auto"
                        >
                            <AreaChart
                                data={chartData}
                                margin={{ top: 2, right: 2, bottom: 2, left: 2 }}
                            >
                                <defs>
                                    <linearGradient
                                        id="sparklineFill"
                                        x1="0"
                                        y1="0"
                                        x2="0"
                                        y2="1"
                                    >
                                        <stop
                                            offset="0%"
                                            stopColor="var(--color-value)"
                                            stopOpacity={0.3}
                                        />
                                        <stop
                                            offset="100%"
                                            stopColor="var(--color-value)"
                                            stopOpacity={0.05}
                                        />
                                    </linearGradient>
                                </defs>
                                <Area
                                    type="monotone"
                                    dataKey="value"
                                    stroke="var(--color-value)"
                                    strokeWidth={1.5}
                                    fill="url(#sparklineFill)"
                                    isAnimationActive={false}
                                />
                            </AreaChart>
                        </ChartContainer>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
