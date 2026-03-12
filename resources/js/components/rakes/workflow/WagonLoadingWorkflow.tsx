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
import { Package, CheckCircle, Clock, Loader, Plus, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

function getCsrfHeaders(): Record<string, string> {
    const cookieMatch = document.cookie.match(/\bXSRF-TOKEN=([^;]+)/);
    if (cookieMatch) {
        return { 'X-XSRF-TOKEN': decodeURIComponent(cookieMatch[1].trim()) };
    }
    const meta = document.querySelector('meta[name=\"csrf-token\"]');
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
    wagon?: { wagon_number: string; wagon_sequence: number; wagon_type?: string | null; pcc_weight_mt?: string | null };
    loader_id?: number | null;
    loader?: { loader_name: string; code: string };
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
    onWagonUpdated?: (wagonId: number, updates: { wagon_number: string }) => void;
}

interface LoadingRow {
    id?: number;
    key: string;
    wagon_id: string;
    wagon_number: string;
    loader_id: string;
    wagon_type: string;
    pcc_capacity: string;
    loaded_quantity_mt: string;
    loading_time: string;
    remarks: string;
}

function newLoadingRow(): LoadingRow {
    return {
        key: `load-${Date.now()}-${Math.random().toString(36).slice(2)}`,
        wagon_id: '',
        wagon_number: '',
        loader_id: '',
        wagon_type: '',
        pcc_capacity: '',
        loaded_quantity_mt: '',
        loading_time: new Date().toISOString().slice(0, 16),
        remarks: '',
    };
}

const EMPTY_LOADINGS: WagonLoadingRecord[] = [];

export function WagonLoadingWorkflow({
    rake,
    disabled,
    onWagonLoadingsSaved,
    onWagonUpdated,
}: WagonLoadingWorkflowProps) {
    const existingLoadings = rake.wagonLoadings ?? rake.wagon_loadings ?? EMPTY_LOADINGS;
    const fitWagons = rake.wagons.filter((w) => !w.is_unfit);

    const [rows, setRows] = useState<LoadingRow[]>([]);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const isStopped = false;

    useEffect(() => {
        const nextRows: LoadingRow[] =
            existingLoadings.length === 0
                ? []
                : existingLoadings.map((l) => ({
                      id: l.id,
                      key: `load-${l.wagon_id}-${l.id ?? Date.now()}`,
                      wagon_id: String(l.wagon_id),
                      wagon_number: l.wagon?.wagon_number ?? '',
                      loader_id: l.loader_id ? String(l.loader_id) : '',
                      wagon_type: l.wagon?.wagon_type ?? '',
                      pcc_capacity: l.wagon?.pcc_weight_mt ?? '',
                      loaded_quantity_mt: l.loaded_quantity_mt ?? '',
                      loading_time: l.loading_time
                          ? new Date(l.loading_time).toISOString().slice(0, 16)
                          : new Date().toISOString().slice(0, 16),
                      remarks: l.remarks ?? '',
                  }));
        setRows(nextRows);
    }, [rake.id, existingLoadings]);

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
    const unloadedWagons = fitWagons.filter((w) => !positivelyLoadedWagonIds.has(w.id));
    const isCompleted = fitWagons.length > 0 && unloadedWagons.length === 0;

    const [editMode, setEditMode] = useState(!isCompleted);

    useEffect(() => {
        setEditMode(!isCompleted);
    }, [isCompleted]);

    const handleSave = async () => {
        setError(null);

        const loadings = rows
            .filter((r) => r.wagon_id && r.loader_id && r.loaded_quantity_mt)
            .map((r) => ({
                wagon_id: Number(r.wagon_id),
                loader_id: Number(r.loader_id),
                loaded_quantity_mt: r.loaded_quantity_mt,
                loading_time: r.loading_time || new Date().toISOString(),
                remarks: r.remarks || null,
            }));

        if (loadings.length === 0) {
            setError('Add at least one wagon loading with wagon, loader and quantity.');
            return;
        }

        setSaving(true);
        try {
            const response = await fetch(`/rakes/${rake.id}/load/wagons`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    ...getCsrfHeaders(),
                },
                body: JSON.stringify({ loadings }),
            });

            const data = (await response.json().catch(() => null)) as
                | { wagonLoadings?: WagonLoadingRecord[]; message?: string }
                | null;

            if (!response.ok) {
                setError(data?.message ?? 'Failed to save wagon loadings.');
                return;
            }

            const updatedLoadings = data?.wagonLoadings ?? [];
            onWagonLoadingsSaved?.(updatedLoadings);

            const nextRows: LoadingRow[] =
                updatedLoadings.length > 0
                    ? updatedLoadings.map((l) => ({
                          id: l.id,
                          key: `load-${l.wagon_id}-${l.id ?? Date.now()}`,
                          wagon_id: String(l.wagon_id),
                          wagon_number: l.wagon?.wagon_number ?? '',
                          loader_id: l.loader_id ? String(l.loader_id) : '',
                          wagon_type: l.wagon?.wagon_type ?? '',
                          pcc_capacity: l.wagon?.pcc_weight_mt ?? '',
                          loaded_quantity_mt: l.loaded_quantity_mt ?? '',
                          loading_time: l.loading_time
                              ? new Date(l.loading_time).toISOString().slice(0, 16)
                              : new Date().toISOString().slice(0, 16),
                          remarks: l.remarks ?? '',
                      }))
                    : [newLoadingRow()];

            setRows(nextRows);
        } catch {
            setError('Failed to save wagon loadings.');
        } finally {
            setSaving(false);
        }
    };

    const addRow = async () => {
        setError(null);

        const fitWagonsOrdered = [...fitWagons].sort((a, b) => a.wagon_sequence - b.wagon_sequence);
        const loadedIds = new Set(existingLoadings.map((l) => l.wagon_id));
        const nextWagon = fitWagonsOrdered.find((w) => !loadedIds.has(w.id));

        if (!nextWagon) {
            setError('All fit wagons are already loaded.');
            return;
        }

        setSaving(true);
        try {
            const response = await fetch(`/rakes/${rake.id}/load/wagon-rows`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    ...getCsrfHeaders(),
                },
                body: JSON.stringify({ wagon_id: nextWagon.id }),
            });

            const data = (await response.json().catch(() => null)) as
                | { loading?: WagonLoadingRecord; message?: string }
                | null;

            if (!response.ok || !data?.loading) {
                setError(data?.message ?? 'Failed to add wagon loading row.');
                return;
            }

            const updatedLoadings = [...existingLoadings, data.loading];
            onWagonLoadingsSaved?.(updatedLoadings);
        } catch {
            setError('Failed to add wagon loading row.');
        } finally {
            setSaving(false);
        }
    };

    const removeRow = async (key: string) => {
        const row = rows.find((r) => r.key === key);
        if (row?.id) {
            setError(null);
            setSaving(true);
            try {
                const response = await fetch(
                    `/rakes/${rake.id}/load/wagon-rows/${row.id}`,
                    {
                        method: 'DELETE',
                        headers: { Accept: 'application/json', ...getCsrfHeaders() },
                    }
                );
                const data = (await response.json().catch(() => null)) as
                    | { deleted?: boolean; message?: string }
                    | null;
                if (!response.ok) {
                    setError(data?.message ?? 'Failed to remove wagon loading.');
                    return;
                }
                const merged = existingLoadings.filter((l) => l.id !== row.id);
                onWagonLoadingsSaved?.(merged);
            } catch {
                setError('Failed to remove wagon loading.');
            } finally {
                setSaving(false);
            }
        }
        setRows((prev) => prev.filter((r) => r.key !== key));
    };

    const updateRow = (key: string, field: keyof LoadingRow, value: string) => {
        setRows((prev) =>
            prev.map((r) => {
                if (r.key !== key) return r;
                const next = { ...r, [field]: value };
                return next;
            })
        );
    };

    const getStatusIcon = () => {
        if (isCompleted) return <CheckCircle className="h-4 w-4 text-green-600" />;
        if (existingLoadings.length > 0) return <Loader className="h-4 w-4 text-blue-600" />;
        return <Clock className="h-4 w-4" />;
    };

    const getStatusText = () => {
        if (isCompleted) return 'Completed';
        if (existingLoadings.length > 0) return 'In Progress';
        return 'Not Started';
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
                                    disabled={saving}
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
                {editMode && (
                    <div>
                        <div className="flex items-center justify-between mb-2">
                            <Label className="text-base font-medium">Add Wagon Loadings</Label>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addRow}
                                disabled={disabled || saving}
                            >
                                <Plus className="mr-1 h-4 w-4" />
                                Add Row
                            </Button>
                        </div>
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
                            <div className="max-h-80 overflow-y-auto border rounded-lg">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Wagon</TableHead>
                                            <TableHead>Loader</TableHead>
                                            <TableHead>Wagon Type</TableHead>
                                            <TableHead>PCC Capacity</TableHead>
                                            <TableHead>Loaded Qty (MT)</TableHead>
                                            <TableHead>Loading Time</TableHead>
                                            <TableHead>Remarks</TableHead>
                                            <TableHead className="w-12"></TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {rows.map((row) => (
                                            <TableRow key={row.key}>
                                                <TableCell className="min-w-[140px]">
                                                    <div className="flex flex-col gap-1">
                                                        <Input
                                                            value={row.wagon_number}
                                                            onChange={(e) =>
                                                                updateRow(row.key, 'wagon_number', e.target.value)
                                                            }
                                                            onBlur={async (e) => {
                                                                const value = e.target.value.trim();
                                                                const currentRow = rows.find((r) => r.key === row.key);
                                                                if (!currentRow?.wagon_id || value === (fitWagons.find((w) => String(w.id) === currentRow.wagon_id)?.wagon_number ?? '')) {
                                                                    return;
                                                                }
                                                                setError(null);
                                                                setSaving(true);
                                                                try {
                                                                    const response = await fetch(
                                                                        `/rakes/${rake.id}/wagons/${currentRow.wagon_id}`,
                                                                        {
                                                                            method: 'PUT',
                                                                            headers: {
                                                                                Accept: 'application/json',
                                                                                'Content-Type': 'application/json',
                                                                                ...getCsrfHeaders(),
                                                                            },
                                                                            body: JSON.stringify({
                                                                                wagon_number: value,
                                                                            }),
                                                                        }
                                                                    );
                                                                    const data = (await response.json().catch(() => null)) as
                                                                        | { wagon?: { id: number; wagon_number: string } }
                                                                        | null;
                                                                    if (!response.ok) {
                                                                        const msg = data && typeof data === 'object' && 'message' in data ? (data as { message?: string }).message : null;
                                                                        setError(msg ?? 'Failed to update wagon number.');
                                                                        return;
                                                                    }
                                                                    if (data?.wagon) {
                                                                        onWagonUpdated?.(data.wagon.id, { wagon_number: data.wagon.wagon_number });
                                                                    }
                                                                } catch {
                                                                    setError('Failed to update wagon number.');
                                                                } finally {
                                                                    setSaving(false);
                                                                }
                                                            }}
                                                            placeholder="Wagon number"
                                                            disabled={disabled}
                                                            className="w-full"
                                                        />
                                                        {row.wagon_id && (
                                                            <span className="text-xs text-muted-foreground">
                                                                Pos {fitWagons.find((w) => String(w.id) === row.wagon_id)?.wagon_sequence ?? '-'}
                                                            </span>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="min-w-[180px]">
                                                    <Select
                                                        value={row.loader_id || '__none__'}
                                                        onValueChange={async (value) => {
                                                            const loaderId = value === '__none__' ? null : value;
                                                            const currentRow = rows.find((r) => r.key === row.key);
                                                            if (!currentRow?.id) return;
                                                            updateRow(row.key, 'loader_id', loaderId ? String(loaderId) : '');
                                                            setError(null);
                                                            setSaving(true);
                                                            try {
                                                                const response = await fetch(
                                                                    `/rakes/${rake.id}/load/wagon-rows/${currentRow.id}`,
                                                                    {
                                                                        method: 'PATCH',
                                                                        headers: {
                                                                            Accept: 'application/json',
                                                                            'Content-Type': 'application/json',
                                                                            ...getCsrfHeaders(),
                                                                        },
                                                                        body: JSON.stringify({
                                                                            loader_id: loaderId,
                                                                        }),
                                                                    }
                                                                );
                                                                const data = (await response.json().catch(() => null)) as
                                                                    | { loading?: WagonLoadingRecord; message?: string }
                                                                    | null;
                                                                if (!response.ok || !data?.loading) {
                                                                    setError(data?.message ?? 'Failed to update loader.');
                                                                    return;
                                                                }
                                                                const merged = existingLoadings.map((l) =>
                                                                    l.id === data.loading!.id ? data.loading! : l
                                                                );
                                                                onWagonLoadingsSaved?.(merged);
                                                            } catch {
                                                                setError('Failed to update loader.');
                                                            } finally {
                                                                setSaving(false);
                                                            }
                                                        }}
                                                        disabled={disabled}
                                                    >
                                                        <SelectTrigger className="w-full min-w-[140px]">
                                                            <SelectValue placeholder="No loader" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="__none__">No loader</SelectItem>
                                                            {loaders.map((loader) => (
                                                                <SelectItem key={loader.id} value={String(loader.id)}>
                                                                    {loader.loader_name} {loader.code ? `(${loader.code})` : ''}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
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
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        value={row.loaded_quantity_mt}
                                                        onChange={(e) =>
                                                            updateRow(row.key, 'loaded_quantity_mt', e.target.value)
                                                        }
                                                        onBlur={async (e) => {
                                                            const value = e.target.value;
                                                            const currentRow = rows.find((r) => r.key === row.key);
                                                            if (!currentRow?.id || !value) {
                                                                return;
                                                            }
                                                            setError(null);
                                                            setSaving(true);
                                                            try {
                                                                const response = await fetch(
                                                                    `/rakes/${rake.id}/load/wagon-rows/${currentRow.id}`,
                                                                    {
                                                                        method: 'PATCH',
                                                                        headers: {
                                                                            Accept: 'application/json',
                                                                            'Content-Type': 'application/json',
                                                                            ...getCsrfHeaders(),
                                                                        },
                                                                        body: JSON.stringify({
                                                                            loaded_quantity_mt: value,
                                                                        }),
                                                                    }
                                                                );
                                                                const data = (await response.json().catch(() => null)) as
                                                                    | { loading?: WagonLoadingRecord; message?: string }
                                                                    | null;
                                                                if (!response.ok || !data?.loading) {
                                                                    setError(
                                                                        data?.message ??
                                                                            'Failed to update loaded quantity.'
                                                                    );
                                                                    return;
                                                                }
                                                                const updated = data.loading;
                                                                const merged = existingLoadings.map((l) =>
                                                                    l.id === updated.id ? updated : l
                                                                );
                                                                onWagonLoadingsSaved?.(merged);
                                                            } catch {
                                                                setError('Failed to update loaded quantity.');
                                                            } finally {
                                                                setSaving(false);
                                                            }
                                                        }}
                                                        placeholder="0"
                                                        disabled={disabled}
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
                                                        disabled={disabled}
                                                        className="w-40"
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        value={row.remarks}
                                                        onChange={(e) => updateRow(row.key, 'remarks', e.target.value)}
                                                        placeholder="Remarks"
                                                        disabled={disabled}
                                                        className="min-w-[100px]"
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => removeRow(row.key)}
                                                        disabled={disabled || saving}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                            <div className="flex justify-end mt-4 text-xs text-muted-foreground">
                                Changes are saved automatically.
                            </div>
                        </form>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
