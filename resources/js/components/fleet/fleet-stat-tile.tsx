'use client';

import { FleetGlassCard } from '@/components/fleet/glass-card';
import { cn } from '@/lib/utils';
import { ArrowDown, ArrowRight, ArrowUp, Dot } from 'lucide-react';
import * as React from 'react';

/**
 * KPI tint map (reference StatTile) — border, badge, iconCircle.
 */
export const FLEET_STAT_TILE_TINT: Record<
    string,
    { border: string; badge: string; iconCircle: string }
> = {
    blue: {
        border: 'border-l-blue-400',
        badge: 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-200/60',
        iconCircle:
            'bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-slate-300',
    },
    violet: {
        border: 'border-l-violet-400',
        badge: 'bg-violet-500/15 text-violet-600 dark:text-violet-400 border-violet-200/60',
        iconCircle:
            'bg-indigo-100 dark:bg-white/10 text-indigo-600 dark:text-indigo-300',
    },
    emerald: {
        border: 'border-l-emerald-400',
        badge: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-200/60',
        iconCircle:
            'bg-emerald-100 dark:bg-white/10 text-emerald-600 dark:text-emerald-300',
    },
    slate: {
        border: 'border-l-slate-400',
        badge: 'bg-slate-500/15 text-slate-600 dark:text-slate-400 border-slate-200/60',
        iconCircle:
            'bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-slate-300',
    },
    amber: {
        border: 'border-l-amber-400',
        badge: 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-200/60',
        iconCircle:
            'bg-amber-100 dark:bg-white/10 text-amber-600 dark:text-amber-300',
    },
    sky: {
        border: 'border-l-sky-400',
        badge: 'bg-sky-500/15 text-sky-600 dark:text-sky-400 border-sky-200/60',
        iconCircle:
            'bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-sky-300',
    },
    rose: {
        border: 'border-l-rose-400',
        badge: 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-200/60',
        iconCircle:
            'bg-rose-100 dark:bg-white/10 text-rose-600 dark:text-rose-300',
    },
};

export type FleetStatTileTint = keyof typeof FLEET_STAT_TILE_TINT;

export interface FleetStatTileProps {
    icon: React.ComponentType<{ className?: string }>;
    title: string;
    value: string;
    sub: string;
    tint?: FleetStatTileTint;
    className?: string;
    /** 'cockpit' = larger icon circle and KPI hierarchy */
    variant?: 'default' | 'cockpit';
    trend?: 'up' | 'down' | 'flat';
    trendValue?: string;
}

const TREND_CONFIG = {
    up: { icon: ArrowUp, className: 'text-emerald-600 dark:text-emerald-400' },
    down: { icon: ArrowDown, className: 'text-red-600 dark:text-red-400' },
    flat: { icon: ArrowRight, className: 'text-muted-foreground' },
} as const;

export function FleetStatTile({
    icon: Icon,
    title,
    value,
    sub,
    tint = 'slate',
    className,
    variant = 'default',
    trend,
    trendValue,
}: FleetStatTileProps) {
    const t = FLEET_STAT_TILE_TINT[tint] ?? FLEET_STAT_TILE_TINT.slate;
    const { border, badge, iconCircle } = t;
    const isCockpit = variant === 'cockpit';

    return (
        <FleetGlassCard
            className={cn(
                'relative flex flex-col justify-between overflow-hidden border-l-4',
                isCockpit ? 'min-h-[84px] p-3' : 'min-h-[70px] p-2',
                border,
                className,
            )}
            noPadding
        >
            <div className="relative flex items-center gap-2">
                <div
                    className={cn(
                        'flex shrink-0 items-center justify-center',
                        isCockpit
                            ? 'h-8 w-8 rounded-full'
                            : 'h-6 w-6 rounded-md border',
                        isCockpit ? iconCircle : badge,
                    )}
                >
                    <Icon className={isCockpit ? 'size-4' : 'size-3'} />
                </div>
                <p
                    className={cn(
                        'truncate leading-tight font-medium tracking-wide text-muted-foreground uppercase',
                        isCockpit ? 'text-[11px]' : 'text-[10px]',
                    )}
                >
                    {title}
                </p>
            </div>
            <div className="relative mt-1">
                <div className="flex items-baseline gap-1.5">
                    <p
                        className={cn(
                            'font-semibold tracking-tight text-foreground',
                            isCockpit ? 'text-2xl leading-none' : 'text-xl',
                        )}
                    >
                        {value}
                    </p>
                    {trend && (
                        <span
                            className={cn(
                                'inline-flex items-center gap-0.5 text-xs font-medium',
                                TREND_CONFIG[trend].className,
                            )}
                        >
                            {React.createElement(TREND_CONFIG[trend].icon, {
                                className: 'size-3',
                            })}
                            {trendValue}
                        </span>
                    )}
                </div>
                <p
                    className={cn(
                        'mt-0.5 flex items-center leading-tight text-muted-foreground',
                        isCockpit ? 'text-xs' : 'text-[10px]',
                    )}
                >
                    <Dot className="size-2 shrink-0" />
                    {sub}
                </p>
            </div>
        </FleetGlassCard>
    );
}
