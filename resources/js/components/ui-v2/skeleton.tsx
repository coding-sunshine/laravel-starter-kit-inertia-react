import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/lib/utils';

const skeletonVariants = cva('rounded-md', {
    variants: {
        variant: {
            default: 'bg-primary/10 animate-pulse',
            /** 3–5 line grey shimmer for AI response placeholders (replaces spinners) */
            shimmer:
                'animate-shimmer bg-[length:200%_100%] bg-[linear-gradient(90deg,hsl(var(--muted))_0%,hsl(var(--muted-foreground)/0.15)_50%,hsl(var(--muted))_100%)]',
        },
    },
    defaultVariants: {
        variant: 'default',
    },
});

function Skeleton({
    className,
    variant,
    ...props
}: React.ComponentProps<'div'> & VariantProps<typeof skeletonVariants>) {
    return (
        <div
            data-slot="skeleton"
            className={cn(skeletonVariants({ variant }), className)}
            {...props}
        />
    );
}

/** Multi-line shimmer block for AI response placeholder (3–5 lines). */
function SkeletonShimmerLines({
    lines = 4,
    className,
}: {
    lines?: number;
    className?: string;
}) {
    return (
        <div
            data-slot="skeleton-shimmer-lines"
            className={cn('flex flex-col gap-2', className)}
            aria-hidden
        >
            {Array.from({ length: lines }).map((_, i) => (
                <Skeleton
                    key={i}
                    variant="shimmer"
                    className={cn(
                        'h-4',
                        i === lines - 1 && lines > 1 ? 'w-3/4' : 'w-full',
                    )}
                />
            ))}
        </div>
    );
}

export { Skeleton, SkeletonShimmerLines, skeletonVariants };
