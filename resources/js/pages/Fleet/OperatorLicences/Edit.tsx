import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props { operatorLicence: { id: number; license_number: string } }

export default function FleetOperatorLicencesEdit({ operatorLicence }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }, { title: 'Fleet', href: '/fleet/operator-licences' }, { title: 'Operator licences', href: '/fleet/operator-licences' }, { title: operatorLicence.license_number, href: `/fleet/operator-licences/${operatorLicence.id}` }, { title: 'Edit', href: `/fleet/operator-licences/${operatorLicence.id}/edit` }];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${operatorLicence.license_number}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Edit operator licence</h1>
                <Button variant="outline" asChild><Link href={`/fleet/operator-licences/${operatorLicence.id}`}>Back</Link></Button>
            </div>
        </AppLayout>
    );
}
