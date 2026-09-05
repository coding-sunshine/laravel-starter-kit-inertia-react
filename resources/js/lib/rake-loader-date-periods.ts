/**
 * Build `filter[loading_date]` value for OperatorFilter `between:start,end` (matches backend / India FY).
 */
export type RakeLoaderDatePeriodId =
    | 'today'
    | 'yesterday'
    | 'this_week'
    | 'this_month'
    | 'financial_year';

function iso(y: number, m: number, d: number): string {
    return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
}

export function rakeLoaderLoadingDateFilterValue(period: RakeLoaderDatePeriodId): string {
    const now = new Date();
    const y = now.getFullYear();
    const m = now.getMonth() + 1;
    const d = now.getDate();
    const today = iso(y, m, d);

    switch (period) {
        case 'today':
            return `between:${today},${today}`;
        case 'yesterday': {
            const t = new Date(now);
            t.setDate(t.getDate() - 1);
            const ystr = iso(t.getFullYear(), t.getMonth() + 1, t.getDate());
            return `between:${ystr},${ystr}`;
        }
        case 'this_week': {
            const day = now.getDay();
            const diff = now.getDate() - day + (day === 0 ? -6 : 1);
            const start = new Date(now);
            start.setDate(diff);
            const startStr = iso(start.getFullYear(), start.getMonth() + 1, start.getDate());
            return `between:${startStr},${today}`;
        }
        case 'this_month':
            return `between:${iso(y, m, 1)},${today}`;
        case 'financial_year':
            if (m >= 4) {
                return `between:${iso(y, 4, 1)},${iso(y + 1, 3, 31)}`;
            }
            return `between:${iso(y - 1, 4, 1)},${iso(y, 3, 31)}`;
        default:
            return `between:${today},${today}`;
    }
}

export const RAKE_LOADER_DATE_PERIODS: {
    id: RakeLoaderDatePeriodId;
    label: string;
}[] = [
    { id: 'today', label: 'Today' },
    { id: 'yesterday', label: 'Yesterday' },
    { id: 'this_week', label: 'This week' },
    { id: 'this_month', label: 'This month' },
    { id: 'financial_year', label: 'Financial year' },
];
