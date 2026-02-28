import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Template { id: number; name: string; code?: string; check_type: string; category?: string; workflow_route?: string; completion_percentage_threshold?: number; is_active: boolean; checklist?: { label?: string; result_type?: string }[]; }
interface Props { vehicleCheckTemplate: Template; }

export default function VehicleCheckTemplatesShow({ vehicleCheckTemplate }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicle check templates', href: '/fleet/vehicle-check-templates' },
        { title: vehicleCheckTemplate.name, href: `/fleet/vehicle-check-templates/${vehicleCheckTemplate.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Vehicle check template" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{vehicleCheckTemplate.name}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/vehicle-check-templates/${vehicleCheckTemplate.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/vehicle-check-templates">Back</Link></Button>
                    </div>
                </div>
                <dl className="max-w-md space-y-2 rounded-lg border p-6">
                    <div><dt className="text-sm text-muted-foreground">Name</dt><dd className="font-medium">{vehicleCheckTemplate.name}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Code</dt><dd>{vehicleCheckTemplate.code ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Check type</dt><dd>{vehicleCheckTemplate.check_type}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Category</dt><dd>{vehicleCheckTemplate.category ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Workflow route</dt><dd>{vehicleCheckTemplate.workflow_route ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Completion % threshold</dt><dd>{vehicleCheckTemplate.completion_percentage_threshold ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Active</dt><dd>{vehicleCheckTemplate.is_active ? 'Yes' : 'No'}</dd></div>
                </dl>
            </div>
        </AppLayout>
    );
}
