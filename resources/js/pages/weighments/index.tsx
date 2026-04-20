import { DataTable } from '@/components/data-table/data-table';
import type { DataTableResponse } from 'laravel-data-table';
import { Badge } from '@/components/ui/badge';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { useCan } from '@/hooks/use-can';
import { manualWeighmentFieldsFromRake } from '@/lib/manual-weighment-from-rake';
import { cn } from '@/lib/utils';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Download, Eye, FileText, PenLine, Scale, Trash2, Upload } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

export interface WeighmentsRakeRow {
    id: number;
    rake_number: string;
    rake_serial_number: string | null;
    /** E-Demand / forwarding note number from linked indent (shown as Priority number). */
    indent_number: string | null;
    loading_date: string | null;
    siding_id: number | null;
    siding_code: string | null;
    siding_name: string | null;
    destination: string | null;
    rake_destination_code: string | null;
    rake_priority_number: number | null;
    weighment_row_state: 'missing' | 'manual_only' | 'complete';
    latest_weighment_id: number | null;
    latest_attempt_no: number | null;
    latest_total_net_weight_mt: string | null;
    latest_total_gross_weight_mt: string | null;
    latest_total_tare_weight_mt: string | null;
    latest_from_station: string | null;
    latest_to_station: string | null;
    latest_priority_number: string | null;
    latest_wagon_weighments_count: number;
    latest_has_pdf_path: boolean;
}

interface Props {
    tableData: DataTableResponse<WeighmentsRakeRow>;
}

type HubTab = 'file' | 'manual';

function rowStateClass(state: WeighmentsRakeRow['weighment_row_state']): string {
    if (state === 'missing') {
        return 'bg-red-100/80 dark:bg-red-950/50';
    }
    if (state === 'manual_only') {
        return 'bg-yellow-100/80 dark:bg-yellow-950/40';
    }
    return 'bg-green-100/80 dark:bg-green-950/40';
}

function rowStateLabel(state: WeighmentsRakeRow['weighment_row_state']): string {
    if (state === 'missing') {
        return 'No weighment yet';
    }
    if (state === 'manual_only') {
        return 'Manual totals only (no slip / wagon lines)';
    }
    return 'Document or wagon lines on file';
}

function rowStateBadgeClass(state: WeighmentsRakeRow['weighment_row_state']): string {
    if (state === 'missing') {
        return 'border-red-300 bg-red-100 text-red-900 dark:border-red-800 dark:bg-red-950/80 dark:text-red-100';
    }
    if (state === 'manual_only') {
        return 'border-amber-300 bg-amber-100 text-amber-950 dark:border-amber-700 dark:bg-amber-950/50 dark:text-amber-100';
    }
    return 'border-green-300 bg-green-100 text-green-950 dark:border-green-800 dark:bg-green-950/50 dark:text-green-100';
}

