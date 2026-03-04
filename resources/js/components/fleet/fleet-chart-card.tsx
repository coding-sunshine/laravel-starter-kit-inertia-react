'use client';

import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import * as React from 'react';

interface TimeRange {
    label: string;
    value: string;
}

interface FleetChartCardProps {
    title: string;
    description?: string;
    timeRanges?: TimeRange[];
    selectedRange?: string;
    onRangeChange?: (value: string) => void;
    children: React.ReactNode;
    className?: string;
}

export function FleetChartCard({
    title,
    description,
    timeRanges,
    selectedRange,
    onRangeChange,
    children,
    className,
}: FleetChartCardProps) {
    return (
        <Card className={cn('overflow-hidden', className)}>
            <CardHeader>
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                {description && (
                    <CardDescription>{description}</CardDescription>
                )}
                {timeRanges && timeRanges.length > 0 && (
                    <CardAction>
                        <div className="inline-flex items-center rounded-lg border bg-muted p-0.5">
                            {timeRanges.map((range) => (
                                <button
                                    key={range.value}
                                    type="button"
                                    onClick={() =>
                                        onRangeChange?.(range.value)
                                    }
                                    className={cn(
                                        'rounded-md px-2.5 py-1 text-xs font-medium transition-colors',
                                        selectedRange === range.value
                                            ? 'bg-background text-foreground shadow-sm'
                                            : 'text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    {range.label}
                                </button>
                            ))}
                        </div>
                    </CardAction>
                )}
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
}
