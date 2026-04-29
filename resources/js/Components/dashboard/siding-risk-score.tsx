import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface SidingRiskScoreData {
    siding_id: number;
    siding_name: string;
    siding_code: string;
    score: number;
    trend: string;
    risk_factors: string[];
    calculated_at: string | null;
}

function scoreColor(score: number): string {
    if (score >= 70) return 'text-red-600';
    if (score >= 30) return 'text-amber-600';
    return 'text-green-600';
}

function scoreBg(score: number): string {
    if (score >= 70) return 'border-red-200 bg-red-50';
    if (score >= 30) return 'border-amber-200 bg-amber-50';
    return 'border-green-200 bg-green-50';
}

export function SidingRiskScoreWidget({ scores }: { scores: Record<number, SidingRiskScoreData> }) {
    const entries = Object.values(scores);

    return (
        <Card className="shadow-sm">
            <CardHeader className="pb-2">
                <CardTitle
                    className="text-sm font-semibold uppercase tracking-wide"
                    style={{ color: 'oklch(0.22 0.06 150)' }}
                >
                    Siding Risk Score
                </CardTitle>
            </CardHeader>
            <CardContent>
                {entries.length === 0 ? (
                    <p className="text-sm text-gray-400">No risk data available.</p>
                ) : (
                    <div className="flex flex-col gap-3">
                        {entries.map((s) => (
                            <div key={s.siding_id} className={`rounded-lg border p-3 ${scoreBg(s.score)}`}>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-semibold text-gray-900">{s.siding_name}</p>
                                        <p className="text-[10px] text-gray-500">{s.siding_code}</p>
                                    </div>
                                    <div className="text-right">
                                        <p
                                            className={`font-mono text-2xl font-bold tabular-nums ${scoreColor(s.score)}`}
                                        >
                                            {s.score}
                                        </p>
                                        <p className="text-[10px] text-gray-400">/ 100</p>
                                    </div>
                                </div>
                                {s.risk_factors.length > 0 && (
                                    <ul className="mt-2 flex flex-wrap gap-1">
                                        {s.risk_factors.slice(0, 3).map((f, i) => (
                                            <li
                                                key={i}
                                                className="rounded bg-white/60 px-1.5 py-0.5 text-[10px] text-gray-600"
                                            >
                                                {f}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
