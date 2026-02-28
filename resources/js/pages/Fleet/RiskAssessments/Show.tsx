import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Assessment {
    id: number;
    title: string;
    type: string;
    reference_number?: string;
    description?: string;
    hazards?: string;
    control_measures?: string;
    status: string;
    review_date?: string;
}
interface Props { riskAssessment: Assessment; }

export default function RiskAssessmentsShow({ riskAssessment }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Risk assessments', href: '/fleet/risk-assessments' },
        { title: riskAssessment.title, href: `/fleet/risk-assessments/${riskAssessment.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Risk assessment" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{riskAssessment.title}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/risk-assessments/${riskAssessment.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/risk-assessments">Back</Link></Button>
                    </div>
                </div>
                <dl className="max-w-md space-y-2 rounded-lg border p-6">
                    <div><dt className="text-sm text-muted-foreground">Title</dt><dd className="font-medium">{riskAssessment.title}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Type</dt><dd>{riskAssessment.type}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Reference</dt><dd>{riskAssessment.reference_number ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Status</dt><dd>{riskAssessment.status}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Review date</dt><dd>{riskAssessment.review_date ?? '—'}</dd></div>
                    {riskAssessment.description && <div><dt className="text-sm text-muted-foreground">Description</dt><dd className="whitespace-pre-wrap">{riskAssessment.description}</dd></div>}
                    {riskAssessment.hazards && <div><dt className="text-sm text-muted-foreground">Hazards</dt><dd className="whitespace-pre-wrap">{riskAssessment.hazards}</dd></div>}
                    {riskAssessment.control_measures && <div><dt className="text-sm text-muted-foreground">Control measures</dt><dd className="whitespace-pre-wrap">{riskAssessment.control_measures}</dd></div>}
                </dl>
            </div>
        </AppLayout>
    );
}
