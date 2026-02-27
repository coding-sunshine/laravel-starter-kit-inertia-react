import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props { operatorLicence: { id: number; license_number: string; license_type: string; status: string; expiry_date: string } }

export default function FleetOperatorLicencesShow({ operatorLicence }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/operator-licences' }, { title: 'Operator licences', href: '/fleet/operator-licences' }, { title: operatorLicence.license_number, href: `/fleet/operator-licences/${operatorLicence.id}` }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${operatorLicence.license_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">{operatorLicence.license_number}</h1>
                <p className="text-muted-foreground">Type: {operatorLicence.license_type} · Status: {operatorLicence.status} · Expiry: {operatorLicence.expiry_date}</p>
                <Button variant="outline" asChild><Link href="/fleet/operator-licences">Back to operator licences</Link></Button>
            </div>
        </AppLayout>
    );
}
