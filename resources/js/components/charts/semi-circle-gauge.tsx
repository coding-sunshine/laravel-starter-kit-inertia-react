'use client';

import { useMemo } from 'react';

const RADIUS = 80;
const STROKE_WIDTH = 16;
const CENTER_X = 100;
const CENTER_Y = 100;

const RED_TRACK = 'rgb(254 202 202)';   // lighter red (track)
const RED_FILL = 'rgb(185 28 28)';      // darker red (fill when below)
const GREEN_TRACK = 'rgb(187 247 208)';
const GREEN_FILL = 'rgb(22 163 74)';
const BLUE_TRACK = 'rgb(191 219 254)';
const BLUE_FILL = 'rgb(30 64 175)';

/**
 * SVG arc. Angles in degrees: 0° = right (cx+r, cy), 90° = bottom, 180° = left, 270° = top.
 * sweep 1 = clockwise. large=0 picks the arc with span ≤ 180°, large=1 the other.
 * PreferShort: when true, use the shorter arc (so fill length matches value; when false, use default.
 */
function describeArc(
    cx: number,
    cy: number,
    r: number,
    startAngleDeg: number,
    endAngleDeg: number,
    sweep: 0 | 1 = 1,
    preferShort = false
): string {
    const toRad = (deg: number) => ((deg % 360) * Math.PI) / 180;
    const start = toRad(startAngleDeg);
    const end = toRad(endAngleDeg);
    const x1 = cx + r * Math.cos(start);
    const y1 = cy + r * Math.sin(start);
    const x2 = cx + r * Math.cos(end);
    const y2 = cy + r * Math.sin(end);
    const span = ((endAngleDeg - startAngleDeg) % 360 + 360) % 360;
    const large = preferShort ? 0 : (span > 180 ? 1 : 0);
    return `M ${x1} ${y1} A ${r} ${r} 0 ${large} ${sweep} ${x2} ${y2}`;
}

interface SemiCircleGaugeProps {
    /** Current value (e.g. stock MT). */
    value: number;
    /** Required value for "one rake" (e.g. 3800 MT). Defines green zone end. */
    required: number;
    /** Optional max for scale; defaults to max(required * 1.2, value). */
    max?: number;
    /** Format for center label. */
    formatValue?: (n: number) => string;
    /** Status label under value: below | ready | above | no_data */
    status?: string;
    /** Siding or title above the gauge. */
    title?: string;
    /** Optional palette: [redTrack, redFill, greenTrack, greenFill, blueTrack, blueFill] */
    colors?: {
        redTrack: string;
        redFill: string;
        greenTrack: string;
        greenFill: string;
        blueTrack: string;
        blueFill: string;
    };
    className?: string;
}

/** Semi-circle gauge (speedometer): arc on TOP, 0 at left, max at right. */
export function SemiCircleGauge({
    value,
    required,
    max: maxProp,
    formatValue = (n) => `${n.toLocaleString()} MT`,
    status = 'no_data',
    title,
    colors: customColors,
    className,
}: SemiCircleGaugeProps) {
    const R = customColors?.redTrack ?? RED_TRACK;
    const RF = customColors?.redFill ?? RED_FILL;
    const G = customColors?.greenTrack ?? GREEN_TRACK;
    const GF = customColors?.greenFill ?? GREEN_FILL;
    const B = customColors?.blueTrack ?? BLUE_TRACK;
    const BF = customColors?.blueFill ?? BLUE_FILL;
    // SVG: 0°=right, 90°=bottom, 180°=left, 270°=top. Arc = top half: left(180°) → top(270°) → right(0°), sweep 0.
    const max = useMemo(() => {
        const m = maxProp ?? Math.max(required * 1.5, value, required);
        return m <= 0 ? 1 : m;
    }, [required, value, maxProp]);

    const valueRatio = Math.min(1, value / max);
    const fillAngle = valueRatio * 180;
    // Fill from left (180°) toward right (0°): angle = 180 - fillAngle

    const statusColor =
        status === 'below'
            ? 'text-red-500'
            : status === 'ready'
              ? 'text-green-500'
              : status === 'above'
                ? 'text-blue-500'
                : 'text-muted-foreground';

    const statusLabel =
        status === 'below'
            ? 'Below'
            : status === 'ready'
              ? 'READY'
              : status === 'above'
                ? 'Above'
                : 'No data';

    return (
        <div className={`flex flex-col items-center ${className ?? ''}`}>
            {title && (
                <div className="mb-1 text-center text-sm font-medium text-foreground">{title}</div>
            )}
            <svg
                viewBox="0 0 200 120"
                className="h-auto w-full max-w-[200px]"
                aria-hidden
            >
                {/* Top half: left(180°) → top(270°) → right(0°). Segments use default (short arc = 60° each on top). */}
                <path
                    d={describeArc(CENTER_X, CENTER_Y, RADIUS, 180, 240, 1, false)}
                    fill="none"
                    stroke={R}
                    strokeWidth={STROKE_WIDTH}
                    strokeLinecap="round"
                />
                <path
                    d={describeArc(CENTER_X, CENTER_Y, RADIUS, 240, 300, 1, false)}
                    fill="none"
                    stroke={G}
                    strokeWidth={STROKE_WIDTH}
                    strokeLinecap="round"
                />
                <path
                    d={describeArc(CENTER_X, CENTER_Y, RADIUS, 300, 360, 1, false)}
                    fill="none"
                    stroke={B}
                    strokeWidth={STROKE_WIDTH}
                    strokeLinecap="round"
                />
                {/* Filled portion: short arc so 63 MT draws a tiny segment, not the whole circle */}
                {value > 0 && fillAngle > 0 && (
                    <path
                        d={describeArc(CENTER_X, CENTER_Y, RADIUS - 2, 180, 180 - fillAngle, 1, true)}
                        fill="none"
                        stroke={
                            status === 'below'
                                ? RF
                                : status === 'ready'
                                  ? GF
                                  : status === 'above'
                                    ? BF
                                    : 'currentColor'
                        }
                        strokeWidth={STROKE_WIDTH - 4}
                        strokeLinecap="round"
                    />
                )}
            </svg>
            <div className="mt-1 text-center">
                <div className="text-lg font-bold tabular-nums">{formatValue(value)}</div>
                <div className={`text-xs font-medium ${statusColor}`}>{statusLabel}</div>
                <div className="text-xs text-muted-foreground">Required: {formatValue(required)}</div>
            </div>
        </div>
    );
}
