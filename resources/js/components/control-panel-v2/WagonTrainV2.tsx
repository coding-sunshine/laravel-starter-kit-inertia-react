import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import type { KeyboardEvent } from 'react';
import { useMemo } from 'react';

import type { WagonCard } from '@/components/control-room/types';

export interface WagonTrainV2Props {
    wagons: WagonCard[];
    bulldozerWagonId?: number | null;
    pulseEventId?: string | null;
    onWagonClick?: (wagon: WagonCard) => void;
    size?: 'mini' | 'full';
    /** When this key changes the locomotive replays its entry animation. */
    entryKey?: string | number | null;
    /** Draws a heat-trail under the wagon strip indicating MT per wagon. */
    showHeatTrail?: boolean;
}

interface TrainDimensions {
    wagon: { w: number; h: number; gap: number };
    loco: number;
    coalMax: number;
    font: number;
    verticalPad: number;
}

const DIM: Record<'mini' | 'full', TrainDimensions> = {
    mini: {
        wagon: { w: 30, h: 40, gap: 3 },
        loco: 50,
        coalMax: 6,
        font: 8,
        verticalPad: 28,
    },
    full: {
        wagon: { w: 56, h: 72, gap: 5 },
        loco: 90,
        coalMax: 14,
        font: 10,
        verticalPad: 36,
    },
};

export function WagonTrainV2({
    wagons,
    bulldozerWagonId = null,
    pulseEventId = null,
    onWagonClick,
    size = 'mini',
    entryKey = null,
    showHeatTrail = false,
}: WagonTrainV2Props) {
    const reducedMotion = useReducedMotion() ?? false;
    const dim = DIM[size];

    const sortedWagons = useMemo(
        () => [...wagons].sort((a, b) => a.wagon_sequence - b.wagon_sequence),
        [wagons],
    );

    const trainStartX = dim.loco + dim.wagon.gap * 2;
    const totalWidth =
        trainStartX +
        sortedWagons.length * dim.wagon.w +
        Math.max(0, sortedWagons.length - 1) * dim.wagon.gap +
        8;
    const totalHeight = dim.wagon.h + dim.verticalPad;

    const bulldozerX = useMemo(() => {
        if (bulldozerWagonId == null) return null;
        const idx = sortedWagons.findIndex(
            (w) => w.wagon_id === bulldozerWagonId,
        );
        if (idx < 0) return null;
        return trainStartX + idx * (dim.wagon.w + dim.wagon.gap);
    }, [bulldozerWagonId, sortedWagons, trainStartX, dim.wagon.w, dim.wagon.gap]);

    return (
        <div className="w-full overflow-x-auto">
            <svg
                width={totalWidth}
                height={totalHeight}
                viewBox={`0 0 ${totalWidth} ${totalHeight}`}
                role="img"
                aria-label={`Wagon train with ${sortedWagons.length} wagons`}
                className="block"
            >
                <Rails y={dim.wagon.h + 2} width={totalWidth} />

                {showHeatTrail && (
                    <HeatTrail
                        wagons={sortedWagons}
                        startX={trainStartX}
                        wagonWidth={dim.wagon.w}
                        wagonGap={dim.wagon.gap}
                        baselineY={dim.wagon.h + 8}
                        size={size}
                    />
                )}

                <Locomotive
                    x={0}
                    height={dim.wagon.h}
                    width={dim.loco}
                    size={size}
                    entryKey={entryKey}
                    reducedMotion={reducedMotion}
                />

                {sortedWagons.map((wagon, idx) => {
                    const x = trainStartX + idx * (dim.wagon.w + dim.wagon.gap);
                    return (
                        <WagonNode
                            key={wagon.wagon_id}
                            wagon={wagon}
                            x={x}
                            dim={dim}
                            size={size}
                            reducedMotion={reducedMotion}
                            onClick={onWagonClick}
                        />
                    );
                })}

                {bulldozerX !== null && (
                    <Bulldozer
                        x={bulldozerX}
                        y={dim.wagon.h + 6}
                        width={dim.wagon.w}
                        size={size}
                        reducedMotion={reducedMotion}
                        pulseEventId={pulseEventId}
                    />
                )}
            </svg>
        </div>
    );
}

function Rails({ y, width }: { y: number; width: number }) {
    return (
        <>
            <line x1={0} y1={y} x2={width} y2={y} className="stroke-slate-300" strokeWidth={1} />
            <line
                x1={0}
                y1={y + 3}
                x2={width}
                y2={y + 3}
                className="stroke-slate-300"
                strokeWidth={1}
            />
        </>
    );
}

