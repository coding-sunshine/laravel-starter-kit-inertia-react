import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { AlertTriangle, MapPin, Train as TrainIcon } from 'lucide-react';

import { ControlRoomShell } from '@/components/control-room/ControlRoomShell';
import { KpiTile } from '@/components/control-room/KpiTile';
import { LegendStrip } from '@/components/control-room/LegendStrip';
import { SidingOverviewCard } from '@/components/control-room/SidingOverviewCard';
import type { OverviewPayload } from '@/components/control-room/types';
import { useControlRoomBroadcast } from '@/hooks/use-control-room-broadcast';
import { useStaleIndicator } from '@/hooks/use-stale-indicator';
import { useTvMode } from '@/hooks/use-tv-mode';

interface Props {
    overview: OverviewPayload;
    subscribable_sidings: number[];
    server_time: string;
}

export default function ControlRoomIndex({
    overview,
    subscribable_sidings,
}: Props) {
    const { lastEventAtAny } = useControlRoomBroadcast(
        subscribable_sidings,
        {},
    );
    const stale = useStaleIndicator(lastEventAtAny ?? overview.last_event_at, {
        only: ['overview'],
    });

    const tv = useTvMode(overview.sidings.length);

    const subtitle = `${overview.totals.sidings} sidings · ${overview.totals.rakes_active} rakes active · ${overview.totals.alerts_active} alerts`;

    return (
        <AppLayout
            breadcrumbs={[{ title: 'Control Room', href: '/control-room' }]}
        >
            <Head title="Control Room" />
            <div
                className="-m-4 sm:-m-6 lg:-m-8"
                style={
                    tv.enabled ? { fontSize: `${tv.fontScale}rem` } : undefined
                }
            >
                <ControlRoomShell
                    title="Central Monitoring"
                    subtitle={subtitle}
                    staleStatus={stale.status}
                    secondsSince={stale.secondsSince}
                >
                    {/* Top totals strip */}
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-3">
                        <KpiTile
                            icon={MapPin}
                            label="Total Sidings"
                            value={overview.totals.sidings}
                            accentColor="blue"
                        />
                        <KpiTile
                            icon={TrainIcon}
                            label="Rakes Running"
                            value={overview.totals.rakes_active}
                            accentColor="green"
                        />
                        <KpiTile
                            icon={AlertTriangle}
                            label="Active Alerts"
                            value={overview.totals.alerts_active}
                            accentColor={
                                overview.totals.alerts_active > 0
                                    ? 'red'
                                    : 'gray'
                            }
                        />
                    </div>

                    <div className="mt-4 flex justify-end">
                        <LegendStrip compact />
                    </div>

                    {/* Siding cards */}
                    {overview.sidings.length === 0 ? (
                        <div className="mt-6 rounded-xl border border-dashed border-border bg-card p-8 text-center text-sm text-muted-foreground">
                            No sidings visible to your account.
                        </div>
                    ) : (
                        <div className="mt-4 space-y-3">
                            {overview.sidings.map((siding, idx) => (
                                <SidingOverviewCard
                                    key={siding.siding_id}
                                    siding={siding}
                                    href={
                                        siding.rake
                                            ? `/control-room/${siding.rake.id}`
                                            : '#'
                                    }
                                    isFocused={
                                        tv.enabled && tv.activeIndex === idx
                                    }
                                />
                            ))}
                        </div>
                    )}
                </ControlRoomShell>
            </div>
        </AppLayout>
    );
}
