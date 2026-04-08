import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Scale, CheckCircle, Clock, AlertTriangle, Upload, FileText, Trash2, Eye } from 'lucide-react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { manualWeighmentFieldsFromRake } from '@/lib/manual-weighment-from-rake';
import { useEffect, useMemo, useState } from 'react';

interface WeighmentRecord {
    id: number;
    weighment_time: string | null;
    total_weight_mt: string | number | null;
    status: string | null;
    train_speed_kmph: number | string | null;
    attempt_no: number;
    from_station?: string | null;
    to_station?: string | null;
    priority_number?: string | number | null;
    isPendingDocument?: boolean;
    wagonWeights?: Array<{
        wagon_id: number;
        gross_weight_mt: number;
        net_weight_mt: number;
        wagon: {
            id: number;
            wagon_number: string;
            wagon_sequence: number;
            pcc_weight_mt?: string | number | null;
        };
    }>;
}

interface WeighmentWorkflowProps {
    rake: {
        id: number;
        state: string;
        wagons: Array<{ id: number }>;
        weighments?: WeighmentRecord[];
        siding?: { name?: string | null; code?: string | null } | null;
        destination?: string | null;
        destination_code?: string | null;
        priority_number?: number | null;
    };
    disabled: boolean;
}

export function WeighmentWorkflow({ rake, disabled }: WeighmentWorkflowProps) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const [pdfFile, setPdfFile] = useState<File | null>(null);

    const { setData, post, processing, reset } = useForm({
        weighment_pdf: null as File | null,
    });

    const manualForm = useForm({
        total_net_weight_mt: '',
        from_station: '',
        to_station: '',
        priority_number: '',
    });

    const editManualForm = useForm({
        total_net_weight_mt: '',
        from_station: '',
        to_station: '',
        priority_number: '',
    });

    const latestWeighment = useMemo(() => {
        if (!rake.weighments || rake.weighments.length === 0) {
            return undefined;
        }

        return [...rake.weighments].sort((a, b) => a.attempt_no - b.attempt_no)[rake.weighments.length - 1];
    }, [rake.weighments]);

    const hasWeighment = !!latestWeighment;
    const isPendingDocument = latestWeighment?.isPendingDocument === true;

    useEffect(() => {
        const f = manualWeighmentFieldsFromRake(rake);
        manualForm.setData('from_station', f.from_station);
        manualForm.setData('to_station', f.to_station);
        manualForm.setData('priority_number', f.priority_number);
    }, [rake.id]); // eslint-disable-line react-hooks/exhaustive-deps -- prefill when rake id changes only

    useEffect(() => {
        if (!latestWeighment || !isPendingDocument) {
            return;
        }
        editManualForm.setData({
            total_net_weight_mt:
                latestWeighment.total_weight_mt != null && latestWeighment.total_weight_mt !== ''
                    ? String(latestWeighment.total_weight_mt)
                    : '',
            from_station: latestWeighment.from_station ?? '',
            to_station: latestWeighment.to_station ?? '',
            priority_number:
                latestWeighment.priority_number != null && latestWeighment.priority_number !== ''
                    ? String(latestWeighment.priority_number)
                    : '',
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps -- sync when server sends updated weighment
    }, [latestWeighment?.id, isPendingDocument, latestWeighment?.total_weight_mt]);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setPdfFile(file);
            setData('weighment_pdf', file);
        }
    };

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        post(`/rakes/${rake.id}/weighments`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setPdfFile(null);
            },
        });
    };

    const handleManualSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        manualForm.post(`/rakes/${rake.id}/weighments/manual`, {
            preserveScroll: true,
            onSuccess: () => {
                manualForm.reset();
            },
        });
    };

    const handleEditManualSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (!latestWeighment) {
            return;
        }
        editManualForm.patch(`/rakes/${rake.id}/weighments/${latestWeighment.id}`, {
            preserveScroll: true,
        });
    };

    const isFullyDocumented = hasWeighment && !isPendingDocument;
    const isCompleted = isFullyDocumented && latestWeighment?.status === 'success';

    const getStatusIcon = () => {
        if (!hasWeighment) return <Clock className="h-4 w-4" />;
        if (isPendingDocument) return <AlertTriangle className="h-4 w-4 text-amber-600" />;
        if (isCompleted) return <CheckCircle className="h-4 w-4 text-green-600" />;
        return <AlertTriangle className="h-4 w-4 text-orange-600" />;
    };

    const getStatusText = () => {
        if (!hasWeighment) return 'Not Weighed';
        if (isPendingDocument) return 'Awaiting document';
        if (isCompleted) return 'Completed';
        return latestWeighment?.status || 'Processing';
    };

    const getStatusVariant = () => {
        if (!hasWeighment) return 'secondary';
        if (isPendingDocument) return 'outline';
        if (isCompleted) return 'default';
        return 'destructive';
    };

    const renderUploadForm = (context: 'initial' | 'pending') => (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div>
                <Label htmlFor={context === 'pending' ? 'weighment_pdf_pending' : 'weighment_pdf'}>
                    Weighment PDF or Excel
                </Label>
                <div className="mt-2 flex items-center gap-4">
                    <Input
                        id={context === 'pending' ? 'weighment_pdf_pending' : 'weighment_pdf'}
                        type="file"
                        accept=".pdf,.xlsx"
                        onChange={handleFileChange}
                        disabled={disabled}
                        className="file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100"
                    />
                    {pdfFile && (
                        <div className="flex items-center gap-2 text-sm text-green-600">
                            <FileText className="h-4 w-4" />
                            {pdfFile.name}
                        </div>
                    )}
                </div>
                <p className="mt-1 text-xs text-muted-foreground">
                    {context === 'pending'
                        ? 'The slip may show a different total net than your approximate manual entry; stock will be adjusted automatically when the document is processed.'
                        : 'Upload weighment slip as PDF. If unsupported, upload the filled Excel template (XLSX).'}
                </p>
                <InputError message={errors?.weighment_pdf} />
            </div>

            <div className="flex items-center justify-between rounded-md border p-3 text-sm">
                <div>
                    <div className="font-medium">Need the Excel template?</div>
                    <div className="text-xs text-muted-foreground">
                        Download a prefilled XLSX for this rake, fill it, then upload it here.
                    </div>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => {
                        window.location.href = `/weighments/template-xlsx?rake_id=${encodeURIComponent(String(rake.id))}`;
                    }}
                    data-pan="rake-weighment-download-xlsx-template"
                >
                    Download XLSX
                </Button>
            </div>

            <div className="flex justify-end space-x-2">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => {
                        reset();
                        setPdfFile(null);
                    }}
                    disabled={disabled}
                >
                    Cancel
                </Button>
                <Button type="submit" disabled={disabled || processing || !pdfFile} data-pan="rake-weighment-upload-document-submit">
                    <Upload className="mr-2 h-4 w-4" />
                    Upload & Process
                </Button>
            </div>
        </form>
    );

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Scale className="h-5 w-5" />
                        Rake Weighment
                    </div>
                    <div className="flex items-center gap-2">
                        {getStatusIcon()}
                        <Badge variant={getStatusVariant()}>{getStatusText()}</Badge>
                    </div>
                </CardTitle>
                <CardDescription>
                    Record net weight manually if the slip is not ready, then upload the document when available.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {!hasWeighment ? (
                    <div className="space-y-8">
                        {renderUploadForm('initial')}
                        <div className="relative">
                            <div className="absolute inset-0 flex items-center">
                                <span className="w-full border-t" />
                            </div>
                            <div className="relative flex justify-center text-xs uppercase">
                                <span className="bg-card px-2 text-muted-foreground">Or enter manually</span>
                            </div>
                        </div>
                        <form onSubmit={handleManualSubmit} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="md:col-span-2">
                                    <Label htmlFor="total_net_weight_mt">Total net weight (MT)</Label>
                                    <Input
                                        id="total_net_weight_mt"
                                        name="total_net_weight_mt"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={manualForm.data.total_net_weight_mt}
                                        onChange={(e) => manualForm.setData('total_net_weight_mt', e.target.value)}
                                        required
                                        disabled={disabled}
                                        data-pan="rake-weighment-manual-net-mt"
                                    />
                                    <InputError message={manualForm.errors.total_net_weight_mt} />
                                </div>
                                <div>
                                    <Label htmlFor="from_station">From station</Label>
                                    <Input
                                        id="from_station"
                                        name="from_station"
                                        value={manualForm.data.from_station}
                                        onChange={(e) => manualForm.setData('from_station', e.target.value)}
                                        disabled={disabled}
                                    />
                                    <InputError message={manualForm.errors.from_station} />
                                </div>
                                <div>
                                    <Label htmlFor="to_station">To station</Label>
                                    <Input
                                        id="to_station"
                                        name="to_station"
                                        value={manualForm.data.to_station}
                                        onChange={(e) => manualForm.setData('to_station', e.target.value)}
                                        disabled={disabled}
                                    />
                                    <InputError message={manualForm.errors.to_station} />
                                </div>
                                <div className="md:col-span-2">
                                    <Label htmlFor="priority_number">Priority number</Label>
                                    <Input
                                        id="priority_number"
                                        name="priority_number"
                                        value={manualForm.data.priority_number}
                                        onChange={(e) => manualForm.setData('priority_number', e.target.value)}
                                        disabled={disabled}
                                    />
                                    <InputError message={manualForm.errors.priority_number} />
                                </div>
                            </div>
                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    variant="secondary"
                                    disabled={disabled || manualForm.processing}
                                    data-pan="rake-weighment-manual-submit"
                                >
                                    Save manual weighment
                                </Button>
                            </div>
                        </form>
                    </div>
                ) : isPendingDocument ? (
                    <div className="space-y-6">
                        <div className="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                            Approximate manual net (<strong>{String(latestWeighment?.total_weight_mt ?? '')} MT</strong>)
                            is saved and stock has been updated. When you upload the official PDF or XLSX, totals from
                            the document will replace this entry and coal stock will be adjusted by the difference.
                        </div>
                        <div className="grid gap-4 md:grid-cols-3">
                            <div>
                                <Label>Total net (MT)</Label>
                                <p className="text-lg font-bold">{latestWeighment?.total_weight_mt ?? '—'}</p>
                            </div>
                            <div>
                                <Label>Gross weighment</Label>
                                <p className="text-sm">
                                    {latestWeighment?.weighment_time
                                        ? new Date(latestWeighment.weighment_time).toLocaleString()
                                        : '—'}
                                </p>
                            </div>
                            <div>
                                <Label>Train speed</Label>
                                <p className="text-sm">{latestWeighment?.train_speed_kmph ?? '—'} km/h</p>
                            </div>
                        </div>
                        <form onSubmit={handleEditManualSubmit} className="space-y-4 rounded-md border p-4">
                            <div className="text-sm font-medium">Edit manual totals</div>
                            <p className="text-xs text-muted-foreground">
                                Adjust net weight or header fields before the document is uploaded. Stock will be
                                updated by the difference from the previous saved net.
                            </p>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="md:col-span-2">
                                    <Label htmlFor="edit_total_net_weight_mt">Total net weight (MT)</Label>
                                    <Input
                                        id="edit_total_net_weight_mt"
                                        name="total_net_weight_mt"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={editManualForm.data.total_net_weight_mt}
                                        onChange={(e) => editManualForm.setData('total_net_weight_mt', e.target.value)}
                                        required
                                        disabled={disabled}
                                        data-pan="rake-weighment-edit-net-mt"
                                    />
                                    <InputError message={editManualForm.errors.total_net_weight_mt} />
                                </div>
                                <div>
                                    <Label htmlFor="edit_from_station">From station</Label>
                                    <Input
                                        id="edit_from_station"
                                        name="from_station"
                                        value={editManualForm.data.from_station}
                                        onChange={(e) => editManualForm.setData('from_station', e.target.value)}
                                        disabled={disabled}
                                    />
                                    <InputError message={editManualForm.errors.from_station} />
                                </div>
                                <div>
                                    <Label htmlFor="edit_to_station">To station</Label>
                                    <Input
                                        id="edit_to_station"
                                        name="to_station"
                                        value={editManualForm.data.to_station}
                                        onChange={(e) => editManualForm.setData('to_station', e.target.value)}
                                        disabled={disabled}
                                    />
                                    <InputError message={editManualForm.errors.to_station} />
                                </div>
                                <div className="md:col-span-2">
                                    <Label htmlFor="edit_priority_number">Priority number</Label>
                                    <Input
                                        id="edit_priority_number"
                                        name="priority_number"
                                        value={editManualForm.data.priority_number}
                                        onChange={(e) => editManualForm.setData('priority_number', e.target.value)}
                                        disabled={disabled}
                                    />
                                    <InputError message={editManualForm.errors.priority_number} />
                                </div>
                            </div>
                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    variant="secondary"
                                    disabled={disabled || editManualForm.processing}
                                    data-pan="rake-weighment-edit-manual-submit"
                                >
                                    Save changes
                                </Button>
                            </div>
                        </form>
                        {renderUploadForm('pending')}
                    </div>
                ) : (
                    <div className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-3">
                            <div>
                                <Label>Weighment Time</Label>
                                <p className="text-sm">
                                    {latestWeighment?.weighment_time
                                        ? new Date(latestWeighment.weighment_time).toLocaleString()
                                        : '—'}
                                </p>
                            </div>
                            <div>
                                <Label>Total Weight</Label>
                                <p className="text-lg font-bold">{latestWeighment?.total_weight_mt} MT</p>
                            </div>
                            <div>
                                <Label>Train Speed</Label>
                                <p className="text-sm">{latestWeighment?.train_speed_kmph ?? '—'} km/h</p>
                            </div>
                        </div>

                        <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                            <div
                                className={`flex items-center gap-2 text-sm ${
                                    isCompleted ? 'text-green-600' : 'text-orange-600'
                                }`}
                            >
                                {isCompleted ? (
                                    <>
                                        <CheckCircle className="h-4 w-4" />
                                        Weighment completed successfully
                                    </>
                                ) : (
                                    <>
                                        <AlertTriangle className="h-4 w-4" />
                                        Weighment failed - attempt #{latestWeighment?.attempt_no}
                                    </>
                                )}
                            </div>
                            <div className="flex items-center gap-2">
                                <Link
                                    href={`/weighments/${latestWeighment?.id}`}
                                    className="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-medium hover:bg-muted"
                                >
                                    <Eye className="mr-1.5 h-3.5 w-3.5" />
                                    View data
                                </Link>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        if (!window.confirm('Delete all weighment data for this rake?')) {
                                            return;
                                        }

                                        router.delete(`/rakes/${rake.id}/weighments`, {
                                            preserveScroll: true,
                                        });
                                    }}
                                    disabled={disabled || processing}
                                >
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Delete Weighment
                                </Button>
                            </div>
                        </div>
                    </div>
                )}

                {disabled && !hasWeighment && (
                    <div className="py-4 text-center text-sm text-muted-foreground">
                        Complete guard inspection to enable weighment
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
