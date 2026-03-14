import { DataTable } from '@/components/data-table/data-table';
import type { DataTableResponse } from '@/components/data-table/types';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, BarChart3, FileSearch } from 'lucide-react';

interface ChartDataPoint {
    name: string;
    value: number;
}

interface SharedDevice {
    fingerprint_masked: string;
    user_count: number;
    login_count: number;
    last_seen: string;
}

interface Props {
    reportType: string;
    reportTitle: string;
    chartData: ChartDataPoint[];
    chartType: 'bar' | 'line';
    tableData?: DataTableResponse<Record<string, unknown>> | null;
    searchableColumns: string[];
    sharedDevices?: SharedDevice[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
    { title: 'Report', href: '#' },
];

export default function ReportShowPage({
    reportType,
    reportTitle,
    chartData,
    chartType,
    tableData,
    searchableColumns = [],
    sharedDevices,
}: Props) {
    const crumbs: BreadcrumbItem[] = [
        { title: 'Reports', href: '/reports' },
        { title: reportTitle, href: `/reports/${reportType}` },
    ];

    return (
        <AppSidebarLayout breadcrumbs={crumbs}>
            <Head title={reportTitle} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center gap-3">
                    <Link
                        href="/reports"
                        className="text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-5" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{reportTitle}</h1>
                        {tableData && (
                            <p className="text-sm text-muted-foreground">
                                {tableData.meta.total} records
                            </p>
                        )}
                    </div>
                </div>

                {chartData.length > 0 && (
                    <div className="rounded-lg border bg-card p-4">
                        <div className="mb-3 flex items-center gap-2">
                            <BarChart3 className="size-4 text-muted-foreground" />
                            <h2 className="text-sm font-medium">Summary</h2>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            {chartData.map((point) => (
                                <div
                                    key={point.name}
                                    className="flex min-w-[100px] flex-col rounded-md bg-muted/50 p-3"
                                >
                                    <span className="text-xs text-muted-foreground">
                                        {point.name}
                                    </span>
                                    <span className="text-lg font-semibold">
                                        {typeof point.value === 'number'
                                            ? point.value.toLocaleString()
                                            : point.value}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {reportType === 'same-device' && sharedDevices !== undefined ? (
                    <div className="rounded-lg border bg-card">
                        <div className="border-b p-4">
                            <h2 className="font-medium">
                                Shared Device Fingerprints (last 30 days)
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {sharedDevices.length} suspicious device(s) detected
                            </p>
                        </div>
                        {sharedDevices.length === 0 ? (
                            <div className="flex flex-col items-center justify-center gap-3 py-12 text-center">
                                <FileSearch className="size-8 text-muted-foreground" />
                                <p className="font-medium">No shared devices detected</p>
                                <p className="text-sm text-muted-foreground">
                                    No device fingerprints are shared by multiple users.
                                </p>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {sharedDevices.map((device, idx) => (
                                    <div
                                        key={idx}
                                        className="flex items-center justify-between p-4"
                                    >
                                        <div>
                                            <p className="font-mono text-sm font-medium">
                                                {device.fingerprint_masked}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Last seen: {device.last_seen}
                                            </p>
                                        </div>
                                        <div className="flex gap-4 text-right text-sm">
                                            <div>
                                                <p className="font-semibold text-destructive">
                                                    {device.user_count}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    users
                                                </p>
                                            </div>
                                            <div>
                                                <p className="font-semibold">{device.login_count}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    logins
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                ) : tableData !== null && tableData !== undefined ? (
                    <DataTable
                        tableData={tableData}
                        tableName={`report-${reportType}`}
                        searchableColumns={searchableColumns}
                        debounceMs={300}
                        partialReloadKey="tableData"
                        emptyState={
                            <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                                <div className="rounded-full bg-muted p-4">
                                    <FileSearch className="size-8 text-muted-foreground" />
                                </div>
                                <div>
                                    <p className="font-medium">No data found</p>
                                    <p className="text-sm text-muted-foreground">
                                        Try adjusting your search or filters.
                                    </p>
                                </div>
                            </div>
                        }
                        options={{
                            columnVisibility: true,
                            columnOrdering: true,
                            columnResizing: true,
                            exports: true,
                            filters: true,
                            density: true,
                            copyCell: true,
                        }}
                        translations={{
                            noData: 'No data',
                            search: 'Search...',
                            clearAllFilters: 'Clear all filters',
                            density: 'Row density',
                            selectAllMatching: (count) => `Select all ${count} records`,
                        }}
                    />
                ) : null}
            </div>
        </AppSidebarLayout>
    );
}
