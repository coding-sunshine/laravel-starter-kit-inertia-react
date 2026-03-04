import { FleetIndexSummaryBar } from '@/components/fleet';
import type { SummaryStat } from '@/components/fleet';
import { Button } from '@/components/ui/button';
import { ChartContainer } from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { CheckCircle, Clock, Pencil, Plus, ShieldCheck, Trash2, XCircle } from 'lucide-react';
import { PolarAngleAxis, RadialBar, RadialBarChart } from 'recharts';

const healthGaugeConfig = {
    health: { label: 'Compliance Health', color: 'var(--chart-1)' },
} satisfies ChartConfig;

function getGaugeColor(percentage: number): string {
    if (percentage > 80) return 'var(--color-emerald-500, #10b981)';
    if (percentage >= 60) return 'var(--color-amber-500, #f59e0b)';
    return 'var(--color-red-500, #ef4444)';
}

function ComplianceHealthGauge({ percentage }: { percentage: number }) {
    const color = getGaugeColor(percentage);
    const data = [{ name: 'health', value: percentage, fill: color }];

    return (
        <div className="flex flex-col items-center rounded-xl border bg-card p-4 shadow-sm">
            <h4 className="mb-2 text-sm font-medium text-muted-foreground">Compliance Health</h4>
            <ChartContainer config={healthGaugeConfig} className="mx-auto h-[160px] w-full max-w-[200px]">
                <RadialBarChart
                    innerRadius="70%"
                    outerRadius="100%"
                    data={data}
                    startAngle={90}
                    endAngle={-270}
                    barSize={12}
                >
                    <PolarAngleAxis type="number" domain={[0, 100]} angleAxisId={0} tick={false} />
                    <RadialBar
                        dataKey="value"
                        background={{ fill: 'var(--color-muted, #e5e7eb)' }}
                        cornerRadius={6}
                        isAnimationActive={true}
                        animationDuration={800}
                        animationEasing="ease-out"
                    />
                    <text x="50%" y="50%" textAnchor="middle" dominantBaseline="central" className="fill-foreground">
                        <tspan x="50%" dy="-0.3em" className="text-2xl font-bold" style={{ fontSize: '1.5rem', fontWeight: 700 }}>
                            {percentage}%
                        </tspan>
                        <tspan x="50%" dy="1.5em" style={{ fontSize: '0.75rem', fill: 'var(--color-muted-foreground, #6b7280)' }}>
                            Valid
                        </tspan>
                    </text>
                </RadialBarChart>
            </ChartContainer>
        </div>
    );
}

interface ItemRecord {
    id: number;
    entity_type: string;
    entity_id: number;
    compliance_type: string;
    title: string;
    expiry_date: string;
    status: string;
}
interface Props {
    complianceItems: {
        data: ItemRecord[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: Record<string, string>;
    entityTypes: { value: string; name: string }[];
    statuses: { value: string; name: string }[];
    summary?: {
        total: number;
        valid: number;
        expiring_soon: number;
        expired: number;
    };
    complianceHealth?: {
        percentage: number;
    };
}

export default function FleetComplianceItemsIndex({
    complianceItems,
    filters,
    entityTypes,
    statuses,
    summary,
    complianceHealth,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/compliance-items' },
        { title: 'Compliance items', href: '/fleet/compliance-items' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Compliance items" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Compliance items</h1>
                    <Button asChild>
                        <Link href="/fleet/compliance-items/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>

                {(summary || complianceHealth) && (
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-start">
                        {summary && (
                            <FleetIndexSummaryBar
                                stats={
                                    [
                                        { label: 'Total', value: summary.total, icon: ShieldCheck },
                                        { label: 'Valid', value: summary.valid, icon: CheckCircle, variant: 'success' },
                                        { label: 'Expiring Soon', value: summary.expiring_soon, icon: Clock, variant: summary.expiring_soon > 0 ? 'warning' : 'default' },
                                        { label: 'Expired', value: summary.expired, icon: XCircle, variant: summary.expired > 0 ? 'danger' : 'default' },
                                    ] satisfies SummaryStat[]
                                }
                            />
                        )}
                        {complianceHealth && <ComplianceHealthGauge percentage={complianceHealth.percentage} />}
                    </div>
                )}

                <form
                    method="get"
                    className="flex flex-wrap items-end gap-4 rounded-lg border p-4"
                >
                    <div className="space-y-1">
                        <Label>Entity type</Label>
                        <select
                            name="entity_type"
                            defaultValue={filters.entity_type ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {entityTypes.map((e) => (
                                <option key={e.value} value={e.value}>
                                    {e.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Status</Label>
                        <select
                            name="status"
                            defaultValue={filters.status ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
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
                        Filter
                    </Button>
                </form>
                {complianceItems.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <ShieldCheck className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No compliance items yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/compliance-items/create">
                                Add compliance item
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">
                                            Entity
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Type
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Title
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Expiry
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Status
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {complianceItems.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                {row.entity_type} #
                                                {row.entity_id}
                                            </td>
                                            <td className="p-3">
                                                {row.compliance_type}
                                            </td>
                                            <td className="p-3">
                                                <Link
                                                    href={`/fleet/compliance-items/${row.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {row.title}
                                                </Link>
                                            </td>
                                            <td className="p-3">
                                                {new Date(
                                                    row.expiry_date,
                                                ).toLocaleDateString()}
                                            </td>
                                            <td className="p-3">
                                                {row.status}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/compliance-items/${row.id}`}
                                                    >
                                                        View
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/compliance-items/${row.id}/edit`}
                                                    >
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/compliance-items/${row.id}`}
                                                    method="delete"
                                                    className="ml-2 inline"
                                                    onSubmit={(e) => {
                                                        if (!confirm('Delete?'))
                                                            e.preventDefault();
                                                    }}
                                                >
                                                    <Button
                                                        type="submit"
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        <Trash2 className="size-3.5 text-destructive" />
                                                    </Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {complianceItems.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {complianceItems.links.map((link, i) => (
                                    <Link
                                        key={i}
                                        href={link.url ?? '#'}
                                        className={`rounded border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                    >
                                        {link.label}
                                    </Link>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
