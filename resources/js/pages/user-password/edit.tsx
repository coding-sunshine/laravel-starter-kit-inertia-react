import UserPasswordController from '@/actions/App/Http/Controllers/UserPasswordController';
import HeadingSmall from '@/components/heading-small';
import InputError, {
    ariaPropsForField,
    normalizeErrorMessage,
    validationErrorsFromProps,
} from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { cn } from '@/lib/utils';
import { edit } from '@/routes/password';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Form, Head, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    CheckCircle2,
    Eye,
    EyeOff,
    LoaderCircle,
    ShieldCheck,
} from 'lucide-react';
import { type RefObject, useMemo, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Password settings',
        href: edit().url,
    },
];

interface PasswordFieldProps {
    id: string;
    name: string;
    label: string;
    placeholder: string;
    autoComplete: string;
    error?: string;
    inputRef?: RefObject<HTMLInputElement | null>;
    autoFocus?: boolean;
    hint?: string;
}

function PasswordField({
    id,
    name,
    label,
    placeholder,
    autoComplete,
    error,
    inputRef,
    autoFocus,
    hint,
}: PasswordFieldProps) {
    const [visible, setVisible] = useState(false);

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <div className="relative">
                <Input
                    id={id}
                    ref={inputRef}
                    name={name}
                    type={visible ? 'text' : 'password'}
                    className={cn(
                        'h-10 pr-10 transition-[border-color,box-shadow]',
                        error && 'border-destructive',
                    )}
                    autoComplete={autoComplete}
                    placeholder={placeholder}
                    autoFocus={autoFocus}
                    {...ariaPropsForField(id, error)}
                />
                <button
                    type="button"
                    className="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-muted-foreground transition-colors hover:text-foreground"
                    onClick={() => setVisible((current) => !current)}
                    aria-label={visible ? 'Hide password' : 'Show password'}
                    tabIndex={-1}
                >
                    {visible ? (
                        <EyeOff className="size-4" />
                    ) : (
                        <Eye className="size-4" />
                    )}
                </button>
            </div>
            {hint && !error ? (
                <p className="text-xs text-muted-foreground">{hint}</p>
            ) : null}
            <InputError id={`${id}-error`} message={error} />
        </div>
    );
}

export default function Password() {
    const { flash, props } = usePage<
        SharedData & {
            errors?: Record<string, unknown>;
        }
    >();
    const { auth } = props;
    const pageErrors = useMemo(
        () => validationErrorsFromProps(props.errors),
        [props.errors],
    );

    const passwordInputRef = useRef<HTMLInputElement>(null);
    const currentPasswordInputRef = useRef<HTMLInputElement>(null);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);
    const statusMessage =
        typeof flash?.status === 'string' ? flash.status : successMessage;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Password settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Update password"
                        description={`Change the password for ${auth.user.email}. You’ll need your current password to confirm.`}
                    />

                    {statusMessage ? (
                        <div
                            role="status"
                            className="flex items-start gap-3 rounded-lg border border-emerald-200/80 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100"
                        >
                            <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                            <div>
                                <p className="font-medium">Password updated</p>
                                <p className="mt-0.5 text-emerald-800/80 dark:text-emerald-200/80">
                                    {statusMessage}
                                </p>
                            </div>
                        </div>
                    ) : null}

                    <Form
                        {...UserPasswordController.update.form()}
                        disableWhileProcessing
                        options={{
                            preserveScroll: true,
                        }}
                        resetOnError={[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]}
                        resetOnSuccess
                        onSuccess={(page) => {
                            setSuccessMessage(
                                typeof page.flash?.status === 'string'
                                    ? page.flash.status
                                    : 'Your password has been updated.',
                            );
                        }}
                        onError={(errors) => {
                            if (errors.password) {
                                passwordInputRef.current?.focus();
                            }

                            if (errors.current_password) {
                                currentPasswordInputRef.current?.focus();
                            }
                        }}
                        className="space-y-6"
                    >
                        {({ errors: formErrors, processing }) => {
                            const errors = {
                                ...pageErrors,
                                ...Object.fromEntries(
                                    Object.entries(formErrors).map(
                                        ([key, value]) => [
                                            key,
                                            normalizeErrorMessage(value) ?? '',
                                        ],
                                    ),
                                ),
                            };

                            const hasErrors = Object.values(errors).some(
                                Boolean,
                            );

                            return (
                                <>
                                    {/* Helps the browser password manager target the signed-in account */}
                                    <input
                                        type="email"
                                        name="username"
                                        value={auth.user.email}
                                        autoComplete="username"
                                        readOnly
                                        tabIndex={-1}
                                        aria-hidden="true"
                                        className="sr-only"
                                    />

                                    {hasErrors ? (
                                        <div
                                            role="alert"
                                            className="flex items-start gap-3 rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive"
                                        >
                                            <AlertCircle className="mt-0.5 size-4 shrink-0" />
                                            <div>
                                                <p className="font-medium">
                                                    Could not update password
                                                </p>
                                                <p className="mt-0.5 opacity-80">
                                                    Please review the fields
                                                    below and try again.
                                                </p>
                                            </div>
                                        </div>
                                    ) : null}

                                    <div className="space-y-5">
                                        <PasswordField
                                            id="current_password"
                                            name="current_password"
                                            label="Current password"
                                            placeholder="Enter your current password"
                                            autoComplete="current-password"
                                            error={errors.current_password}
                                            inputRef={currentPasswordInputRef}
                                            autoFocus
                                        />

                                        <div className="space-y-5 border-t border-border/60 pt-5">
                                            <div className="flex items-start gap-2.5 rounded-lg bg-muted/40 px-3 py-2.5">
                                                <ShieldCheck className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                                <p className="text-xs leading-relaxed text-muted-foreground">
                                                    Use at least 8 characters
                                                    with a mix of letters,
                                                    numbers, and symbols.
                                                </p>
                                            </div>

                                            <PasswordField
                                                id="password"
                                                name="password"
                                                label="New password"
                                                placeholder="Enter a new password"
                                                autoComplete="new-password"
                                                error={errors.password}
                                                inputRef={passwordInputRef}
                                            />

                                            <PasswordField
                                                id="password_confirmation"
                                                name="password_confirmation"
                                                label="Confirm new password"
                                                placeholder="Re-enter your new password"
                                                autoComplete="off"
                                                error={
                                                    errors.password_confirmation
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3 border-t border-border/60 pt-5">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            data-test="update-password-button"
                                            data-pan="settings-password-save"
                                            className="min-w-36"
                                        >
                                            {processing ? (
                                                <>
                                                    <LoaderCircle className="size-4 animate-spin" />
                                                    Saving…
                                                </>
                                            ) : (
                                                'Save password'
                                            )}
                                        </Button>
                                    </div>
                                </>
                            );
                        }}
                    </Form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
