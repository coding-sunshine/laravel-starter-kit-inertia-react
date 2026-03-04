import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Battery, Sparkles } from 'lucide-react';
import { useState } from 'react';

interface ReplacementItem {
    vehicle_id: number;
    recommended_year: number;
    reason: string;
    priority: string;
}
interface ChargingItem {
    type: string;
    count: number;
    location_type: string;
    reason: string;
}
interface TcoSummary {
    current_tco: number;
    projected_ev_tco: number;
    savings: number;
}
interface MilestoneItem {
    year: number;
    description: string;
    target: string;
}
interface PlanResult {
    readiness_score: number;
    replacement_order: ReplacementItem[];
    charging_recommendations: ChargingItem[];
    tco_summary: TcoSummary;
    milestones: MilestoneItem[];
}
interface LatestPlan {
    id: number;
    primary_finding: string;
    detailed_analysis: PlanResult;
    created_at: string;
}
interface Props {
    latestPlan: LatestPlan | null;
    generateUrl: string;
}

export default function FleetElectrificationPlanIndex({
    latestPlan,
    generateUrl,
}: Props) {
    const [generating, setGenerating] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [justGenerated, setJustGenerated] = useState<PlanResult | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Electrification plan', href: '/fleet/electrification-plan' },
    ];

    const plan = justGenerated ?? latestPlan?.detailed_analysis ?? null;

    const handleGenerate = async () => {
        setError(null);
        setJustGenerated(null);
        setGenerating(true);
        try {
            const res = await fetch(generateUrl, {
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
                setError(data?.message ?? 'Failed to generate plan.');
                return;
            }
            if (data.result) setJustGenerated(data.result);
        } finally {
            setGenerating(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Electrification plan" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">
                        Electrification plan
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/fleet">Back to Fleet</Link>
                        </Button>
                        <Button onClick={handleGenerate} disabled={generating}>
                            <Sparkles className="mr-1.5 size-4" />
                            {generating
                                ? 'Generating…'
                                : 'Generate electrification plan'}
                        </Button>
                    </div>
                </div>
                {error && <p className="text-sm text-destructive">{error}</p>}

                {!plan ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-16">
                            <Battery className="size-12 text-muted-foreground" />
                            <p className="mt-2 text-sm text-muted-foreground">
                                No electrification plan yet.
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Click &quot;Generate electrification plan&quot;
                                to create one from your fleet and sustainability
                                data.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-6 lg:grid-cols-2">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    Readiness & TCO
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <p>
                                    <span className="font-medium">
                                        Readiness score:
                                    </span>{' '}
                                    {Number.isFinite(Number(plan.readiness_score))
                                        ? `${Number(plan.readiness_score).toFixed(0)}%`
                                        : 'N/A'}
                                </p>
                                {plan.tco_summary && (
                                    <div className="rounded border p-3 text-sm">
                                        <p>
                                            Current TCO:{' '}
                                            {Number(
                                                plan.tco_summary.current_tco,
                                            ).toLocaleString()}
                                        </p>
                                        <p>
                                            Projected EV TCO:{' '}
                                            {Number(
                                                plan.tco_summary
                                                    .projected_ev_tco,
                                            ).toLocaleString()}
                                        </p>
                                        <p className="font-medium text-green-600">
                                            Savings:{' '}
                                            {Number(
                                                plan.tco_summary.savings,
                                            ).toLocaleString()}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    Replacement order
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {!plan.replacement_order ||
                                plan.replacement_order.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No vehicles in replacement order.
                                    </p>
                                ) : (
                                    <ul className="space-y-2 text-sm">
                                        {plan.replacement_order
                                            .slice(0, 10)
                                            .map((r, i) => (
                                                <li
                                                    key={i}
                                                    className="flex justify-between border-b border-dashed pb-2"
                                                >
                                                    <span>
                                                        Vehicle #{r.vehicle_id}{' '}
                                                        · {r.recommended_year}
                                                    </span>
                                                    <span className="text-muted-foreground capitalize">
                                                        {r.priority}
                                                    </span>
                                                </li>
                                            ))}
                                        {plan.replacement_order.length > 10 && (
                                            <li className="text-muted-foreground">
                                                +
                                                {plan.replacement_order.length -
                                                    10}{' '}
                                                more
                                            </li>
                                        )}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                        <Card className="lg:col-span-2">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    Charging recommendations
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {!plan.charging_recommendations ||
                                plan.charging_recommendations.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No charging recommendations.
                                    </p>
                                ) : (
                                    <ul className="space-y-2 text-sm">
                                        {plan.charging_recommendations.map(
                                            (c, i) => (
                                                <li key={i}>
                                                    {c.type}: {c.count} at{' '}
                                                    {c.location_type} –{' '}
                                                    {c.reason}
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                        <Card className="lg:col-span-2">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    Milestones
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {!plan.milestones ||
                                plan.milestones.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No milestones.
                                    </p>
                                ) : (
                                    <ul className="space-y-2 text-sm">
                                        {plan.milestones.map((m, i) => (
                                            <li key={i}>
                                                <strong>{m.year}</strong>:{' '}
                                                {m.description} – {m.target}
                                            </li>
                                        ))}
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
