import { DataTable } from 'laravel-data-table';
import type { DataTableResponse } from 'laravel-data-table';
import { GlossaryTerm } from '@/components/glossary-term';
import Heading from '@/components/heading';
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
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Train, CheckCircle2, Clock, AlertCircle } from 'lucide-react';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface WorkflowSteps {
    txr_done: boolean;
    wagon_loading_done: boolean;
    guard_done: boolean;
    weighment_done: boolean;
    rr_done: boolean;
}

interface RakeRow {
    id: number;
    rake_number: string;
    rake_type: string | null;
    wagon_count: number | null;
    state: string | null;
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

const STEP_LABELS: Array<{ key: keyof WorkflowSteps; label: string }> = [
    { key: 'txr_done', label: 'TXR' },
    { key: 'wagon_loading_done', label: 'Wagon loading' },
    { key: 'guard_done', label: 'Guard inspection' },
    { key: 'weighment_done', label: 'Weighment' },
    { key: 'rr_done', label: 'RR document' },
];

function ProgressCell({ row }: { row: RakeRow }) {
    const steps = row.workflow_steps;
    const allDone = STEP_LABELS.every(({ key }) => steps[key]);
    const tooltipContent = (
        <div className="space-y-1 text-left">
            {STEP_LABELS.map(({ key, label }) => (
                <div key={key} className="flex items-center gap-2">
                    {steps[key] ? (
                        <CheckCircle2 className="size-3.5 shrink-0 text-green-500" />
                    ) : (
                        <Clock className="size-3.5 shrink-0 text-amber-500" />
                    )}
                    <span>{label}: {steps[key] ? 'Done' : 'Pending'}</span>
                </div>
            ))}
        </div>
    );
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <button
                    type="button"
                    className="inline-flex cursor-default items-center justify-center rounded p-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    onClick={(e) => e.preventDefault()}
                >
                    {allDone ? (
                        <CheckCircle2 className="size-5 text-green-600 dark:text-green-400" />
                    ) : (
                        <AlertCircle className="size-5 text-amber-600 dark:text-amber-400" />
                    )}
                </button>
            </TooltipTrigger>
            <TooltipContent side="left" className="max-w-xs">
                {tooltipContent}
            </TooltipContent>
        </Tooltip>
    );
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
                <Heading
                    title="Railway Rakes"
                    description="Manage railway rakes and wagons for the RRMCS system"
                />

                <RrmcsGuidance
                    title="What this section is for"
                    before="Rake status and 3-hour loading window tracked in Excel and stopwatch; demurrage and penalties found only after Railway Receipt (RR) arrives."
                    after="Rake list with live demurrage countdown; alerts at 60 min (amber), 30 min (red), 0 min (critical). Overload detection during weighment—24+ hours before RR."
                />

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Train className="h-5 w-5" />
                            Rakes
                        </CardTitle>
                        <CardDescription>
                            All railway rakes you have access to
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
                                    return <ProgressCell row={row} />;
                                }
                                if (columnId === 'placement_time') {
                                    return row.placement_time
                                        ? new Date(row.placement_time).toLocaleString()
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
