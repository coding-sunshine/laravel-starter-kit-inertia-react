'use client';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, CheckCircle2, ShieldAlert } from 'lucide-react';

interface FleetHealthBannerProps {
    score: number;
    summary?: string | null;
    breakdown?: {
        open_alerts: number;
        overdue_work_orders: number;
        compliance_pct: number;
        compliance_label: string;
        open_defects: number;
        vehicles: number;
    } | null;
    className?: string;
}

function getScoreColor(score: number) {
    if (score >= 80) return { ring: 'text-emerald-500', bg: 'bg-emerald-500', label: 'Healthy' };
    if (score >= 60) return { ring: 'text-amber-500', bg: 'bg-amber-500', label: 'Needs Attention' };
    return { ring: 'text-red-500', bg: 'bg-red-500', label: 'Critical' };
}

function ScoreCircle({ score }: { score: number }) {
    const { ring, label } = getScoreColor(score);
    const circumference = 2 * Math.PI * 40;
    const offset = circumference - (score / 100) * circumference;

    return (
        <div className="relative flex flex-col items-center gap-1">
            <svg width="96" height="96" viewBox="0 0 96 96" className="-rotate-90">
                <circle
                    cx="48"
                    cy="48"
                    r="40"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="6"
                    className="text-muted/30"
                />
                <circle
                    cx="48"
                    cy="48"
                    r="40"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="6"
                    strokeLinecap="round"
                    strokeDasharray={circumference}
                    strokeDashoffset={offset}
                    className={cn('transition-all duration-700 ease-out', ring)}
                />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
                <span className="text-2xl font-bold tabular-nums">{score}</span>
                <span className="text-[10px] font-medium text-muted-foreground uppercase tracking-wide">
                    / 100
                </span>
            </div>
            <span className={cn('text-xs font-medium', ring)}>{label}</span>
        </div>
    );
}

function StatusIcon({ score }: { score: number }) {
    if (score >= 80) return <CheckCircle2 className="size-5 text-emerald-500" />;
    if (score >= 60) return <AlertTriangle className="size-5 text-amber-500" />;
    return <ShieldAlert className="size-5 text-red-500" />;
}

export function FleetHealthBanner({ score, summary, breakdown, className }: FleetHealthBannerProps) {
    const { bg } = getScoreColor(score);

    return (
        <Card className={cn('relative overflow-hidden', className)}>
            {/* Subtle accent bar at top */}
            <div className={cn('absolute inset-x-0 top-0 h-1', bg)} />

            <CardContent className="pt-5">
                <div className="flex flex-col gap-6 md:flex-row md:items-center">
                    {/* Score circle */}
                    <div className="flex shrink-0 justify-center md:justify-start">
                        <ScoreCircle score={score} />
                    </div>

                    {/* Summary + breakdown */}
                    <div className="flex min-w-0 flex-1 flex-col gap-3">
                        <div className="flex items-start gap-2">
                            <StatusIcon score={score} />
                            <div className="min-w-0">
                                <h3 className="text-sm font-semibold leading-tight">
                                    Fleet Health
                                </h3>
                                {summary && (
                                    <p className="mt-1 text-sm text-muted-foreground leading-relaxed">
                                        {summary}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Breakdown row */}
                        {breakdown && (
                            <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                <span className="inline-flex items-center gap-1">
                                    <span className="size-1.5 rounded-full bg-red-500" />
                                    {breakdown.open_alerts} active alert{breakdown.open_alerts !== 1 ? 's' : ''}
                                </span>
                                <span className="inline-flex items-center gap-1">
                                    <span className="size-1.5 rounded-full bg-amber-500" />
                                    {breakdown.overdue_work_orders} overdue work order{breakdown.overdue_work_orders !== 1 ? 's' : ''}
                                </span>
                                <span className="inline-flex items-center gap-1">
                                    <span className="size-1.5 rounded-full bg-blue-500" />
                                    {breakdown.compliance_label} compliance
                                </span>
                            </div>
                        )}
                    </div>

                    {/* CTA */}
                    <div className="flex shrink-0 md:self-center">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/fleet/alerts">
                                View Details
                                <ArrowRight className="ml-1 size-3.5" />
                            </Link>
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
