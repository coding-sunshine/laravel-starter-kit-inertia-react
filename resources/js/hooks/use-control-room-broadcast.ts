import { useEffect, useRef, useState } from 'react';

interface WagonLoadingUpdatedPayload {
    rake_id: number;
    siding_id: number;
    rake_number: string | null;
    wagon_id: number;
    wagon_loading: Record<string, unknown>;
    live_status: string;
    live_status_label: string;
    live_status_color: string;
    broadcast_at: string;
}

interface WagonWeightUpdatedPayload {
    wagon_id: number;
    sequence: number;
    loadrite_weight_mt: number;
    weight_source: string;
    percentage: number;
    status: string;
}

interface RakeStatusUpdatedPayload {
    rake_id: number;
    state: string;
}

export interface ControlRoomEvents {
    onWagonLoadingUpdated?: (
        sidingId: number,
        payload: WagonLoadingUpdatedPayload,
    ) => void;
    onWagonWeightUpdated?: (
        sidingId: number,
        payload: WagonWeightUpdatedPayload,
    ) => void;
    onRakeStatusUpdated?: (
        sidingId: number,
        payload: RakeStatusUpdatedPayload,
    ) => void;
}

/**
 * Subscribes to private siding.{id} channels for the given sidings and dispatches
 * Control Room events. Tracks the most recent event timestamp per siding so the
 * stale indicator can render "live | lagging | stale" without a separate clock.
 */
export function useControlRoomBroadcast(
    sidingIds: number[],
    handlers: ControlRoomEvents,
): {
    lastEventAt: Record<number, string>;
    lastEventAtAny: string | null;
} {
    const handlersRef = useRef(handlers);
    handlersRef.current = handlers;

    const [lastEventAt, setLastEventAt] = useState<Record<number, string>>({});
    const [lastEventAtAny, setLastEventAtAny] = useState<string | null>(null);

    useEffect(() => {
        if (
            sidingIds.length === 0 ||
            typeof window === 'undefined' ||
            !window.Echo
        ) {
            return;
        }

        const channels: {
            id: number;
            channel: ReturnType<typeof window.Echo.private>;
        }[] = [];

        const stamp = (sidingId: number) => {
            const at = new Date().toISOString();
            setLastEventAt((prev) => ({ ...prev, [sidingId]: at }));
            setLastEventAtAny(at);
        };

        for (const sidingId of sidingIds) {
            const channel = window.Echo.private(`siding.${sidingId}`);

            channel.listen(
                '.wagon-loading.updated',
                (payload: WagonLoadingUpdatedPayload) => {
                    stamp(sidingId);
                    handlersRef.current.onWagonLoadingUpdated?.(
                        sidingId,
                        payload,
                    );
                },
            );
            channel.listen(
                '.wagon.weight.updated',
                (payload: WagonWeightUpdatedPayload) => {
                    stamp(sidingId);
                    handlersRef.current.onWagonWeightUpdated?.(
                        sidingId,
                        payload,
                    );
                },
            );
            channel.listen(
                '.rake-status.updated',
                (payload: RakeStatusUpdatedPayload) => {
                    stamp(sidingId);
                    handlersRef.current.onRakeStatusUpdated?.(
                        sidingId,
                        payload,
                    );
                },
            );

            channels.push({ id: sidingId, channel });
        }

        return () => {
            for (const { id, channel } of channels) {
                channel.stopListening('.wagon-loading.updated');
                channel.stopListening('.wagon.weight.updated');
                channel.stopListening('.rake-status.updated');
                window.Echo?.leaveChannel(`siding.${id}`);
            }
        };
    }, [sidingIds.join(',')]);

    return { lastEventAt, lastEventAtAny };
}
