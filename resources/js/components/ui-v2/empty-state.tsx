import * as React from 'react';

import { cn } from '@/lib/utils';

export interface EmptyStateProps {
    icon?: React.ComponentType<{ className?: string }>;
    title: string;
    description?: string;
    action?: React.ReactNode;
    /** Optional illustration: image src (e.g. /images/empty/vehicles.svg) or ReactNode */
    illustration?: string | React.ReactNode;
    className?: string;
    children?: React.ReactNode;
}

/**
 * Empty state for list/index pages. Compound-friendly; use icon + title + description + action.
 */
function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    illustration,
    className,
    children,
}: EmptyStateProps) {
    return (
        <div
            data-slot="empty-state"
            className={cn(
                'flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-border bg-muted/20 px-6 py-16 text-center',
                className,
            )}
        >
            {illustration !== undefined ? (
                <div className="flex h-24 w-24 items-center justify-center text-muted-foreground/70">
                    {typeof illustration === 'string' ? (
                        <img
                            src={illustration}
                            alt=""
                            className="h-full w-full object-contain"
                            aria-hidden
                        />
                    ) : (
                        illustration
                    )}
                </div>
            ) : Icon ? (
                <div className="flex size-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <Icon className="size-7" />
                </div>
            ) : null}
            <h3 className="mt-4 text-base font-semibold text-foreground">
                {title}
            </h3>
            {description && (
                <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                    {description}
                </p>
            )}
            {children}
            {action && <div className="mt-6">{action}</div>}
        </div>
    );
}

export { EmptyState };
