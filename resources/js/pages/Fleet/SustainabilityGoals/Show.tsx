import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface SustainabilityGoalRecord {
    id: number;
    title: string;
    description?: string | null;
    status: string;
    target_date?: string | null;
    target_value?: string | number | null;
    target_unit?: string | null;
}
interface Props {
    sustainabilityGoal: SustainabilityGoalRecord;
}

export default function FleetSustainabilityGoalsShow({
    sustainabilityGoal,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Sustainability goals', href: '/fleet/sustainability-goals' },
        {
            title: sustainabilityGoal.title,
            href: `/fleet/sustainability-goals/${sustainabilityGoal.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${sustainabilityGoal.title}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">
                        {sustainabilityGoal.title}
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/sustainability-goals/${sustainabilityGoal.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/sustainability-goals">
                                Back to list
                            </Link>
                        </Button>
                    </div>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Title:</span>{' '}
                            {sustainabilityGoal.title}
                        </p>
                        {sustainabilityGoal.description && (
                            <p>
                                <span className="font-medium">
                                    Description:
                                </span>{' '}
                                {sustainabilityGoal.description}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">Status:</span>{' '}
                            {sustainabilityGoal.status}
                        </p>
                        {sustainabilityGoal.target_date && (
                            <p>
                                <span className="font-medium">
                                    Target date:
                                </span>{' '}
                                {new Date(
                                    sustainabilityGoal.target_date,
                                ).toLocaleDateString()}
                            </p>
                        )}
                        {sustainabilityGoal.target_value != null && (
                            <p>
                                <span className="font-medium">
                                    Target value:
                                </span>{' '}
                                {String(sustainabilityGoal.target_value)}
                                {sustainabilityGoal.target_unit
                                    ? ` ${sustainabilityGoal.target_unit}`
                                    : ''}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
