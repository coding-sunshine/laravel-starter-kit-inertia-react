import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface WorkshopBay {
    id: number;
    name: string;
    code?: string;
    status: string;
    description?: string;
    is_active: boolean;
    garage?: { id: number; name: string };
}
interface Props {
    workshopBay: WorkshopBay;
}

export default function FleetWorkshopBaysShow({ workshopBay }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Workshop bays', href: '/fleet/workshop-bays' },
        { title: 'View', href: `/fleet/workshop-bays/${workshopBay.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Workshop bay" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {workshopBay.name}
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/workshop-bays/${workshopBay.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/workshop-bays">
                                Back to list
                            </Link>
                        </Button>
                    </div>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Name:</span>{' '}
                            {workshopBay.name}
                        </p>
                        {workshopBay.code && (
                            <p>
                                <span className="font-medium">Code:</span>{' '}
                                {workshopBay.code}
                            </p>
                        )}
                        {workshopBay.garage && (
                            <p>
                                <span className="font-medium">Garage:</span>{' '}
                                {workshopBay.garage.name}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">Status:</span>{' '}
                            {workshopBay.status}
                        </p>
                        <p>
                            <span className="font-medium">Active:</span>{' '}
                            {workshopBay.is_active ? 'Yes' : 'No'}
                        </p>
                        {workshopBay.description && (
                            <p>
                                <span className="font-medium">
                                    Description:
                                </span>{' '}
                                {workshopBay.description}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
