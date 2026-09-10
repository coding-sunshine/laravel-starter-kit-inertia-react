import type { WagonCard } from '@/components/control-room/types';

import type { ReplayEvent } from './useReplayState';

/**
 * Walks Short Total events chronologically up to virtualTimeMs and assigns
 * each one to the next unfilled wagon (by wagon_sequence), mirroring the
 * backend SyncLoadriteEvent::attributeShortTotal algorithm. Returns a copy
 * of baseWagons with overridden loaded_mt / status / overload_mt.
 */
export function deriveReplayWagons(
    baseWagons: WagonCard[],
    events: ReplayEvent[],
    virtualTimeMs: number,
): WagonCard[] {
    const sorted = [...baseWagons].sort(
        (a, b) => a.wagon_sequence - b.wagon_sequence,
    );
    const eligible = sorted.filter((w) => w.status !== 'unfit');
    const overrides = new Map<
        number,
        { loadedMt: number; overloadMt: number; status: WagonCard['status']; statusLabel: string; statusColor: string }
    >();

    let cursor = 0;
    for (const e of events) {
        if (e.event_type !== 'Short Total') continue;
        if (!e.event_time) continue;
        const ts = new Date(e.event_time).getTime();
        if (ts > virtualTimeMs) break;
        if (cursor >= eligible.length) break;

        const wagon = eligible[cursor];
        const weight = Number(e.weight_mt);
        const cc = wagon.cc_mt ?? 0;
        const overload = cc > 0 && weight > cc ? weight - cc : 0;
        const status: WagonCard['status'] = overload > 0 ? 'overload' : 'loaded';

        overrides.set(wagon.wagon_id, {
            loadedMt: weight,
            overloadMt: overload,
            status,
            statusLabel: status === 'overload' ? 'Over Load' : 'Loaded',
            statusColor: status === 'overload' ? '#EF4444' : '#10B981',
        });
        cursor++;
    }

    return sorted.map((w) => {
        const o = overrides.get(w.wagon_id);
        if (!o) {
            // Wagon hasn't been loaded yet at virtualTime — reset to empty
            // unless flagged unfit.
            if (w.status === 'unfit') return w;
            return {
                ...w,
                loaded_mt: 0,
                overload_mt: 0,
                status: 'empty',
                status_label: 'Empty',
                status_color: '#E2E8F0',
            };
        }
        return {
            ...w,
            loaded_mt: o.loadedMt,
            overload_mt: o.overloadMt,
            status: o.status,
            status_label: o.statusLabel,
            status_color: o.statusColor,
        };
    });
}

export function nextUnfilledWagonId(wagons: WagonCard[]): number | null {
    const sorted = [...wagons].sort(
        (a, b) => a.wagon_sequence - b.wagon_sequence,
    );
    const next = sorted.find(
        (w) => (w.loaded_mt ?? 0) === 0 && w.status !== 'unfit',
    );
    return next?.wagon_id ?? null;
}
