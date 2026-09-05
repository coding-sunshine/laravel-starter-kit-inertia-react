import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Train, CheckCircle, Clock, AlertTriangle } from 'lucide-react';
import { useRef, useState } from 'react';
import { TxrTable } from './TxrTable';
import { UnfitWagonTable } from './UnfitWagonTable';

function getCsrfHeaders(): Record<string, string> {
    const cookieMatch = document.cookie.match(/\bXSRF-TOKEN=([^;]+)/);
    if (cookieMatch) {
        return { 'X-XSRF-TOKEN': decodeURIComponent(cookieMatch[1].trim()) };
    }
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta?.getAttribute('content')) {
        return { 'X-CSRF-TOKEN': meta.getAttribute('content')! };
    }
    return {};
}

interface Wagon {
    id: number;
    wagon_number: string;
    wagon_sequence: number;
    wagon_type?: string | null;
    is_unfit?: boolean;
}

interface WagonUnfitLog {
    id?: number;
    wagon_id: number;
    wagon?: { wagon_number: string; wagon_sequence: number; wagon_type?: string | null };
    reason?: string | null;
    marking_method?: string | null;
    marked_at?: string | null;
}

interface TxrRecord {
    id: number;
    rake_id: number;
    inspection_time: string;
    inspection_end_time?: string | null;
    status: string;
    remarks: string | null;
    handwritten_note_url?: string | null;
}

interface TxrWorkflowProps {
    rake: {
        id: number;
        rake_number: string;
        state: string;
        wagons: Wagon[];
        txr: (TxrRecord & { wagonUnfitLogs?: WagonUnfitLog[] }) | null;
        wagonUnfitLogs?: WagonUnfitLog[];
    };
    disabled: boolean;
    onUnfitLogsSaved?: (logs: WagonUnfitLog[]) => void;
    onTxrNoteUploaded?: (url: string | null) => void;
    onTxrHeaderSaved?: (txr: Record<string, unknown>) => void;
}

export function TxrWorkflow({
    rake,
    disabled,
    onUnfitLogsSaved,
    onTxrNoteUploaded,
    onTxrHeaderSaved,
}: TxrWorkflowProps) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploadingNote, setUploadingNote] = useState(false);
    const [noteError, setNoteError] = useState<string | null>(null);

    const getStatusIcon = () => {
        if (!rake.txr) return <Clock className="h-4 w-4" />;
        if (rake.txr.status === 'completed') return <CheckCircle className="h-4 w-4 text-green-600" />;
        if (rake.txr.status === 'in_progress') return <Clock className="h-4 w-4 text-blue-600" />;
        return <AlertTriangle className="h-4 w-4 text-orange-600" />;
    };

    const getStatusText = () => {
        if (!rake.txr) return 'Not saved';
        return rake.txr.status.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase());
    };

    const isCompleted = rake.txr?.status === 'completed';
    const handwrittenNoteUrl =
        (rake.txr as { handwritten_note_url?: string | null } | null)?.handwritten_note_url ??
        rake.txr?.handwritten_note_url ??
        null;

    const handleUploadNoteClick = () => {
        fileInputRef.current?.click();
    };

    const handleNoteFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file || !rake.txr) {
            return;
        }

        setNoteError(null);
        setUploadingNote(true);

        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await fetch(`/rakes/${rake.id}/txr/upload-note`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    ...getCsrfHeaders(),
                },
                body: formData,
            });

            const data = (await response.json().catch(() => null)) as
                | { handwritten_note_url?: string | null; message?: string }
                | null;

            if (!response.ok) {
                setNoteError(data?.message ?? 'Failed to upload TXR note.');
                return;
            }

            onTxrNoteUploaded?.(data?.handwritten_note_url ?? null);
        } catch {
            setNoteError('Failed to upload TXR note.');
        } finally {
            setUploadingNote(false);
            e.target.value = '';
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Train className="h-5 w-5" />
                        TXR - Train Examination Report
                    </div>
                    <div className="flex items-center gap-2">
                        {getStatusIcon()}
                        <Badge variant={isCompleted ? 'default' : 'secondary'}>{getStatusText()}</Badge>
                    </div>
                </CardTitle>
                <CardDescription>Train examination and wagon fitness check</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <TxrTable rake={rake} disabled={disabled} onTxrHeaderSaved={onTxrHeaderSaved} />

                {!rake.txr && (
                    <div className="rounded-md border border-dashed bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                        Save the TXR header above to enable note upload and unfit wagon saves.
                    </div>
                )}

                <div
                    className={
                        !rake.txr ? 'space-y-6 opacity-60' : 'space-y-6'
                    }
                >
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-md border bg-muted/20 p-3">
                        <div className="space-y-1">
                            <div className="text-sm font-medium">Railway officer’s handwritten note</div>
                            {handwrittenNoteUrl ? (
                                <a
                                    href={handwrittenNoteUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="text-sm text-primary underline underline-offset-4"
                                >
                                    View / Download uploaded note
                                </a>
                            ) : (
                                <div className="text-sm text-muted-foreground">No file uploaded</div>
                            )}
                            {noteError && (
                                <div className="text-sm text-destructive">{noteError}</div>
                            )}
                        </div>

                        <div className="flex items-center gap-2">
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept=".pdf,image/jpeg,image/png"
                                className="hidden"
                                onChange={handleNoteFileChange}
                            />
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleUploadNoteClick}
                                disabled={disabled || !rake.txr || uploadingNote}
                                data-pan="txr-upload-note-button"
                            >
                                {uploadingNote ? 'Uploading…' : 'Upload note'}
                            </Button>
                        </div>
                    </div>

                    <UnfitWagonTable rake={rake} disabled={disabled} onUnfitLogsSaved={onUnfitLogsSaved} />

                    {isCompleted && (
                        <div className="flex items-center gap-2 text-sm text-green-600">
                            <CheckCircle className="h-4 w-4" />
                            TXR completed successfully
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
