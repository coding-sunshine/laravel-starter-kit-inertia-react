import { AnimatePresence, motion } from 'framer-motion';
import { Loader2, X } from 'lucide-react';
import { useEffect, useState } from 'react';

import type { WagonCard } from '@/components/control-room/types';

interface TimelineEvent {
    event_id: string;
    event_type: string;
    weight_mt: string | number;
    event_time: string | null;
    operator: string | null;
    scale_id: string | null;
    product: string | null;
    wagon_sequence: number | null;
}

interface Props {
    wagon: WagonCard | null;
    onClose: () => void;
}

const EVENT_TONE: Record<string, string> = {
    Add: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    Subtract: 'bg-rose-50 text-rose-700 ring-rose-200',
    'Short Total': 'bg-sky-50 text-sky-700 ring-sky-200',
    Total: 'bg-slate-50 text-slate-700 ring-slate-200',
};

export function WagonDrawer({ wagon, onClose }: Props) {
    const [events, setEvents] = useState<TimelineEvent[] | null>(null);
    const [loading, setLoading] = useState<boolean>(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!wagon) {
            setEvents(null);
            return;
        }
        setLoading(true);
        setError(null);
        const controller = new AbortController();
        fetch(`/control-panel-2/wagons/${wagon.wagon_id}/loadrite-events`, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
            credentials: 'same-origin',
        })
            .then(async (r) => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then((data) => setEvents(data.events ?? []))
            .catch((e: Error) => {
                if (e.name !== 'AbortError') setError(e.message);
            })
            .finally(() => setLoading(false));
        return () => controller.abort();
    }, [wagon]);

    return (
        <AnimatePresence>
            {wagon && (
                <>
                    <motion.div
                        key="overlay"
                        className="fixed inset-0 z-40 bg-slate-900/30"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        onClick={onClose}
                    />
                    <motion.aside
                        key="drawer"
                        role="dialog"
                        aria-modal="true"
                        aria-label={`Wagon ${wagon.wagon_sequence} details`}
                        className="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-white shadow-xl"
                        initial={{ x: '100%' }}
                        animate={{ x: 0 }}
                        exit={{ x: '100%' }}
                        transition={{ type: 'spring', stiffness: 320, damping: 32 }}
                    >
                        <header className="flex items-start justify-between border-b border-slate-200 p-4">
                            <div>
                                <div className="text-[10px] font-medium uppercase tracking-wide text-sky-700">
                                    Wagon {wagon.wagon_sequence}
                                </div>
                                <div className="text-lg font-semibold text-slate-900">
                                    {wagon.wagon_number ?? '—'}
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={onClose}
                                aria-label="Close"
                                className="rounded-md p-2 text-slate-500 hover:bg-slate-100"
                            >
                                <X className="h-4 w-4" aria-hidden />
                            </button>
                        </header>

                        <dl className="grid grid-cols-2 gap-3 border-b border-slate-200 p-4 text-sm">
                            <DetailRow label="Type" value={wagon.wagon_type ?? '—'} />
                            <DetailRow
                                label="CC"
                                value={wagon.cc_mt != null ? `${wagon.cc_mt} MT` : '—'}
                            />
                            <DetailRow
                                label="Net Wt"
                                value={
                                    wagon.loaded_mt != null
                                        ? `${wagon.loaded_mt.toFixed(2)} MT`
                                        : '—'
                                }
                            />
                            <DetailRow
                                label="Overload"
                                value={
                                    wagon.overload_mt != null
                                        ? `${wagon.overload_mt.toFixed(2)} MT`
                                        : '—'
                                }
                            />
                            <DetailRow label="Status" value={wagon.status_label} />
                            <DetailRow label="Source" value={wagon.weight_source ?? '—'} />
                        </dl>

                        <section className="flex-1 overflow-y-auto p-4">
                            <h3 className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Loadrite Event Timeline
                            </h3>
                            {loading && (
                                <div className="flex items-center gap-2 text-sm text-slate-500">
                                    <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
                                    Loading events…
                                </div>
                            )}
                            {error && (
                                <div className="rounded-md bg-rose-50 p-3 text-sm text-rose-700">
                                    Failed to load events: {error}
                                </div>
                            )}
                            {!loading && !error && events && events.length === 0 && (
                                <div className="text-sm text-slate-500">
                                    No Loadrite events recorded for this wagon.
                                </div>
                            )}
                            {!loading && !error && events && events.length > 0 && (
                                <ol className="space-y-2">
                                    {events.map((e) => (
                                        <li
                                            key={e.event_id}
                                            className={`rounded-md p-3 ring-1 ring-inset ${EVENT_TONE[e.event_type] ?? EVENT_TONE.Total}`}
                                        >
                                            <div className="flex items-center justify-between text-xs font-semibold uppercase tracking-wide">
                                                <span>{e.event_type}</span>
                                                <span className="tabular-nums">
                                                    {Number(e.weight_mt).toFixed(2)} MT
                                                </span>
                                            </div>
                                            <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[11px] text-slate-700">
                                                {e.event_time && (
                                                    <span className="tabular-nums">
                                                        {formatTimestamp(e.event_time)}
                                                    </span>
                                                )}
                                                {e.operator && <span>· {e.operator}</span>}
                                                {e.scale_id && <span>· {e.scale_id}</span>}
                                                {e.product && <span>· {e.product}</span>}
                                            </div>
                                        </li>
                                    ))}
                                </ol>
                            )}
                        </section>
                    </motion.aside>
                </>
            )}
        </AnimatePresence>
    );
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-[10px] font-medium uppercase tracking-wide text-slate-500">
                {label}
            </dt>
            <dd className="text-sm font-semibold text-slate-900">{value}</dd>
        </div>
    );
}

function formatTimestamp(iso: string): string {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });
}
