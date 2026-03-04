import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface ClaimRecord {
    id: number;
    claim_number: string;
    status: string;
    claim_amount?: string | number | null;
    settlement_amount?: string | number | null;
    submitted_date?: string | null;
    settled_at?: string | null;
    work_order?: { id: number; work_order_number: string };
}
interface Props {
    warrantyClaim: ClaimRecord;
}

export default function FleetWarrantyClaimsShow({ warrantyClaim }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Warranty claims', href: '/fleet/warranty-claims' },
        {
            title: warrantyClaim.claim_number,
            href: `/fleet/warranty-claims/${warrantyClaim.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${warrantyClaim.claim_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">
                        {warrantyClaim.claim_number}
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={`/fleet/warranty-claims/${warrantyClaim.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/warranty-claims">Back</Link>
                        </Button>
                    </div>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Status:</span>{' '}
                            {warrantyClaim.status}
                        </p>
                        {warrantyClaim.work_order && (
                            <p>
                                <span className="font-medium">Work order:</span>{' '}
                                <Link
                                    href={`/fleet/work-orders/${warrantyClaim.work_order.id}`}
                                    className="underline"
                                >
                                    {warrantyClaim.work_order.work_order_number}
                                </Link>
                            </p>
                        )}
                        {warrantyClaim.claim_amount != null && (
                            <p>
                                <span className="font-medium">
                                    Claim amount:
                                </span>{' '}
                                {warrantyClaim.claim_amount}
                            </p>
                        )}
                        {warrantyClaim.settlement_amount != null && (
                            <p>
                                <span className="font-medium">
                                    Settlement amount:
                                </span>{' '}
                                {warrantyClaim.settlement_amount}
                            </p>
                        )}
                        {warrantyClaim.submitted_date && (
                            <p>
                                <span className="font-medium">
                                    Submitted date:
                                </span>{' '}
                                {new Date(
                                    warrantyClaim.submitted_date,
                                ).toLocaleDateString()}
                            </p>
                        )}
                        {warrantyClaim.settled_at && (
                            <p>
                                <span className="font-medium">Settled at:</span>{' '}
                                {new Date(
                                    warrantyClaim.settled_at,
                                ).toLocaleDateString()}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
