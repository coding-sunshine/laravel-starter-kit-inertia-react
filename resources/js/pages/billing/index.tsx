import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { CreditCard, FileText } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

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
}

export default function BillingIndex() {
    const { creditBalance, activePlan, isOnTrial, invoices } = usePage<
        Props & SharedData
    >().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <h2 className="text-lg font-medium">Billing</h2>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CreditCard className="size-5" />
                                Current plan
                            </CardTitle>
                            <CardDescription>
                                {activePlan
                                    ? activePlan.name
                                    : 'No active subscription'}
                                {isOnTrial && ' (Trial)'}
                            </CardDescription>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Credits</CardTitle>
                            <CardDescription>
                                Balance: {creditBalance} credits
                            </CardDescription>
                            <CardContent className="pt-2">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href="/billing/credits">
                                        Buy credits
                                    </Link>
                                </Button>
                            </CardContent>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="size-5" />
                            Recent invoices
                        </CardTitle>
                        <CardDescription>
                            View and download invoices
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {invoices.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No invoices yet.
                            </p>
                        ) : (
                            <ul className="space-y-2">
                                {invoices.map((inv) => (
                                    <li
                                        key={inv.id}
                                        className="flex items-center justify-between text-sm"
                                    >
                                        <span>
                                            {inv.number} — {inv.status}
                                        </span>
                                        <span>
                                            {inv.currency.toUpperCase()}{' '}
                                            {(inv.total / 100).toFixed(2)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <Button variant="link" className="mt-2 p-0" asChild>
                            <Link href="/billing/invoices">
                                View all invoices
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
