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
import { Eye, Loader2 } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

export interface WagonOverviewWagon {
    id: number;
    wagon_sequence: number;
    wagon_number: string;
    wagon_type: string | null;
    tare_weight_mt: string | number | null;
    pcc_weight_mt: string | number | null;
    is_unfit: boolean;
    state: string | null;
}

export interface WagonTypeOption {
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

interface WagonOverviewDialogProps {
    wagons: WagonOverviewWagon[];
    wagonTypes: WagonTypeOption[];
    rakeId: number;
    onWagonSaved?: (wagon: WagonOverviewWagon) => void;
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

function toRowState(wagon: WagonOverviewWagon): WagonRowState {
    return {
        wagon_number: wagon.wagon_number ?? '',
        wagon_type: wagon.wagon_type ?? '',
        tare_weight_mt:
            wagon.tare_weight_mt != null ? String(wagon.tare_weight_mt) : '',
        pcc_weight_mt:
            wagon.pcc_weight_mt != null ? String(wagon.pcc_weight_mt) : '',
    };
}

function buildPayload(state: WagonRowState): {
    wagon_number: string | null;
    wagon_type: string | null;
    tare_weight_mt: number | null;
    pcc_weight_mt: number | null;
} {
    return {
        wagon_number: state.wagon_number || null,
        wagon_type: state.wagon_type || null,
        tare_weight_mt: state.tare_weight_mt
            ? parseFloat(state.tare_weight_mt)
            : null,
        pcc_weight_mt: state.pcc_weight_mt
            ? parseFloat(state.pcc_weight_mt)
            : null,
    };
}

export function WagonOverviewDialog({
    wagons,
    wagonTypes,
    rakeId,
    onWagonSaved,
}: WagonOverviewDialogProps) {
    const [open, setOpen] = useState(false);
    const [rows, setRows] = useState<Record<number, WagonRowState>>({});
    const [savingAll, setSavingAll] = useState(false);
    const hasAppliedFirstType = useRef(false);

    useEffect(() => {
        if (open && wagons.length > 0) {
            const next: Record<number, WagonRowState> = {};
            wagons.forEach((w) => {
                next[w.id] = toRowState(w);
            });
            setRows(next);
            hasAppliedFirstType.current = false;
        }
    }, [open, wagons]);

    const setRow = useCallback((wagonId: number, patch: Partial<WagonRowState>) => {
        setRows((prev) => {
            const current = prev[wagonId];
            if (!current) return prev;
            return { ...prev, [wagonId]: { ...current, ...patch } };
        });
    }, []);

    const bulkSave = useCallback(
        async (states: Record<number, WagonRowState>) => {
            const payload = {
                wagons: wagons.map((wagon) => {
                    const state = states[wagon.id] ?? toRowState(wagon);
                    return {
                        id: wagon.id,
                        wagon_number: state.wagon_number || null,
                        wagon_type: state.wagon_type || null,
                        tare_weight_mt: state.tare_weight_mt
                            ? parseFloat(state.tare_weight_mt)
                            : null,
                        pcc_weight_mt: state.pcc_weight_mt
                            ? parseFloat(state.pcc_weight_mt)
                            : null,
                    };
                }),
            };

            const res = await fetch(`/rakes/${rakeId}/wagons-bulk`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...getCsrfHeaders(),
                },
                body: JSON.stringify(payload),
                credentials: 'include',
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const err = data as {
                    message?: string;
                    errors?: Record<string, string[]>;
                };
                const msg =
                    err.message ??
                    (err.errors
                        ? Object.values(err.errors).flat().join(', ')
                        : null) ??
                    res.statusText ??
                    'Save failed';
                throw new Error(msg);
            }

            const updated = (data as { wagons?: WagonOverviewWagon[] }).wagons ?? [];
            updated.forEach((wagon) => {
                onWagonSaved?.(wagon);
            });
        },
        [rakeId, wagons, onWagonSaved]
    );

