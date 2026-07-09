import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'react';

interface InputErrorProps extends HTMLAttributes<HTMLParagraphElement> {
    message?: string | string[];
    /** ID for aria-describedby on the associated input. Use e.g. `${fieldName}-error` */
    id?: string;
}

export function normalizeErrorMessage(
    message?: string | string[],
): string | undefined {
    if (!message) {
        return undefined;
    }

    if (typeof message === 'string') {
        return message;
    }

    return message[0];
}

export function validationErrorsFromProps(
    value: unknown,
): Record<string, string> {
    if (value === null || typeof value !== 'object' || Array.isArray(value)) {
        return {};
    }

    return Object.fromEntries(
        Object.entries(value as Record<string, unknown>).flatMap(
            ([key, fieldValue]) => {
                const message = normalizeErrorMessage(
                    fieldValue as string | string[] | undefined,
                );

                return message ? [[key, message] as const] : [];
            },
        ),
    );
}

export default function InputError({
    message,
    id,
    className = '',
    ...props
}: InputErrorProps) {
    const normalizedMessage = normalizeErrorMessage(message);

    return normalizedMessage ? (
        <p
            {...props}
            id={id}
            role="alert"
            aria-live="polite"
            className={cn('text-sm text-destructive', className)}
        >
            {normalizedMessage}
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
