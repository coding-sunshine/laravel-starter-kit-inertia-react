import { Calendar } from "@/components/ui/calendar";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { isEqual } from "date-fns";
import { Search } from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";
import type { DateRange } from "react-day-picker";
import { DEFAULT_OPERATOR, OPERATORS, type FilterColumn, type FilterValue } from "./types";

const INLINE_CONTAINS_DEBOUNCE_MS = 2000;

export interface FilterControlProps {
    column: FilterColumn;
    value?: FilterValue;
    onSubmit: (operator: string, values: string[]) => void;
    hideOperator?: boolean;
    /** Compact row layout for always-visible filter bars. */
    variant?: 'default' | 'inline';
    /** When set, the operator dropdown is omitted and this operator is always used (e.g. `between`, `contains`). */
    fixedOperator?: string;
}

function OperatorSelect({
    type,
    value,
    onChange,
}: {
    type: FilterColumn["type"];
    value: string;
    onChange: (op: string) => void;
}) {
    const ops = OPERATORS[type];
    if (ops.length <= 1) return null;

    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger className="h-8 text-xs">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {ops.map((op) => (
                    <SelectItem key={op.value} value={op.value}>
                        {op.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

const OPTION_FILTER_ALL = '__all__';

function OptionFilterInlineSelect({
    column,
    value,
    onSubmit,
}: Pick<FilterControlProps, 'column' | 'value' | 'onSubmit'>) {
    const options = column.options ?? [];
    const v0 = value?.values?.[0];
    const op = value?.operator ?? '';
    const current =
        v0 && (op === 'in' || op === 'eq') && options.some((o) => o.value === v0)
            ? v0
            : OPTION_FILTER_ALL;

    return (
        <Select
            value={current}
            onValueChange={(v) => {
                if (v === OPTION_FILTER_ALL) {
                    onSubmit('in', []);
                } else {
                    onSubmit('in', [v]);
                }
            }}
        >
            <SelectTrigger className="h-8 w-full text-sm">
                <SelectValue placeholder="All sidings" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value={OPTION_FILTER_ALL}>All sidings</SelectItem>
                {options.map((opt) => (
                    <SelectItem key={opt.value} value={opt.value}>
                        {opt.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

export function OptionFilter({ column, value, onSubmit, hideOperator, variant = 'default' }: FilterControlProps) {
    const [search, setSearch] = useState('');
    const [operator, setOperator] = useState(value?.operator || DEFAULT_OPERATOR.option);
    const selected = new Set(value?.values ?? []);

    useEffect(() => {
        setOperator(value?.operator || DEFAULT_OPERATOR.option);
    }, [value]);

    const filteredOptions = useMemo(() => {
        if (!column.options) {
            return [];
        }
        if (!search) {
            return column.options;
        }
        const s = search.toLowerCase();
        return column.options.filter((o) => o.label.toLowerCase().includes(s));
    }, [column.options, search]);

    function toggle(optionValue: string) {
        const next = new Set(selected);
        if (next.has(optionValue)) {
            next.delete(optionValue);
        } else {
            next.add(optionValue);
        }
        onSubmit(operator, Array.from(next));
    }

    function handleOperatorChange(op: string) {
        setOperator(op);
        if (selected.size > 0) {
            onSubmit(op, Array.from(selected));
        }
    }

    const threshold = column.searchThreshold ?? 5;
    const showSearch = (column.options?.length ?? 0) >= threshold;
    const isInline = variant === 'inline';

    if (isInline && column.options && column.options.length > 0) {
        return (
            <div className="flex min-w-0 flex-col gap-2">
                <OptionFilterInlineSelect column={column} value={value} onSubmit={onSubmit} />
            </div>
        );
    }

    return (
        <div
            className={
                isInline
                    ? 'flex min-w-0 flex-col gap-2'
                    : 'flex w-[260px] flex-col gap-2 p-2'
            }
        >
            {!hideOperator && <OperatorSelect type="option" value={operator} onChange={handleOperatorChange} />}
            {showSearch && (
                <div className="relative">
                    <Search className="absolute left-2 top-2 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Search..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="h-8 pl-8 text-sm"
                    />
                </div>
            )}
            <div className="flex max-h-[200px] flex-col gap-0.5 overflow-y-auto">
                {filteredOptions.map((opt) => (
                    <label
                        key={opt.value}
                        className="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-accent"
                    >
                        <Checkbox
                            checked={selected.has(opt.value)}
                            onCheckedChange={() => toggle(opt.value)}
                        />
                        {opt.label}
                    </label>
                ))}
                {filteredOptions.length === 0 && (
                    <p className="px-2 py-1 text-sm text-muted-foreground">No results.</p>
                )}
            </div>
        </div>
    );
}

export function NumberFilter({ value, onSubmit, hideOperator, variant = 'default' }: FilterControlProps) {
    const [operator, setOperator] = useState(value?.operator || DEFAULT_OPERATOR.number);
    const [val1, setVal1] = useState(value?.values[0] ?? "");
    const [val2, setVal2] = useState(value?.values[1] ?? "");

    useEffect(() => {
        setOperator(value?.operator || DEFAULT_OPERATOR.number);
        setVal1(value?.values[0] ?? '');
        setVal2(value?.values[1] ?? '');
    }, [value]);

    const isRange = OPERATORS.number.find((o) => o.value === operator)?.multi ?? false;

    function submit() {
        const values = isRange && val2 ? [val1, val2] : val1 ? [val1] : [];
        onSubmit(operator, values);
    }

    function handleOperatorChange(op: string) {
        setOperator(op);
        if (val1) {
            const multi = OPERATORS.number.find((o) => o.value === op)?.multi ?? false;
            const values = multi && val2 ? [val1, val2] : [val1];
            onSubmit(op, values);
        }
    }

    function handleKeyDown(e: React.KeyboardEvent) {
        if (e.key === "Enter") submit();
    }

    const isInline = variant === 'inline';

    return (
        <div className={isInline ? 'flex min-w-0 flex-col gap-2' : 'flex w-[260px] flex-col gap-2 p-2'}>
            {!hideOperator && <OperatorSelect type="number" value={operator} onChange={handleOperatorChange} />}
            <div className={isRange ? 'grid grid-cols-2 gap-2' : ''}>
                <Input
                    type="number"
                    placeholder={isRange ? 'Min' : 'Value'}
                    value={val1}
                    onChange={(e) => setVal1(e.target.value)}
                    onKeyDown={handleKeyDown}
                    autoFocus={!isInline}
                    className="h-8 text-sm"
                />
                {isRange && (
                    <Input
                        type="number"
                        placeholder="Max"
                        value={val2}
                        onChange={(e) => setVal2(e.target.value)}
                        onKeyDown={handleKeyDown}
                        className="h-8 text-sm"
                    />
                )}
            </div>
            {!isInline && <p className="text-xs text-muted-foreground">Press Enter to filter</p>}
        </div>
    );
}

function DateFilterInline({
    value,
    onSubmit,
    hideOperator,
    fixedOperator,
}: Pick<FilterControlProps, 'value' | 'onSubmit' | 'hideOperator' | 'fixedOperator'>) {
    const betweenOnly = fixedOperator === 'between';
    const [operator, setOperator] = useState(
        betweenOnly ? 'between' : value?.operator || DEFAULT_OPERATOR.date,
    );
    const [d1, setD1] = useState(value?.values[0] ?? '');
    const [d2, setD2] = useState(value?.values[1] ?? '');

    const isRange = OPERATORS.date.find((o) => o.value === operator)?.multi ?? false;

    useEffect(() => {
        if (betweenOnly) {
            const op = value?.operator;
            const v = value?.values ?? [];
            if (op === 'between' && v.length >= 2) {
                setD1(v[0]);
                setD2(v[1]);
            } else if (op === 'eq' && v[0]) {
                setD1(v[0]);
                setD2(v[0]);
            } else {
                setD1('');
                setD2('');
            }
            setOperator('between');
            return;
        }
        setOperator(value?.operator || DEFAULT_OPERATOR.date);
        setD1(value?.values[0] ?? '');
        setD2(value?.values[1] ?? '');
    }, [value, betweenOnly]);

    function submitWith(op: string, v1: string, v2: string) {
        if (betweenOnly) {
            if (v1 && v2) {
                onSubmit('between', [v1, v2]);
            } else if (!v1 && !v2) {
                onSubmit('between', []);
            }
            return;
        }
        const range = OPERATORS.date.find((o) => o.value === op)?.multi ?? false;
        if (range) {
            if (v1 && v2) {
                onSubmit(op, [v1, v2]);
            } else if (!v1 && !v2) {
                onSubmit(op, []);
            }
            return;
        }
        if (v1) {
            onSubmit(op, [v1]);
        } else {
            onSubmit(op, []);
        }
    }

    function handleOperatorChange(op: string) {
        setOperator(op);
        const range = OPERATORS.date.find((o) => o.value === op)?.multi ?? false;
        if (range) {
            if (d1 && d2) {
                onSubmit(op, [d1, d2]);
            }
        } else if (d1) {
            onSubmit(op, [d1]);
        }
    }

    const showRange = betweenOnly || isRange;
    const hideOp = hideOperator || betweenOnly;

    return (
        <div className="flex min-w-0 flex-col gap-2">
            {!hideOp && <OperatorSelect type="date" value={operator} onChange={handleOperatorChange} />}
            <div
                className={
                    showRange ? 'grid grid-cols-1 gap-2 sm:grid-cols-2' : ''
                }
            >
                <Input
                    type="date"
                    value={d1}
                    onChange={(e) => {
                        const next = e.target.value;
                        setD1(next);
                        submitWith(betweenOnly ? 'between' : operator, next, d2);
                    }}
                    className="h-8 text-sm"
                />
                {showRange && (
                    <Input
                        type="date"
                        value={d2}
                        onChange={(e) => {
                            const next = e.target.value;
                            setD2(next);
                            submitWith(betweenOnly ? 'between' : operator, d1, next);
                        }}
                        className="h-8 text-sm"
                    />
                )}
            </div>
        </div>
    );
}

export function DateFilter({
    value,
    onSubmit,
    hideOperator,
    variant = 'default',
    fixedOperator,
}: FilterControlProps) {
    const [operator, setOperator] = useState(value?.operator || DEFAULT_OPERATOR.date);
    const isRange = OPERATORS.date.find((o) => o.value === operator)?.multi ?? false;

    const [date, setDate] = useState<DateRange | undefined>(() => {
        if (!value?.values.length) {
            return undefined;
        }
        return {
            from: new Date(value.values[0]),
            to: value.values[1] ? new Date(value.values[1]) : undefined,
        };
    });

    useEffect(() => {
        setOperator(value?.operator || DEFAULT_OPERATOR.date);
        if (!value?.values.length) {
            setDate(undefined);
        } else {
            setDate({
                from: new Date(value.values[0]),
                to: value.values[1] ? new Date(value.values[1]) : undefined,
            });
        }
    }, [value]);

    function fmt(d: Date): string {
        return d.toISOString().slice(0, 10);
    }

    function handleDateChange(range: DateRange | undefined) {
        setDate(range);
        if (!range?.from) {
            return;
        }

        if (isRange) {
            if (range.from && range.to && !isEqual(range.from, range.to)) {
                onSubmit(operator, [fmt(range.from), fmt(range.to)]);
            }
        } else {
            onSubmit(operator, [fmt(range.from)]);
        }
    }

    function handleOperatorChange(op: string) {
        setOperator(op);
        if (date?.from) {
            const multi = OPERATORS.date.find((o) => o.value === op)?.multi ?? false;
            if (multi && date.to) {
                onSubmit(op, [fmt(date.from), fmt(date.to)]);
            } else if (!multi) {
                onSubmit(op, [fmt(date.from)]);
            }
        }
    }

    if (variant === 'inline') {
        return (
            <DateFilterInline
                value={value}
                onSubmit={onSubmit}
                hideOperator={hideOperator}
                fixedOperator={fixedOperator}
            />
        );
    }

    return (
        <div className="flex flex-col gap-2 p-2">
            {!hideOperator && <OperatorSelect type="date" value={operator} onChange={handleOperatorChange} />}
            <Calendar
                mode="range"
                selected={date}
                onSelect={handleDateChange}
                numberOfMonths={1}
                initialFocus
            />
        </div>
    );
}

export function TextFilter({
    value,
    onSubmit,
    hideOperator,
    variant = 'default',
    fixedOperator,
}: FilterControlProps) {
    const fixedTextOp =
        fixedOperator === 'contains' || fixedOperator === 'eq' ? fixedOperator : null;
    const containsOnly = fixedTextOp === 'contains';
    const equalsOnly = fixedTextOp === 'eq';
    const [operator, setOperator] = useState(
        fixedTextOp ? fixedTextOp : value?.operator || DEFAULT_OPERATOR.text,
    );
    const [text, setText] = useState(value?.values[0] ?? '');

    const onSubmitRef = useRef(onSubmit);
    onSubmitRef.current = onSubmit;

    const debounceTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const skipInitialContainsDebounceRef = useRef(true);

    useEffect(() => {
        if (fixedTextOp) {
            setOperator(fixedTextOp);
        } else {
            setOperator(value?.operator || DEFAULT_OPERATOR.text);
        }
        setText(value?.values[0] ?? '');
    }, [value, fixedTextOp]);

    const isInline = variant === 'inline';

    useEffect(() => {
        if (!containsOnly || !isInline) {
            return;
        }

        if (skipInitialContainsDebounceRef.current) {
            skipInitialContainsDebounceRef.current = false;
            return;
        }

        if (debounceTimerRef.current !== null) {
            clearTimeout(debounceTimerRef.current);
        }

        debounceTimerRef.current = setTimeout(() => {
            debounceTimerRef.current = null;
            const submitFn = onSubmitRef.current;
            if (text) {
                submitFn('contains', [text]);
            } else {
                submitFn('contains', []);
            }
        }, INLINE_CONTAINS_DEBOUNCE_MS);

        return () => {
            if (debounceTimerRef.current !== null) {
                clearTimeout(debounceTimerRef.current);
                debounceTimerRef.current = null;
            }
        };
    }, [text, containsOnly, isInline]);

    function submit() {
        const op = fixedTextOp ?? operator;
        if (text) {
            onSubmit(op, [text]);
        } else {
            onSubmit(op, []);
        }
    }

    function handleOperatorChange(op: string) {
        setOperator(op);
        if (text) {
            onSubmit(op, [text]);
        }
    }

    function handleKeyDown(e: React.KeyboardEvent) {
        if (e.key === "Enter") {
            if (containsOnly && isInline && debounceTimerRef.current !== null) {
                clearTimeout(debounceTimerRef.current);
                debounceTimerRef.current = null;
            }
            submit();
        }
    }

    const placeholder = equalsOnly ? 'Exact value…' : 'Search…';

    if (isInline) {
        return (
            <div className="flex min-w-0 flex-col gap-1">
                <div className="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-end">
                    {!hideOperator && !fixedTextOp && (
                        <OperatorSelect type="text" value={operator} onChange={handleOperatorChange} />
                    )}
                    <Input
                        placeholder={placeholder}
                        value={text}
                        onChange={(e) => setText(e.target.value)}
                        onKeyDown={handleKeyDown}
                        onBlur={() => {
                            if (equalsOnly) {
                                submit();
                            }
                        }}
                        autoFocus={false}
                        className="h-8 min-w-0 flex-1 text-sm"
                    />
                </div>
                {containsOnly && (
                    <p className="text-xs text-muted-foreground">
                        Applies {INLINE_CONTAINS_DEBOUNCE_MS / 1000}s after you stop typing (Enter
                        applies immediately)
                    </p>
                )}
                {equalsOnly && (
                    <p className="text-xs text-muted-foreground">
                        Type the full rake number (exact match). Press Enter or leave the field to
                        apply.
                    </p>
                )}
            </div>
        );
    }

    return (
        <div className="flex w-[260px] flex-col gap-2 p-2">
            {!hideOperator && !fixedTextOp && (
                <OperatorSelect type="text" value={operator} onChange={handleOperatorChange} />
            )}
            <Input
                placeholder={placeholder}
                value={text}
                onChange={(e) => setText(e.target.value)}
                onKeyDown={handleKeyDown}
                autoFocus
                className="h-8 text-sm"
            />
            <p className="text-xs text-muted-foreground">Press Enter to filter</p>
        </div>
    );
}

const BOOL_OPTIONS = [
    { label: "Yes", value: "1" },
    { label: "No", value: "0" },
];

export function FilterControl({
    column,
    value,
    onSubmit,
    hideOperator,
    variant = 'default',
    fixedOperator,
}: FilterControlProps) {
    switch (column.type) {
        case 'boolean':
            return (
                <OptionFilter
                    column={{ ...column, type: 'option', options: BOOL_OPTIONS, searchThreshold: 999 }}
                    value={value}
                    onSubmit={onSubmit}
                    hideOperator
                    variant={variant}
                />
            );
        case 'option':
            return (
                <OptionFilter
                    column={column}
                    value={value}
                    onSubmit={onSubmit}
                    hideOperator={hideOperator}
                    variant={variant}
                />
            );
        case 'number':
            return (
                <NumberFilter
                    column={column}
                    value={value}
                    onSubmit={onSubmit}
                    hideOperator={hideOperator}
                    variant={variant}
                />
            );
        case 'date':
            return (
                <DateFilter
                    column={column}
                    value={value}
                    onSubmit={onSubmit}
                    hideOperator={hideOperator}
                    variant={variant}
                    fixedOperator={fixedOperator}
                />
            );
        case 'text':
            return (
                <TextFilter
                    column={column}
                    value={value}
                    onSubmit={onSubmit}
                    hideOperator={hideOperator}
                    variant={variant}
                    fixedOperator={fixedOperator}
                />
            );
        default:
            return null;
    }
}
