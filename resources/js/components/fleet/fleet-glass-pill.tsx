'use client';

import { cn } from '@/lib/utils';
import * as React from 'react';

/**
 * Glassmorphism pill/badge (reference style): rounded-full, soft border, pastel variants.
 */
const glassPillBase =
    'inline-flex items-center gap-1.5 rounded-full border border-white/[0.35] ' +
    'bg-white/50 backdrop-blur-md dark:bg-white/20 dark:border-white/20 ' +
    'shadow-[0_2px_10px_rgba(0,0,0,0.05)] ' +
    'px-3 py-1.5 text-sm transition-colors';

export interface FleetGlassPillProps extends React.HTMLAttributes<HTMLSpanElement> {
    variant?: 'default' | 'success' | 'warning' | 'critical' | 'info';
}

const variantClass = {
    default: 'text-muted-foreground',
    success:
        'bg-emerald-50/90 text-emerald-700/90 border-emerald-200/40 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-400/30',
    warning:
        'bg-amber-50/90 text-amber-700/90 border-amber-200/40 dark:bg-amber-500/20 dark:text-amber-300 dark:border-amber-400/30',
    critical:
        'bg-red-50/90 text-red-700/90 border-red-200/40 dark:bg-red-500/20 dark:text-red-300 dark:border-red-400/30',
    info: 'bg-indigo-50/90 text-indigo-700/90 border-indigo-200/40 dark:bg-indigo-500/20 dark:text-indigo-300 dark:border-indigo-400/30',
} as const;

export function FleetGlassPill({
    className,
    variant = 'default',
    children,
    ...props
}: FleetGlassPillProps) {
    return (
        <span
            className={cn(glassPillBase, variantClass[variant], className)}
            {...props}
        >
            {children}
        </span>
    );
}
