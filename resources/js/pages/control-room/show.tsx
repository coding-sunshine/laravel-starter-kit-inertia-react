import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import {
    ArrowDownCircle,
    ArrowUpCircle,
    CalendarClock,
    Clock,
    Train,
    Tv,
    Weight,
} from 'lucide-react';
import { useState } from 'react';

import { AlertsFeed } from '@/components/control-room/AlertsFeed';
import { ControlRoomShell } from '@/components/control-room/ControlRoomShell';
import { KpiTile } from '@/components/control-room/KpiTile';
import { LegendStrip } from '@/components/control-room/LegendStrip';
import { LoaderTrucks } from '@/components/control-room/LoaderTrucks';
import { LoadingProgressDonut } from '@/components/control-room/LoadingProgressDonut';
import { SummaryTiles } from '@/components/control-room/SummaryTiles';
import { TimeStatusDonut } from '@/components/control-room/TimeStatusDonut';
import type {
    LoaderActivity,
    RakeData,
    WagonCard,
} from '@/components/control-room/types';
import { WagonLoadingTable } from '@/components/control-room/WagonLoadingTable';
import { WagonTrain } from '@/components/control-room/WagonTrain';
import { useControlRoomBroadcast } from '@/hooks/use-control-room-broadcast';
import { useStaleIndicator } from '@/hooks/use-stale-indicator';

interface Props {
    rakeData: RakeData;
    subscribable_sidings: number[];
    server_time: string;
}

const WAGON_WIDTH = 56;
const WAGON_GAP = 4;

function formatTime(value: string | null): string {
    if (!value) {
        return '—';
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

export default function ControlRoomShow({
    rakeData,
    subscribable_sidings,
}: Props) {
    const [data, setData] = useState<RakeData>(rakeData);

    const { lastEventAtAny } = useControlRoomBroadcast(subscribable_sidings, {
        onWagonLoadingUpdated: (_sidingId, payload) => {
            if (payload.rake_id !== data.rake.id) {
                return;
            }
            setData((prev) =>
                updateWagonInRake(prev, payload.wagon_id, {
                    status: payload.live_status as WagonCard['status'],
                    status_label: payload.live_status_label,
                    status_color: payload.live_status_color,
                    last_updated_at: payload.broadcast_at,
                }),
            );
        },
    });

    const stale = useStaleIndicator(lastEventAtAny ?? data.last_event_at, {
        only: ['rakeData'],
    });

    const activeLoaderWagonIds = data.loaders
        .filter((l) => l.status === 'active' && l.current_wagon_id !== null)
        .map((l) => l.current_wagon_id as number);

    const subtitle = [
        data.rake.siding?.name,
        data.rake.rake_number,
        `${data.rake.wagon_count} wagons`,
    ]
        .filter(Boolean)
        .join(' · ');

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Control Room', href: '/control-room' },
                {
                    title: data.rake.rake_number ?? `Rake #${data.rake.id}`,
                    href: `/control-room/${data.rake.id}`,
                },
            ]}
        >
            <Head title={`Control Room — ${data.rake.rake_number ?? 'Rake'}`} />
            <div className="-m-4 sm:-m-6 lg:-m-8">
                <ControlRoomShell
                    title={data.rake.rake_number ?? `Rake #${data.rake.id}`}
                    subtitle={subtitle}
                    staleStatus={stale.status}
                    secondsSince={stale.secondsSince}
                    headerActions={
                        data.rake.siding?.id ? (
                            <a
                                href={`/sidings/${data.rake.siding.id}/monitor`}
                                target="_blank"
                                rel="noopener noreferrer"
                                title="Open TV monitor (new tab) — chrome-free siding view for displays"
                                className="inline-flex items-center gap-1.5 rounded-md border border-input bg-background px-2.5 py-1.5 text-xs font-medium text-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                            >
                                <Tv className="size-4" aria-hidden="true" />
                                TV Mode
                            </a>
                        ) : null
                    }
                >
                    {/* KPI strip */}
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                        <KpiTile
                            icon={Train}
                            label="Wagons"
                            value={data.rake.wagon_count}
                            accentColor="blue"
                        />
                        <KpiTile
                            icon={Weight}
                            label="Total Net"
                            value={data.kpis.total_loaded_mt}
                            unit="MT"
                            decimals={2}
                            accentColor="purple"
                        />
                        <KpiTile
                            icon={ArrowUpCircle}
                            label="Over Load"
                            value={data.kpis.total_overload_mt}
                            unit="MT"
                            decimals={2}
                            accentColor="red"
                        />
                        <KpiTile
                            icon={ArrowDownCircle}
                            label="Under Load"
                            value={data.kpis.total_underload_mt}
                            unit="MT"
                            decimals={2}
                            accentColor="amber"
                        />
                        <KpiTile
                            icon={CalendarClock}
                            label="Placement"
                            value={formatTime(data.kpis.placement_time)}
                            accentColor="purple"
                        />
                        <KpiTile
                            icon={Clock}
                            label="Loading time"
                            value={
                                data.kpis.loading_elapsed_minutes !== null
                                    ? `${data.kpis.loading_elapsed_minutes} min`
                                    : null
                            }
                            accentColor="cyan"
                        />
                    </div>

                    {/* Wagon train */}
                    <div className="mt-4 rounded-xl border border-border bg-card p-4">
                        <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p className="text-sm font-semibold text-foreground">
                                    Wagon position
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Scroll horizontally to see all{' '}
                                    {data.rake.wagon_count} wagons
                                </p>
                            </div>
                            <LegendStrip counts={data.status_counts} compact />
                        </div>
                        <div className="relative overflow-x-auto">
                            <WagonTrain
                                wagons={data.wagons}
                                activeLoaderWagonIds={activeLoaderWagonIds}
                            />
                            <LoaderTrucks
                                loaders={data.loaders}
                                wagons={data.wagons}
                                wagonWidth={WAGON_WIDTH}
                                gap={WAGON_GAP}
                            />
                        </div>
                    </div>

                    {/* Status row: loaders / progress / time / alerts */}
                    <div className="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-4">
                        <div className="rounded-xl border border-border bg-card p-4">
                            <p className="mb-3 text-sm font-semibold text-foreground">
                                Loader status
                            </p>
                            <LoaderStatusList loaders={data.loaders} />
                        </div>
                        <div className="flex flex-col items-center justify-center rounded-xl border border-border bg-card p-4">
                            <p className="mb-3 self-start text-sm font-semibold text-foreground">
                                Loading progress
                            </p>
                            <LoadingProgressDonut
                                progress={data.loading_progress}
                            />
                        </div>
                        <div className="flex flex-col items-center justify-center rounded-xl border border-border bg-card p-4">
                            <p className="mb-3 self-start text-sm font-semibold text-foreground">
                                Time status
                            </p>
                            <TimeStatusDonut timeStatus={data.time_status} />
                        </div>
                        <div className="rounded-xl border border-border bg-card p-4">
                            <AlertsFeed alerts={data.alerts} />
                        </div>
                    </div>

                    {/* Summary tiles — full-width strip so labels never wrap awkwardly. */}
                    <div className="mt-4 rounded-xl border border-border bg-card p-4">
                        <p className="mb-3 text-sm font-semibold text-foreground">
                            Summary
                        </p>
                        <SummaryTiles
                            totalNetMt={data.kpis.total_loaded_mt}
                            avgNetMt={data.kpis.avg_net_mt}
                            totalOverloadMt={data.kpis.total_overload_mt}
                            totalUnderloadMt={data.kpis.total_underload_mt}
                        />
                    </div>

                    {/* Wagon loading summary table — full width below summary. */}
                    <div className="mt-4 rounded-xl border border-border bg-card p-4">
                        <p className="mb-3 text-sm font-semibold text-foreground">
                            Wagon loading summary
                        </p>
                        <WagonLoadingTable
                            wagons={data.wagons}
                            loaders={data.loaders}
                        />
                    </div>
                </ControlRoomShell>
            </div>
        </AppLayout>
    );
}

