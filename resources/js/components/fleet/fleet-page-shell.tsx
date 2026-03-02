'use client';

import * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * Page shell matching reference UI: max-width container, gradient bg, header row (title, subtitle, rightActions).
 * Use for Fleet list/detail pages so layout matches reference dashboard feel.
 */
const pageContainerClass =
    'relative mx-auto w-full max-w-[1200px] space-y-4 2xl:max-w-[1320px]';

const pageBgClass =
    'pointer-events-none absolute -inset-[1px] -z-10 overflow-hidden rounded-[28px]';

const pageHeaderRowClass =
    'relative flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between';

const pageTitleClass = 'text-2xl font-semibold tracking-tight text-foreground';
const pageSubtitleClass = 'mt-0.5 text-xs text-muted-foreground lg:text-sm';

export interface FleetPageShellProps {
    title: string;
    subtitle?: string;
    rightActions?: React.ReactNode;
    children: React.ReactNode;
    className?: string;
    titleClassName?: string;
    contentWrapperClassName?: string;
}

export function FleetPageShell({
    title,
    subtitle,
    rightActions,
    children,
    className,
    titleClassName,
    contentWrapperClassName,
}: FleetPageShellProps) {
    return (
        <div className={cn(pageContainerClass, className)}>
            <div className={pageBgClass} aria-hidden>
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_10%_20%,rgba(255,182,193,0.15),transparent_50%)]" />
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_70%_60%_at_85%_15%,rgba(221,160,221,0.12),transparent_45%)]" />
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_60%_50%_at_50%_85%,rgba(173,216,230,0.14),transparent_45%)]" />
            </div>

            <div className={pageHeaderRowClass}>
                <div>
                    <h1 className={cn(pageTitleClass, titleClassName)}>{title}</h1>
                    {subtitle != null && <p className={pageSubtitleClass}>{subtitle}</p>}
                </div>
                {rightActions != null && (
                    <div className="self-start lg:self-auto">{rightActions}</div>
                )}
            </div>

            <div className={cn('relative', contentWrapperClassName)}>{children}</div>
        </div>
    );
}

export { pageContainerClass, pageBgClass, pageHeaderRowClass, pageTitleClass, pageSubtitleClass };
