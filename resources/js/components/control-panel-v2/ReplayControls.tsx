import { Loader2, Pause, Play, Rewind, X } from 'lucide-react';

import type { ReplaySpeed, UseReplayReturn } from './useReplayState';

interface Props {
    replay: UseReplayReturn;
    onLoad: () => void;
}

const SPEEDS: ReplaySpeed[] = [1, 5, 10, 30, 60];

export function ReplayControls({ replay, onLoad }: Props) {
    if (!replay.isActive) {
        return (
            <div className="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <div className="flex items-center justify-between">
                    <div>
                        <h3 className="text-sm font-semibold text-slate-900">
                            Replay this rake's loading
                        </h3>
                        <p className="text-xs text-slate-500">
                            Walk through every Add / Subtract / Short Total
                            event chronologically.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onLoad}
                        disabled={replay.isLoading}
                        className="inline-flex items-center gap-2 rounded-md border border-sky-300 bg-sky-50 px-3 py-2 text-sm font-medium text-sky-700 hover:bg-sky-100 disabled:opacity-60"
                    >
                        {replay.isLoading ? (
                            <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
                        ) : (
                            <Rewind className="h-4 w-4" aria-hidden />
                        )}
                        {replay.isLoading ? 'Loading…' : 'Load Replay'}
                    </button>
                </div>
                {replay.loadError && (
                    <div className="mt-2 rounded-md bg-rose-50 p-2 text-xs text-rose-700">
                        {replay.loadError}
                    </div>
                )}
            </div>
        );
    }

    const elapsedMs = replay.virtualTimeMs - replay.startMs;
    const progressPct = (elapsedMs / Math.max(1, replay.durationMs)) * 100;

    return (
        <div className="rounded-xl border border-sky-200 bg-sky-50/40 p-3 shadow-sm">
            <div className="mb-2 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    onClick={replay.toggle}
                    className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-sky-600 text-white hover:bg-sky-700"
                    aria-label={replay.isPlaying ? 'Pause' : 'Play'}
                >
                    {replay.isPlaying ? (
                        <Pause className="h-4 w-4" aria-hidden />
                    ) : (
                        <Play className="h-4 w-4" aria-hidden />
                    )}
                </button>

                <div className="text-xs text-slate-700">
                    <div className="font-semibold tabular-nums">
                        {formatTime(replay.virtualTimeMs)}
                    </div>
                    <div className="text-[10px] uppercase tracking-wide text-slate-500">
                        {formatElapsed(elapsedMs)} /{' '}
                        {formatElapsed(replay.durationMs)} ·{' '}
                        {replay.events.length} events
                    </div>
                </div>

                <div className="ml-auto flex items-center gap-1 rounded-md border border-slate-200 bg-white p-0.5 text-xs">
                    {SPEEDS.map((s) => (
                        <button
                            key={s}
                            type="button"
                            onClick={() => replay.setSpeed(s)}
                            aria-pressed={replay.speed === s}
                            className={`rounded px-2 py-1 font-medium transition ${
                                replay.speed === s
                                    ? 'bg-sky-600 text-white'
                                    : 'text-slate-700 hover:bg-slate-100'
                            }`}
                        >
                            {s}×
                        </button>
                    ))}
                </div>

                <button
                    type="button"
                    onClick={replay.stop}
                    aria-label="Exit replay"
                    className="rounded-md border border-slate-200 bg-white p-2 text-slate-600 hover:bg-slate-50"
                >
                    <X className="h-4 w-4" aria-hidden />
                </button>
            </div>

            <input
                type="range"
                min={replay.startMs}
                max={replay.endMs}
                value={replay.virtualTimeMs}
                onChange={(e) => replay.seek(Number(e.target.value))}
                aria-label="Replay scrubber"
                className="h-2 w-full cursor-pointer appearance-none rounded-full bg-slate-200 accent-sky-600"
                style={{
                    background: `linear-gradient(to right, rgb(14 165 233) ${progressPct}%, rgb(226 232 240) ${progressPct}%)`,
                }}
            />
        </div>
    );
}

function formatTime(ms: number): string {
    if (!ms) return '—';
    const d = new Date(ms);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });
}

function formatElapsed(ms: number): string {
    const total = Math.max(0, Math.floor(ms / 1000));
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    if (h > 0) {
        return `${h}h ${String(m).padStart(2, '0')}m`;
    }
    return `${m}:${String(s).padStart(2, '0')}`;
}
