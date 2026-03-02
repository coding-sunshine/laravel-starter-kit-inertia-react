'use client';

import * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * Toolbar row for filters and actions (reference fleet UI).
 * Use inside FleetGlassCard; left = filters/search, right = buttons.
 */
export function FleetPageToolbar({ children, className }: { children: React.ReactNode; className?: string }) {
    return (
        <div className={cn('flex flex-wrap justify-between items-center gap-4', className)}>
            {children}
        </div>
    );
}

export function FleetPageToolbarLeft({ children, className }: { children: React.ReactNode; className?: string }) {
    return <div className={cn('flex flex-wrap items-center gap-3', className)}>{children}</div>;
}

export function FleetPageToolbarRight({ children, className }: { children: React.ReactNode; className?: string }) {
    return <div className={cn('flex flex-wrap items-center gap-2', className)}>{children}</div>;
}
