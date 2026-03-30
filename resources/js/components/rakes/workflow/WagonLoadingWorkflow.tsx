import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Package, CheckCircle, Clock, Loader } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

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
    pcc_weight_mt?: string | null;
    is_unfit?: boolean;
}

interface LoaderOption {
    id: number;
    loader_name: string;
    code: string;
}

interface WagonLoadingRecord {
    id?: number;
    wagon_id: number;
    wagon?: {
        wagon_number: string;
        wagon_sequence: number;
        wagon_type?: string | null;
        pcc_weight_mt?: string | null;
    };
    loader_id?: number | null;
    loader?: { loader_name: string; code: string };
    loader_operator_name?: string | null;
    loaded_quantity_mt: string;
    loading_time?: string | null;
    remarks?: string | null;
}

interface WagonLoadingWorkflowProps {
    rake: {
        id: number;
        state: string;
        loading_start_time?: string | null;
        loading_end_time?: string | null;
        loading_free_minutes?: number | null;
        loading_warning_minutes?: number | null;
        loading_section_free_minutes?: number | null;
        wagons: Wagon[];
        wagonLoadings?: WagonLoadingRecord[];
        wagon_loadings?: WagonLoadingRecord[];
        siding?: { loaders?: LoaderOption[] } | null;
    };
    disabled: boolean;
    onWagonLoadingsSaved?: (loadings: WagonLoadingRecord[]) => void;
    /**
     * By default the table scrolls internally to keep the page compact.
     * For dedicated screens (like Rake Loader) you may want full height.
     */
    compact?: boolean;
}

interface LoadingRow {
    id?: number;
    key: string;
    wagon_id: string;
    wagon_number: string;
    loader_id: string;
    loader_operator_name: string;
    wagon_type: string;
    pcc_capacity: string;
    loaded_quantity_mt: string;
    loading_time: string;
    remarks: string;
}

const EMPTY_LOADINGS: WagonLoadingRecord[] = [];

function shouldSkipLoaderWeighmentWagonNumber(wagonNumber: string | null | undefined): boolean {
    const trimmed = (wagonNumber ?? '').trim();
    return /^W\d+$/.test(trimmed);
}

