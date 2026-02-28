import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Observation {
    id: number;
    title: string;
    description?: string;
    category: string;
    location_description?: string;
    status: string;
    action_taken?: string;
    reported_by?: { id: number; name: string };
    location?: { id: number; name: string };
}
interface Props { safetyObservation: Observation; }

export default function SafetyObservationsShow({ safetyObservation }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Safety observations', href: '/fleet/safety-observations' },
        { title: safetyObservation.title, href: `/fleet/safety-observations/${safetyObservation.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Safety observation" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{safetyObservation.title}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/safety-observations/${safetyObservation.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/safety-observations">Back</Link></Button>
                    </div>
                </div>
                <dl className="max-w-md space-y-2 rounded-lg border p-6">
                    <div><dt className="text-sm text-muted-foreground">Title</dt><dd className="font-medium">{safetyObservation.title}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Category</dt><dd>{safetyObservation.category}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Status</dt><dd>{safetyObservation.status}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Reported by</dt><dd>{safetyObservation.reported_by?.name ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Location</dt><dd>{safetyObservation.location?.name ?? safetyObservation.location_description ?? '—'}</dd></div>
                    {safetyObservation.description && <div><dt className="text-sm text-muted-foreground">Description</dt><dd className="whitespace-pre-wrap">{safetyObservation.description}</dd></div>}
                    {safetyObservation.action_taken && <div><dt className="text-sm text-muted-foreground">Action taken</dt><dd className="whitespace-pre-wrap">{safetyObservation.action_taken}</dd></div>}
                </dl>
            </div>
        </AppLayout>
    );
}
