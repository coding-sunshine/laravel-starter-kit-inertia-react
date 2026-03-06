import { cn } from '@/lib/utils';

const statusStyles: Record<string, string> = {
    pending:
        'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
    unloading:
        'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300',
    completed:
        'bg-green-100 text-green-800 dark:bg-green-950/50 dark:text-green-300',
    cancelled: 'bg-muted text-muted-foreground',
    active: 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
    resolved:
        'bg-green-100 text-green-800 dark:bg-green-950/50 dark:text-green-300',
    confirmed:
        'bg-green-100 text-green-800 dark:bg-green-950/50 dark:text-green-300',
    disputed:
        'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
    waived: 'bg-muted text-muted-foreground',
};

export function StatusPill({
    status,
    className,
}: {
    status: string | null | undefined;
    className?: string;
}) {
    if (status == null || status === '') {
        return (
            <span
                className={cn(
                    'inline-flex rounded-full px-3 py-1.5 text-xs font-medium text-muted-foreground',
                    'bg-muted',
                    className,
                )}
            >
                —
            </span>
        );
    }
    const normalized = status.toLowerCase();
    const style = statusStyles[normalized] ?? 'bg-muted text-muted-foreground';

    return (
        <span
            className={cn(
                'inline-flex rounded-full px-3 py-1.5 text-xs font-medium capitalize',
                style,
                className,
            )}
        >
            {status}
        </span>
    );
}
