import { IndentCreateForm, type IndentCreatePrefill } from '@/components/indents/indent-create-form';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { GlossaryTerm } from '@/components/glossary-term';
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
import { useCan } from '@/hooks/use-can';
import { type BreadcrumbItem } from '@/types';
import { DataTable } from '@/components/data-table/data-table';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { parseLaravel422ResponseBody } from '@/lib/laravel-validation-errors';
import type { DataTableResponse } from 'laravel-data-table';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FileText, Plus, Upload } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface SidingOption {
    id: number;
    name: string;
    code: string;
}

interface PowerPlantOption {
    name: string;
    code: string;
}

interface IndentRow {
    id: number;
    rake_number: string | null;
    rake_serial_number: string | null;
    siding_code: string | null;
    indent_number: string | null;
    siding: string | null;
    indent_date: string | null;
    expected_loading_date: string | null;
    e_demand_reference_id: string | null;
    state: string;
    fnr_number: string | null;
    weighment_pdf_uploaded: boolean;
}

interface Props {
    tableData: DataTableResponse<IndentRow>;
    sidings: SidingOption[];
    power_plants: PowerPlantOption[];
}

const ASSIGN_RAKE_KNOWN_KEYS = new Set<string>(['rake_serial_number']);

function getCsrfHeaders(): HeadersInit {
    const cookieMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (cookieMatch?.[1]) {
        return {
            'X-XSRF-TOKEN': decodeURIComponent(cookieMatch[1].trim()),
            Accept: 'application/json',
        };
    }
    const meta = document.querySelector('meta[name="csrf-token"]');
    const t = meta?.getAttribute('content');
    if (t) {
        return { 'X-CSRF-TOKEN': t, Accept: 'application/json' };
    }

    return { Accept: 'application/json' };
}

