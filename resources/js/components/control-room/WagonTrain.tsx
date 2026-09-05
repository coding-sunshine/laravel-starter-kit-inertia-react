import { motion, useReducedMotion } from 'framer-motion';
import type { KeyboardEvent } from 'react';

import type { WagonCard } from '@/components/control-room/types';

export interface WagonTrainProps {
    wagons: WagonCard[];
    activeLoaderWagonIds?: number[];
    showLocomotive?: boolean;
    onWagonClick?: (wagon: WagonCard) => void;
    compact?: boolean;
}

const WAGON_WIDTH = 56;
const WAGON_HEIGHT = 56;
const WAGON_GAP = 4;
const COMPACT_WAGON_WIDTH = 28;
const COMPACT_WAGON_HEIGHT = 36;
const COMPACT_WAGON_GAP = 3;
const LOCOMOTIVE_WIDTH = 80;
const COMPACT_LOCOMOTIVE_WIDTH = 44;
const SVG_VERTICAL_PADDING = 12;

function Locomotive({
    x,
    height,
    compact,
}: {
    x: number;
    height: number;
    compact: boolean;
}) {
    const width = compact ? COMPACT_LOCOMOTIVE_WIDTH : LOCOMOTIVE_WIDTH;
    const bodyY = compact ? 4 : 6;
    const bodyHeight = height - 12;
    const wheelR = compact ? 3 : 5;
    const wheelY = height - wheelR - 1;

    return (
        <g transform={`translate(${x}, 0)`} aria-hidden>
            {/* main body */}
            <rect
                x={0}
                y={bodyY}
                width={width - 10}
                height={bodyHeight}
                rx={4}
                className="fill-slate-700 dark:fill-slate-500"
            />
            {/* nose */}
            <polygon
                points={`${width - 10},${bodyY} ${width},${bodyY + bodyHeight / 2} ${width - 10},${bodyY + bodyHeight}`}
                className="fill-slate-700 dark:fill-slate-500"
            />
            {/* cab window */}
            <rect
                x={6}
                y={bodyY + 3}
                width={compact ? 10 : 18}
                height={compact ? 6 : 10}
                rx={1}
                className="fill-sky-300/80 dark:fill-sky-400/70"
            />
            {!compact && (
                <text
                    x={(width - 10) / 2 + 4}
                    y={bodyY + bodyHeight - 8}
                    textAnchor="middle"
                    className="fill-white text-[8px] font-semibold tracking-wider"
                >
                    ENGINE
                </text>
            )}
            {/* wheels */}
            <circle
                cx={wheelR + 4}
                cy={wheelY}
                r={wheelR}
                className="fill-slate-900 dark:fill-slate-200"
            />
            <circle
                cx={width - 18}
                cy={wheelY}
                r={wheelR}
                className="fill-slate-900 dark:fill-slate-200"
            />
        </g>
    );
}

interface WagonNodeProps {
    wagon: WagonCard;
    x: number;
    width: number;
    height: number;
    compact: boolean;
    isActiveLoader: boolean;
    reducedMotion: boolean;
    onClick?: (wagon: WagonCard) => void;
}

