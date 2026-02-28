import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

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
    photoUrls: PhotoUrl[];
    damageAnalysis?: DamageAnalysisRecord | null;
    runDamageAssessmentUrl: string;
}

export default function FleetInsuranceClaimsShow({ insuranceClaim, photoUrls, damageAnalysis, runDamageAssessmentUrl }: Props) {
    const [analyzing, setAnalyzing] = useState(false);

    function handleRunDamageAnalysis() {
        setAnalyzing(true);
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        fetch(runDamageAssessmentUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
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

    const detail = damageAnalysis?.detailed_analysis as { severity?: string; description?: string; cost_range?: string; parts_affected?: string } | undefined;
    const costRange = damageAnalysis?.recommendations && typeof damageAnalysis.recommendations === 'object' && 'cost_range' in damageAnalysis.recommendations
        ? (damageAnalysis.recommendations as { cost_range?: string }).cost_range
        : detail?.cost_range;
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
                                    <a key={p.id} href={p.url} target="_blank" rel="noopener noreferrer" className="block">
                                        <img src={p.url} alt="" className="h-32 w-auto rounded border object-cover" />
                                    </a>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
                {damageAnalysis && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">AI damage / claims analysis</CardTitle>
                            <p className="text-muted-foreground text-xs">
                                {new Date(damageAnalysis.created_at).toLocaleString()}
                                {damageAnalysis.priority && (
                                    <> · <Badge variant="secondary">{damageAnalysis.priority}</Badge></>
                                )}
                                {damageAnalysis.confidence_score != null && (
                                    <> · {Number(damageAnalysis.confidence_score) * 100}% confidence</>
                                )}
                            </p>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {damageAnalysis.primary_finding && (
                                <p><span className="font-medium">Summary:</span> {damageAnalysis.primary_finding}</p>
                            )}
                            {detail?.severity && (
                                <p><span className="font-medium">Severity:</span> {detail.severity}</p>
                            )}
                            {detail?.parts_affected && (
                                <p><span className="font-medium">Parts affected:</span> {detail.parts_affected}</p>
                            )}
                            {costRange && (
                                <p><span className="font-medium">Cost range:</span> {costRange}</p>
                            )}
                            {detail?.description && (
                                <p><span className="font-medium">Description:</span> {detail.description}</p>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
