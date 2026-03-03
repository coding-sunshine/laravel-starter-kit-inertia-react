'use client';

import { cn } from '@/lib/utils';
import * as React from 'react';

const glassCardBase =
    'bg-card text-card-foreground rounded-xl border shadow-sm transition-shadow hover:shadow-md';

export interface FleetGlassCardProps extends React.HTMLAttributes<HTMLDivElement> {
    noPadding?: boolean;
}

export function FleetGlassCard({
    className,
    noPadding,
    children,
    ...props
}: FleetGlassCardProps) {
    return (
        <div
            className={cn(glassCardBase, !noPadding && 'p-4', className)}
            {...props}
        >
            {children}
        </div>
    );
}
