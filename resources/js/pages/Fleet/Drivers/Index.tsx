import AppLayout from '@/layouts/app-layout';
import {
    FleetActionIconButton,
    FleetActionIconLink,
    FleetEmptyState,
    FleetGlassCard,
    FleetGlassPill,
    FleetPageHeader,
    FleetPageToolbarRight,
    FleetPagination,
} from '@/components/fleet';
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
import { Eye, Pencil, Plus, Trash2, Users } from 'lucide-react';
import { useState } from 'react';

interface DriverRecord {
    id: number;
    first_name: string;
    last_name: string;
    status: string;
    license_number: string;
    license_expiry_date: string;
}
interface Props {
    drivers: {
        data: DriverRecord[];
        last_page: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
}

export default function FleetDriversIndex({ drivers }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<DriverRecord | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Drivers', href: '/fleet/drivers' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Drivers" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6">
                <FleetPageHeader
                    title="Drivers"
                    description="Manage drivers, licenses, and assignments."
                    action={
                        <Button asChild>
                            <Link href="/fleet/drivers/create">
                                <Plus className="mr-2 size-4" />
                                New driver
                            </Link>
                        </Button>
                    }
                />

                <FleetGlassCard className="min-w-0 overflow-hidden" noPadding>
                    <div className="mb-2 flex h-9 items-center justify-between border-b border-white/30 px-4 pt-4 pb-2">
                        <h3 className="text-base font-medium text-[#5b638d]">
                            All drivers — {drivers.data.length === 0 ? 'No drivers yet' : `${drivers.data.length} driver${drivers.data.length === 1 ? '' : 's'}`}
                        </h3>
                        <FleetPageToolbarRight>
                            <Button asChild size="sm">
                                <Link href="/fleet/drivers/create">
                                    <Plus className="mr-2 size-4" />
                                    New driver
                                </Link>
                            </Button>
                        </FleetPageToolbarRight>
                    </div>
                    {drivers.data.length === 0 ? (
                        <div className="px-6 pb-8">
                            <FleetEmptyState
                                icon={Users}
                                title="No drivers yet"
                                description="Add your first driver to manage assignments and compliance."
                                action={
                                    <Button asChild>
                                        <Link href="/fleet/drivers/create">
                                            <Plus className="mr-2 size-4" />
                                            Add driver
                                        </Link>
                                    </Button>
                                }
                            />
                        </div>
                    ) : (
                        <div className="fleet-glass-table w-full overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow className="border-0 bg-transparent hover:bg-transparent">
                                        <TableHead className="h-11 px-4 font-semibold">Name</TableHead>
                                        <TableHead className="h-11 px-4 font-semibold">License</TableHead>
                                        <TableHead className="h-11 px-4 font-semibold">Status</TableHead>
                                        <TableHead className="h-11 w-[120px] px-4 text-right font-semibold">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {drivers.data.map((row) => (
                                        <TableRow key={row.id} className="group transition-colors">
                                            <TableCell className="px-4 py-3">
                                                <Link href={`/fleet/drivers/${row.id}`} className="font-medium text-foreground hover:underline">
                                                    {row.first_name} {row.last_name}
                                                </Link>
                                            </TableCell>
                                            <TableCell className="px-4 py-3 text-muted-foreground">{row.license_number || '—'}</TableCell>
                                            <TableCell className="px-4 py-3">
                                                <FleetGlassPill variant={row.status === 'active' ? 'success' : 'default'}>
                                                    {row.status}
                                                </FleetGlassPill>
                                            </TableCell>
                                            <TableCell className="px-4 py-3 text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <FleetActionIconLink href={`/fleet/drivers/${row.id}`} label="View" variant="view">
                                                        <Eye className="size-4" />
                                                    </FleetActionIconLink>
                                                    <FleetActionIconLink href={`/fleet/drivers/${row.id}/edit`} label="Edit" variant="edit">
                                                        <Pencil className="size-4" />
                                                    </FleetActionIconLink>
                                                    <FleetActionIconButton label="Delete" variant="delete" onClick={() => setDeleteTarget(row)}>
                                                        <Trash2 className="size-4" />
                                                    </FleetActionIconButton>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            <FleetPagination links={drivers.links ?? []} />
                        </div>
                    )}
                </FleetGlassCard>

                <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete driver</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to delete{' '}
                                <strong>
                                    {deleteTarget?.first_name} {deleteTarget?.last_name}
                                </strong>
                                ? This action cannot be undone.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button variant="outline">Cancel</Button>
                            </DialogClose>
                            {deleteTarget && (
                                <Form
                                    action={`/fleet/drivers/${deleteTarget.id}`}
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
