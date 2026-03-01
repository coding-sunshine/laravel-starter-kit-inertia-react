import SessionController from '@/actions/App/Http/Controllers/SessionController';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

const isErrorStatus = (s: string) =>
    /invalid|incorrect|error|failed|expired/i.test(s);

export default function Login({ status, canResetPassword }: LoginProps) {
    return (
        <AuthLayout
            title="Log in to your account"
            description="Enter your email and password below to log in"
        >
            <Head title="Log in" />

            {status && (
                <div
                    role="alert"
                    className={
                        isErrorStatus(status)
                            ? 'mb-4 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-center text-sm font-medium text-destructive'
                            : 'mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-center text-sm font-medium text-green-800 dark:border-green-800 dark:bg-green-950/30 dark:text-green-200'
                    }
                >
                    {status}
                </div>
            )}

            <Form
                {...SessionController.store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor="email" className="text-foreground">
                                    Email address
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="email@example.com"
                                    aria-describedby={errors.email ? 'email-error' : undefined}
                                    aria-invalid={!!errors.email}
                                />
                                <InputError
                                    id="email-error"
                                    message={errors.email}
                                />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center justify-between gap-2">
                                    <Label htmlFor="password" className="text-foreground">
                                        Password
                                    </Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="text-sm font-medium text-primary underline-offset-4 hover:underline"
                                            tabIndex={4}
                                            data-pan="auth-forgot-password-link"
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
                                    aria-describedby={errors.password ? 'password-error' : undefined}
                                    aria-invalid={!!errors.password}
                                />
                                <InputError
                                    id="password-error"
                                    message={errors.password}
                                />
                            </div>

                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                    aria-describedby="remember-label"
                                />
                                <Label
                                    id="remember-label"
                                    htmlFor="remember"
                                    className="text-foreground cursor-pointer font-normal"
                                >
                                    Remember me
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                size="lg"
                                className="mt-2 w-full gap-2"
                                tabIndex={5}
                                disabled={processing}
                                data-test="login-button"
                                data-pan="auth-login-button"
                            >
                                {processing && (
                                    <LoaderCircle
                                        className="size-4 shrink-0 animate-spin"
                                        aria-hidden
                                    />
                                )}
                                {processing ? 'Signing in…' : 'Log in'}
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Don't have an account?{' '}
                            <TextLink
                                href={register()}
                                className="font-medium text-primary underline-offset-4 hover:underline"
                                tabIndex={6}
                                data-pan="auth-sign-up-link"
                            >
                                Sign up
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
