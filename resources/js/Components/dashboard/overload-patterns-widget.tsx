import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type OverloadPattern = {
    wagon_type: string;
    overload_rate_percent: number;
    overloaded_count: number;
    total_count: number;
};

type SidingPattern = {
    siding_name: string;
    patterns: OverloadPattern[];
};

interface Props {
    overloadPatterns: SidingPattern[];
}

function TrendArrow({ rate }: { rate: number }) {
    if (rate >= 30) return <span className="text-red-500">↑</span>;
    if (rate >= 15) return <span className="text-amber-500">→</span>;
    return <span className="text-green-500">↓</span>;
}

export function OverloadPatternsWidget({ overloadPatterns }: Props) {
    if (overloadPatterns.length === 0) return null;

    return (
        <Card>
            <CardHeader className="pb-3">
                <CardTitle className="flex items-center gap-2 text-sm font-semibold text-gray-900">
                    <span>🧠</span>
                    <span>AI Risk Patterns — 30-Day Overload Trends</span>
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                {overloadPatterns.map((siding) => (
                    <div key={siding.siding_name}>
                        <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {siding.siding_name}
                        </p>
                        <div className="space-y-1">
                            {siding.patterns.map((p) => (
                                <div
                                    key={p.wagon_type}
                                    className="flex items-center justify-between rounded-md bg-gray-50 px-2 py-1 text-xs"
                                >
                                    <span className="font-medium text-gray-700">{p.wagon_type}</span>
                                    <div className="flex items-center gap-1">
                                        <TrendArrow rate={p.overload_rate_percent} />
                                        <span
                                            className={
                                                p.overload_rate_percent >= 30
                                                    ? 'font-bold text-red-600'
                                                    : p.overload_rate_percent >= 15
                                                      ? 'font-semibold text-amber-600'
                                                      : 'text-green-600'
                                            }
                                        >
                                            {p.overload_rate_percent.toFixed(1)}%
                                        </span>
                                        <span className="text-gray-400">
                                            ({p.overloaded_count}/{p.total_count})
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
