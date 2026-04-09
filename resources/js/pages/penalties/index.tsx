import { AreaChart } from '@/components/charts/area-chart';
import { BarChart } from '@/components/charts/bar-chart';
import { PieChart } from '@/components/charts/pie-chart';
import { DataTable } from 'laravel-data-table';
import type { DataTableResponse } from 'laravel-data-table';
import { GlossaryTerm } from '@/components/glossary-term';
import Heading from '@/components/heading';
import { RrmcsGuidance } from '@/components/rrmcs-guidance';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { BarChart3, Bot, Check, Pencil, Scale, X } from 'lucide-react';
import { useRef, useState } from 'react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface CalculationBreakdown {
    formula?: string;
    demurrage_hours?: number;
    weight_mt?: number;
    rate_per_mt_hour?: number;
    free_hours?: number | null;
    dwell_hours?: number | null;
}

interface PenaltyRow {
    id: number;
    rake_id: number;
    penalty_type: string;
    penalty_amount: string;
    penalty_status: string;
    penalty_date: string;
    responsible_party: string | null;
    root_cause: string | null;
    calculation_breakdown?: CalculationBreakdown | null;
    rake_number: string | null;
    siding_name: string | null;
}

interface ChartData {
    byType: { name: string; value: number; count: number }[];
    bySiding: { name: string; total: number }[];
    monthlyTrend: { month: string; total: number; count: number }[];
}

interface Props {
    tableData: DataTableResponse<PenaltyRow>;
    chartData: ChartData;
    sidings: Siding[];
    demurrage_rate_per_mt_hour: number;
}

const RESPONSIBLE_PARTIES = [
    { value: '', label: 'Unassigned' },
    { value: 'railway', label: 'Railway' },
    { value: 'siding', label: 'Siding' },
    { value: 'transporter', label: 'Transporter' },
    { value: 'plant', label: 'Plant' },
    { value: 'other', label: 'Other' },
];

const STATUS_COLORS: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    incurred: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    disputed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    waived: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
};

function InlineResponsibleParty({ penalty }: { penalty: PenaltyRow }) {
    const handleChange = (value: string) => {
        router.patch(`/penalties/${penalty.id}`, {
            responsible_party: value || null,
        }, { preserveScroll: true });
    };

    return (
        <select
            value={penalty.responsible_party ?? ''}
            onChange={(e) => handleChange(e.target.value)}
            className="rounded border border-input bg-background px-2 py-1 text-xs"
            data-pan="penalty-assign-responsibility"
        >
            {RESPONSIBLE_PARTIES.map((rp) => (
                <option key={rp.value} value={rp.value}>
                    {rp.label}
                </option>
            ))}
        </select>
    );
}

function InlineRootCause({ penalty }: { penalty: PenaltyRow }) {
    const [editing, setEditing] = useState(false);
    const [value, setValue] = useState(penalty.root_cause ?? '');
    const inputRef = useRef<HTMLInputElement>(null);

    const save = () => {
        router.patch(`/penalties/${penalty.id}`, {
            root_cause: value || null,
        }, { preserveScroll: true });
        setEditing(false);
    };

    const cancel = () => {
        setValue(penalty.root_cause ?? '');
        setEditing(false);
    };

    if (editing) {
        return (
            <div className="flex items-center gap-1">
                <input
                    ref={inputRef}
                    type="text"
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') save();
                        if (e.key === 'Escape') cancel();
                    }}
                    className="w-full max-w-xs rounded border border-input bg-background px-2 py-1 text-xs"
                    placeholder="Why did this penalty happen?"
                    autoFocus
                    data-pan="penalty-set-root-cause"
                />
                <button onClick={save} className="rounded p-0.5 hover:bg-muted" type="button">
                    <Check className="h-3.5 w-3.5 text-green-600" />
                </button>
                <button onClick={cancel} className="rounded p-0.5 hover:bg-muted" type="button">
                    <X className="h-3.5 w-3.5 text-muted-foreground" />
                </button>
            </div>
        );
    }

    return (
        <button
            onClick={() => setEditing(true)}
            className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
            type="button"
        >
            {penalty.root_cause ? (
                <span className="max-w-xs truncate text-foreground">{penalty.root_cause}</span>
            ) : (
                <span className="italic">Add root cause</span>
            )}
            <Pencil className="h-3 w-3 shrink-0" />
        </button>
    );
}

