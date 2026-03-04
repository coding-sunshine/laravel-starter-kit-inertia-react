import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface FuelTransactionRecord {
    id: number;
    transaction_timestamp: string;
    fuel_type: string;
    total_cost: number;
    vehicle?: { id: number; registration: string };
    fuel_card?: { id: number; card_number: string };
}
interface Props {
    fuelTransaction: FuelTransactionRecord;
}

export default function FleetFuelTransactionsShow({ fuelTransaction }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/fuel-transactions' },
        { title: 'Fuel transactions', href: '/fleet/fuel-transactions' },
        {
            title: `#${fuelTransaction.id}`,
            href: `/fleet/fuel-transactions/${fuelTransaction.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Fuel transaction #${fuelTransaction.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">{`Fuel transaction #${fuelTransaction.id}`}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={`/fleet/fuel-transactions/${fuelTransaction.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/fuel-transactions">
                                Back to fuel transactions
                            </Link>
                        </Button>
                    </div>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Date:</span>{' '}
                            {new Date(
                                fuelTransaction.transaction_timestamp,
                            ).toLocaleString()}
                        </p>
                        <p>
                            <span className="font-medium">Fuel type:</span>{' '}
                            {fuelTransaction.fuel_type}
                        </p>
                        <p>
                            <span className="font-medium">Total cost:</span>{' '}
                            {fuelTransaction.total_cost}
                        </p>
                        {fuelTransaction.vehicle && (
                            <p>
                                <span className="font-medium">Vehicle:</span>{' '}
                                <Link
                                    href={`/fleet/vehicles/${fuelTransaction.vehicle.id}`}
                                    className="underline"
                                >
                                    {fuelTransaction.vehicle.registration}
                                </Link>
                            </p>
                        )}
                        {fuelTransaction.fuel_card && (
                            <p>
                                <span className="font-medium">Fuel card:</span>{' '}
                                <Link
                                    href={`/fleet/fuel-cards/${fuelTransaction.fuel_card.id}`}
                                    className="underline"
                                >
                                    {fuelTransaction.fuel_card.card_number}
                                </Link>
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