export function WagonLoadingWorkflow({
    rake,
    disabled,
    onWagonLoadingsSaved,
    compact = true,
}: WagonLoadingWorkflowProps) {
    const existingLoadings = rake.wagonLoadings ?? rake.wagon_loadings ?? EMPTY_LOADINGS;
    /** All wagons on the rake (including unfit — loaders may still record quantities). */
    const rakeWagonsOrdered = useMemo(
        () =>
            [...rake.wagons]
                .filter((w) => !shouldSkipLoaderWeighmentWagonNumber(w.wagon_number))
                .sort(
                (a, b) => (a.wagon_sequence ?? 0) - (b.wagon_sequence ?? 0)
            ),
        [rake.wagons]
    );

    const [rows, setRows] = useState<LoadingRow[]>([]);
    const [saving, setSaving] = useState(false);
    const [ensuring, setEnsuring] = useState(false);
    const [savingRowKey, setSavingRowKey] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    const onWagonLoadingsSavedRef = useRef(onWagonLoadingsSaved);
    onWagonLoadingsSavedRef.current = onWagonLoadingsSaved;

    const loadingsSyncKey = useMemo(() => {
        const list = rake.wagonLoadings ?? rake.wagon_loadings ?? EMPTY_LOADINGS;
        return list
            .map(
                (l) =>
                    `${l.id ?? 'n'}:${l.wagon_id}:${String(l.loaded_quantity_mt ?? '')}:${l.loader_id ?? ''}:${l.loading_time ?? ''}`,
            )
            .join('|');
    }, [rake.wagonLoadings, rake.wagon_loadings]);

    const needsEnsureAll = useMemo(() => {
        if (disabled || rakeWagonsOrdered.length === 0) {
            return false;
        }
        const requiredIds = new Set(rakeWagonsOrdered.map((w) => w.id));
        const covered = new Set(existingLoadings.map((l) => l.wagon_id));
        for (const id of requiredIds) {
            if (!covered.has(id)) {
                return true;
            }
        }
        return false;
    }, [disabled, rakeWagonsOrdered, existingLoadings]);

    useEffect(() => {
        const list = rake.wagonLoadings ?? rake.wagon_loadings ?? EMPTY_LOADINGS;
        const nextRows: LoadingRow[] =
            list.length === 0
                ? []
                : list
                      .filter((l) => !shouldSkipLoaderWeighmentWagonNumber(l.wagon?.wagon_number))
                      .map((l) => ({
                      id: l.id,
                      key: `load-${l.wagon_id}-${l.id ?? Date.now()}`,
                      wagon_id: String(l.wagon_id),
                      wagon_number: l.wagon?.wagon_number ?? '',
                      loader_id: l.loader_id ? String(l.loader_id) : '',
                      loader_operator_name: l.loader_operator_name ?? '',
                      wagon_type: l.wagon?.wagon_type ?? '',
                      pcc_capacity: l.wagon?.pcc_weight_mt ?? '',
                      loaded_quantity_mt: l.loaded_quantity_mt ?? '',
                      loading_time: l.loading_time
                          ? new Date(l.loading_time).toISOString().slice(0, 16)
                          : new Date().toISOString().slice(0, 16),
                      remarks: l.remarks ?? '',
                  }));
        setRows(nextRows);
    }, [rake.id, loadingsSyncKey]);

    const wagonLoadingsFetchStartedRef = useRef(false);
    useEffect(() => {
        wagonLoadingsFetchStartedRef.current = false;
    }, [rake.id]);

    useEffect(() => {
        const propCount =
            rake.wagonLoadings?.length ?? rake.wagon_loadings?.length ?? 0;
        if (propCount > 0) {
            return;
        }
        if (!disabled) {
            return;
        }
        if (wagonLoadingsFetchStartedRef.current) {
            return;
        }
        wagonLoadingsFetchStartedRef.current = true;
        let cancelled = false;
        void (async () => {
            try {
                const response = await fetch(`/rakes/${rake.id}/load/wagon-loadings`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok || cancelled) {
                    return;
                }
                const data = (await response.json()) as {
                    wagonLoadings?: WagonLoadingRecord[];
                };
                if (cancelled || !data.wagonLoadings?.length) {
                    return;
                }
                onWagonLoadingsSavedRef.current?.(data.wagonLoadings);
            } catch {
                //
            }
        })();
        return () => {
            cancelled = true;
        };
    }, [rake.id, rake.wagonLoadings, rake.wagon_loadings, disabled]);

    useEffect(() => {
        if (!needsEnsureAll) {
            return;
        }
        let cancelled = false;
        setEnsuring(true);
        setError(null);
        void (async () => {
            try {
                const response = await fetch(`/rakes/${rake.id}/load/wagon-rows/ensure-all`, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        ...getCsrfHeaders(),
                    },
                    credentials: 'same-origin',
                });
                const data = (await response.json().catch(() => null)) as
                    | { wagonLoadings?: WagonLoadingRecord[]; message?: string }
                    | null;
                if (cancelled) {
                    return;
                }
                if (!response.ok) {
                    setError(data?.message ?? 'Failed to prepare wagon loading rows.');
                    return;
                }
                onWagonLoadingsSavedRef.current?.(data?.wagonLoadings ?? []);
            } catch {
                if (!cancelled) {
                    setError('Failed to prepare wagon loading rows.');
                }
            } finally {
                if (!cancelled) {
                    setEnsuring(false);
                }
            }
        })();
        return () => {
            cancelled = true;
        };
    }, [needsEnsureAll, rake.id]);

    const loaders = useMemo(() => rake.siding?.loaders ?? [], [rake.siding?.loaders]);

    const positivelyLoadedWagonIds = useMemo(
        () =>
            new Set(
                existingLoadings
                    .filter((l) => Number(l.loaded_quantity_mt) > 0)
                    .map((l) => l.wagon_id)
            ),
        [existingLoadings]
    );

    const loadedAndHasLoaderWagonIds = useMemo(() => {
        const ids = new Set<number>();
        for (const l of existingLoadings) {
            if (Number(l.loaded_quantity_mt) > 0 && l.loader_id) {
                ids.add(l.wagon_id);
            }
        }
        return ids;
    }, [existingLoadings]);

    /** Status / “Completed” uses fit wagons only; unfit wagons may stay at 0 for audit. */
    const fitWagonsOrdered = useMemo(
        () => rakeWagonsOrdered.filter((w) => !w.is_unfit),
        [rakeWagonsOrdered]
    );
    const incompleteFitWagons = fitWagonsOrdered.filter(
        (w) => !loadedAndHasLoaderWagonIds.has(w.id)
    );
    const isCompleted =
        fitWagonsOrdered.length > 0 && incompleteFitWagons.length === 0;

    const [editMode, setEditMode] = useState(!isCompleted);

    useEffect(() => {
        setEditMode(!isCompleted);
    }, [isCompleted]);

    const updateRow = (key: string, field: keyof LoadingRow, value: string) => {
        setRows((prev) => {
            const idx = prev.findIndex((r) => r.key === key);
            if (idx < 0) {
                return prev;
            }

            const current = prev[idx];
            if (!current) {
                return prev;
            }

            // Avoid rewriting array for no-op updates (helps typing performance).
            if (current[field] === value) {
                return prev;
            }

            const next = [...prev];
            next[idx] = { ...current, [field]: value };
            return next;
        });
    };

    const getStatusIcon = () => {
        if (isCompleted) {
            return <CheckCircle className="h-4 w-4 text-green-600" />;
        }
        if (existingLoadings.length > 0) {
            return <Loader className="h-4 w-4 text-blue-600" />;
        }
        return <Clock className="h-4 w-4" />;
    };

    const getStatusText = () => {
        if (isCompleted) {
            return 'Completed';
        }
        if (existingLoadings.length > 0) {
            return 'In Progress';
        }
        return 'Not Started';
    };

    const showWorkflowPanel = editMode || rows.length > 0 || rake.wagons.length > 0;
    const tableReadOnly = !editMode;

    const saveRow = async (row: LoadingRow): Promise<void> => {
        if (!row.id) {
            return;
        }
        if (tableReadOnly || disabled) {
            return;
        }

        const loaderId =
            row.loader_id && row.loader_id !== '__none__'
                ? Number(row.loader_id)
                : null;

        const operatorName = row.loader_operator_name.trim();
        const quantityString = row.loaded_quantity_mt.trim();
        if (quantityString === '') {
            setError('Loaded quantity is required.');
            return;
        }

        setError(null);
        setSaving(true);
        setSavingRowKey(row.key);

        try {
            const response = await fetch(
                `/rakes/${rake.id}/load/wagon-rows/${row.id}`,
                {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        ...getCsrfHeaders(),
                    },
                    body: JSON.stringify({
                        loader_id: loaderId,
                        loader_operator_name: operatorName === '' ? null : operatorName,
                        loaded_quantity_mt: quantityString,
                    }),
                }
            );

            const data = (await response.json().catch(() => null)) as
                | { loading?: WagonLoadingRecord; message?: string }
                | null;
            if (!response.ok || !data?.loading) {
                setError(data?.message ?? 'Failed to update wagon row.');
                return;
            }

            const updated = data.loading;
            const merged = existingLoadings.map((l) =>
                l.id === updated.id ? updated : l
            );
            onWagonLoadingsSaved?.(merged);
        } catch {
            setError('Failed to update wagon row.');
        } finally {
            setSaving(false);
            setSavingRowKey(null);
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Package className="h-5 w-5" />
                        Wagon Loading
                    </div>
                    <div className="flex flex-col items-end gap-1">
                        <div className="flex items-center gap-2">
                            {getStatusIcon()}
                            <Badge variant={isCompleted ? 'default' : 'secondary'}>{getStatusText()}</Badge>
                            {isCompleted && (
                                <Button
                                    type="button"
                                    size="xs"
                                    variant="outline"
                                    onClick={() => setEditMode((prev) => !prev)}
                                    disabled={saving || ensuring}
                                >
                                    {editMode ? 'Cancel edit' : 'Edit'}
                                </Button>
                            )}
                        </div>
                        <div className="flex items-center gap-2 text-xs" />
                    </div>
                </CardTitle>
                <CardDescription>Load each wagon with specified quantity</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {rake.wagons.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No wagons are registered for this rake. Add wagons before recording loader
                        weighment.
                    </p>
                ) : (
                    showWorkflowPanel && (
                        <div>
                            {editMode && (
                                <div className="flex items-center justify-between mb-2">
                                    <Label className="text-base font-medium">Wagon loadings</Label>
                                </div>
                            )}
                            {editMode && !isCompleted && rows.length === 0 && (ensuring || needsEnsureAll) && (
                                <p className="mb-3 text-sm text-muted-foreground">
                                    Loading one row per wagon…
                                </p>
                            )}
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                }}
                            >
                                {error && (
                                    <div className="mb-3 text-sm text-destructive">
                                        {error}
                                    </div>
                                )}
                                <div
                                    className={
                                        (compact ? 'max-h-80 overflow-y-auto ' : '') +
                                        'border rounded-lg'
                                    }
                                >
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Wagon</TableHead>
                                                <TableHead>Loader</TableHead>
                                                <TableHead>Loader operator</TableHead>
                                                <TableHead>Wagon Type</TableHead>
                                                <TableHead>PCC Capacity</TableHead>
                                                <TableHead>Loaded Qty (MT)</TableHead>
                                                <TableHead>Loading Time</TableHead>
                                                <TableHead>Remarks</TableHead>
                                                <TableHead className="w-[120px]">Update</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {rows.length === 0 && ensuring ? (
                                                <TableRow>
                                                    <TableCell colSpan={7} className="text-center text-muted-foreground text-sm py-8">
                                                        Preparing rows…
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                rows.map((row) => {
                                                    const wagonForRow = rake.wagons.find(
                                                        (w) => String(w.id) === row.wagon_id
                                                    );
                                                    const isUnfitRow = wagonForRow?.is_unfit === true;

                                                    return (
                                                        <TableRow
                                                            key={row.key}
                                                            className={
                                                                isUnfitRow
                                                                    ? 'bg-red-950/40 dark:bg-red-950/50 border-b border-red-900/55'
                                                                    : undefined
                                                            }
                                                        >
                                                            <TableCell className="min-w-[140px]">
                                                                <div className="flex flex-col gap-1">
                                                                    {isUnfitRow && (
                                                                        <span className="text-xs font-semibold uppercase tracking-wide text-red-950 dark:text-red-100">
                                                                            Unfit wagon
                                                                        </span>
                                                                    )}
                                                                    <span className="font-medium tabular-nums">
                                                                        {row.wagon_number ||
                                                                            wagonForRow?.wagon_number ||
                                                                            '—'}
                                                                    </span>
                                                                    <span className="text-xs text-muted-foreground">
                                                                        Pos{' '}
                                                                        {wagonForRow?.wagon_sequence ?? '-'}
                                                                    </span>
                                                                </div>
                                                            </TableCell>
                                                            <TableCell className="min-w-[180px]">
                                                                <Select
                                                                    value={row.loader_id || '__none__'}
                                                                    onValueChange={(value) => {
                                                                        const loaderId = value === '__none__' ? null : value;
                                                                        updateRow(row.key, 'loader_id', loaderId ? String(loaderId) : '');
                                                                    }}
                                                                    disabled={disabled || tableReadOnly}
                                                                >
                                                                    <SelectTrigger className="w-full min-w-[140px]">
                                                                        <SelectValue placeholder="No loader" />
                                                                    </SelectTrigger>
                                                                    <SelectContent>
                                                                        <SelectItem value="__none__">No loader</SelectItem>
                                                                        {loaders.map((loader) => (
                                                                            <SelectItem key={loader.id} value={String(loader.id)}>
                                                                                {loader.loader_name}{' '}
                                                                                {loader.code ? `(${loader.code})` : ''}
                                                                            </SelectItem>
                                                                        ))}
                                                                    </SelectContent>
                                                                </Select>
                                                            </TableCell>
                                                            <TableCell className="min-w-[180px]">
                                                                <Input
                                                                    value={row.loader_operator_name}
                                                                    onChange={(e) =>
                                                                        updateRow(
                                                                            row.key,
                                                                            'loader_operator_name',
                                                                            e.target.value
                                                                        )
                                                                    }
                                                                    placeholder="Operator name"
                                                                    disabled={disabled || tableReadOnly}
                                                                    className="w-44"
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <Input
                                                                    value={row.wagon_type}
                                                                    readOnly
                                                                    placeholder="Auto"
                                                                    className="bg-muted w-24"
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <Input
                                                                    value={row.pcc_capacity}
                                                                    readOnly
                                                                    placeholder="Auto"
                                                                    className="bg-muted w-20"
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <Input
                                                                    // Use text to avoid browser spinners and preserve fast, permissive typing.
                                                                    type="text"
                                                                    inputMode="decimal"
                                                                    pattern="[0-9]*[.,]?[0-9]*"
                                                                    value={row.loaded_quantity_mt}
                                                                    onChange={(e) =>
                                                                        updateRow(row.key, 'loaded_quantity_mt', e.target.value)
                                                                    }
                                                                    placeholder="0"
                                                                    disabled={disabled || tableReadOnly}
                                                                    className="w-24"
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <Input
                                                                    type="datetime-local"
                                                                    value={row.loading_time}
                                                                    onChange={(e) =>
                                                                        updateRow(row.key, 'loading_time', e.target.value)
                                                                    }
                                                                    disabled={disabled || tableReadOnly}
                                                                    className="w-40"
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <Input
                                                                    value={row.remarks}
                                                                    onChange={(e) => updateRow(row.key, 'remarks', e.target.value)}
                                                                    placeholder="Remarks"
                                                                    disabled={disabled || tableReadOnly}
                                                                    className="w-28"
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <Button
                                                                    type="button"
                                                                    size="sm"
                                                                    onClick={() => void saveRow(row)}
                                                                    disabled={
                                                                        disabled ||
                                                                        tableReadOnly ||
                                                                        ensuring ||
                                                                        (savingRowKey !== null && savingRowKey !== row.key)
                                                                    }
                                                                    className="w-full"
                                                                >
                                                                    {savingRowKey === row.key ? (
                                                                        <>
                                                                            <Loader className="mr-2 h-4 w-4 animate-spin" />
                                                                            Saving…
                                                                        </>
                                                                    ) : (
                                                                        'Update'
                                                                    )}
                                                                </Button>
                                                            </TableCell>
                                                        </TableRow>
                                                    );
                                                })
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                                <div className="flex justify-end mt-4 text-xs text-muted-foreground">
                                    Click Update to save each row.
                                </div>
                            </form>
                        </div>
                    )
                )}
            </CardContent>
        </Card>
    );
}
