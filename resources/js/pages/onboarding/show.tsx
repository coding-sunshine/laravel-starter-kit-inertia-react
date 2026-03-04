import OnboardingController from '@/actions/App/Http/Controllers/OnboardingController';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { dashboard } from '@/routes';
import { Form, Head, Link, router } from '@inertiajs/react';
import {
    Bell,
    Check,
    LoaderCircle,
    Rocket,
    Sparkles,
    Truck,
    Users,
} from 'lucide-react';
import { useMemo, useState } from 'react';

const BASE_STEPS = [
    {
        id: 'welcome',
        title: 'Welcome',
        description: 'Complete a quick setup to get the most out of the app.',
        icon: Rocket,
    },
    {
        id: 'explore',
        title: 'Explore',
        description:
            'Your dashboard is ready. Add vehicles, drivers, and routes to manage your fleet.',
        icon: Sparkles,
    },
    {
        id: 'complete',
        title: "You're all set",
        description: 'Click below to finish and go to your dashboard.',
        icon: Check,
    },
];

const FLEET_SETUP_STEP = {
    id: 'fleet_setup',
    title: 'Fleet setup',
    description:
        'Add your first vehicle, driver, or alert to get started. You can skip and do this later.',
    icon: Truck,
};

interface OnboardingProps {
    status?: string;
    alreadyCompleted?: boolean;
    fleetOnlyApp?: boolean;
    initialStep?: number;
}

export default function OnboardingShow({
    status,
    alreadyCompleted,
    fleetOnlyApp = false,
    initialStep = 0,
}: OnboardingProps) {
    const STEPS = useMemo(
        () =>
            fleetOnlyApp
                ? [
                      BASE_STEPS[0],
                      FLEET_SETUP_STEP,
                      BASE_STEPS[1],
                      BASE_STEPS[2],
                  ]
                : BASE_STEPS,
        [fleetOnlyApp],
    );
    const [step, setStep] = useState(
        Math.min(initialStep, Math.max(0, STEPS.length - 1)),
    );
    const isLastStep = step === STEPS.length - 1;
    const isFleetSetupStep = fleetOnlyApp && STEPS[step]?.id === 'fleet_setup';
    const StepIcon = STEPS[step].icon;

    const saveProgressAndGoTo = (nextStep: number) => {
        router.put(
            '/onboarding',
            { current_step: nextStep },
            { preserveScroll: true },
        );
        setStep(nextStep);
    };

    return (
        <AuthLayout
            title={STEPS[step].title}
            description={
                alreadyCompleted
                    ? 'Review or run through onboarding again.'
                    : STEPS[step].description
            }
        >
            <Head
                title={alreadyCompleted ? 'Review onboarding' : 'Get started'}
            />

            {alreadyCompleted && (
                <div
                    className="body-sm mb-4 rounded-md bg-muted p-3 text-muted-foreground"
                    role="status"
                >
                    You&apos;ve already completed onboarding. You can run
                    through it again below.
                </div>
            )}
            {status && (
                <div
                    className="body-sm mb-4 rounded-md bg-green-50 p-3 text-green-800 dark:bg-green-900/20 dark:text-green-200"
                    role="alert"
                >
                    {status}
                </div>
            )}

            {/* Progress indicator */}
            <div className="mb-6 flex gap-2" data-pan="onboarding-progress">
                {STEPS.map((s, i) => (
                    <div
                        key={s.id}
                        className={`h-1.5 flex-1 rounded-full transition-colors ${
                            i <= step ? 'bg-primary' : 'bg-muted'
                        }`}
                        aria-hidden
                    />
                ))}
            </div>

            <div className="flex size-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                <StepIcon className="size-7" />
            </div>

            {isFleetSetupStep ? (
                <div className="mt-6 flex flex-col gap-3">
                    <Button
                        asChild
                        className="w-full gap-2"
                        data-pan="onboarding-add-vehicle"
                    >
                        <Link href="/fleet/vehicles/create" prefetch="click">
                            <Truck className="size-4" />
                            Add first vehicle
                        </Link>
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        className="w-full gap-2"
                        data-pan="onboarding-add-driver"
                    >
                        <Link href="/fleet/drivers/create" prefetch="click">
                            <Users className="size-4" />
                            Add first driver
                        </Link>
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        className="w-full gap-2"
                        data-pan="onboarding-add-alert"
                    >
                        <Link href="/fleet/alert-preferences" prefetch="click">
                            <Bell className="size-4" />
                            Set up first alert
                        </Link>
                    </Button>
                    <Button
                        variant="ghost"
                        onClick={() => saveProgressAndGoTo(step + 1)}
                        className="w-full"
                        data-pan="onboarding-skip-fleet"
                    >
                        Skip for now
                    </Button>
                    {step > 0 && (
                        <Button
                            variant="ghost"
                            onClick={() => setStep(step - 1)}
                            className="w-full"
                        >
                            Back
                        </Button>
                    )}
                </div>
            ) : !isLastStep ? (
                <div className="mt-6 flex flex-col gap-3">
                    <Button
                        onClick={() => saveProgressAndGoTo(step + 1)}
                        className="w-full"
                        data-pan="onboarding-next"
                    >
                        Next
                    </Button>
                    {step > 0 && (
                        <Button
                            variant="ghost"
                            onClick={() => setStep(step - 1)}
                            className="w-full"
                        >
                            Back
                        </Button>
                    )}
                </div>
            ) : (
                <Form
                    {...OnboardingController.store.form()}
                    className="mt-6 flex flex-col gap-4"
                >
                    {({ processing }) => (
                        <Button
                            type="submit"
                            disabled={processing}
                            className="w-full"
                            data-pan="onboarding-complete"
                        >
                            {processing ? (
                                <LoaderCircle className="size-4 animate-spin" />
                            ) : (
                                'Get started'
                            )}
                        </Button>
                    )}
                </Form>
            )}

            {fleetOnlyApp && step === 2 && !isLastStep && (
                <Button asChild variant="outline" className="mt-4 w-full">
                    <Link href={dashboard().url} prefetch="click">
                        Go to dashboard
                    </Link>
                </Button>
            )}
        </AuthLayout>
    );
}
