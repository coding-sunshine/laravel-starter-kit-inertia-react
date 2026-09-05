import { AreaChart } from '@/components/charts/area-chart';
import { BarChart } from '@/components/charts/bar-chart';
import { PieChart } from '@/components/charts/pie-chart';
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
import type { DataTableResponse } from 'laravel-data-table';
import { DataTable } from 'laravel-data-table';
import { BarChart3 } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface PenaltyRow {
    id: number;
    rake_id: number | null;
    penalty_code: string;
    penalty_type_name: string;
    penalty_amount: string;
    penalty_date: string;
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

const formatCurrency = (v: number) => `₹${v.toLocaleString()}`;

export default function PenaltiesIndex({
    tableData,
    chartData,
    sidings,
    demurrage_rate_per_mt_hour,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
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
                        <Button
                            variant="outline"
                            size="sm"
                            data-pan="penalty-analytics-tab"
                        >
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
                <div
                    className="grid gap-4 lg:grid-cols-3"
                    data-pan="penalty-index-charts"
                >
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">By type</CardTitle>
                            <CardDescription>
                                Distribution of filtered penalties
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {chartData.byType.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">
                                    No data
                                </p>
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
                            <CardTitle className="text-base">
                                By siding
                            </CardTitle>
                            <CardDescription>
                                Top sidings by amount
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {chartData.bySiding.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">
                                    No data
                                </p>
                            ) : (
                                <BarChart
                                    data={chartData.bySiding}
                                    xKey="name"
                                    yKey="total"
                                    layout="vertical"
                                    formatY={formatCurrency}
                                    formatTooltip={formatCurrency}
                                    color="var(--chart-3)"
                                    height={Math.max(
                                        180,
                                        chartData.bySiding.length * 36,
                                    )}
                                />
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">
                                Monthly trend
                            </CardTitle>
                            <CardDescription>Amount over time</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {chartData.monthlyTrend.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">
                                    No data
                                </p>
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
                            <GlossaryTerm term="Demurrage">
                                Demurrage
                            </GlossaryTerm>{' '}
                            formula: hours over free time × weight (
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
                                if (columnId === 'penalty_code') {
                                    return (
                                        <span className="inline-flex rounded bg-muted px-1.5 py-0.5 text-xs font-medium">
                                            {row.penalty_code}
                                        </span>
                                    );
                                }
                                if (columnId === 'penalty_type_name') {
                                    return row.penalty_type_name;
                                }
                                if (columnId === 'penalty_amount') {
                                    return (
                                        <span className="font-medium">
                                            ₹
                                            {Number(
                                                row.penalty_amount,
                                            ).toLocaleString()}
                                        </span>
                                    );
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
                                if (
                                    columnId === 'penalty_amount' &&
                                    value != null
                                ) {
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
