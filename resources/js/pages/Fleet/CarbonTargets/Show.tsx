import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface CarbonTargetRecord {
    id: number;
    name: string;
    description?: string | null;
    period: string;
    target_year: number;
    target_co2_kg: string | number;
    baseline_co2_kg?: string | number | null;
    is_active: boolean;
}
interface Props { carbonTarget: CarbonTargetRecord; }

export default function FleetCarbonTargetsShow({ carbonTarget }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Carbon targets', href: '/fleet/carbon-targets' },
        { title: carbonTarget.name, href: `/fleet/carbon-targets/${carbonTarget.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${carbonTarget.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">{carbonTarget.name}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/carbon-targets/${carbonTarget.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/carbon-targets">Back to list</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle className="text-base">Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Name:</span> {carbonTarget.name}</p>
                        {carbonTarget.description && <p><span className="font-medium">Description:</span> {carbonTarget.description}</p>}
                        <p><span className="font-medium">Period:</span> {carbonTarget.period}</p>
                        <p><span className="font-medium">Target year:</span> {carbonTarget.target_year}</p>
                        <p><span className="font-medium">Target CO₂ (kg):</span> {String(carbonTarget.target_co2_kg)}</p>
                        {carbonTarget.baseline_co2_kg != null && <p><span className="font-medium">Baseline CO₂ (kg):</span> {String(carbonTarget.baseline_co2_kg)}</p>}
                        <p><span className="font-medium">Active:</span> {carbonTarget.is_active ? 'Yes' : 'No'}</p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
