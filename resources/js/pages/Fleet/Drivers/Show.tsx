import { AiInsightBanner } from '@/components/fleet';
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
import { Bot, Plus } from 'lucide-react';

interface AssignmentRecord {
    id: number;
    assignment_type: string;
    assigned_date: string;
    unassigned_date: string | null;
    is_current: boolean;
    vehicle?: { id: number; registration: string };
}

interface VehicleOption {
    id: number;
    registration: string;
}

interface AssignmentTypeOption {
    name: string;
    value: string;
}

interface DriverData {
    id: number;
    first_name: string;
    last_name: string;
    status: string;
    license_number: string;
    safety_score?: number | null;
    risk_category?: string | null;
    vehicle_assignments?: AssignmentRecord[];
    current_assignment?: AssignmentRecord | null;
}

interface AiInsightData {
    id: number;
    primary_finding: string;
    priority: 'high' | 'medium' | 'low' | 'critical';
    analysis_type: string;
    recommendations?: string[] | null;
}

interface Props {
    driver: DriverData;
    vehicles: VehicleOption[];
    assignmentTypes: AssignmentTypeOption[];
    aiInsight?: AiInsightData | null;
}

export default function FleetDriversShow({
    driver,
    vehicles,
    assignmentTypes,
    aiInsight,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/drivers' },
        { title: 'Drivers', href: '/fleet/drivers' },
        {
            title: `${driver.first_name} ${driver.last_name}`,
            href: `/fleet/drivers/${driver.id}`,
        },
    ];

    const assignForm = useForm({
        vehicle_id: '',
        assignment_type: 'primary',
        assigned_date: new Date().toISOString().slice(0, 10),
        notes: '',
    });

    const assignAction = `/fleet/drivers/${driver.id}/assign-vehicle`;
    const unassignAction = `/fleet/drivers/${driver.id}/unassign-vehicle`;
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
                            License: {driver.license_number} · Status:{' '}
                            {driver.status}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {current?.vehicle && (
                            <Button size="sm" asChild>
                                <Link
                                    href={`/fleet/work-orders/create?vehicle_id=${current.vehicle.id}`}
                                >
                                    <Plus className="mr-2 size-4" />
                                    Create work order
                                </Link>
                            </Button>
                        )}
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/assistant?prompt=${encodeURIComponent(`Tell me about driver ${driver.first_name} ${driver.last_name} (ID ${driver.id}). Assignments and compliance?`)}`}
                            >
                                <Bot className="mr-2 size-4" />
                                Ask assistant
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/fleet/drivers/${driver.id}/edit`}>
                                Edit
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/drivers">Back to drivers</Link>
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
                        <TabsTrigger value="history">
                            Assignment history
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent value="overview" className="mt-0 space-y-6">
                        {/* Safety score */}
                        {(driver.safety_score != null ||
                            driver.risk_category) && (
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-base">
                                        Safety score
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="text-sm">
                                    <div className="flex items-center gap-3">
                                        {driver.safety_score != null && (
                                            <span className="text-2xl font-semibold tabular-nums">
                                                {driver.safety_score}/100
                                            </span>
                                        )}
                                        {driver.risk_category && (
                                            <Badge
                                                variant={
                                                    driver.risk_category ===
                                                    'low'
                                                        ? 'default'
                                                        : driver.risk_category ===
                                                            'medium'
                                                          ? 'secondary'
                                                          : 'destructive'
                                                }
                                            >
                                                {driver.risk_category}
                                            </Badge>
                                        )}
                                    </div>
                                    <p className="mt-1 text-muted-foreground">
                                        Based on behavior events, incidents, and
                                        compliance. Updated daily.
                                    </p>
                                </CardContent>
                            </Card>
                        )}

                        {/* Current vehicle assignment */}
                        {current?.vehicle && (
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-base">
                                        Current vehicle
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-wrap items-center justify-between gap-4">
                                    <div>
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
                                            Assigned from{' '}
                                            {current.assigned_date}
                                        </p>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.post(unassignAction)
                                        }
                                    >
                                        Unassign vehicle
                                    </Button>
                                </CardContent>
                            </Card>
                        )}
                        {!current && (
                            <Card>
                                <CardContent className="py-4">
                                    <p className="text-sm text-muted-foreground">
                                        No vehicle currently assigned.
                                    </p>
                                </CardContent>
                            </Card>
                        )}

                        {/* Assign vehicle form */}
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    Assign vehicle
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
                                        <Label htmlFor="vehicle_id">
                                            Vehicle
                                        </Label>
                                        <select
                                            id="vehicle_id"
                                            className="h-9 w-full min-w-[12rem] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                            value={assignForm.data.vehicle_id}
                                            onChange={(e) =>
                                                assignForm.setData(
                                                    'vehicle_id',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        >
                                            <option value="">
                                                Select vehicle
                                            </option>
                                            {vehicles.map((v) => (
                                                <option key={v.id} value={v.id}>
                                                    {v.registration}
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
                                {assignForm.errors.vehicle_id && (
                                    <p className="mt-2 text-sm text-destructive">
                                        {assignForm.errors.vehicle_id}
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
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
                                                        Vehicle
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
                                                            {a.vehicle ? (
                                                                <Link
                                                                    href={`/fleet/vehicles/${a.vehicle.id}`}
                                                                    className="text-primary underline-offset-4 hover:underline"
                                                                >
                                                                    {
                                                                        a
                                                                            .vehicle
                                                                            .registration
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
                </Tabs>
            </div>
        </AppLayout>
    );
}
