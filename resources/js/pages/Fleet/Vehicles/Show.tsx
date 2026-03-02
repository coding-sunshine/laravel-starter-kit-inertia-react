import AppLayout from '@/layouts/app-layout';
import { FleetMap, FleetMapMarker } from '@/components/fleet/FleetMap';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Bot, MapPin } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';

interface DriverOption {
    id: number;
    first_name: string;
    last_name: string;
}

interface AssignmentTypeOption {
    name: string;
    value: string;
}

interface AssignmentRecord {
    id: number;
    assignment_type: string;
    assigned_date: string;
    unassigned_date: string | null;
    is_current: boolean;
    driver?: { id: number; first_name: string; last_name: string };
    vehicle?: { id: number; registration: string };
}

interface VehicleData {
    id: number;
    registration: string;
    make: string;
    model: string;
    status: string;
    fuel_type: string;
    current_driver_id: number | null;
    current_driver?: { id: number; first_name: string; last_name: string } | null;
    driver_assignments?: AssignmentRecord[];
    current_lat?: number | null;
    current_lng?: number | null;
    home_location?: { id: number; name?: string; lat: number; lng: number } | null;
}

interface Props {
    vehicle: VehicleData;
    drivers: DriverOption[];
    assignmentTypes: AssignmentTypeOption[];
}

