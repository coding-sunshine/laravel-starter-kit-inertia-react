import type { LucideIcon } from 'lucide-react';

export type KpiAccentColor =
    | 'blue'
    | 'green'
    | 'amber'
    | 'red'
    | 'purple'
    | 'cyan'
    | 'gray';

export interface KpiTileProps {
    icon: LucideIcon;
    label: string;
    value: number | string | null;
    unit?: string;
    formatNumber?: (n: number) => string;
    accentColor?: KpiAccentColor;
    compact?: boolean;
    decimals?: number;
}

const ACCENT_CLASSES: Record<KpiAccentColor, string> = {
    blue: 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400',
    green: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400',
    amber: 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400',
    red: 'bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400',
    purple: 'bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400',
    cyan: 'bg-cyan-50 text-cyan-600 dark:bg-cyan-950/40 dark:text-cyan-400',
    gray: 'bg-gray-100 text-gray-600 dark:bg-gray-800/60 dark:text-gray-300',
};

function defaultFormat(n: number, decimals: number): string {
    return new Intl.NumberFormat(undefined, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(n);
}

export function KpiTile({
    icon: Icon,
    label,
    value,
    unit,
    formatNumber,
    accentColor = 'gray',
    compact = false,
    decimals = 0,
}: KpiTileProps) {
    const accent = ACCENT_CLASSES[accentColor];

    const padding = compact ? 'p-3' : 'p-4';
    const iconBox = compact ? 'size-9' : 'size-11';
    const iconSize = compact ? 18 : 22;
    const labelSize = compact ? 'text-[10px]' : 'text-[11px]';
    const valueSize = compact ? 'text-lg' : 'text-2xl';
    const unitSize = compact ? 'text-[11px]' : 'text-xs';
    const gap = compact ? 'gap-2.5' : 'gap-3';

    // Always render the value directly. Number tweens on a constantly-updating
    // monitoring dashboard read as "unstable" to operators — they want to trust
    // the digit, not chase it. Animation was also racing during heavy ingestion.
    let valueNode: React.ReactNode;
    if (value === null) {
        valueNode = <span className="text-muted-foreground">—</span>;
    } else if (typeof value === 'string') {
        valueNode = <span>{value}</span>;
    } else {
        valueNode = (
            <span>
                {formatNumber
                    ? formatNumber(value)
                    : defaultFormat(value, decimals)}
            </span>
        );
    }

    return (
        <div
            className={`flex items-center ${gap} rounded-xl border border-border bg-card ${padding} shadow-xs transition-colors`}
        >
            <div
                className={`flex ${iconBox} shrink-0 items-center justify-center rounded-lg ${accent}`}
                aria-hidden="true"
            >
                <Icon size={iconSize} strokeWidth={2} />
            </div>
            <div className="flex min-w-0 flex-1 flex-col">
                <span
                    className={`${labelSize} font-medium tracking-wider text-muted-foreground uppercase`}
                >
                    {label}
                </span>
                <div
                    className={`flex items-baseline gap-1.5 ${valueSize} font-semibold text-foreground tabular-nums`}
                >
                    <span className="truncate">{valueNode}</span>
                    {unit && value !== null && (
                        <span
                            className={`${unitSize} font-medium text-muted-foreground`}
                        >
                            {unit}
                        </span>
                    )}
                </div>
            </div>
        </div>
    );
}

export default KpiTile;
