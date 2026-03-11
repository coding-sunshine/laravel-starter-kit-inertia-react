import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { SearchableSelect, type SearchableSelectOption } from '@/components/ui/searchable-select';
import InputError from '@/components/input-error';
import { Plus, Trash2, AlertTriangle, Loader2 } from 'lucide-react';
import { useState, useMemo, useCallback } from 'react';

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
    reason_unfit?: string | null;
    marked_by?: string | null;
    marking_method?: string | null;
    marked_at?: string | null;
}

interface TxrData {
    id: number;
    rake_id: number;
}

interface UnfitWagonTableProps {
    rake: {
        id: number;
        rake_number: string;
        wagons: Wagon[];
        txr: (TxrData & { wagonUnfitLogs?: WagonUnfitLog[] }) | null;
        wagonUnfitLogs?: WagonUnfitLog[];
    };
    disabled: boolean;
    onUnfitLogsSaved?: (logs: WagonUnfitLog[]) => void;
}

interface UnfitWagonRow {
    key: string;
    wagon_id: string;
    wagon_number: string;
    wagon_type: string;
    reason_unfit: string;
    marked_by: string;
    marking_method: string;
    marked_at: string;
}

const MARKING_METHODS = [
    { value: 'flag', label: 'Flag' },
    { value: 'light', label: 'Light' },
];

function newUnfitWagonRow(): UnfitWagonRow {
    return {
        key: `unfit-${Date.now()}-${Math.random().toString(36).slice(2)}`,
        wagon_id: '',
        wagon_number: '',
        wagon_type: '',
        reason_unfit: '',
        marked_by: '',
        marking_method: '',
        marked_at: new Date().toISOString().slice(0, 16),
    };
}

