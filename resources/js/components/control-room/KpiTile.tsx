import {
    animate,
    motion,
    useMotionValue,
    useReducedMotion,
    useTransform,
} from 'framer-motion';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

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

interface AnimatedNumberProps {
    target: number;
    decimals: number;
    formatNumber?: (n: number) => string;
}

function AnimatedNumber({
    target,
    decimals,
    formatNumber,
}: AnimatedNumberProps) {
    const motionValue = useMotionValue(0);
    const rounded = useTransform(motionValue, (latest) => {
        const factor = Math.pow(10, decimals);
        const r = Math.round(latest * factor) / factor;
        return formatNumber ? formatNumber(r) : defaultFormat(r, decimals);
    });
    const [display, setDisplay] = useState<string>(() =>
        formatNumber ? formatNumber(0) : defaultFormat(0, decimals),
    );

    // Subscribe once on mount; rounded is a new ref each render but the underlying
    // MotionValue chain is stable, so a one-time subscription is correct.
    useEffect(() => {
        const unsubscribe = rounded.on('change', (v) => setDisplay(v));
        return unsubscribe;
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // Animate only when target changes. Without splitting these effects, every
    // parent re-render (e.g. from a broadcast) restarted the tween from the current
    // motion-value position, making the displayed number oscillate.
    useEffect(() => {
        const controls = animate(motionValue, target, {
            duration: 0.6,
            ease: 'easeOut',
        });
        return () => controls.stop();
    }, [target, motionValue]);

    return <span>{display}</span>;
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
    const reduceMotion = useReducedMotion();

    const accent = ACCENT_CLASSES[accentColor];

    const padding = compact ? 'p-3' : 'p-4';
    const iconBox = compact ? 'size-9' : 'size-11';
    const iconSize = compact ? 18 : 22;
    const labelSize = compact ? 'text-[10px]' : 'text-[11px]';
    const valueSize = compact ? 'text-lg' : 'text-2xl';
    const unitSize = compact ? 'text-[11px]' : 'text-xs';
    const gap = compact ? 'gap-2.5' : 'gap-3';

    let valueNode: React.ReactNode;
    if (value === null) {
        valueNode = <span className="text-muted-foreground">—</span>;
    } else if (typeof value === 'string') {
        valueNode = <span>{value}</span>;
    } else {
        if (reduceMotion) {
            valueNode = (
                <span>
                    {formatNumber
                        ? formatNumber(value)
                        : defaultFormat(value, decimals)}
                </span>
            );
        } else {
            valueNode = (
                <AnimatedNumber
                    target={value}
                    decimals={decimals}
                    formatNumber={formatNumber}
                />
            );
        }
    }

    return (
        <motion.div
            initial={reduceMotion ? false : { opacity: 0, y: 4 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25, ease: 'easeOut' }}
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
        </motion.div>
    );
}

export default KpiTile;
