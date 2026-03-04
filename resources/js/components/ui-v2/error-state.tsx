import * as React from 'react';

import { cn } from '@/lib/utils';

export interface ErrorStateProps {
    title?: string;
    description?: string;
    action?: React.ReactNode;
    className?: string;
    icon?: React.ComponentType<{ className?: string }>;
}

const defaultTitle = 'Something went wrong';
const defaultDescription =
    'An error occurred while loading this content. You can try again or go back.';

/**
 * Error state block for failed content (not full-page). Use for inline errors and retry UI.
 */
function ErrorState({
    title = defaultTitle,
    description = defaultDescription,
    action,
    className,
    icon: Icon,
}: ErrorStateProps) {
    return (
        <div
            data-slot="error-state"
            role="alert"
            className={cn(
                'flex min-h-[200px] flex-col items-center justify-center gap-4 rounded-xl border border-destructive/20 bg-destructive/5 p-8 text-center',
                className,
            )}
        >
            {Icon && (
                <div className="flex size-12 items-center justify-center rounded-full bg-destructive/10 text-destructive">
                    <Icon className="size-6" />
                </div>
            )}
            <h2 className="text-lg font-semibold text-foreground">{title}</h2>
            <p className="max-w-md text-sm text-muted-foreground">
                {description}
            </p>
            {action && <div className="flex gap-3">{action}</div>}
        </div>
    );
}

export { ErrorState };
