interface SourceBadgeProps {
    source?: string | null;
    override?: boolean;
    hasWeight: boolean;
    compact?: boolean;
}

// Small chip indicating where a wagon's weight came from. Operators glance at
// this column when reconciling figures with the weighbridge log or operator
// shift report. Override flag is shown as a tiny dot — manual entry that
// explicitly beats incoming Loadrite values.
const SOURCE_STYLES: Record<
    string,
    { label: string; bg: string; text: string; ring: string }
> = {
    loadrite: {
        label: 'Loadrite',
        bg: 'bg-sky-100 dark:bg-sky-950/40',
        text: 'text-sky-700 dark:text-sky-300',
        ring: 'ring-sky-300/40 dark:ring-sky-800/60',
    },
    manual: {
        label: 'Manual',
        bg: 'bg-amber-100 dark:bg-amber-950/40',
        text: 'text-amber-700 dark:text-amber-300',
        ring: 'ring-amber-300/40 dark:ring-amber-800/60',
    },
    weighbridge: {
        label: 'Weighbridge',
        bg: 'bg-emerald-100 dark:bg-emerald-950/40',
        text: 'text-emerald-700 dark:text-emerald-300',
        ring: 'ring-emerald-300/40 dark:ring-emerald-800/60',
    },
};

export function SourceBadge({
    source,
    override,
    hasWeight,
    compact = false,
}: SourceBadgeProps) {
    if (!hasWeight) {
        return (
            <span className="text-xs text-muted-foreground">—</span>
        );
    }

    const key = source ?? 'manual';
    const style = SOURCE_STYLES[key] ?? SOURCE_STYLES.manual;
    const size = compact ? 'text-[10px] px-1.5 py-0.5' : 'text-xs px-2 py-0.5';

    return (
        <span
            className={`inline-flex items-center gap-1 rounded-full font-medium ring-1 ring-inset ${size} ${style.bg} ${style.text} ${style.ring}`}
            title={
                override
                    ? `${style.label} (operator override)`
                    : style.label
            }
        >
            {style.label}
            {override && (
                <span
                    className="size-1.5 rounded-full bg-current opacity-70"
                    aria-label="operator override"
                />
            )}
        </span>
    );
}

export default SourceBadge;
