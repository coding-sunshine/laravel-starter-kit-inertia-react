import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface ContractorComplianceItem {
    id: number;
    compliance_type: string;
    status?: string;
    reference_number?: string;
    issue_date?: string;
    expiry_date?: string;
    document_url?: string;
    notes?: string;
    contractor?: { id: number; name: string };
}
interface Props {
    contractorCompliance: ContractorComplianceItem;
}

export default function FleetContractorComplianceShow({
    contractorCompliance,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        {
            title: 'Contractor compliance',
            href: '/fleet/contractor-compliance',
        },
        {
            title: 'View',
            href: `/fleet/contractor-compliance/${contractorCompliance.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Contractor compliance" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {contractorCompliance.contractor?.name ?? 'Compliance'}{' '}
                        – {contractorCompliance.compliance_type}
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/contractor-compliance/${contractorCompliance.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/contractor-compliance">
                                Back to list
                            </Link>
                        </Button>
                    </div>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        {contractorCompliance.contractor && (
                            <p>
                                <span className="font-medium">Contractor:</span>{' '}
                                {contractorCompliance.contractor.name}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">
                                Compliance type:
                            </span>{' '}
                            {contractorCompliance.compliance_type}
                        </p>
                        {contractorCompliance.status && (
                            <p>
                                <span className="font-medium">Status:</span>{' '}
                                {contractorCompliance.status}
                            </p>
                        )}
                        {contractorCompliance.reference_number && (
                            <p>
                                <span className="font-medium">Reference:</span>{' '}
                                {contractorCompliance.reference_number}
                            </p>
                        )}
                        {contractorCompliance.issue_date && (
                            <p>
                                <span className="font-medium">Issue date:</span>{' '}
                                {new Date(
                                    contractorCompliance.issue_date,
                                ).toLocaleDateString()}
                            </p>
                        )}
                        {contractorCompliance.expiry_date && (
                            <p>
                                <span className="font-medium">
                                    Expiry date:
                                </span>{' '}
                                {new Date(
                                    contractorCompliance.expiry_date,
                                ).toLocaleDateString()}
                            </p>
                        )}
                        {contractorCompliance.document_url && (
                            <p>
                                <span className="font-medium">
                                    Document URL:
                                </span>{' '}
                                <a
                                    href={contractorCompliance.document_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="text-primary underline"
                                >
                                    {contractorCompliance.document_url}
                                </a>
                            </p>
                        )}
                        {contractorCompliance.notes && (
                            <p>
                                <span className="font-medium">Notes:</span>{' '}
                                {contractorCompliance.notes}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
