import type { ReactNode } from 'react';
import type {
    Formatter,
    NameType,
    Payload,
    ValueType,
} from 'recharts/types/component/DefaultTooltipContent';

/**
 * Adapt a numeric tooltip formatter to the wide value type recharts declares.
 *
 * recharts types a tooltip value as `string | number | (string | number)[]`
 * because a chart may plot ranges or categories. Every chart in this app plots
 * a single number per point, so the callback is handed a coerced number and a
 * coerced series name instead of the raw union.
 */
export function numericTooltipFormatter(
    format: (
        value: number,
        name: string,
        item: Payload<ValueType, NameType>,
    ) => ReactNode | [ReactNode, string],
): Formatter<ValueType, NameType> {
    return (value, name, item) =>
        format(Number(value ?? 0), String(name ?? ''), item);
}
