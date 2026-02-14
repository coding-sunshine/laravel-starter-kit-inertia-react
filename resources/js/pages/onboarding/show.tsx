import OnboardingController from '@/actions/App/Http/Controllers/OnboardingController';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

interface OnboardingProps {
    status?: string;
    alreadyCompleted?: boolean;
}

export default function OnboardingShow({
    status,
    alreadyCompleted,
}: OnboardingProps) {
    return (
        <AuthLayout
            title="Welcome"
            description={
                alreadyCompleted
                    ? 'Review or run through onboarding again.'
                    : 'Complete a quick step to get started.'
            }
        >
            <Head
                title={alreadyCompleted ? 'Review onboarding' : 'Get started'}
            />

            {alreadyCompleted && (
                <div className="mb-4 rounded-md bg-muted p-3 text-sm text-muted-foreground">
                    You&apos;ve already completed onboarding. You can run
                    through it again below.
                </div>
            )}
            {status && (
                <div className="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-200">
                    {status}
                </div>
            )}

            <Form
                {...OnboardingController.store.form()}
                className="flex flex-col gap-4"
            >
                {({ processing }) => (
                    <Button
                        type="submit"
                        disabled={processing}
                        className="w-full"
                    >
                        {processing ? (
                            <LoaderCircle className="size-4 animate-spin" />
                        ) : (
                            'Get started'
                        )}
                    </Button>
                )}
            </Form>
        </AuthLayout>
    );
}
