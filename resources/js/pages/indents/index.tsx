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
import type { DataTableResponse } from 'laravel-data-table';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FileText, Plus, Upload } from 'lucide-react';
import { useRef, useState } from 'react';

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

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'E-Demand', href: '/indents' },
    ];

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
                            rowClassName={(row) =>
                                row.weighment_pdf_uploaded
                                    ? 'bg-green-200/80 dark:bg-green-900/40'
                                    : ''
                            }
                            actions={[
                                {
                                    label: 'View',
                                    onClick: (row) => {
                                        router.visit(`/indents/${row.id}`);
                                    },
                                },
                            ]}
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
        </AppLayout>
    );
}
