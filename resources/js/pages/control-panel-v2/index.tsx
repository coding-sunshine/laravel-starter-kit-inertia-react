import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import { PersistentHeader } from '@/components/control-panel-v2/PersistentHeader';
import { SidingCardV2 } from '@/components/control-panel-v2/SidingCardV2';
import type { OverviewPayload } from '@/components/control-room/types';
import { useControlRoomBroadcast } from '@/hooks/use-control-room-broadcast';

interface Props {
    overview: OverviewPayload;
    subscribable_sidings: number[];
    server_time: string;
}

export default function ControlPanelV2Index({
    overview,
    subscribable_sidings,
}: Props) {
    const [autoRefresh, setAutoRefresh] = useState<boolean>(true);
    const [view, setView] = useState<'card' | 'table'>('card');

    const [pulseBySiding, setPulseBySiding] = useState<Record<number, string | null>>({});

    const { lastEventAtAny } = useControlRoomBroadcast(subscribable_sidings, {
        onLoadriteEvent: (sidingId, payload) => {
            setPulseBySiding((prev) => ({ ...prev, [sidingId]: payload.event_id }));
            window.setTimeout(() => {
                setPulseBySiding((prev) => ({ ...prev, [sidingId]: null }));
            }, 700);
            if (autoRefresh && payload.event_type === 'Short Total') {
                router.reload({ only: ['overview', 'server_time'] });
            }
        },
        onWagonWeightUpdated: () => {
            if (autoRefresh) {
                router.reload({ only: ['overview', 'server_time'] });
            }
        },
        onWagonLoadingUpdated: () => {
            if (autoRefresh) {
                router.reload({ only: ['overview', 'server_time'] });
            }
        },
        onRakeStatusUpdated: () => {
            if (autoRefresh) {
                router.reload({ only: ['overview', 'server_time'] });
            }
        },
    });

    const goToSiding = (sidingId: number) => {
        router.visit(`/control-panel-2/${sidingId}`);
    };

    return (
        <AppLayout>
            <Head title="Control Panel — All Sidings" />

            <PersistentHeader
                totalSidings={overview.totals.sidings}
                rakesRunning={overview.totals.rakes_active}
                activeAlerts={overview.totals.alerts_active}
                autoRefresh={autoRefresh}
                onToggleAutoRefresh={() => setAutoRefresh((v) => !v)}
                lastUpdatedAt={lastEventAtAny ?? overview.last_event_at}
                view={view}
                onChangeView={setView}
                subtitle="Multi-Siding Monitoring Dashboard"
            />

            <main className="mx-auto max-w-[1600px] px-4 py-6">
                <div className="mb-4">
                    <h1 className="text-xl font-semibold text-slate-900">
                        All Sidings Overview
                    </h1>
                    <p className="text-sm text-slate-500">
                        Real-time status of all sidings at a glance
                    </p>
                </div>

                {overview.sidings.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">
                        No sidings visible for your account.
                    </div>
                ) : (
                    <div className="space-y-4">
                        {overview.sidings.map((s, idx) => (
                            <SidingCardV2
                                key={s.siding_id}
                                siding={s}
                                index={idx}
                                pulseEventId={pulseBySiding[s.siding_id] ?? null}
                                onOpen={goToSiding}
                            />
                        ))}
                    </div>
                )}
            </main>
        </AppLayout>
    );
}
