import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface TyreInventoryItem {
    id: number;
    size: string;
    brand?: string;
    pattern?: string;
    category?: string;
    quantity?: number;
    min_quantity?: number;
    unit_cost?: string;
    storage_location?: string;
    is_active?: boolean;
}
interface Props {
    tyreInventory: TyreInventoryItem;
}

export default function FleetTyreInventoryShow({ tyreInventory }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Tyre inventory', href: '/fleet/tyre-inventory' },
        { title: 'View', href: `/fleet/tyre-inventory/${tyreInventory.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Tyre inventory item" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {tyreInventory.size}{' '}
                        {tyreInventory.brand ? `– ${tyreInventory.brand}` : ''}
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/tyre-inventory/${tyreInventory.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/tyre-inventory">
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
                            <span className="font-medium">Size:</span>{' '}
                            {tyreInventory.size}
                        </p>
                        {tyreInventory.brand && (
                            <p>
                                <span className="font-medium">Brand:</span>{' '}
                                {tyreInventory.brand}
                            </p>
                        )}
                        {tyreInventory.pattern && (
                            <p>
                                <span className="font-medium">Pattern:</span>{' '}
                                {tyreInventory.pattern}
                            </p>
                        )}
                        {tyreInventory.category && (
                            <p>
                                <span className="font-medium">Category:</span>{' '}
                                {tyreInventory.category}
                            </p>
                        )}
                        {tyreInventory.quantity != null && (
                            <p>
                                <span className="font-medium">Quantity:</span>{' '}
                                {tyreInventory.quantity}
                            </p>
                        )}
                        {tyreInventory.unit_cost != null && (
                            <p>
                                <span className="font-medium">Unit cost:</span>{' '}
                                {tyreInventory.unit_cost}
                            </p>
                        )}
                        {tyreInventory.storage_location && (
                            <p>
                                <span className="font-medium">
                                    Storage location:
                                </span>{' '}
                                {tyreInventory.storage_location}
                            </p>
                        )}
                        {tyreInventory.is_active != null && (
                            <p>
                                <span className="font-medium">Active:</span>{' '}
                                {tyreInventory.is_active ? 'Yes' : 'No'}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