function Locomotive({
    x,
    height,
    width,
    size,
    entryKey,
    reducedMotion,
}: {
    x: number;
    height: number;
    width: number;
    size: 'mini' | 'full';
    entryKey: string | number | null;
    reducedMotion: boolean;
}) {
    const compact = size === 'mini';
    const bodyY = compact ? 4 : 8;
    const bodyHeight = height - 12;
    const wheelR = compact ? 3 : 6;
    const wheelY = height - wheelR - 1;
    const noseW = compact ? 10 : 16;
    const stackW = compact ? 5 : 10;
    const stackH = compact ? 4 : 10;

    const slideTransition = reducedMotion
        ? { duration: 0 }
        : { type: 'spring' as const, stiffness: 90, damping: 18 };

    return (
        <motion.g
            key={entryKey ?? 'static-loco'}
            transform={`translate(${x}, 0)`}
            initial={reducedMotion ? false : { x: -width - 40, opacity: 0 }}
            animate={{ x, opacity: 1 }}
            transition={slideTransition}
            aria-hidden
        >
            <AnimatePresence>
                {!reducedMotion &&
                    entryKey != null &&
                    [0, 1, 2].map((i) => (
                        <motion.circle
                            key={`puff-${entryKey}-${i}`}
                            cx={stackW + 4 + stackW / 2}
                            cy={bodyY - stackH - 2}
                            r={2 + i}
                            className="fill-slate-400"
                            initial={{ opacity: 0.7, y: 0, x: 0, scale: 0.4 }}
                            animate={{
                                opacity: 0,
                                y: -18 - i * 6,
                                x: -4 - i * 3,
                                scale: 1 + i * 0.4,
                            }}
                            exit={{ opacity: 0 }}
                            transition={{
                                duration: 1.2 + i * 0.2,
                                delay: i * 0.2,
                                ease: 'easeOut',
                            }}
                        />
                    ))}
            </AnimatePresence>

            <rect
                x={stackW + 2}
                y={bodyY - stackH}
                width={stackW}
                height={stackH}
                rx={1}
                className="fill-slate-800"
            />
            <rect
                x={0}
                y={bodyY}
                width={width - noseW}
                height={bodyHeight}
                rx={4}
                className="fill-sky-700"
            />
            <polygon
                points={`${width - noseW},${bodyY} ${width},${bodyY + bodyHeight / 2} ${width - noseW},${bodyY + bodyHeight}`}
                className="fill-sky-700"
            />
            <rect
                x={6}
                y={bodyY + 3}
                width={compact ? 14 : 24}
                height={compact ? 8 : 14}
                rx={1}
                className="fill-sky-200"
            />
            {!compact && (
                <text
                    x={(width - noseW) / 2 + 2}
                    y={bodyY + bodyHeight - 10}
                    textAnchor="middle"
                    className="fill-white text-[9px] font-semibold tracking-wider"
                >
                    ENGINE
                </text>
            )}
            <circle cx={wheelR + 4} cy={wheelY} r={wheelR} className="fill-slate-900" />
            <circle cx={width - noseW - wheelR - 2} cy={wheelY} r={wheelR} className="fill-slate-900" />
        </motion.g>
    );
}

function HeatTrail({
    wagons,
    startX,
    wagonWidth,
    wagonGap,
    baselineY,
    size,
}: {
    wagons: WagonCard[];
    startX: number;
    wagonWidth: number;
    wagonGap: number;
    baselineY: number;
    size: 'mini' | 'full';
}) {
    const bandHeight = size === 'mini' ? 4 : 8;
    const maxCc = wagons.reduce((acc, w) => Math.max(acc, w.cc_mt ?? 0), 0) || 1;

    return (
        <g aria-hidden>
            {wagons.map((w, idx) => {
                const loaded = w.loaded_mt ?? 0;
                const pct = Math.max(0, Math.min(1, loaded / maxCc));
                const color =
                    pct === 0
                        ? '#E2E8F0'
                        : pct > 1.02
                          ? '#EF4444'
                          : pct >= 0.95
                            ? '#10B981'
                            : pct >= 0.6
                              ? '#34D399'
                              : pct >= 0.3
                                ? '#FCD34D'
                                : '#F59E0B';
                const x = startX + idx * (wagonWidth + wagonGap);
                return (
                    <motion.rect
                        key={w.wagon_id}
                        x={x}
                        y={baselineY}
                        width={wagonWidth}
                        height={bandHeight}
                        rx={1}
                        fill={color}
                        initial={false}
                        animate={{ fill: color, opacity: 0.3 + 0.7 * pct }}
                        transition={{ duration: 0.4 }}
                    />
                );
            })}
        </g>
    );
}

interface WagonNodeProps {
    wagon: WagonCard;
    x: number;
    dim: TrainDimensions;
    size: 'mini' | 'full';
    reducedMotion: boolean;
    onClick?: (wagon: WagonCard) => void;
}

