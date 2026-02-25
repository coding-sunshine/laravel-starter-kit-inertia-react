import { useEffect, useRef } from 'react';

interface LoadState {
    active_step: string;
    attempt_no: number;
    failure_reason: string | null;
}

interface RakeWagonLoading {
    id: number;
    wagon_id: number;
    loader_id: number | null;
    loaded_quantity_mt: string;
    attempt_no: number;
    wagon?: {
        id: number;
        wagon_number: string;
        wagon_sequence: number;
        tare_weight_mt: string | null;
        pcc_weight_mt: string | null;
    };
    loader?: {
        id: number;
        loader_name: string;
        code: string;
    };
}

interface GuardInspectionRecord {
    id: number;
    inspection_time: string;
    movement_permission_time: string | null;
    is_approved: boolean;
    remarks: string | null;
}

interface RakeWagonWeighment {
    id: number;
    wagon_id: number;
    gross_weight_mt: string;
    is_overloaded: boolean;
}

interface WeighmentRecord {
    id: number;
    weighment_time: string;
    total_weight_mt: string;
    status: string | null;
    train_speed_kmph: number;
    attempt_no: number;
    wagon_weighments?: RakeWagonWeighment[];
}

interface RakeLoad {
    id: number;
    placement_time: string;
    free_time_minutes: number;
    status: string;
    wagonLoadings?: RakeWagonLoading[];
    wagon_loadings?: RakeWagonLoading[];
    guardInspections?: GuardInspectionRecord[];
    guard_inspections?: GuardInspectionRecord[];
    weighments?: WeighmentRecord[];
}

interface BroadcastEvents {
    'load.updated': {
        rake_id: number;
        rake_number: string;
        load_state: LoadState;
        trigger: string;
        rake_load: RakeLoad;
        placement_time: string;
        free_time_minutes: number;
        status: string;
    };
    'wagon-loading.updated': {
        rake_id: number;
        rake_number: string;
        action: string;
        wagon_loading: RakeWagonLoading;
        wagon_id: number;
        attempt_no: number;
    };
    'guard-inspection.updated': {
        rake_id: number;
        rake_number: string;
        action: string;
        guard_inspection: GuardInspectionRecord;
        inspection_time: string;
        movement_permission_time: string | null;
        is_approved: boolean;
        remarks: string | null;
        attempt_no: number;
    };
    'weighment.updated': {
        rake_id: number;
        rake_number: string;
        action: string;
        weighment: WeighmentRecord;
        weighment_time: string;
        train_speed_kmph: number;
        total_weight_mt: string;
        status: string;
        attempt_no: number;
        wagon_weighments: RakeWagonWeighment[];
    };
}

export function useRakeLoadBroadcasting(
    rakeId: number,
    callbacks: {
        onLoadUpdated?: (data: BroadcastEvents['load.updated']) => void;
        onWagonLoadingUpdated?: (data: BroadcastEvents['wagon-loading.updated']) => void;
        onGuardInspectionUpdated?: (data: BroadcastEvents['guard-inspection.updated']) => void;
        onWeighmentUpdated?: (data: BroadcastEvents['weighment.updated']) => void;
    }
) {
    const channelRef = useRef<any>(null);

    useEffect(() => {
        // Only set up broadcasting if Echo is available
        if (!window.Echo) {
            console.warn('Echo is not available. Broadcasting will not work.');
            return;
        }

        // Subscribe to the private channel for this rake load
        channelRef.current = window.Echo.private(`rake-load.${rakeId}`);

        // Listen for load updates
        channelRef.current.listen('load.updated', (data: BroadcastEvents['load.updated']) => {
            console.log('Load updated:', data);
            callbacks.onLoadUpdated?.(data);
        });

        // Listen for wagon loading updates
        channelRef.current.listen('wagon-loading.updated', (data: BroadcastEvents['wagon-loading.updated']) => {
            console.log('Wagon loading updated:', data);
            callbacks.onWagonLoadingUpdated?.(data);
        });

        // Listen for guard inspection updates
        channelRef.current.listen('guard-inspection.updated', (data: BroadcastEvents['guard-inspection.updated']) => {
            console.log('Guard inspection updated:', data);
            callbacks.onGuardInspectionUpdated?.(data);
        });

        // Listen for weighment updates
        channelRef.current.listen('weighment.updated', (data: BroadcastEvents['weighment.updated']) => {
            console.log('Weighment updated:', data);
            callbacks.onWeighmentUpdated?.(data);
        });

        // Cleanup function
        return () => {
            if (channelRef.current) {
                window.Echo.leaveChannel(`rake-load.${rakeId}`);
                channelRef.current = null;
            }
        };
    }, [rakeId, callbacks]);

    // Function to manually reconnect if needed
    const reconnect = () => {
        if (channelRef.current) {
            window.Echo.leaveChannel(`rake-load.${rakeId}`);
        }
        
        if (window.Echo) {
            channelRef.current = window.Echo.private(`rake-load.${rakeId}`);
            
            // Reattach all listeners
            channelRef.current.listen('load.updated', (data: BroadcastEvents['load.updated']) => {
                callbacks.onLoadUpdated?.(data);
            });
            
            channelRef.current.listen('wagon-loading.updated', (data: BroadcastEvents['wagon-loading.updated']) => {
                callbacks.onWagonLoadingUpdated?.(data);
            });
            
            channelRef.current.listen('guard-inspection.updated', (data: BroadcastEvents['guard-inspection.updated']) => {
                callbacks.onGuardInspectionUpdated?.(data);
            });
            
            channelRef.current.listen('weighment.updated', (data: BroadcastEvents['weighment.updated']) => {
                callbacks.onWeighmentUpdated?.(data);
            });
        }
    };

    return { reconnect };
}