function LoaderStatusList({ loaders }: { loaders: LoaderActivity[] }) {
    if (loaders.length === 0) {
        return (
            <p className="text-xs text-muted-foreground">
                No loaders assigned.
            </p>
        );
    }

    return (
        <ul className="space-y-2">
            {loaders.map((loader) => {
                const dotClass =
                    loader.status === 'active'
                        ? 'bg-emerald-500'
                        : 'bg-muted-foreground/40';

                return (
                    <li
                        key={loader.loader_id}
                        className="flex items-center justify-between gap-2 rounded-md bg-muted/30 px-2.5 py-2"
                    >
                        <div className="flex min-w-0 items-center gap-2">
                            <span
                                className={`size-2 shrink-0 rounded-full ${dotClass}`}
                                aria-hidden="true"
                            />
                            <div className="min-w-0">
                                <p className="truncate text-xs font-semibold text-foreground">
                                    {loader.loader_name}
                                    {loader.loader_code && (
                                        <span className="ml-1 text-[10px] font-normal text-muted-foreground">
                                            {loader.loader_code}
                                        </span>
                                    )}
                                </p>
                                <p className="text-[10px] text-muted-foreground">
                                    {loader.status === 'active'
                                        ? `Wagon ${loader.current_wagon_number ?? '—'} · ${loader.wagons_completed} done`
                                        : `Idle · ${loader.wagons_completed} done`}
                                </p>
                            </div>
                        </div>
                        {loader.status === 'active' && (
                            <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                LOADING
                            </span>
                        )}
                    </li>
                );
            })}
        </ul>
    );
}

function updateWagonInRake(
    rake: RakeData,
    wagonId: number,
    patch: Partial<WagonCard>,
): RakeData {
    return {
        ...rake,
        wagons: rake.wagons.map((w) =>
            w.wagon_id === wagonId ? { ...w, ...patch } : w,
        ),
        last_event_at: patch.last_updated_at ?? rake.last_event_at,
    };
}
