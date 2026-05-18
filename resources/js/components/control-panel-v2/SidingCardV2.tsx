import { motion } from 'framer-motion';
import {
    AlertTriangle,
    Box,
    ChevronRight,
    Clock,
    MapPin,
    Package,
    SkipForward,
    Truck,
} from 'lucide-react';
import { useMemo } from 'react';

import type { OverviewSiding } from '@/components/control-room/types';
import { LoadingProgressDonut } from '@/components/control-room/LoadingProgressDonut';

import { CountUpNumber } from './CountUpNumber';
import { WagonTrainV2 } from './WagonTrainV2';

interface Props {
    siding: OverviewSiding;
    onOpen?: (sidingId: number) => void;
    index?: number;
    pulseEventId?: string | null;
}

export function SidingCardV2({ siding, onOpen, index = 0, pulseEventId = null }: Props) {
    const counts = useMemo(() => {
        const s = siding.status_counts as Record<string, number> | [];
        if (Array.isArray(s)) {
            return { empty: 0, loading: 0, loaded: 0, overload: 0, unfit: 0 };
        }
        return {
            empty: s.empty ?? 0,
            loading: s.loading ?? 0,
            loaded: s.loaded ?? 0,
            overload: s.overload ?? 0,
            unfit: s.unfit ?? 0,
        };
    }, [siding.status_counts]);

    const totalWagons = siding.rake?.wagon_count ?? 0;
    const progress = siding.loading_progress ?? {
        loaded: 0,
        total: totalWagons,
        percent: 0,
    };
    const kpis = siding.kpis;

    const bulldozerWagonId = useMemo(() => {
        // Next unfilled by sequence
        const sorted = [...siding.wagons].sort(
            (a, b) => a.wagon_sequence - b.wagon_sequence,
        );
        const next = sorted.find(
            (w) => (w.loaded_mt ?? 0) === 0 && w.status !== 'unfit',
        );
        return next?.wagon_id ?? null;
    }, [siding.wagons]);

    const placementTime = siding.time_status?.anchor_at
        ? new Date(siding.time_status.anchor_at).toLocaleTimeString('en-IN', {
              hour: '2-digit',
              minute: '2-digit',
              hour12: true,
          })
        : '—';

    const loadingMinutes = siding.time_status?.elapsed_minutes ?? null;

    return (
        <motion.section
            initial={{ opacity: 0, y: 16 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.35, delay: index * 0.08, ease: 'easeOut' }}
            className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
        >
            <div className="flex flex-wrap items-center gap-3">
                <div className="flex items-center gap-2">
                    <MapPin className="h-5 w-5 text-amber-600" aria-hidden />
                    <h2 className="text-lg font-semibold uppercase tracking-wide text-slate-900">
                        {siding.siding_name}
                    </h2>
                </div>
                {siding.rake && (
                    <span className="text-sm text-slate-600">
                        Rake No.{' '}
                        {siding.rake.rake_serial_number ??
                            siding.rake.rake_number ??
                            '—'}
                    </span>
                )}
                {siding.rake?.state && (
                    <span className="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium uppercase tracking-wide text-amber-800">
                        {siding.rake.state}
                    </span>
                )}

                <div className="ml-auto flex items-center gap-3">
                    <LoadingProgressDonut
                        progress={progress}
                        size={72}
                        strokeWidth={9}
                        showLabel={false}
                    />
                    <CountChips
                        counts={counts}
                        empty={Math.max(0, totalWagons - (counts.loaded + counts.loading + counts.overload + counts.unfit))}
                        total={totalWagons}
                    />
                    <button
                        type="button"
                        onClick={() => onOpen?.(siding.siding_id)}
                        className="inline-flex items-center gap-1 rounded-md border border-sky-300 bg-sky-50 px-3 py-1.5 text-sm font-medium text-sky-700 hover:bg-sky-100"
                    >
                        View Details
                        <ChevronRight className="h-4 w-4" aria-hidden />
                    </button>
                </div>
            </div>

            {siding.rake && siding.wagons.length > 0 ? (
                <>
                    <div className="mt-4 text-xs font-medium uppercase tracking-wide text-slate-500">
                        Wagon Position{' '}
                        <span className="font-normal normal-case text-slate-400">
                            (Scroll to view all {totalWagons} wagons)
                        </span>
                    </div>
                    <div className="mt-1">
                        <WagonTrainV2
                            wagons={siding.wagons}
                            bulldozerWagonId={bulldozerWagonId}
                            pulseEventId={pulseEventId}
                            entryKey={siding.rake?.id ?? null}
                            showHeatTrail
                            size="mini"
                        />
                    </div>
                </>
            ) : (
                <div className="mt-4 rounded-md bg-slate-50 p-4 text-center text-sm text-slate-500">
                    No active rake at this siding.
                </div>
            )}

            <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                <KpiCell
                    icon={<Package className="h-4 w-4 text-emerald-600" />}
                    label="Total Net Weight"
                    valueNode={
                        kpis ? (
                            <>
                                <CountUpNumber value={kpis.total_loaded_mt} decimals={2} />
                                <span className="ml-1 text-xs opacity-70">MT</span>
                            </>
                        ) : (
                            '—'
                        )
                    }
                />
                <KpiCell
                    icon={<AlertTriangle className="h-4 w-4 text-rose-600" />}
                    label="Over Load"
                    valueNode={
                        kpis ? (
                            <>
                                <CountUpNumber value={kpis.total_overload_mt} decimals={1} />
                                <span className="ml-1 text-xs opacity-70">MT</span>
                            </>
                        ) : (
                            '—'
                        )
                    }
                />
                <KpiCell
                    icon={<AlertTriangle className="h-4 w-4 text-amber-600" />}
                    label="Under Load"
                    valueNode={
                        kpis ? (
                            <>
                                <CountUpNumber value={kpis.total_underload_mt} decimals={0} />
                                <span className="ml-1 text-xs opacity-70">MT</span>
                            </>
                        ) : (
                            '—'
                        )
                    }
                />
                <KpiCell
                    icon={<Clock className="h-4 w-4 text-sky-600" />}
                    label="Rake Loading Time"
                    valueNode={
                        loadingMinutes != null ? formatMinutes(loadingMinutes) : '—'
                    }
                />
                <KpiCell
                    icon={<Clock className="h-4 w-4 text-slate-600" />}
                    label="Rake Placement"
                    valueNode={placementTime}
                />
            </div>
        </motion.section>
    );
}