export function UnfitWagonTable({ rake, disabled, onUnfitLogsSaved }: UnfitWagonTableProps) {
    const logsSource =
        rake.wagonUnfitLogs ??
        rake.txr?.wagonUnfitLogs ??
        (rake.txr as { wagon_unfit_logs?: WagonUnfitLog[] } | undefined)?.wagon_unfit_logs ??
        [];
    const initialUnfitRows: UnfitWagonRow[] = useMemo(() => {
        const logs = logsSource;
        if (logs.length === 0) return [newUnfitWagonRow()];

        return logs.map((log) => ({
            key: `unfit-${log.wagon_id}-${log.id ?? Date.now()}`,
            wagon_id: String(log.wagon_id),
            wagon_number: log.wagon?.wagon_number ?? '',
            wagon_type: log.wagon?.wagon_type ?? '',
            reason_unfit: (log as { reason_unfit?: string; reason?: string }).reason_unfit ?? (log as { reason?: string }).reason ?? '',
            marked_by: log.marked_by ?? '',
            marking_method: log.marking_method ?? '',
            marked_at: log.marked_at
                ? new Date(log.marked_at).toISOString().slice(0, 16)
                : new Date().toISOString().slice(0, 16),
        }));
    }, [logsSource]);

    const [unfitRows, setUnfitRows] = useState<UnfitWagonRow[]>(initialUnfitRows);
    const [saving, setSaving] = useState(false);
    const [formError, setFormError] = useState<string | null>(null);

    const wagonOptions: SearchableSelectOption[] = useMemo(
        () =>
            rake.wagons.map((w) => ({
                value: String(w.id),
                label: `${w.wagon_number} (${w.wagon_sequence})`,
                meta: `${w.wagon_type || 'N/A'}`,
            })),
        [rake.wagons]
    );

    const handleSubmit = useCallback(
        async (e: React.FormEvent) => {
            e.preventDefault();
            setFormError(null);
            if (!rake.txr) return;

            const payload = {
                unfit_logs: unfitRows
                    .filter((r) => r.wagon_id)
                    .map((row) => ({
                        wagon_id: row.wagon_id,
                        reason_unfit: row.reason_unfit,
                        marking_method: row.marking_method,
                        marked_at: row.marked_at || null,
                    })),
            };

            setSaving(true);
            try {
                const res = await fetch(`/rakes/${rake.id}/txr/unfit-logs`, {
                    method: 'POST',
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
                    const err = data as { message?: string; errors?: Record<string, string[]> };
                    const msg =
                        err.message ??
                        (err.errors ? Object.values(err.errors).flat().join(', ') : null) ??
                        res.statusText ??
                        'Save failed';
                    setFormError(msg);
                    return;
                }
                const logs = (data as { wagonUnfitLogs?: WagonUnfitLog[] }).wagonUnfitLogs ?? [];
                onUnfitLogsSaved?.(logs);
            } finally {
                setSaving(false);
            }
        },
        [rake.id, rake.txr, unfitRows, onUnfitLogsSaved]
    );

    const addUnfitRow = () => {
        setUnfitRows((prev) => [...prev, newUnfitWagonRow()]);
    };

    const removeUnfitRow = (key: string) => {
        setUnfitRows((prev) => prev.filter((r) => r.key !== key));
    };

    const updateUnfitRow = (key: string, field: keyof UnfitWagonRow, value: string) => {
        setUnfitRows((prev) =>
            prev.map((r) => {
                if (r.key !== key) return r;
                const next = { ...r, [field]: value };

                if (field === 'wagon_id') {
                    const wagon = rake.wagons.find((w) => String(w.id) === value);
                    next.wagon_number = wagon?.wagon_number ?? '';
                    next.wagon_type = wagon?.wagon_type ?? '';
                }

                return next;
            })
        );
    };

    const handleReset = () => {
        setUnfitRows(initialUnfitRows);
        setFormError(null);
    };

    if (!rake.txr) {
        return (
            <Card>
                <CardContent className="p-8 text-center text-sm text-muted-foreground">
                    <AlertTriangle className="mx-auto h-8 w-8 mb-2" />
                    TXR must be started before adding unfit wagon details.
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div>Unfit Wagon Details</div>
                    <Button type="button" variant="outline" size="sm" onClick={addUnfitRow} disabled={disabled}>
                        <Plus className="mr-1 h-4 w-4" />
                        Add Row
                    </Button>
                </CardTitle>
                <CardDescription>
                    Record wagon fitness details during TXR inspection
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-4">
                    {formError && (
                        <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
                            {formError}
                        </div>
                    )}
                    <div className="max-h-80 overflow-y-auto border rounded-lg">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Rake No</TableHead>
                                    <TableHead>Wagon No</TableHead>
                                    <TableHead>Wagon Type</TableHead>
                                    <TableHead>Reason Unfit</TableHead>
                                    <TableHead>Marked By</TableHead>
                                    <TableHead>Marking Method</TableHead>
                                    <TableHead>Time</TableHead>
                                    <TableHead className="w-12"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {unfitRows.map((row) => (
                                    <TableRow key={row.key}>
                                        <TableCell className="font-medium">
                                            {rake.rake_number}
                                        </TableCell>
                                        <TableCell className="min-w-[180px]">
                                            <SearchableSelect
                                                options={wagonOptions}
                                                value={row.wagon_id}
                                                onValueChange={(v) => updateUnfitRow(row.key, 'wagon_id', v)}
                                                placeholder="Select wagon"
                                                disabled={disabled}
                                                renderOption={(o) => (
                                                    <span>
                                                        {o.label} - {o.meta}
                                                    </span>
                                                )}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                value={row.wagon_type}
                                                readOnly
                                                placeholder="Auto-filled"
                                                className="bg-muted"
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                value={row.reason_unfit}
                                                onChange={(e) =>
                                                    updateUnfitRow(row.key, 'reason_unfit', e.target.value)
                                                }
                                                placeholder="Reason for unfit"
                                                disabled={disabled}
                                            />
                                            <InputError message={null} />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                value={row.marked_by}
                                                onChange={(e) =>
                                                    updateUnfitRow(row.key, 'marked_by', e.target.value)
                                                }
                                                placeholder="Marked by"
                                                disabled={disabled}
                                            />
                                            <InputError message={null} />
                                        </TableCell>
                                        <TableCell>
                                            <select
                                                value={row.marking_method}
                                                onChange={(e) =>
                                                    updateUnfitRow(row.key, 'marking_method', e.target.value)
                                                }
                                                disabled={disabled}
                                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            >
                                                <option value="">Select</option>
                                                {MARKING_METHODS.map((m) => (
                                                    <option key={m.value} value={m.value}>
                                                        {m.label}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={null} />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                type="datetime-local"
                                                value={row.marked_at}
                                                onChange={(e) =>
                                                    updateUnfitRow(row.key, 'marked_at', e.target.value)
                                                }
                                                disabled={disabled}
                                            />
                                            <InputError message={null} />
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => removeUnfitRow(row.key)}
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

                    <div className="flex justify-end space-x-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleReset}
                            disabled={disabled}
                        >
                            Reset
                        </Button>
                        <Button type="submit" disabled={disabled || saving}>
                            {saving ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Saving...
                                </>
                            ) : (
                                'Save Unfit Wagons'
                            )}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
