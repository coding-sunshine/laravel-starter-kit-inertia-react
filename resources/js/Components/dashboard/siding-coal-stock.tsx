import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface SidingStock {
    siding_id: number;
    closing_balance_mt: number;
    dispatched_mt: number;
    last_receipt_at: string | null;
}

function daysOfStock(closingMt: number, dispatchedMt: number): number | null {
    if (dispatchedMt <= 0) return null;
    return Math.round(closingMt / dispatchedMt);
}

export function SidingCoalStock({ stocks }: { stocks: Record<number, SidingStock> }) {
    const entries = Object.values(stocks);

    return (
        <Card className="shadow-sm">
            <CardHeader className="pb-2">
                <CardTitle
                    className="text-sm font-semibold uppercase tracking-wide"
                    style={{ color: 'oklch(0.22 0.06 150)' }}
                >
                    Siding Coal Stock
                </CardTitle>
            </CardHeader>
            <CardContent>
                {entries.length === 0 ? (
                    <p className="text-sm text-gray-400">No stock data available.</p>
                ) : (
                    <div className="divide-y divide-gray-100">
                        {entries.map((s) => {
                            const days = daysOfStock(s.closing_balance_mt, s.dispatched_mt);
                            const daysColor =
                                days === null
                                    ? 'text-gray-400'
                                    : days < 3
                                      ? 'text-red-600'
                                      : days < 7
                                        ? 'text-amber-600'
                                        : 'text-green-600';
                            return (
                                <div key={s.siding_id} className="flex items-center justify-between py-2">
                                    <div>
                                        <p className="font-mono text-base font-semibold tabular-nums text-gray-900">
                                            {s.closing_balance_mt.toLocaleString('en-IN')} MT
                                        </p>
                                        <p className="text-[11px] text-gray-400">
                                            {s.last_receipt_at ? `Last receipt: ${s.last_receipt_at}` : 'No recent receipt'}
                                        </p>
                                    </div>
                                    {days !== null && (
                                        <div className="text-right">
                                            <p className={`font-mono text-xl font-bold tabular-nums ${daysColor}`}>
                                                {days}d
                                            </p>
                                            <p className="text-[10px] text-gray-400">stock</p>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
