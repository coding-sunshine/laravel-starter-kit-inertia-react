import SessionController from '@/actions/App/Http/Controllers/SessionController';
import InputError, { ariaPropsForField } from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { register } from '@/routes';
import { request } from '@/routes/password';
import type { SharedData } from '@/types';
import { Form, Head, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

function validationErrorsFromProps(value: unknown): Record<string, string> {
    if (value === null || typeof value !== 'object' || Array.isArray(value)) {
        return {};
    }

    return Object.fromEntries(
        Object.entries(value as Record<string, unknown>).flatMap(([key, v]) => {
            if (typeof v === 'string') {
                return [[key, v] as const];
            }

            if (Array.isArray(v) && typeof v[0] === 'string') {
                return [[key, v[0]] as const];
            }

            return [];
        }),
    );
}

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const page = usePage<SharedData & { errors?: Record<string, string> }>();
    const pageErrors = validationErrorsFromProps(page.props.errors);

    return (
        <AuthLayout
            title="Log in to your account"
            description="Enter your email and password below to log in"
        >
            <Head title="Log in" />

            <Form
                {...SessionController.store.form()}
                resetOnSuccess={['password']}
                options={{ preserveState: false }}
                className="flex flex-col gap-6"
            >
                {({ processing, errors: formErrors }) => {
                    const errors = { ...pageErrors, ...formErrors };

                    return (
                        <>
                            <div className="grid gap-6">
                                {errors.email && (
                                    <div className="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                                        {errors.email}
                                    </div>
                                )}

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="email"
                                        placeholder="email@example.com"
                                        {...ariaPropsForField(
                                            'email',
                                            errors.email,
                                        )}
                                    />
                                    <InputError
                                        id="email-error"
                                        message={errors.email}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <div className="flex items-center">
                                        <Label htmlFor="password">
                                            Password
                                        </Label>
                                        {canResetPassword && (
                                            <TextLink
                                                href={request()}
                                                className="ml-auto text-sm"
                                                tabIndex={5}
                                            >
                                                Forgot password?
                                            </TextLink>
                                        )}
                                    </div>
                                    <Input
                                        id="password"
                                        type="password"
                                        name="password"
                                        required
                                        tabIndex={2}
                                        autoComplete="current-password"
                                        placeholder="Password"
                                        {...ariaPropsForField(
                                            'password',
                                            errors.password,
                                        )}
                                    />
                                    <InputError
                                        id="password-error"
                                        message={errors.password}
                                    />
                                </div>

                                <Button
                                    type="submit"
                                    className="mt-4 w-full"
                                    tabIndex={3}
                                    disabled={processing}
                                    data-test="login-button"
                                    data-pan="auth-login-button"
                                >
                                    {processing && (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    )}
                                    Log in
                                </Button>
                            </div>

                            <div className="text-center text-sm text-muted-foreground">
                                Don't have an account?{' '}
                                <TextLink
                                    href={register()}
                                    tabIndex={5}
                                    data-pan="auth-sign-up-link"
                                >
                                    Sign up
                                </TextLink>
                            </div>
                        </>
                    );
                }}
            </Form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </AuthLayout>
    );
}