const formatCurrency = (v: number) => `₹${v.toLocaleString()}`;

export default function PenaltiesIndex({
    tableData,
    chartData,
    sidings,
    demurrage_rate_per_mt_hour,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Penalties', href: '/penalties' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Penalties" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Penalties"
                        description="Penalty register by rake and siding"
                    />
                    <Link href="/penalties/analytics">
                        <Button variant="outline" size="sm" data-pan="penalty-analytics-tab">
                            <BarChart3 className="mr-1.5 h-4 w-4" />
                            Analytics
                        </Button>
                    </Link>
                </div>
                <RrmcsGuidance
                    title="What this section is for"
                    before="Penalty amounts calculated manually from RR after the fact — often discovered days late."
                    after="Real-time penalty tracking with automated calculation (hours × MT × rate); demurrage alerts warn you BEFORE penalties hit."
                />
                {/* Dynamic charts — reflect current table filters */}
                <div className="grid gap-4 lg:grid-cols-3" data-pan="penalty-index-charts">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">By type</CardTitle>
                            <CardDescription>Distribution of filtered penalties</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {chartData.byType.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">No data</p>
                            ) : (
                                <PieChart
                                    data={chartData.byType}
                                    nameKey="name"
                                    valueKey="value"
                                    formatTooltip={formatCurrency}
                                    height={200}
                                />
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">By siding</CardTitle>
                            <CardDescription>Top sidings by amount</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {chartData.bySiding.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">No data</p>
                            ) : (
                                <BarChart
                                    data={chartData.bySiding}
                                    xKey="name"
                                    yKey="total"
                                    layout="vertical"
                                    formatY={formatCurrency}
                                    formatTooltip={formatCurrency}
                                    color="var(--chart-3)"
                                    height={Math.max(180, chartData.bySiding.length * 36)}
                                />
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Monthly trend</CardTitle>
                            <CardDescription>Amount over time</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {chartData.monthlyTrend.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">No data</p>
                            ) : (
                                <AreaChart
                                    data={chartData.monthlyTrend}
                                    xKey="month"
                                    yKey="total"
                                    yLabel="₹"
                                    formatY={formatCurrency}
                                    formatTooltip={formatCurrency}
                                    height={200}
                                />
                            )}
                        </CardContent>
                    </Card>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Penalty register</CardTitle>
                        <CardDescription>
                            Filter by siding, status, type, or date.{' '}
                            <GlossaryTerm term="Demurrage">Demurrage</GlossaryTerm> formula:
                            hours over free time × weight (
                            <GlossaryTerm term="MT">MT</GlossaryTerm>) × ₹
                            {demurrage_rate_per_mt_hour}/MT/h.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable<PenaltyRow>
                            tableData={tableData}
                            tableName="penalties"
                            actions={[
                                {
                                    label: 'View rake',
                                    onClick: (row) =>
                                        router.visit(`/rakes/${row.rake_id}`),
                                },
                            ]}
                            renderCell={(columnId, _value, row) => {
                                if (columnId === 'penalty_type') {
                                    return (
                                        <span className="inline-flex rounded bg-muted px-1.5 py-0.5 text-xs font-medium">
                                            {row.penalty_type}
                                        </span>
                                    );
                                }
                                if (columnId === 'penalty_amount') {
                                    return (
                                        <span className="font-medium">
                                            ₹{Number(row.penalty_amount).toLocaleString()}
                                        </span>
                                    );
                                }
                                if (columnId === 'penalty_status') {
                                    return (
                                        <span
                                            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_COLORS[row.penalty_status] ?? 'bg-muted text-muted-foreground'}`}
                                        >
                                            {row.penalty_status}
                                        </span>
                                    );
                                }
                                if (columnId === 'responsible_party') {
                                    return <InlineResponsibleParty penalty={row} />;
                                }
                                if (columnId === 'root_cause') {
                                    return <InlineRootCause penalty={row} />;
                                }
                                if (columnId === 'rake_number') {
                                    return row.rake_number ?? '-';
                                }
                                if (columnId === 'siding_name') {
                                    return row.siding_name ?? '-';
                                }
                                return undefined;
                            }}
                            renderFooterCell={(columnId, value) => {
                                if (columnId === 'penalty_amount' && value != null) {
                                    return (
                                        <span className="font-medium text-emerald-600">
                                            ₹{Number(value).toLocaleString()}
                                        </span>
                                    );
                                }
                                return undefined;
                            }}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
