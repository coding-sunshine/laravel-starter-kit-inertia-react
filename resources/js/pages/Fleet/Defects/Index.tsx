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
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { AlertTriangle, Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface DefectRecord {
    id: number;
    defect_number: string;
    title: string;
    severity: string;
    status: string;
    reported_at: string;
    vehicle?: { id: number; registration: string };
}
interface Props {
    defects: { data: DefectRecord[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    vehicles: { id: number; registration: string }[];
    statuses: { value: string; name: string }[];
    severities: { value: string; name: string }[];
}

const selectClass =
    'flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-ring';

export default function FleetDefectsIndex({ defects, filters, vehicles, statuses, severities }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/defects' },
        { title: 'Defects', href: '/fleet/defects' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Defects" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 md:p-6">
                <FleetPageHeader
                    title="Defects"
                    description="Reported defects and their status."
                    action={
                        <Button asChild>
                            <Link href="/fleet/defects/create">
                                <Plus className="mr-2 size-4" />
                                New defect
                            </Link>
                        </Button>
                    }
                />

                <FleetGlassCard className="p-3">
                    <Form method="get">
                        <FleetPageToolbar>
                            <FleetPageToolbarLeft className="flex flex-wrap items-end gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs">Vehicle</Label>
                                    <select name="vehicle_id" defaultValue={filters.vehicle_id ?? ''} className={selectClass + ' w-[160px]'}>
                                        <option value="">All</option>
                                        {vehicles.map((v) => (
                                            <option key={v.id} value={v.id}>{v.registration}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs">Status</Label>
                                    <select name="status" defaultValue={filters.status ?? ''} className={selectClass + ' w-[140px]'}>
                                        <option value="">All</option>
                                        {statuses.map((s) => (
                                            <option key={s.value} value={s.value}>{s.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs">Severity</Label>
                                    <select name="severity" defaultValue={filters.severity ?? ''} className={selectClass + ' w-[140px]'}>
                                        <option value="">All</option>
                                        {severities.map((s) => (
                                            <option key={s.value} value={s.value}>{s.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <Button type="submit" variant="secondary" size="sm">Filter</Button>
                            </FleetPageToolbarLeft>
                        </FleetPageToolbar>
                    </Form>
                </FleetGlassCard>

                <FleetGlassCard className="min-w-0 overflow-hidden" noPadding>
                    <div className="mb-2 flex h-9 items-center justify-between border-b border-white/30 px-4 pt-4 pb-2">
                        <h3 className="text-base font-medium text-[#5b638d]">
                            Defect list — {defects.data.length === 0 ? 'No defects' : `${defects.data.length} defect${defects.data.length === 1 ? '' : 's'}`}
                        </h3>
                        <FleetPageToolbarRight>
                            <Button asChild size="sm">
                                <Link href="/fleet/defects/create">
                                    <Plus className="mr-2 size-4" />
                                    New
                                </Link>
                            </Button>
                        </FleetPageToolbarRight>
                    </div>
                    {defects.data.length === 0 ? (
                        <div className="px-6 pb-8">
                            <FleetEmptyState
                                icon={AlertTriangle}
                                title="No defects reported"
                                description="Report a defect to track and resolve issues."
                                action={
                                    <Button asChild>
                                        <Link href="/fleet/defects/create">Report defect</Link>
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
                                            <TableHead className="h-11 px-4 font-semibold">Severity</TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">Status</TableHead>
                                            <TableHead className="h-11 px-4 font-semibold">Reported</TableHead>
                                            <TableHead className="h-11 w-[120px] px-4 text-right font-semibold">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {defects.data.map((row) => (
                                            <TableRow key={row.id} className="group transition-colors">
                                                <TableCell className="px-4 py-3">
                                                    <Link href={`/fleet/defects/${row.id}`} className="font-medium text-foreground hover:underline">
                                                        {row.defect_number}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">{row.title}</TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">{row.vehicle?.registration ?? '—'}</TableCell>
                                                <TableCell className="px-4 py-3">
                                                    <FleetGlassPill
                                                        variant={
                                                            row.severity?.toLowerCase() === 'critical' || row.severity?.toLowerCase() === 'high'
                                                                ? 'critical'
                                                                : row.severity?.toLowerCase() === 'medium'
                                                                  ? 'warning'
                                                                  : 'default'
                                                        }
                                                    >
                                                        {row.severity}
                                                    </FleetGlassPill>
                                                </TableCell>
                                                <TableCell className="px-4 py-3">
                                                    <FleetGlassPill variant="default">{row.status}</FleetGlassPill>
                                                </TableCell>
                                                <TableCell className="px-4 py-3 text-muted-foreground">{new Date(row.reported_at).toLocaleString()}</TableCell>
                                                <TableCell className="px-4 py-3 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <FleetActionIconLink href={`/fleet/defects/${row.id}`} label="View" variant="view">
                                                            <Eye className="size-4" />
                                                        </FleetActionIconLink>
                                                        <FleetActionIconLink href={`/fleet/defects/${row.id}/edit`} label="Edit" variant="edit">
                                                            <Pencil className="size-4" />
                                                        </FleetActionIconLink>
                                                        <Form action={`/fleet/defects/${row.id}`} method="delete" className="inline" onSubmit={(e) => { if (!confirm('Delete?')) e.preventDefault(); }}>
                                                            <FleetActionIconButton type="submit" label="Delete" variant="delete">
                                                                <Trash2 className="size-4" />
                                                            </FleetActionIconButton>
                                                        </Form>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                            <FleetPagination links={defects.links ?? []} />
                        </>
                    )}
                </FleetGlassCard>
            </div>
        </AppLayout>
    );
}
