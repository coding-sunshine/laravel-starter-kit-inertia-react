'use client';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { FleetIndexSummaryBar, type SummaryStat } from '@/components/fleet/fleet-index-summary-bar';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    AlertOctagon,
    AlertTriangle,
    BarChart3,
    BrainCircuit,
    CarFront,
    FileText,
    Fuel,
    GraduationCap,
    Heart,
    Route,
    Shield,
    ShieldAlert,
    Target,
    TrendingDown,
    Wrench,
    Zap,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useMemo } from 'react';

interface AiAnalysisResultRecord {
    id: number;
    analysis_type: string;
    entity_type: string;
    entity_id: number;
    primary_finding: string | null;
    priority: string;
    confidence_score: number | string;
    status: string;
    created_at: string;
}

interface Props {
    aiAnalysisResults: {
        data: AiAnalysisResultRecord[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    summary?: {
        totalResults: number;
        highPriority: number;
        mediumPriority: number;
        avgConfidence: number;
    };
    [key: string]: unknown;
}

const PRIORITY_CONFIG: Record<string, { border: string; dot: string; badgeCls: string; label: string }> = {
    critical: {
        border: 'border-l-red-500',
        dot: 'bg-red-500',
        badgeCls: 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300',
        label: 'Critical',
    },
    high: {
        border: 'border-l-red-500',
        dot: 'bg-red-500',
        badgeCls: 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300',
        label: 'High',
    },
    medium: {
        border: 'border-l-amber-500',
        dot: 'bg-amber-500',
        badgeCls: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300',
        label: 'Medium',
    },
    low: {
        border: 'border-l-blue-500',
        dot: 'bg-blue-500',
        badgeCls: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300',
        label: 'Low',
    },
};

const TYPE_ICONS: Record<string, LucideIcon> = {
    fraud_detection: ShieldAlert,
    predictive_maintenance: Wrench,
    route_optimization: Route,
    driver_coaching: GraduationCap,
    cost_optimization: TrendingDown,
    compliance_prediction: Shield,
    risk_assessment: AlertTriangle,
    fuel_efficiency: Fuel,
    safety_scoring: Heart,
    damage_detection: CarFront,
    claims_processing: FileText,
    incident_analysis: AlertOctagon,
    electrification_planning: Zap,
};

function formatAnalysisType(type: string): string {
    return type
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function entityRoute(entityType: string, entityId: number): string | null {
    const routeMap: Record<string, string> = {
        vehicle: '/fleet/vehicles',
        driver: '/fleet/drivers',
        trip: '/fleet/trips',
        incident: '/fleet/incidents',
    };
    const base = routeMap[entityType];
    return base ? `${base}/${entityId}` : null;
}

export default function FleetAiAnalysisResultsIndex({ aiAnalysisResults, summary }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'AI Analysis Results', href: '/fleet/ai-analysis-results' },
    ];

    const summaryStats: SummaryStat[] = summary
        ? [
              { label: 'Total Results', value: summary.totalResults, icon: BrainCircuit },
              {
                  label: 'High Priority',
                  value: summary.highPriority,
                  icon: AlertTriangle,
                  variant: summary.highPriority > 0 ? 'danger' : 'default',
              },
              {
                  label: 'Medium Priority',
                  value: summary.mediumPriority,
                  icon: Target,
                  variant: summary.mediumPriority > 0 ? 'warning' : 'default',
              },
              {
                  label: 'Avg Confidence',
                  value: `${summary.avgConfidence}%`,
                  icon: BarChart3,
                  variant: 'success',
              },
          ]
        : [];

    // Group results by analysis_type
    const grouped = useMemo(() => {
        const groups: Record<string, AiAnalysisResultRecord[]> = {};
        for (const result of aiAnalysisResults.data) {
            if (!groups[result.analysis_type]) {
                groups[result.analysis_type] = [];
            }
            groups[result.analysis_type].push(result);
        }
        return Object.entries(groups);
    }, [aiAnalysisResults.data]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – AI Analysis Results" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <BrainCircuit className="size-7 text-primary" />
                    <h1 className="text-2xl font-semibold">AI Analysis Results</h1>
                </div>

                {/* Summary Cards */}
                {summary ? (
                    <FleetIndexSummaryBar stats={summaryStats} />
                ) : (
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                        {Array.from({ length: 4 }).map((_, i) => (
                            <Card key={i}>
                                <CardContent className="pt-0">
                                    <Skeleton className="mb-2 h-4 w-24" />
                                    <Skeleton className="h-8 w-16" />
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {/* Results */}
                {aiAnalysisResults.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <BrainCircuit className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No AI analysis results yet. Run an analysis to get started.
                        </p>
                    </div>
                ) : (
                    <>
                        {/* Grouped card layout */}
                        <div className="space-y-8">
                            {grouped.map(([type, results]) => {
                                const TypeIcon = TYPE_ICONS[type] ?? BrainCircuit;

                                return (
                                    <section key={type}>
                                        <div className="mb-3 flex items-center gap-2">
                                            <TypeIcon className="size-5 text-muted-foreground" />
                                            <h2 className="text-lg font-semibold">
                                                {formatAnalysisType(type)}
                                            </h2>
                                            <span className="text-sm text-muted-foreground">
                                                ({results.length})
                                            </span>
                                        </div>
                                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                            {results.map((result) => {
                                                const config =
                                                    PRIORITY_CONFIG[result.priority] ?? PRIORITY_CONFIG.low;
                                                const confidence = Number(result.confidence_score);
                                                const entityLink = entityRoute(
                                                    result.entity_type,
                                                    result.entity_id,
                                                );

                                                return (
                                                    <Link
                                                        key={result.id}
                                                        href={`/fleet/ai-analysis-results/${result.id}`}
                                                        className="block"
                                                    >
                                                        <Card
                                                            className={`border-l-4 transition-shadow hover:shadow-md ${config.border}`}
                                                        >
                                                            <CardContent className="pt-0">
                                                                {/* Top row: priority badge + confidence */}
                                                                <div className="flex items-center justify-between">
                                                                    <Badge
                                                                        variant="outline"
                                                                        className={config.badgeCls}
                                                                    >
                                                                        <span
                                                                            className={`size-1.5 rounded-full ${config.dot}`}
                                                                        />
                                                                        {config.label}
                                                                    </Badge>
                                                                    <span className="text-xs font-medium tabular-nums text-muted-foreground">
                                                                        {Math.round(confidence * 100)}%
                                                                        confidence
                                                                    </span>
                                                                </div>

                                                                {/* Primary finding */}
                                                                <p className="mt-2 line-clamp-2 text-sm leading-snug">
                                                                    {result.primary_finding ?? '—'}
                                                                </p>

                                                                {/* Footer: entity + date */}
                                                                <div className="mt-3 flex items-center justify-between text-xs text-muted-foreground">
                                                                    {entityLink ? (
                                                                        <span className="capitalize">
                                                                            {result.entity_type} #
                                                                            {result.entity_id}
                                                                        </span>
                                                                    ) : (
                                                                        <span className="capitalize">
                                                                            {result.entity_type}
                                                                        </span>
                                                                    )}
                                                                    <span>
                                                                        {formatDate(result.created_at)}
                                                                    </span>
                                                                </div>
                                                            </CardContent>
                                                        </Card>
                                                    </Link>
                                                );
                                            })}
                                        </div>
                                    </section>
                                );
                            })}
                        </div>

                        {/* Pagination */}
                        {aiAnalysisResults.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {aiAnalysisResults.links.map((link, i) => (
                                    <Link
                                        key={i}
                                        href={link.url ?? '#'}
                                        className={`rounded border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
