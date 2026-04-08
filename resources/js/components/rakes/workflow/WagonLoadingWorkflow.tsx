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
import { cn } from '@/lib/utils';
import { CheckCircle, Clock, Loader, Package } from 'lucide-react';
import { useCallback, useEffect, useId, useMemo, useRef, useState } from 'react';

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
    loaded_quantity_mt: string;
    loading_time?: string | null;
    remarks?: string | null;
}

export type WagonLoadingTableVariant = 'default' | 'spreadsheet';

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
    /**
     * Spreadsheet-style grid (rake-loader loading page): auto-save, fewer columns, Excel-like borders.
     */
    tableVariant?: WagonLoadingTableVariant;
}

interface LoadingRow {
    id?: number;
    key: string;
    wagon_id: string;
    wagon_number: string;
    loader_id: string;
    loaded_quantity_mt: string;
    wagon_type?: string;
    pcc_capacity?: string;
    loading_time?: string;
    remarks?: string;
}

const EMPTY_LOADINGS: WagonLoadingRecord[] = [];

function spreadsheetRowMatchesServerLoading(
    local: LoadingRow | undefined,
    server: WagonLoadingRecord,
): boolean {
    if (!local) {
        return true;
    }
    const localLoader =
        local.loader_id && local.loader_id !== '__none__' ? Number(local.loader_id) : null;
    const serverLoader = server.loader_id ?? null;
    if (localLoader !== serverLoader) {
        return false;
    }
    const lq = parseFloat(String(local.loaded_quantity_mt).trim().replace(',', '.'));
    const sq = parseFloat(String(server.loaded_quantity_mt ?? '').trim().replace(',', '.'));
    if (Number.isFinite(lq) && Number.isFinite(sq)) {
        return lq === sq;
    }
    return (
        String(local.loaded_quantity_mt).trim() === String(server.loaded_quantity_mt ?? '').trim()
    );
}

/** Excel-style: move to qty in the same column on the next row. */
function focusNextRowQtyInput(fromElement: HTMLElement): boolean {
    const currentRow = fromElement.closest('tr');
    const nextRow = currentRow?.nextElementSibling;
    if (!(nextRow instanceof HTMLElement)) {
        return false;
    }

    const next = nextRow.querySelector<HTMLElement>('[data-field="rake-loader-qty"]');
    if (!next) {
        return false;
    }

    next.focus();
    if (next instanceof HTMLInputElement) {
        next.select();
    }
    return true;
}

function formatLoaderOptionLabel(loader: LoaderOption): string {
    return `${loader.loader_name}${loader.code ? ` (${loader.code})` : ''}`;
}

function toEditDigits(value: string): string {
    return value.replace(/\D+/g, '');
}

function toDisplayDecimal(value: string): string {
    const digits = toEditDigits(value);
    if (digits === '') {
        return '';
    }

    if (digits.length <= 2) {
        return digits;
    }

    const integerPart = digits.slice(0, 2);
    const fractionalPart = digits.slice(2);

    return `${integerPart}.${fractionalPart}`;
}

function parseLoadedQuantityForSave(value: string): number | null {
    const trimmed = value.trim();
    if (trimmed === '') {
        return null;
    }

    if (/[.,]/.test(trimmed)) {
        const decimalValue = parseFloat(trimmed.replace(',', '.'));
        return Number.isFinite(decimalValue) ? decimalValue : null;
    }

    const digits = toEditDigits(trimmed);
    if (digits === '') {
        return null;
    }

    if (digits.length <= 2) {
        const whole = Number.parseInt(digits, 10);
        return Number.isFinite(whole) ? whole : null;
    }

    const normalized = `${digits.slice(0, 2)}.${digits.slice(2)}`;
    const parsed = Number.parseFloat(normalized);

    return Number.isFinite(parsed) ? parsed : null;
}

interface LoaderListOption {
    id: string;
    label: string;
}

