import UserController from '@/actions/App/Http/Controllers/UserController';
import HoneypotFields from '@/components/honeypot-fields';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthSplitLayout from '@/layouts/auth/auth-split-layout';
import { login } from '@/routes';
import { type SharedData } from '@/types';
import { Form, Head, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useMemo, useState } from 'react';

export default function Register() {
    const { fleet_only_app: fleetOnlyApp } = usePage<SharedData>().props;

    const steps = useMemo(
        () =>
            fleetOnlyApp
                ? ['account', 'organization', 'fleet']
                : ['account', 'organization'],
        [fleetOnlyApp],
    );

    const [step, setStep] = useState(0);
    const isLastStep = step === steps.length - 1;

    const goToNext = () => {
        setStep((current) => Math.min(current + 1, steps.length - 1));
    };

    const goToPrevious = () => {
        setStep((current) => Math.max(current - 1, 0));
    };

    return (
        <AuthSplitLayout
            title="Create an account"
            description="Tell us about you and your fleet to get started."
        >
            <Head title="Register" />

            {steps.length > 1 && (
                <div
                    className="mb-4 flex gap-2"
                    aria-label="Registration progress"
                >
                    {steps.map((id, index) => (
                        <div
                            key={id}
                            className={`h-1.5 flex-1 rounded-full transition-colors ${
                                index <= step ? 'bg-primary' : 'bg-muted'
                            }`}
                            aria-hidden
                        />
                    ))}
                </div>
            )}

            <Form
                {...UserController.store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <HoneypotFields />

                        {/* Account step */}
                        {steps[step] === 'account' && (
                            <div className="grid gap-6">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        type="text"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="name"
                                        name="name"
                                        placeholder="Full name"
                                    />
                                    <InputError
                                        message={errors.name}
                                        className="mt-2"
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        required
                                        tabIndex={2}
                                        autoComplete="email"
                                        name="email"
                                        placeholder="email@example.com"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password">Password</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        required
                                        tabIndex={3}
                                        autoComplete="new-password"
                                        name="password"
                                        placeholder="Password"
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirm password
                                    </Label>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        required
                                        tabIndex={4}
                                        autoComplete="new-password"
                                        name="password_confirmation"
                                        placeholder="Confirm password"
                                    />
                                    <InputError
                                        message={errors.password_confirmation}
                                    />
                                </div>
                            </div>
                        )}

                        {/* Organization step – frontend only for now */}
                        {steps[step] === 'organization' && (
                            <div className="grid gap-6">
                                <div className="grid gap-2">
                                    <Label htmlFor="organization_name">
                                        Organization name
                                    </Label>
                                    <Input
                                        id="organization_name"
                                        type="text"
                                        name="organization_name"
                                        placeholder="Your company or fleet name"
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="role">
                                        Your role (optional)
                                    </Label>
                                    <Input
                                        id="role"
                                        type="text"
                                        name="role"
                                        placeholder="Fleet manager, owner, dispatcher…"
                                    />
                                </div>
                            </div>
                        )}

                        {/* Fleet step – only shown when fleetOnlyApp is enabled */}
                        {fleetOnlyApp && steps[step] === 'fleet' && (
                            <div className="grid gap-6">
                                <div className="grid gap-2">
                                    <Label htmlFor="fleet_size">
                                        Fleet size (approximate)
                                    </Label>
                                    <Input
                                        id="fleet_size"
                                        type="number"
                                        min={0}
                                        name="fleet_size"
                                        placeholder="e.g. 25"
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="primary_use_case">
                                        Primary use case
                                    </Label>
                                    <Input
                                        id="primary_use_case"
                                        type="text"
                                        name="primary_use_case"
                                        placeholder="e.g. last-mile delivery, construction"
                                    />
                                </div>
                            </div>
                        )}

                        <div className="mt-4 flex flex-col gap-3">
                            {!isLastStep ? (
                                <>
                                    <Button
                                        type="button"
                                        onClick={goToNext}
                                        className="w-full"
                                        data-pan="auth-register-next-step"
                                    >
                                        Continue
                                    </Button>
                                    {step > 0 && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            onClick={goToPrevious}
                                            className="w-full"
                                        >
                                            Back
                                        </Button>
                                    )}
                                </>
                            ) : (
                                <Button
                                    type="submit"
                                    className="w-full"
                                    tabIndex={5}
                                    data-test="register-user-button"
                                    data-pan="auth-register-button"
                                    disabled={processing}
                                >
                                    {processing && (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    )}
                                    Create account
                                </Button>
                            )}
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink
                                href={login()}
                                tabIndex={6}
                                data-pan="auth-log-in-link"
                            >
                                Log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </AuthSplitLayout>
    );
}
