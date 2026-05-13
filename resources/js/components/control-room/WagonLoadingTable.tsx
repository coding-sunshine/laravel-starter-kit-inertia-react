import { motion, useReducedMotion } from 'framer-motion';
import { useEffect, useMemo, useRef, useState } from 'react';

import { SourceBadge } from '@/components/control-room/SourceBadge';
import type {
    LoaderActivity,
    WagonCard,
} from '@/components/control-room/types';
import { cn } from '@/lib/utils';

interface WagonLoadingTableProps {
    wagons: WagonCard[];
    loaders: LoaderActivity[];
    maxRows?: number;
}

const FRESH_DURATION_MS = 1500;

function isToday(date: Date): boolean {
    const now = new Date();
    return (
        date.getFullYear() === now.getFullYear() &&
        date.getMonth() === now.getMonth() &&
        date.getDate() === now.getDate()
    );
}

function formatLastUpdated(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    if (isToday(date)) {
        return date.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    return `${date.toLocaleDateString([], { month: 'short', day: 'numeric' })} ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
}

function formatNumber(value: number | null, fractionDigits = 2): string {
    if (value === null || value === undefined || Number.isNaN(value)) {
        return '—';
    }

    return value.toLocaleString(undefined, {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
    });
}

function valueOrDash(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
}

export function WagonLoadingTable({
    wagons,
    loaders,
    maxRows,
}: WagonLoadingTableProps) {
    const prefersReducedMotion = useReducedMotion();
    const [freshIds, setFreshIds] = useState<Set<number>>(() => new Set());
    const previousMapRef = useRef<Map<number, string | null>>(new Map());
    const isInitialRef = useRef(true);

    const visibleWagons = useMemo(
        () => (typeof maxRows === 'number' ? wagons.slice(0, maxRows) : wagons),
        [wagons, maxRows],
    );

    const loaderNameById = useMemo(() => {
        const map = new Map<number, string>();
        loaders.forEach((loader) => {
            map.set(loader.loader_id, loader.loader_name);
        });
        return map;
    }, [loaders]);

    useEffect(() => {
        if (prefersReducedMotion) {
            previousMapRef.current = new Map(
                visibleWagons.map((wagon) => [
                    wagon.wagon_id,
                    wagon.last_updated_at,
                ]),
            );
            isInitialRef.current = false;
            return;
        }

        const previous = previousMapRef.current;
        const changed: number[] = [];

        if (!isInitialRef.current) {
            visibleWagons.forEach((wagon) => {
                const prior = previous.get(wagon.wagon_id);
                const hadEntry = previous.has(wagon.wagon_id);

                if (!hadEntry) {
                    if (wagon.last_updated_at) {
                        changed.push(wagon.wagon_id);
                    }
                    return;
                }

                if (prior !== wagon.last_updated_at) {
                    changed.push(wagon.wagon_id);
                }
            });
        }

        previousMapRef.current = new Map(
            visibleWagons.map((wagon) => [
                wagon.wagon_id,
                wagon.last_updated_at,
            ]),
        );

        if (isInitialRef.current) {
            isInitialRef.current = false;
            return;
        }

        if (changed.length === 0) {
            return;
        }

        setFreshIds((current) => {
            const next = new Set(current);
            changed.forEach((id) => next.add(id));
            return next;
        });

        const timer = window.setTimeout(() => {
            setFreshIds((current) => {
                const next = new Set(current);
                changed.forEach((id) => next.delete(id));
                return next;
            });
        }, FRESH_DURATION_MS);

        return () => {
            window.clearTimeout(timer);
        };
    }, [visibleWagons, prefersReducedMotion]);

    return (
        <div className="overflow-hidden rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div className="max-h-[480px] overflow-auto">
                <table className="w-full text-sm">
                    <thead className="sticky top-0 z-10 bg-muted/60 backdrop-blur supports-[backdrop-filter]:bg-muted/40">
                        <tr className="text-left text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            <th className="px-3 py-2.5">Wagon No.</th>
                            <th className="px-3 py-2.5">Type</th>
                            <th className="px-3 py-2.5 text-right">CC (MT)</th>
                            <th className="px-3 py-2.5 text-right">
                                Net Weight (MT)
                            </th>
                            <th className="px-3 py-2.5">Status</th>
                            <th className="px-3 py-2.5">Source</th>
                            <th className="px-3 py-2.5">Loader</th>
                            <th className="px-3 py-2.5">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        {visibleWagons.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={8}
                                    className="px-3 py-8 text-center text-sm text-muted-foreground"
                                >
                                    No wagons to display.
                                </td>
                            </tr>
                        ) : (
                            visibleWagons.map((wagon) => {
                                const loaderName =
                                    wagon.loader_id !== null &&
                                    loaderNameById.has(wagon.loader_id)
                                        ? loaderNameById.get(wagon.loader_id)!
                                        : '—';

                                const isFresh =
                                    !prefersReducedMotion &&
                                    freshIds.has(wagon.wagon_id);

                                return (
                                    <motion.tr
                                        key={wagon.wagon_id}
                                        layout={!prefersReducedMotion}
                                        initial={false}
                                        animate={{
                                            backgroundColor: isFresh
                                                ? 'rgba(120, 113, 108, 0.18)'
                                                : 'rgba(120, 113, 108, 0)',
                                        }}
                                        transition={{
                                            duration: isFresh ? 1.5 : 0.3,
                                            ease: 'easeOut',
                                        }}
                                        className={cn(
                                            'border-t border-border align-middle',
                                            'hover:bg-muted/30',
                                        )}
                                    >
                                        <td className="px-3 py-2 font-mono text-xs">
                                            {valueOrDash(wagon.wagon_number)}
                                        </td>
                                        <td className="px-3 py-2 text-foreground">
                                            {valueOrDash(wagon.wagon_type)}
                                        </td>
                                        <td className="px-3 py-2 text-right tabular-nums">
                                            {formatNumber(wagon.cc_mt)}
                                        </td>
                                        <td className="px-3 py-2 text-right tabular-nums">
                                            {formatNumber(wagon.loaded_mt)}
                                        </td>
                                        <td className="px-3 py-2">
                                            <span className="inline-flex items-center gap-2">
                                                <span
                                                    className="h-2.5 w-2.5 rounded-full"
                                                    style={{
                                                        backgroundColor:
                                                            wagon.status_color,
                                                    }}
                                                    aria-hidden="true"
                                                />
                                                <span className="text-foreground">
                                                    {wagon.status_label}
                                                </span>
                                            </span>
                                        </td>
                                        <td className="px-3 py-2">
                                            <SourceBadge
                                                source={wagon.weight_source}
                                                override={wagon.loadrite_override}
                                                hasWeight={wagon.loaded_mt !== null && wagon.loaded_mt > 0}
                                            />
                                        </td>
                                        <td className="px-3 py-2 text-foreground">
                                            {loaderName}
                                        </td>
                                        <td className="px-3 py-2 text-muted-foreground tabular-nums">
                                            {formatLastUpdated(
                                                wagon.last_updated_at,
                                            )}
                                        </td>
                                    </motion.tr>
                                );
                            })
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

export default WagonLoadingTable;
