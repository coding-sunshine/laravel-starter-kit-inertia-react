import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface InsurancePolicyRecord {
    id: number;
    policy_number: string;
    insurer_name: string;
    policy_type: string;
    coverage_type: string;
    start_date: string;
    end_date: string;
    premium_amount?: number | null;
    excess_amount?: number | null;
    status: string;
}
interface Props {
    insurancePolicy: InsurancePolicyRecord;
}

export default function FleetInsurancePoliciesShow({ insurancePolicy }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/insurance-policies' },
        { title: 'Insurance policies', href: '/fleet/insurance-policies' },
        {
            title: insurancePolicy.policy_number,
            href: `/fleet/insurance-policies/${insurancePolicy.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${insurancePolicy.policy_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">
                        {insurancePolicy.policy_number}
                    </h1>
                    <Button variant="outline" asChild>
                        <Link href="/fleet/insurance-policies">Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">
                            Insurance policy
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Policy number:</span>{' '}
                            {insurancePolicy.policy_number}
                        </p>
                        <p>
                            <span className="font-medium">Insurer:</span>{' '}
                            {insurancePolicy.insurer_name}
                        </p>
                        <p>
                            <span className="font-medium">Policy type:</span>{' '}
                            {insurancePolicy.policy_type}
                        </p>
                        <p>
                            <span className="font-medium">Coverage type:</span>{' '}
                            {insurancePolicy.coverage_type}
                        </p>
                        <p>
                            <span className="font-medium">Start date:</span>{' '}
                            {insurancePolicy.start_date
                                ? new Date(
                                      insurancePolicy.start_date,
                                  ).toLocaleDateString()
                                : '—'}
                        </p>
                        <p>
                            <span className="font-medium">End date:</span>{' '}
                            {insurancePolicy.end_date
                                ? new Date(
                                      insurancePolicy.end_date,
                                  ).toLocaleDateString()
                                : '—'}
                        </p>
                        {insurancePolicy.premium_amount != null && (
                            <p>
                                <span className="font-medium">
                                    Premium amount:
                                </span>{' '}
                                {Number(
                                    insurancePolicy.premium_amount,
                                ).toLocaleString()}
                            </p>
                        )}
                        {insurancePolicy.excess_amount != null && (
                            <p>
                                <span className="font-medium">
                                    Excess amount:
                                </span>{' '}
                                {Number(
                                    insurancePolicy.excess_amount,
                                ).toLocaleString()}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">Status:</span>{' '}
                            {insurancePolicy.status}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
