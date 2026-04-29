import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface SidingStock {
    siding_id: number;
    dispatched_mt: number;
    received_mt: number;
}

export function DispatchSummary({ stocks }: { stocks: Record<number, SidingStock> }) {
    const entries = Object.values(stocks);
    const totalDispatched = entries.reduce((sum, s) => sum + (s.dispatched_mt ?? 0), 0);
    const totalReceived = entries.reduce((sum, s) => sum + (s.received_mt ?? 0), 0);

    return (
        <Card className="shadow-sm">
            <CardHeader className="pb-2">
                <CardTitle className="text-sm font-semibold uppercase tracking-wide text-green-900 dark:text-green-400">
                    Today's Dispatch
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <p className="mb-1 text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">Total Dispatched</p>
                        <p className="font-mono text-2xl font-bold tabular-nums text-gray-900 dark:text-gray-100">
                            {totalDispatched.toLocaleString('en-IN')}
                        </p>
                        <p className="text-[11px] text-gray-400 dark:text-gray-500">MT</p>
                    </div>
                    <div>
                        <p className="mb-1 text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">Total Received</p>
                        <p className="font-mono text-2xl font-bold tabular-nums text-gray-900 dark:text-gray-100">
                            {totalReceived.toLocaleString('en-IN')}
                        </p>
                        <p className="text-[11px] text-gray-400 dark:text-gray-500">MT</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