function buildLoaderListOptions(
    loaders: LoaderOption[],
    query: string,
    fullList: boolean,
): LoaderListOption[] {
    const q = query.trim().toLowerCase();
    const out: LoaderListOption[] = [];

    if (fullList) {
        out.push({ id: '__none__', label: 'No loader' });
        for (const loader of loaders) {
            out.push({ id: String(loader.id), label: formatLoaderOptionLabel(loader) });
        }
        return out;
    }

    if (!q) {
        return out;
    }

    const matchesNoneRow =
        'no loader'.includes(q) ||
        q === 'no' ||
        'clear'.includes(q) ||
        'none'.includes(q);

    if (matchesNoneRow) {
        out.push({ id: '__none__', label: 'No loader' });
    }

    for (const loader of loaders) {
        const name = loader.loader_name.toLowerCase();
        const code = (loader.code ?? '').toLowerCase();
        if (name.includes(q) || code.includes(q)) {
            out.push({ id: String(loader.id), label: formatLoaderOptionLabel(loader) });
        }
    }

    return out;
}

function initialLoaderInputQuery(loaders: LoaderOption[], value: string): string {
    const loader = loaders.find((l) => String(l.id) === value);
    return loader ? formatLoaderOptionLabel(loader) : '';
}

/**
 * Spreadsheet loader field: plain text input, suggestions appear when typing or pressing ↓.
 * Navigate list with ↑/↓, confirm with Enter. Focus stays in this cell so Tab moves to qty naturally.
 */
