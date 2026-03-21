import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import { FileText, CheckCircle, Clock, Download, Eye, Upload, Trash2 } from 'lucide-react';
import { Link, router, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';

interface RrDocumentRecord {
    id: number;
    rr_number: string;
    rr_received_date: string;
    rr_weight_mt: string | null;
    document_status: string;
    diverrt_destination_id?: number | null;
}

interface DiverrtDestinationRow {
    id: number;
    location: string;
}

interface RrDocumentWorkflowProps {
    rake: {
        id: number;
        state: string;
        is_diverted?: boolean;
        rrDocuments?: RrDocumentRecord[];
        diverrtDestinations?: DiverrtDestinationRow[];
    };
    disabled: boolean;
}

function findDocForSlot(
    rrDocuments: RrDocumentRecord[] | undefined,
    diverrtDestinationId: number | null,
): RrDocumentRecord | undefined {
    const docs = rrDocuments ?? [];
    if (diverrtDestinationId === null) {
        return docs.find((d) => d.diverrt_destination_id == null);
    }
    return docs.find((d) => d.diverrt_destination_id === diverrtDestinationId);
}

function RrSlotCard({
    label,
    description,
    doc,
    disabled,
    uploading,
    panUpload,
    onFile,
}: {
    label: string;
    description?: string;
    doc: RrDocumentRecord | undefined;
    disabled: boolean;
    uploading: boolean;
    panUpload: string;
    onFile: (file: File) => void;
}) {
    const inputRef = useRef<HTMLInputElement>(null);

    return (
        <div className="rounded-lg border bg-muted/30 p-4 space-y-3">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p className="text-sm font-medium">{label}</p>
                    {description ? (
                        <p className="text-xs text-muted-foreground mt-0.5">{description}</p>
                    ) : null}
                </div>
                {doc ? (
                    <Badge variant="default">Uploaded</Badge>
                ) : (
                    <Badge variant="secondary">Pending</Badge>
                )}
            </div>
            {!doc ? (
                !disabled && (
                    <>
                        <input
                            ref={inputRef}
                            type="file"
                            accept=".pdf"
                            className="hidden"
                            onChange={(e) => {
                                const file = e.target.files?.[0];
                                if (file) onFile(file);
                                e.target.value = '';
                            }}
                        />
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            onClick={() => inputRef.current?.click()}
                            disabled={uploading}
                            data-pan={panUpload}
                        >
                            <Upload className="mr-2 h-4 w-4" />
                            {uploading ? 'Uploading…' : 'Upload RR PDF'}
                        </Button>
                    </>
                )
            ) : (
                <div className="space-y-2">
                    <div className="grid gap-2 sm:grid-cols-2">
                        <div>
                            <Label className="text-xs text-muted-foreground">RR number</Label>
                            <p className="font-semibold">{doc.rr_number}</p>
                        </div>
                        <div>
                            <Label className="text-xs text-muted-foreground">Received</Label>
                            <p className="text-sm">
                                {new Date(doc.rr_received_date).toLocaleDateString()}
                            </p>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" size="sm">
                            <Link href={`/railway-receipts/${doc.id}`}>
                                <Eye className="mr-2 h-4 w-4" />
                                View RR
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <a href={`/railway-receipts/${doc.id}/pdf`} target="_blank" rel="noreferrer">
                                <Download className="mr-2 h-4 w-4" />
                                PDF
                            </a>
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}

export function RrDocumentWorkflow({ rake, disabled }: RrDocumentWorkflowProps) {
    const {
        props: { errors },
    } = usePage<{
        errors?: Record<string, string>;
    }>();
    const [uploadingKey, setUploadingKey] = useState<string | null>(null);
    const [diversionModeBusy, setDiversionModeBusy] = useState(false);
    const [newLocation, setNewLocation] = useState('');
    const [addingDestination, setAddingDestination] = useState(false);

    const isDiverted = Boolean(rake.is_diverted);
    const rrDocuments = rake.rrDocuments ?? [];
    const diverrtDestinations = rake.diverrtDestinations ?? [];

    const primaryDoc = findDocForSlot(rrDocuments, null);
    const hasPrimary = !!primaryDoc;

    const diversionSlotsComplete =
        diverrtDestinations.length === 0 ||
        diverrtDestinations.every((d) => findDocForSlot(rrDocuments, d.id));

    const allComplete = isDiverted ? hasPrimary && diversionSlotsComplete : hasPrimary;

    const postPdfUpload = (file: File, diverrtDestinationId: number | null) => {
        const formData = new FormData();
        formData.append('pdf', file);
        formData.append('rake_id', String(rake.id));
        if (diverrtDestinationId !== null) {
            formData.append('diverrt_destination_id', String(diverrtDestinationId));
        }
        const key = diverrtDestinationId === null ? 'primary' : `div-${diverrtDestinationId}`;
        setUploadingKey(key);
        router.post('/railway-receipts/import', formData, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                setUploadingKey(null);
            },
        });
    };

    const handleDivertedToggle = (checked: boolean) => {
        setDiversionModeBusy(true);
        router.patch(
            `/rakes/${rake.id}/diversion-mode`,
            { is_diverted: checked },
            {
                preserveScroll: true,
                onFinish: () => setDiversionModeBusy(false),
            },
        );
    };

    const addDestination = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newLocation.trim()) return;
        setAddingDestination(true);
        router.post(
            `/rakes/${rake.id}/diverrt-destinations`,
            { location: newLocation.trim() },
            {
                preserveScroll: true,
                onFinish: () => {
                    setAddingDestination(false);
                    setNewLocation('');
                },
            },
        );
    };

    const removeDestination = (destinationId: number) => {
        if (
            !confirm(
                'Remove this diversion destination? Only allowed if no Railway Receipt has been uploaded for it.',
            )
        ) {
            return;
        }
        router.delete(`/rakes/${rake.id}/diverrt-destinations/${destinationId}`, {
            preserveScroll: true,
        });
    };

    const getStatusIcon = () => {
        if (!allComplete) return <Clock className="h-4 w-4" />;
        return <CheckCircle className="h-4 w-4 text-green-600" />;
    };

    const getStatusText = () => {
        if (!allComplete) return 'Incomplete';
        return 'Complete';
    };

    const getStatusVariant = () => {
        if (!allComplete) return 'secondary';
        return 'default';
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <FileText className="h-5 w-5" />
                        Railway Receipt (RR) Document
                    </div>
                    <div className="flex items-center gap-2">
                        {getStatusIcon()}
                        <Badge variant={getStatusVariant()}>{getStatusText()}</Badge>
                    </div>
                </CardTitle>
                <CardDescription>
                    {isDiverted
                        ? 'Upload the primary RR plus one RR per diversion leg (Station To on each PDF must match the leg IR code).'
                        : 'Create official railway receipt document for this rake.'}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {!disabled && (
                    <div className="flex flex-col gap-3 rounded-lg border border-dashed p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-1">
                            <p className="text-sm font-medium">Diverted rake (multiple RRs)</p>
                            <p className="text-xs text-muted-foreground">
                                Enable when this rake has diversion legs and needs a separate Railway Receipt per
                                destination.
                            </p>
                        </div>
                        <label className="flex cursor-pointer items-center gap-3">
                            <Checkbox
                                checked={isDiverted}
                                disabled={diversionModeBusy}
                                onCheckedChange={(v) => handleDivertedToggle(v === true)}
                                data-pan="rake-rr-diverted-mode-checkbox"
                            />
                            <span className="text-sm font-medium">{isDiverted ? 'Enabled' : 'Disabled'}</span>
                        </label>
                    </div>
                )}

                {isDiverted && !disabled && (
                    <div className="space-y-3">
                        <p className="text-sm font-medium">Diversion legs</p>
                        <p className="text-xs text-muted-foreground">
                            Add one row per diverted destination. Use the IR <strong>Station To</strong> code as shown
                            on that leg&apos;s RR PDF.
                        </p>
                        <form onSubmit={addDestination} className="flex flex-col gap-2 sm:flex-row sm:items-end">
                            <div className="flex-1 space-y-1">
                                <Label htmlFor="diverrt-location">IR station code (Station To)</Label>
                                <input
                                    id="diverrt-location"
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    value={newLocation}
                                    onChange={(e) => setNewLocation(e.target.value)}
                                    placeholder="e.g. PPSA"
                                    maxLength={255}
                                />
                            </div>
                            <Button
                                type="submit"
                                disabled={addingDestination || !newLocation.trim()}
                                data-pan="rake-rr-diversion-destination-add"
                            >
                                Add leg
                            </Button>
                        </form>
                        {diverrtDestinations.length > 0 && (
                            <ul className="space-y-2">
                                {diverrtDestinations.map((d) => {
                                    const hasDoc = !!findDocForSlot(rrDocuments, d.id);
                                    return (
                                        <li
                                            key={d.id}
                                            className="flex items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm"
                                        >
                                            <span className="font-mono">{d.location}</span>
                                            {!hasDoc && !disabled && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-destructive hover:text-destructive"
                                                    onClick={() => removeDestination(d.id)}
                                                    data-pan="rake-rr-diversion-destination-remove"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                    <span className="sr-only">Remove leg</span>
                                                </Button>
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </div>
                )}

                <div className="space-y-4">
                    <p className="text-sm font-medium">Primary RR (original rake destination)</p>
                    <RrSlotCard
                        label="Primary Railway Receipt"
                        description="Station To on the PDF must match the rake destination code."
                        doc={primaryDoc}
                        disabled={disabled}
                        uploading={uploadingKey === 'primary'}
                        panUpload="rake-rr-upload-primary-pdf-button"
                        onFile={(file) => postPdfUpload(file, null)}
                    />
                </div>

                {isDiverted && diverrtDestinations.length > 0 && (
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
                                    disabled={disabled}
                                    uploading={uploadingKey === key}
                                    panUpload="rake-rr-upload-diversion-pdf-button"
                                    onFile={(file) => postPdfUpload(file, d.id)}
                                />
                            );
                        })}
                    </div>
                )}

                <InputError message={errors?.pdf} />
                <InputError message={errors?.is_diverted} />
                <InputError message={errors?.diverrt_destination} />
                <InputError message={errors?.location} />

                {disabled && !hasPrimary && !isDiverted && (
                    <div className="py-4 text-center text-sm text-muted-foreground">
                        Complete weighment to enable RR document creation
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
