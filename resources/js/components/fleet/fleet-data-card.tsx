'use client';

import * as React from 'react';
import { MoreHorizontal } from 'lucide-react';
import { FleetGlassCard } from '@/components/fleet/glass-card';
import { cn } from '@/lib/utils';

/**
 * Card with header row (title + optional right action) and content.
 * Reference: DataCard for dashboard blocks (Safety Events, Recent Transactions).
 */
const cardHeaderRowClass =
    'mb-2 flex h-7 items-center justify-between';
const cardTitleClass = 'text-sm font-semibold text-foreground';
const cardHeaderIconClass = 'size-4 shrink-0 text-muted-foreground';

/** List container for data rows — divide-y style */
export const fleetDataCardListClass = 'divide-y divide-white/30 dark:divide-white/20 text-sm';

/** Single row: primary + secondary */
export const fleetDataCardRowClass =
    'flex items-center justify-between gap-2 py-2 first:pt-0';
export const fleetDataCardRowPrimaryClass = 'min-w-0 truncate text-foreground';
export const fleetDataCardRowSecondaryClass = 'shrink-0 text-[11px] text-muted-foreground';

export interface FleetDataCardProps {
    title: string;
    right?: React.ReactNode;
    children: React.ReactNode;
    className?: string;
}

export function FleetDataCard({ title, right, children, className }: FleetDataCardProps) {
    return (
        <FleetGlassCard className={cn('p-3', className)}>
            <div className={cardHeaderRowClass}>
                <h3 className={cardTitleClass}>{title}</h3>
                {right !== undefined ? right : <MoreHorizontal className={cardHeaderIconClass} aria-hidden />}
            </div>
            {children}
        </FleetGlassCard>
    );
}
