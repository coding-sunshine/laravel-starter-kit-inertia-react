import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { searchTransportRegistrationsForVehicleWorkorder } from '@/actions/App/Http/Controllers/VehicleWorkorderController';
import { ChevronsUpDown } from 'lucide-react';
import React, {
    startTransition,
    useCallback,
    useEffect,
    useRef,
    useState,
} from 'react';

export type TransportRegistrationSearchRow = {
    id: number;
    label: string;
    defaults: Record<string, string>;
};

/** Keys overwritten when applying / clearing registration defaults (excluding vehicle-only fields). */
export const transportRegistrationVehicleFormKeys = (
    includeSidingId: boolean,
): readonly string[] =>
    includeSidingId
        ? [
              'siding_id',
              'wo_no',
              'wo_no_2',
              'work_order_date',
              'transport_name',
              'pan_no',
              'gst_no',
              'mobile_no_1',
              'mobile_no_2',
              'address',
              'referenced',
              'proprietor_name',
          ]
        : [
              'wo_no',
              'wo_no_2',
              'work_order_date',
              'transport_name',
              'pan_no',
              'gst_no',
              'mobile_no_1',
              'mobile_no_2',
              'address',
              'referenced',
              'proprietor_name',
          ];

type SetDataFn = (key: string, value: string) => void;

function registrationIdsEqual(
    selectedId: number,
    initialId: number,
): boolean {
    return Number(selectedId) === Number(initialId);
}

function useDebouncedValue<T>(value: T, ms: number): T {
    const [debounced, setDebounced] = useState(value);
    useEffect(() => {
        const t = window.setTimeout(() => setDebounced(value), ms);
        return () => window.clearTimeout(t);
    }, [value, ms]);
    return debounced;
}

