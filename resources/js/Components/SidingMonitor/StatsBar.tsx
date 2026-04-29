interface Props {
    sidingName: string;
    wagonsLoaded: number;
    wagonCount: number;
    loadriteActive: boolean;
}

export function StatsBar({ sidingName, wagonsLoaded, wagonCount, loadriteActive }: Props) {
    const pct = wagonCount > 0 ? Math.round((wagonsLoaded / wagonCount) * 100) : 0;

    return (
        <div className="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900/60 px-6 py-3">
            <div className="flex items-center gap-3">
                <div className="h-2.5 w-2.5 animate-pulse rounded-full bg-green-500" />
                <h1 className="text-lg font-semibold text-slate-100">{sidingName}</h1>
                <span className="rounded-full bg-slate-800 px-2.5 py-0.5 font-mono text-xs text-slate-400">
                    {wagonsLoaded} / {wagonCount} wagons · {pct}%
                </span>
            </div>
            <div className={`rounded-full px-3 py-1 text-xs font-medium ${loadriteActive ? 'bg-green-900/50 text-green-400 border border-green-800' : 'bg-slate-800 text-slate-500'}`}>
                Loadrite: {loadriteActive ? 'Active' : 'Inactive'}
            </div>
        </div>
    );
}
