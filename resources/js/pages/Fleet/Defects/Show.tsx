import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface DefectRecord {
    id: number;
    defect_number: string;
    title: string;
    description?: string;
    category?: string;
    severity?: string;
    reported_at: string;
    vehicle?: { id: number; registration: string };
    reportedByDriver?: { id: number; first_name: string; last_name: string };
    workOrder?: { id: number; work_order_number: string };
}
interface PhotoUrl {
    id: number;
    url: string;
}

interface DamageAnalysisRecord {
    id: number;
    primary_finding: string | null;
    detailed_analysis: Record<string, unknown> | null;
    recommendations: Record<string, unknown> | null;
    priority: string | null;
    confidence_score: number | string | null;
    created_at: string;
}
interface Props {
    defect: DefectRecord;
    photoUrls: PhotoUrl[];
    damageAnalysis?: DamageAnalysisRecord | null;
    runDamageAssessmentUrl: string;
}

export default function FleetDefectsShow({
    defect,
    photoUrls,
    damageAnalysis,
    runDamageAssessmentUrl,
}: Props) {
    const [analyzing, setAnalyzing] = useState(false);
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/defects' },
        { title: 'Defects', href: '/fleet/defects' },
        { title: defect.defect_number, href: `/fleet/defects/${defect.id}` },
    ];

    function handleRunDamageAnalysis() {
        setAnalyzing(true);
        fetch(runDamageAssessmentUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((res) => res.json().then((data) => ({ ok: res.ok, data })))
            .then(({ ok, data }) => {
                if (ok) {
                    router.reload();
                } else {
                    setAnalyzing(false);
                    alert(data?.message ?? 'Analysis could not be started.');
                }
            })
            .catch(() => {
                setAnalyzing(false);
                alert('Request failed. Please try again.');
            });
    }

    const detail = damageAnalysis?.detailed_analysis as
        | {
              severity?: string;
              description?: string;
              cost_range?: string;
              parts_affected?: string;
          }
        | undefined;
    const costRange =
        damageAnalysis?.recommendations &&
        typeof damageAnalysis.recommendations === 'object' &&
        'cost_range' in damageAnalysis.recommendations
            ? (damageAnalysis.recommendations as { cost_range?: string })
                  .cost_range
            : detail?.cost_range;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${defect.defect_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">
                        {defect.defect_number}
                    </h1>
                    <Button variant="outline" asChild>
                        <Link href="/fleet/defects">Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Title:</span>{' '}
                            {defect.title}
                        </p>
                        {defect.description && (
                            <p>
                                <span className="font-medium">
                                    Description:
                                </span>{' '}
                                {defect.description}
                            </p>
                        )}
                        {defect.category && (
                            <p>
                                <span className="font-medium">Category:</span>{' '}
                                {defect.category}
                            </p>
                        )}
                        {defect.severity && (
                            <p>
                                <span className="font-medium">Severity:</span>{' '}
                                {defect.severity}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">Reported at:</span>{' '}
                            {new Date(defect.reported_at).toLocaleString()}
                        </p>
                        {defect.vehicle && (
                            <p>
                                <span className="font-medium">Vehicle:</span>{' '}
                                <Link
                                    href={`/fleet/vehicles/${defect.vehicle.id}`}
                                    className="underline"
                                >
                                    {defect.vehicle.registration}
                                </Link>
                            </p>
                        )}
                        {defect.reportedByDriver && (
                            <p>
                                <span className="font-medium">
                                    Reported by:
                                </span>{' '}
                                <Link
                                    href={`/fleet/drivers/${defect.reportedByDriver.id}`}
                                    className="underline"
                                >
                                    {defect.reportedByDriver.first_name}{' '}
                                    {defect.reportedByDriver.last_name}
                                </Link>
                            </p>
                        )}
                        {defect.workOrder && (
                            <p>
                                <span className="font-medium">Work order:</span>{' '}
                                <Link
                                    href={`/fleet/work-orders/${defect.workOrder.id}`}
                                    className="underline"
                                >
                                    {defect.workOrder.work_order_number}
                                </Link>
                            </p>
                        )}
                    </CardContent>
                </Card>
                {photoUrls.length > 0 && (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-base">Photos</CardTitle>
                            <Button
                                variant="secondary"
                                size="sm"
                                onClick={handleRunDamageAnalysis}
                                disabled={analyzing}
                            >
                                {analyzing ? 'Analyzing…' : 'Analyze with AI'}
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-4">
                                {photoUrls.map((p) => (
                                    <a
                                        key={p.id}
                                        href={p.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="block"
                                    >
                                        <img
                                            src={p.url}
                                            alt=""
                                            className="h-32 w-auto rounded border object-cover"
                                        />
                                    </a>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
                {damageAnalysis && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">
                                AI damage analysis
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                {new Date(
                                    damageAnalysis.created_at,
                                ).toLocaleString()}
                                {damageAnalysis.priority && (
                                    <>
                                        {' '}
                                        ·{' '}
                                        <Badge variant="secondary">
                                            {damageAnalysis.priority}
                                        </Badge>
                                    </>
                                )}
                                {damageAnalysis.confidence_score != null && (
                                    <>
                                        {' '}
                                        ·{' '}
                                        {Number(
                                            damageAnalysis.confidence_score,
                                        ) * 100}
                                        % confidence
                                    </>
                                )}
                            </p>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {damageAnalysis.primary_finding && (
                                <p>
                                    <span className="font-medium">
                                        Summary:
                                    </span>{' '}
                                    {damageAnalysis.primary_finding}
                                </p>
                            )}
                            {detail?.severity && (
                                <p>
                                    <span className="font-medium">
                                        Severity:
                                    </span>{' '}
                                    {detail.severity}
                                </p>
                            )}
                            {detail?.parts_affected && (
                                <p>
                                    <span className="font-medium">
                                        Parts affected:
                                    </span>{' '}
                                    {detail.parts_affected}
                                </p>
                            )}
                            {costRange && (
                                <p>
                                    <span className="font-medium">
                                        Cost range:
                                    </span>{' '}
                                    {costRange}
                                </p>
                            )}
                            {detail?.description && (
                                <p>
                                    <span className="font-medium">
                                        Description:
                                    </span>{' '}
                                    {detail.description}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
