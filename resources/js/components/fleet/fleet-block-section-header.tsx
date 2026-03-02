'use client';

import * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * Section header for dashboard/content blocks (reference style): uppercase label + thin separator.
 */
export function FleetBlockSectionHeader({
    children,
    className,
    titleClassName,
}: {
    children: React.ReactNode;
    className?: string;
    titleClassName?: string;
}) {
    return (
        <div className={cn('mt-6 mb-2', className)}>
            <h2
                className={cn(
                    'text-[11px] font-semibold uppercase tracking-wide text-muted-foreground',
                    titleClassName
                )}
            >
                {children}
            </h2>
            <div className="mt-1.5 h-px rounded-full bg-white/30 dark:bg-white/20" aria-hidden />
        </div>
    );
}