export default function IndentsIndex({
    tableData,
    sidings,
    power_plants,
}: Props) {
    const canCreateIndent = useCan('sections.indents.create');
    const { flash } = usePage<{
        flash?: { success?: string };
    }>().props;

    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [previewOpen, setPreviewOpen] = useState(false);
    const [previewPrefill, setPreviewPrefill] =
        useState<IndentCreatePrefill | null>(null);
    const [stagedPdfFile, setStagedPdfFile] = useState<File | null>(null);
    const [previewSessionId, setPreviewSessionId] = useState(0);
    const [previewPdfError, setPreviewPdfError] = useState<string | null>(
        null,
    );
    const [rowContextMenu, setRowContextMenu] = useState<{
        x: number;
        y: number;
        row: IndentRow;
    } | null>(null);
    const [assignDialogOpen, setAssignDialogOpen] = useState(false);
    const [selectedIndent, setSelectedIndent] = useState<IndentRow | null>(null);
    const [assignRakeSerialNumber, setAssignRakeSerialNumber] = useState('');
    const [assignFieldErrors, setAssignFieldErrors] = useState<
        Record<string, string>
    >({});
    const [assignErrorBanner, setAssignErrorBanner] = useState<string | null>(
        null,
    );
    const [assigningRakeNumber, setAssigningRakeNumber] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'E-Demand', href: '/indents' },
    ];

    useEffect(() => {
        if (rowContextMenu === null) {
            return;
        }

        const closeContextMenu = () => {
            setRowContextMenu(null);
        };

        window.addEventListener('click', closeContextMenu);
        window.addEventListener('scroll', closeContextMenu, true);
        window.addEventListener('resize', closeContextMenu);

        return () => {
            window.removeEventListener('click', closeContextMenu);
            window.removeEventListener('scroll', closeContextMenu, true);
            window.removeEventListener('resize', closeContextMenu);
        };
    }, [rowContextMenu]);

    const handleUploadClick = () => {
        if (!canCreateIndent) {
            return;
        }
        fileInputRef.current?.click();
    };

    const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        if (!canCreateIndent) {
            return;
        }
        const file = e.target.files?.[0];
        if (!file) {
            return;
        }
        setUploading(true);
        setPreviewPdfError(null);
        const formData = new FormData();
        formData.append('pdf', file);

        try {
            const res = await fetch('/indents/import', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: getCsrfHeaders(),
            });

            const json = (await res.json()) as {
                data?: { prefill: IndentCreatePrefill };
                message?: string;
                errors?: { pdf?: string[] };
            };

            if (!res.ok) {
                const msg =
                    json.errors?.pdf?.[0] ??
                    json.message ??
                    'Could not parse this PDF.';
                setPreviewPdfError(msg);
                return;
            }

            if (!json.data?.prefill) {
                setPreviewPdfError('Unexpected response from server.');
                return;
            }

            setStagedPdfFile(file);
            setPreviewPrefill(json.data.prefill);
            setPreviewSessionId((k) => k + 1);
            setPreviewOpen(true);
        } catch {
            setPreviewPdfError('Upload failed. Check your connection and try again.');
        } finally {
            setUploading(false);
            e.target.value = '';
        }
    };

    const closePreview = () => {
        setPreviewOpen(false);
        setPreviewPrefill(null);
        setStagedPdfFile(null);
        setPreviewSessionId(0);
    };

    const openAssignDialog = (row: IndentRow) => {
        setRowContextMenu(null);
        setSelectedIndent(row);
        setAssignRakeSerialNumber(row.rake_serial_number ?? '');
        setAssignFieldErrors({});
        setAssignErrorBanner(null);
        setAssignDialogOpen(true);
    };

    const closeAssignDialog = () => {
        if (assigningRakeNumber) {
            return;
        }
        setAssignDialogOpen(false);
        setSelectedIndent(null);
        setAssignRakeSerialNumber('');
        setAssignFieldErrors({});
        setAssignErrorBanner(null);
    };

    const submitAssignRakeNumber = async (
        e: React.FormEvent<HTMLFormElement>,
    ) => {
        e.preventDefault();
        if (selectedIndent === null) {
            return;
        }

        setAssignFieldErrors({});
        setAssignErrorBanner(null);
        setAssigningRakeNumber(true);
        try {
            const response = await fetch(
                `/indents/${selectedIndent.id}/assign-rake-number`,
                {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        ...getCsrfHeaders(),
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        rake_serial_number: assignRakeSerialNumber,
                    }),
                },
            );

            const body = (await response.json().catch(() => ({}))) as unknown;

            if (!response.ok) {
                if (response.status === 422) {
                    const { fields, banner } = parseLaravel422ResponseBody(
                        body,
                        ASSIGN_RAKE_KNOWN_KEYS,
                    );
                    setAssignFieldErrors(fields);
                    setAssignErrorBanner(banner);
                } else {
                    const message =
                        typeof body === 'object' &&
                        body !== null &&
                        'message' in body
                            ? String((body as { message: unknown }).message)
                            : 'Could not assign rake number.';
                    setAssignErrorBanner(message);
                }

                return;
            }

            closeAssignDialog();
            router.reload({ only: ['tableData'] });
        } finally {
            setAssigningRakeNumber(false);
        }
    };

    const assignButtonLabel =
        selectedIndent?.rake_serial_number !== null &&
        selectedIndent?.rake_serial_number !== undefined &&
        selectedIndent.rake_serial_number.trim() !== ''
            ? 'Update'
            : 'Save';

    const formatRakeSequence = (value: string, row: IndentRow): string => {
        const normalized = value.trim();
        if (normalized === '') {
            return normalized;
        }

        const sidingValue = `${row.siding_code ?? ''} ${row.siding ?? ''}`.toLowerCase();
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

        return normalized.startsWith(`${prefix}-`)
            ? normalized
            : `${prefix}-${normalized}`;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="E-Demand" />

            <div className="space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                        {flash.success}
                    </div>
                )}

                {previewPdfError && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                        {previewPdfError}
                    </div>
                )}

                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Heading
                        title="E-Demand"
                        description="Manage rake orders and requests for the RRMCS system"
                    />
                    <div className="flex items-center gap-2">
                        {canCreateIndent && (
                            <>
                                <Link href="/indents/create">
                                    <Button
                                        size="sm"
                                        data-pan="indents-create-button"
                                    >
                                        <Plus className="mr-2 size-4" />
                                        Create e-Demand
                                    </Button>
                                </Link>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept=".pdf,application/pdf"
                                    className="hidden"
                                    onChange={handleFileChange}
                                />
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={handleUploadClick}
                                    disabled={uploading}
                                    data-pan="indents-upload-pdf-button"
                                >
                                    <Upload className="mr-2 size-4" />
                                    {uploading ? 'Uploading…' : 'Upload e-Demand PDF'}
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            E-Demand
                        </CardTitle>
                        <CardDescription>
                            View and manage all rake e-demand (orders). e-Demand reference and FNR
                            shown when set.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable<IndentRow>
                            tableData={tableData}
                            tableName="indents"
                            renderHeader={{
                                rake_number: 'Rake Seq',
                            }}
                            rowClassName={(row) =>
                                row.weighment_pdf_uploaded
                                    ? 'bg-green-200/80 dark:bg-green-900/40'
                                    : ''
                            }
                            onRowContextMenu={(event, row) => {
                                event.preventDefault();
                                setRowContextMenu({
                                    x: event.clientX,
                                    y: event.clientY,
                                    row,
                                });
                            }}
                            renderCell={(columnId, value, row) => {
                                if (columnId === 'indent_number') {
                                    return (
                                        <Link
                                            href={`/indents/${row.id}`}
                                            className="font-medium underline underline-offset-2"
                                        >
                                            {row.indent_number ?? '—'}
                                        </Link>
                                    );
                                }

                                if (columnId === 'expected_loading_date') {
                                    return row.expected_loading_date
                                        ? new Date(
                                              row.expected_loading_date,
                                          ).toLocaleDateString()
                                        : '—';
                                }

                                if (columnId === 'indent_date') {
                                    return row.indent_date
                                        ? new Date(
                                              row.indent_date,
                                          ).toLocaleDateString()
                                        : '—';
                                }

                                if (columnId === 'rake_serial_number') {
                                    if (row.rake_serial_number) {
                                        return row.rake_serial_number;
                                    }

                                    if (row.rake_number) {
                                        return (
                                            <span className="text-amber-600 dark:text-amber-400">
                                                {row.rake_number}
                                            </span>
                                        );
                                    }

                                    return '—';
                                }

                                if (columnId === 'rake_number') {
                                    if (!row.rake_number) {
                                        return '—';
                                    }

                                    return formatRakeSequence(row.rake_number, row);
                                }

                                if (columnId === 'fnr_number') {
                                    return (
                                        <GlossaryTerm term="FNR">
                                            {row.fnr_number ?? '—'}
                                        </GlossaryTerm>
                                    );
                                }

                                if (columnId === 'e_demand_reference_id') {
                                    return (
                                        <GlossaryTerm term="e-Demand">
                                            {row.e_demand_reference_id ?? '—'}
                                        </GlossaryTerm>
                                    );
                                }

                                if (columnId === 'state') {
                                    return (
                                        <span className="capitalize">
                                            {row.state ?? '—'}
                                        </span>
                                    );
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

            {rowContextMenu !== null && (
                <div
                    className="fixed z-50 min-w-48 rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
                    style={{
                        left: rowContextMenu.x,
                        top: rowContextMenu.y,
                    }}
                >
                    <button
                        type="button"
                        className="w-full rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent"
                        onClick={() => openAssignDialog(rowContextMenu.row)}
                    >
                        Assign Rake Number
                    </button>
                    <button
                        type="button"
                        className="w-full rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent"
                        onClick={() => {
                            setRowContextMenu(null);
                            router.visit(`/indents/${rowContextMenu.row.id}`);
                        }}
                    >
                        View
                    </button>
                </div>
            )}

            <Dialog
                open={previewOpen}
                onOpenChange={(open) => {
                    if (!open) {
                        closePreview();
                    }
                }}
            >
                <DialogContent
                    showCloseButton
                    className="top-[5%] flex max-h-[min(92vh,920px)] translate-y-0 flex-col gap-0 overflow-hidden sm:max-w-4xl"
                >
                    <DialogHeader className="shrink-0 border-b pb-4">
                        <DialogTitle>Confirm e-demand from PDF</DialogTitle>
                        <DialogDescription>
                            Review or edit the parsed fields, then create the e-demand. A linked
                            rake is created after you save.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="min-h-0 flex-1 overflow-y-auto pr-1">
                        {previewPrefill !== null && (
                            <IndentCreateForm
                                key={previewSessionId}
                                sidings={sidings}
                                power_plants={power_plants}
                                prefill={previewPrefill}
                                stagedPdfFile={stagedPdfFile}
                                variant="modal"
                                onCancel={closePreview}
                                onSubmitSuccess={closePreview}
                            />
                        )}
                    </div>
                </DialogContent>
            </Dialog>

            <Dialog
                open={assignDialogOpen}
                onOpenChange={(open) => {
                    if (!open) {
                        closeAssignDialog();
                    }
                }}
            >
                <DialogContent showCloseButton className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {selectedIndent?.indent_number ?? 'E-Demand'}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitAssignRakeNumber} className="space-y-4">
                        {assignErrorBanner !== null && (
                            <div
                                role="alert"
                                className="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
                            >
                                {assignErrorBanner}
                            </div>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="rake_serial_number">
                                Rake number
                            </Label>
                            <Input
                                id="rake_serial_number"
                                name="rake_serial_number"
                                value={assignRakeSerialNumber}
                                onChange={(event) =>
                                    setAssignRakeSerialNumber(event.target.value)
                                }
                                autoFocus
                            />
                            <InputError
                                message={assignFieldErrors.rake_serial_number}
                            />
                        </div>
                        <div className="flex justify-end">
                            <Button type="submit" disabled={assigningRakeNumber}>
                                {assigningRakeNumber
                                    ? `${assignButtonLabel}…`
                                    : assignButtonLabel}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
