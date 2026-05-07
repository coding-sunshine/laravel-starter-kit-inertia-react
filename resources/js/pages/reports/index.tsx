import Heading from '@/components/heading';
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
// axios removed in Inertia v3 — use native fetch with XSRF-TOKEN cookie
function csrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}
import {
    CalendarRange,
    ChevronDown,
    Download,
    FileSpreadsheet,
    Loader2,
} from 'lucide-react';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
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

interface ReportGridMeta {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
}

const RAKE_MANAGEMENT_REPORTS: string[] = [
    'siding_coal_receipt',
    'rake_indent',
    'txr',
    'unfit_wagon',
    'wagon_loading',
    'weighment',
    'loader_vs_weighment',
    'rr_summary',
    'penalty_register',
];

const RAKE_NUMBER_FILTER_REPORTS = new Set([
    'txr',
    'unfit_wagon',
    'wagon_loading',
    'weighment',
    'loader_vs_weighment',
    'rr_summary',
    'penalty_register',
]);

/** Placeholder labels until backend report keys exist (sidebar only). */
const COAL_LOGESTIC_CORE_REPORT_LABELS: string[] = [
    'Rail Dispatch DPR',
    'Penalty Report',
    'Overloading Report',
    'Underloading',
    'Loader Performance',
    'Siding Dispatch',
    'PowerPlant Dispatch',
    'Operator Performance',
];

const COAL_LOGESTIC_ADVANCE_REPORT_LABELS: string[] = [
    'Weighment Analysis',
    'Loader vs Weighment',
    'Weighment Summary',
    'RR Charges',
    'RR Wagon Details',
    'Weighment vs RR',
    'Auto DPR Report',
];

const REPORTS_GRID_PER_PAGE = 60;

/** Collapsible triggers for sections without a selected report (Core / Advance, or Reports fallback). */
const SECTION_TRIGGER_NEUTRAL =
    'flex w-full items-center justify-between gap-2 rounded-md border border-border bg-muted/60 px-2 py-2 text-left text-xs font-semibold uppercase text-foreground shadow-sm hover:bg-muted hover:text-foreground dark:bg-muted/45 dark:hover:bg-muted/80';

function formatCurrency(n: number): string {
    if (n >= 100000) return `₹${(n / 100000).toFixed(1)}L`;
    if (n >= 1000) return `₹${(n / 1000).toFixed(1)}K`;
    return `₹${n.toFixed(0)}`;
}

