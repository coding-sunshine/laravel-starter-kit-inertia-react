import CreditBalanceCard from '@/components/billing/credit-balance-card';
import CurrentPlanCard from '@/components/billing/current-plan-card';
import RecentInvoicesCard from '@/components/billing/recent-invoices-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { BarChart3, Bot } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Billing', href: '/billing' }];

interface Props {
    organization: { id: number; name: string; billing_email?: string };
    creditBalance: number;
    activePlan: { id: number; name: string } | null;
    isOnTrial: boolean;
    invoices: {
        id: number;
        number: string;
        status: string;
        total: number;
        currency: string;
    }[];
    usageChartData?: { month: string; credits: number }[];
    plans?: {
        id: number;
        name: string;
        description?: string;
        price: number;
        currency: string;
        interval: string;
        is_per_seat?: boolean;
        price_per_seat?: number | null;
    }[];
}

export default function BillingIndex() {
    const {
        creditBalance,
        activePlan,
        isOnTrial,
        invoices,
        usageChartData,
        plans,
    } = usePage<Props & SharedData>().props;

    const recommendPlanPrompt = encodeURIComponent(
        'Based on my current usage and needs, recommend a billing plan. Explain the options and which plan fits best.',
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h2 className="heading-4 text-foreground">Billing</h2>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/chat?prompt=${encodeURIComponent('Explain my current billing usage, credits, and invoices. What do I have and what should I know?')}`}
                                className="inline-flex items-center gap-2"
                                data-pan="billing-explain-usage"
                            >
                                <Bot className="size-4" />
                                Explain my usage
                            </Link>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            asChild
                            data-pan="billing-recommend-plan"
                        >
                            <Link
                                href={`/chat?prompt=${recommendPlanPrompt}`}
                                className="inline-flex items-center gap-2"
                            >
                                <Bot className="size-4" />
                                Recommend plan
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <CurrentPlanCard
                        activePlan={activePlan}
                        isOnTrial={isOnTrial}
                    />
                    <CreditBalanceCard creditBalance={creditBalance} />
                </div>

                {usageChartData && usageChartData.length > 0 && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <BarChart3 className="size-4" />
                                Usage
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-4">
                                {usageChartData.map(({ month, credits }) => (
                                    <div
                                        key={month}
                                        className="rounded-lg border bg-muted/30 px-3 py-2 text-sm"
                                    >
                                        <span className="text-muted-foreground">
                                            {month}
                                        </span>
                                        <span className="ml-2 font-medium">
                                            {credits} credits
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {plans && plans.length > 0 && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">
                                Plan comparison
                            </CardTitle>
                            <p className="text-sm text-muted-foreground">
                                Compare features and pricing to find the right
                                plan.
                            </p>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto rounded-md border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="p-3 text-left font-medium">
                                                Plan
                                            </th>
                                            <th className="p-3 text-right font-medium">
                                                Price
                                            </th>
                                            <th className="p-3 text-left font-medium">
                                                Billing
                                            </th>
                                            <th className="p-3 text-left font-medium">
                                                Seats
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {plans.map((plan) => (
                                            <tr
                                                key={plan.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="p-3 font-medium">
                                                    {plan.name}
                                                </td>
                                                <td className="p-3 text-right">
                                                    {plan.currency.toUpperCase()}{' '}
                                                    {plan.price.toFixed(2)}
                                                    {plan.is_per_seat &&
                                                        plan.price_per_seat !=
                                                            null && (
                                                            <span className="text-muted-foreground">
                                                                {' '}
                                                                /seat
                                                            </span>
                                                        )}
                                                </td>
                                                <td className="p-3 text-muted-foreground">
                                                    {plan.interval === 'month'
                                                        ? 'Monthly'
                                                        : plan.interval ===
                                                            'year'
                                                          ? 'Yearly'
                                                          : plan.interval}
                                                </td>
                                                <td className="p-3 text-muted-foreground">
                                                    {plan.is_per_seat
                                                        ? 'Per seat'
                                                        : '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                asChild
                                className="mt-3"
                            >
                                <Link href="/pricing">View full pricing</Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                <RecentInvoicesCard invoices={invoices} />
            </div>
        </AppLayout>
    );
}
