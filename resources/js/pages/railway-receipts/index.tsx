import { DataTable } from 'laravel-data-table';
import type { DataTableResponse } from 'laravel-data-table';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { useCan } from '@/hooks/use-can';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { CalendarDays, Upload } from 'lucide-react';
import { useCallback, useMemo, useRef, useState } from 'react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface RrDocumentRow {
    id: number;
    rake_id: number;
    rr_number: string;
    rr_received_date: string;
    rr_weight_mt: string | null;
    document_status: string;
    rake_number: string | null;
    siding_name: string | null;
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
}

interface Props {
    tableData: DataTableResponse<RrDocumentRow>;
    sidings: Siding[];
    can_upload_rr?: boolean;
}

function getCurrentMonthValue(): string {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');

    return `${year}-${month}`;
}

export default function RailwayReceiptsIndex({
    tableData,
    can_upload_rr = false,
}: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [month, setMonth] = useState<string>(getCurrentMonthValue);
    const [rakes, setRakes] = useState<RakeOption[]>([]);
    const [rakesLoading, setRakesLoading] = useState(false);
    const [rakesError, setRakesError] = useState<string | null>(null);
    const [selectedRakeId, setSelectedRakeId] = useState<string>('');

    const {
        props: { errors },
    } = usePage<{ errors: Record<string, string | undefined> }>();
    const canUploadRr = useCan('sections.railway_receipts.upload');
    const canUpload = can_upload_rr && canUploadRr;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Railway Receipts', href: '/railway-receipts' },
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
        if (!canUpload) {
            return;
        }

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

        if (!selectedRakeId) {
            // No rake selected — upload without rake
        } else {
            const rakeId = Number.parseInt(selectedRakeId, 10);
            if (!Number.isNaN(rakeId)) {
                formData.append('rake_id', String(rakeId));
            }
        }

        router.post('/railway-receipts/upload', formData, {
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

        if (rake.loading_date) {
            parts.push(`(${rake.loading_date})`);
        }

        return parts.join(' ');
    }, []);

    const dialogTitle = useMemo(() => {
        if (!selectedFile) {
            return 'Attach RR PDF to a rake';
        }

        return `Attach “${selectedFile.name}” to a rake`;
    }, [selectedFile]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Railway Receipts" />
            <div className="space-y-6">
                <Heading
                    title="Railway Receipts"
                    description="RR documents and receipts by rake"
                />
                <div className="flex flex-wrap items-center gap-3">
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept=".pdf"
                        className="hidden"
                        onChange={handleFileChange}
                    />
                    {canUpload && (
                        <Button
                            onClick={handleUploadClick}
                            disabled={uploading}
                            data-pan="rr-upload-pdf-button"
                        >
                            <Upload className="mr-2 size-4" />
                            {uploading ? 'Uploading…' : 'Upload RR PDF'}
                        </Button>
                    )}
                    {errors?.pdf && (
                        <p className="text-sm text-destructive">
                            {errors.pdf}
                        </p>
                    )}
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>RR documents</CardTitle>
                        <CardDescription>
                            Filter by siding or rake. Click View to see
                            details.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable<RrDocumentRow>
                            tableData={tableData}
                            tableName="railway-receipts"
                            actions={[
                                {
                                    label: 'View',
                                    onClick: (row) =>
                                        router.visit(
                                            `/railway-receipts/${row.id}`,
                                        ),
                                },
                            ]}
                            renderCell={(columnId, _value, row) => {
                                if (columnId === 'rake_number') {
                                    return row.rake_number ?? '-';
                                }
                                if (columnId === 'siding_name') {
                                    return row.siding_name ?? '-';
                                }
                                if (columnId === 'rr_weight_mt') {
                                    return row.rr_weight_mt ?? '-';
                                }
                                return undefined;
                            }}
                        />
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
                            Optionally select a rake to attach this Railway
                            Receipt to. If you skip this step, the RR will be
                            uploaded without a rake.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="rr-month">
                                Loading month (filter)
                            </Label>
                            <div className="flex items-center gap-2">
                                <div className="relative inline-flex items-center">
                                    <CalendarDays className="pointer-events-none absolute left-2 size-4 text-muted-foreground" />
                                    <input
                                        id="rr-month"
                                        type="month"
                                        className="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 flex h-9 w-[11rem] rounded-md border pl-8 pr-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                        value={month}
                                        onChange={(event) => {
                                            const value =
                                                event.target.value ||
                                                getCurrentMonthValue();
                                            setMonth(value);
                                            void fetchRakesForMonth(value);
                                        }}
                                    />
                                </div>
                                {rakesLoading && (
                                    <span className="text-xs text-muted-foreground">
                                        Loading rakes…
                                    </span>
                                )}
                            </div>
                            {rakesError ? (
                                <p className="text-xs text-destructive">
                                    {rakesError}
                                </p>
                            ) : null}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="rake-select">
                                Attach to rake (optional)
                            </Label>
                            <Select
                                value={selectedRakeId}
                                onValueChange={setSelectedRakeId}
                            >
                                <SelectTrigger
                                    id="rake-select"
                                    className="min-w-[260px]"
                                >
                                    <SelectValue placeholder="No rake selected" />
                                </SelectTrigger>
                                <SelectContent>
                                    {rakes.length === 0 ? (
                                        <SelectItem value="__none" disabled>
                                            No rakes found for this month
                                        </SelectItem>
                                    ) : (
                                        rakes.map((rake) => (
                                            <SelectItem
                                                key={rake.id}
                                                value={String(rake.id)}
                                            >
                                                {rakeLabel(rake)}
                                            </SelectItem>
                                        ))
                                    )}
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-muted-foreground">
                                Leaving this blank will upload the RR without
                                linking it to a rake.
                            </p>
                        </div>
                    </div>
                    <DialogFooter className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={resetDialogState}
                            disabled={uploading}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            onClick={submitUpload}
                            disabled={uploading || !selectedFile}
                            data-pan="rr-upload-with-rake-button"
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
