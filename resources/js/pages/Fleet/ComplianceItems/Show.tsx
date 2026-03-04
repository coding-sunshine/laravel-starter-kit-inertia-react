import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface ComplianceItemRecord {
    id: number;
    entity_type: string;
    entity_id: number;
    compliance_type: string;
    title: string;
    expiry_date: string;
    status?: string;
}
interface Props {
    complianceItem: ComplianceItemRecord;
}

export default function FleetComplianceItemsShow({ complianceItem }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/compliance-items' },
        { title: 'Compliance items', href: '/fleet/compliance-items' },
        {
            title: complianceItem.title,
            href: `/fleet/compliance-items/${complianceItem.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${complianceItem.title}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">
                        {complianceItem.title}
                    </h1>
                    <Button variant="outline" asChild>
                        <Link href="/fleet/compliance-items">Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Entity type:</span>{' '}
                            {complianceItem.entity_type}
                        </p>
                        <p>
                            <span className="font-medium">Entity ID:</span>{' '}
                            {complianceItem.entity_id}
                        </p>
                        <p>
                            <span className="font-medium">
                                Compliance type:
                            </span>{' '}
                            {complianceItem.compliance_type}
                        </p>
                        <p>
                            <span className="font-medium">Expiry date:</span>{' '}
                            {new Date(
                                complianceItem.expiry_date,
                            ).toLocaleDateString()}
                        </p>
                        {complianceItem.status && (
                            <p>
                                <span className="font-medium">Status:</span>{' '}
                                {complianceItem.status}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