function WagonNode({
    wagon,
    x,
    width,
    height,
    compact,
    isActiveLoader,
    reducedMotion,
    onClick,
}: WagonNodeProps) {
    const loadedPct = (() => {
        if (!wagon.cc_mt || wagon.cc_mt <= 0) {
            return 0;
        }
        const loaded = wagon.loaded_mt ?? 0;
        return Math.max(0, Math.min(1, loaded / wagon.cc_mt));
    })();

    const isOverload = wagon.status === 'overload';
    const isClickable = typeof onClick === 'function';

    const bodyY = 4;
    const bodyHeight = height - (compact ? 8 : 14);
    const wheelR = compact ? 2.5 : 4;
    const wheelY = height - wheelR - 1;

    // coal pile sits above wagon body, scaled by loaded percent
    const coalMaxHeight = compact ? 0 : 10;
    const coalHeight = compact ? 0 : Math.round(coalMaxHeight * loadedPct);
    const coalY = bodyY - coalHeight;
    const coalWidth = width - 8;

    const handleKeyDown = (event: KeyboardEvent<SVGGElement>) => {
        if (!isClickable) return;
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            onClick?.(wagon);
        }
    };

    // Removed motion.g pulse + motion.rect fill animation. Both inline-animate
    // objects re-fired every parent re-render (TimeStatusDonut ticks the parent
    // every 1s) — wagons sat mid-tween at 45% opacity and flickered. Static
    // SVG renders the correct colour every render.
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
            className="outline-none focus-visible:[&>rect]:stroke-sky-500"
        >
            {/* coal pile (full mode only) */}
            {!compact && coalHeight > 0 && (
                <rect
                    x={4}
                    y={coalY}
                    width={coalWidth}
                    height={coalHeight}
                    rx={1}
                    className="fill-slate-800/85 dark:fill-slate-900/85"
                />
            )}

            {/* wagon body */}
            <rect
                x={0}
                y={bodyY}
                width={width}
                height={bodyHeight}
                rx={3}
                fill={wagon.status_color}
                stroke={isActiveLoader ? '#0f172a' : 'transparent'}
                strokeWidth={isActiveLoader ? 1.5 : 0}
                className="dark:[stroke:theme(colors.slate.100)]"
            />

            {/* sequence number — top-left */}
            <text
                x={3}
                y={bodyY + (compact ? 9 : 10)}
                className="fill-white text-[9px] font-bold"
                style={{
                    paintOrder: 'stroke',
                    stroke: 'rgba(0,0,0,0.35)',
                    strokeWidth: 0.6,
                }}
            >
                {wagon.wagon_sequence}
            </text>

            {/* wagon number — bottom (full mode only) */}
            {!compact && wagon.wagon_number && (
                <text
                    x={width / 2}
                    y={bodyY + bodyHeight - 3}
                    textAnchor="middle"
                    className="fill-white text-[7px] font-medium"
                    style={{
                        paintOrder: 'stroke',
                        stroke: 'rgba(0,0,0,0.35)',
                        strokeWidth: 0.5,
                    }}
                >
                    {wagon.wagon_number.length > 8
                        ? wagon.wagon_number.slice(-6)
                        : wagon.wagon_number}
                </text>
            )}

            {/* wheels */}
            <circle
                cx={wheelR + 3}
                cy={wheelY}
                r={wheelR}
                className="fill-slate-900 dark:fill-slate-200"
            />
            <circle
                cx={width - wheelR - 3}
                cy={wheelY}
                r={wheelR}
                className="fill-slate-900 dark:fill-slate-200"
            />
        </g>
    );
}

export function WagonTrain({
    wagons,
    activeLoaderWagonIds = [],
    showLocomotive = true,
    onWagonClick,
    compact = false,
}: WagonTrainProps) {
    const reducedMotion = useReducedMotion() ?? false;

    const sortedWagons = [...wagons].sort(
        (a, b) => a.wagon_sequence - b.wagon_sequence,
    );
    const activeSet = new Set(activeLoaderWagonIds);

    const wagonWidth = compact ? COMPACT_WAGON_WIDTH : WAGON_WIDTH;
    const wagonHeight = compact ? COMPACT_WAGON_HEIGHT : WAGON_HEIGHT;
    const wagonGap = compact ? COMPACT_WAGON_GAP : WAGON_GAP;
    const locomotiveWidth = compact
        ? COMPACT_LOCOMOTIVE_WIDTH
        : LOCOMOTIVE_WIDTH;

    const trainStartX = showLocomotive ? locomotiveWidth + wagonGap * 2 : 0;
    const totalWidth =
        trainStartX +
        sortedWagons.length * wagonWidth +
        Math.max(0, sortedWagons.length - 1) * wagonGap +
        4;
    const totalHeight = wagonHeight + SVG_VERTICAL_PADDING;

    return (
        <div
            className="w-full overflow-x-auto"
            data-pan="control-room-wagon-train"
        >
            <svg
                width={totalWidth}
                height={totalHeight}
                viewBox={`0 0 ${totalWidth} ${totalHeight}`}
                role="img"
                aria-label={`Wagon train with ${sortedWagons.length} wagons`}
                className="block"
            >
                {/* rails */}
                <line
                    x1={0}
                    y1={wagonHeight + 2}
                    x2={totalWidth}
                    y2={wagonHeight + 2}
                    className="stroke-slate-300 dark:stroke-slate-700"
                    strokeWidth={1}
                />
                <line
                    x1={0}
                    y1={wagonHeight + 5}
                    x2={totalWidth}
                    y2={wagonHeight + 5}
                    className="stroke-slate-300 dark:stroke-slate-700"
                    strokeWidth={1}
                />

                {showLocomotive && (
                    <Locomotive x={0} height={wagonHeight} compact={compact} />
                )}

                {sortedWagons.map((wagon, idx) => {
                    const x = trainStartX + idx * (wagonWidth + wagonGap);
                    return (
                        <WagonNode
                            key={wagon.wagon_id}
                            wagon={wagon}
                            x={x}
                            width={wagonWidth}
                            height={wagonHeight}
                            compact={compact}
                            isActiveLoader={activeSet.has(wagon.wagon_id)}
                            reducedMotion={reducedMotion}
                            onClick={onWagonClick}
                        />
                    );
                })}
            </svg>
        </div>
    );
}

export default WagonTrain;
