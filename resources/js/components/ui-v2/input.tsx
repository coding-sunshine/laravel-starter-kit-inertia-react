'use client';

import { cn } from '@/lib/utils';
import * as React from 'react';

function Input({ className, type, ...props }: React.ComponentProps<'input'>) {
    return (
        <input
            type={type}
            data-slot="input-v2"
            className={cn(
                'h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-[var(--text-base)] outline-none',
                'border-[var(--color-neutral-300)] dark:border-[var(--color-neutral-600)]',
                'placeholder:text-[var(--color-neutral-500)]',
                'focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-[var(--color-primary)]',
                'disabled:pointer-events-none disabled:opacity-50',
                'aria-invalid:border-[var(--color-error)]',
                className,
            )}
            {...props}
        />
    );
}

export { Input };
