import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Link } from '@inertiajs/react';
import { Download, Eye, Upload } from 'lucide-react';
import { useRef } from 'react';

export interface RrDocumentRecord {
    id: number;
    rr_number: string;
    rr_received_date: string;
    rr_weight_mt: string | null;
    document_status: string;
    diverrt_destination_id?: number | null;
}

export interface DiverrtDestinationRow {
    id: number;
    location: string;
}

export function findDocForSlot(
    rrDocuments: RrDocumentRecord[] | undefined,
    diverrtDestinationId: number | null,
): RrDocumentRecord | undefined {
    const docs = rrDocuments ?? [];
    if (diverrtDestinationId === null) {
        return docs.find((d) => d.diverrt_destination_id == null);
    }
    return docs.find((d) => d.diverrt_destination_id === diverrtDestinationId);
}

export function RrSlotCard({
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
        <div className="bg-muted/30 space-y-3 rounded-lg border p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p className="text-sm font-medium">{label}</p>
                    {description ? (
                        <p className="text-muted-foreground mt-0.5 text-xs">{description}</p>
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
                                if (file) {
                                    onFile(file);
                                }
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
                            <Label className="text-muted-foreground text-xs">RR number</Label>
                            <p className="font-semibold">{doc.rr_number}</p>
                        </div>
                        <div>
                            <Label className="text-muted-foreground text-xs">Received</Label>
                            <p className="text-sm">{new Date(doc.rr_received_date).toLocaleDateString()}</p>
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
