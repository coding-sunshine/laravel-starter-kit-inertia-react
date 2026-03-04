import { AiInsightBanner } from '@/components/fleet';
import { FleetMap, FleetMapMarker } from '@/components/fleet/FleetMap';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Bot, ClipboardList, MapPin, Plus } from 'lucide-react';
import { useMemo } from 'react';

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
    current_driver?: {
        id: number;
        first_name: string;
        last_name: string;
    } | null;
    driver_assignments?: AssignmentRecord[];
    current_lat?: number | null;
    current_lng?: number | null;
    home_location?: {
        id: number;
        name?: string;
        lat: number;
        lng: number;
    } | null;
}

interface RecentWorkOrder {
    id: number;
    work_order_number: string;
    title: string;
    status: string;
    created_at: string;
}
interface RecentDefect {
    id: number;
    defect_number: string;
    title: string;
    severity: string;
    reported_at: string;
}
interface RecentTrip {
    id: number;
    started_at: string;
    ended_at: string | null;
}

interface AiInsightData {
    id: number;
    primary_finding: string;
    priority: 'high' | 'medium' | 'low' | 'critical';
    analysis_type: string;
    recommendations?: string[] | null;
}

interface Props {
    vehicle: VehicleData;
    drivers: DriverOption[];
    assignmentTypes: AssignmentTypeOption[];
    recentWorkOrders?: RecentWorkOrder[];
    recentDefects?: RecentDefect[];
    recentTrips?: RecentTrip[];
    aiInsight?: AiInsightData | null;
}

