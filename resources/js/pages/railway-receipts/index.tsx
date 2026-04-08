import { DataTable } from '@/components/data-table/data-table';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { useCan } from '@/hooks/use-can';
import { Head, Link, router, usePage } from '@inertiajs/react';
import type { DataTableResponse } from 'laravel-data-table';
import { ExternalLink, TrainFront, Upload } from 'lucide-react';
import { type ReactNode, useCallback, useMemo, useRef, useState } from 'react';

export interface RailwayReceiptsRakeRow {
    id: number;
    rake_number: string;
    loading_date: string | null;
    siding_id: number | null;
    siding_code: string | null;
    siding_name: string | null;
    destination: string | null;
    has_diversion: boolean;
    rr_document_id: number | null;
    rr_number: string | null;
    rr_received_date: string | null;
    rr_weight_mt: string | null;
    document_status: string | null;
    has_discrepancy: boolean | null;
    discrepancy_details: string | null;
    fnr: string | null;
    from_station_code: string | null;
    to_station_code: string | null;
    freight_total: string | null;
    distance_km: string | null;
    commodity_code: string | null;
    commodity_description: string | null;
    invoice_number: string | null;
    invoice_date: string | null;
    rate: string | null;
    document_class: string | null;
}

export interface RailwayReceiptsStandaloneRow {
    id: number;
    rr_number: string;
    rr_received_date: string;
    rr_weight_mt: string | null;
}

interface Props {
    tableData: DataTableResponse<RailwayReceiptsRakeRow | RailwayReceiptsStandaloneRow>;
    activeTab?: 'rakes' | 'standalone';
    can_upload_rr?: boolean;
    showStandaloneTab?: boolean;
}

function rakeRowClassName(row: RailwayReceiptsRakeRow): string {
    if (row.has_diversion) {
        return 'bg-amber-200/70 dark:bg-amber-950/45';
    }
    if (row.rr_document_id == null) {
        return 'bg-red-100/80 dark:bg-red-950/50';
    }
    return 'bg-green-100/80 dark:bg-green-950/40';
}

function rakeMetaLine(row: RailwayReceiptsRakeRow): string {
    const parts: string[] = [];
    if (row.siding_code && row.siding_name) {
        parts.push(`${row.siding_code} (${row.siding_name})`);
    } else if (row.siding_name) {
        parts.push(row.siding_name);
    } else if (row.siding_code) {
        parts.push(row.siding_code);
    }
    if (row.loading_date) {
        parts.push(`Loading ${new Date(row.loading_date).toLocaleDateString()}`);
    }
    if (row.destination) {
        parts.push(row.destination);
    }
    return parts.length > 0 ? parts.join(' · ') : '—';
}

function RrHubDetailRows({ row }: { row: RailwayReceiptsRakeRow }): ReactNode {
    if (row.rr_document_id == null) {
        return null;
    }

    const entries: { label: string; value: string }[] = [
        { label: 'RR number', value: row.rr_number ?? '—' },
        { label: 'Received date', value: row.rr_received_date ? new Date(row.rr_received_date).toLocaleDateString() : '—' },
        { label: 'Weight (MT)', value: row.rr_weight_mt ?? '—' },
        { label: 'Document status', value: row.document_status ?? '—' },
        {
            label: 'Discrepancy',
            value: row.has_discrepancy === true ? 'Yes' : row.has_discrepancy === false ? 'No' : '—',
        },
    ];

    if (row.discrepancy_details) {
        entries.push({ label: 'Discrepancy details', value: row.discrepancy_details });
    }
    if (row.fnr) {
        entries.push({ label: 'FNR', value: row.fnr });
    }
    if (row.from_station_code) {
        entries.push({ label: 'From station', value: row.from_station_code });
    }
    if (row.to_station_code) {
        entries.push({ label: 'To station', value: row.to_station_code });
    }
    if (row.freight_total) {
        entries.push({ label: 'Freight total', value: row.freight_total });
    }
    if (row.distance_km) {
        entries.push({ label: 'Distance (km)', value: row.distance_km });
    }
    if (row.commodity_code) {
        entries.push({ label: 'Commodity code', value: row.commodity_code });
    }
    if (row.commodity_description) {
        entries.push({ label: 'Commodity', value: row.commodity_description });
    }
    if (row.invoice_number) {
        entries.push({ label: 'Invoice number', value: row.invoice_number });
    }
    if (row.invoice_date) {
        entries.push({ label: 'Invoice date', value: new Date(row.invoice_date).toLocaleDateString() });
    }
    if (row.rate) {
        entries.push({ label: 'Rate', value: row.rate });
    }
    if (row.document_class) {
        entries.push({ label: 'Class', value: row.document_class });
    }

    return (
        <dl className="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
            {entries.map(({ label, value }) => (
                <div key={label} className="sm:col-span-1">
                    <dt className="text-muted-foreground font-medium">{label}</dt>
                    <dd className="mt-0.5 whitespace-pre-wrap break-words">{value}</dd>
                </div>
            ))}
        </dl>
    );
}

