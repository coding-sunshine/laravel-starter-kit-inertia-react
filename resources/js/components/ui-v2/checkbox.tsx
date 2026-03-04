'use client';

import { cn } from '@/lib/utils';
import { CheckIcon } from 'lucide-react';
import { Checkbox as CheckboxPrimitive } from 'radix-ui';
import * as React from 'react';

function Checkbox({
    className,
    ...props
}: React.ComponentProps<typeof CheckboxPrimitive.Root>) {
    return (
        <CheckboxPrimitive.Root
            data-slot="checkbox-v2"
            className={cn(
                'size-4 shrink-0 rounded-[4px] border border-[var(--color-neutral-300)] dark:border-[var(--color-neutral-600)]',
                'bg-transparent dark:bg-[var(--color-neutral-800)]',
                'data-[state=checked]:border-[var(--color-primary)] data-[state=checked]:bg-[var(--color-primary)] data-[state=checked]:text-[var(--color-primary-foreground)]',
                'outline-none focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-[var(--color-primary)]',
                'disabled:cursor-not-allowed disabled:opacity-50',
                'transition-[background-color,border-color] duration-[var(--duration-fast)]',
                className,
            )}
            {...props}
        >
            <CheckboxPrimitive.Indicator className="grid place-content-center text-current">
                <CheckIcon className="size-3.5" />
            </CheckboxPrimitive.Indicator>
        </CheckboxPrimitive.Root>
    );
}

export { Checkbox };
