interface Props {
    sidingName: string;
    wagonsLoaded: number;
    wagonCount: number;
    loadriteActive: boolean;
}

export function StatsBar({ sidingName, wagonsLoaded, wagonCount, loadriteActive }: Props) {
    const pct = wagonCount > 0 ? Math.round((wagonsLoaded / wagonCount) * 100) : 0;
    const barColor = pct >= 100 ? 'bg-red-500' : pct >= 80 ? 'bg-amber-500' : 'bg-green-500';
    const pctColor = pct >= 100 ? 'text-red-400' : pct >= 80 ? 'text-amber-400' : 'text-green-400';

    return (
        <div className="rounded-xl border border-slate-800 bg-slate-900/60 px-6 py-4">
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <div className="h-2.5 w-2.5 animate-pulse rounded-full bg-green-500" />
                    <h1 className="text-xl font-bold text-slate-100">{sidingName}</h1>
                    <span className="rounded-full bg-slate-800 px-2.5 py-0.5 font-mono text-xs text-slate-400">
                        {wagonsLoaded} / {wagonCount} wagons
                    </span>
                    <span className={`font-mono text-lg font-bold ${pctColor}`}>{pct}%</span>
                </div>
                <div
                    className={`rounded-full border px-3 py-1 text-xs font-medium ${
                        loadriteActive
                            ? 'border-green-800 bg-green-900/50 text-green-400'
                            : 'border-slate-700 bg-slate-800 text-slate-500'
                    }`}
                >
                    Loadrite: {loadriteActive ? 'Active' : 'Inactive'}
                </div>
            </div>
            <div className="h-2 overflow-hidden rounded-full bg-slate-800">
                <div
                    className={`h-full rounded-full transition-all duration-500 ${barColor}`}
                    style={{ width: `${Math.min(pct, 100)}%` }}
                />
            </div>
        </div>
    );
}
