/**
 * Siding colors shared with the executive dashboard Rail Dispatch bar chart.
 * Keep in sync with {@link EXECUTIVE_SIDING_BAR_CHART_COLORS} usage in dashboard.tsx.
 */
export const EXECUTIVE_SIDING_BAR_CHART_COLORS = [
    '#4682b4', // steelBlue
    '#2d6a4f', // successGreen
    '#e6b800', // safetyYellow
    '#6b9bc4', // steelBlueLight
    '#40916c', // successGreenLight
    '#64748b', // darkGrey
    '#8B5CF6',
    '#F97316',
    '#EC4899',
    '#14B8A6',
    '#6366F1',
] as const;

export function executiveSidingChartColor(index: number): string {
    return EXECUTIVE_SIDING_BAR_CHART_COLORS[
        index % EXECUTIVE_SIDING_BAR_CHART_COLORS.length
    ];
}

function hexToRgba(hex: string, alpha: number): string {
    const normalized = hex.replace('#', '');
    const r = Number.parseInt(normalized.slice(0, 2), 16);
    const g = Number.parseInt(normalized.slice(2, 4), 16);
    const b = Number.parseInt(normalized.slice(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

/** Light card background / border tint matching executive siding chart colors. */
export function executiveSidingChartSurface(index: number): {
    backgroundColor: string;
    borderColor: string;
} {
    const color = executiveSidingChartColor(index);

    return {
        backgroundColor: hexToRgba(color, 0.14),
        borderColor: hexToRgba(color, 0.35),
    };
}

/** Single-siding load trend lines — high contrast (overload vs underload). */
export const RAKE_PERFORMANCE_LOAD_TREND_METRIC_COLORS = {
    overload: '#c41e3a', // alertRed
    underload: '#4682b4', // steelBlue
} as const;
