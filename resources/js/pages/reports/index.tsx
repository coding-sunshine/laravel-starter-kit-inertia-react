import { AreaChart } from '@/components/charts/area-chart';
import { BarChart } from '@/components/charts/bar-chart';
import { PieChart } from '@/components/charts/pie-chart';
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
import { Head } from '@inertiajs/react';
import axios from 'axios';
import {
    BarChart3,
    CalendarRange,
    Download,
    FileSpreadsheet,
    Loader2,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface ReportMeta {
    name: string;
    description: string;
}

interface Props {
    reports: Record<string, ReportMeta>;
    sidings: Siding[];
}

type ReportData = Record<string, unknown>[];

const RAKE_MANAGEMENT_REPORTS: string[] = [
    'siding_coal_receipt',
    'rake_indent',
    'txr',
    'unfit_wagon',
    'wagon_loading',
    'weighment',
    'loader_vs_weighment',
    'rake_movement',
    'rr_summary',
    'penalty_register',
];

/** Determine the best chart for a given report key. */
function getChartType(key: string): 'area' | 'bar' | 'pie' | 'table' {
    if (['demurrage_analysis', 'siding_coal_receipt'].includes(key)) return 'area';
    if (['penalty_register', 'rake_movement', 'wagon_loading', 'loader_vs_weighment', 'unfit_wagon', 'weighment'].includes(key)) return 'bar';
    if (['indent_fulfillment'].includes(key)) return 'pie';
    return 'table';
}

function formatCurrency(n: number): string {
    if (n >= 100000) return `₹${(n / 100000).toFixed(1)}L`;
    if (n >= 1000) return `₹${(n / 1000).toFixed(1)}K`;
    return `₹${n.toFixed(0)}`;
}

/** Extract chart-friendly data from report results. */
function extractChartData(key: string, data: ReportData): { chartData: Record<string, unknown>[]; xKey: string; yKey: string; nameKey?: string } | null {
    if (!data || data.length === 0) return null;

    // For delegated reports (daily_operations, etc.) the data comes wrapped
    const first = data[0];

    if (key === 'demurrage_analysis' && first?.by_month && Array.isArray(first.by_month)) {
        return { chartData: first.by_month as Record<string, unknown>[], xKey: 'month', yKey: 'total' };
    }
    if (key === 'siding_coal_receipt') {
        const grouped: Record<string, number> = {};
        data.forEach((r) => {
            const dt = String(r.date ?? 'Unknown');
            grouped[dt] = (grouped[dt] ?? 0) + Number(r.received_qty_mt ?? 0);
        });
        const chartData = Object.entries(grouped).map(([date, total_mt]) => ({ date, total_mt }));
        return { chartData, xKey: 'date', yKey: 'total_mt' };
    }
    if (key === 'penalty_register') {
        // Group by penalty_type for bar
        const grouped: Record<string, number> = {};
        data.forEach((r) => {
            const t = String(r.penalty_type ?? 'Unknown');
            grouped[t] = (grouped[t] ?? 0) + Number(r.penalty_amount ?? 0);
        });
        const chartData = Object.entries(grouped).map(([name, total]) => ({ name, total }));
        return { chartData, xKey: 'name', yKey: 'total' };
    }
    if (key === 'rake_movement') {
        return { chartData: data.slice(0, 20), xKey: 'rake_number', yKey: 'duration_minutes' };
    }
    if (key === 'indent_fulfillment' && first?.summary) {
        const s = first.summary as Record<string, number>;
        const chartData = [
            { name: 'Fulfilled', value: s.fulfilled ?? 0 },
            { name: 'Partial', value: s.partial ?? 0 },
            { name: 'Pending', value: s.pending ?? 0 },
            { name: 'Overdue', value: s.overdue ?? 0 },
        ].filter((d) => d.value > 0);
        return { chartData, xKey: 'name', yKey: 'value', nameKey: 'name' };
    }
    if (key === 'wagon_loading') {
        return { chartData: data.slice(0, 20), xKey: 'wagon_number', yKey: 'loader_qty_mt' };
    }
    if (key === 'loader_vs_weighment') {
        return { chartData: data.slice(0, 20), xKey: 'wagon_number', yKey: 'variance' };
    }

    return null;
}

/** Render the summary section for delegated reports. */
function ReportSummary({ data, reportKey }: { data: ReportData; reportKey: string }) {
    const first = data[0];
    if (!first) return null;

    const summary = (first as Record<string, unknown>).summary as Record<string, unknown> | undefined;
    if (!summary) return null;

    return (
        <div className="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {Object.entries(summary).map(([k, v]) => (
                <div key={k} className="rounded-md border bg-muted/30 p-3">
                    <p className="text-xs text-muted-foreground">
                        {k.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
                    </p>
                    <p className="mt-0.5 text-lg font-semibold">
                        {typeof v === 'number'
                            ? (k.includes('amount') || k.includes('charged') || k.includes('pending') || k.includes('collected') || k.includes('savings') || k.includes('demurrage'))
                                ? formatCurrency(v)
                                : v.toLocaleString()
                            : String(v)}
                    </p>
                </div>
            ))}
        </div>
    );
}

