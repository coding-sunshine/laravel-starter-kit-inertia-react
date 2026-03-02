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
import { Head, Link, router } from '@inertiajs/react';
import { Eye, MapPin, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';

interface RouteRecord {
    id: number;
    name: string;
    route_type: string;
    is_active: boolean;
}
interface Props {
    routes: { data: RouteRecord[]; links: { url: string | null; label: string; active: boolean }[] };
}

export default function FleetRoutesIndex({ routes }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<RouteRecord | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/routes' },
        { title: 'Routes', href: '/fleet/routes' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Routes" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6">
                <FleetPageHeader
                    title="Routes"
                    description="Manage routes and route types."
                    action={
                        <Button asChild>
                            <Link href="/fleet/routes/create">
                                <Plus className="mr-2 size-4" />
                                New route
                            </Link>
                        </Button>
                    }
                />

                <FleetGlassCard className="min-w-0 overflow-hidden" noPadding>
                    <div className="mb-2 flex h-9 items-center justify-between border-b border-white/30 px-4 pt-4 pb-2">
                        <h3 className="text-base font-medium text-[#5b638d]">
                            All routes — {routes.data.length === 0 ? 'No routes yet' : `${routes.data.length} route${routes.data.length === 1 ? '' : 's'}`}
                        </h3>
                        <FleetPageToolbarRight>
                            <Button asChild size="sm">
                                <Link href="/fleet/routes/create">
                                    <Plus className="mr-2 size-4" />
                                    New
                                </Link>
                            </Button>
                        </FleetPageToolbarRight>
                    </div>
                    {routes.data.length === 0 ? (
                        <div className="px-6 pb-8">
                            <FleetEmptyState
                                icon={MapPin}
                                title="No routes yet"
                                description="Create a route to organize trips and schedules."
                                action={
                                    <Button asChild>
                                        <Link href="/fleet/routes/create">Create route</Link>
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
                                            <TableHead className="h-11 px-4 font-semibold">Name</TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">Type</TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">Status</TableHead>
                                            <TableHead className="h-11 w-[120px] px-4 text-right font-semibold">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {routes.data.map((row) => (
                                            <TableRow key={row.id} className="group transition-colors">
                                                <TableCell className="px-4 py-3">
                                                    <Link href={`/fleet/routes/${row.id}`} className="font-medium text-foreground hover:underline">
                                                        {row.name}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">{row.route_type}</TableCell>
                                                <TableCell className="px-4 py-3">
                                                    <FleetGlassPill variant={row.is_active ? 'success' : 'default'}>
                                                        {row.is_active ? 'Active' : 'Inactive'}
                                                    </FleetGlassPill>
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <FleetActionIconLink href={`/fleet/routes/${row.id}`} label="View" variant="view">
                                                            <Eye className="size-4" />
                                                        </FleetActionIconLink>
                                                        <FleetActionIconLink href={`/fleet/routes/${row.id}/edit`} label="Edit" variant="edit">
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
                            <FleetPagination links={routes.links ?? []} />
                        </>
                    )}
                </FleetGlassCard>

                <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete route</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to delete <strong>{deleteTarget?.name}</strong>? This action cannot be undone.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button variant="outline">Cancel</Button>
                            </DialogClose>
                            <Button
                                variant="destructive"
                                onClick={() => {
                                    if (deleteTarget) {
                                        router.delete(`/fleet/routes/${deleteTarget.id}`);
                                        setDeleteTarget(null);
                                    }
                                }}
                            >
                                Delete
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
