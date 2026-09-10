import { useEffect, useMemo, useState } from 'react';

interface TvModeState {
    enabled: boolean;
    cycleSeconds: number;
    fontScale: number;
    activeIndex: number;
}

/**
 * Reads `?display=tv` and `?cycle=Ns` from the URL. When TV mode is on:
 *  - sidebar/header is hidden (caller's responsibility)
 *  - font scale is bumped for distance viewing
 *  - the multi-siding overview auto-cycles its focused siding every N seconds
 *
 * Pass the count of sidings to enable cycling; activeIndex stays at 0 when count is 1 or less.
 */
export function useTvMode(sidingCount: number): TvModeState {
    const params = useMemo(() => {
        if (typeof window === 'undefined') {
            return new URLSearchParams();
        }

        return new URLSearchParams(window.location.search);
    }, []);

    const enabled = params.get('display') === 'tv';
    const cycleSeconds = (() => {
        const raw = params.get('cycle');
        if (raw === null) {
            return 30;
        }
        const match = raw.match(/^(\d+)s?$/);

        return match ? Math.max(5, Number(match[1])) : 30;
    })();
    const fontScale = enabled ? 1.15 : 1;

    const [activeIndex, setActiveIndex] = useState(0);

    useEffect(() => {
        if (!enabled || sidingCount <= 1) {
            setActiveIndex(0);

            return;
        }

        const id = setInterval(() => {
            setActiveIndex((idx) => (idx + 1) % sidingCount);
        }, cycleSeconds * 1_000);

        return () => clearInterval(id);
    }, [enabled, cycleSeconds, sidingCount]);

    return { enabled, cycleSeconds, fontScale, activeIndex };
}
