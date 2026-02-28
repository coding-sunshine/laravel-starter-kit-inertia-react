import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface CostAllocation { id: number; allocation_date: string; cost_type: string; source_type: string; amount: string; approval_status: string; cost_center?: { name: string }; }
interface Props { costAllocation: CostAllocation; }

export default function FleetCostAllocationsShow({ costAllocation }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Cost allocations', href: '/fleet/cost-allocations' },
        { title: 'View', href: `/fleet/cost-allocations/${costAllocation.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Cost allocation" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Cost allocation</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/cost-allocations/${costAllocation.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/cost-allocations">Back to list</Link></Button>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium">Cost center:</span> {costAllocation.cost_center?.name ?? '—'}</p>
                        <p><span className="font-medium">Date:</span> {new Date(costAllocation.allocation_date).toLocaleDateString()}</p>
                        <p><span className="font-medium">Cost type:</span> {costAllocation.cost_type}</p>
                        <p><span className="font-medium">Source type:</span> {costAllocation.source_type}</p>
                        <p><span className="font-medium">Amount:</span> {costAllocation.amount}</p>
                        <p><span className="font-medium">Approval status:</span> {costAllocation.approval_status}</p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
