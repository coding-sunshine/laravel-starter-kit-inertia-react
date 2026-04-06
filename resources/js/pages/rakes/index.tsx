import { DataTable } from 'laravel-data-table';
import type { DataTableResponse } from 'laravel-data-table';
import Heading from '@/components/heading';
import type { WorkflowSteps } from '@/components/rake-workflow-progress';
import { RakeWorkflowProgressCell } from '@/components/rake-workflow-progress';
import { RrmcsGuidance } from '@/components/rrmcs-guidance';
import { StatusPill } from '@/components/status-pill';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { create as indentsCreate } from '@/routes/indents';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus, Train } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface RakeRow {
    id: number;
    rake_number: string;
    rake_type: string | null;
    wagon_count: number | null;
    state: string | null;
    loading_date: string | null;
    placement_time: string | null;
    dispatch_time: string | null;
    siding_code: string | null;
    siding_name: string | null;
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
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Rakes', href: '/rakes' },
    ];

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
                            tableData={tableData}
                            tableName="rakes"
                            rowClassName={(row) =>
                                row.workflow_has_pending
                                    ? 'bg-red-100/80 dark:bg-red-950/50'
                                    : ''
                            }
                            actions={[
                                {
                                    label: 'View',
                                    onClick: (row) => {
                                        const isFromRr =
                                            row.data_source === 'historical_rr' &&
                                            row.rr_document_id != null;
                                        router.visit(
                                            isFromRr
                                                ? `/railway-receipts/${row.rr_document_id}`
                                                : `/rakes/${row.id}`,
                                        );
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
                                if (columnId === 'siding_code') {
                                    return row.siding_code && row.siding_name
                                        ? `${row.siding_code} (${row.siding_name})`
                                        : '—';
                                }
                                if (columnId === 'state') {
                                    return <StatusPill status={row.state} />;
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
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