function WagonNode({ wagon, x, dim, size, reducedMotion, onClick }: WagonNodeProps) {
    const isClickable = typeof onClick === 'function';
    const compact = size === 'mini';

    const loadedPct = (() => {
        if (!wagon.cc_mt || wagon.cc_mt <= 0) return 0;
        const loaded = wagon.loaded_mt ?? 0;
        return Math.max(0, Math.min(1, loaded / wagon.cc_mt));
    })();

    const bodyY = 6;
    const bodyHeight = dim.wagon.h - (compact ? 12 : 20);
    const wheelR = compact ? 3 : 5;
    const wheelY = dim.wagon.h - wheelR - 1;

    const coalMax = dim.coalMax;
    const coalHeight = Math.round(coalMax * loadedPct);
    const coalY = bodyY - coalHeight;
    const coalWidth = dim.wagon.w - 8;

    const handleKeyDown = (event: KeyboardEvent<SVGGElement>) => {
        if (!isClickable) return;
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            onClick?.(wagon);
        }
    };

    const fillTransition = reducedMotion
        ? { duration: 0 }
        : { type: 'spring' as const, stiffness: 140, damping: 18 };

    return (
        <g
            transform={`translate(${x}, 0)`}
            tabIndex={isClickable ? 0 : -1}
            role={isClickable ? 'button' : undefined}
            aria-label={
                wagon.wagon_number
                    ? `Wagon ${wagon.wagon_sequence} (${wagon.wagon_number}) — ${wagon.status_label}`
                    : `Wagon ${wagon.wagon_sequence} — ${wagon.status_label}`
            }
            onKeyDown={handleKeyDown}
            onClick={isClickable ? () => onClick?.(wagon) : undefined}
            style={{ cursor: isClickable ? 'pointer' : 'default' }}
            className="outline-none focus-visible:[&>rect.body]:stroke-sky-500"
        >
            <motion.rect
                x={4}
                width={coalWidth}
                rx={1}
                className="fill-slate-800"
                initial={false}
                animate={{ y: coalY, height: coalHeight }}
                transition={fillTransition}
            />

            <motion.rect
                className="body"
                x={0}
                y={bodyY}
                width={dim.wagon.w}
                height={bodyHeight}
                rx={3}
                fill={wagon.status_color}
                initial={false}
                animate={{ fill: wagon.status_color }}
                transition={{ duration: reducedMotion ? 0 : 0.35 }}
            />

            <text
                x={3}
                y={bodyY + (compact ? 9 : 11)}
                className="fill-white font-bold"
                style={{
                    fontSize: dim.font,
                    paintOrder: 'stroke',
                    stroke: 'rgba(0,0,0,0.35)',
                    strokeWidth: 0.6,
                }}
            >
                {String(wagon.wagon_sequence).padStart(2, '0')}
            </text>

            {!compact && wagon.wagon_number && (
                <text
                    x={dim.wagon.w / 2}
                    y={bodyY + bodyHeight - 4}
                    textAnchor="middle"
                    className="fill-white text-[8px] font-medium"
                    style={{
                        paintOrder: 'stroke',
                        stroke: 'rgba(0,0,0,0.3)',
                        strokeWidth: 0.4,
                    }}
                >
                    {wagon.wagon_number.length > 8
                        ? wagon.wagon_number.slice(-6)
                        : wagon.wagon_number}
                </text>
            )}

            <circle cx={wheelR + 3} cy={wheelY} r={wheelR} className="fill-slate-900" />
            <circle
                cx={dim.wagon.w - wheelR - 3}
                cy={wheelY}
                r={wheelR}
                className="fill-slate-900"
            />
        </g>
    );
}

function Bulldozer({
    x,
    y,
    width,
    size,
    reducedMotion,
    pulseEventId,
}: {
    x: number;
    y: number;
    width: number;
    size: 'mini' | 'full';
    reducedMotion: boolean;
    pulseEventId: string | null;
}) {
    const compact = size === 'mini';
    const w = compact ? 22 : 36;
    const h = compact ? 16 : 26;

    const moveTransition = reducedMotion
        ? { duration: 0 }
        : { type: 'spring' as const, stiffness: 90, damping: 16 };

    return (
        <motion.g
            initial={false}
            animate={{ x: x + width / 2 - w / 2, y }}
            transition={moveTransition}
        >
            <rect width={w} height={h - 4} y={2} rx={2} className="fill-amber-500" />
            <polygon
                points={`${w},${h - 6} ${w + 4},${h - 2} ${w},${h - 2}`}
                className="fill-amber-600"
            />
            <rect width={5} y={0} height={3} x={w / 2 - 2} className="fill-amber-700" />
            <circle cx={4} cy={h - 2} r={3} className="fill-slate-900" />
            <circle cx={w / 2} cy={h - 2} r={3} className="fill-slate-900" />
            <circle cx={w - 4} cy={h - 2} r={3} className="fill-slate-900" />

            <AnimatePresence>
                {pulseEventId && (
                    <motion.circle
                        key={pulseEventId}
                        cx={w / 2}
                        cy={-4}
                        r={3}
                        className="fill-amber-400"
                        initial={{ opacity: 0.8, scale: 0.4 }}
                        animate={{ opacity: 0, scale: 2.6 }}
                        exit={{ opacity: 0 }}
                        transition={{ duration: reducedMotion ? 0 : 0.6, ease: 'easeOut' }}
                    />
                )}
            </AnimatePresence>
        </motion.g>
    );
}

export default WagonTrainV2;
