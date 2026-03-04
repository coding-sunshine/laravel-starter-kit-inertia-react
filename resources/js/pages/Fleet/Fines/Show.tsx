import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface FineRecord {
    id: number;
    fine_type: string;
    offence_description?: string | null;
    offence_date: string;
    amount: string | number;
    amount_paid: string | number;
    due_date?: string | null;
    appeal_deadline?: string | null;
    status: string;
    appeal_notes?: string | null;
    external_reference?: string | null;
    issuing_authority?: string | null;
    vehicle?: { id: number; registration: string };
    driver?: { id: number; first_name: string; last_name: string } | null;
}
interface Props {
    fine: FineRecord;
}

export default function FleetFinesShow({ fine }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Fines', href: '/fleet/fines' },
        { title: `Fine #${fine.id}`, href: `/fleet/fines/${fine.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Fine #${fine.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">Fine #{fine.id}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={`/fleet/fines/${fine.id}/edit`}>
                                Edit
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/fines">Back</Link>
                        </Button>
                    </div>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Type:</span>{' '}
                            {fine.fine_type}
                        </p>
                        <p>
                            <span className="font-medium">Status:</span>{' '}
                            {fine.status}
                        </p>
                        <p>
                            <span className="font-medium">Offence date:</span>{' '}
                            {new Date(fine.offence_date).toLocaleDateString()}
                        </p>
                        <p>
                            <span className="font-medium">Amount:</span>{' '}
                            {fine.amount}
                        </p>
                        <p>
                            <span className="font-medium">Amount paid:</span>{' '}
                            {fine.amount_paid}
                        </p>
                        {fine.vehicle && (
                            <p>
                                <span className="font-medium">Vehicle:</span>{' '}
                                <Link
                                    href={`/fleet/vehicles/${fine.vehicle.id}`}
                                    className="underline"
                                >
                                    {fine.vehicle.registration}
                                </Link>
                            </p>
                        )}
                        {fine.driver && (
                            <p>
                                <span className="font-medium">Driver:</span>{' '}
                                <Link
                                    href={`/fleet/drivers/${fine.driver.id}`}
                                    className="underline"
                                >
                                    {fine.driver.first_name}{' '}
                                    {fine.driver.last_name}
                                </Link>
                            </p>
                        )}
                        {fine.due_date && (
                            <p>
                                <span className="font-medium">Due date:</span>{' '}
                                {new Date(fine.due_date).toLocaleDateString()}
                            </p>
                        )}
                        {fine.issuing_authority && (
                            <p>
                                <span className="font-medium">
                                    Issuing authority:
                                </span>{' '}
                                {fine.issuing_authority}
                            </p>
                        )}
                        {fine.offence_description && (
                            <p>
                                <span className="font-medium">
                                    Description:
                                </span>{' '}
                                {fine.offence_description}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
