'use client';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    AlertOctagon,
    AlertTriangle,
    ArrowLeft,
    BarChart3,
    BrainCircuit,
    Calendar,
    CarFront,
    ExternalLink,
    FileText,
    Fuel,
    GraduationCap,
    Heart,
    Lightbulb,
    Route,
    Shield,
    ShieldAlert,
    Target,
    TrendingDown,
    User,
    Wrench,
    Zap,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';

interface ReviewedBy {
    id: number;
    name?: string;
    first_name?: string;
    last_name?: string;
}

interface AiAnalysisResultRecord {
    id: number;
    analysis_type: string;
    entity_type: string;
    entity_id: number;
    primary_finding: string | null;
    detailed_analysis: Record<string, unknown> | null;
    status: string;
    priority: string;
    review_notes?: string | null;
    reviewed_at?: string | null;
    reviewed_by?: ReviewedBy | null;
    created_at: string;
    confidence_score?: string | number | null;
    risk_score?: string | number | null;
    recommendations?: string[] | null;
    action_items?: string[] | null;
    business_impact?: Record<string, string> | null;
    model_name?: string | null;
    model_version?: string | null;
}

interface Props {
    aiAnalysisResult: AiAnalysisResultRecord;
    analysisTypes?: { value: string; name: string }[];
    statuses?: { value: string; name: string }[];
}

const PRIORITY_CONFIG: Record<string, { border: string; dot: string; badgeCls: string; label: string; ringColor: string }> = {
    critical: {
        border: 'border-l-red-500',
        dot: 'bg-red-500',
        badgeCls: 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300',
        label: 'Critical',
        ringColor: 'stroke-red-500',
    },
    high: {
        border: 'border-l-red-500',
        dot: 'bg-red-500',
        badgeCls: 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300',
        label: 'High',
        ringColor: 'stroke-red-500',
    },
    medium: {
        border: 'border-l-amber-500',
        dot: 'bg-amber-500',
        badgeCls: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300',
        label: 'Medium',
        ringColor: 'stroke-amber-500',
    },
    low: {
        border: 'border-l-blue-500',
        dot: 'bg-blue-500',
        badgeCls: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300',
        label: 'Low',
        ringColor: 'stroke-blue-500',
    },
};

