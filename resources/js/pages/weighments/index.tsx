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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { useCan } from '@/hooks/use-can';
import { Head, router, usePage } from '@inertiajs/react';
import { CalendarDays, Eye, Scale, Upload } from 'lucide-react';
import { useCallback, useMemo, useRef, useState } from 'react';

interface WeighmentRow {
    id: number;
    rake_id: number;
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
    siding?: {
        name: string;
        code: string;
    } | null;
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
        errors?: { pdf?: string };
    }>().props;

    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [month, setMonth] = useState<string>(getCurrentMonthValue);
    const [rakes, setRakes] = useState<RakeOption[]>([]);
    const [rakesLoading, setRakesLoading] = useState(false);
    const [rakesError, setRakesError] = useState<string | null>(null);
    const [selectedRakeId, setSelectedRakeId] = useState<string>('');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Weighments', href: '/weighments' },
    ];

    const handleUploadClick = () => {
        if (!canUpload) {
            return;
        }
        fileInputRef.current?.click();
    };

    const resetDialogState = useCallback(() => {
        setIsDialogOpen(false);
        setSelectedFile(null);
        setSelectedRakeId('');
        setRakes([]);
        setRakesError(null);
        setMonth(getCurrentMonthValue());
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }, []);

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
        setIsDialogOpen(true);
        void fetchRakesForMonth(month);
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

    const rakeLabel = useCallback((rake: RakeOption): string => {
        const parts: string[] = [rake.rake_number];

        if (rake.siding?.name) {
            parts.push(`– ${rake.siding.name}`);
        } else if (rake.siding?.code) {
            parts.push(`– ${rake.siding.code}`);
        }

        if (rake.rr_actual_date) {
            parts.push(`(${rake.rr_actual_date})`);
        }

        return parts.join(' ');
    }, []);

    const dialogTitle = useMemo(() => {
        if (!selectedFile) {
            return 'Upload weighment PDF';
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

                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Weighments</h1>
                        <p className="text-muted-foreground">
                            Manage historical rake wagon weighment data
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        {canUpload && (
                            <>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept=".pdf"
                                    className="hidden"
                                    onChange={handleFileChange}
                                />
                                <Button
                                    onClick={handleUploadClick}
                                    disabled={uploading}
                                    data-pan="weighments-upload-pdf-button"
                                    className="flex items-center gap-2"
                                >
                                    <Upload className="h-4 w-4" />
                                    {uploading ? 'Uploading…' : 'Upload Document'}
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
                                    Upload a document to start viewing weighment data
                                </p>
                                {canUpload && (
                                    <Button
                                        onClick={handleUploadClick}
                                        variant="outline"
                                        data-pan="weighments-upload-first-document"
                                    >
                                        <Upload className="mr-2 h-4 w-4" />
                                        Upload First Document
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full border-collapse">
                                    <thead>
                                        <tr className="border-b">
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
                    open={isDialogOpen}
                    onOpenChange={(open) => {
                        if (!open) {
                            resetDialogState();
                        }
                    }}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{dialogTitle}</DialogTitle>
                            <DialogDescription>
                                Optionally select a rake to attach this weighment to. If you skip this step, the PDF
                                is imported as a historical weighment (same as before).
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="weighment-month">Rake month (filter)</Label>
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
                                    onValueChange={setSelectedRakeId}
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
                                <p className="text-xs text-muted-foreground">
                                    Leaving this blank imports the PDF without linking to an existing rake workflow.
                                </p>
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
        </AppLayout>
    );
}