export default function FleetVehiclesShow({ vehicle, drivers, assignmentTypes }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/vehicles' },
        { title: 'Vehicles', href: '/fleet/vehicles' },
        { title: vehicle.registration, href: `/fleet/vehicles/${vehicle.id}` },
    ];

    const assignForm = useForm({
        driver_id: '',
        assignment_type: 'primary',
        assigned_date: new Date().toISOString().slice(0, 10),
        notes: '',
    });

    const assignAction = `/fleet/vehicles/${vehicle.id}/assign-driver`;
    const unassignAction = `/fleet/vehicles/${vehicle.id}/unassign-driver`;
    const assignments = vehicle.driver_assignments ?? [];

    const vehicleMapPosition = useMemo(() => {
        const hasCurrent = vehicle.current_lat != null && vehicle.current_lng != null && !Number.isNaN(Number(vehicle.current_lat)) && !Number.isNaN(Number(vehicle.current_lng));
        if (hasCurrent) return { lat: Number(vehicle.current_lat), lng: Number(vehicle.current_lng), source: 'current' as const };
        const home = vehicle.home_location;
        if (home?.lat != null && home?.lng != null && !Number.isNaN(Number(home.lat)) && !Number.isNaN(Number(home.lng))) {
            return { lat: Number(home.lat), lng: Number(home.lng), source: 'home' as const };
        }
        return null;
    }, [vehicle.current_lat, vehicle.current_lng, vehicle.home_location]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${vehicle.registration}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">{vehicle.registration}</h1>
                        <p className="text-muted-foreground">
                            {vehicle.make} {vehicle.model} · {vehicle.fuel_type} · {vehicle.status}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/fleet/assistant?prompt=${encodeURIComponent(`Tell me about vehicle ${vehicle.registration} (ID ${vehicle.id}). When is the next service?`)}`}>
                                <Bot className="mr-2 size-4" />
                                Ask assistant
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/fleet/vehicles/${vehicle.id}/edit`}>Edit</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/vehicles">Back to vehicles</Link>
                        </Button>
                    </div>
                </div>

                {/* Current driver */}
                {vehicle.current_driver && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Current driver</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-wrap items-center justify-between gap-4">
                            <p>
                                <Link
                                    href={`/fleet/drivers/${vehicle.current_driver.id}`}
                                    className="font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    {vehicle.current_driver.first_name} {vehicle.current_driver.last_name}
                                </Link>
                            </p>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => router.post(unassignAction)}
                            >
                                Unassign driver
                            </Button>
                        </CardContent>
                    </Card>
                )}

                {/* Location map — current position or home location */}
                {vehicleMapPosition && (
                    <Card className="overflow-hidden border border-border">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-base font-semibold text-foreground">
                                <MapPin className="size-4 text-primary" />
                                Location
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                {vehicleMapPosition.source === 'current' ? 'Last known position' : 'Home location'}
                            </p>
                        </CardHeader>
                        <CardContent className="p-0">
                            <FleetMap
                                center={vehicleMapPosition}
                                zoom={12}
                                mapContainerStyle={{ width: '100%', height: '280px' }}
                                className="rounded-b-lg"
                            >
                                <FleetMapMarker
                                    position={vehicleMapPosition}
                                    title={vehicle.registration}
                                    label={vehicle.registration.slice(0, 2).toUpperCase()}
                                />
                            </FleetMap>
                        </CardContent>
                    </Card>
                )}

                {/* Assign driver form */}
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Assign driver</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                assignForm.post(assignAction);
                            }}
                            className="flex flex-wrap items-end gap-4"
                        >
                            <div className="space-y-2">
                                <Label htmlFor="driver_id">Driver</Label>
                                <select
                                    id="driver_id"
                                    className="border-input h-9 w-full min-w-[12rem] rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    value={assignForm.data.driver_id}
                                    onChange={(e) => assignForm.setData('driver_id', e.target.value)}
                                    required
                                >
                                    <option value="">Select driver</option>
                                    {drivers.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.first_name} {d.last_name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="assignment_type">Type</Label>
                                <select
                                    id="assignment_type"
                                    className="border-input h-9 w-full min-w-[10rem] rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    value={assignForm.data.assignment_type}
                                    onChange={(e) => assignForm.setData('assignment_type', e.target.value)}
                                >
                                    {assignmentTypes.map((t) => (
                                        <option key={t.value} value={t.value}>
                                            {t.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="assigned_date">Assigned date</Label>
                                <Input
                                    id="assigned_date"
                                    type="date"
                                    value={assignForm.data.assigned_date}
                                    onChange={(e) => assignForm.setData('assigned_date', e.target.value)}
                                    className="h-9 w-full min-w-[10rem]"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="notes">Notes (optional)</Label>
                                <Input
                                    id="notes"
                                    type="text"
                                    placeholder="Notes"
                                    value={assignForm.data.notes}
                                    onChange={(e) => assignForm.setData('notes', e.target.value)}
                                    className="h-9 w-full min-w-[12rem]"
                                />
                            </div>
                            <Button type="submit" disabled={assignForm.processing}>
                                {assignForm.processing ? 'Assigning…' : 'Assign'}
                            </Button>
                        </form>
                        {assignForm.errors.driver_id && (
                            <p className="mt-2 text-sm text-destructive">{assignForm.errors.driver_id}</p>
                        )}
                    </CardContent>
                </Card>

                {/* Assignment history */}
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Assignment history</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {assignments.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No assignment history.</p>
                        ) : (
                            <div className="rounded-md border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50">
                                            <th className="p-3 text-left font-medium">Driver</th>
                                            <th className="p-3 text-left font-medium">Type</th>
                                            <th className="p-3 text-left font-medium">Assigned</th>
                                            <th className="p-3 text-left font-medium">Unassigned</th>
                                            <th className="p-3 text-left font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {assignments.map((a) => (
                                            <tr key={a.id} className="border-b last:border-0">
                                                <td className="p-3">
                                                    {a.driver ? (
                                                        <Link
                                                            href={`/fleet/drivers/${a.driver.id}`}
                                                            className="text-primary underline-offset-4 hover:underline"
                                                        >
                                                            {a.driver.first_name} {a.driver.last_name}
                                                        </Link>
                                                    ) : (
                                                        '—'
                                                    )}
                                                </td>
                                                <td className="p-3 capitalize">{a.assignment_type}</td>
                                                <td className="p-3">{a.assigned_date}</td>
                                                <td className="p-3">{a.unassigned_date ?? '—'}</td>
                                                <td className="p-3">
                                                    {a.is_current ? (
                                                        <Badge variant="default">Current</Badge>
                                                    ) : (
                                                        <span className="text-muted-foreground">Past</span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
