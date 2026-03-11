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
import { useCallback, useEffect, useState } from 'react';

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

export function WagonOverviewDialog({
    wagons,
    wagonTypes,
    rakeId,
    onWagonSaved,
}: WagonOverviewDialogProps) {
    const [open, setOpen] = useState(false);
    const [rows, setRows] = useState<Record<number, WagonRowState>>({});
    const [savingId, setSavingId] = useState<number | null>(null);

    useEffect(() => {
        if (open && wagons.length > 0) {
            const next: Record<number, WagonRowState> = {};
            wagons.forEach((w) => {
                next[w.id] = toRowState(w);
            });
            setRows(next);
        }
    }, [open, wagons]);

    const setRow = useCallback((wagonId: number, patch: Partial<WagonRowState>) => {
        setRows((prev) => {
            const current = prev[wagonId];
            if (!current) return prev;
            return { ...prev, [wagonId]: { ...current, ...patch } };
        });
    }, []);

    const handleWagonTypeChange = useCallback(
        (wagonId: number, code: string) => {
            setRow(wagonId, { wagon_type: code });
            if (!code) return;
            const type = wagonTypes.find((t) => t.code === code);
            if (type) {
                setRow(wagonId, {
                    tare_weight_mt:
                        type.gross_tare_weight_mt != null
                            ? String(type.gross_tare_weight_mt)
                            : '',
                    pcc_weight_mt:
                        type.default_pcc_weight_mt != null
                            ? String(type.default_pcc_weight_mt)
                            : '',
                });
            }
        },
        [wagonTypes, setRow]
    );

    const handleSave = useCallback(
        async (wagon: WagonOverviewWagon) => {
            const state = rows[wagon.id];
            if (!state) return;
            setSavingId(wagon.id);
            const payload = {
                wagon_number: state.wagon_number || null,
                wagon_type: state.wagon_type || null,
                tare_weight_mt: state.tare_weight_mt
                    ? parseFloat(state.tare_weight_mt)
                    : null,
                pcc_weight_mt: state.pcc_weight_mt
                    ? parseFloat(state.pcc_weight_mt)
                    : null,
            };
            try {
                const res = await fetch(
                    `/rakes/${rakeId}/wagons/${wagon.id}`,
                    {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            ...getCsrfHeaders(),
                        },
                        body: JSON.stringify(payload),
                        credentials: 'include',
                    }
                );
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
                onWagonSaved?.((data as { wagon: WagonOverviewWagon }).wagon);
            } finally {
                setSavingId(null);
            }
        },
        [rakeId, rows, onWagonSaved]
    );

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
                                <TableHead className="w-[80px]">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {wagons.map((wagon) => {
                                const state = rows[wagon.id] ?? toRowState(wagon);
                                const isSaving = savingId === wagon.id;
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
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                type="button"
                                                size="sm"
                                                className="h-8"
                                                disabled={isSaving}
                                                onClick={() =>
                                                    handleSave(wagon)
                                                }
                                            >
                                                {isSaving ? (
                                                    <Loader2 className="h-4 w-4 animate-spin" />
                                                ) : (
                                                    'Save'
                                                )}
                                            </Button>
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
