import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
                    <Button variant="outline" asChild>
                        <Link href="/fleet/vehicles">Back to vehicles</Link>
                    </Button>
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
