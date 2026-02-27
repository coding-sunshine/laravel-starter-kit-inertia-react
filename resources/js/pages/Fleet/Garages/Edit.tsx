import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props { garage: { id: number; name: string } }

export default function FleetGaragesEdit({ garage }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/garages' }, { title: 'Garages', href: '/fleet/garages' }, { title: garage.name, href: `/fleet/garages/${garage.id}` }, { title: 'Edit', href: `/fleet/garages/${garage.id}/edit` }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${garage.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit garage</h1>
                <Button variant="outline" asChild><Link href={`/fleet/garages/${garage.id}`}>Back</Link></Button>
            </div>
        </AppLayout>
    );
}
