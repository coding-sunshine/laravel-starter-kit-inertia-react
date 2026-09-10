import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import type { StaleStatus } from '@/components/control-room/types';

const LIVE_THRESHOLD_MS = 30_000;
const LAGGING_THRESHOLD_MS = 120_000;
const FALLBACK_RELOAD_MS = 60_000;

interface Options {
    /** Inertia props to refresh on the fallback poll. */
    only?: string[];
    /** Disable the fallback poll entirely. */
    disablePoll?: boolean;
}

/**
 * Derives a stale-indicator status from the most recent broadcast event timestamp.
 *
 * Tracks "now" via a 1s tick so the badge updates without external triggers.
 * When status crosses to `stale` and the tab is visible, fires a partial Inertia
 * reload as a fallback in case Reverb is down. The reload re-arms the timer.
 */
export function useStaleIndicator(
    lastEventAt: string | null,
    options: Options = {},
): {
    status: StaleStatus;
    secondsSince: number | null;
} {
    const { only, disablePoll = false } = options;

    const [now, setNow] = useState<number>(() => Date.now());

    useEffect(() => {
        const id = setInterval(() => setNow(Date.now()), 1_000);

        return () => clearInterval(id);
    }, []);

    const lastMs = lastEventAt ? new Date(lastEventAt).getTime() : null;
    const elapsed = lastMs !== null ? now - lastMs : null;

    const status: StaleStatus = (() => {
        if (elapsed === null) {
            return 'lagging';
        }
        if (elapsed < LIVE_THRESHOLD_MS) {
            return 'live';
        }
        if (elapsed < LAGGING_THRESHOLD_MS) {
            return 'lagging';
        }

        return 'stale';
    })();

    useEffect(() => {
        if (disablePoll) {
            return;
        }
        if (elapsed === null || elapsed < FALLBACK_RELOAD_MS) {
            return;
        }
        if (
            typeof document !== 'undefined' &&
            document.visibilityState !== 'visible'
        ) {
            return;
        }

        router.reload(only ? { only } : {});
    }, [
        elapsed === null ? 0 : Math.floor(elapsed / 1000),
        disablePoll,
        only?.join(','),
    ]);

    return {
        status,
        secondsSince: elapsed !== null ? Math.floor(elapsed / 1000) : null,
    };
}