const STATUS_CONFIG: Record<string, { badgeCls: string; label: string }> = {
    pending: {
        badgeCls: 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300',
        label: 'Pending',
    },
    reviewed: {
        badgeCls: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300',
        label: 'Reviewed',
    },
    actioned: {
        badgeCls: 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-300',
        label: 'Actioned',
    },
    dismissed: {
        badgeCls: 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400',
        label: 'Dismissed',
    },
    escalated: {
        badgeCls: 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300',
        label: 'Escalated',
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
        hour: '2-digit',
        minute: '2-digit',
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

function ConfidenceGauge({ value, ringColor }: { value: number; ringColor: string }) {
    const percentage = Math.round(value * 100);
    const radius = 40;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (percentage / 100) * circumference;

    return (
        <div className="relative flex size-24 items-center justify-center">
            <svg className="-rotate-90" width="96" height="96" viewBox="0 0 96 96">
                <circle
                    cx="48"
                    cy="48"
                    r={radius}
                    fill="none"
                    className="stroke-muted"
                    strokeWidth="6"
                />
                <circle
                    cx="48"
                    cy="48"
                    r={radius}
                    fill="none"
                    className={ringColor}
                    strokeWidth="6"
                    strokeLinecap="round"
                    strokeDasharray={circumference}
                    strokeDashoffset={offset}
                    style={{ transition: 'stroke-dashoffset 0.6s ease' }}
                />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
                <span className="text-xl font-bold tabular-nums">{percentage}%</span>
                <span className="text-[10px] text-muted-foreground">confidence</span>
            </div>
        </div>
    );
}

function DetailedAnalysisCards({ analysis }: { analysis: Record<string, unknown> }) {
    const entries = Object.entries(analysis);
    if (entries.length === 0) return null;

    return (
        <div className="grid gap-3 sm:grid-cols-2">
            {entries.map(([key, value]) => {
                const label = key
                    .split('_')
                    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
                    .join(' ');

                const displayValue =
                    typeof value === 'string'
                        ? value
                        : typeof value === 'number'
                          ? String(value)
                          : Array.isArray(value)
                            ? value.join(', ')
                            : JSON.stringify(value);

                const isLongText = typeof value === 'string' && value.length > 80;

                if (isLongText) {
                    return (
                        <Card key={key} className="sm:col-span-2">
                            <CardContent className="pt-0">
                                <p className="mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    {label}
                                </p>
                                <p className="text-sm leading-relaxed">{displayValue}</p>
                            </CardContent>
                        </Card>
                    );
                }

                return (
                    <Card key={key}>
                        <CardContent className="pt-0">
                            <p className="mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {label}
                            </p>
                            <p className="text-sm font-medium">{displayValue}</p>
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}

function ActionChecklist({ items }: { items: string[] }) {
    const [checked, setChecked] = useState<Record<number, boolean>>({});

    return (
        <div className="space-y-3">
            {items.map((item, index) => (
                <label
                    key={index}
                    className="flex items-start gap-3 rounded-lg border p-3 transition-colors hover:bg-muted/50"
                >
                    <Checkbox
                        checked={checked[index] ?? false}
                        onCheckedChange={(val) =>
                            setChecked((prev) => ({ ...prev, [index]: !!val }))
                        }
                        className="mt-0.5"
                    />
                    <span
                        className={`text-sm leading-snug ${checked[index] ? 'text-muted-foreground line-through' : ''}`}
                    >
                        {item}
                    </span>
                </label>
            ))}
        </div>
    );
}

export default function FleetAiAnalysisResultsShow({ aiAnalysisResult }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'AI Analysis Results', href: '/fleet/ai-analysis-results' },
        {
            title: `Analysis #${aiAnalysisResult.id}`,
            href: `/fleet/ai-analysis-results/${aiAnalysisResult.id}`,
        },
    ];

    const priorityConfig = PRIORITY_CONFIG[aiAnalysisResult.priority] ?? PRIORITY_CONFIG.low;
    const statusConfig = STATUS_CONFIG[aiAnalysisResult.status] ?? STATUS_CONFIG.pending;
    const TypeIcon = TYPE_ICONS[aiAnalysisResult.analysis_type] ?? BrainCircuit;
    const confidence = aiAnalysisResult.confidence_score != null ? Number(aiAnalysisResult.confidence_score) : null;
    const riskScore = aiAnalysisResult.risk_score != null ? Number(aiAnalysisResult.risk_score) : null;
    const entityLink = entityRoute(aiAnalysisResult.entity_type, aiAnalysisResult.entity_id);

    const reviewerName = aiAnalysisResult.reviewed_by
        ? (aiAnalysisResult.reviewed_by.name ??
              [aiAnalysisResult.reviewed_by.first_name, aiAnalysisResult.reviewed_by.last_name]
                  .filter(Boolean)
                  .join(' ')) || '—'
        : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – AI Analysis #${aiAnalysisResult.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                {/* Back button */}
                <div>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet/ai-analysis-results">
                            <ArrowLeft className="mr-1.5 size-4" />
                            Back to results
                        </Link>
                    </Button>
                </div>

                {/* Header Card */}
                <Card className={`border-l-4 ${priorityConfig.border}`}>
                    <CardContent className="pt-0">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            {/* Left side: icon, type, badges, finding */}
                            <div className="flex-1 space-y-3">
                                <div className="flex flex-wrap items-center gap-2">
                                    <TypeIcon className="size-6 text-primary" />
                                    <h1 className="text-xl font-semibold">
                                        {formatAnalysisType(aiAnalysisResult.analysis_type)}
                                    </h1>
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="outline" className={priorityConfig.badgeCls}>
                                        <span className={`size-1.5 rounded-full ${priorityConfig.dot}`} />
                                        {priorityConfig.label} Priority
                                    </Badge>
                                    <Badge variant="outline" className={statusConfig.badgeCls}>
                                        {statusConfig.label}
                                    </Badge>
                                    {riskScore != null && (
                                        <Badge variant="outline" className="text-muted-foreground">
                                            Risk: {riskScore.toFixed(1)}
                                        </Badge>
                                    )}
                                </div>

                                {aiAnalysisResult.primary_finding && (
                                    <p className="max-w-2xl text-sm leading-relaxed text-foreground">
                                        {aiAnalysisResult.primary_finding}
                                    </p>
                                )}

                                {/* Entity link */}
                                {entityLink && (
                                    <Link
                                        href={entityLink}
                                        className="inline-flex items-center gap-1.5 text-sm text-primary hover:underline"
                                    >
                                        <ExternalLink className="size-3.5" />
                                        View {aiAnalysisResult.entity_type} #{aiAnalysisResult.entity_id}
                                    </Link>
                                )}
                            </div>

                            {/* Right side: confidence gauge */}
                            {confidence != null && (
                                <div className="flex-shrink-0">
                                    <ConfidenceGauge value={confidence} ringColor={priorityConfig.ringColor} />
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Detailed Analysis */}
                {aiAnalysisResult.detailed_analysis != null &&
                    Object.keys(aiAnalysisResult.detailed_analysis).length > 0 && (
                        <section>
                            <div className="mb-3 flex items-center gap-2">
                                <BarChart3 className="size-5 text-muted-foreground" />
                                <h2 className="text-lg font-semibold">Detailed Analysis</h2>
                            </div>
                            <DetailedAnalysisCards analysis={aiAnalysisResult.detailed_analysis} />
                        </section>
                    )}

                {/* Recommendations */}
                {aiAnalysisResult.recommendations && aiAnalysisResult.recommendations.length > 0 && (
                    <section>
                        <div className="mb-3 flex items-center gap-2">
                            <Lightbulb className="size-5 text-muted-foreground" />
                            <h2 className="text-lg font-semibold">Recommendations</h2>
                        </div>
                        <Card>
                            <CardContent className="pt-0">
                                <ul className="space-y-2">
                                    {aiAnalysisResult.recommendations.map((rec, i) => (
                                        <li key={i} className="flex items-start gap-2 text-sm">
                                            <span className="mt-1.5 size-1.5 flex-shrink-0 rounded-full bg-primary" />
                                            {rec}
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    </section>
                )}

                {/* Action Items Checklist */}
                {aiAnalysisResult.action_items && aiAnalysisResult.action_items.length > 0 && (
                    <section>
                        <div className="mb-3 flex items-center gap-2">
                            <Target className="size-5 text-muted-foreground" />
                            <h2 className="text-lg font-semibold">Action Items</h2>
                        </div>
                        <ActionChecklist items={aiAnalysisResult.action_items} />
                    </section>
                )}

                {/* Business Impact */}
                {aiAnalysisResult.business_impact &&
                    Object.keys(aiAnalysisResult.business_impact).length > 0 && (
                        <section>
                            <div className="mb-3 flex items-center gap-2">
                                <AlertTriangle className="size-5 text-muted-foreground" />
                                <h2 className="text-lg font-semibold">Business Impact</h2>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {Object.entries(aiAnalysisResult.business_impact).map(([key, value]) => (
                                    <Card key={key}>
                                        <CardContent className="pt-0">
                                            <p className="mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                                {key.charAt(0).toUpperCase() + key.slice(1)}
                                            </p>
                                            <p className="text-sm">{value}</p>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </section>
                    )}

                {/* Review Section */}
                {(aiAnalysisResult.review_notes || reviewerName || aiAnalysisResult.reviewed_at) && (
                    <section>
                        <div className="mb-3 flex items-center gap-2">
                            <User className="size-5 text-muted-foreground" />
                            <h2 className="text-lg font-semibold">Review</h2>
                        </div>
                        <Card>
                            <CardContent className="space-y-2 pt-0 text-sm">
                                {aiAnalysisResult.review_notes && (
                                    <p>
                                        <span className="font-medium">Notes:</span>{' '}
                                        {aiAnalysisResult.review_notes}
                                    </p>
                                )}
                                {reviewerName && (
                                    <p>
                                        <span className="font-medium">Reviewed by:</span>{' '}
                                        {reviewerName}
                                    </p>
                                )}
                                {aiAnalysisResult.reviewed_at && (
                                    <p>
                                        <span className="font-medium">Reviewed at:</span>{' '}
                                        {formatDate(aiAnalysisResult.reviewed_at)}
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </section>
                )}

                {/* Metadata Footer */}
                <Card className="bg-muted/30">
                    <CardContent className="pt-0">
                        <div className="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-muted-foreground">
                            <span className="inline-flex items-center gap-1.5">
                                <Calendar className="size-3.5" />
                                Created {formatDate(aiAnalysisResult.created_at)}
                            </span>
                            <span className="capitalize">
                                Entity: {aiAnalysisResult.entity_type} #{aiAnalysisResult.entity_id}
                            </span>
                            {aiAnalysisResult.model_name && (
                                <span>
                                    Model: {aiAnalysisResult.model_name}
                                    {aiAnalysisResult.model_version ? ` v${aiAnalysisResult.model_version}` : ''}
                                </span>
                            )}
                            <span>Analysis #{aiAnalysisResult.id}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
