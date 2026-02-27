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
import { SearchableSelect, type SearchableSelectOption } from '@/components/ui/searchable-select';
import { Package, CheckCircle, Clock, Loader, Plus, Trash2 } from 'lucide-react';
import { router } from '@inertiajs/react';
import { useState, useMemo } from 'react';

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
        wagons: Wagon[];
        wagonLoadings?: WagonLoadingRecord[];
        siding?: { loaders?: LoaderOption[] } | null;
    };
    disabled: boolean;
}

interface LoadingRow {
    key: string;
    wagon_id: string;
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
        loader_id: '',
        wagon_type: '',
        pcc_capacity: '',
        loaded_quantity_mt: '',
        loading_time: new Date().toISOString().slice(0, 16),
        remarks: '',
    };
}

export function WagonLoadingWorkflow({ rake, disabled }: WagonLoadingWorkflowProps) {
    const existingLoadings = rake.wagonLoadings ?? [];
    const fitWagons = rake.wagons.filter((w) => !w.is_unfit);

    const initialRows: LoadingRow[] = useMemo(() => {
        if (existingLoadings.length === 0) return [newLoadingRow()];
        return existingLoadings.map((l) => ({
            key: `load-${l.wagon_id}-${l.id ?? Date.now()}`,
            wagon_id: String(l.wagon_id),
            loader_id: l.loader_id ? String(l.loader_id) : '',
            wagon_type: l.wagon?.wagon_type ?? '',
            pcc_capacity: l.wagon?.pcc_weight_mt ?? '',
            loaded_quantity_mt: l.loaded_quantity_mt ?? '',
            loading_time: l.loading_time
                ? new Date(l.loading_time).toISOString().slice(0, 16)
                : new Date().toISOString().slice(0, 16),
            remarks: l.remarks ?? '',
        }));
    }, [rake.id, existingLoadings.length]);

    const [rows, setRows] = useState<LoadingRow[]>(initialRows);

    const wagonOptions: SearchableSelectOption[] = useMemo(
        () =>
            fitWagons.map((w) => ({
                value: String(w.id),
                label: w.wagon_number,
                meta: String(w.wagon_sequence),
            })),
        [fitWagons]
    );

    const loaderOptions: SearchableSelectOption[] = useMemo(
        () =>
            (rake.siding?.loaders ?? []).map((l) => ({
                value: String(l.id),
                label: l.loader_name,
                meta: l.code,
            })),
        [rake.siding?.loaders]
    );

    const loadedWagonIds = useMemo(
        () => new Set(existingLoadings.map((l) => l.wagon_id)),
        [existingLoadings]
    );
    const unloadedWagons = fitWagons.filter((w) => !loadedWagonIds.has(w.id));
    const isCompleted = fitWagons.length > 0 && unloadedWagons.length === 0;

    const handleSave = (e: React.FormEvent) => {
        e.preventDefault();
        const loadings = rows
            .filter((r) => r.wagon_id && r.loader_id && r.loaded_quantity_mt)
            .map((r) => ({
                wagon_id: Number(r.wagon_id),
                loader_id: Number(r.loader_id),
                loaded_quantity_mt: r.loaded_quantity_mt,
                loading_time: r.loading_time || new Date().toISOString(),
                remarks: r.remarks || null,
            }));
        router.post(`/rakes/${rake.id}/load/wagons`, { loadings }, { preserveScroll: true });
    };

    const addRow = () => {
        setRows((prev) => [...prev, newLoadingRow()]);
    };

    const removeRow = (key: string) => {
        setRows((prev) => prev.filter((r) => r.key !== key));
    };

    const updateRow = (key: string, field: keyof LoadingRow, value: string) => {
        setRows((prev) =>
            prev.map((r) => {
                if (r.key !== key) return r;
                const next = { ...r, [field]: value };
                if (field === 'wagon_id') {
                    const wagon = fitWagons.find((w) => String(w.id) === value);
                    next.wagon_type = wagon?.wagon_type ?? '';
                    next.pcc_capacity = wagon?.pcc_weight_mt ?? '';
                }
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
                    <div className="flex items-center gap-2">
                        {getStatusIcon()}
                        <Badge variant={isCompleted ? 'default' : 'secondary'}>{getStatusText()}</Badge>
                    </div>
                </CardTitle>
                <CardDescription>Load each wagon with specified quantity</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {existingLoadings.length > 0 && (
                    <div>
                        <Label className="text-base font-medium">Loaded Wagons</Label>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Wagon</TableHead>
                                    <TableHead>Loader</TableHead>
                                    <TableHead>Quantity (MT)</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {existingLoadings.map((loading) => (
                                    <TableRow key={loading.id ?? loading.wagon_id}>
                                        <TableCell>
                                            {loading.wagon?.wagon_number} (Pos {loading.wagon?.wagon_sequence})
                                        </TableCell>
                                        <TableCell>
                                            {loading.loader?.loader_name} ({loading.loader?.code})
                                        </TableCell>
                                        <TableCell>{loading.loaded_quantity_mt}</TableCell>
                                        <TableCell>
                                            <Badge variant="default">Loaded</Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {!isCompleted && (
                    <div>
                        <div className="flex items-center justify-between mb-2">
                            <Label className="text-base font-medium">Add Wagon Loadings</Label>
                            <Button type="button" variant="outline" size="sm" onClick={addRow}>
                                <Plus className="mr-1 h-4 w-4" />
                                Add Row
                            </Button>
                        </div>
                        <form onSubmit={handleSave}>
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
                                                <TableCell className="min-w-[160px]">
                                                    <SearchableSelect
                                                        options={wagonOptions}
                                                        value={row.wagon_id}
                                                        onValueChange={(v) => updateRow(row.key, 'wagon_id', v)}
                                                        placeholder="Select wagon"
                                                        disabled={disabled}
                                                        renderOption={(o) => (
                                                            <span>
                                                                {o.label} ({o.meta})
                                                            </span>
                                                        )}
                                                    />
                                                </TableCell>
                                                <TableCell className="min-w-[160px]">
                                                    <SearchableSelect
                                                        options={loaderOptions}
                                                        value={row.loader_id}
                                                        onValueChange={(v) => updateRow(row.key, 'loader_id', v)}
                                                        placeholder="Select loader"
                                                        disabled={disabled}
                                                        renderOption={(o) => (
                                                            <span>
                                                                {o.label} ({o.meta})
                                                            </span>
                                                        )}
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
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        value={row.loaded_quantity_mt}
                                                        onChange={(e) =>
                                                            updateRow(row.key, 'loaded_quantity_mt', e.target.value)
                                                        }
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
                                                        disabled={disabled}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                            <div className="flex justify-end mt-4">
                                <Button type="submit" disabled={disabled}>
                                    Save Wagon Loadings
                                </Button>
                            </div>
                        </form>
                    </div>
                )}

                {isCompleted && (
                    <div className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label>Total Wagons Loaded</Label>
                                <p className="text-2xl font-bold">{existingLoadings.length}</p>
                            </div>
                            <div>
                                <Label>Total Quantity</Label>
                                <p className="text-2xl font-bold">
                                    {existingLoadings
                                        .reduce(
                                            (sum, l) => sum + parseFloat(l.loaded_quantity_mt || '0'),
                                            0
                                        )
                                        .toFixed(2)}{' '}
                                    MT
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2 text-sm text-green-600">
                            <CheckCircle className="h-4 w-4" />
                            All wagons loaded successfully
                        </div>
                    </div>
                )}

                {disabled && !isCompleted && (
                    <div className="text-center py-4 text-sm text-muted-foreground">
                        Complete TXR to enable wagon loading
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
