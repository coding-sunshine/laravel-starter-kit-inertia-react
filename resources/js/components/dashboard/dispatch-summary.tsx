import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { Truck } from 'lucide-react';

interface SidingStock {
    siding_id: number;
    dispatched_mt: number;
    received_mt: number;
}

const mt = new Intl.NumberFormat('en-IN', { maximumFractionDigits: 0 });

export function DispatchSummary({
    stocks,
    className,
}: {
    stocks: Record<number, SidingStock>;
    className?: string;
}) {
    const entries = Object.values(stocks);
    const totalDispatched = entries.reduce((sum, s) => sum + (s.dispatched_mt ?? 0), 0);
    const totalReceived = entries.reduce((sum, s) => sum + (s.received_mt ?? 0), 0);
    const variance = totalDispatched - totalReceived;
    const reconciliationPct =
        totalDispatched > 0 ? Math.min(100, (totalReceived / totalDispatched) * 100) : 0;

    return (
        <Card className={cn('shadow-sm', className)}>
            <CardHeader className="pb-3">
                <CardTitle className="flex items-center gap-2 text-sm font-semibold text-foreground">
                    <Truck className="h-4 w-4 text-emerald-700 dark:text-emerald-400" aria-hidden="true" />
                    Today's dispatch
                </CardTitle>
            </CardHeader>
            <CardContent className="flex flex-1 flex-col space-y-4">
                {/* Two compact stats */}
                <div className="grid grid-cols-2 gap-3">
                    <Stat label="Dispatched" value={totalDispatched} />
                    <Stat label="Received" value={totalReceived} />
                </div>

                {/* Reconciliation bar */}
                <div>
                    <div className="mb-1 flex items-center justify-between text-[11px]">
                        <span className="text-muted-foreground">In transit</span>
                        <span
                            className={`font-mono font-semibold tabular-nums ${variance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'}`}
                        >
                            {mt.format(variance)} MT
                        </span>
                    </div>
                    <div
                        className="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        aria-valuenow={Math.round(reconciliationPct)}
                        aria-valuemin={0}
                        aria-valuemax={100}
                    >
                        <div
                            className="h-full rounded-full bg-emerald-500 transition-[width] duration-300"
                            style={{ width: `${reconciliationPct}%` }}
                        />
                    </div>
                    <p className="mt-1 text-[11px] text-muted-foreground">
                        {reconciliationPct.toFixed(1)}% reconciled at power plants
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}

function Stat({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-md border bg-card p-3">
            <p className="mb-1 text-xs font-medium text-muted-foreground">{label}</p>
            <p className="font-mono text-xl font-bold tabular-nums text-foreground">
                {mt.format(value)}
                <span className="ml-1 text-[11px] font-normal text-muted-foreground">MT</span>
            </p>
        </div>
    );
}
