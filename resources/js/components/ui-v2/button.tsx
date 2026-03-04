'use client';

import { cn } from '@/lib/utils';
import { cva, type VariantProps } from 'class-variance-authority';
import { Slot } from 'radix-ui';
import * as React from 'react';

const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-[color,box-shadow,opacity] duration-[var(--duration-fast,100ms)] disabled:pointer-events-none disabled:opacity-50 [&_svg]:shrink-0 outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-primary)]',
    {
        variants: {
            variant: {
                default:
                    'bg-[var(--color-primary)] text-[var(--color-primary-foreground)] hover:opacity-90',
                destructive:
                    'bg-[var(--color-error)] text-[var(--color-error-foreground)] hover:opacity-90',
                outline:
                    'border border-[var(--color-neutral-300)] dark:border-[var(--color-neutral-600)] bg-transparent hover:bg-[var(--color-neutral-100)] dark:hover:bg-[var(--color-neutral-800)]',
                secondary:
                    'bg-[var(--color-neutral-200)] dark:bg-[var(--color-neutral-700)] text-[var(--color-neutral-900)] dark:text-[var(--color-neutral-100)] hover:opacity-90',
                ghost: 'hover:bg-[var(--color-neutral-100)] dark:hover:bg-[var(--color-neutral-800)]',
                link: 'text-[var(--color-primary)] underline-offset-4 hover:underline',
            },
            size: {
                default: 'h-9 px-4 py-2',
                sm: 'h-8 gap-1.5 px-3',
                lg: 'h-10 px-6',
                icon: 'size-9',
            },
        },
        defaultVariants: { variant: 'default', size: 'default' },
    },
);

export interface ButtonProps
    extends
        React.ComponentProps<'button'>,
        VariantProps<typeof buttonVariants> {
    asChild?: boolean;
}

function Button({
    className,
    variant = 'default',
    size = 'default',
    asChild = false,
    ...props
}: ButtonProps) {
    const Comp = asChild ? Slot.Root : 'button';
    return (
        <Comp
            data-slot="button-v2"
            className={cn(buttonVariants({ variant, size, className }))}
            {...props}
        />
    );
}

export { Button, buttonVariants };
