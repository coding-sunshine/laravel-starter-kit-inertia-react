import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Circle } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Get Started', href: '/signup/onboarding' },
];

interface Step {
    key: string;
    label: string;
    description: string;
    completed: boolean;
    completed_at: string | null;
}

interface Props {
    steps: Step[];
    completedCount: number;
    totalSteps: number;
}

export default function SignupOnboarding({ steps, completedCount, totalSteps }: Props) {
    const percentage = totalSteps > 0 ? Math.round((completedCount / totalSteps) * 100) : 0;

    function markComplete(stepKey: string) {
        router.post(`/signup/onboarding/${stepKey}/complete`, {}, {
            preserveScroll: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Getting Started" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div>
                    <h2 className="text-2xl font-bold">Welcome to Fusion CRM!</h2>
                    <p className="mt-1 text-muted-foreground">
                        Complete these steps to get the most out of your new CRM.
                    </p>
                </div>

                {/* Progress bar */}
                <div className="space-y-2">
                    <div className="flex items-center justify-between text-sm">
                        <span className="font-medium">{completedCount} of {totalSteps} steps complete</span>
                        <span className="text-muted-foreground">{percentage}%</span>
                    </div>
                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                        <div
                            className="h-full rounded-full bg-primary transition-all duration-500"
                            style={{ width: `${percentage}%` }}
                        />
                    </div>
                </div>

                {/* Steps */}
                <div className="space-y-3">
                    {steps.map((step) => (
                        <div
                            key={step.key}
                            className={`flex items-start gap-4 rounded-xl border p-4 transition-colors ${
                                step.completed ? 'border-primary/20 bg-primary/5' : 'border-border bg-card'
                            }`}
                        >
                            <div className="mt-0.5 shrink-0">
                                {step.completed ? (
                                    <CheckCircle2 className="h-5 w-5 text-primary" />
                                ) : (
                                    <Circle className="h-5 w-5 text-muted-foreground" />
                                )}
                            </div>

                            <div className="flex-1">
                                <p className={`font-medium ${step.completed ? 'line-through text-muted-foreground' : ''}`}>
                                    {step.label}
                                </p>
                                <p className="mt-0.5 text-sm text-muted-foreground">{step.description}</p>
                                {step.completed_at && (
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Completed {new Date(step.completed_at).toLocaleDateString()}
                                    </p>
                                )}
                            </div>

                            {!step.completed && (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => markComplete(step.key)}
                                    data-pan={`onboarding-complete-${step.key}`}
                                >
                                    Mark done
                                </Button>
                            )}
                        </div>
                    ))}
                </div>

                {completedCount === totalSteps && (
                    <div className="rounded-xl border border-primary/30 bg-primary/5 p-6 text-center">
                        <CheckCircle2 className="mx-auto mb-2 h-8 w-8 text-primary" />
                        <h3 className="text-lg font-semibold">You're all set!</h3>
                        <p className="text-muted-foreground">All onboarding steps are complete. Enjoy Fusion CRM.</p>
                        <Button className="mt-4" onClick={() => router.visit('/dashboard')} data-pan="onboarding-go-dashboard">
                            Go to Dashboard
                        </Button>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
