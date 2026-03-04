import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface PartsSupplier {
    id: number;
    name: string;
    code?: string;
    contact_name?: string;
    contact_phone?: string;
    contact_email?: string;
    address?: string;
    postcode?: string;
    city?: string;
    payment_terms?: string;
    is_active?: boolean;
}
interface Props {
    partsSupplier: PartsSupplier;
}

export default function FleetPartsSuppliersShow({ partsSupplier }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Parts suppliers', href: '/fleet/parts-suppliers' },
        { title: 'View', href: `/fleet/parts-suppliers/${partsSupplier.id}` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Parts supplier" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {partsSupplier.name}
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/parts-suppliers/${partsSupplier.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/parts-suppliers">
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
                            {partsSupplier.name}
                        </p>
                        {partsSupplier.code && (
                            <p>
                                <span className="font-medium">Code:</span>{' '}
                                {partsSupplier.code}
                            </p>
                        )}
                        {partsSupplier.contact_name && (
                            <p>
                                <span className="font-medium">Contact:</span>{' '}
                                {partsSupplier.contact_name}
                            </p>
                        )}
                        {partsSupplier.contact_email && (
                            <p>
                                <span className="font-medium">Email:</span>{' '}
                                {partsSupplier.contact_email}
                            </p>
                        )}
                        {partsSupplier.contact_phone && (
                            <p>
                                <span className="font-medium">Phone:</span>{' '}
                                {partsSupplier.contact_phone}
                            </p>
                        )}
                        {partsSupplier.address && (
                            <p>
                                <span className="font-medium">Address:</span>{' '}
                                {partsSupplier.address}
                            </p>
                        )}
                        {(partsSupplier.city || partsSupplier.postcode) && (
                            <p>
                                <span className="font-medium">
                                    City / Postcode:
                                </span>{' '}
                                {[partsSupplier.city, partsSupplier.postcode]
                                    .filter(Boolean)
                                    .join(' ')}
                            </p>
                        )}
                        {partsSupplier.payment_terms && (
                            <p>
                                <span className="font-medium">
                                    Payment terms:
                                </span>{' '}
                                {partsSupplier.payment_terms}
                            </p>
                        )}
                        {partsSupplier.is_active != null && (
                            <p>
                                <span className="font-medium">Active:</span>{' '}
                                {partsSupplier.is_active ? 'Yes' : 'No'}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
