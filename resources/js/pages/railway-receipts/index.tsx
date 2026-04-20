import { DataTable } from '@/components/data-table/data-table';
import Heading from '@/components/heading';
import {
    DiverrtDestinationRow,
    RrDocumentRecord,
    RrSlotCard,
    findDocForSlot,
} from '@/components/railway-receipts/rr-hub-shared';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useCan } from '@/hooks/use-can';
import AppLayout from '@/layouts/app-layout';
import { JsonFetchError, laravelJsonFetch, postRailwayReceiptImport } from '@/lib/laravel-json-fetch';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import type { DataTableResponse } from 'laravel-data-table';
import { ExternalLink, TrainFront, Trash2 } from 'lucide-react';
import { type ReactNode, useCallback, useEffect, useMemo, useState } from 'react';

export interface RailwayReceiptsRakeRow {
    id: number;
    rake_number: string;
    rake_serial_number: string | null;
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

interface RrHubPayload {
    is_diverted: boolean;
    rrDocuments: RrDocumentRecord[];
    diverrtDestinations: DiverrtDestinationRow[];
}

interface Props {
    tableData: DataTableResponse<RailwayReceiptsRakeRow | RailwayReceiptsStandaloneRow>;
    activeTab?: 'rakes' | 'standalone';
    can_upload_rr?: boolean;
    showStandaloneTab?: boolean;
    can_manage_rake_diversion?: boolean;
}

function parseRrHub(raw: unknown): RrHubPayload | null {
    if (typeof raw !== 'object' || raw === null) {
        return null;
    }
    const o = raw as Record<string, unknown>;
    if (typeof o.is_diverted !== 'boolean' || !Array.isArray(o.rrDocuments) || !Array.isArray(o.diverrtDestinations)) {
        return null;
    }
    return {
        is_diverted: o.is_diverted,
        rrDocuments: o.rrDocuments as RrDocumentRecord[],
        diverrtDestinations: o.diverrtDestinations as DiverrtDestinationRow[],
    };
}

function firstValidationError(body: unknown, keys: string[]): string | undefined {
    if (typeof body !== 'object' || body === null) {
        return undefined;
    }
    const errors = (body as { errors?: Record<string, string[]> }).errors;
    if (!errors) {
        return undefined;
    }
    for (const k of keys) {
        const line = errors[k]?.[0];
        if (line) {
            return line;
        }
    }
    return undefined;
}

function messageFromFetchError(err: unknown): string {
    if (err instanceof JsonFetchError) {
        const v =
            firstValidationError(err.body, ['pdf', 'is_diverted', 'diverrt_destination', 'location']) ??
            (typeof err.body === 'object' && err.body !== null && 'message' in err.body
                ? String((err.body as { message: unknown }).message)
                : null);
        if (v) {
            return v;
        }
        return err.message;
    }
    return err instanceof Error ? err.message : 'Something went wrong.';
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

function PrimaryRrHubSummary({ hubRow, primaryDoc }: { hubRow: RailwayReceiptsRakeRow; primaryDoc: RrDocumentRecord | undefined }) {
    const showTableDetail = primaryDoc != null && hubRow.rr_document_id === primaryDoc.id;

    if (showTableDetail) {
        return (
            <>
                <RrHubDetailRows row={hubRow} />
                <div className="mt-4">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/railway-receipts/${primaryDoc.id}`}>
                            <ExternalLink className="mr-2 size-4" />
                            Open full record
                        </Link>
                    </Button>
                </div>
            </>
        );
    }

    if (primaryDoc) {
        return (
            <>
                <dl className="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                    <div>
                        <dt className="text-muted-foreground font-medium">RR number</dt>
                        <dd className="mt-0.5">{primaryDoc.rr_number}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground font-medium">Received date</dt>
                        <dd className="mt-0.5">
                            {primaryDoc.rr_received_date ? new Date(primaryDoc.rr_received_date).toLocaleDateString() : '—'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground font-medium">Weight (MT)</dt>
                        <dd className="mt-0.5">{primaryDoc.rr_weight_mt ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground font-medium">Document status</dt>
                        <dd className="mt-0.5">{primaryDoc.document_status}</dd>
                    </div>
                </dl>
                <div className="mt-4">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/railway-receipts/${primaryDoc.id}`}>
                            <ExternalLink className="mr-2 size-4" />
                            Open full record
                        </Link>
                    </Button>
                </div>
            </>
        );
    }

    if (!hubRow.has_diversion && hubRow.rr_document_id != null) {
        return (
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
        );
    }

    return <p className="text-muted-foreground text-sm">No primary RR yet for this rake.</p>;
}

export default function RailwayReceiptsIndex({
    tableData,
    activeTab = 'rakes',
    can_upload_rr = false,
    showStandaloneTab = false,
    can_manage_rake_diversion: canManageRakeDiversionProp,
}: Props) {
    const [hubRow, setHubRow] = useState<RailwayReceiptsRakeRow | null>(null);
    const [rrHub, setRrHub] = useState<RrHubPayload | null>(null);
    const [hubLoading, setHubLoading] = useState(false);
    const [hubFetchError, setHubFetchError] = useState<string | null>(null);
    const [hubActionError, setHubActionError] = useState<string | null>(null);
    const [uploadingKey, setUploadingKey] = useState<string | null>(null);
    const [diversionModeBusy, setDiversionModeBusy] = useState(false);
    const [addingDestination, setAddingDestination] = useState(false);
    const [newLocation, setNewLocation] = useState('');

    const {
        props: { errors },
    } = usePage<{ errors: Record<string, string | undefined> }>();
    const canUploadRr = useCan('sections.railway_receipts.upload');
    const canUpdateRakes = useCan('sections.rakes.update');
    const canUpload = can_upload_rr && canUploadRr;
    const canManageDiversion = canManageRakeDiversionProp ?? canUpdateRakes;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Railway Receipts', href: '/railway-receipts' },
    ];

