import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export interface RRHeaderProps {
    rrNumber: string;
    siding: string;
    powerPlant: string;
    rrDate: string;
    totalWeightMt: string;
    status: 'parsed' | 'pending' | 'error';
}

const statusStyles: Record<string, string> = {
    parsed:
        'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950/50 dark:text-green-300',
    pending:
        'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
    error: 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950/50 dark:text-red-300',
};

export function RRHeader({
    rrNumber,
    siding,
    powerPlant,
    rrDate,
    totalWeightMt,
    status,
}: RRHeaderProps) {
    const normalizedStatus = status.toLowerCase();
    const badgeStyle =
        statusStyles[normalizedStatus] ?? 'bg-muted text-muted-foreground';

    return (
        <div className="rounded-xl border bg-card p-6 shadow-sm">
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <div>
                    <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        RR Number
                    </p>
                    <p className="mt-1 font-medium">{rrNumber}</p>
                </div>
                <div>
                    <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        Siding
                    </p>
                    <p className="mt-1 font-medium">{siding}</p>
                </div>
                <div>
                    <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        Power Plant
                    </p>
                    <p className="mt-1 font-medium">{powerPlant}</p>
                </div>
                <div>
                    <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        RR Date
                    </p>
                    <p className="mt-1 font-medium">{rrDate}</p>
                </div>
                <div>
                    <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        Total Weight (MT)
                    </p>
                    <p className="mt-1 font-medium">{totalWeightMt}</p>
                </div>
                <div>
                    <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                        Status
                    </p>
                    <Badge
                        variant="outline"
                        className={cn(
                            'mt-1 capitalize',
                            badgeStyle,
                        )}
                    >
                        {status}
                    </Badge>
                </div>
            </div>
        </div>
    );
}