export default function ReportsIndex({ reports, sidings }: Props) {
    const [activeKey, setActiveKey] = useState<string>(Object.keys(reports)[0] ?? 'siding_coal_receipt');
    const [sidingId, setSidingId] = useState<string>('');
    const [dateFrom, setDateFrom] = useState<string>('');
    const [dateTo, setDateTo] = useState<string>('');
    const [loading, setLoading] = useState(false);
    const [data, setData] = useState<ReportData | null>(null);
    const [error, setError] = useState<string | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Reports', href: '/reports' },
    ];

    const activeReport = reports[activeKey];

    const generate = useCallback(async () => {
        setLoading(true);
        setError(null);
        setData(null);
        try {
            const resp = await axios.post('/reports/generate', {
                key: activeKey,
                siding_id: sidingId || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
                preview: true,
                preview_limit: 25,
            });
            setData(resp.data.data ?? []);
        } catch (e: unknown) {
            const msg = e instanceof Error ? e.message : 'Failed to generate report';
            setError(msg);
        } finally {
            setLoading(false);
        }
    }, [activeKey, sidingId, dateFrom, dateTo]);

    const downloadCsv = useCallback(async () => {
        try {
            const resp = await axios.post(
                '/reports/generate',
                {
                    key: activeKey,
                    siding_id: sidingId || undefined,
                    date_from: dateFrom || undefined,
                    date_to: dateTo || undefined,
                    export_csv: true,
                },
                { responseType: 'blob' },
            );
            const url = window.URL.createObjectURL(new Blob([resp.data]));
            const a = document.createElement('a');
            a.href = url;
            a.download = `${activeReport?.name ?? activeKey}_${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        } catch {
            setError('CSV download failed');
        }
    }, [activeKey, sidingId, dateFrom, dateTo, activeReport]);

    const downloadXlsx = useCallback(async () => {
        try {
            const resp = await axios.post(
                '/reports/generate',
                {
                    key: activeKey,
                    siding_id: sidingId || undefined,
                    date_from: dateFrom || undefined,
                    date_to: dateTo || undefined,
                    export_xlsx: true,
                },
                { responseType: 'blob' },
            );
            const url = window.URL.createObjectURL(new Blob([resp.data]));
            const a = document.createElement('a');
            a.href = url;
            a.download = `${activeReport?.name ?? activeKey}_${new Date().toISOString().slice(0, 10)}.xlsx`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        } catch {
            setError('XLSX download failed');
        }
    }, [activeKey, sidingId, dateFrom, dateTo, activeReport]);

    const chartInfo = useMemo(() => {
        if (!data) return null;
        return extractChartData(activeKey, data);
    }, [data, activeKey]);

    const chartType = getChartType(activeKey);

    // Flatten data for table display
    const tableRows = useMemo<Record<string, unknown>[]>(() => {
        if (!data || data.length === 0) return [];
        const first = data[0];
        // Delegated reports wrap data in summary/items — try to extract items
        if (first && typeof first === 'object') {
            const inner = (first as Record<string, unknown>);
            // Look for array fields that contain the actual rows
            for (const k of ['transactions', 'rakes', 'indents', 'by_month']) {
                if (Array.isArray(inner[k])) {
                    return inner[k] as Record<string, unknown>[];
                }
            }
        }
        // Flat report data
        return data;
    }, [data]);

    const tableColumns = useMemo(() => {
        if (tableRows.length === 0) return [];
        return Object.keys(tableRows[0]).filter(
            (k) => typeof tableRows[0][k] !== 'object' || tableRows[0][k] === null,
        );
    }, [tableRows]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reports" />
            <div className="space-y-6">
                <Heading
                    title="Reports"
                    description="Generate and export operational reports"
                />
                <RrmcsGuidance
                    title="What this section is for"
                    before="Reports extracted manually from different registers and Excel files — takes hours and is error-prone."
                    after="One-click report generation with charts and CSV export for all siding operations, penalties, indents, and financial data."
                />

                <div className="grid gap-6 lg:grid-cols-[240px_1fr]">
                    {/* Sidebar: Report Types */}
                    <Card className="h-fit">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm">Report Types</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 p-3 pt-0">
                            <div>
                                <p className="mb-1 px-2 text-xs font-medium text-muted-foreground uppercase">
                                    Reports
                                </p>
                                <div className="space-y-0.5">
                                    {RAKE_MANAGEMENT_REPORTS
                                        .filter((k) => reports[k])
                                        .map((k) => (
                                            <button
                                                key={k}
                                                onClick={() => {
                                                    setActiveKey(k);
                                                    setData(null);
                                                    setError(null);
                                                }}
                                                className={`w-full rounded-md px-2 py-1.5 text-left text-sm transition-colors ${
                                                    activeKey === k
                                                        ? 'bg-primary text-primary-foreground'
                                                        : 'hover:bg-muted'
                                                }`}
                                                data-pan="report-select-type"
                                                type="button"
                                            >
                                                {reports[k].name}
                                            </button>
                                        ))}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Main area */}
                    <div className="space-y-4">
                        {/* Controls bar */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2">
                                    <FileSpreadsheet className="h-5 w-5" />
                                    {activeReport?.name ?? activeKey}
                                </CardTitle>
                                <CardDescription>
                                    {activeReport?.description}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="flex flex-wrap items-end gap-3">
                                    <div className="grid gap-1.5">
                                        <label className="text-xs font-medium">Siding</label>
                                        <select
                                            value={sidingId}
                                            onChange={(e) => setSidingId(e.target.value)}
                                            className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">All sidings</option>
                                            {sidings.map((s) => (
                                                <option key={s.id} value={s.id}>
                                                    {s.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="grid gap-1.5">
                                        <label className="text-xs font-medium">
                                            <CalendarRange className="mr-1 inline h-3 w-3" />
                                            From
                                        </label>
                                        <input
                                            type="date"
                                            value={dateFrom}
                                            onChange={(e) => setDateFrom(e.target.value)}
                                            className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        />
                                    </div>
                                    <div className="grid gap-1.5">
                                        <label className="text-xs font-medium">To</label>
                                        <input
                                            type="date"
                                            value={dateTo}
                                            onChange={(e) => setDateTo(e.target.value)}
                                            className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        />
                                    </div>
                                    <Button
                                        onClick={generate}
                                        disabled={loading}
                                        data-pan="report-generate"
                                    >
                                        {loading ? (
                                            <Loader2 className="mr-1.5 h-4 w-4 animate-spin" />
                                        ) : (
                                            <BarChart3 className="mr-1.5 h-4 w-4" />
                                        )}
                                        Generate
                                    </Button>
                                    {data && data.length > 0 && (
                                        <Button
                                            variant="outline"
                                            onClick={downloadCsv}
                                            data-pan="report-download-csv"
                                        >
                                            <Download className="mr-1.5 h-4 w-4" />
                                            CSV
                                        </Button>
                                    )}
                                    {data && data.length > 0 && (
                                        <Button
                                            variant="outline"
                                            onClick={downloadXlsx}
                                            data-pan="report-download-xlsx"
                                        >
                                            <Download className="mr-1.5 h-4 w-4" />
                                            XLSX
                                        </Button>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Error */}
                        {error && (
                            <div className="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                                {error}
                            </div>
                        )}

                        {/* Results */}
                        {data !== null && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Results</CardTitle>
                                    <CardDescription>
                                        Showing first {tableRows.length} rows (preview). Export for full data.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {/* Summary cards for delegated reports */}
                                    <ReportSummary data={data} reportKey={activeKey} />

                                    {/* Chart */}
                                    {/*
                                      Chart preview is intentionally hidden for now.
                                      Re-enable by uncommenting this block.
                                    */}
                                    {/*
                                    {chartInfo && chartInfo.chartData.length > 0 && (
                                        <div className="rounded-lg border bg-muted/20 p-4">
                                            {chartType === 'area' && (
                                                <AreaChart
                                                    data={chartInfo.chartData}
                                                    xKey={chartInfo.xKey}
                                                    yKey={chartInfo.yKey}
                                                    height={280}
                                                    formatY={
                                                        chartInfo.yKey === 'total'
                                                            ? formatCurrency
                                                            : undefined
                                                    }
                                                    formatTooltip={
                                                        chartInfo.yKey === 'total'
                                                            ? formatCurrency
                                                            : undefined
                                                    }
                                                />
                                            )}
                                            {chartType === 'bar' && (
                                                <BarChart
                                                    data={chartInfo.chartData}
                                                    xKey={chartInfo.xKey}
                                                    yKey={chartInfo.yKey}
                                                    height={280}
                                                    formatY={
                                                        chartInfo.yKey === 'total'
                                                            ? formatCurrency
                                                            : undefined
                                                    }
                                                    formatTooltip={
                                                        chartInfo.yKey === 'total'
                                                            ? formatCurrency
                                                            : undefined
                                                    }
                                                />
                                            )}
                                            {chartType === 'pie' && chartInfo.nameKey && (
                                                <PieChart
                                                    data={chartInfo.chartData}
                                                    nameKey={chartInfo.nameKey}
                                                    valueKey={chartInfo.yKey}
                                                    height={280}
                                                />
                                            )}
                                        </div>
                                    )}
                                    */}

                                    {/* Data table */}
                                    {tableRows.length > 0 && tableColumns.length > 0 ? (
                                        <div className="overflow-x-auto rounded-md border">
                                            <table className="w-full text-sm">
                                                <thead>
                                                    <tr className="border-b bg-muted/50">
                                                        {tableColumns.map((col) => (
                                                            <th
                                                                key={col}
                                                                className="whitespace-nowrap px-4 py-3 text-left font-medium"
                                                            >
                                                                {col
                                                                    .replace(/_/g, ' ')
                                                                    .replace(/\b\w/g, (c) =>
                                                                        c.toUpperCase(),
                                                                    )}
                                                            </th>
                                                        ))}
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {tableRows.slice(0, 100).map((row, idx) => (
                                                        <tr
                                                            key={idx}
                                                            className="border-b last:border-0 hover:bg-muted/30"
                                                        >
                                                            {tableColumns.map((col) => {
                                                                const val = row[col];
                                                                const isNumber = typeof val === 'number';
                                                                const isCurrency = isNumber && (
                                                                    col.includes('amount') ||
                                                                    col.includes('total') ||
                                                                    col.includes('penalty') ||
                                                                    col.includes('weight') ||
                                                                    col.includes('_mt')
                                                                );
                                                                return (
                                                                    <td
                                                                        key={col}
                                                                        className={`whitespace-nowrap px-4 py-2.5 ${
                                                                            isNumber ? 'text-right' : ''
                                                                        }`}
                                                                    >
                                                                        {val === null || val === undefined
                                                                            ? '-'
                                                                            : isCurrency
                                                                              ? Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                                                                              : isNumber
                                                                                ? Number(val).toLocaleString()
                                                                                : typeof val === 'boolean'
                                                                                  ? val ? 'Yes' : 'No'
                                                                                  : String(val)}
                                                                    </td>
                                                                );
                                                            })}
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                            {tableRows.length > 100 && (
                                                <div className="border-t px-4 py-2 text-center text-xs text-muted-foreground">
                                                    Showing first 100 of {tableRows.length} records. Export CSV for full data.
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                            <FileSpreadsheet className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                            <p>No records found for the selected filters.</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {/* Initial state */}
                        {data === null && !loading && !error && (
                            <Card>
                                <CardContent className="py-12">
                                    <div className="text-center text-sm text-muted-foreground">
                                        <BarChart3 className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                        <p className="mb-1 font-medium text-foreground">
                                            Select a report and click Generate
                                        </p>
                                        <p>
                                            Choose a report type from the sidebar, optionally filter by siding and date range, then click Generate to view results.
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Loading state */}
                        {loading && (
                            <Card>
                                <CardContent className="py-12">
                                    <div className="flex flex-col items-center gap-3 text-center text-sm text-muted-foreground">
                                        <Loader2 className="h-8 w-8 animate-spin" />
                                        <p>Generating report...</p>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
