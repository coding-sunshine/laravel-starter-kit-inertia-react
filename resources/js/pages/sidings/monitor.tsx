import { createContext, useContext, useEffect, useRef } from 'react';
import type { StoreApi } from 'zustand';
import { useStore } from 'zustand';
import { AlertsFeed } from '@/components/SidingMonitor/AlertsFeed';
import { CountdownTimer } from '@/components/SidingMonitor/CountdownTimer';
import { LoadingRing } from '@/components/SidingMonitor/LoadingRing';
import { StatsBar } from '@/components/SidingMonitor/StatsBar';
import { WagonTrain } from '@/components/SidingMonitor/WagonTrain';
import { createSidingStore } from '@/stores/useSidingStore';
import type { SidingStore } from '@/stores/useSidingStore';

const StoreContext = createContext<StoreApi<SidingStore> | null>(null);

function useSidingStore<T>(selector: (state: SidingStore) => T): T {
    const store = useContext(StoreContext);
    if (!store) throw new Error('Missing SidingStoreProvider');
    return useStore(store, selector);
}

interface PageProps {
    siding: { id: number; name: string };
    rake: {
        id: number;
        status: string;
        wagon_count: number;
        wagons_loaded: number;
        placement_time: string | null;
        loading_end_time: string | null;
    } | null;
    wagons: Array<{
        id: number;
        sequence: number;
        loadrite_weight_mt: number | null;
        loaded_quantity_mt: number | null;
        cc_capacity_mt: number;
        weight_source: 'manual' | 'loadrite' | 'weighbridge';
        percentage: number;
        loadrite_override: boolean;
    }>;
    free_minutes: number;
    loadrite_active: boolean;
}

function SidingMonitorContent({ siding, free_minutes, loadrite_active }: PageProps) {
    const wagons = useSidingStore((s) => s.wagons);
    const rake = useSidingStore((s) => s.rake);
    const alerts = useSidingStore((s) => s.alerts);

    const activeWagon = Object.values(wagons)
        .filter((w) => w.weightSource === 'loadrite')
        .sort((a, b) => b.sequence - a.sequence)[0] ?? null;

    return (
        <div className="min-h-screen bg-[#020617] p-4 text-slate-100 lg:p-6">
            <div className="mx-auto max-w-7xl space-y-4">
                <StatsBar
                    sidingName={siding.name}
                    wagonsLoaded={rake?.wagonsLoaded ?? 0}
                    wagonCount={rake?.wagonCount ?? 0}
                    loadriteActive={loadrite_active}
                />

                <CountdownTimer
                    placementTime={rake?.placementTime ?? null}
                    freeMinutes={free_minutes}
                />

                <div className="grid gap-4 lg:grid-cols-[1fr_200px]">
                    <div className="rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                        <h2 className="mb-3 text-sm font-medium text-slate-500">Wagon Train</h2>
                        <WagonTrain wagons={wagons} />
                    </div>

                    {activeWagon && (
                        <div className="flex items-center justify-center rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                            <LoadingRing
                                percentage={activeWagon.percentage}
                                weightMt={activeWagon.loadriteWeightMt ?? 0}
                                ccMt={activeWagon.ccCapacityMt}
                                sequence={activeWagon.sequence}
                            />
                        </div>
                    )}
                </div>

                <div className="rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                    <h2 className="mb-3 text-sm font-medium text-slate-500">Alerts</h2>
                    <AlertsFeed alerts={alerts} />
                </div>
            </div>
        </div>
    );
}

export default function Monitor(props: PageProps) {
    const storeRef = useRef<ReturnType<typeof createSidingStore>>(null!);
    if (!storeRef.current) {
        storeRef.current = createSidingStore();
    }

    const { siding, wagons, rake, loadrite_active } = props;

    useEffect(() => {
        storeRef.current.getState().init({
            wagons: wagons.map((w) => ({
                id: w.id,
                sequence: w.sequence,
                loadriteWeightMt: w.loadrite_weight_mt,
                loadedQuantityMt: w.loaded_quantity_mt,
                ccCapacityMt: w.cc_capacity_mt,
                weightSource: w.weight_source,
                percentage: w.percentage,
                loadriteOverride: w.loadrite_override,
            })),
            rake: rake
                ? {
                    id: rake.id,
                    status: rake.status,
                    wagonCount: rake.wagon_count,
                    wagonsLoaded: rake.wagons_loaded,
                    placementTime: rake.placement_time,
                    loadingEndTime: rake.loading_end_time,
                  }
                : null,
            loadriteActive: loadrite_active,
        });

        const channel = (window as any).Echo?.private(`siding.${siding.id}`);

        if (channel) {
            channel
                .listen('.wagon.weight.updated', (e: any) => storeRef.current.getState().updateWagon(e))
                .listen('.wagon.overload.warning', (e: any) => storeRef.current.getState().addAlert(e))
                .listen('.wagon.overload.critical', (e: any) => storeRef.current.getState().addAlert(e))
                .listen('.rake.status.updated', (e: any) => storeRef.current.getState().updateRake(e));
        }

        return () => {
            (window as any).Echo?.leave(`siding.${siding.id}`);
        };
    }, [siding.id]);

    return (
        <StoreContext.Provider value={storeRef.current}>
            <SidingMonitorContent {...props} />
        </StoreContext.Provider>
    );
}
