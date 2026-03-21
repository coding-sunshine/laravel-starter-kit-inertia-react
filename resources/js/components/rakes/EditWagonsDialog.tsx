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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Loader2, Pencil } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

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

export interface EditWagonsTypeOption {
    id: number;
    code: string;
    full_form: string | null;
    carrying_capacity_min_mt: string | number;
    carrying_capacity_max_mt: string | number;
    gross_tare_weight_mt: string | number;
    default_pcc_weight_mt: string | number | null;
}

interface WagonRowState {
    wagon_number: string;
    wagon_type: string;
    tare_weight_mt: string;
    pcc_weight_mt: string;
}

interface EditWagonsDialogProps {
    wagons: EditWagonsWagon[];
    wagonTypes: EditWagonsTypeOption[];
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
        wagon_number: wagon.wagon_number ?? '',
        wagon_type: wagon.wagon_type ?? '',
        tare_weight_mt:
            wagon.tare_weight_mt != null ? String(wagon.tare_weight_mt) : '',
        pcc_weight_mt:
            wagon.pcc_weight_mt != null ? String(wagon.pcc_weight_mt) : '',
    };
}

export function EditWagonsDialog({
    wagons,
    wagonTypes,
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

    const wagonTypeByCode = useMemo(() => {
        const map = new Map<string, EditWagonsTypeOption>();
        wagonTypes.forEach((type) => {
            map.set(type.code, type);
        });
        return map;
    }, [wagonTypes]);

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

    const handleWagonNumberBlur = async (wagon: EditWagonsWagon): Promise<void> => {
        const state = rows[wagon.id] ?? toRowState(wagon);
        const wagonNumber = state.wagon_number.trim();

        if (wagonNumber.length <= 4 || wagonNumber === (wagon.wagon_number ?? '').trim()) {
            return;
        }

        await saveRow(wagon.id, { wagon_number: wagonNumber });
    };

    const handleWagonTypeChange = async (wagon: EditWagonsWagon, code: string): Promise<void> => {
        const value = code === '_none' ? '' : code;
        const selectedType = value ? wagonTypeByCode.get(value) : null;
        const tareWeight = selectedType?.gross_tare_weight_mt != null
            ? String(selectedType.gross_tare_weight_mt)
            : '';
        const pccWeight = selectedType?.default_pcc_weight_mt != null
            ? String(selectedType.default_pcc_weight_mt)
            : '';

        setRow(wagon.id, {
            wagon_type: value,
            tare_weight_mt: tareWeight,
            pcc_weight_mt: pccWeight,
        });

        await saveRow(wagon.id, {
            wagon_type: value || null,
            tare_weight_mt: tareWeight ? parseFloat(tareWeight) : null,
            pcc_weight_mt: pccWeight ? parseFloat(pccWeight) : null,
        });
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
                                            <TableCell>
                                                <Input
                                                    className="h-8 w-36"
                                                    value={row.wagon_number}
                                                    onChange={(event) => {
                                                        setRow(wagon.id, {
                                                            wagon_number: event.target.value,
                                                        });
                                                    }}
                                                    onBlur={() => void handleWagonNumberBlur(wagon)}
                                                    placeholder="Min 5 chars"
                                                    disabled={isSaving}
                                                />
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {wagon.wagon_sequence}
                                            </TableCell>
                                            <TableCell>
                                                <Select
                                                    value={row.wagon_type || '_none'}
                                                    onValueChange={(value) => {
                                                        void handleWagonTypeChange(wagon, value);
                                                    }}
                                                    disabled={isSaving}
                                                >
                                                    <SelectTrigger className="h-8 w-[140px]">
                                                        <SelectValue placeholder="Type" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="_none">—</SelectItem>
                                                        {wagonTypes.map((wagonType) => (
                                                            <SelectItem key={wagonType.id} value={wagonType.code}>
                                                                {wagonType.code}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </TableCell>
                                            <TableCell>{row.tare_weight_mt || '—'}</TableCell>
                                            <TableCell>{row.pcc_weight_mt || '—'}</TableCell>
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
