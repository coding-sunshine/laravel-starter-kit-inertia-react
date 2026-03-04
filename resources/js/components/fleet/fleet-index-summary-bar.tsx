'use client';

import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import * as React from 'react';

export interface SummaryStat {
    label: string;
    value: string | number;
    icon?: React.ComponentType<{ className?: string }>;
    variant?: 'default' | 'success' | 'warning' | 'danger';
}

interface FleetIndexSummaryBarProps {
    stats: SummaryStat[];
    className?: string;
}

const VARIANT_STYLES = {
    default: 'text-foreground',
    success: 'text-emerald-600 dark:text-emerald-400',
    warning: 'text-amber-600 dark:text-amber-400',
    danger: 'text-red-600 dark:text-red-400',
} as const;

export function FleetIndexSummaryBar({ stats, className }: FleetIndexSummaryBarProps) {
    return (
        <div className={cn('grid grid-cols-2 gap-3 md:grid-cols-4', className)}>
            {stats.map((stat) => {
                const Icon = stat.icon;
                return (
                    <Card key={stat.label}>
                        <CardContent className="pt-0">
                            <div className="flex items-center gap-2">
                                {Icon && <Icon className="size-4 shrink-0 text-muted-foreground" />}
                                <span className="truncate text-sm font-medium text-muted-foreground">
                                    {stat.label}
                                </span>
                            </div>
                            <p
                                className={cn(
                                    'mt-1 text-2xl font-bold tabular-nums tracking-tight',
                                    VARIANT_STYLES[stat.variant ?? 'default'],
                                )}
                            >
                                {typeof stat.value === 'number' ? stat.value.toLocaleString() : stat.value}
                            </p>
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}
