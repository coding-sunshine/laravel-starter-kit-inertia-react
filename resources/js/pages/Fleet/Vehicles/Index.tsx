import AppLayout from '@/layouts/app-layout';
import { FleetEmptyState, FleetPageHeader } from '@/components/fleet';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Car, Eye, MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

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

                <Card className="border border-border shadow-sm">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base font-semibold">All vehicles</CardTitle>
                        <CardDescription>
                            {vehicles.data.length === 0
                                ? 'No vehicles in the fleet yet.'
                                : `${vehicles.data.length} vehicle${vehicles.data.length === 1 ? '' : 's'}`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
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
                            <div className="overflow-hidden rounded-b-xl border-t border-border">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="bg-muted/40 hover:bg-muted/40">
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
                                                    <span
                                                        className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                            row.status === 'active'
                                                                ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                                                : 'bg-muted text-muted-foreground'
                                                        }`}
                                                    >
                                                        {row.status}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/fleet/vehicles/${row.id}`}
                                                                title="View details"
                                                            >
                                                                <Eye className="size-4" />
                                                            </Link>
                                                        </Button>
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="size-8"
                                                                    title="More actions"
                                                                >
                                                                    <MoreHorizontal className="size-4" />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="end">
                                                                <DropdownMenuItem asChild>
                                                                    <Link
                                                                        href={`/fleet/vehicles/${row.id}/edit`}
                                                                    >
                                                                        <Pencil className="mr-2 size-4" />
                                                                        Edit
                                                                    </Link>
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem
                                                                    className="text-destructive focus:text-destructive"
                                                                    onClick={() =>
                                                                        setDeleteTarget(row)
                                                                    }
                                                                >
                                                                    <Trash2 className="mr-2 size-4" />
                                                                    Delete
                                                                </DropdownMenuItem>
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>

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
