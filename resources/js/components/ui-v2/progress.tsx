'use client';

import { cn } from '@/lib/utils';
import * as React from 'react';

interface ProgressProps extends React.ComponentProps<'div'> {
    value?: number;
    max?: number;
}

function Progress({
    className,
    value = 0,
    max = 100,
    ...props
}: ProgressProps) {
    const pct = Math.min(100, Math.max(0, max ? (value / max) * 100 : value));
    return (
        <div
            role="progressbar"
            aria-valuenow={value}
            aria-valuemin={0}
            aria-valuemax={max}
            data-slot="progress-v2"
            className={cn(
                'h-2 w-full overflow-hidden rounded-full bg-[var(--color-neutral-200)] dark:bg-[var(--color-neutral-700)]',
                className,
            )}
            {...props}
        >
            <div
                className="h-full rounded-full bg-[var(--color-primary)] transition-transform duration-[var(--duration-normal)]"
                style={{ width: `${pct}%` }}
            />
        </div>
    );
}

export { Progress };
