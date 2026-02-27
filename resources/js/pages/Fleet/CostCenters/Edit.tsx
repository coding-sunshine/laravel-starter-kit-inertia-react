import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props { costCenter: { id: number; name: string } }

export default function FleetCostCentersEdit({ costCenter }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/cost-centers' },
        { title: 'Cost centers', href: '/fleet/cost-centers' },
        { title: costCenter.name, href: `/fleet/cost-centers/${costCenter.id}` },
        { title: 'Edit', href: `/fleet/cost-centers/${costCenter.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${costCenter.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit cost center</h1>
                <Button variant="outline" asChild><Link href={`/fleet/cost-centers/${costCenter.id}`}>Back</Link></Button>
            </div>
        </AppLayout>
    );
}
