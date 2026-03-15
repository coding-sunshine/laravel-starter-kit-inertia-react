import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { Head, Link } from '@inertiajs/react';
import { Check, Zap } from 'lucide-react';

interface Plan {
    slug: string;
    name: string;
    description: string;
    price: number;
    setup_fee: number;
    currency: string;
    interval: string;
    features: { flags?: string[]; max_users?: number | null; ai_credits?: number };
    max_users: number | null;
    ai_credits: number;
}

interface Props {
    plans: Plan[];
}

function formatPrice(amount: number, currency: string): string {
    return new Intl.NumberFormat('en-AU', {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

const FEATURE_LABELS: Record<string, string> = {
    PropertyAccessFeature: 'Property CRM',
    AiToolsFeature: 'AI Tools',
    AiBotsCustomFeature: 'Custom AI Bots',
    BotInABoxFeature: 'Bot in a Box',
    SprFeature: 'SPR Module',
    ApiAccessFeature: 'API Access',
    WebsitesFeature: 'Websites',
    WordPressSitesFeature: 'WordPress Sites',
    PhpSitesFeature: 'PHP Sites',
    CampaignWebsitesFeature: 'Campaign Websites',
    FlyersFeature: 'Flyer Generator',
    XeroIntegrationFeature: 'Xero Integration',
    AdvancedReportsFeature: 'Advanced Reports',
};

export default function SignupIndex({ plans }: Props) {
    return (
        <AuthLayout title="Choose your plan" description="Start your Fusion CRM journey. Cancel anytime.">
            <Head title="Sign Up – Choose a Plan" />

            <div className="w-full space-y-6">
                <div className="grid gap-4 sm:grid-cols-1 lg:grid-cols-3">
                    {plans.map((plan, idx) => (
                        <div
                            key={plan.slug}
                            className={`relative flex flex-col rounded-xl border p-6 shadow-sm transition-shadow hover:shadow-md ${
                                idx === 1
                                    ? 'border-primary bg-primary/5 ring-2 ring-primary'
                                    : 'border-border bg-card'
                            }`}
                        >
                            {idx === 1 && (
                                <span className="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary px-3 py-0.5 text-xs font-semibold text-white">
                                    Most Popular
                                </span>
                            )}

                            <div className="mb-4">
                                <h3 className="text-lg font-semibold">{plan.name}</h3>
                                <p className="mt-1 text-sm text-muted-foreground">{plan.description}</p>
                            </div>

                            <div className="mb-6">
                                <span className="text-3xl font-bold">
                                    {formatPrice(plan.price, plan.currency)}
                                </span>
                                <span className="text-sm text-muted-foreground">/{plan.interval}</span>
                                {plan.setup_fee > 0 && (
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        + {formatPrice(plan.setup_fee, plan.currency)} setup fee
                                    </p>
                                )}
                            </div>

                            <ul className="mb-6 flex-1 space-y-2">
                                {(plan.features.flags ?? []).map((flag) => (
                                    <li key={flag} className="flex items-center gap-2 text-sm">
                                        <Check className="h-4 w-4 shrink-0 text-primary" />
                                        {FEATURE_LABELS[flag] ?? flag}
                                    </li>
                                ))}
                                {plan.ai_credits > 0 && (
                                    <li className="flex items-center gap-2 text-sm">
                                        <Zap className="h-4 w-4 shrink-0 text-amber-500" />
                                        {plan.ai_credits} AI credits/period
                                    </li>
                                )}
                                {plan.max_users !== null && (
                                    <li className="flex items-center gap-2 text-sm">
                                        <Check className="h-4 w-4 shrink-0 text-primary" />
                                        Up to {plan.max_users} team members
                                    </li>
                                )}
                                {plan.max_users === null && (
                                    <li className="flex items-center gap-2 text-sm">
                                        <Check className="h-4 w-4 shrink-0 text-primary" />
                                        Unlimited team members
                                    </li>
                                )}
                            </ul>

                            <Link href={`/signup/register?plan=${plan.slug}`}>
                                <Button
                                    className="w-full"
                                    variant={idx === 1 ? 'default' : 'outline'}
                                    data-pan={`signup-plan-${plan.slug}`}
                                >
                                    Get started
                                </Button>
                            </Link>
                        </div>
                    ))}
                </div>

                <p className="text-center text-xs text-muted-foreground">
                    By signing up you agree to our{' '}
                    <a href="/legal/terms" className="underline hover:text-foreground">
                        Terms of Service
                    </a>{' '}
                    and{' '}
                    <a href="/legal/privacy" className="underline hover:text-foreground">
                        Privacy Policy
                    </a>
                    .
                </p>
            </div>
        </AuthLayout>
    );
}
