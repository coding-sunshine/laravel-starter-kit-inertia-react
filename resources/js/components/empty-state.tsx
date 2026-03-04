import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

export interface EmptyStateProps {
    icon: LucideIcon;
    title: string;
    description?: string;
    action?: ReactNode;
    /** Optional illustration: image src (e.g. /images/empty/vehicles.svg) or ReactNode */
    illustration?: string | ReactNode;
    className?: string;
}

/**
 * Shared empty state for list/index pages. Use when a list has no items.
 * Use illustration for fleet/vehicles/data-specific empty states (see public/images/empty/).
 */
export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    illustration,
    className = '',
}: EmptyStateProps) {
    return (
        <div
            className={`flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-muted-foreground/20 bg-muted/20 px-6 py-16 text-center ${className}`}
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
            ) : (
                <div className="flex size-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <Icon className="size-7" />
                </div>
            )}
            <h3 className="mt-4 text-base font-semibold text-foreground">
                {title}
            </h3>
            {description && (
                <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                    {description}
                </p>
            )}
            {action && <div className="mt-6">{action}</div>}
        </div>
    );
}
