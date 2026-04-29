import { createStore } from 'zustand';

export interface WagonSlice {
    id: number;
    sequence: number;
    loadriteWeightMt: number | null;
    loadedQuantityMt: number | null;
    ccCapacityMt: number;
    weightSource: 'manual' | 'loadrite' | 'weighbridge';
    percentage: number;
    loadriteOverride: boolean;
}

export interface RakeSlice {
    id: number;
    status: string;
    wagonCount: number;
    wagonsLoaded: number;
    placementTime: string | null;
    loadingEndTime: string | null;
}

export interface AlertItem {
    id: string;
    level: 'warning' | 'critical';
    wagonNumber: string;
    weightMt: number;
    ccMt: number;
    percentage: number;
    receivedAt: string;
}

interface SidingState {
    wagons: Record<number, WagonSlice>;
    rake: RakeSlice | null;
    alerts: AlertItem[];
    loadriteActive: boolean;
}

interface SidingActions {
    init: (props: { wagons: WagonSlice[]; rake: RakeSlice | null; loadriteActive: boolean }) => void;
    updateWagon: (event: {
        wagon_id: number;
        sequence: number;
        loadrite_weight_mt: number;
        weight_source: 'manual' | 'loadrite' | 'weighbridge';
        percentage: number;
        status: string;
    }) => void;
    updateRake: (event: {
        rake_id: number;
        status: string;
        wagons_loaded: number;
        wagon_count: number;
        placement_time: string | null;
        loading_end_time: string | null;
    }) => void;
    addAlert: (event: {
        wagon_id: number;
        wagon_number: string;
        weight_mt: number;
        cc_mt: number;
        percentage: number;
        level: 'warning' | 'critical';
    }) => void;
}

export type SidingStore = SidingState & SidingActions;

export function createSidingStore() {
    return createStore<SidingStore>()((set) => ({
        wagons: {},
        rake: null,
        alerts: [],
        loadriteActive: false,

        init: ({ wagons, rake, loadriteActive }) => {
            const wagonsMap: Record<number, WagonSlice> = {};
            wagons.forEach((w) => { wagonsMap[w.sequence] = w; });
            set({ wagons: wagonsMap, rake, loadriteActive });
        },

        updateWagon: (event) => {
            set((state) => ({
                wagons: {
                    ...state.wagons,
                    [event.sequence]: {
                        ...state.wagons[event.sequence],
                        id: event.wagon_id,
                        sequence: event.sequence,
                        loadriteWeightMt: event.loadrite_weight_mt,
                        weightSource: event.weight_source,
                        percentage: event.percentage,
                    },
                },
            }));
        },

        updateRake: (event) => {
            set((state) => ({
                rake: state.rake
                    ? {
                        ...state.rake,
                        status: event.status,
                        wagonsLoaded: event.wagons_loaded,
                        wagonCount: event.wagon_count,
                        placementTime: event.placement_time,
                        loadingEndTime: event.loading_end_time,
                      }
                    : null,
            }));
        },

        addAlert: (event) => {
            const alert: AlertItem = {
                id: `${event.wagon_id}-${event.level}-${Date.now()}`,
                level: event.level,
                wagonNumber: event.wagon_number,
                weightMt: event.weight_mt,
                ccMt: event.cc_mt,
                percentage: event.percentage,
                receivedAt: new Date().toISOString(),
            };
            set((state) => ({
                alerts: [alert, ...state.alerts].slice(0, 10),
            }));
        },
    }));
}

export type SidingStoreApi = ReturnType<typeof createSidingStore>;
