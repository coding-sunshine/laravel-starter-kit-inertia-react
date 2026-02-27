import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { MapPin, Pencil } from 'lucide-react';

import { Button } from '@/components/ui/button';

interface LocationRecord {
    id: number;
    name: string;
    type: string;
    address: string;
    postcode: string | null;
    city: string | null;
    country: string;
    contact_name: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    notes: string | null;
    is_active: boolean;
}

interface Props {
    location: LocationRecord;
}

export default function FleetLocationsShow({ location }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/locations' },
        { title: 'Locations', href: '/fleet/locations' },
        { title: location.name, href: `/fleet/locations/${location.id}` },
    ];

    const addressParts = [location.address, location.city, location.postcode, location.country].filter(Boolean);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${location.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{location.name}</h1>
                    <Button asChild>
                        <Link href={`/fleet/locations/${location.id}/edit`}>
                            <Pencil className="mr-2 size-4" />
                            Edit
                        </Link>
                    </Button>
                </div>
                <div className="flex items-start gap-4 rounded-lg border p-4">
                    <MapPin className="mt-0.5 size-5 text-muted-foreground" />
                    <div className="space-y-1">
                        <p className="text-sm font-medium text-muted-foreground">Type</p>
                        <p className="capitalize">{location.type.replace('_', ' ')}</p>
                        <p className="mt-2 text-sm font-medium text-muted-foreground">Address</p>
                        <p>{addressParts.join(', ')}</p>
                        {location.contact_name && (
                            <>
                                <p className="mt-2 text-sm font-medium text-muted-foreground">Contact</p>
                                <p>
                                    {location.contact_name}
                                    {location.contact_phone && ` · ${location.contact_phone}`}
                                    {location.contact_email && ` · ${location.contact_email}`}
                                </p>
                            </>
                        )}
                        {location.notes && (
                            <>
                                <p className="mt-2 text-sm font-medium text-muted-foreground">Notes</p>
                                <p className="whitespace-pre-wrap">{location.notes}</p>
                            </>
                        )}
                        <p className="mt-2">
                            <span
                                className={
                                    location.is_active
                                        ? 'rounded bg-green-100 px-1.5 py-0.5 text-xs text-green-800'
                                        : 'rounded bg-muted px-1.5 py-0.5 text-xs'
                                }
                            >
                                {location.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </p>
                    </div>
                </div>
                <Button variant="outline" asChild>
                    <Link href="/fleet/locations">Back to locations</Link>
                </Button>
            </div>
        </AppLayout>
    );
}
