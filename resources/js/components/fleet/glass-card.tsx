'use client';

import * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * Glass-style card inspired by reference fleet UI: frosted glass, soft shadow, subtle border.
 * Use for dashboard tiles and list wrappers in fleet.
 */
const glassCardBase =
    'rounded-xl border border-white/50 dark:border-white/10 ' +
    'shadow-[0_2px_8px_rgba(31,38,135,0.04),0_1px_4px_rgba(0,0,0,0.03)] dark:shadow-[0_2px_8px_rgba(0,0,0,0.2)] ' +
    'transition-all duration-200 ' +
    'bg-white/55 dark:bg-white/10 backdrop-blur-xl ' +
    'hover:bg-white/65 dark:hover:bg-white/15 hover:shadow-[0_4px_12px_rgba(31,38,135,0.06)] dark:hover:shadow-[0_4px_12px_rgba(0,0,0,0.25)]';

export interface FleetGlassCardProps extends React.HTMLAttributes<HTMLDivElement> {
    noPadding?: boolean;
}

export function FleetGlassCard({ className, noPadding, children, ...props }: FleetGlassCardProps) {
    return (
        <div
            className={cn(glassCardBase, !noPadding && 'p-4', className)}
            {...props}
        >
            {children}
        </div>
    );
}
