import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Loader2, Pencil } from 'lucide-react';
import { useEffect, useState } from 'react';

export interface EditWagonsWagon {
    id: number;
    wagon_sequence: number;
    wagon_number: string;
    wagon_type: string | null;
    tare_weight_mt: string | number | null;
    pcc_weight_mt: string | number | null;
    is_unfit: boolean;
    state: string | null;
}

interface WagonRowState {
    tare_weight_mt: string;
    pcc_weight_mt: string;
}

interface EditWagonsDialogProps {
    wagons: EditWagonsWagon[];
    rakeId: number;
    onWagonSaved?: (wagon: EditWagonsWagon) => void;
}

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

function toRowState(wagon: EditWagonsWagon): WagonRowState {
    return {
        tare_weight_mt:
            wagon.tare_weight_mt != null ? String(wagon.tare_weight_mt) : '',
        pcc_weight_mt:
            wagon.pcc_weight_mt != null ? String(wagon.pcc_weight_mt) : '',
    };
}

function normalizeStoredWeight(value: string | number | null | undefined): string {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value);
}

export function EditWagonsDialog({
    wagons,
    rakeId,
    onWagonSaved,
}: EditWagonsDialogProps) {
    const [open, setOpen] = useState(false);
    const [rows, setRows] = useState<Record<number, WagonRowState>>({});
    const [savingById, setSavingById] = useState<Record<number, boolean>>({});
    const [errorById, setErrorById] = useState<Record<number, string | null>>({});

    useEffect(() => {
        if (!open) {
            return;
        }

        const next: Record<number, WagonRowState> = {};
        wagons.forEach((wagon) => {
            next[wagon.id] = toRowState(wagon);
        });

        setRows(next);
        setSavingById({});
        setErrorById({});
    }, [open, wagons]);

    const setRow = (wagonId: number, patch: Partial<WagonRowState>): void => {
        setRows((prev) => {
            const current = prev[wagonId];
            if (!current) {
                return prev;
            }

            return { ...prev, [wagonId]: { ...current, ...patch } };
        });
    };

    const saveRow = async (wagonId: number, payload: Record<string, unknown>): Promise<void> => {
        setSavingById((prev) => ({ ...prev, [wagonId]: true }));
        setErrorById((prev) => ({ ...prev, [wagonId]: null }));

        try {
            const response = await fetch(`/rakes/${rakeId}/wagons/${wagonId}`, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    ...getCsrfHeaders(),
                },
                body: JSON.stringify(payload),
                credentials: 'include',
            });

            const data = (await response.json().catch(() => ({}))) as {
                wagon?: EditWagonsWagon;
                message?: string;
                errors?: Record<string, string[]>;
            };

            if (!response.ok || !data.wagon) {
                const message =
                    data.message ??
                    (data.errors ? Object.values(data.errors).flat().join(', ') : null) ??
                    'Failed to save wagon.';
                setErrorById((prev) => ({ ...prev, [wagonId]: message }));
                return;
            }

            onWagonSaved?.(data.wagon);
            setRows((prev) => ({
                ...prev,
                [wagonId]: toRowState(data.wagon!),
            }));
        } catch {
            setErrorById((prev) => ({
                ...prev,
                [wagonId]: 'Failed to save wagon.',
            }));
        } finally {
            setSavingById((prev) => ({ ...prev, [wagonId]: false }));
        }
    };

    const handleWeightBlur = async (
        wagon: EditWagonsWagon,
        field: 'tare_weight_mt' | 'pcc_weight_mt',
    ): Promise<void> => {
        const state = rows[wagon.id] ?? toRowState(wagon);
        const raw = state[field].trim();
        const previous = normalizeStoredWeight(wagon[field]);

        if (raw === previous) {
            return;
        }

        if (raw === '') {
            await saveRow(wagon.id, { [field]: null });
            return;
        }

        const num = parseFloat(raw);
        if (Number.isNaN(num)) {
            setErrorById((prev) => ({
                ...prev,
                [wagon.id]: 'Enter a valid number or leave empty.',
            }));
            return;
        }

        await saveRow(wagon.id, { [field]: num });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button" size="sm">
                    <Pencil className="mr-2 h-4 w-4" />
                    Edit Wagons
                </Button>
            </DialogTrigger>
            <DialogContent className="!max-w-[92vw] w-[92vw] max-h-[90vh] flex flex-col p-4 sm:p-6">
                <DialogHeader>
                    <DialogTitle>Edit Wagons</DialogTitle>
                </DialogHeader>
                <div
                    className="flex-1 min-h-0 overflow-auto border rounded-md"
                    style={{ height: 'min(70vh, 600px)' }}
                >
                    <div className="inline-block min-w-[900px] align-top">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Wagon No</TableHead>
                                    <TableHead>Seq</TableHead>
                                    <TableHead>Wagon Type</TableHead>
                                    <TableHead>Tare (MT)</TableHead>
                                    <TableHead>PCC (MT)</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {wagons.map((wagon) => {
                                    const row = rows[wagon.id] ?? toRowState(wagon);
                                    const isSaving = savingById[wagon.id] === true;
                                    const rowError = errorById[wagon.id];

                                    return (
                                        <TableRow key={wagon.id}>
                                            <TableCell className="font-medium tabular-nums">
                                                {wagon.wagon_number}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {wagon.wagon_sequence}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {wagon.wagon_type?.trim() ? wagon.wagon_type : '—'}
                                            </TableCell>
                                            <TableCell>
                                                <Input
                                                    className="h-8 w-28"
                                                    value={row.tare_weight_mt}
                                                    onChange={(event) => {
                                                        setRow(wagon.id, {
                                                            tare_weight_mt: event.target.value,
                                                        });
                                                    }}
                                                    onBlur={() => void handleWeightBlur(wagon, 'tare_weight_mt')}
                                                    disabled={isSaving}
                                                    inputMode="decimal"
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Input
                                                    className="h-8 w-28"
                                                    value={row.pcc_weight_mt}
                                                    onChange={(event) => {
                                                        setRow(wagon.id, {
                                                            pcc_weight_mt: event.target.value,
                                                        });
                                                    }}
                                                    onBlur={() => void handleWeightBlur(wagon, 'pcc_weight_mt')}
                                                    disabled={isSaving}
                                                    inputMode="decimal"
                                                />
                                            </TableCell>
                                            <TableCell className="text-xs">
                                                {isSaving ? (
                                                    <span className="inline-flex items-center text-muted-foreground">
                                                        <Loader2 className="mr-1 h-3 w-3 animate-spin" />
                                                        Saving
                                                    </span>
                                                ) : rowError ? (
                                                    <span className="text-destructive">{rowError}</span>
                                                ) : (
                                                    <span className="text-muted-foreground">Ready</span>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
