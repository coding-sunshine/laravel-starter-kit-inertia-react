import AppLayout from '@/layouts/app-layout';
import {
    FleetActionIconButton,
    FleetActionIconLink,
    FleetEmptyState,
    FleetGlassCard,
    FleetGlassPill,
    FleetPageHeader,
    FleetPageToolbar,
    FleetPageToolbarLeft,
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
import { Label } from '@/components/ui/label';
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
import { ClipboardList, Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-ring';

interface WorkOrderRecord {
    id: number;
    work_order_number: string;
    title: string;
    status: string;
    priority: string;
    scheduled_date: string | null;
    vehicle?: { id: number; registration: string };
}
interface Props {
    workOrders: { data: WorkOrderRecord[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    vehicles: { id: number; registration: string }[];
    statuses: { value: string; name: string }[];
}

export default function FleetWorkOrdersIndex({ workOrders, filters, vehicles, statuses }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<WorkOrderRecord | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Work orders', href: '/fleet/work-orders' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Work orders" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6">
                <FleetPageHeader
                    title="Work orders"
                    description="Manage maintenance and repair work orders."
                    action={
                        <Button asChild>
                            <Link href="/fleet/work-orders/create">
                                <Plus className="mr-2 size-4" />
                                New work order
                            </Link>
                        </Button>
                    }
                />

                <FleetGlassCard className="p-3">
                    <Form method="get">
                        <FleetPageToolbar>
                            <FleetPageToolbarLeft className="flex flex-wrap items-end gap-3">
                                <div className="space-y-2">
                                    <Label htmlFor="wo-vehicle">Vehicle</Label>
                                    <select id="wo-vehicle" name="vehicle_id" defaultValue={filters.vehicle_id ?? ''} className={selectClass + ' w-[180px]'}>
                                        <option value="">All</option>
                                        {vehicles.map((v) => (
                                            <option key={v.id} value={v.id}>{v.registration}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="wo-status">Status</Label>
                                    <select id="wo-status" name="status" defaultValue={filters.status ?? ''} className={selectClass + ' w-[160px]'}>
                                        <option value="">All</option>
                                        {statuses.map((s) => (
                                            <option key={s.value} value={s.value}>{s.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <Button type="submit" variant="secondary" size="sm">Apply</Button>
                            </FleetPageToolbarLeft>
                            <FleetPageToolbarRight>
                                <Button asChild size="sm">
                                    <Link href="/fleet/work-orders/create">
                                        <Plus className="mr-2 size-4" />
                                        New work order
                                    </Link>
                                </Button>
                            </FleetPageToolbarRight>
                        </FleetPageToolbar>
                    </Form>
                </FleetGlassCard>

                <FleetGlassCard className="min-w-0 overflow-hidden" noPadding>
                    <div className="mb-2 flex h-9 items-center justify-between border-b border-white/30 px-4 pt-4 pb-2">
                        <h3 className="text-base font-medium text-[#5b638d]">
                            All work orders — {workOrders.data.length === 0 ? 'None' : `${workOrders.data.length} work order${workOrders.data.length === 1 ? '' : 's'}`}
                        </h3>
                        <FleetPageToolbarRight>
                            <Button asChild size="sm">
                                <Link href="/fleet/work-orders/create">
                                    <Plus className="mr-2 size-4" />
                                    New
                                </Link>
                            </Button>
                        </FleetPageToolbarRight>
                    </div>
                    {workOrders.data.length === 0 ? (
                        <div className="px-6 pb-8">
                            <FleetEmptyState
                                icon={ClipboardList}
                                title="No work orders yet"
                                description="Create a work order to schedule maintenance or repairs."
                                action={
                                    <Button asChild>
                                        <Link href="/fleet/work-orders/create">
                                            <Plus className="mr-2 size-4" />
                                            Create work order
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
                                            <TableHead className="h-11 px-4 font-semibold">Number</TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">Title</TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">Vehicle</TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">Status</TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">Priority</TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">Scheduled</TableHead>
                                            <TableHead className="h-11 w-[120px] px-4 text-right font-semibold">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {workOrders.data.map((row) => (
                                            <TableRow key={row.id} className="group transition-colors">
                                                <TableCell className="px-4 py-3">
                                                    <Link href={`/fleet/work-orders/${row.id}`} className="font-medium text-foreground hover:underline">
                                                        {row.work_order_number}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">{row.title}</TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">{row.vehicle?.registration ?? '—'}</TableCell>
                                                <TableCell className="px-4 py-3">
                                                    <FleetGlassPill variant="default">{row.status}</FleetGlassPill>
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">{row.priority}</TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">
                                                    {row.scheduled_date ? new Date(row.scheduled_date).toLocaleDateString() : '—'}
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <FleetActionIconLink href={`/fleet/work-orders/${row.id}`} label="View" variant="view">
                                                            <Eye className="size-4" />
                                                        </FleetActionIconLink>
                                                        <FleetActionIconLink href={`/fleet/work-orders/${row.id}/edit`} label="Edit" variant="edit">
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
                            </div>
                            <FleetPagination
                                links={workOrders.links ?? []}
                                showingLabel={
                                    workOrders.data.length > 0
                                        ? `Showing ${workOrders.data.length} work order${workOrders.data.length === 1 ? '' : 's'}`
                                        : undefined
                                }
                            />
                        </>
                    )}
                </FleetGlassCard>

                <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete work order</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to delete{' '}
                                <strong>{deleteTarget?.work_order_number}</strong>? This action cannot be
                                undone.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button variant="outline">Cancel</Button>
                            </DialogClose>
                            {deleteTarget && (
                                <Form
                                    action={`/fleet/work-orders/${deleteTarget.id}`}
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
