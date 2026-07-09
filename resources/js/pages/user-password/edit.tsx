import UserPasswordController from '@/actions/App/Http/Controllers/UserPasswordController';
import InputError, {
    ariaPropsForField,
    normalizeErrorMessage,
    validationErrorsFromProps,
} from '@/components/input-error';
import {
    Alert,
    AlertDescription,
    AlertTitle,
} from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Form, Head, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    CheckCircle2,
    Eye,
    EyeOff,
    KeyRound,
    LoaderCircle,
} from 'lucide-react';
import { type RefObject, useMemo, useRef, useState } from 'react';

import { edit } from '@/routes/password';

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
}

function PasswordField({
    id,
    name,
    label,
    placeholder,
    autoComplete,
    error,
    inputRef,
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
                    className="pr-10"
                    autoComplete={autoComplete}
                    placeholder={placeholder}
                    {...ariaPropsForField(id, error)}
                />
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="absolute top-0 right-0 h-9 w-9 text-muted-foreground hover:text-foreground"
                    onClick={() => setVisible((current) => !current)}
                    aria-label={visible ? 'Hide password' : 'Show password'}
                    tabIndex={-1}
                >
                    {visible ? (
                        <EyeOff className="size-4" />
                    ) : (
                        <Eye className="size-4" />
                    )}
                </Button>
            </div>
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
    const statusMessage = flash?.status ?? successMessage;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Password settings" />

            <SettingsLayout>
                <Card>
                    <CardHeader>
                        <div className="flex items-start gap-3">
                            <div className="flex size-10 shrink-0 items-center justify-center rounded-lg border bg-muted/50">
                                <KeyRound className="size-5 text-muted-foreground" />
                            </div>
                            <div className="space-y-1">
                                <CardTitle>Update password</CardTitle>
                                <CardDescription>
                                    Change the password for{' '}
                                    <span className="font-medium text-foreground">
                                        {auth.user.email}
                                    </span>
                                    . You will need your current password to
                                    confirm this change.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="space-y-6">
                        {statusMessage && (
                            <Alert
                                role="status"
                                className="border-green-200 bg-green-50 text-green-900 dark:border-green-900/50 dark:bg-green-950/40 dark:text-green-100"
                            >
                                <CheckCircle2 className="text-green-600 dark:text-green-400" />
                                <AlertTitle>Password updated</AlertTitle>
                                <AlertDescription>
                                    {statusMessage}
                                </AlertDescription>
                            </Alert>
                        )}

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
                            {({
                                errors: formErrors,
                                processing,
                            }) => {
                                const errors = {
                                    ...pageErrors,
                                    ...Object.fromEntries(
                                        Object.entries(formErrors).map(
                                            ([key, value]) => [
                                                key,
                                                normalizeErrorMessage(value) ??
                                                    '',
                                            ],
                                        ),
                                    ),
                                };

                                const hasErrors = Object.values(errors).some(
                                    Boolean,
                                );

                                return (
                                    <>
                                        {hasErrors && (
                                            <Alert variant="destructive">
                                                <AlertCircle />
                                                <AlertTitle>
                                                    Could not update password
                                                </AlertTitle>
                                                <AlertDescription>
                                                    Please review the fields
                                                    below and try again.
                                                </AlertDescription>
                                            </Alert>
                                        )}

                                        <div className="space-y-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor="username">
                                                    Account
                                                </Label>
                                                <Input
                                                    id="username"
                                                    name="username"
                                                    type="email"
                                                    value={auth.user.email}
                                                    readOnly
                                                    tabIndex={-1}
                                                    autoComplete="username"
                                                    className="bg-muted/50"
                                                />
                                                <p className="text-xs text-muted-foreground">
                                                    Only your signed-in account
                                                    can be updated from this
                                                    page.
                                                </p>
                                            </div>

                                            <PasswordField
                                                id="current_password"
                                                name="current_password"
                                                label="Current password"
                                                placeholder="Enter your current password"
                                                autoComplete="current-password"
                                                error={
                                                    errors.current_password
                                                }
                                                inputRef={
                                                    currentPasswordInputRef
                                                }
                                            />

                                            <div className="border-t pt-4">
                                                <p className="mb-4 text-sm text-muted-foreground">
                                                    Choose a strong password
                                                    with at least 8 characters,
                                                    including letters, numbers,
                                                    and symbols.
                                                </p>

                                                <div className="space-y-4">
                                                    <PasswordField
                                                        id="password"
                                                        name="password"
                                                        label="New password"
                                                        placeholder="Enter a new password"
                                                        autoComplete="new-password"
                                                        error={errors.password}
                                                        inputRef={
                                                            passwordInputRef
                                                        }
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
                                        </div>

                                        <div className="flex items-center gap-3 border-t pt-4">
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                data-test="update-password-button"
                                                data-pan="settings-password-save"
                                            >
                                                {processing && (
                                                    <LoaderCircle className="size-4 animate-spin" />
                                                )}
                                                Save password
                                            </Button>
                                        </div>
                                    </>
                                );
                            }}
                        </Form>
                    </CardContent>
                </Card>
            </SettingsLayout>
        </AppLayout>
    );
}
