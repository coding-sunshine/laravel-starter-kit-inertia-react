import { Link } from '@inertiajs/react';
import { motion, useReducedMotion } from 'framer-motion';
import { ArrowUpCircle, Bell, Weight } from 'lucide-react';

import { KpiTile } from '@/components/control-room/KpiTile';
import { LoadingProgressDonut } from '@/components/control-room/LoadingProgressDonut';
import type { OverviewSiding } from '@/components/control-room/types';
import { WagonTrain } from '@/components/control-room/WagonTrain';
import { cn } from '@/lib/utils';

interface SidingOverviewCardProps {
    siding: OverviewSiding;
    href: string;
    isFocused?: boolean;
}

function formatHoursAgo(value: string | null): string {
    if (!value) {
        return 'No recent activity';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return 'No recent activity';
    }

    const diffMs = Date.now() - date.getTime();
    if (diffMs < 0) {
        return 'just now';
    }

    const minutes = Math.floor(diffMs / 60000);
    if (minutes < 1) {
        return 'just now';
    }
    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);
    return `${days}d ago`;
}

export function SidingOverviewCard({
    siding,
    href,
    isFocused = false,
}: SidingOverviewCardProps) {
    const prefersReducedMotion = useReducedMotion();
    const loaded = siding.kpis?.total_loaded_mt ?? 0;
    const overload = siding.kpis?.total_overload_mt ?? 0;
    const alertsCount = siding.alerts_count ?? 0;
    const hasProgress =
        siding.loading_progress !== null &&
        siding.loading_progress !== undefined;

    const animateScale = !prefersReducedMotion && isFocused ? 1.01 : 1;

    return (
        <motion.div
            animate={{ scale: animateScale }}
            transition={{
                duration: prefersReducedMotion ? 0 : 0.25,
                ease: 'easeOut',
            }}
            className="relative"
        >
            <Link
                href={href}
                className={cn(
                    'group block rounded-xl border bg-card text-card-foreground shadow-sm transition-colors',
                    'hover:border-primary/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                    isFocused
                        ? 'border-emerald-500 ring-2 ring-emerald-500'
                        : 'border-border',
                )}
            >
                <div className="flex items-start justify-between gap-3 border-b border-border px-4 py-3">
                    <div className="min-w-0">
                        <h3 className="truncate text-base leading-tight font-semibold">
                            {siding.siding_name}
                        </h3>
                        {siding.siding_code ? (
                            <p className="mt-0.5 font-mono text-xs tracking-wide text-muted-foreground uppercase">
                                {siding.siding_code}
                            </p>
                        ) : null}
                    </div>
                    {alertsCount > 0 ? (
                        <span
                            className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-950/50 dark:text-amber-300"
                            aria-label={`${alertsCount} active alerts`}
                        >
                            <Bell className="h-3 w-3" aria-hidden="true" />
                            {alertsCount}
                        </span>
                    ) : null}
                </div>

                <div className="px-4 py-3">
                    {siding.has_active_rake ? (
                        <>
                            <div className="grid grid-cols-2 gap-2">
                                <KpiTile
                                    compact
                                    icon={Weight}
                                    label="Loaded"
                                    value={loaded}
                                    unit="MT"
                                    accentColor="blue"
                                />
                                <KpiTile
                                    compact
                                    icon={ArrowUpCircle}
                                    label="Overload"
                                    value={overload}
                                    unit="MT"
                                    accentColor={overload > 0 ? 'red' : 'gray'}
                                />
                            </div>

                            <div className="mt-3 flex items-center gap-3">
                                <div className="min-w-0 flex-1">
                                    <WagonTrain
                                        compact
                                        wagons={siding.wagons}
                                    />
                                </div>
                                {hasProgress ? (
                                    <div className="shrink-0">
                                        <LoadingProgressDonut
                                            size={64}
                                            strokeWidth={6}
                                            progress={siding.loading_progress!}
                                            showLabel={false}
                                        />
                                    </div>
                                ) : null}
                            </div>
                        </>
                    ) : (
                        <div className="flex min-h-[112px] items-center justify-center px-3 py-6 text-center">
                            <p className="text-sm text-muted-foreground">
                                {siding.last_event_at
                                    ? `No active rake — last activity ${formatHoursAgo(siding.last_event_at)}`
                                    : 'No recent activity'}
                            </p>
                        </div>
                    )}
                </div>
            </Link>
        </motion.div>
    );
}

export default SidingOverviewCard;
