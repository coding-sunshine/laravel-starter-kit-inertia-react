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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import { ClipboardList, Eye, MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react';
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

                <Card className="border border-border shadow-sm">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base font-semibold">Filters</CardTitle>
                        <CardDescription>Narrow by vehicle or status.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form method="get" className="flex flex-wrap items-end gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="wo-vehicle">Vehicle</Label>
                                <select
                                    id="wo-vehicle"
                                    name="vehicle_id"
                                    defaultValue={filters.vehicle_id ?? ''}
                                    className={selectClass}
                                >
                                    <option value="">All</option>
                                    {vehicles.map((v) => (
                                        <option key={v.id} value={v.id}>
                                            {v.registration}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="wo-status">Status</Label>
                                <select
                                    id="wo-status"
                                    name="status"
                                    defaultValue={filters.status ?? ''}
                                    className={selectClass}
                                >
                                    <option value="">All</option>
                                    {statuses.map((s) => (
                                        <option key={s.value} value={s.value}>
                                            {s.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <Button type="submit" variant="secondary" size="sm">
                                Apply
                            </Button>
                        </Form>
                    </CardContent>
                </Card>

                <Card className="border border-border shadow-sm">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base font-semibold">All work orders</CardTitle>
                        <CardDescription>
                            {workOrders.data.length === 0
                                ? 'No work orders match the filters.'
                                : `${workOrders.data.length} work order${workOrders.data.length === 1 ? '' : 's'}`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
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
                                <div className="overflow-hidden rounded-b-xl border-t border-border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow className="bg-muted/40 hover:bg-muted/40">
                                                <TableHead className="h-11 px-4 font-semibold">Number</TableHead>
                                                <TableHead className="h-11 px-4 font-semibold">Title</TableHead>
                                                <TableHead className="h-11 px-4 font-semibold">Vehicle</TableHead>
                                                <TableHead className="h-11 px-4 font-semibold">Status</TableHead>
                                                <TableHead className="h-11 px-4 font-semibold">Priority</TableHead>
                                                <TableHead className="h-11 px-4 font-semibold">Scheduled</TableHead>
                                                <TableHead className="h-11 w-[80px] px-4 text-right font-semibold">
                                                    Actions
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {workOrders.data.map((row) => (
                                                <TableRow key={row.id} className="group transition-colors">
                                                    <TableCell className="px-4 py-3">
                                                        <Link
                                                            href={`/fleet/work-orders/${row.id}`}
                                                            className="font-medium text-foreground hover:underline"
                                                        >
                                                            {row.work_order_number}
                                                        </Link>
                                                    </TableCell>
                                                    <TableCell className="px-4 py-3 text-muted-foreground">
                                                        {row.title}
                                                    </TableCell>
                                                    <TableCell className="px-4 py-3 text-muted-foreground">
                                                        {row.vehicle?.registration ?? '—'}
                                                    </TableCell>
                                                    <TableCell className="px-4 py-3">
                                                        <span className="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                                                            {row.status}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell className="px-4 py-3 text-muted-foreground">
                                                        {row.priority}
                                                    </TableCell>
                                                    <TableCell className="px-4 py-3 text-muted-foreground">
                                                        {row.scheduled_date
                                                            ? new Date(row.scheduled_date).toLocaleDateString()
                                                            : '—'}
                                                    </TableCell>
                                                    <TableCell className="px-4 py-3 text-right">
                                                        <div className="flex items-center justify-end gap-1">
                                                            <Button variant="ghost" size="icon" className="size-8" asChild>
                                                                <Link href={`/fleet/work-orders/${row.id}`} title="View details">
                                                                    <Eye className="size-4" />
                                                                </Link>
                                                            </Button>
                                                            <DropdownMenu>
                                                                <DropdownMenuTrigger asChild>
                                                                    <Button variant="ghost" size="icon" className="size-8" title="More actions">
                                                                        <MoreHorizontal className="size-4" />
                                                                    </Button>
                                                                </DropdownMenuTrigger>
                                                                <DropdownMenuContent align="end">
                                                                    <DropdownMenuItem asChild>
                                                                        <Link href={`/fleet/work-orders/${row.id}/edit`}>
                                                                            <Pencil className="mr-2 size-4" />
                                                                            Edit
                                                                        </Link>
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem
                                                                        className="text-destructive focus:text-destructive"
                                                                        onClick={() => setDeleteTarget(row)}
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
                                {workOrders.links && workOrders.links.length > 1 && (
                                    <div className="flex flex-wrap gap-2 border-t border-border px-4 py-3">
                                        {workOrders.links.map((link, i) => (
                                            <Link
                                                key={i}
                                                href={link.url ?? '#'}
                                                className={`rounded-md border px-3 py-1.5 text-sm font-medium transition-colors ${
                                                    link.active
                                                        ? 'border-primary bg-primary text-primary-foreground'
                                                        : 'border-border hover:bg-muted'
                                                }`}
                                            >
                                                {link.label}
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>

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
