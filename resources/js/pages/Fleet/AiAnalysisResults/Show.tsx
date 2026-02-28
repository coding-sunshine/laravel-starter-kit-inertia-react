import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface ReviewedBy { id: number; name?: string; first_name?: string; last_name?: string; }
interface AiAnalysisResultRecord {
    id: number;
    analysis_type: string;
    entity_type: string;
    entity_id: number;
    primary_finding: string | null;
    detailed_analysis: unknown;
    status: string;
    review_notes?: string | null;
    reviewed_at?: string | null;
    reviewed_by?: ReviewedBy | null;
    created_at: string;
    confidence_score?: string | number | null;
    risk_score?: string | number | null;
    recommendations?: unknown;
    action_items?: unknown;
}
interface Props {
    aiAnalysisResult: AiAnalysisResultRecord;
    analysisTypes?: { value: string; name: string }[];
    statuses?: { value: string; name: string }[];
}

export default function FleetAiAnalysisResultsShow({ aiAnalysisResult }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'AI analysis results', href: '/fleet/ai-analysis-results' },
        { title: `Analysis #${aiAnalysisResult.id}`, href: `/fleet/ai-analysis-results/${aiAnalysisResult.id}` },
    ];
    const reviewerName = aiAnalysisResult.reviewed_by
        ? (aiAnalysisResult.reviewed_by.name ?? [aiAnalysisResult.reviewed_by.first_name, aiAnalysisResult.reviewed_by.last_name].filter(Boolean).join(' ') || '—')
        : null;
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – AI analysis #${aiAnalysisResult.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">AI analysis result #{aiAnalysisResult.id}</h1>
                    <Button variant="ghost" size="sm" asChild><Link href="/fleet/ai-analysis-results">Back to list</Link></Button>
                </div>
                <Card>
                    <CardHeader><CardTitle className="text-base">Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">ID:</span> {aiAnalysisResult.id}</p>
                        <p><span className="font-medium">Analysis type:</span> {aiAnalysisResult.analysis_type}</p>
                        <p><span className="font-medium">Entity type:</span> {aiAnalysisResult.entity_type}</p>
                        <p><span className="font-medium">Entity ID:</span> {aiAnalysisResult.entity_id}</p>
                        <p><span className="font-medium">Status:</span> {aiAnalysisResult.status}</p>
                        {aiAnalysisResult.confidence_score != null && <p><span className="font-medium">Confidence score:</span> {String(aiAnalysisResult.confidence_score)}</p>}
                        {aiAnalysisResult.risk_score != null && <p><span className="font-medium">Risk score:</span> {String(aiAnalysisResult.risk_score)}</p>}
                        <p><span className="font-medium">Created:</span> {new Date(aiAnalysisResult.created_at).toLocaleString()}</p>
                    </CardContent>
                </Card>
                {aiAnalysisResult.primary_finding && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">Primary finding</CardTitle></CardHeader>
                        <CardContent className="text-sm whitespace-pre-wrap">{aiAnalysisResult.primary_finding}</CardContent>
                    </Card>
                )}
                {aiAnalysisResult.detailed_analysis != null && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">Detailed analysis</CardTitle></CardHeader>
                        <CardContent className="text-sm">
                            {typeof aiAnalysisResult.detailed_analysis === 'string'
                                ? <span className="whitespace-pre-wrap">{aiAnalysisResult.detailed_analysis}</span>
                                : <pre className="overflow-auto rounded bg-muted p-2 text-xs">{JSON.stringify(aiAnalysisResult.detailed_analysis, null, 2)}</pre>}
                        </CardContent>
                    </Card>
                )}
                {(aiAnalysisResult.review_notes || reviewerName || aiAnalysisResult.reviewed_at) && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">Review</CardTitle></CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {aiAnalysisResult.review_notes && <p><span className="font-medium">Notes:</span> {aiAnalysisResult.review_notes}</p>}
                            {reviewerName && <p><span className="font-medium">Reviewed by:</span> {reviewerName}</p>}
                            {aiAnalysisResult.reviewed_at && <p><span className="font-medium">Reviewed at:</span> {new Date(aiAnalysisResult.reviewed_at).toLocaleString()}</p>}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
