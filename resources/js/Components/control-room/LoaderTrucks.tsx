import { motion, useReducedMotion } from 'framer-motion';
import { useMemo } from 'react';

import type {
    LoaderActivity,
    WagonCard,
} from '@/components/control-room/types';

export interface LoaderTrucksProps {
    loaders: LoaderActivity[];
    wagons: WagonCard[];
    wagonWidth: number;
    locomotiveOffset?: number;
    gap?: number;
}

const TRUCK_WIDTH = 56;
const TRUCK_HEIGHT = 36;

function formatRelativeMinutes(iso: string | null): string {
    if (!iso) return 'unknown';
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return 'unknown';
    const diffMs = Date.now() - date.getTime();
    const minutes = Math.max(0, Math.round(diffMs / 60_000));
    if (minutes < 1) return 'just now';
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.round(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.round(hours / 24);
    return `${days}d ago`;
}

function TruckGraphic({ name, code }: { name: string; code: string | null }) {
    return (
        <svg
            width={TRUCK_WIDTH}
            height={TRUCK_HEIGHT}
            viewBox={`0 0 ${TRUCK_WIDTH} ${TRUCK_HEIGHT}`}
            role="img"
            aria-label={`Loader ${name}`}
            className="block"
        >
            {/* bucket arm */}
            <rect
                x={4}
                y={4}
                width={3}
                height={14}
                rx={1}
                className="fill-amber-600 dark:fill-amber-400"
            />
            <rect
                x={2}
                y={16}
                width={10}
                height={5}
                rx={1}
                className="fill-amber-600 dark:fill-amber-400"
            />
            {/* cab */}
            <rect
                x={14}
                y={6}
                width={14}
                height={14}
                rx={2}
                className="fill-amber-500 dark:fill-amber-400"
            />
            <rect
                x={16}
                y={8}
                width={6}
                height={5}
                rx={1}
                className="fill-sky-200/90 dark:fill-sky-300/80"
            />
            {/* body */}
            <rect
                x={14}
                y={18}
                width={36}
                height={8}
                rx={2}
                className="fill-amber-600 dark:fill-amber-500"
            />
            {/* code badge */}
            {code && (
                <text
                    x={32}
                    y={24.5}
                    textAnchor="middle"
                    className="fill-white text-[7px] font-bold"
                >
                    {code.slice(0, 4)}
                </text>
            )}
            {/* wheels */}
            <circle
                cx={20}
                cy={28}
                r={4}
                className="fill-slate-900 dark:fill-slate-200"
            />
            <circle
                cx={42}
                cy={28}
                r={4}
                className="fill-slate-900 dark:fill-slate-200"
            />
            <circle
                cx={20}
                cy={28}
                r={1.5}
                className="fill-slate-400 dark:fill-slate-700"
            />
            <circle
                cx={42}
                cy={28}
                r={1.5}
                className="fill-slate-400 dark:fill-slate-700"
            />
        </svg>
    );
}

export function LoaderTrucks({
    loaders,
    wagons,
    wagonWidth,
    locomotiveOffset = 80,
    gap = 4,
}: LoaderTrucksProps) {
    const reducedMotion = useReducedMotion() ?? false;

    const wagonIndexById = useMemo(() => {
        const sorted = [...wagons].sort(
            (a, b) => a.wagon_sequence - b.wagon_sequence,
        );
        const map = new Map<number, number>();
        sorted.forEach((wagon, idx) => map.set(wagon.wagon_id, idx));
        return map;
    }, [wagons]);

    const wagonNumberById = useMemo(() => {
        const map = new Map<number, string>();
        wagons.forEach((wagon) => {
            if (wagon.wagon_number) map.set(wagon.wagon_id, wagon.wagon_number);
        });
        return map;
    }, [wagons]);

    const activeLoaders = loaders.filter(
        (loader) =>
            loader.status === 'active' && loader.current_wagon_id != null,
    );
    const idleLoaders = loaders.filter((loader) => loader.status !== 'active');

    const trainStartX = locomotiveOffset + gap * 2;

    const trackTransition = reducedMotion
        ? { duration: 0 }
        : { type: 'spring' as const, stiffness: 200, damping: 25 };

    return (
        <div className="flex w-full items-start gap-4">
            {/* active loader trucks — positioned beneath wagons */}
            <div className="relative min-h-[88px] flex-1">
                {activeLoaders.map((loader) => {
                    const wagonId = loader.current_wagon_id;
                    if (wagonId == null) return null;
                    const idx = wagonIndexById.get(wagonId);
                    if (idx === undefined) return null;

                    const wagonCenter =
                        trainStartX + idx * (wagonWidth + gap) + wagonWidth / 2;
                    const truckX = wagonCenter - TRUCK_WIDTH / 2;
                    const wagonNumber =
                        loader.current_wagon_number ??
                        wagonNumberById.get(wagonId) ??
                        `#${idx + 1}`;

                    return (
                        <motion.div
                            key={loader.loader_id}
                            layout
                            initial={false}
                            animate={{ x: truckX }}
                            transition={trackTransition}
                            className="absolute top-0 left-0 flex flex-col items-center"
                            style={{ width: TRUCK_WIDTH }}
                        >
                            {/* triangle pointer up at the wagon */}
                            <div
                                aria-hidden
                                className="h-0 w-0 border-r-[6px] border-b-[8px] border-l-[6px] border-r-transparent border-b-slate-700 border-l-transparent dark:border-b-slate-300"
                            />
                            <div className="text-[10px] leading-tight font-semibold text-slate-700 dark:text-slate-200">
                                {wagonNumber}
                            </div>
                            <TruckGraphic
                                name={loader.loader_name}
                                code={loader.loader_code}
                            />
                            <div className="mt-0.5 max-w-[72px] truncate text-center text-[10px] font-medium text-slate-600 dark:text-slate-400">
                                {loader.loader_name}
                            </div>
                        </motion.div>
                    );
                })}
            </div>

            {/* idle loader legend — stacked to the right */}
            {idleLoaders.length > 0 && (
                <ul
                    className="flex w-44 shrink-0 flex-col gap-1.5"
                    aria-label="Idle loaders"
                >
                    {idleLoaders.map((loader) => (
                        <li
                            key={loader.loader_id}
                            className="rounded-md border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-400"
                        >
                            <div className="flex items-center justify-between gap-2">
                                <span className="truncate font-medium text-slate-700 dark:text-slate-300">
                                    {loader.loader_name}
                                </span>
                                {loader.loader_code && (
                                    <span className="shrink-0 rounded bg-slate-200 px-1 font-mono text-[10px] text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                        {loader.loader_code}
                                    </span>
                                )}
                            </div>
                            <div className="mt-0.5 text-[10px]">
                                idle • last active{' '}
                                {formatRelativeMinutes(loader.last_active_at)}
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

export default LoaderTrucks;