export default function FleetVehiclesShow({
    vehicle,
    drivers,
    assignmentTypes,
    recentWorkOrders = [],
    recentDefects = [],
    recentTrips = [],
    aiInsight,
}: Props) {
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
        const hasCurrent =
            vehicle.current_lat != null &&
            vehicle.current_lng != null &&
            !Number.isNaN(Number(vehicle.current_lat)) &&
            !Number.isNaN(Number(vehicle.current_lng));
        if (hasCurrent)
            return {
                lat: Number(vehicle.current_lat),
                lng: Number(vehicle.current_lng),
                source: 'current' as const,
            };
        const home = vehicle.home_location;
        if (
            home?.lat != null &&
            home?.lng != null &&
            !Number.isNaN(Number(home.lat)) &&
            !Number.isNaN(Number(home.lng))
        ) {
            return {
                lat: Number(home.lat),
                lng: Number(home.lng),
                source: 'home' as const,
            };
        }
        return null;
    }, [vehicle.current_lat, vehicle.current_lng, vehicle.home_location]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${vehicle.registration}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {vehicle.registration}
                        </h1>
                        <p className="text-muted-foreground">
                            {vehicle.make} {vehicle.model} · {vehicle.fuel_type}{' '}
                            · {vehicle.status}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button size="sm" asChild>
                            <Link
                                href={`/fleet/work-orders/create?vehicle_id=${vehicle.id}`}
                            >
                                <Plus className="mr-2 size-4" />
                                Create work order
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/work-orders/create?vehicle_id=${vehicle.id}&type=inspection`}
                            >
                                <ClipboardList className="mr-2 size-4" />
                                Schedule inspection
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/assistant?prompt=${encodeURIComponent(`Tell me about vehicle ${vehicle.registration} (ID ${vehicle.id}). When is the next service?`)}`}
                            >
                                <Bot className="mr-2 size-4" />
                                Ask assistant
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/fleet/vehicles/${vehicle.id}/edit`}>
                                Edit
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/vehicles">Back to vehicles</Link>
                        </Button>
                    </div>
                </div>

                {aiInsight && (
                    <AiInsightBanner
                        id={aiInsight.id}
                        primaryFinding={aiInsight.primary_finding}
                        priority={aiInsight.priority}
                        analysisType={aiInsight.analysis_type}
                        recommendations={aiInsight.recommendations}
                    />
                )}

                <Tabs defaultValue="overview" className="w-full">
                    <TabsList className="mb-4">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        {vehicleMapPosition && (
                            <TabsTrigger value="location">Location</TabsTrigger>
                        )}
                        <TabsTrigger value="history">
                            Assignment history
                        </TabsTrigger>
                        <TabsTrigger value="related">Related</TabsTrigger>
                    </TabsList>
                    <TabsContent value="overview" className="space-y-6">
                        {/* Current driver */}
                        {vehicle.current_driver && (
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-base">
                                        Current driver
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-wrap items-center justify-between gap-4">
                                    <p>
                                        <Link
                                            href={`/fleet/drivers/${vehicle.current_driver.id}`}
                                            className="font-medium text-primary underline-offset-4 hover:underline"
                                        >
                                            {vehicle.current_driver.first_name}{' '}
                                            {vehicle.current_driver.last_name}
                                        </Link>
                                    </p>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.post(unassignAction)
                                        }
                                    >
                                        Unassign driver
                                    </Button>
                                </CardContent>
                            </Card>
                        )}

                        {/* Assign driver form */}
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    Assign driver
                                </CardTitle>
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
                                        <Label htmlFor="driver_id">
                                            Driver
                                        </Label>
                                        <select
                                            id="driver_id"
                                            className="h-9 w-full min-w-[12rem] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                            value={assignForm.data.driver_id}
                                            onChange={(e) =>
                                                assignForm.setData(
                                                    'driver_id',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        >
                                            <option value="">
                                                Select driver
                                            </option>
                                            {drivers.map((d) => (
                                                <option key={d.id} value={d.id}>
                                                    {d.first_name} {d.last_name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="assignment_type">
                                            Type
                                        </Label>
                                        <select
                                            id="assignment_type"
                                            className="h-9 w-full min-w-[10rem] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                            value={
                                                assignForm.data.assignment_type
                                            }
                                            onChange={(e) =>
                                                assignForm.setData(
                                                    'assignment_type',
                                                    e.target.value,
                                                )
                                            }
                                        >
                                            {assignmentTypes.map((t) => (
                                                <option
                                                    key={t.value}
                                                    value={t.value}
                                                >
                                                    {t.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="assigned_date">
                                            Assigned date
                                        </Label>
                                        <Input
                                            id="assigned_date"
                                            type="date"
                                            value={
                                                assignForm.data.assigned_date
                                            }
                                            onChange={(e) =>
                                                assignForm.setData(
                                                    'assigned_date',
                                                    e.target.value,
                                                )
                                            }
                                            className="h-9 w-full min-w-[10rem]"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="notes">
                                            Notes (optional)
                                        </Label>
                                        <Input
                                            id="notes"
                                            type="text"
                                            placeholder="Notes"
                                            value={assignForm.data.notes}
                                            onChange={(e) =>
                                                assignForm.setData(
                                                    'notes',
                                                    e.target.value,
                                                )
                                            }
                                            className="h-9 w-full min-w-[12rem]"
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        disabled={assignForm.processing}
                                    >
                                        {assignForm.processing
                                            ? 'Assigning…'
                                            : 'Assign'}
                                    </Button>
                                </form>
                                {assignForm.errors.driver_id && (
                                    <p className="mt-2 text-sm text-destructive">
                                        {assignForm.errors.driver_id}
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                    {vehicleMapPosition && (
                        <TabsContent value="location" className="mt-0">
                            <Card className="overflow-hidden border border-border">
                                <CardHeader className="pb-2">
                                    <CardTitle className="flex items-center gap-2 text-base font-semibold text-foreground">
                                        <MapPin className="size-4 text-primary" />
                                        Location
                                    </CardTitle>
                                    <p className="text-xs text-muted-foreground">
                                        {vehicleMapPosition.source === 'current'
                                            ? 'Last known position'
                                            : 'Home location'}
                                    </p>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <FleetMap
                                        center={vehicleMapPosition}
                                        zoom={12}
                                        mapContainerStyle={{
                                            width: '100%',
                                            height: '280px',
                                        }}
                                        className="rounded-b-lg"
                                    >
                                        <FleetMapMarker
                                            position={vehicleMapPosition}
                                            title={vehicle.registration}
                                            label={vehicle.registration
                                                .slice(0, 2)
                                                .toUpperCase()}
                                        />
                                    </FleetMap>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    )}
                    <TabsContent value="history" className="mt-0">
                        {/* Assignment history */}
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    Assignment history
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {assignments.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No assignment history.
                                    </p>
                                ) : (
                                    <div className="rounded-md border">
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="border-b bg-muted/50">
                                                    <th className="p-3 text-left font-medium">
                                                        Driver
                                                    </th>
                                                    <th className="p-3 text-left font-medium">
                                                        Type
                                                    </th>
                                                    <th className="p-3 text-left font-medium">
                                                        Assigned
                                                    </th>
                                                    <th className="p-3 text-left font-medium">
                                                        Unassigned
                                                    </th>
                                                    <th className="p-3 text-left font-medium">
                                                        Status
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {assignments.map((a) => (
                                                    <tr
                                                        key={a.id}
                                                        className="border-b last:border-0"
                                                    >
                                                        <td className="p-3">
                                                            {a.driver ? (
                                                                <Link
                                                                    href={`/fleet/drivers/${a.driver.id}`}
                                                                    className="text-primary underline-offset-4 hover:underline"
                                                                >
                                                                    {
                                                                        a.driver
                                                                            .first_name
                                                                    }{' '}
                                                                    {
                                                                        a.driver
                                                                            .last_name
                                                                    }
                                                                </Link>
                                                            ) : (
                                                                '—'
                                                            )}
                                                        </td>
                                                        <td className="p-3 capitalize">
                                                            {a.assignment_type}
                                                        </td>
                                                        <td className="p-3">
                                                            {a.assigned_date}
                                                        </td>
                                                        <td className="p-3">
                                                            {a.unassigned_date ??
                                                                '—'}
                                                        </td>
                                                        <td className="p-3">
                                                            {a.is_current ? (
                                                                <Badge variant="default">
                                                                    Current
                                                                </Badge>
                                                            ) : (
                                                                <span className="text-muted-foreground">
                                                                    Past
                                                                </span>
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
                    </TabsContent>
                    <TabsContent value="related" className="mt-0 space-y-6">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-base">
                                    Recent work orders
                                </CardTitle>
                                <Button size="sm" variant="outline" asChild>
                                    <Link
                                        href={`/fleet/work-orders?vehicle_id=${vehicle.id}`}
                                    >
                                        View all
                                    </Link>
                                </Button>
                            </CardHeader>
                            <CardContent>
                                {recentWorkOrders.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No work orders for this vehicle.
                                    </p>
                                ) : (
                                    <ul className="space-y-2 text-sm">
                                        {recentWorkOrders.map((wo) => (
                                            <li
                                                key={wo.id}
                                                className="flex items-center justify-between"
                                            >
                                                <Link
                                                    href={`/fleet/work-orders/${wo.id}`}
                                                    className="font-medium text-primary hover:underline"
                                                >
                                                    {wo.work_order_number}
                                                </Link>
                                                <span className="text-muted-foreground">
                                                    {wo.status}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-base">
                                    Recent defects
                                </CardTitle>
                                <Button size="sm" variant="outline" asChild>
                                    <Link
                                        href={`/fleet/defects?vehicle_id=${vehicle.id}`}
                                    >
                                        View all
                                    </Link>
                                </Button>
                            </CardHeader>
                            <CardContent>
                                {recentDefects.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No defects reported for this vehicle.
                                    </p>
                                ) : (
                                    <ul className="space-y-2 text-sm">
                                        {recentDefects.map((d) => (
                                            <li
                                                key={d.id}
                                                className="flex items-center justify-between"
                                            >
                                                <Link
                                                    href={`/fleet/defects/${d.id}`}
                                                    className="font-medium text-primary hover:underline"
                                                >
                                                    {d.defect_number}
                                                </Link>
                                                <span className="text-muted-foreground">
                                                    {d.severity}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-base">
                                    Recent trips
                                </CardTitle>
                                <Button size="sm" variant="outline" asChild>
                                    <Link
                                        href={`/fleet/trips?vehicle_id=${vehicle.id}`}
                                    >
                                        View all
                                    </Link>
                                </Button>
                            </CardHeader>
                            <CardContent>
                                {recentTrips.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No trips for this vehicle.
                                    </p>
                                ) : (
                                    <ul className="space-y-2 text-sm">
                                        {recentTrips.map((t) => (
                                            <li
                                                key={t.id}
                                                className="flex items-center justify-between"
                                            >
                                                <Link
                                                    href={`/fleet/trips/${t.id}`}
                                                    className="font-medium text-primary hover:underline"
                                                >
                                                    {new Date(
                                                        t.started_at,
                                                    ).toLocaleString()}
                                                </Link>
                                                {t.ended_at && (
                                                    <span className="text-muted-foreground">
                                                        –{' '}
                                                        {new Date(
                                                            t.ended_at,
                                                        ).toLocaleString()}
                                                    </span>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
