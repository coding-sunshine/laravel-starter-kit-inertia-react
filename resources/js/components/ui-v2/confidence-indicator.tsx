import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/lib/utils';

const confidenceVariants = cva(
    'inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-xs font-medium',
    {
        variants: {
            level: {
                high: 'border-emerald-500/50 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
                medium: 'border-amber-500/50 bg-amber-500/10 text-amber-800 dark:text-amber-400',
                low: 'border-red-500/50 bg-red-500/10 text-red-700 dark:text-red-400',
            },
        },
        defaultVariants: {
            level: 'high',
        },
    },
);

function ConfidenceIndicator({
    className,
    level,
    label,
    children,
    ...props
}: React.ComponentProps<'span'> &
    VariantProps<typeof confidenceVariants> & {
        label?: string;
    }) {
    return (
        <span
            data-slot="confidence-indicator"
            className={cn(confidenceVariants({ level }), className)}
            title={label}
            {...props}
        >
            {children ?? label}
        </span>
    );
}

/** Source citation chip (e.g. "Help: Getting started"). */
function SourceCitation({
    href,
    children,
    className,
}: {
    href?: string;
    children: React.ReactNode;
    className?: string;
}) {
    const Comp = href ? 'a' : 'span';
    return (
        <Comp
            data-slot="source-citation"
            href={href}
            className={cn(
                'text-xs text-muted-foreground underline-offset-2 hover:text-foreground hover:underline',
                className,
            )}
        >
            {children}
        </Comp>
    );
}

export { ConfidenceIndicator, confidenceVariants, SourceCitation };
