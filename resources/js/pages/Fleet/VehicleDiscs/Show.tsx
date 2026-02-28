import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Disc {
    id: number;
    disc_number: string;
    valid_from: string;
    valid_to: string;
    status: string;
    vehicle?: { id: number; registration: string };
    operator_licence?: { id: number; license_number: string };
}
interface Props { vehicleDisc: Disc; }

export default function VehicleDiscsShow({ vehicleDisc }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicle discs', href: '/fleet/vehicle-discs' },
        { title: vehicleDisc.disc_number, href: `/fleet/vehicle-discs/${vehicleDisc.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Vehicle disc" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{vehicleDisc.disc_number}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild><Link href={`/fleet/vehicle-discs/${vehicleDisc.id}/edit`}>Edit</Link></Button>
                        <Button variant="ghost" size="sm" asChild><Link href="/fleet/vehicle-discs">Back</Link></Button>
                    </div>
                </div>
                <dl className="max-w-md space-y-2 rounded-lg border p-6">
                    <div><dt className="text-sm text-muted-foreground">Disc number</dt><dd className="font-medium">{vehicleDisc.disc_number}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Vehicle</dt><dd>{vehicleDisc.vehicle?.registration ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Operator licence</dt><dd>{vehicleDisc.operator_licence?.license_number ?? '—'}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Valid from</dt><dd>{vehicleDisc.valid_from}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Valid to</dt><dd>{vehicleDisc.valid_to}</dd></div>
                    <div><dt className="text-sm text-muted-foreground">Status</dt><dd>{vehicleDisc.status}</dd></div>
                </dl>
            </div>
        </AppLayout>
    );
}
