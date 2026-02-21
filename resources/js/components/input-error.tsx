import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'react';

interface InputErrorProps extends HTMLAttributes<HTMLParagraphElement> {
    message?: string;
    /** ID for aria-describedby on the associated input. Use e.g. `${fieldName}-error` */
    id?: string;
}

export default function InputError({
    message,
    id,
    className = '',
    ...props
}: InputErrorProps) {
    return message ? (
        <p
            {...props}
            id={id}
            role="alert"
            aria-live="polite"
            className={cn('text-sm text-red-600 dark:text-red-400', className)}
        >
            {message}
        </p>
    ) : null;
}

/**
 * Helper to build aria props for an input with validation errors.
 * Usage: <Input {...ariaPropsForField('email', errors?.email)} />
 */
export function ariaPropsForField(
    fieldName: string,
    error?: string,
): { 'aria-invalid'?: boolean; 'aria-describedby'?: string } {
    const hasError = Boolean(error);
    return {
        ...(hasError && { 'aria-invalid': true }),
        ...(hasError && { 'aria-describedby': `${fieldName}-error` }),
    };
}
