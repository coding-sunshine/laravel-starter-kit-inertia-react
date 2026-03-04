import type { ReactNode } from 'react';

interface FleetPageHeaderProps {
    title: string;
    description?: string;
    action?: ReactNode;
}

export function FleetPageHeader({
    title,
    description,
    action,
}: FleetPageHeaderProps) {
    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 className="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">
                    {title}
                </h1>
                {description && (
                    <p className="mt-1 text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {action && <div className="shrink-0">{action}</div>}
        </div>
    );
}
