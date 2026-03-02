import AppLayout from '@/layouts/app-layout';
import { FleetActionIconButton, FleetActionIconLink, FleetEmptyState, FleetGlassCard, FleetGlassPill, FleetPageHeader, FleetPageToolbarRight, FleetPagination } from '@/components/fleet';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { Form, Head, Link } from '@inertiajs/react';
import { Car, Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface VehicleRecord {
    id: number;
    registration: string;
    make: string;
    model: string;
    status: string;
}
interface Props {
    vehicles: {
        data: VehicleRecord[];
        last_page: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
}

export default function FleetVehiclesIndex({ vehicles }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<VehicleRecord | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Vehicles', href: '/fleet/vehicles' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Vehicles" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6">
                <FleetPageHeader
                    title="Vehicles"
                    description="Manage fleet vehicles, registration, and assignment."
                    action={
                        <Button asChild>
                            <Link href="/fleet/vehicles/create">
                                <Plus className="mr-2 size-4" />
                                New vehicle
                            </Link>
                        </Button>
                    }
                />

                <FleetGlassCard className="min-w-0 overflow-hidden" noPadding>
                    <div className="mb-2 flex h-9 items-center justify-between border-b border-white/30 px-4 pt-4 pb-2">
                        <h3 className="text-base font-medium text-[#5b638d]">
                            All vehicles — {vehicles.data.length === 0 ? 'No vehicles yet' : `${vehicles.data.length} vehicle${vehicles.data.length === 1 ? '' : 's'}`}
                        </h3>
                        <FleetPageToolbarRight>
                            <Button asChild size="sm">
                                <Link href="/fleet/vehicles/create">
                                    <Plus className="mr-2 size-4" />
                                    New vehicle
                                </Link>
                            </Button>
                        </FleetPageToolbarRight>
                    </div>
                    {vehicles.data.length === 0 ? (
                        <div className="px-6 pb-8">
                            <FleetEmptyState
                                icon={Car}
                                title="No vehicles yet"
                                description="Add your first vehicle to start managing your fleet."
                                action={
                                    <Button asChild>
                                        <Link href="/fleet/vehicles/create">
                                            <Plus className="mr-2 size-4" />
                                            Add vehicle
                                        </Link>
                                    </Button>
                                }
                            />
                        </div>
                    ) : (
                        <>
                            <div className="fleet-glass-table w-full overflow-x-auto">
                                <Table>
                                <TableHeader>
                                    <TableRow className="border-0 bg-transparent hover:bg-transparent">
                                            <TableHead className="h-11 px-4 font-semibold">
                                                Registration
                                            </TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">
                                                Make / Model
                                            </TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">
                                                Status
                                            </TableHead>
                                            <TableHead className="h-11 w-[80px] px-4 text-right font-semibold">
                                                Actions
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {vehicles.data.map((row) => (
                                            <TableRow
                                                key={row.id}
                                                className="group transition-colors"
                                            >
                                                <TableCell className="px-4 py-3">
                                                    <Link
                                                        href={`/fleet/vehicles/${row.id}`}
                                                        className="font-medium text-foreground hover:underline"
                                                    >
                                                        {row.registration}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">
                                                    {row.make} {row.model}
                                                </TableCell>
                                                <TableCell className="px-4 py-3">
                                                    <FleetGlassPill variant={row.status === 'active' ? 'success' : 'default'}>
                                                        {row.status}
                                                    </FleetGlassPill>
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <FleetActionIconLink
                                                            href={`/fleet/vehicles/${row.id}`}
                                                            label="View details"
                                                            variant="view"
                                                        >
                                                            <Eye className="size-4" />
                                                        </FleetActionIconLink>
                                                        <FleetActionIconLink
                                                            href={`/fleet/vehicles/${row.id}/edit`}
                                                            label="Edit"
                                                            variant="edit"
                                                        >
                                                            <Pencil className="size-4" />
                                                        </FleetActionIconLink>
                                                        <FleetActionIconButton
                                                            label="Delete"
                                                            variant="delete"
                                                            onClick={() => setDeleteTarget(row)}
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </FleetActionIconButton>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                            <FleetPagination links={vehicles.links ?? []} />
                        </>
                        )}
                </FleetGlassCard>

                <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete vehicle</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to delete{' '}
                                <strong>{deleteTarget?.registration}</strong>? This action cannot be
                                undone.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button variant="outline">Cancel</Button>
                            </DialogClose>
                            {deleteTarget && (
                                <Form
                                    action={`/fleet/vehicles/${deleteTarget.id}`}
                                    method="delete"
                                    onSubmit={() => setDeleteTarget(null)}
                                >
                                    <Button type="submit" variant="destructive">
                                        Delete
                                    </Button>
                                </Form>
                            )}
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
