import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface PartsInventoryItem {
    id: number;
    part_number: string;
    description?: string;
    category?: string;
    quantity?: number;
    min_quantity?: number;
    unit?: string;
    unit_cost?: string;
    storage_location?: string;
    garage?: { id: number; name: string };
    supplier?: { id: number; name: string };
}
interface Props {
    partsInventory: PartsInventoryItem;
}

export default function FleetPartsInventoryShow({ partsInventory }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Parts inventory', href: '/fleet/parts-inventory' },
        { title: 'View', href: `/fleet/parts-inventory/${partsInventory.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Parts inventory item" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {partsInventory.part_number}
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/parts-inventory/${partsInventory.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/parts-inventory">
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
                            <span className="font-medium">Part number:</span>{' '}
                            {partsInventory.part_number}
                        </p>
                        {partsInventory.description && (
                            <p>
                                <span className="font-medium">
                                    Description:
                                </span>{' '}
                                {partsInventory.description}
                            </p>
                        )}
                        {partsInventory.category && (
                            <p>
                                <span className="font-medium">Category:</span>{' '}
                                {partsInventory.category}
                            </p>
                        )}
                        {partsInventory.garage && (
                            <p>
                                <span className="font-medium">Garage:</span>{' '}
                                {partsInventory.garage.name}
                            </p>
                        )}
                        {partsInventory.supplier && (
                            <p>
                                <span className="font-medium">Supplier:</span>{' '}
                                {partsInventory.supplier.name}
                            </p>
                        )}
                        {partsInventory.quantity != null && (
                            <p>
                                <span className="font-medium">Quantity:</span>{' '}
                                {partsInventory.quantity}{' '}
                                {partsInventory.unit ?? ''}
                            </p>
                        )}
                        {partsInventory.unit_cost != null && (
                            <p>
                                <span className="font-medium">Unit cost:</span>{' '}
                                {partsInventory.unit_cost}
                            </p>
                        )}
                        {partsInventory.storage_location && (
                            <p>
                                <span className="font-medium">
                                    Storage location:
                                </span>{' '}
                                {partsInventory.storage_location}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