export default function RailwayReceiptsIndex({
    tableData,
    activeTab = 'rakes',
    can_upload_rr = false,
    showStandaloneTab = false,
}: Props) {
    const hubFileRef = useRef<HTMLInputElement>(null);
    const [hubRow, setHubRow] = useState<RailwayReceiptsRakeRow | null>(null);
    const [hubFile, setHubFile] = useState<File | null>(null);
    const [uploading, setUploading] = useState(false);

    const {
        props: { errors },
    } = usePage<{ errors: Record<string, string | undefined> }>();
    const canUploadRr = useCan('sections.railway_receipts.upload');
    const canUpload = can_upload_rr && canUploadRr;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Railway Receipts', href: '/railway-receipts' },
    ];

    const switchTab = useCallback((tab: 'rakes' | 'standalone') => {
        router.get('/railway-receipts', { tab }, { preserveScroll: true });
    }, []);

    const closeHub = useCallback(() => {
        setHubRow(null);
        setHubFile(null);
        if (hubFileRef.current) {
            hubFileRef.current.value = '';
        }
    }, []);

    const submitHubUpload = useCallback(() => {
        if (!canUpload || !hubRow || !hubFile) {
            return;
        }
        setUploading(true);
        const formData = new FormData();
        formData.append('pdf', hubFile);
        formData.append('rake_id', String(hubRow.id));
        if (hubRow.siding_id != null) {
            formData.append('siding_id', String(hubRow.siding_id));
        }
        router.post('/railway-receipts/upload', formData, {
            forceFormData: true,
            onFinish: () => {
                setUploading(false);
            },
            onSuccess: () => {
                closeHub();
            },
        });
    }, [canUpload, closeHub, hubFile, hubRow]);

    const rakesTableData = useMemo((): DataTableResponse<RailwayReceiptsRakeRow> => {
        return {
            ...tableData,
            columns: tableData.columns.map((col) =>
                col.id === 'rake_number' ? { ...col, filterTextOperator: 'eq' as const } : col,
            ),
        } as DataTableResponse<RailwayReceiptsRakeRow>;
    }, [tableData]);

    const filtersEmpty =
        tableData.meta.filters == null ||
        (typeof tableData.meta.filters === 'object' && Object.keys(tableData.meta.filters).length === 0);

    const hubMeta = hubRow ? rakeMetaLine(hubRow) : '';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Railway Receipts" />
            <div className="space-y-6">
                <Heading title="Railway Receipts" description="RR documents by rake and standalone uploads" />
                {errors?.pdf && activeTab === 'rakes' ? <p className="text-sm text-destructive">{errors.pdf}</p> : null}

                <div className="flex flex-wrap gap-1 border-b border-border pb-px">
                    <button
                        type="button"
                        className={cn(
                            '-mb-px border-b-2 px-3 py-2 text-sm font-medium',
                            activeTab === 'rakes'
                                ? 'border-primary text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        )}
                        onClick={() => switchTab('rakes')}
                        data-pan="railway-receipts-tab-rakes"
                    >
                        <span className="inline-flex items-center gap-2">
                            <TrainFront className="h-4 w-4" />
                            With rake
                        </span>
                    </button>
                    {showStandaloneTab ? (
                        <button
                            type="button"
                            className={cn(
                                '-mb-px border-b-2 px-3 py-2 text-sm font-medium',
                                activeTab === 'standalone'
                                    ? 'border-primary text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                            onClick={() => switchTab('standalone')}
                            data-pan="railway-receipts-tab-standalone"
                        >
                            Without rake
                        </button>
                    ) : null}
                </div>

                {activeTab === 'rakes' ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Rakes</CardTitle>
                            <CardDescription>
                                System rakes for your sidings. RR number, received date, and weight come from the primary
                                RR (non-diversion). Default loading date is today — adjust filters as needed. Open a row
                                to view RR details and upload a PDF.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {tableData.meta.total === 0 && filtersEmpty ? (
                                <div className="py-8 text-center">
                                    <TrainFront className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                    <h3 className="mb-2 text-lg font-medium">No rakes to show</h3>
                                    <p className="text-muted-foreground">
                                        No rakes match today&apos;s loading date and your access, or filters excluded
                                        everything. Adjust filters or pick another loading date.
                                    </p>
                                </div>
                            ) : null}

                            {!(tableData.meta.total === 0 && filtersEmpty) ? (
                                <DataTable<RailwayReceiptsRakeRow>
                                    tableData={rakesTableData}
                                    tableName="railway-receipts-rakes"
                                    rowClassName={rakeRowClassName}
                                    onRowClick={(row) => setHubRow(row)}
                                    actions={[
                                        {
                                            label: 'View',
                                            onClick: (row) => {
                                                if (row.rr_document_id != null) {
                                                    router.visit(`/railway-receipts/${row.rr_document_id}`);
                                                } else {
                                                    router.visit(`/rakes/${row.id}`);
                                                }
                                            },
                                        },
                                    ]}
                                    renderCell={(columnId, _value, row) => {
                                        if (columnId === 'siding_code') {
                                            return row.siding_code && row.siding_name
                                                ? `${row.siding_code} (${row.siding_name})`
                                                : '—';
                                        }
                                        if (columnId === 'destination') {
                                            return row.destination ? row.destination : '—';
                                        }
                                        if (columnId === 'loading_date') {
                                            return row.loading_date
                                                ? new Date(row.loading_date).toLocaleDateString()
                                                : '—';
                                        }
                                        if (columnId === 'rr_number') {
                                            return row.rr_number ?? '—';
                                        }
                                        if (columnId === 'rr_received_date') {
                                            return row.rr_received_date
                                                ? new Date(row.rr_received_date).toLocaleDateString()
                                                : '—';
                                        }
                                        if (columnId === 'rr_weight_mt') {
                                            return row.rr_weight_mt ?? '—';
                                        }
                                        return undefined;
                                    }}
                                    options={{
                                        exports: false,
                                        quickViews: false,
                                        customQuickViews: false,
                                        filtersLayout: 'inline',
                                        filtersInlineSingleRow: true,
                                        filtersColumnWrapClassNames: {
                                            rake_number: 'w-[4.5rem]',
                                            siding_code: 'w-[12rem]',
                                            destination: 'w-[10rem]',
                                            loading_date: 'min-w-[18rem]',
                                            rr_number: 'min-w-[12rem] w-[15rem]',
                                        },
                                    }}
                                />
                            ) : null}
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Without rake</CardTitle>
                            <CardDescription>Historical or standalone RR PDFs not linked to a rake.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <DataTable<RailwayReceiptsStandaloneRow>
                                tableData={tableData as DataTableResponse<RailwayReceiptsStandaloneRow>}
                                tableName="railway-receipts-standalone"
                                actions={[
                                    {
                                        label: 'View',
                                        onClick: (row) => router.visit(`/railway-receipts/${row.id}`),
                                    },
                                ]}
                                renderCell={(columnId, _value, row) => {
                                    if (columnId === 'rr_received_date') {
                                        return row.rr_received_date
                                            ? new Date(row.rr_received_date).toLocaleDateString()
                                            : '—';
                                    }
                                    if (columnId === 'rr_weight_mt') {
                                        return row.rr_weight_mt ?? '—';
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
                )}
            </div>

            {hubRow != null ? (
                <Dialog
                    open={true}
                    onOpenChange={(open) => {
                        if (!open) {
                            closeHub();
                        }
                    }}
                >
                    <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto" data-pan="rr-rake-hub-dialog-content">
                        <DialogHeader>
                            <DialogTitle>Rake {hubRow.rake_number}</DialogTitle>
                            <DialogDescription>{hubMeta}</DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4">
                            <div className="border-border rounded-lg border p-4">
                                <h3 className="mb-3 text-sm font-semibold">Railway receipt (primary)</h3>
                                {hubRow.rr_document_id != null ? (
                                    <>
                                        <RrHubDetailRows row={hubRow} />
                                        <div className="mt-4">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={`/railway-receipts/${hubRow.rr_document_id}`}>
                                                    <ExternalLink className="mr-2 size-4" />
                                                    Open full record
                                                </Link>
                                            </Button>
                                        </div>
                                    </>
                                ) : (
                                    <p className="text-muted-foreground text-sm">No primary RR yet for this rake.</p>
                                )}
                            </div>

                            {canUpload ? (
                                <div className="border-border space-y-3 rounded-lg border p-4">
                                    <h3 className="text-sm font-semibold">Upload RR PDF</h3>
                                    <input
                                        ref={hubFileRef}
                                        type="file"
                                        accept=".pdf"
                                        className="hidden"
                                        onChange={(e) => {
                                            const f = e.target.files?.[0];
                                            setHubFile(f ?? null);
                                        }}
                                        data-pan="rr-rake-hub-pdf-input"
                                    />
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() => hubFileRef.current?.click()}
                                            data-pan="rr-rake-hub-choose-pdf"
                                        >
                                            Choose PDF
                                        </Button>
                                        {hubFile ? (
                                            <span className="text-muted-foreground text-sm">{hubFile.name}</span>
                                        ) : null}
                                    </div>
                                    {errors?.pdf ? <p className="text-destructive text-sm">{errors.pdf}</p> : null}
                                </div>
                            ) : null}
                        </div>

                        <DialogFooter className="gap-2 sm:justify-between">
                            <Button type="button" variant="outline" onClick={closeHub} disabled={uploading}>
                                Close
                            </Button>
                            {canUpload ? (
                                <Button
                                    type="button"
                                    onClick={submitHubUpload}
                                    disabled={uploading || !hubFile}
                                    data-pan="rr-rake-hub-submit-upload"
                                >
                                    <Upload className="mr-2 size-4" />
                                    {uploading ? 'Uploading…' : 'Upload PDF'}
                                </Button>
                            ) : null}
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            ) : null}
        </AppLayout>
    );
}