function CountChips({
    counts,
    empty,
    total,
}: {
    counts: { loaded: number; loading: number; overload: number; unfit: number };
    empty: number;
    total: number;
}) {
    return (
        <div className="hidden flex-wrap items-center gap-2 lg:flex">
            <Chip
                icon={<Package className="h-3.5 w-3.5" />}
                label="Loaded"
                value={counts.loaded}
                tone="emerald"
            />
            <Chip
                icon={<Truck className="h-3.5 w-3.5" />}
                label="Loading"
                value={counts.loading}
                tone="amber"
            />
            <Chip
                icon={<AlertTriangle className="h-3.5 w-3.5" />}
                label="Over Load"
                value={counts.overload}
                tone="rose"
            />
            <Chip
                icon={<SkipForward className="h-3.5 w-3.5" />}
                label="Unfit/Skip"
                value={counts.unfit}
                tone="slate"
            />
            <Chip
                icon={<Box className="h-3.5 w-3.5" />}
                label="Empty"
                value={empty}
                tone="slate"
            />
            <Chip label="Total Wagons" value={total} tone="sky" />
        </div>
    );
}

function Chip({
    icon,
    label,
    value,
    tone,
}: {
    icon?: React.ReactNode;
    label: string;
    value: number;
    tone: 'emerald' | 'amber' | 'rose' | 'slate' | 'sky';
}) {
    const tones: Record<typeof tone, string> = {
        emerald: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        amber: 'bg-amber-50 text-amber-700 ring-amber-200',
        rose: 'bg-rose-50 text-rose-700 ring-rose-200',
        slate: 'bg-slate-50 text-slate-700 ring-slate-200',
        sky: 'bg-sky-50 text-sky-700 ring-sky-200',
    };
    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${tones[tone]}`}
        >
            {icon}
            <span className="text-[10px] uppercase tracking-wide opacity-80">{label}</span>
            <span className="tabular-nums">{value}</span>
        </span>
    );
}

function KpiCell({
    icon,
    label,
    valueNode,
}: {
    icon: React.ReactNode;
    label: string;
    valueNode: React.ReactNode;
}) {
    return (
        <div className="flex items-center gap-2 rounded-md border border-slate-100 bg-slate-50 px-3 py-2">
            <span className="flex h-7 w-7 items-center justify-center rounded-full bg-white shadow-sm">
                {icon}
            </span>
            <div className="min-w-0">
                <div className="text-[10px] font-medium uppercase tracking-wide text-slate-500">
                    {label}
                </div>
                <div className="truncate text-sm font-semibold tabular-nums text-slate-900">
                    {valueNode}
                </div>
            </div>
        </div>
    );
}

function formatMinutes(min: number): string {
    if (min < 60) return `${Math.round(min)} Minutes`;
    const h = Math.floor(min / 60);
    const m = Math.round(min % 60);
    return `${h}h ${m}m`;
}
