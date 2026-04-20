import { DataTable } from '@/components/data-table/data-table';
import type { DataTableResponse } from 'laravel-data-table';
import Heading from '@/components/heading';
import type { WorkflowSteps } from '@/components/rake-workflow-progress';
import { RakeWorkflowProgressCell } from '@/components/rake-workflow-progress';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { withReturnTo } from '@/lib/safe-return-url';
import { create as indentsCreate } from '@/routes/indents';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus, Train } from 'lucide-react';
import { useCallback, useMemo } from 'react';
import { Button } from '@/components/ui/button';

interface RakeRow {
    id: number;
    rake_number: string;
    rake_serial_number: string | null;
    rake_type: string | null;
    wagon_count: number | null;
    state: string | null;
    loading_date: string | null;
    placement_time: string | null;
    dispatch_time: string | null;
    siding_code: string | null;
    siding_name: string | null;
    destination: string | null;
    data_source: string | null;
    rr_document_id: number | null;
    pdf_download_url: string | null;
    workflow_has_pending: boolean;
    workflow_steps: WorkflowSteps;
}

interface Props {
    tableData: DataTableResponse<RakeRow>;
}

export default function RakesIndex({ tableData }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Rakes', href: '/rakes' },
    ];

    /** Rake #: exact match only (client expects the number they type to be the whole ID). */
    const tableDataWithExactRakeNumber = useMemo(
        () => ({
            ...tableData,
            columns: tableData.columns.map((col) =>
                col.id === 'rake_number'
                    ? { ...col, filterTextOperator: 'eq' as const }
                    : col,
            ),
        }),
        [tableData],
    );

    const openRakeRow = useCallback((row: RakeRow) => {
        const isFromRr =
            row.data_source === 'historical_rr' && row.rr_document_id != null;
        if (isFromRr) {
            router.visit(`/railway-receipts/${row.rr_document_id}`);
            return;
        }
        const returnPath =
            typeof window !== 'undefined'
                ? `${window.location.pathname}${window.location.search}`
                : '/rakes';
        router.visit(withReturnTo(`/rakes/${row.id}`, returnPath));
    }, []);

    const formatRakeSequence = useCallback((value: string, row: RakeRow): string => {
        const normalized = value.trim();
        if (normalized === '') {
            return normalized;
        }

        const sidingValue = `${row.siding_code ?? ''} ${row.siding_name ?? ''}`.toLowerCase();
        let prefix = '';
        if (sidingValue.includes('pakur')) {
            prefix = 'P';
        } else if (sidingValue.includes('dumka')) {
            prefix = 'D';
        } else if (sidingValue.includes('kurwa')) {
            prefix = 'K';
        }

        if (prefix === '') {
            return normalized;
        }

        return normalized.startsWith(`${prefix}-`) ? normalized : `${prefix}-${normalized}`;
    }, []);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rakes" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Railway Rakes"
                        description="Manage railway rakes and wagons for the RRMCS system"
                    />
                    <Button asChild data-pan="rakes-create-rake-button">
                        <Link href={indentsCreate.url()} className="inline-flex items-center gap-2">
                            <Plus className="size-4" />
                            Create rake
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Train className="h-5 w-5" />
                            Rakes
                        </CardTitle>
                        <CardDescription>
                            Current-month railway rakes you have access to (use filters to change date range)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable<RakeRow>
                            tableData={tableDataWithExactRakeNumber}
                            tableName="rakes"
                            renderHeader={{ rake_number: 'Rake Seq' }}
                            onRowClick={openRakeRow}
                            rowClassName={(row) =>
                                row.workflow_has_pending
                                    ? 'bg-red-100/80 dark:bg-red-950/50'
                                    : ''
                            }
                            actions={[
                                {
                                    label: 'View',
                                    onClick: (row) => {
                                        openRakeRow(row);
                                    },
                                },
                                {
                                    label: 'Download PDF',
                                    visible: (row) =>
                                        row.pdf_download_url != null,
                                    onClick: (row) => {
                                        if (row.pdf_download_url) {
                                            window.open(
                                                row.pdf_download_url,
                                                '_blank',
                                                'noopener,noreferrer',
                                            );
                                        }
                                    },
                                },
                            ]}
                            renderCell={(columnId, value, row) => {
                                if (columnId === 'rake_number') {
                                    const raw = String(value ?? row.rake_number);
                                    return formatRakeSequence(raw, row);
                                }

                                if (columnId === 'rake_serial_number') {
                                    if (row.rake_serial_number != null && row.rake_serial_number !== '') {
                                        return row.rake_serial_number;
                                    }

                                    if (row.rake_number !== '') {
                                        return (
                                            <span className="text-amber-600 dark:text-amber-400">
                                                {row.rake_number}
                                            </span>
                                        );
                                    }

                                    return '—';
                                }
                                if (columnId === 'siding_code') {
                                    return row.siding_code && row.siding_name
                                        ? `${row.siding_code} (${row.siding_name})`
                                        : '—';
                                }
                                if (columnId === 'destination') {
                                    return row.destination ? row.destination : '—';
                                }
                                if (columnId === 'progress') {
                                    return (
                                        <RakeWorkflowProgressCell
                                            steps={row.workflow_steps}
                                        />
                                    );
                                }
                                if (columnId === 'placement_time') {
                                    return row.placement_time
                                        ? new Date(row.placement_time).toLocaleString()
                                        : '—';
                                }
                                if (columnId === 'loading_date') {
                                    return row.loading_date
                                        ? new Date(row.loading_date).toLocaleDateString()
                                        : '—';
                                }
                                return undefined;
                            }}
                            options={{
                                exports: false,
                                quickViews: false,
                                customQuickViews: false,
                                filtersLayout: 'inline',
                            }}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
