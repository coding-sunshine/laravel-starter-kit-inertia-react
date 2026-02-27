import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props { costCenter: { id: number; code: string; name: string; cost_center_type: string; is_active: boolean } }

export default function FleetCostCentersShow({ costCenter }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/cost-centers' },
        { title: 'Cost centers', href: '/fleet/cost-centers' },
        { title: costCenter.name, href: `/fleet/cost-centers/${costCenter.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${costCenter.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">{costCenter.name}</h1>
                <p className="text-muted-foreground">Code: {costCenter.code} · Type: {costCenter.cost_center_type} · {costCenter.is_active ? 'Active' : 'Inactive'}</p>
                <Button variant="outline" asChild><Link href="/fleet/cost-centers">Back to cost centers</Link></Button>
            </div>
        </AppLayout>
    );
}
