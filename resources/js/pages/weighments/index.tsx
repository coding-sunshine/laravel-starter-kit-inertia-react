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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { useCan } from '@/hooks/use-can';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { CalendarDays, Download, Eye, PenLine, Scale, Upload } from 'lucide-react';
import { useCallback, useMemo, useRef, useState } from 'react';

interface WeighmentRow {
    id: number;
    rake_id: number;
    rake?: {
        rake_number: string | null;
    } | null;
    attempt_no: number;
    gross_weighment_datetime: string | null;
    tare_weighment_datetime: string | null;
    train_name: string | null;
    direction: string | null;
    commodity: string | null;
    from_station: string | null;
    to_station: string | null;
    priority_number: string | null;
    pdf_file_path: string | null;
    status: string;
    created_by: number | null;
    created_at: string;
    updated_at: string;
}

interface RakeOption {
    id: number;
    rake_number: string;
    rr_actual_date?: string | null;
    loading_date?: string | null;
    siding?: {
        name: string;
        code: string;
    } | null;
    /** Precomputed from rake siding + destination + priority (same as rake show manual form) */
    from_station?: string | null;
    to_station?: string | null;
    priority_number?: string | null;
}

interface Props {
    weighments?: WeighmentRow[];
}

function getCurrentMonthValue(): string {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');

    return `${year}-${month}`;
}

