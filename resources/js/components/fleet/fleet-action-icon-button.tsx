'use client';

import * as React from 'react';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

/**
 * Colored icon button for table row actions (reference BusMasterPage style).
 */
export const FLEET_ACTION_ICON_STYLES: Record<string, string> = {
    view: 'text-sky-600 bg-sky-50 hover:bg-sky-100 border border-sky-200/60 dark:text-sky-400 dark:bg-sky-500/20 dark:border-sky-400/40',
    edit: 'text-violet-600 bg-violet-50 hover:bg-violet-100 border border-violet-200/60 dark:text-violet-400 dark:bg-violet-500/20 dark:border-violet-400/40',
    delete: 'text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200/60 dark:text-rose-400 dark:bg-rose-500/20 dark:border-rose-400/40',
    add: 'text-emerald-600 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/60 dark:text-emerald-400 dark:bg-emerald-500/20 dark:border-emerald-400/40',
    lock: 'text-amber-600 bg-amber-50 hover:bg-amber-100 border border-amber-200/60 dark:text-amber-400 dark:bg-amber-500/20 dark:border-amber-400/40',
    settings: 'text-indigo-600 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200/60 dark:text-indigo-400 dark:bg-indigo-500/20 dark:border-indigo-400/40',
    power: 'text-cyan-600 bg-cyan-50 hover:bg-cyan-100 border border-cyan-200/60 dark:text-cyan-400 dark:bg-cyan-500/20 dark:border-cyan-400/40',
};

export type FleetActionIconVariant = keyof typeof FLEET_ACTION_ICON_STYLES;

export interface FleetActionIconButtonProps
    extends Omit<React.ButtonHTMLAttributes<HTMLButtonElement>, 'children'> {
    children: React.ReactNode;
    label: string;
    variant?: FleetActionIconVariant;
}

const styleMap = FLEET_ACTION_ICON_STYLES;

export function FleetActionIconButton({
    onClick,
    children,
    label,
    variant = 'view',
    className,
    ...props
}: FleetActionIconButtonProps) {
    const style = styleMap[variant] ?? styleMap.view;
    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={label}
            className={cn(
                'flex h-8 w-8 shrink-0 items-center justify-center rounded-md transition-colors',
                style,
                className
            )}
            {...props}
        >
            {children}
        </button>
    );
}

/** Link styled as action icon button (for view/edit in tables). */
export function FleetActionIconLink({
    href,
    children,
    label,
    variant = 'view',
    className,
    ...props
}: { href: string; children: React.ReactNode; label: string; variant?: FleetActionIconVariant } & Omit<React.AnchorHTMLAttributes<HTMLAnchorElement>, 'children'>) {
    const style = styleMap[variant] ?? styleMap.view;
    return (
        <Link
            href={href}
            aria-label={label}
            className={cn(
                'inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md transition-colors',
                style,
                className
            )}
            {...props}
        >
            {children}
        </Link>
    );
}
