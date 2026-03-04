import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { BarChart3, Sparkles } from 'lucide-react';
import { useState } from 'react';

interface WhatIfScenario {
    title: string;
    description: string;
    estimated_impact: string;
}
interface AnalysisResult {
    right_sizing_summary: string;
    replacement_timing_summary: string;
    fleet_mix_summary: string;
    what_if_scenarios: WhatIfScenario[];
}
interface LatestAnalysis {
    id: number;
    primary_finding: string;
    detailed_analysis: AnalysisResult;
    created_at: string;
}
interface Props {
    latestAnalysis: LatestAnalysis | null;
    analyzeUrl: string;
}

export default function FleetOptimizationIndex({
    latestAnalysis,
    analyzeUrl,
}: Props) {
    const [analyzing, setAnalyzing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [justAnalyzed, setJustAnalyzed] = useState<AnalysisResult | null>(
        null,
    );

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Fleet optimization', href: '/fleet/fleet-optimization' },
    ];

    const result = justAnalyzed ?? latestAnalysis?.detailed_analysis ?? null;

    const handleAnalyze = async () => {
        setError(null);
        setJustAnalyzed(null);
        setAnalyzing(true);
        try {
            const res = await fetch(analyzeUrl, {
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
                credentials: 'include',
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                setError(data?.message ?? 'Analysis failed.');
                return;
            }
            if (data.result) setJustAnalyzed(data.result);
        } finally {
            setAnalyzing(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Fleet optimization" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">
                        Fleet optimization
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/fleet">Back to Fleet</Link>
                        </Button>
                        <Button onClick={handleAnalyze} disabled={analyzing}>
                            <Sparkles className="mr-1.5 size-4" />
                            {analyzing
                                ? 'Analyzing…'
                                : 'Run optimization analysis'}
                        </Button>
                    </div>
                </div>
                {error && <p className="text-sm text-destructive">{error}</p>}

                {!result ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-16">
                            <BarChart3 className="size-12 text-muted-foreground" />
                            <p className="mt-2 text-sm text-muted-foreground">
                                No fleet optimization analysis yet.
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Click &quot;Run optimization analysis&quot; for
                                right-sizing, replacement timing, fleet mix, and
                                what-if scenarios.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-6 lg:grid-cols-2">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    Right-sizing
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm whitespace-pre-wrap">
                                    {result.right_sizing_summary ||
                                        'No summary.'}
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    Replacement timing
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm whitespace-pre-wrap">
                                    {result.replacement_timing_summary ||
                                        'No summary.'}
                                </p>
                            </CardContent>
                        </Card>
                        <Card className="lg:col-span-2">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    Fleet mix
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm whitespace-pre-wrap">
                                    {result.fleet_mix_summary || 'No summary.'}
                                </p>
                            </CardContent>
                        </Card>
                        <Card className="lg:col-span-2">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    What-if scenarios
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {!result.what_if_scenarios ||
                                result.what_if_scenarios.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No scenarios.
                                    </p>
                                ) : (
                                    <ul className="space-y-4">
                                        {result.what_if_scenarios.map(
                                            (s, i) => (
                                                <li
                                                    key={i}
                                                    className="rounded-lg border p-3"
                                                >
                                                    <p className="font-medium">
                                                        {s.title}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {s.description}
                                                    </p>
                                                    <p className="mt-1 text-sm font-medium text-primary">
                                                        {s.estimated_impact}
                                                    </p>
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
