'use client';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowRight, BrainCircuit } from 'lucide-react';

interface AiPrediction {
    id: number;
    priority: string;
    primary_finding: string;
    analysis_type: string;
    entity_type: string;
    entity_id: number;
}

interface FleetAiPanelProps {
    predictions: AiPrediction[];
    className?: string;
}

const PRIORITY_CONFIG: Record<string, { dot: string; label: string }> = {
    critical: { dot: 'bg-red-500', label: 'Critical' },
    high: { dot: 'bg-red-500', label: 'High' },
    medium: { dot: 'bg-amber-500', label: 'Medium' },
    low: { dot: 'bg-blue-500', label: 'Low' },
};

function formatAnalysisType(type: string): string {
    return type
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

export function FleetAiPanel({ predictions, className }: FleetAiPanelProps) {
    return (
        <Card className={cn('flex flex-col', className)}>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                    <BrainCircuit className="size-5 text-primary" />
                    AI Insights
                </CardTitle>
            </CardHeader>
            <CardContent className="flex-1 pt-0">
                {predictions.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-2 py-8 text-center">
                        <BrainCircuit className="size-8 text-muted-foreground/40" />
                        <p className="text-sm text-muted-foreground">
                            No AI insights yet. Run an analysis to get started.
                        </p>
                    </div>
                ) : (
                    <ul className="divide-y divide-border">
                        {predictions.map((prediction) => {
                            const config = PRIORITY_CONFIG[prediction.priority] ?? PRIORITY_CONFIG.low;

                            return (
                                <li key={prediction.id} className="py-3 first:pt-0 last:pb-0">
                                    <div className="flex items-start gap-3">
                                        {/* Priority dot */}
                                        <span
                                            className={cn(
                                                'mt-1.5 size-2 shrink-0 rounded-full',
                                                config.dot,
                                            )}
                                        />

                                        {/* Content */}
                                        <div className="min-w-0 flex-1">
                                            <span className="text-xs font-medium text-muted-foreground">
                                                {formatAnalysisType(prediction.analysis_type)}
                                            </span>
                                            <p className="mt-0.5 text-sm leading-snug">
                                                {prediction.primary_finding}
                                            </p>
                                        </div>

                                        {/* CTA link */}
                                        <Link
                                            href={`/fleet/ai-analysis-results/${prediction.id}`}
                                            className="mt-0.5 inline-flex shrink-0 items-center gap-0.5 text-xs font-medium text-primary hover:underline"
                                        >
                                            View
                                            <ArrowRight className="size-3" />
                                        </Link>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
