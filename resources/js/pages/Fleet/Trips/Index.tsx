import AppLayout from '@/layouts/app-layout';
import { FleetActionIconLink, FleetEmptyState, FleetGlassCard, FleetGlassPill, FleetPageHeader, FleetPageToolbar, FleetPageToolbarLeft, FleetPagination } from '@/components/fleet';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
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
import { Eye, MapPin } from 'lucide-react';

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

export default function FleetTripsIndex({ trips, filters, vehicles, drivers }: Props) {
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
                    description="View and manage planned and completed trips. Filter by vehicle, driver, or date."
                />

                <FleetGlassCard className="p-3">
                    <form method="get">
                        <FleetPageToolbar>
                            <FleetPageToolbarLeft className="flex flex-wrap items-end gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs">Vehicle</Label>
                                    <select name="vehicle_id" defaultValue={filters.vehicle_id ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                        <option value="">All</option>
                                        {vehicles.map((v) => (
                                            <option key={v.id} value={v.id}>{v.registration}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs">Driver</Label>
                                    <select name="driver_id" defaultValue={filters.driver_id ?? ''} className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                        <option value="">All</option>
                                        {drivers.map((d) => (
                                            <option key={d.id} value={d.id}>{d.first_name} {d.last_name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs">From date</Label>
                                    <input type="date" name="from_date" defaultValue={filters.from_date ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm" />
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs">To date</Label>
                                    <input type="date" name="to_date" defaultValue={filters.to_date ?? ''} className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm" />
                                </div>
                                <Button type="submit" variant="secondary" size="sm">Filter</Button>
                            </FleetPageToolbarLeft>
                        </FleetPageToolbar>
                    </form>
                </FleetGlassCard>

                <FleetGlassCard className="min-w-0 overflow-hidden" noPadding>
                    <div className="mb-2 flex h-9 items-center justify-between border-b border-white/30 px-4 pt-4 pb-2">
                        <h3 className="text-base font-medium text-[#5b638d]">
                            All trips — {trips.data.length === 0 ? 'No trips yet' : `${trips.data.length} trip${trips.data.length === 1 ? '' : 's'}`}
                        </h3>
                    </div>
                    {trips.data.length === 0 ? (
                        <div className="px-6 pb-8">
                            <FleetEmptyState
                                icon={MapPin}
                                title="No trips yet"
                                description="Trips will appear here once planned or completed."
                            />
                        </div>
                    ) : (
                        <div className="fleet-glass-table w-full overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow className="border-0 bg-transparent hover:bg-transparent">
                                        <TableHead className="h-11 px-4 font-semibold">Vehicle</TableHead>
                                        <TableHead className="h-11 px-4 font-semibold">Driver</TableHead>
                                        <TableHead className="h-11 px-4 font-semibold">Route</TableHead>
                                        <TableHead className="h-11 px-4 font-semibold">Planned start</TableHead>
                                        <TableHead className="h-11 px-4 font-semibold">Status</TableHead>
                                        <TableHead className="h-11 w-[80px] px-4 text-right font-semibold">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {trips.data.map((row) => (
                                        <TableRow key={row.id} className="group transition-colors">
                                            <TableCell className="px-4 py-3 text-muted-foreground">{row.vehicle?.registration ?? '—'}</TableCell>
                                            <TableCell className="px-4 py-3 text-muted-foreground">
                                                {row.driver ? `${row.driver.first_name} ${row.driver.last_name}` : '—'}
                                            </TableCell>
                                            <TableCell className="px-4 py-3">{row.route?.name ?? '—'}</TableCell>
                                            <TableCell className="px-4 py-3 text-muted-foreground">
                                                {row.planned_start_time ? new Date(row.planned_start_time).toLocaleString() : '—'}
                                            </TableCell>
                                                <TableCell className="px-4 py-3">
                                                    <FleetGlassPill variant="default">{row.status}</FleetGlassPill>
                                                </TableCell>
                                            <TableCell className="px-4 py-3 text-right">
                                                <FleetActionIconLink href={`/fleet/trips/${row.id}`} label="View" variant="view">
                                                    <Eye className="size-4" />
                                                </FleetActionIconLink>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            <FleetPagination links={trips.links ?? []} />
                        </div>
                    )}
                </FleetGlassCard>
            </div>
        </AppLayout>
    );
}
