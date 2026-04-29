import type { WagonPccState } from '@/utils/pcc';

interface PenaltyIntelligencePanelProps {
    states: WagonPccState[];
    summary: {
        ok: number;
        near: number;
        over: number;
        empty: number;
        totalPenaltyRs: number;
        totalExcessMt: number;
    };
    onRequestClearance: () => void;
}

const inr = (v: number) =>
    new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(v);

export function PenaltyIntelligencePanel({
    states,
    summary,
    onRequestClearance,
}: PenaltyIntelligencePanelProps) {
    const overWagons = states.filter((s) => s.status === 'over');

    return (
        <div
            className="flex h-full flex-col gap-4 rounded-xl p-4 text-white"
            style={{ backgroundColor: 'oklch(0.22 0.06 150)' }}
        >
            {/* Penalty total */}
            <div>
                <p className="text-[10px] font-semibold uppercase tracking-widest text-white/50">
                    Estimated Penalty
                </p>
                <p className="font-mono text-3xl font-bold tabular-nums">
                    {inr(summary.totalPenaltyRs)}
                </p>
                {summary.totalExcessMt > 0 && (
                    <p className="mt-0.5 font-mono text-xs tabular-nums text-white/60">
                        {summary.totalExcessMt.toFixed(3)} MT excess
                    </p>
                )}
            </div>

            {/* Status grid */}
            <div className="grid grid-cols-2 gap-2">
                {[
                    { label: 'Within PCC', count: summary.ok + summary.near, color: 'bg-green-900/40 text-green-300' },
                    { label: 'Over PCC',   count: summary.over,              color: 'bg-red-900/40 text-red-300' },
                    { label: 'Near Limit', count: summary.near,              color: 'bg-amber-900/40 text-amber-300' },
                    { label: 'Not Loaded', count: summary.empty,             color: 'bg-white/10 text-white/50' },
                ].map((item) => (
                    <div key={item.label} className={`rounded-lg p-2 ${item.color}`}>
                        <p className="font-mono text-xl font-bold tabular-nums">{item.count}</p>
                        <p className="text-[10px] font-medium leading-tight">{item.label}</p>
                    </div>
                ))}
            </div>

            {/* Problem wagon list */}
            {overWagons.length > 0 && (
                <div>
                    <p className="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-white/50">
                        Over-PCC Wagons
                    </p>
                    <div className="flex max-h-48 flex-col gap-1.5 overflow-y-auto pr-1">
                        {overWagons.map((w) => (
                            <div key={w.wagonId} className="rounded-lg bg-red-900/30 p-2">
                                <div className="flex items-center justify-between">
                                    <p className="font-mono text-xs font-semibold">{w.wagonNumber}</p>
                                    <p className="font-mono text-xs font-semibold tabular-nums text-red-300">
                                        {inr(w.penaltyRs)}
                                    </p>
                                </div>
                                <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-white/10">
                                    <div
                                        className="h-full rounded-full bg-red-500"
                                        style={{ width: `${Math.min(w.pct, 100)}%` }}
                                    />
                                </div>
                                <p className="mt-0.5 font-mono text-[10px] tabular-nums text-red-300">
                                    +{w.excessMt.toFixed(3)} MT over limit
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* AI suggestion stub */}
            <div className="rounded-lg bg-white/5 p-3 text-xs text-white/60">
                <p className="mb-1 font-semibold text-white/80">Suggestion</p>
                {summary.over === 0
                    ? 'All wagons within PCC. Ready to dispatch.'
                    : `Redistribute ${summary.totalExcessMt.toFixed(2)} MT excess across ${summary.over} wagon${summary.over > 1 ? 's' : ''} to eliminate penalty exposure.`
                }
            </div>

            {/* Clearance button */}
            <div className="mt-auto">
                <button
                    type="button"
                    onClick={onRequestClearance}
                    className="btn-bgr-gold w-full rounded-lg px-4 py-2.5 text-sm font-semibold transition-opacity hover:opacity-90"
                >
                    Request Manager Clearance
                </button>
            </div>
        </div>
    );
}
