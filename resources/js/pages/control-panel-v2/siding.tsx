import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Clock,
    MapPin,
    Package,
    Train as TrainIcon,
} from 'lucide-react';
import { useEffect, useState } from 'react';

import { AlertsFeed } from '@/components/control-room/AlertsFeed';
import { LoaderTrucks } from '@/components/control-room/LoaderTrucks';
import { LoadingProgressDonut } from '@/components/control-room/LoadingProgressDonut';
import { SummaryTiles } from '@/components/control-room/SummaryTiles';
import { TimeStatusDonut } from '@/components/control-room/TimeStatusDonut';
import { WagonLoadingTable } from '@/components/control-room/WagonLoadingTable';
import type {
    OverviewPayload,
    RakeData,
    WagonCard,
} from '@/components/control-room/types';
import { AlertToast, type ToastItem } from '@/components/control-panel-v2/AlertToast';
import {
    EventTickerMarquee,
    type TickerEvent,
} from '@/components/control-panel-v2/EventTickerMarquee';
import { PersistentHeader } from '@/components/control-panel-v2/PersistentHeader';
import { WagonDrawer } from '@/components/control-panel-v2/WagonDrawer';
import { WagonTrainV2 } from '@/components/control-panel-v2/WagonTrainV2';
import { useControlRoomBroadcast } from '@/hooks/use-control-room-broadcast';

interface Props {
    siding: { id: number; name: string; code: string | null };
    rakeData: RakeData | null;
    subscribable_sidings: number[];
    server_time: string;
    overview?: OverviewPayload;
}

