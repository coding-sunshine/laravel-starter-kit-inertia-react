'use client';

import { cn } from '@/lib/utils';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { ArrowRight, BrainCircuit } from 'lucide-react';

interface AiInsightBannerProps {
    id: number;
    primaryFinding: string;
    priority: 'high' | 'medium' | 'low' | 'critical';
    analysisType: string;
    recommendations?: string[] | null;
    className?: string;
}

const PRIORITY_CONFIG: Record<string, { border: string; icon: string; label: string }> = {
    critical: {
        border: 'border-l-red-500 dark:border-l-red-400',
        icon: 'text-red-500 dark:text-red-400',
        label: 'Critical',
    },
    high: {
        border: 'border-l-red-500 dark:border-l-red-400',
        icon: 'text-red-500 dark:text-red-400',
        label: 'High Priority',
    },
    medium: {
        border: 'border-l-amber-500 dark:border-l-amber-400',
        icon: 'text-amber-500 dark:text-amber-400',
        label: 'Medium Priority',
    },
    low: {
        border: 'border-l-blue-500 dark:border-l-blue-400',
        icon: 'text-blue-500 dark:text-blue-400',
        label: 'Low Priority',
    },
};

function formatAnalysisType(type: string): string {
    return type
        .split('_')
        .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
        .join(' ');
}

export function AiInsightBanner({
    id,
    primaryFinding,
    priority,
    analysisType,
    recommendations,
    className,
}: AiInsightBannerProps) {
    const config = PRIORITY_CONFIG[priority] ?? PRIORITY_CONFIG.low;
    const firstRecommendation = recommendations?.[0];

    return (
        <Card className={cn('border-l-4', config.border, className)}>
            <CardContent className="flex items-start gap-4 py-4">
                <div className="mt-0.5 shrink-0">
                    <BrainCircuit className={cn('size-5', config.icon)} />
                </div>
                <div className="min-w-0 flex-1 space-y-1">
                    <div className="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                        <span>AI {formatAnalysisType(analysisType)}</span>
                        <span>·</span>
                        <span>{config.label}</span>
                    </div>
                    <p className="text-sm font-medium text-foreground">{primaryFinding}</p>
                    {firstRecommendation && (
                        <p className="text-sm text-muted-foreground">
                            <span className="font-medium">Recommendation:</span> {firstRecommendation}
                        </p>
                    )}
                </div>
                <Link
                    href={`/fleet/ai-analysis-results/${id}`}
                    className="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-primary hover:underline"
                >
                    View Analysis
                    <ArrowRight className="size-3.5" />
                </Link>
            </CardContent>
        </Card>
    );
}
