import type { LucideIcon } from "lucide-react";

export type FilterType = "text" | "number" | "date" | "option" | "boolean";

export interface FilterColumn {
    id: string;
    label: string;
    type: FilterType;
    icon?: LucideIcon;
    options?: { label: string; value: string }[];
    searchThreshold?: number;
    /** When type is `text` and filters are inline, lock the operator (default is contains). */
    textFixedOperator?: 'contains' | 'eq';
}

export interface FilterValue {
    operator: string;
    values: string[];
}

export type ActiveFilters = Record<string, FilterValue>;

export interface OperatorDef {
    value: string;
    label: string;
    multi: boolean;
}

export const OPERATORS: Record<FilterType, OperatorDef[]> = {
    text: [
        { value: "contains", label: "contains", multi: false },
        { value: "eq", label: "equals", multi: false },
    ],
    number: [
        { value: "eq", label: "=", multi: false },
        { value: "neq", label: "≠", multi: false },
        { value: "gt", label: ">", multi: false },
        { value: "gte", label: "≥", multi: false },
        { value: "lt", label: "<", multi: false },
        { value: "lte", label: "≤", multi: false },
        { value: "between", label: "between", multi: true },
    ],
    date: [
        { value: "eq", label: "is", multi: false },
        { value: "before", label: "before", multi: false },
        { value: "after", label: "after", multi: false },
        { value: "between", label: "between", multi: true },
    ],
    option: [
        { value: "in", label: "is", multi: false },
        { value: "not_in", label: "is not", multi: false },
    ],
    boolean: [
        { value: "eq", label: "is", multi: false },
    ],
};

export const DEFAULT_OPERATOR: Record<FilterType, string> = {
    text: "contains",
    number: "eq",
    date: "eq",
    option: "in",
    boolean: "eq",
};
