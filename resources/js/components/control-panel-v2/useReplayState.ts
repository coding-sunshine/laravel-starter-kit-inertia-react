import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

export interface ReplayEvent {
    event_id: string;
    event_type: string;
    weight_mt: string | number;
    event_time: string | null;
    wagon_id: number | null;
    wagon_sequence: number | null;
    operator: string | null;
    scale_id: string | null;
}

interface ReplayPayload {
    rake_id: number;
    wagon_count: number;
    first_event_at: string | null;
    last_event_at: string | null;
    events: ReplayEvent[];
}

export type ReplaySpeed = 1 | 5 | 10 | 30 | 60;

interface ReplayState {
    isActive: boolean;
    isLoading: boolean;
    loadError: string | null;
    isPlaying: boolean;
    speed: ReplaySpeed;
    virtualTimeMs: number;
    startMs: number;
    endMs: number;
    durationMs: number;
    events: ReplayEvent[];
}

export interface UseReplayReturn extends ReplayState {
    load: (rakeId: number) => Promise<void>;
    play: () => void;
    pause: () => void;
    toggle: () => void;
    setSpeed: (speed: ReplaySpeed) => void;
    seek: (ms: number) => void;
    stop: () => void;
}

/**
 * Drives the time-scrubber replay on /control-panel/{siding}. Fetches all
 * loadrite_events for the rake, then advances a virtual clock through the
 * window so consumers can derive the wagon state at any point in time.
 */
export function useReplayState(): UseReplayReturn {
    const [state, setState] = useState<ReplayState>(() => initial());
    const playingRef = useRef<boolean>(false);
    const speedRef = useRef<ReplaySpeed>(1);
    const lastTickRef = useRef<number>(0);
    const rafRef = useRef<number | null>(null);

    playingRef.current = state.isPlaying;
    speedRef.current = state.speed;

    const load = useCallback(async (rakeId: number) => {
        setState((s) => ({ ...s, isLoading: true, loadError: null }));
        try {
            const r = await fetch(
                `/control-panel/rakes/${rakeId}/replay`,
                {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                },
            );
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            const data: ReplayPayload = await r.json();
            const events = data.events.filter((e) => e.event_time);
            if (events.length === 0) {
                throw new Error('No events to replay for this rake.');
            }
            const startMs = new Date(events[0].event_time!).getTime();
            const endMs = new Date(events[events.length - 1].event_time!).getTime();
            setState({
                isActive: true,
                isLoading: false,
                loadError: null,
                isPlaying: false,
                speed: 10,
                virtualTimeMs: startMs,
                startMs,
                endMs,
                durationMs: Math.max(1, endMs - startMs),
                events,
            });
        } catch (e) {
            setState((s) => ({
                ...s,
                isLoading: false,
                loadError: e instanceof Error ? e.message : 'Failed to load',
            }));
        }
    }, []);

    const play = useCallback(() => {
        setState((s) => {
            if (!s.isActive) return s;
            if (s.virtualTimeMs >= s.endMs) {
                return { ...s, virtualTimeMs: s.startMs, isPlaying: true };
            }
            return { ...s, isPlaying: true };
        });
    }, []);
    const pause = useCallback(
        () => setState((s) => ({ ...s, isPlaying: false })),
        [],
    );
    const toggle = useCallback(() => {
        setState((s) => {
            if (!s.isActive) return s;
            if (s.isPlaying) return { ...s, isPlaying: false };
            const t =
                s.virtualTimeMs >= s.endMs ? s.startMs : s.virtualTimeMs;
            return { ...s, isPlaying: true, virtualTimeMs: t };
        });
    }, []);
    const setSpeed = useCallback(
        (speed: ReplaySpeed) => setState((s) => ({ ...s, speed })),
        [],
    );
    const seek = useCallback(
        (ms: number) =>
            setState((s) => ({
                ...s,
                virtualTimeMs: Math.max(s.startMs, Math.min(s.endMs, ms)),
            })),
        [],
    );
    const stop = useCallback(() => setState(() => initial()), []);

    // RAF loop. Advances virtualTime by (speed × wallClockDelta) while playing.
    useEffect(() => {
        if (!state.isActive) return;
        const tick = (now: number) => {
            if (playingRef.current) {
                const dt = lastTickRef.current
                    ? now - lastTickRef.current
                    : 16;
                setState((s) => {
                    if (!s.isPlaying) return s;
                    const next = s.virtualTimeMs + dt * speedRef.current;
                    if (next >= s.endMs) {
                        return { ...s, virtualTimeMs: s.endMs, isPlaying: false };
                    }
                    return { ...s, virtualTimeMs: next };
                });
            }
            lastTickRef.current = now;
            rafRef.current = requestAnimationFrame(tick);
        };
        rafRef.current = requestAnimationFrame(tick);
        return () => {
            if (rafRef.current != null) cancelAnimationFrame(rafRef.current);
            rafRef.current = null;
            lastTickRef.current = 0;
        };
    }, [state.isActive]);

    return useMemo(
        () => ({
            ...state,
            load,
            play,
            pause,
            toggle,
            setSpeed,
            seek,
            stop,
        }),
        [state, load, play, pause, toggle, setSpeed, seek, stop],
    );
}

function initial(): ReplayState {
    return {
        isActive: false,
        isLoading: false,
        loadError: null,
        isPlaying: false,
        speed: 10,
        virtualTimeMs: 0,
        startMs: 0,
        endMs: 0,
        durationMs: 0,
        events: [],
    };
}
