import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props { garage: { id: number; name: string; type: string; is_active: boolean } }

export default function FleetGaragesShow({ garage }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/garages' }, { title: 'Garages', href: '/fleet/garages' }, { title: garage.name, href: `/fleet/garages/${garage.id}` }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${garage.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">{garage.name}</h1>
                <p className="text-muted-foreground">Type: {garage.type} · {garage.is_active ? 'Active' : 'Inactive'}</p>
                <Button variant="outline" asChild><Link href="/fleet/garages">Back to garages</Link></Button>
            </div>
        </AppLayout>
    );
}
