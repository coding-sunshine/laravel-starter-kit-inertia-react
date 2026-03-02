import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Link2Off, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';

interface AssignmentRecord {
    id: number;
    assignment_type: string;
    assigned_date: string;
    unassigned_date: string | null;
    is_current: boolean;
    driver?: { id: number; first_name: string; last_name: string };
    vehicle?: { id: number; registration: string };
}

interface Props {
    assignments: {
        data: AssignmentRecord[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { is_current?: string };
}

export default function FleetDriverVehicleAssignmentsIndex({ assignments, filters }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Driver–vehicle assignments', href: '/fleet/driver-vehicle-assignments' },
    ];

    const currentOnly = filters.is_current === '1' || filters.is_current === 'true';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Driver–vehicle assignments" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">Driver–vehicle assignments</h1>
                    <form method="get" action="/fleet/driver-vehicle-assignments" className="flex items-center gap-2">
                        <Label htmlFor="current_only" className="flex cursor-pointer items-center gap-2 text-sm">
                            <input
                                id="current_only"
                                type="checkbox"
                                name="is_current"
                                value="1"
                                defaultChecked={currentOnly}
                                onChange={(e) => e.currentTarget.form?.submit()}
                                className="h-4 w-4 rounded border-input"
                            />
                            Current only
                        </Label>
                    </form>
                </div>

                {assignments.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Users className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            {currentOnly ? 'No current assignments.' : 'No assignments yet.'}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Assign drivers from a{' '}
                            <Link href="/fleet/vehicles" className="text-primary underline">
                                vehicle’s page
                            </Link>
                            .
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Driver</th>
                                        <th className="p-3 text-left font-medium">Vehicle</th>
                                        <th className="p-3 text-left font-medium">Type</th>
                                        <th className="p-3 text-left font-medium">Assigned</th>
                                        <th className="p-3 text-left font-medium">Unassigned</th>
                                        <th className="p-3 text-left font-medium">Status</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {assignments.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">
                                                {row.driver ? (
                                                    <Link
                                                        href={`/fleet/drivers/${row.driver.id}`}
                                                        className="font-medium text-primary underline-offset-4 hover:underline"
                                                    >
                                                        {row.driver.first_name} {row.driver.last_name}
                                                    </Link>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className="p-3">
                                                {row.vehicle ? (
                                                    <Link
                                                        href={`/fleet/vehicles/${row.vehicle.id}`}
                                                        className="font-medium text-primary underline-offset-4 hover:underline"
                                                    >
                                                        {row.vehicle.registration}
                                                    </Link>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className="p-3 capitalize">{row.assignment_type}</td>
                                            <td className="p-3">{row.assigned_date}</td>
                                            <td className="p-3">{row.unassigned_date ?? '—'}</td>
                                            <td className="p-3">
                                                {row.is_current ? (
                                                    <Badge variant="default">Current</Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">Past</span>
                                                )}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    title="Remove assignment"
                                                    onClick={() => {
                                                        if (confirm('Remove this assignment?')) {
                                                            router.delete(`/fleet/driver-vehicle-assignments/${row.id}`);
                                                        }
                                                    }}
                                                >
                                                    <Link2Off className="size-3.5 text-destructive" />
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {assignments.links && assignments.links.length > 1 && (
                            <nav className="flex flex-wrap items-center justify-center gap-2">
                                {assignments.links.map((link, i) => (
                                    <span key={i}>
                                        {link.url ? (
                                            <Link
                                                href={link.url}
                                                className={`inline-flex h-9 min-w-9 items-center justify-center rounded-md px-2 text-sm ${
                                                    link.active
                                                        ? 'bg-primary text-primary-foreground'
                                                        : 'border border-input bg-background hover:bg-muted'
                                                }`}
                                            >
                                                {link.label.replace('&laquo;', '«').replace('&raquo;', '»')}
                                            </Link>
                                        ) : (
                                            <span className="inline-flex h-9 min-w-9 cursor-default items-center justify-center rounded-md border border-input bg-muted/50 px-2 text-sm text-muted-foreground">
                                                {link.label.replace('&laquo;', '«').replace('&raquo;', '»')}
                                            </span>
                                        )}
                                    </span>
                                ))}
                            </nav>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
