import { useEffect, useRef } from 'react';

export interface StockUpdatedPayload {
    siding_id: number;
    closing_balance_mt: number;
}

/**
 * Subscribe to private channel siding.{id}.stock and call onStockUpdated
 * when event stock.updated is broadcast (e.g. from daily vehicle entry or ledger updates).
 * Use on both web dashboard and mobile SidingDashboard for live stock.
 */
export function useSidingStockBroadcast(
    sidingIds: number[],
    onStockUpdated: (sidingId: number, closingBalanceMt: number) => void,
): void {
    const onStockUpdatedRef = useRef(onStockUpdated);
    onStockUpdatedRef.current = onStockUpdated;

    useEffect(() => {
        if (sidingIds.length === 0 || typeof window === 'undefined' || !window.Echo) {
            return;
        }

        const channels: { id: number; channel: ReturnType<typeof window.Echo.private> }[] = [];

        for (const sidingId of sidingIds) {
            const channel = window.Echo.private(`siding.${sidingId}.stock`);
            channel.listen('.stock.updated', (payload: StockUpdatedPayload) => {
                onStockUpdatedRef.current(payload.siding_id, payload.closing_balance_mt);
            });
            channels.push({ id: sidingId, channel });
        }

        return () => {
            for (const { id, channel } of channels) {
                channel.stopListening('.stock.updated');
                window.Echo?.leaveChannel(`siding.${id}.stock`);
            }
        };
    }, [sidingIds.join(',')]);
}