    const handleWagonTypeChange = useCallback(
        (wagonId: number, code: string) => {
            setRow(wagonId, { wagon_type: code });
            if (!code) return;
            const type = wagonTypes.find((t) => t.code === code);
            if (!type) return;

            const tareStr =
                type.gross_tare_weight_mt != null
                    ? String(type.gross_tare_weight_mt)
                    : '';
            const pccStr =
                type.default_pcc_weight_mt != null
                    ? String(type.default_pcc_weight_mt)
                    : '';

            setRow(wagonId, {
                tare_weight_mt: tareStr,
                pcc_weight_mt: pccStr,
            });

            if (!hasAppliedFirstType.current) {
                hasAppliedFirstType.current = true;
                setRows((prev) => {
                    const next = { ...prev };
                    for (const id of Object.keys(next)) {
                        next[Number(id)] = {
                            ...next[Number(id)],
                            wagon_type: code,
                            tare_weight_mt: tareStr,
                            pcc_weight_mt: pccStr,
                        };
                    }
                    return next;
                });

                setSavingAll(true);
                (async () => {
                    try {
                        // Use current rows snapshot with new type/tare/PCC applied
                        await bulkSave(
                            Object.keys(rows).length > 0 ? rows : wagons.reduce((acc, w) => {
                                acc[w.id] = toRowState(w);
                                return acc;
                            }, {} as Record<number, WagonRowState>)
                        );
                    } finally {
                        setSavingAll(false);
                    }
                })();
            }
        },
        [wagonTypes, setRow, wagons, rows, bulkSave]
    );

    const handleUpdateAll = useCallback(async () => {
        setSavingAll(true);
        try {
            await bulkSave(rows);
        } finally {
            setSavingAll(false);
        }
    }, [rows, bulkSave]);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline">
                    <Eye className="mr-2 h-4 w-4" />
                    View Wagons
                </Button>
            </DialogTrigger>
            <DialogContent className="!max-w-[92vw] w-[92vw] max-h-[90vh] flex flex-col p-4 sm:p-6">
                <DialogHeader>
                    <DialogTitle>Wagon Overview</DialogTitle>
                    <Button
                        type="button"
                        variant="default"
                        disabled={savingAll}
                        onClick={handleUpdateAll}
                        className="mt-2"
                    >
                        {savingAll ? (
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        ) : null}
                        Update
                    </Button>
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
                                <TableHead>Carrying capacity (MT)</TableHead>
                                <TableHead>PCC (MT)</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {wagons.map((wagon) => {
                                const state = rows[wagon.id] ?? toRowState(wagon);
                                const isSaving = savingAll;
                                return (
                                    <TableRow key={wagon.id}>
                                        <TableCell>
                                            <Input
                                                className="h-8 w-28"
                                                value={state.wagon_number}
                                                onChange={(e) =>
                                                    setRow(wagon.id, {
                                                        wagon_number:
                                                            e.target.value,
                                                    })
                                                }
                                                placeholder="Wagon no"
                                                disabled={isSaving}
                                            />
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {wagon.wagon_sequence}
                                        </TableCell>
                                        <TableCell>
                                            <Select
                                                value={
                                                    state.wagon_type || '_none'
                                                }
                                                onValueChange={(value) =>
                                                    handleWagonTypeChange(
                                                        wagon.id,
                                                        value === '_none'
                                                            ? ''
                                                            : value
                                                    )
                                                }
                                                disabled={isSaving}
                                            >
                                                <SelectTrigger className="h-8 w-[140px]">
                                                    <SelectValue placeholder="Type" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="_none">
                                                        —
                                                    </SelectItem>
                                                    {wagonTypes.map((wt) => (
                                                        <SelectItem
                                                            key={wt.id}
                                                            value={wt.code}
                                                        >
                                                            {wt.code}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                className="h-8 w-20"
                                                value={state.tare_weight_mt}
                                                onChange={(e) =>
                                                    setRow(wagon.id, {
                                                        tare_weight_mt:
                                                            e.target.value,
                                                    })
                                                }
                                                placeholder="Tare"
                                                disabled={isSaving}
                                            />
                                        </TableCell>
                                        <TableCell className="min-w-[120px] text-muted-foreground text-sm">
                                            {state.wagon_type
                                                ? (() => {
                                                      const wt = wagonTypes.find(
                                                          (t) =>
                                                              t.code ===
                                                              state.wagon_type
                                                      );
                                                      return wt
                                                          ? `${wt.carrying_capacity_min_mt}–${wt.carrying_capacity_max_mt} MT`
                                                          : '—';
                                                  })()
                                                : '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                className="h-8 w-20"
                                                value={state.pcc_weight_mt}
                                                onChange={(e) =>
                                                    setRow(wagon.id, {
                                                        pcc_weight_mt:
                                                            e.target.value,
                                                    })
                                                }
                                                placeholder="PCC"
                                                disabled={isSaving}
                                            />
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
