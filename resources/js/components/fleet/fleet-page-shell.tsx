'use client';

import { cn } from '@/lib/utils';
import * as React from 'react';

/**
 * Page shell: max-width container, clean background, header row (title, subtitle, rightActions).
 * Use for Fleet list/detail pages for consistent layout.
 */
const pageContainerClass =
    'relative mx-auto w-full max-w-[1200px] space-y-4 bg-background 2xl:max-w-[1320px]';

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
            <div className={pageHeaderRowClass}>
                <div>
                    <h1 className={cn(pageTitleClass, titleClassName)}>
                        {title}
                    </h1>
                    {subtitle != null && (
                        <p className={pageSubtitleClass}>{subtitle}</p>
                    )}
                </div>
                {rightActions != null && (
                    <div className="self-start lg:self-auto">
                        {rightActions}
                    </div>
                )}
            </div>

            <div className={cn('relative', contentWrapperClassName)}>
                {children}
            </div>
        </div>
    );
}

export {
    pageContainerClass,
    pageHeaderRowClass,
    pageSubtitleClass,
    pageTitleClass,
};
