import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props { trailer: { id: number; registration: string | null; fleet_number: string | null; type: string; status: string } }

export default function FleetTrailersShow({ trailer }: Props) {
    const name = trailer.registration || trailer.fleet_number || `Trailer #${trailer.id}`;
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/trailers' }, { title: 'Trailers', href: '/fleet/trailers' }, { title: name, href: `/fleet/trailers/${trailer.id}` }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">{name}</h1>
                <p className="text-muted-foreground">Type: {trailer.type} · Status: {trailer.status}</p>
                <Button variant="outline" asChild><Link href="/fleet/trailers">Back to trailers</Link></Button>
            </div>
        </AppLayout>
    );
}
