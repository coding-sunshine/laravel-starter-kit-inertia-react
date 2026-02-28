import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface InsuranceClaimRecord {
    id: number;
    claim_number: string;
    claim_type: string;
    status: string;
    incident?: { id: number; incident_number: string };
    insurance_policy?: { id: number; policy_number: string };
}
interface Props {
    insuranceClaim: InsuranceClaimRecord;
}

export default function FleetInsuranceClaimsShow({ insuranceClaim }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/insurance-claims' },
        { title: 'Insurance claims', href: '/fleet/insurance-claims' },
        {
            title: insuranceClaim.claim_number,
            href: `/fleet/insurance-claims/${insuranceClaim.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${insuranceClaim.claim_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">{insuranceClaim.claim_number}</h1>
                    <Button variant="outline" asChild>
                        <Link href="/fleet/insurance-claims">Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Insurance claim</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Claim number:</span>{' '}
                            {insuranceClaim.claim_number}
                        </p>
                        <p>
                            <span className="font-medium">Claim type:</span>{' '}
                            {insuranceClaim.claim_type}
                        </p>
                        <p>
                            <span className="font-medium">Status:</span> {insuranceClaim.status}
                        </p>
                        {insuranceClaim.incident && (
                            <p>
                                <span className="font-medium">Incident:</span>{' '}
                                <Link
                                    href={`/fleet/incidents/${insuranceClaim.incident.id}`}
                                    className="underline"
                                >
                                    {insuranceClaim.incident.incident_number}
                                </Link>
                            </p>
                        )}
                        {insuranceClaim.insurance_policy && (
                            <p>
                                <span className="font-medium">Policy:</span>{' '}
                                <Link
                                    href={`/fleet/insurance-policies/${insuranceClaim.insurance_policy.id}`}
                                    className="underline"
                                >
                                    {insuranceClaim.insurance_policy.policy_number}
                                </Link>
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