export default function ControlPanelV2Siding({
    siding,
    rakeData,
    subscribable_sidings,
}: Props) {
    const [autoRefresh, setAutoRefresh] = useState<boolean>(true);
    const [selectedWagon, setSelectedWagon] = useState<WagonCard | null>(null);
    const [pulseEventId, setPulseEventId] = useState<string | null>(null);
    const [tickerEvents, setTickerEvents] = useState<TickerEvent[]>([]);
    const [toasts, setToasts] = useState<ToastItem[]>([]);
    const dismissToast = (id: string) =>
        setToasts((prev) => prev.filter((t) => t.id !== id));

    useEffect(() => {
        if (!autoRefresh) return;
        const id = window.setInterval(() => {
            router.reload({ only: ['rakeData', 'server_time'] });
        }, 30_000);
        return () => window.clearInterval(id);
    }, [autoRefresh]);

    useControlRoomBroadcast(subscribable_sidings, {
        onLoadriteEvent: (_sidingId, payload) => {
            setPulseEventId(payload.event_id);
            window.setTimeout(() => setPulseEventId(null), 700);

            const tone: TickerEvent['tone'] =
                payload.event_type === 'Add'
                    ? 'add'
                    : payload.event_type === 'Subtract'
                      ? 'subtract'
                      : payload.event_type === 'Short Total'
                        ? 'shortTotal'
                        : 'info';
            const operator = payload.operator ? `${payload.operator} · ` : '';
            const scale = payload.scale_id ? ` on ${payload.scale_id}` : '';
            const label = `${operator}${payload.event_type} ${payload.weight_mt.toFixed(2)} MT${scale}`;
            setTickerEvents((prev) =>
                [{ id: payload.event_id, label, tone }, ...prev].slice(0, 30),
            );

            if (autoRefresh && payload.event_type === 'Short Total') {
                router.reload({ only: ['rakeData', 'server_time'] });
            }
        },
        onWagonWeightUpdated: (_sidingId, payload) => {
            if (payload.status === 'overload') {
                setToasts((prev) => [
                    {
                        id: `overload-${payload.wagon_id}-${Date.now()}`,
                        title: `Overload on wagon ${payload.sequence}`,
                        body: `${payload.loadrite_weight_mt.toFixed(2)} MT (${payload.percentage.toFixed(0)}%)`,
                        severity: 'critical',
                    },
                    ...prev.slice(0, 4),
                ]);
            }
            if (autoRefresh) {
                router.reload({ only: ['rakeData', 'server_time'] });
            }
        },
    });

    const totalsForHeader = {
        sidings: 1,
        rakesActive: rakeData ? 1 : 0,
        alerts: rakeData?.alerts.length ?? 0,
    };

    const lastEventAt = rakeData?.last_event_at ?? null;

    const bulldozerWagonId = (() => {
        if (!rakeData) return null;
        const sorted = [...rakeData.wagons].sort(
            (a, b) => a.wagon_sequence - b.wagon_sequence,
        );
        const next = sorted.find(
            (w) => (w.loaded_mt ?? 0) === 0 && w.status !== 'unfit',
        );
        return next?.wagon_id ?? null;
    })();

    return (
        <AppLayout>
            <Head title={`Control Panel — ${siding.name}`} />

            <PersistentHeader
                totalSidings={totalsForHeader.sidings}
                rakesRunning={totalsForHeader.rakesActive}
                activeAlerts={totalsForHeader.alerts}
                autoRefresh={autoRefresh}
                onToggleAutoRefresh={() => setAutoRefresh((v) => !v)}
                lastUpdatedAt={lastEventAt}
                subtitle={`Rake Monitoring Dashboard · ${siding.name}`}
            />

            <main className="mx-auto max-w-[1600px] px-4 py-6">
                <Link
                    href="/control-panel-2"
                    className="mb-4 inline-flex items-center gap-1 text-sm text-sky-700 hover:underline"
                >
                    <ArrowLeft className="h-4 w-4" aria-hidden />
                    Back to All Sidings
                </Link>

                {!rakeData ? (
                    <div className="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">
                        No active rake at {siding.name}.
                    </div>
                ) : (
                    <>
                        <header className="mb-4 flex flex-wrap items-end justify-between gap-3">
                            <div className="flex items-center gap-3">
                                <MapPin className="h-7 w-7 text-amber-600" aria-hidden />
                                <div>
                                    <h1 className="text-2xl font-semibold uppercase tracking-wide text-slate-900">
                                        {siding.name}
                                    </h1>
                                    <div className="text-sm text-slate-500">
                                        Rake No.{' '}
                                        <span className="font-medium text-slate-700">
                                            {rakeData.rake.rake_number ?? '—'}
                                        </span>
                                        {rakeData.rake.state && (
                                            <span className="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium uppercase text-amber-800">
                                                {rakeData.rake.state}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </header>

                        <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                                <div className="flex items-center gap-2">
                                    <TrainIcon className="h-5 w-5 text-sky-700" aria-hidden />
                                    <span className="text-sm font-semibold uppercase tracking-wide text-slate-700">
                                        Wagon Position
                                    </span>
                                    <span className="text-xs text-slate-400">
                                        (Scroll to view all {rakeData.rake.wagon_count} wagons)
                                    </span>
                                </div>
                                <LegendBar />
                            </div>
                            <WagonTrainV2
                                wagons={rakeData.wagons}
                                bulldozerWagonId={bulldozerWagonId}
                                pulseEventId={pulseEventId}
                                size="full"
                                onWagonClick={setSelectedWagon}
                            />
                        </section>

                        <section className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-[1fr_320px]">
                            <div className="space-y-4">
                                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">
                                        Loader Status
                                    </h2>
                                    <LoaderTrucks
                                        loaders={rakeData.loaders}
                                        wagons={rakeData.wagons}
                                        wagonWidth={56}
                                    />
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                        <h3 className="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-600">
                                            Loading Progress
                                        </h3>
                                        <div className="flex items-center justify-center">
                                            <LoadingProgressDonut
                                                progress={rakeData.loading_progress}
                                                size={180}
                                            />
                                        </div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                        <h3 className="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-600">
                                            Time Status
                                        </h3>
                                        <div className="flex items-center justify-center">
                                            <TimeStatusDonut
                                                timeStatus={rakeData.time_status}
                                                lastEventAt={rakeData.last_event_at}
                                                size={180}
                                            />
                                        </div>
                                    </div>
                                </div>

                                <SummaryTiles
                                    totalNetMt={rakeData.kpis.total_loaded_mt}
                                    avgNetMt={rakeData.kpis.avg_net_mt}
                                    totalOverloadMt={rakeData.kpis.total_overload_mt}
                                    totalUnderloadMt={rakeData.kpis.total_underload_mt}
                                />

                                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">
                                        Wagon Loading Summary
                                    </h2>
                                    <WagonLoadingTable
                                        wagons={rakeData.wagons}
                                        loaders={rakeData.loaders}
                                    />
                                </div>
                            </div>

                            <aside className="space-y-4">
                                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-600">
                                        Active Alerts
                                    </h2>
                                    <AlertsFeed alerts={rakeData.alerts} />
                                </div>

                            </aside>
                        </section>

                        <section className="mt-4">
                            <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Live Loadrite Activity
                            </h2>
                            <EventTickerMarquee events={tickerEvents} />
                        </section>
                    </>
                )}
            </main>

            <AlertToast toasts={toasts} onDismiss={dismissToast} />

            <WagonDrawer
                wagon={selectedWagon}
                onClose={() => setSelectedWagon(null)}
            />
        </AppLayout>
    );
}

function LegendBar() {
    return (
        <div className="flex flex-wrap items-center gap-3 text-[11px] text-slate-600">
            <LegendDot color="#10B981" label="Loaded" />
            <LegendDot color="#F59E0B" label="Loading" />
            <LegendDot color="#EF4444" label="Over Load" />
            <LegendDot color="#94A3B8" label="Unfit/Skip" />
            <LegendDot color="#E2E8F0" label="Empty" />
        </div>
    );
}

function LegendDot({ color, label }: { color: string; label: string }) {
    return (
        <span className="inline-flex items-center gap-1.5">
            <span
                aria-hidden
                className="inline-block h-2.5 w-2.5 rounded-sm"
                style={{ backgroundColor: color }}
            />
            {label}
        </span>
    );
}