export default function WeighmentsIndex({ tableData }: Props) {
    const canUpload = useCan('sections.weighments.upload');
    /** Hub delete: `DELETE .../weighments?return_to=weighments` so redirect stays on weighments index. */
    const canDeleteRakeWeighment = useCan([
        'sections.rakes.update',
        'sections.weighments.upload',
        'sections.weighments.delete',
    ]);

    const { flash, errors } = usePage<{
        flash?: { success?: string };
        errors?: { pdf?: string; rake_id?: string; total_net_weight_mt?: string; template?: string };
    }>().props;

    const fileInputRef = useRef<HTMLInputElement>(null);
    const [hubOpen, setHubOpen] = useState(false);
    const [hubTab, setHubTab] = useState<HubTab>('file');
    const [selectedRake, setSelectedRake] = useState<WeighmentsRakeRow | null>(null);
    const [uploading, setUploading] = useState(false);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);

    const manualForm = useForm({
        rake_id: 0,
        total_net_weight_mt: '',
        from_station: '',
        to_station: '',
        priority_number: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Weighments', href: '/weighments' },
    ];

    const tableDataWithRakeFilter = useMemo(
        () => ({
            ...tableData,
            columns: tableData.columns.map((col) =>
                col.id === 'rake_number' ? { ...col, filterTextOperator: 'eq' as const } : col,
            ),
        }),
        [tableData],
    );

    const openHub = useCallback((row: WeighmentsRakeRow) => {
        setSelectedRake(row);
        setHubTab('file');
        setSelectedFile(null);
        manualForm.reset();
        manualForm.clearErrors();

        const hasWeighment = row.latest_weighment_id != null;
        const rakeManualDefaults = manualWeighmentFieldsFromRake({
            siding: { code: row.siding_code, name: row.siding_name },
            destination: row.destination,
            destination_code: row.rake_destination_code,
            priority_number: row.rake_priority_number,
        });

        manualForm.setData({
            rake_id: row.id,
            total_net_weight_mt: row.latest_total_net_weight_mt ?? '',
            from_station: hasWeighment ? (row.latest_from_station ?? '') : rakeManualDefaults.from_station,
            to_station: hasWeighment ? (row.latest_to_station ?? '') : rakeManualDefaults.to_station,
            priority_number: hasWeighment ? (row.latest_priority_number ?? '') : rakeManualDefaults.priority_number,
        });
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
        setHubOpen(true);
    }, [manualForm]);

    const closeHub = useCallback(() => {
        setHubOpen(false);
        setSelectedRake(null);
        setSelectedFile(null);
        manualForm.reset();
        manualForm.clearErrors();
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }, [manualForm]);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        setSelectedFile(file ?? null);
    };

    const submitUpload = useCallback(() => {
        if (!canUpload || !selectedRake || !selectedFile) {
            return;
        }

        setUploading(true);
        const formData = new FormData();
        formData.append('pdf', selectedFile);
        formData.append('rake_id', String(selectedRake.id));

        router.post('/weighments/import', formData, {
            forceFormData: true,
            onFinish: () => {
                setUploading(false);
                closeHub();
            },
        });
    }, [canUpload, closeHub, selectedFile, selectedRake]);

    const hubAllowsManual = useMemo(() => {
        if (!selectedRake) {
            return false;
        }

        return !selectedRake.latest_has_pdf_path && selectedRake.latest_wagon_weighments_count === 0;
    }, [selectedRake]);

    useEffect(() => {
        if (hubOpen && !hubAllowsManual && hubTab === 'manual') {
            setHubTab('file');
        }
    }, [hubAllowsManual, hubOpen, hubTab]);

    const submitManual = useCallback(() => {
        if (!canUpload || !selectedRake || !hubAllowsManual) {
            return;
        }

        const updating = selectedRake.latest_weighment_id != null;

        if (updating) {
            const ok = window.confirm(
                'This will update the current net weighment with this new net weighment. Are you sure you want to update this weighment?',
            );
            if (!ok) {
                return;
            }
        }

        manualForm.setData({
            rake_id: selectedRake.id,
            total_net_weight_mt: manualForm.data.total_net_weight_mt,
            from_station: manualForm.data.from_station,
            to_station: manualForm.data.to_station,
            priority_number: manualForm.data.priority_number,
        });

        const finish = (): void => {
            closeHub();
        };

        if (updating && selectedRake.latest_weighment_id != null) {
            manualForm.patch(`/rakes/${selectedRake.id}/weighments/${selectedRake.latest_weighment_id}`, {
                preserveScroll: true,
                onFinish: finish,
            });
        } else {
            manualForm.post('/weighments/manual', {
                preserveScroll: true,
                onFinish: finish,
            });
        }
    }, [canUpload, closeHub, hubAllowsManual, manualForm, selectedRake]);

    const downloadTemplate = useCallback(() => {
        if (!selectedRake) {
            return;
        }
        const returnTo = encodeURIComponent(
            `${window.location.pathname}${window.location.search}`,
        );
        window.location.href = `/weighments/template-xlsx?rake_id=${encodeURIComponent(String(selectedRake.id))}&return_to=${returnTo}`;
    }, [selectedRake]);

    const deleteHubWeighmentFile = useCallback(() => {
        if (!selectedRake?.latest_weighment_id) {
            return;
        }
        if (!canDeleteRakeWeighment) {
            return;
        }
        if (!window.confirm('Are you sure you want to delete this weighment file?')) {
            return;
        }
        router.delete(`/rakes/${selectedRake.id}/weighments?return_to=weighments`, {
            preserveScroll: true,
            onFinish: () => {
                closeHub();
            },
        });
    }, [canDeleteRakeWeighment, closeHub, selectedRake]);

    const rakeMetaLine = selectedRake
        ? [
              selectedRake.siding_name ?? selectedRake.siding_code ?? '',
              selectedRake.loading_date
                  ? new Date(selectedRake.loading_date).toLocaleDateString()
                  : '',
              selectedRake.destination ?? '',
          ]
              .filter(Boolean)
              .join(' · ')
        : '';

    const filtersEmpty =
        tableData.meta.filters == null ||
        (typeof tableData.meta.filters === 'object' && Object.keys(tableData.meta.filters).length === 0);

    const formatRakeSequence = (value: string, row: WeighmentsRakeRow): string => {
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
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Weighments" />

            <div className="space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                        {flash.success}
                    </div>
                )}

                {errors?.pdf && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                        {errors.pdf}
                    </div>
                )}

                {errors?.template && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                        {errors.template}
                    </div>
                )}

                {(errors?.rake_id || errors?.total_net_weight_mt) && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                        {errors.rake_id && <p>{errors.rake_id}</p>}
                        {errors.total_net_weight_mt && <p>{errors.total_net_weight_mt}</p>}
                    </div>
                )}

                <div>
                    <h1 className="text-3xl font-bold">Weighments</h1>
                    <p className="text-muted-foreground">
                        Rake weighment uploads and manual entry — select a rake row to open actions
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Scale className="h-5 w-5" />
                            Rakes
                        </CardTitle>
                        <CardDescription>
                            System rakes for your sidings. Row color indicates weighment status. Click a row to upload a
                            document, download the Excel template, or enter net weight manually.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {tableData.meta.total === 0 && filtersEmpty ? (
                            <div className="py-8 text-center">
                                <Scale className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-medium">No rakes to show</h3>
                                <p className="text-muted-foreground">
                                    There are no eligible rakes for your access, or filters excluded everything. Adjust
                                    filters or create rakes from indents.
                                </p>
                            </div>
                        ) : null}

                        {!(tableData.meta.total === 0 && filtersEmpty) ? (
                            <DataTable<WeighmentsRakeRow>
                                tableData={tableDataWithRakeFilter}
                                tableName="weighments-rakes"
                                renderHeader={{ rake_number: 'Rake Seq' }}
                                onRowClick={(row) => {
                                    openHub(row);
                                }}
                                rowClassName={(row) => rowStateClass(row.weighment_row_state)}
                                actions={[
                                    {
                                        label: 'View',
                                        onClick: (row) => {
                                            if (row.latest_weighment_id != null) {
                                                router.visit(`/weighments/${row.latest_weighment_id}`);
                                            } else {
                                                router.visit(`/rakes/${row.id}`);
                                            }
                                        },
                                    },
                                    {
                                        label: 'Delete',
                                        variant: 'destructive',
                                        visible: (row) =>
                                            canDeleteRakeWeighment && row.weighment_row_state !== 'missing',
                                        onClick: (row) => {
                                            if (!window.confirm('Delete all weighment data for this rake?')) {
                                                return;
                                            }
                                            router.delete(`/rakes/${row.id}/weighments?return_to=weighments`, {
                                                preserveScroll: true,
                                            });
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

                                    if (columnId === 'indent_number') {
                                        return row.indent_number != null && row.indent_number !== ''
                                            ? row.indent_number
                                            : '—';
                                    }
                                    if (columnId === 'siding_code') {
                                        return row.siding_code && row.siding_name
                                            ? `${row.siding_code} (${row.siding_name})`
                                            : '—';
                                    }
                                    if (columnId === 'destination') {
                                        return row.destination ? row.destination : '—';
                                    }
                                    if (columnId === 'latest_total_net_weight_mt') {
                                        return row.latest_total_net_weight_mt != null &&
                                            row.latest_total_net_weight_mt !== ''
                                            ? row.latest_total_net_weight_mt
                                            : 'N/A';
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
                        ) : null}
                    </CardContent>
                </Card>
            </div>

            {canUpload && (
                <Dialog
                    open={hubOpen}
                    onOpenChange={(open) => {
                        if (!open) {
                            closeHub();
                        }
                    }}
                >
                    <DialogContent className="max-h-[92vh] w-[min(100vw-2rem,80rem)] max-w-none gap-0 overflow-y-auto p-0 sm:p-0">
                        <div className="border-b border-border bg-muted/40 px-6 py-5">
                            <DialogHeader className="space-y-3 text-left">
                                <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                    Rake number
                                </p>
                                <DialogTitle className="font-mono text-3xl font-bold tracking-tight sm:text-4xl">
                                    {selectedRake?.rake_number ?? '—'}
                                </DialogTitle>
                                <div className="space-y-1">
                                    <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                        Priority number
                                    </p>
                                    <p className="font-mono text-xl font-semibold tracking-tight text-foreground sm:text-2xl">
                                        {selectedRake?.indent_number != null &&
                                        selectedRake.indent_number !== ''
                                            ? selectedRake.indent_number
                                            : '—'}
                                    </p>
                                </div>
                                <DialogDescription className="text-base">{rakeMetaLine || '—'}</DialogDescription>
                            </DialogHeader>
                        </div>

                        <div className="space-y-4 px-6 pt-5">
                            {selectedRake ? (
                                <div
                                    className="rounded-lg border bg-card p-4 shadow-sm"
                                    data-pan="weighments-hub-saved-summary"
                                >
                                    <div className="mb-3 flex flex-wrap items-center gap-2">
                                        <span className="text-sm font-semibold">Saved weighment (latest)</span>
                                        <Badge
                                            variant="outline"
                                            className={rowStateBadgeClass(selectedRake.weighment_row_state)}
                                        >
                                            {rowStateLabel(selectedRake.weighment_row_state)}
                                        </Badge>
                                    </div>
                                    {selectedRake.latest_weighment_id == null ? (
                                        <p className="text-muted-foreground text-sm">
                                            No weighment record for this rake yet. Use File or Manual entry below to add
                                            one.
                                        </p>
                                    ) : (
                                        <dl className="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                                            <div>
                                                <dt className="text-muted-foreground">Attempt</dt>
                                                <dd className="font-medium">{selectedRake.latest_attempt_no ?? '—'}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-muted-foreground">Total net (MT)</dt>
                                                <dd className="font-medium">
                                                    {selectedRake.latest_total_net_weight_mt ?? '—'}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-muted-foreground">Gross / Tare (MT)</dt>
                                                <dd className="font-medium">
                                                    {[
                                                        selectedRake.latest_total_gross_weight_mt,
                                                        selectedRake.latest_total_tare_weight_mt,
                                                    ]
                                                        .every((v) => v == null || v === '')
                                                        ? '—'
                                                        : `${selectedRake.latest_total_gross_weight_mt ?? '—'} / ${selectedRake.latest_total_tare_weight_mt ?? '—'}`}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-muted-foreground">From → To station</dt>
                                                <dd className="font-medium">
                                                    {selectedRake.latest_from_station || selectedRake.latest_to_station
                                                        ? `${selectedRake.latest_from_station ?? '—'} → ${selectedRake.latest_to_station ?? '—'}`
                                                        : '—'}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-muted-foreground">Priority #</dt>
                                                <dd className="font-medium">
                                                    {selectedRake.latest_priority_number ?? '—'}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-muted-foreground">Wagon line rows</dt>
                                                <dd className="font-medium">{selectedRake.latest_wagon_weighments_count}</dd>
                                            </div>
                                            <div className="sm:col-span-2">
                                                <dt className="text-muted-foreground">PDF / Excel path on record</dt>
                                                <dd className="font-medium">
                                                    {selectedRake.latest_has_pdf_path ? 'Yes' : 'No'}
                                                </dd>
                                            </div>
                                        </dl>
                                    )}
                                    <p className="text-muted-foreground mt-3 border-t pt-3 text-xs">
                                        Row highlight matches this status.
                                        {hubAllowsManual
                                            ? ' Manual entry is pre-filled from these values so you can confirm or adjust.'
                                            : selectedRake.latest_has_pdf_path
                                              ? ' A document path is on file — use the File section to view or remove it.'
                                              : ' Use File below to add or replace the slip / spreadsheet.'}
                                    </p>
                                </div>
                            ) : null}
                        </div>

                        {hubAllowsManual ? (
                            <div className="flex gap-1 border-b border-border px-6 pb-px">
                                <button
                                    type="button"
                                    className={cn(
                                        '-mb-px border-b-2 px-3 py-2 text-sm font-medium',
                                        hubTab === 'file'
                                            ? 'border-primary text-foreground'
                                            : 'border-transparent text-muted-foreground hover:text-foreground',
                                    )}
                                    onClick={() => setHubTab('file')}
                                >
                                    <span className="inline-flex items-center gap-2">
                                        <Upload className="h-4 w-4" />
                                        File
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    className={cn(
                                        '-mb-px border-b-2 px-3 py-2 text-sm font-medium',
                                        hubTab === 'manual'
                                            ? 'border-primary text-foreground'
                                            : 'border-transparent text-muted-foreground hover:text-foreground',
                                    )}
                                    onClick={() => setHubTab('manual')}
                                >
                                    <span className="inline-flex items-center gap-2">
                                        <PenLine className="h-4 w-4" />
                                        Manual entry
                                    </span>
                                </button>
                            </div>
                        ) : (
                            <div className="border-b border-border px-6 py-3">
                                <p className="text-muted-foreground flex items-center gap-2 text-sm font-medium">
                                    {selectedRake?.latest_has_pdf_path ? (
                                        <>
                                            <FileText className="h-4 w-4 shrink-0" />
                                            Weighment file on record — view or delete below
                                        </>
                                    ) : (
                                        <>
                                            <Upload className="h-4 w-4 shrink-0" />
                                            Upload file (manual entry is unavailable while wagon lines exist on this
                                            weighment)
                                        </>
                                    )}
                                </p>
                            </div>
                        )}

                        {hubTab === 'file' || !hubAllowsManual ? (
                            <div className="space-y-4 px-6 pb-6 pt-4">
                                {selectedRake?.latest_has_pdf_path ? (
                                    <>
                                        <p className="text-muted-foreground text-sm">
                                            This rake already has a weighment slip path stored. Open the full record or
                                            remove all weighment data for this rake (same as the rake workflow).
                                        </p>
                                        <div className="flex flex-wrap items-center gap-2">
                                            {selectedRake.latest_weighment_id != null ? (
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link
                                                        href={`/weighments/${selectedRake.latest_weighment_id}`}
                                                        className="inline-flex items-center gap-2"
                                                        data-pan="weighments-hub-view-weighment"
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                        View data
                                                    </Link>
                                                </Button>
                                            ) : null}
                                            {canDeleteRakeWeighment && selectedRake.weighment_row_state !== 'missing' ? (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    className="inline-flex items-center gap-2 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                    onClick={() => void deleteHubWeighmentFile()}
                                                    data-pan="weighments-hub-delete-weighment-file"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                    Delete weighment
                                                </Button>
                                            ) : null}
                                        </div>
                                        <DialogFooter className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={closeHub}
                                                data-pan="weighments-upload-dialog-cancel"
                                            >
                                                Close
                                            </Button>
                                        </DialogFooter>
                                    </>
                                ) : (
                                    <>
                                        <div className="space-y-2">
                                            <Label htmlFor="hub-weighment-file">PDF or Excel</Label>
                                            <Input
                                                ref={fileInputRef}
                                                id="hub-weighment-file"
                                                type="file"
                                                accept=".pdf,.xlsx"
                                                onChange={handleFileChange}
                                                className="cursor-pointer"
                                                data-pan="weighments-dialog-file-input"
                                            />
                                            {selectedFile ? (
                                                <p className="text-xs text-muted-foreground">Selected: {selectedFile.name}</p>
                                            ) : null}
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                className="inline-flex items-center gap-2"
                                                onClick={downloadTemplate}
                                                data-pan="weighments-download-xlsx-template-button"
                                            >
                                                <Download className="h-4 w-4" />
                                                Download Excel template
                                            </Button>
                                        </div>
                                        <DialogFooter className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={closeHub}
                                                disabled={uploading}
                                                data-pan="weighments-upload-dialog-cancel"
                                            >
                                                Cancel
                                            </Button>
                                            <Button
                                                type="button"
                                                onClick={() => void submitUpload()}
                                                disabled={uploading || !selectedFile}
                                                data-pan="weighments-upload-with-rake-button"
                                            >
                                                {uploading ? 'Uploading…' : 'Upload'}
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </div>
                        ) : (
                            <div className="space-y-4 px-6 pb-6 pt-4">
                                <div className="space-y-3 rounded-md border p-3">
                                    <div className="grid gap-3 md:grid-cols-2">
                                        <div className="md:col-span-2">
                                            <Label htmlFor="hub-manual-total-net">Total net weight (MT)</Label>
                                            <Input
                                                id="hub-manual-total-net"
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                value={manualForm.data.total_net_weight_mt}
                                                onChange={(e) => manualForm.setData('total_net_weight_mt', e.target.value)}
                                                data-pan="weighments-dialog-manual-net-mt"
                                            />
                                            <InputError message={manualForm.errors.total_net_weight_mt} />
                                        </div>
                                        <div>
                                            <Label htmlFor="hub-manual-from">From station</Label>
                                            <Input
                                                id="hub-manual-from"
                                                value={manualForm.data.from_station}
                                                onChange={(e) => manualForm.setData('from_station', e.target.value)}
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="hub-manual-to">To station</Label>
                                            <Input
                                                id="hub-manual-to"
                                                value={manualForm.data.to_station}
                                                onChange={(e) => manualForm.setData('to_station', e.target.value)}
                                            />
                                        </div>
                                        <div className="md:col-span-2">
                                            <Label htmlFor="hub-manual-priority">Priority number</Label>
                                            <Input
                                                id="hub-manual-priority"
                                                value={manualForm.data.priority_number}
                                                onChange={(e) =>
                                                    manualForm.setData('priority_number', e.target.value)
                                                }
                                            />
                                        </div>
                                    </div>
                                </div>
                                <DialogFooter className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={closeHub}
                                        disabled={manualForm.processing}
                                        data-pan="weighments-manual-dialog-cancel"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="button"
                                        onClick={() => void submitManual()}
                                        disabled={
                                            manualForm.processing || !manualForm.data.total_net_weight_mt
                                        }
                                        data-pan={
                                            selectedRake?.latest_weighment_id != null
                                                ? 'weighments-dialog-update-manual'
                                                : 'weighments-dialog-save-manual'
                                        }
                                    >
                                        {manualForm.processing
                                            ? selectedRake?.latest_weighment_id != null
                                                ? 'Updating…'
                                                : 'Saving…'
                                            : selectedRake?.latest_weighment_id != null
                                              ? 'Update manual weighment'
                                              : 'Save manual weighment'}
                                    </Button>
                                </DialogFooter>
                            </div>
                        )}
                    </DialogContent>
                </Dialog>
            )}
        </AppLayout>
    );
}