export default function WeighmentsIndex({ weighments = [] }: Props) {
    const canUpload = useCan('sections.weighments.upload');
    const { flash, errors } = usePage<{
        flash?: { success?: string };
        errors?: { pdf?: string; rake_id?: string; total_net_weight_mt?: string };
    }>().props;

    const fileInputRef = useRef<HTMLInputElement>(null);
    const downloadDialogTriggerRef = useRef<HTMLButtonElement>(null);
    const [uploading, setUploading] = useState(false);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [isUploadDialogOpen, setIsUploadDialogOpen] = useState(false);
    const [isManualDialogOpen, setIsManualDialogOpen] = useState(false);
    const [isDownloadDialogOpen, setIsDownloadDialogOpen] = useState(false);
    const [month, setMonth] = useState<string>(getCurrentMonthValue);
    const [rakes, setRakes] = useState<RakeOption[]>([]);
    const [rakesLoading, setRakesLoading] = useState(false);
    const [rakesError, setRakesError] = useState<string | null>(null);
    const [selectedRakeId, setSelectedRakeId] = useState<string>('');

    const manualForm = useForm({
        rake_id: 0,
        total_net_weight_mt: '',
        from_station: '',
        to_station: '',
        priority_number: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Weighments', href: '/weighments' },
    ];

    const openUploadDialog = () => {
        if (!canUpload) {
            return;
        }
        setIsManualDialogOpen(false);
        setSelectedFile(null);
        manualForm.reset();
        manualForm.clearErrors();
        setIsUploadDialogOpen(true);
        void fetchRakesForMonth(month);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const openManualEntryDialog = () => {
        if (!canUpload) {
            return;
        }
        setIsUploadDialogOpen(false);
        setSelectedFile(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
        manualForm.reset();
        manualForm.clearErrors();
        setSelectedRakeId('');
        setRakesError(null);
        setIsManualDialogOpen(true);
        void fetchRakesForMonth(month);
    };

    const openDownloadDialog = () => {
        if (!canUpload) {
            return;
        }
        setIsDownloadDialogOpen(true);
        void fetchRakesForMonth(month);
    };

    const resetDialogState = useCallback(() => {
        setIsUploadDialogOpen(false);
        setIsManualDialogOpen(false);
        setIsDownloadDialogOpen(false);
        setSelectedFile(null);
        setSelectedRakeId('');
        setRakes([]);
        setRakesError(null);
        setMonth(getCurrentMonthValue());
        manualForm.reset();
        manualForm.clearErrors();
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }, [manualForm]);

    const fetchRakesForMonth = useCallback(async (monthValue: string) => {
        setRakesLoading(true);
        setRakesError(null);
        try {
            const response = await fetch(
                `/railway-receipts/rakes?month=${encodeURIComponent(monthValue)}`,
                {
                    headers: {
                        Accept: 'application/json',
                    },
                },
            );

            if (!response.ok) {
                throw new Error(`Failed to load rakes (${response.status})`);
            }

            const json = (await response.json()) as {
                data?: RakeOption[];
            };

            setRakes(Array.isArray(json.data) ? json.data : []);
        } catch (error) {
            console.error(error);
            setRakes([]);
            setRakesError(
                'Could not load rakes for the selected month. You can still upload without selecting a rake.',
            );
        } finally {
            setRakesLoading(false);
        }
    }, []);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) {
            return;
        }

        setSelectedFile(file);
    };

    const submitUpload = useCallback(() => {
        if (!canUpload) {
            return;
        }
        if (!selectedFile) {
            return;
        }

        setUploading(true);

        const formData = new FormData();
        formData.append('pdf', selectedFile);

        if (selectedRakeId) {
            const rakeId = Number.parseInt(selectedRakeId, 10);
            if (!Number.isNaN(rakeId)) {
                formData.append('rake_id', String(rakeId));
            }
        }

        router.post('/weighments/import', formData, {
            forceFormData: true,
            onFinish: () => {
                setUploading(false);
                resetDialogState();
            },
        });
    }, [canUpload, resetDialogState, selectedFile, selectedRakeId]);

    const submitManual = useCallback(() => {
        if (!canUpload) {
            return;
        }
        if (!selectedRakeId) {
            setRakesError('Select a rake to save a manual weighment.');
            return;
        }
        const rakeId = Number.parseInt(selectedRakeId, 10);
        if (Number.isNaN(rakeId)) {
            setRakesError('Invalid rake selection.');
            return;
        }

        manualForm.setData({
            rake_id: rakeId,
            total_net_weight_mt: manualForm.data.total_net_weight_mt,
            from_station: manualForm.data.from_station,
            to_station: manualForm.data.to_station,
            priority_number: manualForm.data.priority_number,
        });

        manualForm.post('/weighments/manual', {
            preserveScroll: true,
            onFinish: () => {
                resetDialogState();
            },
        });
    }, [canUpload, manualForm, resetDialogState, selectedRakeId]);

    const downloadTemplate = useCallback(() => {
        if (!selectedRakeId) {
            setRakesError('Select a rake to download the Excel template.');
            return;
        }
        const rakeId = Number.parseInt(selectedRakeId, 10);
        if (Number.isNaN(rakeId)) {
            setRakesError('Invalid rake selection.');
            return;
        }
        window.location.href = `/weighments/template-xlsx?rake_id=${encodeURIComponent(String(rakeId))}`;
        resetDialogState();
    }, [resetDialogState, selectedRakeId]);

    const rakeLabel = useCallback((rake: RakeOption): string => {
        const parts: string[] = [rake.rake_number];

        if (rake.siding?.name) {
            parts.push(`– ${rake.siding.name}`);
        } else if (rake.siding?.code) {
            parts.push(`– ${rake.siding.code}`);
        }

        if (rake.loading_date) {
            parts.push(`(${rake.loading_date})`);
        }

        return parts.join(' ');
    }, []);

    const uploadDialogTitle = useMemo(() => {
        if (!selectedFile) {
            return 'Upload weighment document';
        }

        return `Upload “${selectedFile.name}”`;
    }, [selectedFile]);

    const handleView = (id: number) => {
        router.visit(`/weighments/${id}`);
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

                {(errors?.rake_id || errors?.total_net_weight_mt) && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                        {errors.rake_id && <p>{errors.rake_id}</p>}
                        {errors.total_net_weight_mt && <p>{errors.total_net_weight_mt}</p>}
                    </div>
                )}

                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Weighments</h1>
                        <p className="text-muted-foreground">
                            Manage historical rake wagon weighment data
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        {canUpload && (
                            <>
                                <Button
                                    onClick={openUploadDialog}
                                    disabled={uploading}
                                    data-pan="weighments-upload-pdf-button"
                                    className="flex items-center gap-2"
                                >
                                    <Upload className="h-4 w-4" />
                                    {uploading ? 'Uploading…' : 'Upload Document'}
                                </Button>
                                <Button
                                    variant="secondary"
                                    onClick={openManualEntryDialog}
                                    disabled={uploading || manualForm.processing}
                                    data-pan="weighments-manual-entry-button"
                                    title="Record net weight without a document; upload PDF or Excel later to add wagon lines."
                                    className="flex items-center gap-2"
                                >
                                    <PenLine className="h-4 w-4" />
                                    Manual entry (no PDF yet)
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={openDownloadDialog}
                                    disabled={uploading}
                                    data-pan="weighments-download-xlsx-template-button"
                                    className="flex items-center gap-2"
                                    ref={downloadDialogTriggerRef}
                                >
                                    <Download className="h-4 w-4" />
                                    Download Excel Template
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Scale className="h-5 w-5" />
                            Rake Weighments
                        </CardTitle>
                        <CardDescription>
                            View and manage rake weighment data from uploaded documents
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {weighments.length === 0 ? (
                            <div className="py-8 text-center">
                                <Scale className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-medium">No weighment data</h3>
                                <p className="mb-4 text-muted-foreground">
                                    Upload a document, or enter totals manually if you do not have a file yet.
                                </p>
                                {canUpload && (
                                    <div className="flex flex-wrap items-center justify-center gap-2">
                                        <Button
                                            onClick={openUploadDialog}
                                            variant="outline"
                                            data-pan="weighments-upload-first-document"
                                        >
                                            <Upload className="mr-2 h-4 w-4" />
                                            Upload document
                                        </Button>
                                        <Button
                                            onClick={openManualEntryDialog}
                                            variant="outline"
                                            data-pan="weighments-empty-manual-entry"
                                            title="Record net weight without a document; upload PDF or Excel later to add wagon lines."
                                        >
                                            <PenLine className="mr-2 h-4 w-4" />
                                            Manual entry (no PDF yet)
                                        </Button>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full border-collapse">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="p-2 text-left">Rake #</th>
                                            <th className="p-2 text-left">Train Name</th>
                                            <th className="p-2 text-left">Direction</th>
                                            <th className="p-2 text-left">Commodity</th>
                                            <th className="p-2 text-left">From Station</th>
                                            <th className="p-2 text-left">To Station</th>
                                            <th className="p-2 text-left">Priority Number</th>
                                            <th className="p-2 text-left">Gross Weighment</th>
                                            <th className="p-2 text-left">Tare Weighment</th>
                                            <th className="p-2 text-left">Attempt No</th>
                                            <th className="p-2 text-left">Status</th>
                                            <th className="p-2 text-left">Created At</th>
                                            <th className="p-2 text-left">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {weighments.map((weighment) => (
                                            <tr key={weighment.id} className="border-b hover:bg-muted/50">
                                                <td className="p-2">
                                                    {weighment.rake?.rake_number
                                                        ? weighment.rake.rake_number
                                                        : 'N/A'}
                                                </td>
                                                <td className="p-2">{weighment.train_name || '-'}</td>
                                                <td className="p-2">{weighment.direction || '-'}</td>
                                                <td className="p-2">{weighment.commodity || '-'}</td>
                                                <td className="p-2">{weighment.from_station || '-'}</td>
                                                <td className="p-2">{weighment.to_station || '-'}</td>
                                                <td className="p-2">{weighment.priority_number || '-'}</td>
                                                <td className="p-2">
                                                    {weighment.gross_weighment_datetime
                                                        ? new Date(
                                                              weighment.gross_weighment_datetime,
                                                          ).toLocaleString()
                                                        : '-'}
                                                </td>
                                                <td className="p-2">
                                                    {weighment.tare_weighment_datetime
                                                        ? new Date(
                                                              weighment.tare_weighment_datetime,
                                                          ).toLocaleString()
                                                        : '-'}
                                                </td>
                                                <td className="p-2">{weighment.attempt_no}</td>
                                                <td className="p-2">
                                                    <span
                                                        className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${
                                                            weighment.status === 'success'
                                                                ? 'bg-green-100 text-green-800'
                                                                : 'bg-red-100 text-red-800'
                                                        }`}
                                                    >
                                                        {weighment.status}
                                                    </span>
                                                </td>
                                                <td className="p-2">
                                                    {new Date(weighment.created_at).toLocaleString()}
                                                </td>
                                                <td className="p-2">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="inline-flex items-center gap-1"
                                                        onClick={() => handleView(weighment.id)}
                                                    >
                                                        <Eye className="h-3 w-3" />
                                                        View
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {canUpload && (
                <Dialog
                    open={isUploadDialogOpen}
                    onOpenChange={(open) => {
                        if (!open) {
                            resetDialogState();
                        }
                    }}
                >
                    <DialogContent className="max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>{uploadDialogTitle}</DialogTitle>
                            <DialogDescription>
                                Choose a loading month, optionally attach a rake, then select a PDF or Excel file. Leave
                                rake blank to import a standalone historical PDF only.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="weighment-month">Loading month (filter)</Label>
                                <div className="flex items-center gap-2">
                                    <div className="relative inline-flex items-center">
                                        <CalendarDays className="pointer-events-none absolute left-2 size-4 text-muted-foreground" />
                                        <input
                                            id="weighment-month"
                                            type="month"
                                            className="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex h-9 w-[11rem] rounded-md border pl-8 pr-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                            value={month}
                                            onChange={(event) => {
                                                const value = event.target.value || getCurrentMonthValue();
                                                setMonth(value);
                                                void fetchRakesForMonth(value);
                                            }}
                                        />
                                    </div>
                                    {rakesLoading && (
                                        <span className="text-xs text-muted-foreground">Loading rakes…</span>
                                    )}
                                </div>
                                {rakesError ? <p className="text-xs text-destructive">{rakesError}</p> : null}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="weighment-rake-select">Attach to rake (optional)</Label>
                                <Select
                                    value={selectedRakeId || undefined}
                                    onValueChange={(v) => {
                                        setSelectedRakeId(v);
                                        setRakesError(null);
                                    }}
                                >
                                    <SelectTrigger id="weighment-rake-select" className="min-w-[260px]">
                                        <SelectValue placeholder="No rake selected" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {rakes.length === 0 ? (
                                            <SelectItem value="__none" disabled>
                                                No rakes found for this month
                                            </SelectItem>
                                        ) : (
                                            rakes.map((rake) => (
                                                <SelectItem key={rake.id} value={String(rake.id)}>
                                                    {rakeLabel(rake)}
                                                </SelectItem>
                                            ))
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="weighment-file-input">File</Label>
                                <Input
                                    ref={fileInputRef}
                                    id="weighment-file-input"
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
                        </div>
                        <DialogFooter className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={resetDialogState}
                                disabled={uploading}
                                data-pan="weighments-upload-dialog-cancel"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="button"
                                onClick={submitUpload}
                                disabled={uploading || !selectedFile}
                                data-pan="weighments-upload-with-rake-button"
                            >
                                {uploading ? 'Uploading…' : 'Upload'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            )}

            {canUpload && (
                <Dialog
                    open={isManualDialogOpen}
                    onOpenChange={(open) => {
                        if (!open) {
                            resetDialogState();
                        }
                    }}
                >
                    <DialogContent className="max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Manual weighment entry</DialogTitle>
                            <DialogDescription>
                                Enter total net weight and optional route details when you do not have a PDF or Excel
                                slip yet. Stock is updated from this net weight; you can upload the document later from
                                the rake page or here once it matches this total exactly.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="weighment-month-manual">Loading month (filter)</Label>
                                <div className="flex items-center gap-2">
                                    <div className="relative inline-flex items-center">
                                        <CalendarDays className="pointer-events-none absolute left-2 size-4 text-muted-foreground" />
                                        <input
                                            id="weighment-month-manual"
                                            type="month"
                                            className="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex h-9 w-[11rem] rounded-md border pl-8 pr-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                            value={month}
                                            onChange={(event) => {
                                                const value = event.target.value || getCurrentMonthValue();
                                                setMonth(value);
                                                void fetchRakesForMonth(value);
                                            }}
                                        />
                                    </div>
                                    {rakesLoading && (
                                        <span className="text-xs text-muted-foreground">Loading rakes…</span>
                                    )}
                                </div>
                                {rakesError ? <p className="text-xs text-destructive">{rakesError}</p> : null}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="weighment-rake-select-manual">Rake (required)</Label>
                                <Select
                                    value={selectedRakeId || undefined}
                                    onValueChange={(v) => {
                                        setSelectedRakeId(v);
                                        setRakesError(null);
                                        const opt = rakes.find((r) => String(r.id) === v);
                                        if (opt) {
                                            manualForm.setData({
                                                rake_id: Number.parseInt(v, 10) || 0,
                                                total_net_weight_mt: manualForm.data.total_net_weight_mt,
                                                from_station: opt.from_station ?? '',
                                                to_station: opt.to_station ?? '',
                                                priority_number: opt.priority_number ?? '',
                                            });
                                        }
                                    }}
                                >
                                    <SelectTrigger id="weighment-rake-select-manual" className="min-w-[260px]">
                                        <SelectValue placeholder="Select a rake" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {rakes.length === 0 ? (
                                            <SelectItem value="__none" disabled>
                                                No rakes found for this month
                                            </SelectItem>
                                        ) : (
                                            rakes.map((rake) => (
                                                <SelectItem key={rake.id} value={String(rake.id)}>
                                                    {rakeLabel(rake)}
                                                </SelectItem>
                                            ))
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-3 rounded-md border p-3">
                                <div className="grid gap-3 md:grid-cols-2">
                                    <div className="md:col-span-2">
                                        <Label htmlFor="manual-total-net">Total net weight (MT)</Label>
                                        <Input
                                            id="manual-total-net"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            value={manualForm.data.total_net_weight_mt}
                                            onChange={(e) =>
                                                manualForm.setData('total_net_weight_mt', e.target.value)
                                            }
                                            data-pan="weighments-dialog-manual-net-mt"
                                        />
                                        <InputError message={manualForm.errors.total_net_weight_mt} />
                                    </div>
                                    <div>
                                        <Label htmlFor="manual-from">From station</Label>
                                        <Input
                                            id="manual-from"
                                            value={manualForm.data.from_station}
                                            onChange={(e) => manualForm.setData('from_station', e.target.value)}
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="manual-to">To station</Label>
                                        <Input
                                            id="manual-to"
                                            value={manualForm.data.to_station}
                                            onChange={(e) => manualForm.setData('to_station', e.target.value)}
                                        />
                                    </div>
                                    <div className="md:col-span-2">
                                        <Label htmlFor="manual-priority">Priority number</Label>
                                        <Input
                                            id="manual-priority"
                                            value={manualForm.data.priority_number}
                                            onChange={(e) =>
                                                manualForm.setData('priority_number', e.target.value)
                                            }
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <DialogFooter className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={resetDialogState}
                                disabled={manualForm.processing}
                                data-pan="weighments-manual-dialog-cancel"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="button"
                                onClick={submitManual}
                                disabled={
                                    manualForm.processing || !selectedRakeId || !manualForm.data.total_net_weight_mt
                                }
                                data-pan="weighments-dialog-save-manual"
                            >
                                {manualForm.processing ? 'Saving…' : 'Save manual weighment'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            )}

            {canUpload && (
                <Dialog
                    open={isDownloadDialogOpen}
                    onOpenChange={(open) => {
                        if (!open) {
                            resetDialogState();
                        }
                    }}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Download weighment Excel template</DialogTitle>
                            <DialogDescription>
                                Select a rake to download a prefilled Excel template. Fill it and upload it back if
                                PDF parsing is not supported.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="weighment-month-download">Loading month (filter)</Label>
                                <div className="flex items-center gap-2">
                                    <div className="relative inline-flex items-center">
                                        <CalendarDays className="pointer-events-none absolute left-2 size-4 text-muted-foreground" />
                                        <input
                                            id="weighment-month-download"
                                            type="month"
                                            className="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex h-9 w-[11rem] rounded-md border pl-8 pr-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                            value={month}
                                            onChange={(event) => {
                                                const value = event.target.value || getCurrentMonthValue();
                                                setMonth(value);
                                                void fetchRakesForMonth(value);
                                            }}
                                        />
                                    </div>
                                    {rakesLoading && (
                                        <span className="text-xs text-muted-foreground">Loading rakes…</span>
                                    )}
                                </div>
                                {rakesError ? <p className="text-xs text-destructive">{rakesError}</p> : null}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="weighment-rake-select-download">Rake</Label>
                                <Select value={selectedRakeId || undefined} onValueChange={setSelectedRakeId}>
                                    <SelectTrigger id="weighment-rake-select-download" className="min-w-[260px]">
                                        <SelectValue placeholder="Select a rake" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {rakes.length === 0 ? (
                                            <SelectItem value="__none" disabled>
                                                No rakes found for this month
                                            </SelectItem>
                                        ) : (
                                            rakes.map((rake) => (
                                                <SelectItem key={rake.id} value={String(rake.id)}>
                                                    {rakeLabel(rake)}
                                                </SelectItem>
                                            ))
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <DialogFooter className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={resetDialogState}
                                data-pan="weighments-download-xlsx-dialog-cancel"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="button"
                                onClick={downloadTemplate}
                                disabled={!selectedRakeId}
                                data-pan="weighments-download-xlsx-template-confirm"
                            >
                                Download
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            )}
        </AppLayout>
    );
}
