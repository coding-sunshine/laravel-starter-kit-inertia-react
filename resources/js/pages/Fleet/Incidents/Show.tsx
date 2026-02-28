import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface DamageAnalysisRecord {
    id: number;
    primary_finding: string | null;
    detailed_analysis: Record<string, unknown> | null;
    recommendations: Record<string, unknown> | null;
    priority: string | null;
    confidence_score: number | string | null;
    created_at: string;
}

interface IncidentRecord {
    id: number;
    incident_number: string;
    incident_date?: string;
    incident_timestamp?: string;
    incident_type: string;
    severity: string;
    status: string;
    description?: string;
    location_description?: string | null;
    fault_determination?: string | null;
    vehicle?: { id: number; registration: string };
    driver?: { id: number; first_name: string; last_name: string };
}
interface MediaItem {
    id: number;
    url: string;
    mime_type: string;
    file_name: string;
}
interface IncidentAnalysisRecord {
    id: number;
    primary_finding: string | null;
    detailed_analysis: Record<string, unknown> | null;
    priority: string | null;
    created_at: string;
}
interface Props {
    incident: IncidentRecord;
    mediaItems: MediaItem[];
    damageAnalysis?: DamageAnalysisRecord | null;
    incidentAnalysis?: IncidentAnalysisRecord | null;
    runDamageAssessmentUrl: string;
    runIncidentAnalysisUrl: string;
}

function isImage(mimeType: string): boolean {
    return (mimeType || '').startsWith('image/');
}

export default function FleetIncidentsShow({ incident, mediaItems, damageAnalysis, incidentAnalysis, runDamageAssessmentUrl, runIncidentAnalysisUrl }: Props) {
    const [analyzing, setAnalyzing] = useState(false);
    const [analyzingNarrative, setAnalyzingNarrative] = useState(false);
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/incidents' },
        { title: 'Incidents', href: '/fleet/incidents' },
        { title: incident.incident_number, href: `/fleet/incidents/${incident.id}` },
    ];
    const hasImage = mediaItems.some((m) => isImage(m.mime_type));

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

    function handleRunIncidentAnalysis() {
        setAnalyzingNarrative(true);
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        fetch(runIncidentAnalysisUrl, {
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
                    setAnalyzingNarrative(false);
                    alert(data?.message ?? 'Analysis could not be started.');
                }
            })
            .catch(() => {
                setAnalyzingNarrative(false);
                alert('Request failed. Please try again.');
            });
    }

    const detail = damageAnalysis?.detailed_analysis as { severity?: string; description?: string; cost_range?: string; parts_affected?: string } | undefined;
    const costRange = damageAnalysis?.recommendations && typeof damageAnalysis.recommendations === 'object' && 'cost_range' in damageAnalysis.recommendations
        ? (damageAnalysis.recommendations as { cost_range?: string }).cost_range
        : detail?.cost_range;

    const dateDisplay = incident.incident_timestamp
        ? new Date(incident.incident_timestamp).toLocaleString()
        : incident.incident_date
          ? new Date(incident.incident_date).toLocaleDateString()
          : '—';
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${incident.incident_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">{incident.incident_number}</h1>
                    <Button variant="outline" asChild>
                        <Link href="/fleet/incidents">Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Incident details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Number:</span> {incident.incident_number}
                        </p>
                        <p>
                            <span className="font-medium">Date:</span> {dateDisplay}
                        </p>
                        <p>
                            <span className="font-medium">Type:</span> {incident.incident_type}
                        </p>
                        <p>
                            <span className="font-medium">Severity:</span> {incident.severity}
                        </p>
                        <p>
                            <span className="font-medium">Status:</span> {incident.status}
                        </p>
                        {incident.vehicle && (
                            <p>
                                <span className="font-medium">Vehicle:</span>{' '}
                                <Link
                                    href={`/fleet/vehicles/${incident.vehicle.id}`}
                                    className="underline"
                                >
                                    {incident.vehicle.registration}
                                </Link>
                            </p>
                        )}
                        {incident.driver && (
                            <p>
                                <span className="font-medium">Driver:</span>{' '}
                                <Link
                                    href={`/fleet/drivers/${incident.driver.id}`}
                                    className="underline"
                                >
                                    {incident.driver.first_name} {incident.driver.last_name}
                                </Link>
                            </p>
                        )}
                        {incident.description && (
                            <p>
                                <span className="font-medium">Description:</span> {incident.description}
                            </p>
                        )}
                        {incident.location_description && (
                            <p>
                                <span className="font-medium">Location:</span>{' '}
                                {incident.location_description}
                            </p>
                        )}
                        {incident.fault_determination && (
                            <p>
                                <span className="font-medium">Fault determination:</span>{' '}
                                {incident.fault_determination}
                            </p>
                        )}
                    </CardContent>
                </Card>
                {mediaItems.length > 0 && (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-base">Photos & documents</CardTitle>
                            {hasImage && (
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    onClick={handleRunDamageAnalysis}
                                    disabled={analyzing}
                                >
                                    {analyzing ? 'Analyzing…' : 'Analyze with AI'}
                                </Button>
                            )}
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-4">
                                {mediaItems.map((item) =>
                                    isImage(item.mime_type) ? (
                                        <a
                                            key={item.id}
                                            href={item.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="block"
                                        >
                                            <img
                                                src={item.url}
                                                alt=""
                                                className="h-32 w-auto rounded border object-cover"
                                            />
                                        </a>
                                    ) : (
                                        <a
                                            key={item.id}
                                            href={item.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="flex h-32 min-w-[10rem] items-center justify-center rounded border bg-muted/50 px-4 py-2 text-sm font-medium text-foreground hover:bg-muted"
                                        >
                                            📄 {item.file_name}
                                        </a>
                                    )
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}
                {damageAnalysis && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">AI damage analysis</CardTitle>
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
                {(incidentAnalysis || incident.description) && (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-base">AI narrative summary</CardTitle>
                            {incident.description && (
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    onClick={handleRunIncidentAnalysis}
                                    disabled={analyzingNarrative}
                                >
                                    {analyzingNarrative ? 'Analyzing…' : 'Re-analyze'}
                                </Button>
                            )}
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {incidentAnalysis?.primary_finding ? (
                                <p>{incidentAnalysis.primary_finding}</p>
                            ) : (
                                <p className="text-muted-foreground">No AI summary yet. Click Re-analyze to run incident analysis.</p>
                            )}
                            {incidentAnalysis?.detailed_analysis && typeof incidentAnalysis.detailed_analysis === 'object' && (
                                <>
                                    {(incidentAnalysis.detailed_analysis as Record<string, unknown>).category && (
                                        <p><span className="font-medium">AI category:</span> {(incidentAnalysis.detailed_analysis as Record<string, unknown>).category as string}</p>
                                    )}
                                    {(incidentAnalysis.detailed_analysis as Record<string, unknown>).severity && (
                                        <p><span className="font-medium">AI severity:</span> {(incidentAnalysis.detailed_analysis as Record<string, unknown>).severity as string}</p>
                                    )}
                                    {(incidentAnalysis.detailed_analysis as Record<string, unknown>).location && (
                                        <p><span className="font-medium">Location (extracted):</span> {(incidentAnalysis.detailed_analysis as Record<string, unknown>).location as string}</p>
                                    )}
                                </>
                            )}
                            {incidentAnalysis?.created_at && (
                                <p className="text-muted-foreground text-xs">
                                    {new Date(incidentAnalysis.created_at).toLocaleString()}
                                    {incidentAnalysis.priority && <> · <Badge variant="secondary">{incidentAnalysis.priority}</Badge></>}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
