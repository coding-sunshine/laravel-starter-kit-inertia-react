import { Button } from '@/components/ui/button';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

interface FleetEmptyStateProps {
    icon: LucideIcon;
    title: string;
    description?: string;
    action?: ReactNode;
    className?: string;
}

export function FleetEmptyState({
    icon: Icon,
    title,
    description,
    action,
    className = '',
}: FleetEmptyStateProps) {
    return (
        <div
            className={`flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-muted-foreground/20 bg-muted/20 px-6 py-16 text-center ${className}`}
        >
            <div className="flex size-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                <Icon className="size-7" />
            </div>
            <h3 className="mt-4 text-base font-semibold text-foreground">{title}</h3>
            {description && (
                <p className="mt-1 max-w-sm text-sm text-muted-foreground">{description}</p>
            )}
            {action && <div className="mt-6">{action}</div>}
        </div>
    );
}
