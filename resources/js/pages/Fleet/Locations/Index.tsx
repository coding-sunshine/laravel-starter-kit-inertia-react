import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { MapPin, Pencil, Plus, Trash2 } from 'lucide-react';

import { Button } from '@/components/ui/button';

interface LocationRecord {
    id: number;
    name: string;
    type: string;
    address: string;
    city: string | null;
    postcode: string | null;
    is_active: boolean;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    locations: {
        data: LocationRecord[];
        current_page: number;
        last_page: number;
        per_page: number;
        links: PaginationLink[];
    };
    filters: { type?: string; is_active?: string };
}

export default function FleetLocationsIndex({ locations, filters }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/locations' },
        { title: 'Locations', href: '/fleet/locations' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Locations" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Locations</h1>
                    <Button asChild>
                        <Link href="/fleet/locations/create">
                            <Plus className="mr-2 size-4" />
                            New location
                        </Link>
                    </Button>
                </div>

                {locations.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center">
                        <MapPin className="size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm font-medium text-muted-foreground">No locations yet</p>
                        <p className="mt-1 text-xs text-muted-foreground">Create a location to get started.</p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/locations/create">
                                <Plus className="mr-2 size-4" />
                                Create location
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Name</th>
                                        <th className="p-3 text-left font-medium">Type</th>
                                        <th className="p-3 text-left font-medium">Address</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {locations.data.map((loc) => (
                                        <tr key={loc.id} className="border-b last:border-0">
                                            <td className="p-3">
                                                <Link
                                                    href={`/fleet/locations/${loc.id}`}
                                                    className="font-medium text-foreground hover:underline"
                                                >
                                                    {loc.name}
                                                </Link>
                                            </td>
                                            <td className="p-3 text-muted-foreground">{loc.type}</td>
                                            <td className="p-3">
                                                {[loc.address, loc.city, loc.postcode].filter(Boolean).join(', ')}
                                            </td>
                                            <td className="p-3">
                                                <span
                                                    className={
                                                        loc.is_active
                                                            ? 'rounded bg-green-100 px-1.5 py-0.5 text-xs text-green-800'
                                                            : 'rounded bg-muted px-1.5 py-0.5 text-xs'
                                                    }
                                                >
                                                    {loc.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={`/fleet/locations/${loc.id}/edit`}>
                                                        <Pencil className="mr-1 size-3.5" />
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/locations/${loc.id}`}
                                                    method="delete"
                                                    className="ml-2 inline"
                                                    onSubmit={(e) => {
                                                        if (!confirm('Delete this location?')) e.preventDefault();
                                                    }}
                                                >
                                                    <Button type="submit" variant="ghost" size="sm">
                                                        <Trash2 className="size-3.5 text-destructive" />
                                                    </Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {locations.last_page > 1 && (
                            <nav className="flex items-center gap-2">
                                {locations.links.map((link) =>
                                    link.url ? (
                                        <Link
                                            key={link.label}
                                            href={link.url}
                                            className={
                                                link.active
                                                    ? 'rounded border bg-muted px-3 py-1 text-sm font-medium'
                                                    : 'rounded border px-3 py-1 text-sm hover:bg-muted/50'
                                            }
                                        >
                                            {link.label}
                                        </Link>
                                    ) : (
                                        <span key={link.label} className="px-3 py-1 text-sm text-muted-foreground">
                                            {link.label}
                                        </span>
                                    )
                                )}
                            </nav>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