export function TransporterRegistrationCombobox({
    includeSidingInDefaults,
    initialSelection,
    /** Shown when the work order has transporter/WO text but no linked registration row. */
    fallbackLabelFromForm,
    defaultSidingIdForClear,
    setData,
}: {
    includeSidingInDefaults: boolean;
    initialSelection: { id: number; label: string } | null;
    fallbackLabelFromForm?: string | null;
    /** When clearing, siding resets to this (create: first siding id string). */
    defaultSidingIdForClear: string;
    setData: SetDataFn;
}): React.ReactElement {
    const [open, setOpen] = useState(false);
    const [searchRaw, setSearchRaw] = useState('');
    const debouncedSearch = useDebouncedValue(searchRaw, 220);
    const [rows, setRows] = useState<TransportRegistrationSearchRow[]>([]);
    const [loading, setLoading] = useState(false);
    const [fetchError, setFetchError] = useState<string | null>(null);
    const abortRef = useRef<AbortController | null>(null);

    const initialId =
        initialSelection?.id !== undefined ? Number(initialSelection.id) : null;

    const [selectedId, setSelectedId] = useState<number | null>(
        Number.isFinite(initialId) ? initialId : null,
    );

    useEffect(() => {
        const next =
            initialSelection?.id !== undefined
                ? Number(initialSelection.id)
                : null;
        setSelectedId(Number.isFinite(next) ? next : null);
    }, [initialSelection?.id]);

    const trimmedFallback =
        fallbackLabelFromForm !== undefined &&
        fallbackLabelFromForm !== null &&
        fallbackLabelFromForm.trim() !== ''
            ? fallbackLabelFromForm.trim()
            : null;

    const selectedLabel =
        selectedId !== null
            ? (rows.find((r) => Number(r.id) === Number(selectedId))?.label ??
              (initialSelection &&
              registrationIdsEqual(selectedId, Number(initialSelection.id))
                  ? initialSelection.label
                  : null) ??
              trimmedFallback ??
              '(Linked transporter)')
            : trimmedFallback;

    const keys = transportRegistrationVehicleFormKeys(
        includeSidingInDefaults,
    );

    const applyDefaults = useCallback(
        (defaults: Record<string, string>) => {
            const patch = { ...defaults };
            if (!includeSidingInDefaults) {
                delete patch.siding_id;
            }
            startTransition(() => {
                for (const [k, v] of Object.entries(patch)) {
                    setData(k, v ?? '');
                }
            });
        },
        [includeSidingInDefaults, setData],
    );

    const clearTransporterFields = useCallback(() => {
        startTransition(() => {
            for (const k of keys) {
                if (k === 'siding_id') {
                    setData('siding_id', defaultSidingIdForClear);
                } else {
                    setData(k, '');
                }
            }
        });
        setSelectedId(null);
    }, [defaultSidingIdForClear, keys, setData]);

    useEffect(() => {
        if (!open) {
            return;
        }
        abortRef.current?.abort();
        const ac = new AbortController();
        abortRef.current = ac;
        setLoading(true);
        setFetchError(null);

        const url = searchTransportRegistrationsForVehicleWorkorder.url({
            query: { q: debouncedSearch },
        });

        fetch(url, {
            signal: ac.signal,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(async (res) => {
                if (!res.ok) {
                    throw new Error(`Search failed (${res.status})`);
                }
                return res.json() as Promise<{
                    data: TransportRegistrationSearchRow[];
                }>;
            })
            .then((json) => {
                if (!ac.signal.aborted) {
                    setRows(json.data);
                }
            })
            .catch((e: unknown) => {
                if (
                    e instanceof DOMException &&
                    e.name === 'AbortError'
                ) {
                    return;
                }
                setFetchError(
                    e instanceof Error ? e.message : 'Search failed',
                );
            })
            .finally(() => {
                if (!ac.signal.aborted) {
                    setLoading(false);
                }
            });

        return () => ac.abort();
    }, [open, debouncedSearch]);

    return (
        <div className="space-y-2">
            <Label htmlFor="transporter_registration_picker">
                Transporter (from registration)
            </Label>
            <p className="text-muted-foreground text-sm">
                Search by transporter name or work order number. Selecting a
                row fills transporter-related fields; enter vehicle details
                below.
            </p>
            <div className="flex flex-wrap items-center gap-2">
                <Popover open={open} onOpenChange={setOpen}>
                    <PopoverTrigger asChild>
                        <Button
                            type="button"
                            variant="outline"
                            role="combobox"
                            aria-expanded={open}
                            className="min-w-[240px] max-w-full flex-1 justify-between sm:min-w-[320px]"
                            data-pan="vehicle-workorder-transporter-registration-combobox"
                            id="transporter_registration_picker"
                        >
                            <span className="truncate text-left">
                                {selectedLabel ?? 'Select transporter…'}
                            </span>
                            <ChevronsUpDown className="ml-2 size-4 shrink-0 opacity-50" />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent
                        className="w-[var(--radix-popover-trigger-width)] min-w-[280px] p-0"
                        align="start"
                    >
                        <Command
                            shouldFilter={false}
                            className="rounded-md border-0"
                        >
                            <CommandInput
                                placeholder="Search name or WO no…"
                                value={searchRaw}
                                onValueChange={setSearchRaw}
                                data-pan="vehicle-workorder-transporter-registration-search"
                            />
                            <CommandList>
                                {loading && (
                                    <div className="text-muted-foreground py-6 text-center text-sm">
                                        Loading…
                                    </div>
                                )}
                                {!loading && fetchError && (
                                    <div className="text-destructive py-6 text-center text-sm">
                                        {fetchError}
                                    </div>
                                )}
                                {!loading &&
                                    !fetchError &&
                                    rows.length === 0 && (
                                        <CommandEmpty>
                                            No transporter registrations
                                            found.
                                        </CommandEmpty>
                                    )}
                                {!loading && !fetchError && rows.length > 0 && (
                                    <CommandGroup>
                                        {rows.map((row) => (
                                            <CommandItem
                                                key={row.id}
                                                value={`${row.id}-${row.label}`}
                                                onSelect={() => {
                                                    setSelectedId(row.id);
                                                    applyDefaults(
                                                        row.defaults,
                                                    );
                                                    setOpen(false);
                                                }}
                                            >
                                                {row.label}
                                            </CommandItem>
                                        ))}
                                    </CommandGroup>
                                )}
                            </CommandList>
                        </Command>
                    </PopoverContent>
                </Popover>
                <Button
                    type="button"
                    variant="ghost"
                    onClick={clearTransporterFields}
                    data-pan="vehicle-workorder-transporter-registration-clear"
                >
                    Clear transporter fields
                </Button>
            </div>
        </div>
    );
}
