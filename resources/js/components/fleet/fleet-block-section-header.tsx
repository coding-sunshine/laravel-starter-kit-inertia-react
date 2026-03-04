'use client';

import { cn } from '@/lib/utils';
import * as React from 'react';

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
                    'text-[11px] font-semibold tracking-wide text-muted-foreground uppercase',
                    titleClassName,
                )}
            >
                {children}
            </h2>
            <div
                className="mt-1.5 h-px rounded-full bg-white/30 dark:bg-white/20"
                aria-hidden
            />
        </div>
    );
}
