import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

interface AssignmentRecord {
    id: number;
    assignment_type: string;
    assigned_date: string;
    unassigned_date: string | null;
    is_current: boolean;
    vehicle?: { id: number; registration: string };
}

interface DriverData {
    id: number;
    first_name: string;
    last_name: string;
    status: string;
    license_number: string;
    vehicle_assignments?: AssignmentRecord[];
    current_assignment?: AssignmentRecord | null;
}

interface Props {
    driver: DriverData;
}

export default function FleetDriversShow({ driver }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/drivers' },
        { title: 'Drivers', href: '/fleet/drivers' },
        { title: `${driver.first_name} ${driver.last_name}`, href: `/fleet/drivers/${driver.id}` },
    ];

    const assignments = driver.vehicle_assignments ?? [];
    const current = driver.current_assignment;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${driver.first_name} ${driver.last_name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {driver.first_name} {driver.last_name}
                        </h1>
                        <p className="text-muted-foreground">
                            License: {driver.license_number} · Status: {driver.status}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/fleet/drivers">Back to drivers</Link>
                    </Button>
                </div>

                {/* Current vehicle assignment */}
                {current?.vehicle && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Current vehicle</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p>
                                <Link
                                    href={`/fleet/vehicles/${current.vehicle.id}`}
                                    className="font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    {current.vehicle.registration}
                                </Link>
                                <span className="ml-2 text-muted-foreground">
                                    ({current.assignment_type})
                                </span>
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Assigned from {current.assigned_date}
                            </p>
                        </CardContent>
                    </Card>
                )}
                {!current && (
                    <Card>
                        <CardContent className="py-4">
                            <p className="text-sm text-muted-foreground">No vehicle currently assigned.</p>
                        </CardContent>
                    </Card>
                )}

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
                                            <th className="p-3 text-left font-medium">Vehicle</th>
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
                                                    {a.vehicle ? (
                                                        <Link
                                                            href={`/fleet/vehicles/${a.vehicle.id}`}
                                                            className="text-primary underline-offset-4 hover:underline"
                                                        >
                                                            {a.vehicle.registration}
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