function SpreadsheetLoaderTypeahead({
    loaders,
    value,
    disabled,
    onValueChange,
}: {
    loaders: LoaderOption[];
    value: string;
    disabled: boolean;
    onValueChange: (value: string) => void;
}) {
    const skipQuerySyncRef = useRef(false);
    const listId = useId();
    const selectedLoader = loaders.find((l) => String(l.id) === value);
    const committedLabel = selectedLoader ? formatLoaderOptionLabel(selectedLoader) : '';

    const [query, setQuery] = useState(() => initialLoaderInputQuery(loaders, value));
    const [menuOpen, setMenuOpen] = useState(false);
    const [fullList, setFullList] = useState(false);
    const [highlightIndex, setHighlightIndex] = useState(0);
    const [isFocused, setIsFocused] = useState(false);

    useEffect(() => {
        if (!isFocused && !skipQuerySyncRef.current) {
            setQuery(committedLabel);
        }
    }, [committedLabel, isFocused, value]);

    const options = useMemo(
        () => buildLoaderListOptions(loaders, query, fullList),
        [loaders, query, fullList],
    );

    useEffect(() => {
        if (highlightIndex >= options.length) {
            setHighlightIndex(options.length > 0 ? options.length - 1 : 0);
        }
    }, [highlightIndex, options.length]);

    const closeMenu = () => {
        setMenuOpen(false);
        setFullList(false);
    };

    const applySelection = (optionId: string) => {
        skipQuerySyncRef.current = true;
        onValueChange(optionId);
        closeMenu();
        const loader =
            optionId === '__none__'
                ? null
                : loaders.find((l) => String(l.id) === optionId);
        setQuery(loader ? formatLoaderOptionLabel(loader) : '');
        window.setTimeout(() => {
            skipQuerySyncRef.current = false;
        }, 0);
    };

    const openFullListFromArrow = () => {
        setFullList(true);
        setMenuOpen(true);
        setHighlightIndex(0);
    };

    return (
        <div className="relative min-w-[140px]">
            <Input
                type="text"
                role="combobox"
                aria-expanded={menuOpen}
                aria-controls={menuOpen ? listId : undefined}
                aria-activedescendant={
                    menuOpen && options[highlightIndex]
                        ? `${listId}-opt-${highlightIndex}`
                        : undefined
                }
                autoComplete="off"
                disabled={disabled}
                data-field="rake-loader-loader"
                value={query}
                placeholder="Type loader name or code…"
                className="h-12 w-full px-2 text-xs"
                onChange={(e) => {
                    const next = e.target.value;
                    setQuery(next);
                    setFullList(false);
                    const hasText = next.trim().length > 0;
                    setMenuOpen(hasText);
                    setHighlightIndex(0);
                }}
                onFocus={() => {
                    setIsFocused(true);
                    closeMenu();
                }}
                onBlur={() => {
                    setIsFocused(false);
                    closeMenu();
                }}
                onKeyDown={(e) => {
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        if (!menuOpen) {
                            openFullListFromArrow();
                            return;
                        }
                        if (options.length === 0) {
                            return;
                        }
                        setHighlightIndex((i) => (i + 1) % options.length);
                        return;
                    }
                    if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        if (!menuOpen || options.length === 0) {
                            return;
                        }
                        setHighlightIndex((i) => (i - 1 + options.length) % options.length);
                        return;
                    }
                    if (e.key === 'Enter') {
                        if (menuOpen && options.length > 0 && options[highlightIndex]) {
                            e.preventDefault();
                            applySelection(options[highlightIndex].id);
                        }
                        return;
                    }
                    if (e.key === 'Escape') {
                        if (menuOpen) {
                            e.preventDefault();
                            closeMenu();
                            setQuery(committedLabel);
                        }
                    }
                }}
            />
            {menuOpen && options.length > 0 ? (
                <ul
                    id={listId}
                    role="listbox"
                    className="absolute left-0 top-full z-50 mt-0.5 max-h-48 w-full min-w-[200px] overflow-auto rounded border border-gray-300 bg-popover py-0.5 text-xs shadow-md"
                >
                    {options.map((opt, idx) => (
                        <li
                            key={opt.id === '__none__' ? `${listId}-no-loader` : `${listId}-loader-${opt.id}`}
                            id={`${listId}-opt-${idx}`}
                            role="option"
                            aria-selected={idx === highlightIndex}
                            className={cn(
                                'cursor-pointer px-2 py-1.5 text-left',
                                idx === highlightIndex
                                    ? 'bg-muted text-foreground'
                                    : 'hover:bg-muted/80',
                            )}
                            onMouseDown={(ev) => {
                                ev.preventDefault();
                                applySelection(opt.id);
                            }}
                            onMouseEnter={() => setHighlightIndex(idx)}
                        >
                            {opt.label}
                        </li>
                    ))}
                </ul>
            ) : null}
        </div>
    );
}

