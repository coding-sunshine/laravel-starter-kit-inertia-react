import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Plan {
    id: number;
    plan_type: string;
    title?: string;
    objectives?: string;
    status: string;
    due_date?: string;
    completed_at?: string;
    notes?: string;
    driver?: { id: number; first_name: string; last_name: string };
    assigned_coach?: { id: number; name: string } | null;
}
interface Props { driverCoachingPlan: Plan; }

export default function FleetDriverCoachingPlansShow({ driverCoachingPlan }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Driver coaching plans', href: '/fleet/driver-coaching-plans' },
        { title: 'Plan', href: `/fleet/driver-coaching-plans/${driverCoachingPlan.id}` },
    ];
    const driverName = driverCoachingPlan.driver ? driverCoachingPlan.driver.first_name + ' ' + driverCoachingPlan.driver.last_name : '—';
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Coaching plan" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Coaching plan</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/driver-coaching-plans/${driverCoachingPlan.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/driver-coaching-plans">Back</Link></Button>
                    </div>
                </div>
                <dl className="max-w-md space-y-2 rounded-lg border p-6">
                    <div><dt className="text-sm text-muted-foreground">Driver</dt><dd className="font-medium">{driverName}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Plan type</dt><dd>{driverCoachingPlan.plan_type}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Title</dt><dd>{driverCoachingPlan.title ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Status</dt><dd>{driverCoachingPlan.status}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Due date</dt><dd>{driverCoachingPlan.due_date ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Completed at</dt><dd>{driverCoachingPlan.completed_at ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Assigned coach</dt><dd>{driverCoachingPlan.assigned_coach?.name ?? '—'}</dd></div>
                    {driverCoachingPlan.objectives && <div><dt className="text-sm text-muted-foreground">Objectives</dt><dd className="whitespace-pre-wrap">{driverCoachingPlan.objectives}</dd></div>}
                    {driverCoachingPlan.notes && <div><dt className="text-sm text-muted-foreground">Notes</dt><dd className="whitespace-pre-wrap">{driverCoachingPlan.notes}</dd></div>}
                </dl>
            </div>
        </AppLayout>
    );
}