    const switchTab = useCallback((tab: 'rakes' | 'standalone') => {
        router.get('/railway-receipts', { tab }, { preserveScroll: true });
    }, []);

    const closeHub = useCallback(() => {
        setHubRow(null);
        setRrHub(null);
        setHubFetchError(null);
        setHubActionError(null);
        setUploadingKey(null);
        setNewLocation('');
    }, []);

    useEffect(() => {
        if (hubRow == null) {
            return;
        }
        let cancelled = false;
        setHubLoading(true);
        setHubFetchError(null);
        setHubActionError(null);

        (async () => {
            try {
                const data = await laravelJsonFetch<{ rr_hub: RrHubPayload }>(`/rakes/${hubRow.id}/rr-hub-state`);
                if (!cancelled) {
                    const parsed = parseRrHub(data.rr_hub);
                    setRrHub(parsed);
                    if (!parsed) {
                        setHubFetchError('Invalid hub state from server.');
                    }
                }
            } catch (e) {
                if (!cancelled) {
                    setHubFetchError(messageFromFetchError(e));
                    setRrHub(null);
                }
            } finally {
                if (!cancelled) {
                    setHubLoading(false);
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [hubRow]);

    const mergeRrHubFromResponse = useCallback((raw: unknown) => {
        const parsed = parseRrHub(raw);
        if (parsed) {
            setRrHub(parsed);
        }
    }, []);

    const handleSlotUpload = useCallback(
        async (file: File, diverrtDestinationId: number | null) => {
            if (!hubRow || !canUpload) {
                return;
            }
            const key = diverrtDestinationId === null ? 'primary' : `div-${diverrtDestinationId}`;
            setUploadingKey(key);
            setHubActionError(null);
            try {
                const body = await postRailwayReceiptImport(hubRow.id, file, diverrtDestinationId);
                if (body.rr_hub != null) {
                    mergeRrHubFromResponse(body.rr_hub);
                }
            } catch (e) {
                setHubActionError(messageFromFetchError(e));
            } finally {
                setUploadingKey(null);
            }
        },
        [canUpload, hubRow, mergeRrHubFromResponse],
    );

    const handleDivertedToggle = useCallback(
        async (checked: boolean) => {
            if (!hubRow) {
                return;
            }
            setDiversionModeBusy(true);
            setHubActionError(null);
            try {
                const data = await laravelJsonFetch<{ rr_hub: unknown }>(`/rakes/${hubRow.id}/diversion-mode`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ is_diverted: checked }),
                });
                mergeRrHubFromResponse(data.rr_hub);
            } catch (e) {
                setHubActionError(messageFromFetchError(e));
            } finally {
                setDiversionModeBusy(false);
            }
        },
        [hubRow, mergeRrHubFromResponse],
    );

    const addDestination = useCallback(
        async (e: React.FormEvent) => {
            e.preventDefault();
            if (!hubRow || !newLocation.trim()) {
                return;
            }
            setAddingDestination(true);
            setHubActionError(null);
            try {
                const data = await laravelJsonFetch<{ rr_hub: unknown }>(`/rakes/${hubRow.id}/diverrt-destinations`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ location: newLocation.trim() }),
                });
                mergeRrHubFromResponse(data.rr_hub);
                setNewLocation('');
            } catch (err) {
                setHubActionError(messageFromFetchError(err));
            } finally {
                setAddingDestination(false);
            }
        },
        [hubRow, mergeRrHubFromResponse, newLocation],
    );

    const removeDestination = useCallback(
        async (destinationId: number) => {
            if (!hubRow) {
                return;
            }
            if (
                !confirm(
                    'Remove this diversion destination? Only allowed if no Railway Receipt has been uploaded for it.',
                )
            ) {
                return;
            }
            setHubActionError(null);
            try {
                const data = await laravelJsonFetch<{ rr_hub: unknown }>(
                    `/rakes/${hubRow.id}/diverrt-destinations/${destinationId}`,
                    { method: 'DELETE' },
                );
                mergeRrHubFromResponse(data.rr_hub);
            } catch (e) {
                setHubActionError(messageFromFetchError(e));
            }
        },
        [hubRow, mergeRrHubFromResponse],
    );

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

    const isDiverted = rrHub?.is_diverted ?? false;
    const rrDocuments = rrHub?.rrDocuments ?? [];
    const diverrtDestinations = rrHub?.diverrtDestinations ?? [];
    const primaryDoc = findDocForSlot(rrDocuments, null);

    const formatRakeSequence = useCallback((value: string, row: RailwayReceiptsRakeRow): string => {
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
                                to view RR details and upload PDFs per slot (primary and diversion legs).
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
                                    renderHeader={{ rake_number: 'Rake Seq' }}
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
                                        if (columnId === 'rake_number') {
                                            return row.rake_number !== ''
                                                ? formatRakeSequence(row.rake_number, row)
                                                : '—';
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
                            <DialogTitle>
                                Rake{' '}
                                {hubRow.rake_serial_number ? (
                                    hubRow.rake_serial_number
                                ) : hubRow.rake_number ? (
                                    <span className="text-amber-600 dark:text-amber-400">
                                        {hubRow.rake_number}
                                    </span>
                                ) : (
                                    '—'
                                )}
                            </DialogTitle>
                            <DialogDescription>{hubMeta}</DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4">
                            {hubLoading ? (
                                <p className="text-muted-foreground text-sm">Loading railway receipt state…</p>
                            ) : null}
                            {hubFetchError ? (
                                <p className="text-destructive text-sm" role="alert">
                                    {hubFetchError}
                                </p>
                            ) : null}
                            {hubActionError ? (
                                <p className="text-destructive text-sm" role="alert">
                                    {hubActionError}
                                </p>
                            ) : null}

                            {!hubLoading && !hubFetchError && rrHub != null ? (
                                <>
                                    <div className="border-border rounded-lg border p-4">
                                        <h3 className="mb-3 text-sm font-semibold">Railway receipt (primary)</h3>
                                        <PrimaryRrHubSummary hubRow={hubRow} primaryDoc={primaryDoc} />
                                    </div>

                                    {canManageDiversion ? (
                                        <div className="flex flex-col gap-3 rounded-lg border border-dashed p-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div className="space-y-1">
                                                <p className="text-sm font-medium">Diverted rake (multiple RRs)</p>
                                                <p className="text-muted-foreground text-xs">
                                                    Enable when this rake has diversion legs and needs a separate Railway
                                                    Receipt per destination.
                                                </p>
                                            </div>
                                            <label className="flex cursor-pointer items-center gap-3">
                                                <Checkbox
                                                    checked={isDiverted}
                                                    disabled={diversionModeBusy}
                                                    onCheckedChange={(v) => {
                                                        void handleDivertedToggle(v === true);
                                                    }}
                                                    data-pan="rr-hub-diverted-mode-checkbox"
                                                />
                                                <span className="text-sm font-medium">{isDiverted ? 'Enabled' : 'Disabled'}</span>
                                            </label>
                                        </div>
                                    ) : null}

                                    {isDiverted && canManageDiversion ? (
                                        <div className="space-y-3 rounded-lg border p-4">
                                            <p className="text-sm font-medium">Diversion legs</p>
                                            <p className="text-muted-foreground text-xs">
                                                Add one row per diverted destination. Use the IR{' '}
                                                <strong>Station To</strong> code as shown on that leg&apos;s RR PDF.
                                            </p>
                                            <form onSubmit={(ev) => void addDestination(ev)} className="flex flex-col gap-2 sm:flex-row sm:items-end">
                                                <div className="flex-1 space-y-1">
                                                    <Label htmlFor="rr-hub-diverrt-location">IR station code (Station To)</Label>
                                                    <input
                                                        id="rr-hub-diverrt-location"
                                                        className="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                                        value={newLocation}
                                                        onChange={(e) => setNewLocation(e.target.value)}
                                                        placeholder="e.g. PPSA"
                                                        maxLength={255}
                                                    />
                                                </div>
                                                <Button
                                                    type="submit"
                                                    disabled={addingDestination || !newLocation.trim()}
                                                    data-pan="rr-hub-diversion-destination-add"
                                                >
                                                    Add leg
                                                </Button>
                                            </form>
                                            {diverrtDestinations.length > 0 ? (
                                                <ul className="space-y-2">
                                                    {diverrtDestinations.map((d) => {
                                                        const hasDoc = Boolean(findDocForSlot(rrDocuments, d.id));
                                                        return (
                                                            <li
                                                                key={d.id}
                                                                className="flex items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm"
                                                            >
                                                                <span className="font-mono">{d.location}</span>
                                                                {!hasDoc ? (
                                                                    <Button
                                                                        type="button"
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        className="text-destructive hover:text-destructive"
                                                                        onClick={() => void removeDestination(d.id)}
                                                                        data-pan="rr-hub-diversion-destination-remove"
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                        <span className="sr-only">Remove leg</span>
                                                                    </Button>
                                                                ) : null}
                                                            </li>
                                                        );
                                                    })}
                                                </ul>
                                            ) : null}
                                        </div>
                                    ) : null}

                                    {canUpload ? (
                                        <div className="space-y-4">
                                            <p className="text-sm font-medium">Upload RR PDFs</p>
                                            <RrSlotCard
                                                label="Primary Railway Receipt"
                                                description={
                                                    isDiverted && diverrtDestinations.length > 0
                                                        ? 'Optional if the original destination was cancelled; otherwise Station To must match the rake destination code.'
                                                        : 'Station To on the PDF must match the rake destination code.'
                                                }
                                                doc={primaryDoc}
                                                disabled={false}
                                                uploading={uploadingKey === 'primary'}
                                                panUpload="rr-hub-upload-primary-pdf-button"
                                                onFile={(file) => void handleSlotUpload(file, null)}
                                            />

                                            {isDiverted && diverrtDestinations.length > 0 ? (
                                                <div className="space-y-4">
                                                    <p className="text-sm font-medium">Diversion Railway Receipts</p>
                                                    {diverrtDestinations.map((d) => {
                                                        const doc = findDocForSlot(rrDocuments, d.id);
                                                        const key = `div-${d.id}`;
                                                        return (
                                                            <RrSlotCard
                                                                key={d.id}
                                                                label={`Leg: ${d.location}`}
                                                                description="Station To on the PDF must match this leg code."
                                                                doc={doc}
                                                                disabled={false}
                                                                uploading={uploadingKey === key}
                                                                panUpload="rr-hub-upload-diversion-pdf-button"
                                                                onFile={(file) => void handleSlotUpload(file, d.id)}
                                                            />
                                                        );
                                                    })}
                                                </div>
                                            ) : null}
                                        </div>
                                    ) : null}
                                </>
                            ) : null}
                        </div>

                        <DialogFooter className="gap-2 sm:justify-end">
                            <Button type="button" variant="outline" onClick={closeHub} disabled={uploadingKey != null || diversionModeBusy}>
                                Close
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            ) : null}
        </AppLayout>
    );
}