export function WagonLoadingWorkflow({
    rake,
    disabled,
    onWagonLoadingsSaved,
    compact = true,
    tableVariant = 'default',
}: WagonLoadingWorkflowProps) {
    const isSpreadsheet = tableVariant === 'spreadsheet';
    const spreadsheetCellClass =
        'focus-within:bg-green-100 focus-within:ring-2 focus-within:ring-green-500 focus-within:ring-inset dark:focus-within:bg-green-950/30';

    const existingLoadings = rake.wagonLoadings ?? rake.wagon_loadings ?? EMPTY_LOADINGS;
    /** All wagons on the rake (including unfit — loaders may still record quantities). */
    const rakeWagonsOrdered = useMemo(
        () => [...rake.wagons].sort((a, b) => (a.wagon_sequence ?? 0) - (b.wagon_sequence ?? 0)),
        [rake.wagons]
    );

    const [rows, setRows] = useState<LoadingRow[]>([]);
    const [saving, setSaving] = useState(false);
    const [ensuring, setEnsuring] = useState(false);
    const [savingRowKey, setSavingRowKey] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    /** Row keys currently being saved in background (spreadsheet mode). */
    const [bgSavingKeys, setBgSavingKeys] = useState<Set<string>>(() => new Set());
    const [focusedQtyRowKey, setFocusedQtyRowKey] = useState<string | null>(null);

    const onWagonLoadingsSavedRef = useRef(onWagonLoadingsSaved);
    onWagonLoadingsSavedRef.current = onWagonLoadingsSaved;

    /** Always-current snapshot of rows, safe to read inside timeout callbacks. */
    const rowsRef = useRef(rows);
    rowsRef.current = rows;

    /** Always-current existingLoadings for merging server responses. */
    const existingLoadingsRef = useRef(existingLoadings);
    existingLoadingsRef.current = existingLoadings;

    /** Per-row debounce timers for spreadsheet auto-save. */
    const saveTimersRef = useRef<Map<string, ReturnType<typeof setTimeout>>>(new Map());

    /**
     * Spreadsheet rows with local loader/qty edits not yet applied to `rake.wagonLoadings`.
     * Prevents prop sync (from another row's PATCH finishing) from wiping in-progress edits.
     */
    const spreadsheetDirtyRef = useRef<Set<string>>(new Set());

    useEffect(() => {
        const timers = saveTimersRef.current;
        return () => {
            timers.forEach(clearTimeout);
        };
    }, []);

    useEffect(() => {
        spreadsheetDirtyRef.current.clear();
    }, [rake.id]);

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
                : list.map((l) => {
                          const base: LoadingRow = {
                              id: l.id,
                              key: `load-${l.wagon_id}-${l.id ?? Date.now()}`,
                              wagon_id: String(l.wagon_id),
                              wagon_number: l.wagon?.wagon_number ?? '',
                              loader_id: l.loader_id ? String(l.loader_id) : '',
                              loaded_quantity_mt: l.loaded_quantity_mt ?? '',
                          };
                          if (!isSpreadsheet) {
                              base.wagon_type = l.wagon?.wagon_type ?? '';
                              base.pcc_capacity = l.wagon?.pcc_weight_mt ?? '';
                              base.loading_time = l.loading_time
                                  ? new Date(l.loading_time).toISOString().slice(0, 16)
                                  : new Date().toISOString().slice(0, 16);
                              base.remarks = l.remarks ?? '';
                          }
                          return base;
                      });

        if (!isSpreadsheet) {
            setRows(nextRows);
            return;
        }

        const dirty = spreadsheetDirtyRef.current;
        setRows((prev) => {
            const prevByLoadingId = new Map<number, LoadingRow>();
            for (const r of prev) {
                if (r.id !== undefined) {
                    prevByLoadingId.set(r.id, r);
                }
            }
            return nextRows.map((serverRow) => {
                if (serverRow.id === undefined) {
                    return serverRow;
                }
                const local = prevByLoadingId.get(serverRow.id);
                if (!local || !dirty.has(local.key)) {
                    return serverRow;
                }
                return {
                    ...serverRow,
                    loader_id: local.loader_id,
                    loaded_quantity_mt: local.loaded_quantity_mt,
                    key: local.key,
                };
            });
        });
    }, [rake.id, loadingsSyncKey, isSpreadsheet]);

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

    const patchWagonRow = useCallback(
        async (row: LoadingRow, body: Record<string, unknown>): Promise<boolean> => {
            if (!row.id) {
                return false;
            }
            if (tableReadOnly || disabled) {
                return false;
            }

            setError(null);
            setSaving(true);
            setSavingRowKey(row.key);

            try {
                const response = await fetch(`/rakes/${rake.id}/load/wagon-rows/${row.id}`, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        ...getCsrfHeaders(),
                    },
                    body: JSON.stringify(body),
                });

                const data = (await response.json().catch(() => null)) as
                    | { loading?: WagonLoadingRecord; message?: string }
                    | null;
                if (!response.ok || !data?.loading) {
                    const msg =
                        data?.message ??
                        (response.status === 419
                            ? 'Session expired. Please refresh the page.'
                            : 'Failed to update wagon row.');
                    setError(msg);
                    return false;
                }

                const updated = data.loading;
                const merged = existingLoadings.map((l) => (l.id === updated.id ? updated : l));
                onWagonLoadingsSaved?.(merged);
                return true;
            } catch {
                setError('Failed to update wagon row.');
                return false;
            } finally {
                setSaving(false);
                setSavingRowKey(null);
            }
        },
        [rake.id, tableReadOnly, disabled, existingLoadings, onWagonLoadingsSaved]
    );

    /**
     * Debounced auto-save (1.5 s) for spreadsheet mode.
     * Fires only when both loader_id and loaded_quantity_mt are valid.
     * Non-blocking: never disables fields.
     */
    const scheduleSpreadsheetSave = useCallback(
        (rowKey: string) => {
            const existing = saveTimersRef.current.get(rowKey);
            if (existing !== undefined) {
                clearTimeout(existing);
            }

            const timerId = setTimeout(() => {
                saveTimersRef.current.delete(rowKey);

                const row = rowsRef.current.find((r) => r.key === rowKey);
                if (!row?.id) {
                    return;
                }

                const loaderId =
                    row.loader_id && row.loader_id !== '__none__' ? Number(row.loader_id) : null;
                const qty = parseLoadedQuantityForSave(row.loaded_quantity_mt);

                if (qty === null || qty < 0) {
                    return;
                }

                setBgSavingKeys((prev) => new Set([...prev, rowKey]));

                void fetch(`/rakes/${rake.id}/load/wagon-rows/${row.id}`, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        ...getCsrfHeaders(),
                    },
                    body: JSON.stringify({ loader_id: loaderId, loaded_quantity_mt: qty }),
                    credentials: 'same-origin',
                })
                    .then(async (res) => {
                        const data = (await res.json().catch(() => ({}))) as {
                            loading?: WagonLoadingRecord;
                            message?: string;
                        };
                        if (!res.ok || !data.loading) {
                            setError(
                                data.message ??
                                    (res.status === 419
                                        ? 'Session expired. Please refresh the page.'
                                        : 'Failed to save row.'),
                            );
                            return;
                        }
                        const updated = data.loading;
                        const merged = existingLoadingsRef.current.map((l) =>
                            l.id === updated.id ? updated : l,
                        );
                        const localNow = rowsRef.current.find((r) => r.key === rowKey);
                        if (spreadsheetRowMatchesServerLoading(localNow, updated)) {
                            spreadsheetDirtyRef.current.delete(rowKey);
                        }
                        onWagonLoadingsSavedRef.current?.(merged);
                    })
                    .catch(() => {
                        setError('Failed to save row.');
                    })
                    .finally(() => {
                        setBgSavingKeys((prev) => {
                            const next = new Set(prev);
                            next.delete(rowKey);
                            return next;
                        });
                    });
            }, 1500);

            saveTimersRef.current.set(rowKey, timerId);
        },
        [rake.id],
    );

    const handleSpreadsheetLoaderChange = (row: LoadingRow, value: string) => {
        spreadsheetDirtyRef.current.add(row.key);
        const loaderIdStr = value === '__none__' ? '' : value;
        updateRow(row.key, 'loader_id', loaderIdStr);
        scheduleSpreadsheetSave(row.key);
    };

    const handleSpreadsheetQtyFocus = (row: LoadingRow): void => {
        setFocusedQtyRowKey(row.key);
        updateRow(row.key, 'loaded_quantity_mt', toEditDigits(row.loaded_quantity_mt));
    };

    const handleSpreadsheetQtyBlur = (row: LoadingRow): void => {
        setFocusedQtyRowKey((current) => (current === row.key ? null : current));
        updateRow(row.key, 'loaded_quantity_mt', toDisplayDecimal(row.loaded_quantity_mt));
    };

    const saveRow = async (row: LoadingRow): Promise<void> => {
        if (!row.id) {
            return;
        }
        if (tableReadOnly || disabled) {
            return;
        }

        const loaderId =
            row.loader_id && row.loader_id !== '__none__' ? Number(row.loader_id) : null;
        const quantityString = row.loaded_quantity_mt.trim();
        if (quantityString === '') {
            setError('Loaded quantity is required.');
            return;
        }

        await patchWagonRow(row, {
            loader_id: loaderId,
            loaded_quantity_mt: quantityString,
        });
    };

    const headerToolbar = (
        <div className="flex flex-wrap items-center justify-between gap-2 border border-gray-300 border-b-0 bg-white px-2 py-2 text-[11px]">
            <div className="flex items-center gap-2">
                <Package className="h-4 w-4 shrink-0" aria-hidden />
                <span className="font-medium">Wagon loading</span>
                <span className="text-muted-foreground">
                    Rows: <span className="font-semibold text-foreground">{rows.length}</span>
                </span>
            </div>
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
        </div>
    );

    const emptyState =
        rake.wagons.length === 0 ? (
            <p className="text-sm text-muted-foreground">
                No wagons are registered for this rake. Add wagons before recording loader weighment.
            </p>
        ) : null;

    if (isSpreadsheet) {
        return (
            <div className="space-y-0">
                {emptyState}
                {rake.wagons.length > 0 && showWorkflowPanel && (
                    <div>
                        {editMode && !isCompleted && rows.length === 0 && (ensuring || needsEnsureAll) && (
                            <p className="mb-3 text-sm text-muted-foreground">Loading one row per wagon…</p>
                        )}
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                            }}
                        >
                            {error && (
                                <div className="mb-3 text-sm text-destructive" role="alert">
                                    {error}
                                </div>
                            )}
                            {headerToolbar}
                            <div
                                className={cn(
                                    'min-h-0 bg-white',
                                    compact ? 'max-h-80 overflow-auto' : 'overflow-x-auto',
                                )}
                            >
                                <Table className="w-full border-collapse border border-gray-300 border-t-0 text-xs">
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="h-14 min-h-[4rem] w-12 border-r border-gray-300 px-2 py-3 text-center">
                                                SL NO
                                            </TableHead>
                                            <TableHead className="h-14 min-h-[4rem] min-w-[140px] border-r border-gray-300 px-2 py-3 text-center">
                                                Wagon
                                            </TableHead>
                                            <TableHead className="h-14 min-h-[4rem] min-w-[180px] border-r border-gray-300 px-2 py-3 text-center">
                                                Loader
                                            </TableHead>
                                            <TableHead className="h-14 min-h-[4rem] border-r border-gray-300 px-2 py-3 text-center">
                                                Loaded Qty (MT)
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {rows.length === 0 && ensuring ? (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={4}
                                                    className="py-8 text-center text-sm text-muted-foreground"
                                                >
                                                    Preparing rows…
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            rows.map((row, index) => {
                                                const wagonForRow = rake.wagons.find(
                                                    (w) => String(w.id) === row.wagon_id
                                                );
                                                const isUnfitRow = wagonForRow?.is_unfit === true;
                                                const cellBase =
                                                    'min-h-[4rem] border-t border-r border-gray-300 align-middle';

                                                return (
                                                    <TableRow
                                                        key={row.key}
                                                        className={
                                                            isUnfitRow
                                                                ? 'border-b border-red-900/55 bg-red-950/40 dark:bg-red-950/50'
                                                                : undefined
                                                        }
                                                    >
                                                        <TableCell
                                                            className={cn(
                                                                cellBase,
                                                                'px-2 py-3 text-center font-medium tabular-nums',
                                                            )}
                                                        >
                                                            {index + 1}
                                                        </TableCell>
                                                        <TableCell
                                                            className={cn(
                                                                cellBase,
                                                                'px-2 py-3 text-left',
                                                                spreadsheetCellClass,
                                                            )}
                                                        >
                                                            <div
                                                                tabIndex={0}
                                                                data-field="rake-loader-wagon"
                                                                className="flex flex-col gap-1 rounded-xs outline-none focus-visible:ring-2 focus-visible:ring-green-500 focus-visible:ring-inset"
                                                                onKeyDown={(e) => {
                                                                    if (e.key === 'Enter' || e.key === ' ') {
                                                                        e.preventDefault();
                                                                        const tr = e.currentTarget.closest('tr');
                                                                        tr?.querySelector<HTMLElement>('[data-field="rake-loader-loader"]')?.focus();
                                                                    }
                                                                }}
                                                            >
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
                                                                    Pos {wagonForRow?.wagon_sequence ?? '-'}
                                                                </span>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell
                                                            className={cn(
                                                                cellBase,
                                                                'px-2 py-3',
                                                                spreadsheetCellClass,
                                                            )}
                                                        >
                                                            <SpreadsheetLoaderTypeahead
                                                                loaders={loaders}
                                                                value={row.loader_id}
                                                                disabled={
                                                                    disabled ||
                                                                    tableReadOnly ||
                                                                    ensuring
                                                                }
                                                                onValueChange={(v) =>
                                                                    handleSpreadsheetLoaderChange(row, v)
                                                                }
                                                            />
                                                            {bgSavingKeys.has(row.key) && (
                                                                <span className="mt-1 block text-[10px] text-muted-foreground">
                                                                    Saving…
                                                                </span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell
                                                            className={cn(
                                                                cellBase,
                                                                'px-2 py-3',
                                                                spreadsheetCellClass,
                                                            )}
                                                        >
                                                            <Input
                                                                data-field="rake-loader-qty"
                                                                type="text"
                                                                inputMode="decimal"
                                                                pattern="[0-9]*[.,]?[0-9]*"
                                                                value={
                                                                    focusedQtyRowKey === row.key
                                                                        ? toEditDigits(row.loaded_quantity_mt)
                                                                        : row.loaded_quantity_mt
                                                                }
                                                                onFocus={() =>
                                                                    handleSpreadsheetQtyFocus(row)
                                                                }
                                                                onBlur={() =>
                                                                    handleSpreadsheetQtyBlur(row)
                                                                }
                                                                onChange={(e) => {
                                                                    spreadsheetDirtyRef.current.add(row.key);
                                                                    updateRow(
                                                                        row.key,
                                                                        'loaded_quantity_mt',
                                                                        toEditDigits(e.target.value),
                                                                    );
                                                                    scheduleSpreadsheetSave(row.key);
                                                                }}
                                                                onKeyDown={(e) => {
                                                                    if (e.key === 'Enter') {
                                                                        e.preventDefault();
                                                                        focusNextRowQtyInput(e.currentTarget);
                                                                    }
                                                                }}
                                                                placeholder="0"
                                                                disabled={
                                                                    disabled ||
                                                                    tableReadOnly ||
                                                                    ensuring
                                                                }
                                                                className="h-12 w-24 px-2 text-xs"
                                                            />
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </form>
                    </div>
                )}
            </div>
        );
    }

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
                        No wagons are registered for this rake. Add wagons before recording loader weighment.
                    </p>
                ) : (
                    showWorkflowPanel && (
                        <div>
                            {editMode && (
                                <div className="mb-2 flex items-center justify-between">
                                    <Label className="text-base font-medium">Wagon loadings</Label>
                                </div>
                            )}
                            {editMode && !isCompleted && rows.length === 0 && (ensuring || needsEnsureAll) && (
                                <p className="mb-3 text-sm text-muted-foreground">Loading one row per wagon…</p>
                            )}
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                }}
                            >
                                {error && (
                                    <div className="mb-3 text-sm text-destructive" role="alert">
                                        {error}
                                    </div>
                                )}
                                <div
                                    className={
                                        (compact ? 'max-h-80 overflow-y-auto ' : '') + 'rounded-lg border'
                                    }
                                >
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Wagon</TableHead>
                                                <TableHead>Loader</TableHead>
                                                <TableHead>Loaded Qty (MT)</TableHead>
                                                <TableHead>Wagon Type</TableHead>
                                                <TableHead>PCC Capacity</TableHead>
                                                <TableHead>Loading Time</TableHead>
                                                <TableHead>Remarks</TableHead>
                                                <TableHead className="w-[120px]">Update</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {rows.length === 0 && ensuring ? (
                                                <TableRow>
                                                    <TableCell
                                                        colSpan={8}
                                                        className="py-8 text-center text-sm text-muted-foreground"
                                                    >
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
                                                                    ? 'border-b border-red-900/55 bg-red-950/40 dark:bg-red-950/50'
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
                                                                        Pos {wagonForRow?.wagon_sequence ?? '-'}
                                                                    </span>
                                                                </div>
                                                            </TableCell>
                                                            <TableCell className="min-w-[180px]">
                                                                <Select
                                                                    value={row.loader_id || '__none__'}
                                                                    onValueChange={(value) => {
                                                                        const loaderId =
                                                                            value === '__none__' ? null : value;
                                                                        updateRow(
                                                                            row.key,
                                                                            'loader_id',
                                                                            loaderId ? String(loaderId) : '',
                                                                        );
                                                                    }}
                                                                    disabled={disabled || tableReadOnly}
                                                                >
                                                                    <SelectTrigger className="min-w-[140px] w-full">
                                                                        <SelectValue placeholder="No loader" />
                                                                    </SelectTrigger>
                                                                    <SelectContent>
                                                                        <SelectItem value="__none__">No loader</SelectItem>
                                                                        {loaders.map((loader) => (
                                                                            <SelectItem
                                                                                key={loader.id}
                                                                                value={String(loader.id)}
                                                                            >
                                                                                {loader.loader_name}{' '}
                                                                                {loader.code ? `(${loader.code})` : ''}
                                                                            </SelectItem>
                                                                        ))}
                                                                    </SelectContent>
                                                                </Select>
                                                            </TableCell>
                                                            <TableCell>
                                                                <Input
                                                                    type="text"
                                                                    inputMode="decimal"
                                                                    pattern="[0-9]*[.,]?[0-9]*"
                                                                    value={row.loaded_quantity_mt}
                                                                    onChange={(e) =>
                                                                        updateRow(
                                                                            row.key,
                                                                            'loaded_quantity_mt',
                                                                            e.target.value,
                                                                        )
                                                                    }
                                                                    placeholder="0"
                                                                    disabled={disabled || tableReadOnly}
                                                                    className="w-24"
                                                                />
                                                            </TableCell>
                                                            <TableCell className="min-w-[180px]">
                                                                <Input
                                                                    value={row.wagon_type ?? ''}
                                                                    readOnly
                                                                    placeholder="Auto"
                                                                    className="bg-muted w-24"
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <Input
                                                                    value={row.pcc_capacity ?? ''}
                                                                    readOnly
                                                                    placeholder="Auto"
                                                                    className="bg-muted w-20"
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <Input
                                                                    type="datetime-local"
                                                                    value={row.loading_time ?? ''}
                                                                    onChange={(e) =>
                                                                        updateRow(
                                                                            row.key,
                                                                            'loading_time',
                                                                            e.target.value,
                                                                        )
                                                                    }
                                                                    disabled={disabled || tableReadOnly}
                                                                    className="w-40"
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <Input
                                                                    value={row.remarks ?? ''}
                                                                    onChange={(e) =>
                                                                        updateRow(row.key, 'remarks', e.target.value)
                                                                    }
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
                                                                        (savingRowKey !== null &&
                                                                            savingRowKey !== row.key)
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
                                <div className="mt-4 flex justify-end text-xs text-muted-foreground">
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
