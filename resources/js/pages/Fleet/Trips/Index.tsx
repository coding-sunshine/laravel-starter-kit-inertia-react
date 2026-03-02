import AppLayout from '@/layouts/app-layout';
import { FleetEmptyState, FleetPageHeader } from '@/components/fleet';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { MapPin } from 'lucide-react';

interface TripRecord {
    id: number;
    status: string;
    planned_start_time: string | null;
    vehicle?: { id: number; registration: string };
    driver?: { id: number; first_name: string; last_name: string };
    route?: { id: number; name: string };
}
interface Props {
    trips: { data: TripRecord[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; first_name: string; last_name: string }[];
}

export default function FleetTripsIndex({ trips }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Trips', href: '/fleet/trips' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Trips" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6">
                <FleetPageHeader
                    title="Trips"
                    description="View and manage planned and completed trips."
                />

                <Card className="border border-border shadow-sm">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base font-semibold">All trips</CardTitle>
                        <CardDescription>
                            {trips.data.length === 0
                                ? 'No trips recorded yet.'
                                : `${trips.data.length} trip${trips.data.length === 1 ? '' : 's'}`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        {trips.data.length === 0 ? (
                            <div className="px-6 pb-8">
                                <FleetEmptyState
                                    icon={MapPin}
                                    title="No trips yet"
                                    description="Trips will appear here once planned or completed."
                                />
                            </div>
                        ) : (
                            <div className="overflow-hidden rounded-b-xl border-t border-border">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="bg-muted/40 hover:bg-muted/40">
                                            <TableHead className="h-11 px-4 font-semibold">Vehicle</TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">Driver</TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">Route</TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">Planned start</TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">Status</TableHead>
                                            <TableHead className="h-11 w-[100px] px-4 text-right font-semibold">
                                                Actions
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {trips.data.map((row) => (
                                            <TableRow key={row.id} className="group transition-colors">
                                                <TableCell className="px-4 py-3 text-muted-foreground">
                                                    {row.vehicle?.registration ?? '—'}
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">
                                                    {row.driver
                                                        ? `${row.driver.first_name} ${row.driver.last_name}`
                                                        : '—'}
                                                </TableCell>
                                                <TableCell className="px-4 py-3">
                                                    {row.route?.name ?? '—'}
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">
                                                    {row.planned_start_time
                                                        ? new Date(row.planned_start_time).toLocaleString()
                                                        : '—'}
                                                </TableCell>
                                                <TableCell className="px-4 py-3">
                                                    <span className="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                                                        {row.status}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-right">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/fleet/trips/${row.id}`}>View</Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