/** Render the summary section for delegated reports. */
function ReportSummary({ data, reportKey }: { data: ReportData; reportKey: string }) {
    void reportKey;
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

/** Stable-enough React key for a paginated report row. */
function reportTableRowKey(
    row: Record<string, unknown>,
    columns: string[],
    page: number,
    index: number,
): string {
    const digest = columns.map((c) => `${c}:${String(row[c] ?? '')}`).join('|');

    return `${page}:${index}:${digest}`;
}

function flattenReportPageData(rows: ReportData): Record<string, unknown>[] {
    if (!rows || rows.length === 0) {
        return [];
    }
    const first = rows[0];
    if (first && typeof first === 'object') {
        const inner = first as Record<string, unknown>;
        for (const k of ['transactions', 'rakes', 'indents', 'by_month']) {
            if (Array.isArray(inner[k])) {
                return inner[k] as Record<string, unknown>[];
            }
        }
    }
    return rows;
}

function toLocalDateInput(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

export default function ReportsIndex({ reports, sidings }: Props) {
    const [activeKey, setActiveKey] = useState<string>(Object.keys(reports)[0] ?? 'siding_coal_receipt');
    const [sidingId, setSidingId] = useState<string>('');
    const [dateFrom, setDateFrom] = useState<string>(() => {
        const now = new Date();
        return toLocalDateInput(new Date(now.getFullYear(), now.getMonth(), 1));
    });
    const [dateTo, setDateTo] = useState<string>(() => toLocalDateInput(new Date()));
    const [rakeNumber, setRakeNumber] = useState<string>('');
    const [loader, setLoader] = useState<string>('');
    const [loading, setLoading] = useState(false);
    const [data, setData] = useState<ReportData | null>(null);
    const [paginationMeta, setPaginationMeta] = useState<ReportGridMeta | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [coreSectionOpen, setCoreSectionOpen] = useState(true);
    const [advanceSectionOpen, setAdvanceSectionOpen] = useState(true);
    const [reportsSectionOpen, setReportsSectionOpen] = useState(true);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Reports', href: '/reports' },
    ];

    const activeReport = reports[activeKey];
    const activeReportIsInOperationalSection = useMemo(
        () => RAKE_MANAGEMENT_REPORTS.some((k) => k === activeKey && Boolean(reports[k])),
        [activeKey, reports],
    );
    const showsRakeNumberFilter = RAKE_NUMBER_FILTER_REPORTS.has(activeKey);
    const showsLoaderFilter = activeKey === 'wagon_loading';
    const requestPayload = useMemo(
        () => ({
            key: activeKey,
            siding_id: sidingId || undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
            rake_number: showsRakeNumberFilter ? rakeNumber || undefined : undefined,
            loader: showsLoaderFilter ? loader || undefined : undefined,
        }),
        [activeKey, sidingId, dateFrom, dateTo, rakeNumber, loader, showsRakeNumberFilter, showsLoaderFilter],
    );

    const loadReportPage = useCallback(
        async (page: number) => {
            setLoading(true);
            setError(null);
            setData(null);
            setPaginationMeta(null);
            try {
                const resp = await fetch('/reports/generate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': csrfToken() },
                    body: JSON.stringify({
                        ...requestPayload,
                        page,
                        per_page: REPORTS_GRID_PER_PAGE,
                    }),
                });
                if (!resp.ok) {
                    throw new Error('Failed to generate report');
                }
                const json = (await resp.json()) as {
                    data?: ReportData;
                    meta?: ReportGridMeta;
                };
                const rows = json.data ?? [];
                setData(rows);
                if (json.meta) {
                    setPaginationMeta(json.meta);
                } else {
                    setPaginationMeta({
                        current_page: 1,
                        per_page: rows.length,
                        total: rows.length,
                        last_page: 1,
                    });
                }
            } catch (e: unknown) {
                const msg = e instanceof Error ? e.message : 'Failed to generate report';
                setError(msg);
            } finally {
                setLoading(false);
            }
        },
        [requestPayload],
    );

    const generate = useCallback(() => {
        void loadReportPage(1);
    }, [loadReportPage]);

    const downloadXlsx = useCallback(async () => {
        try {
            const resp = await fetch('/reports/generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': csrfToken() },
                body: JSON.stringify({ ...requestPayload, export_xlsx: true }),
            });
            if (!resp.ok) throw new Error('XLSX download failed');
            const blob = await resp.blob();
            const url = window.URL.createObjectURL(blob);
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
    }, [requestPayload, activeReport, activeKey]);

    const tableRows = useMemo<Record<string, unknown>[]>(() => {
        if (!data || data.length === 0) {
            return [];
        }
        return flattenReportPageData(data);
    }, [data]);

    const tableColumns = useMemo(() => {
        if (tableRows.length === 0) {
            return [];
        }
        return Object.keys(tableRows[0]).filter(
            (k) => typeof tableRows[0][k] !== 'object' || tableRows[0][k] === null,
        );
    }, [tableRows]);

    const resultsDescription = useMemo(() => {
        if (!paginationMeta) {
            return '';
        }
        if (paginationMeta.total === 0) {
            return 'No records for the selected filters.';
        }
        const start = (paginationMeta.current_page - 1) * paginationMeta.per_page + 1;
        const end = Math.min(
            paginationMeta.current_page * paginationMeta.per_page,
            paginationMeta.total,
        );

        return `Showing ${start.toLocaleString()}–${end.toLocaleString()} of ${paginationMeta.total.toLocaleString()} records (${paginationMeta.per_page} per page).`;
    }, [paginationMeta]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reports" />
            <div className="space-y-6">
                <Heading
                    title="Reports"
                    description="Generate and export operational reports"
                />

                <div className="grid gap-6 lg:grid-cols-[240px_1fr]">
                    {/* Sidebar: Report Types */}
                    <Card className="h-fit">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-foreground">Report Types</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 p-3 pt-0">
                            <Collapsible open={coreSectionOpen} onOpenChange={setCoreSectionOpen}>
                                <CollapsibleTrigger
                                    className={SECTION_TRIGGER_NEUTRAL}
                                    data-pan="reports-sidebar-section-core-toggle"
                                    type="button"
                                >
                                    Coal Logestic Core Reports
                                    <ChevronDown
                                        className={cn(
                                            'h-4 w-4 shrink-0 text-foreground/70 transition-transform dark:text-foreground/65',
                                            coreSectionOpen && 'rotate-180',
                                        )}
                                    />
                                </CollapsibleTrigger>
                                <CollapsibleContent className="pt-1">
                                    <ul className="space-y-0.5">
                                        {COAL_LOGESTIC_CORE_REPORT_LABELS.map((label) => (
                                            <li key={label}>
                                                <span
                                                    className="block cursor-not-allowed rounded-md px-2 py-1.5 text-sm text-foreground/80 dark:text-foreground/75"
                                                    title="Coming soon"
                                                >
                                                    {label}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </CollapsibleContent>
                            </Collapsible>

                            <Collapsible open={advanceSectionOpen} onOpenChange={setAdvanceSectionOpen}>
                                <CollapsibleTrigger
                                    className={SECTION_TRIGGER_NEUTRAL}
                                    data-pan="reports-sidebar-section-advance-toggle"
                                    type="button"
                                >
                                    Coal Logestic Advance Reports
                                    <ChevronDown
                                        className={cn(
                                            'h-4 w-4 shrink-0 text-foreground/70 transition-transform dark:text-foreground/65',
                                            advanceSectionOpen && 'rotate-180',
                                        )}
                                    />
                                </CollapsibleTrigger>
                                <CollapsibleContent className="pt-1">
                                    <ul className="space-y-0.5">
                                        {COAL_LOGESTIC_ADVANCE_REPORT_LABELS.map((label) => (
                                            <li key={label}>
                                                <span
                                                    className="block cursor-not-allowed rounded-md px-2 py-1.5 text-sm text-foreground/80 dark:text-foreground/75"
                                                    title="Coming soon"
                                                >
                                                    {label}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </CollapsibleContent>
                            </Collapsible>

                            <Collapsible open={reportsSectionOpen} onOpenChange={setReportsSectionOpen}>
                                <CollapsibleTrigger
                                    className={cn(
                                        'flex w-full items-center justify-between gap-2 rounded-md border px-2 py-2 text-left text-xs font-semibold uppercase shadow-sm transition-colors',
                                        activeReportIsInOperationalSection
                                            ? 'border-primary/55 bg-primary/15 text-primary hover:bg-primary/22 dark:bg-primary/22 dark:hover:bg-primary/30'
                                            : SECTION_TRIGGER_NEUTRAL,
                                        activeReportIsInOperationalSection &&
                                            !reportsSectionOpen &&
                                            'ring-2 ring-primary/35 ring-offset-2 ring-offset-background dark:ring-offset-background',
                                    )}
                                    data-pan="reports-sidebar-section-reports-toggle"
                                    type="button"
                                >
                                    Reports
                                    <ChevronDown
                                        className={cn(
                                            'h-4 w-4 shrink-0 transition-transform',
                                            activeReportIsInOperationalSection
                                                ? 'text-primary'
                                                : 'text-foreground/70 dark:text-foreground/65',
                                            reportsSectionOpen && 'rotate-180',
                                        )}
                                    />
                                </CollapsibleTrigger>
                                <CollapsibleContent className="pt-1">
                                    <div className="space-y-0.5">
                                        {RAKE_MANAGEMENT_REPORTS.filter((k) => reports[k]).map((k) => (
                                            <button
                                                key={k}
                                                onClick={() => {
                                                    setActiveKey(k);
                                                    setData(null);
                                                    setPaginationMeta(null);
                                                    setError(null);
                                                    if (!RAKE_NUMBER_FILTER_REPORTS.has(k)) {
                                                        setRakeNumber('');
                                                    }
                                                    if (k !== 'wagon_loading') {
                                                        setLoader('');
                                                    }
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
                                </CollapsibleContent>
                            </Collapsible>
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
                                    {showsRakeNumberFilter && (
                                        <div className="grid gap-1.5">
                                            <label className="text-xs font-medium">Rake Number</label>
                                            <input
                                                type="text"
                                                value={rakeNumber}
                                                onChange={(e) => setRakeNumber(e.target.value)}
                                                placeholder="Search rake number"
                                                className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            />
                                        </div>
                                    )}
                                    {showsLoaderFilter && (
                                        <div className="grid gap-1.5">
                                            <label className="text-xs font-medium">Loader</label>
                                            <input
                                                type="text"
                                                value={loader}
                                                onChange={(e) => setLoader(e.target.value)}
                                                placeholder="Loader id, name, or code"
                                                className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            />
                                        </div>
                                    )}
                                    <Button
                                        onClick={generate}
                                        disabled={loading}
                                        data-pan="report-generate"
                                    >
                                        {loading ? (
                                            <Loader2 className="mr-1.5 h-4 w-4 animate-spin" />
                                        ) : (
                                            <FileSpreadsheet className="mr-1.5 h-4 w-4" />
                                        )}
                                        Generate
                                    </Button>
                                    {data !== null && (paginationMeta?.total ?? 0) > 0 && (
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
                                    {paginationMeta !== null && (
                                        <CardDescription>{resultsDescription}</CardDescription>
                                    )}
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {/* Summary cards for delegated reports */}
                                    <ReportSummary data={data} reportKey={activeKey} />

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
                                                    {tableRows.map((row, idx) => (
                                                        <tr
                                                            key={reportTableRowKey(
                                                                row,
                                                                tableColumns,
                                                                paginationMeta?.current_page ?? 1,
                                                                idx,
                                                            )}
                                                            className="border-b last:border-0 hover:bg-muted/30"
                                                        >
                                                            {tableColumns.map((col) => {
                                                                const val = row[col];
                                                                const isNumber = typeof val === 'number';
                                                                const isCurrency =
                                                                    isNumber &&
                                                                    (col.includes('amount') ||
                                                                        col.includes('total') ||
                                                                        col.includes('penalty') ||
                                                                        col.includes('weight') ||
                                                                        col.includes('_mt') ||
                                                                        col === 'Quantity Received (MT)');
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
                                            {paginationMeta && paginationMeta.last_page > 1 && (
                                                <div className="flex flex-wrap items-center justify-center gap-2 border-t px-4 py-3">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={loading || paginationMeta.current_page <= 1}
                                                        onClick={() => {
                                                            void loadReportPage(paginationMeta.current_page - 1);
                                                        }}
                                                        data-pan="report-pagination-prev"
                                                    >
                                                        Previous
                                                    </Button>
                                                    <span className="text-sm text-muted-foreground tabular-nums">
                                                        Page {paginationMeta.current_page} of{' '}
                                                        {paginationMeta.last_page}
                                                    </span>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={
                                                            loading ||
                                                            paginationMeta.current_page >= paginationMeta.last_page
                                                        }
                                                        onClick={() => {
                                                            void loadReportPage(paginationMeta.current_page + 1);
                                                        }}
                                                        data-pan="report-pagination-next"
                                                    >
                                                        Next
                                                    </Button>
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
                                        <FileSpreadsheet className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
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
