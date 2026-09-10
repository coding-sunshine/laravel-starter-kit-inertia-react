import React, { useEffect, useMemo, useState } from 'react';

const CX = 110;
const CY = 120;
const OUTER_R = 90;
const INNER_R = 62;
const NEEDLE_R = 80;

const ZONES = [
    { start: 0, end: 30, bg: '#FEE2E2', fill: '#EF4444' },
    { start: 30, end: 60, bg: '#FFEDD5', fill: '#F97316' },
    { start: 60, end: 80, bg: '#FEF9C3', fill: '#F59E0B' },
    { start: 80, end: 100, bg: '#DCFCE7', fill: '#10B981' },
];

function pctToAngle(pct) {
    const angle = (pct / 100) * Math.PI;
    return angle;
}

function polar(cx, cy, r, pct) {
    const angle = pctToAngle(pct);
    const rad = Math.PI - angle;
    const x = cx + r * Math.cos(rad);
    const y = cy - r * Math.sin(rad);
    return { x, y };
}

function arcPath(startPct, endPct, outerR, innerR) {
    const sOuter = polar(CX, CY, outerR, startPct);
    const eOuter = polar(CX, CY, outerR, endPct);
    const sInner = polar(CX, CY, innerR, endPct);
    const eInner = polar(CX, CY, innerR, startPct);
    const largeArc = endPct - startPct > 50 ? 1 : 0;

    return [
        `M ${sOuter.x} ${sOuter.y}`,
        `A ${outerR} ${outerR} 0 ${largeArc} 1 ${eOuter.x} ${eOuter.y}`,
        `L ${sInner.x} ${sInner.y}`,
        `A ${innerR} ${innerR} 0 ${largeArc} 0 ${eInner.x} ${eInner.y}`,
        'Z',
    ].join(' ');
}

function getStatus(current, required) {
    if (!current || current === 0) {
        return { label: 'No Data', color: '#9CA3AF', pct: 0 };
    }
    if (!required || required <= 0) {
        return { label: 'No Data', color: '#9CA3AF', pct: 0 };
    }

    const rawPct = (current / required) * 100;
    const pct = Math.max(0, Math.min(rawPct, 150));

    if (pct >= 100) {
        return { label: 'Sufficient', color: '#10B981', pct };
    }
    if (pct >= 60) {
        return { label: 'Moderate', color: '#F59E0B', pct };
    }
    if (pct >= 30) {
        return { label: 'Low Stock', color: '#F97316', pct };
    }
    return { label: 'Critical', color: '#EF4444', pct };
}

function fmt(n) {
    if (!n || n === 0) {
        return '0 MT';
    }
    if (n >= 1000) {
        return `${(n / 1000).toFixed(1)}K MT`;
    }
    return `${n} MT`;
}

function statusBadgeStyle(color) {
    return {
        display: 'inline-block',
        fontSize: 11,
        fontWeight: 700,
        color,
        backgroundColor: `${color}18`,
        borderRadius: 999,
        padding: '3px 12px',
        marginTop: 4,
    };
}

function Gauge({ siding }) {
    const { name, current, required } = siding;
    const [animated, setAnimated] = useState(false);

    const { label, color, pct } = useMemo(() => getStatus(current, required), [current, required]);

    useEffect(() => {
        const t = setTimeout(() => setAnimated(true), 300);
        return () => clearTimeout(t);
    }, []);

    const displayedPct = animated ? pct : 0;
    const angle = Math.PI - (Math.max(0, Math.min(displayedPct, 100)) / 100) * Math.PI;
    const needleTip = {
        x: CX + NEEDLE_R * Math.cos(angle),
        y: CY - NEEDLE_R * Math.sin(angle),
    };

    const ticks = [0, 25, 50, 75, 100];

    return (
        <div
            style={{
                backgroundColor: '#ffffff',
                borderRadius: 20,
                padding: '24px 20px 20px',
                boxShadow: '0 1px 3px rgba(0,0,0,0.08), 0 4px 20px rgba(0,0,0,0.06)',
                flex: 1,
                textAlign: 'center',
                transition: 'transform 0.2s, box-shadow 0.2s',
            }}
            onMouseEnter={(e) => {
                e.currentTarget.style.transform = 'translateY(-3px)';
                e.currentTarget.style.boxShadow = '0 8px 30px rgba(0,0,0,0.12)';
            }}
            onMouseLeave={(e) => {
                e.currentTarget.style.transform = 'translateY(0px)';
                e.currentTarget.style.boxShadow = '0 1px 3px rgba(0,0,0,0.08), 0 4px 20px rgba(0,0,0,0.06)';
            }}
        >
            <div
                style={{
                    fontSize: 13,
                    fontWeight: 600,
                    color: '#374151',
                    marginBottom: 12,
                }}
            >
                {name}
            </div>

            <svg width={220} height={130} viewBox="0 0 220 130">
                <defs>
                    <filter id="speedometer-needle-shadow" x="-50%" y="-50%" width="200%" height="200%">
                        <feDropShadow dx="0" dy="1" stdDeviation="2" floodOpacity="0.25" />
                    </filter>
                </defs>

                {/* Background zones */}
                {ZONES.map((z) => (
                    <path
                        key={`bg-${z.start}-${z.end}`}
                        d={arcPath(z.start, z.end, OUTER_R, INNER_R)}
                        fill={z.bg}
                    />
                ))}

                {/* Active fill zones */}
                {ZONES.map((z) => {
                    const start = z.start;
                    const end = z.end;
                    const capped = Math.max(0, Math.min(displayedPct, 100));
                    const activeEnd = Math.max(start, Math.min(capped, end));
                    if (activeEnd <= start) {
                        return null;
                    }
                    return (
                        <path
                            key={`fill-${z.start}-${z.end}`}
                            d={arcPath(start, activeEnd, OUTER_R, INNER_R)}
                            fill={z.fill}
                            style={{
                                opacity: animated ? 1 : 0,
                                transition: 'opacity 0.3s',
                            }}
                        />
                    );
                })}

                {/* Ticks and labels */}
                {ticks.map((t) => {
                    const inner = polar(CX, CY, OUTER_R + 5, t);
                    const outer = polar(CX, CY, OUTER_R + 12, t);
                    const labelPos = polar(CX, CY, OUTER_R + 22, t);
                    const text =
                        t === 0 ? '0' : t === 100 ? 'MAX' : `${t}%`;
                    return (
                        <g key={`tick-${t}`}>
                            <line
                                x1={inner.x}
                                y1={inner.y}
                                x2={outer.x}
                                y2={outer.y}
                                stroke="#D1D5DB"
                                strokeWidth="1"
                            />
                            <text
                                x={labelPos.x}
                                y={labelPos.y}
                                textAnchor="middle"
                                dominantBaseline="middle"
                                style={{ fontSize: 9, fill: '#9CA3AF' }}
                            >
                                {text}
                            </text>
                        </g>
                    );
                })}

                {/* Needle */}
                <g filter="url(#speedometer-needle-shadow)">
                    <line
                        x1={CX}
                        y1={CY}
                        x2={needleTip.x}
                        y2={needleTip.y}
                        stroke="#1F2937"
                        strokeWidth={3}
                        strokeLinecap="round"
                    />
                    <circle cx={CX} cy={CY} r={8} fill="#1F2937" />
                    <circle cx={CX} cy={CY} r={4} fill="#F9FAFB" />
                </g>
            </svg>

            <div
                style={{
                    marginTop: 8,
                }}
            >
                <div
                    style={{
                        fontSize: 28,
                        fontWeight: 900,
                        color: '#111827',
                        fontVariantNumeric: 'tabular-nums',
                        lineHeight: 1.1,
                    }}
                >
                    {current === 0 ? '—' : fmt(current)}
                </div>
                <div style={statusBadgeStyle(color)}>{label}</div>
                <div
                    style={{
                        fontSize: 11,
                        color: '#9CA3AF',
                        marginTop: 6,
                    }}
                >
                    Required: {fmt(required).replace(' MT', ' MT')}
                </div>
            </div>
        </div>
    );
}

export default function SpeedometerGauge({ sidings, title = 'Stock vs requirement', subtitle = 'Minimum 3,800 MT per rake — side-wise' }) {
    const hasData = Array.isArray(sidings) && sidings.length > 0;

    return (
        <div
            style={{
                backgroundColor: '#ffffff',
                borderRadius: 20,
                padding: '24px 28px',
                boxShadow: '0 1px 3px rgba(0,0,0,0.08), 0 4px 20px rgba(0,0,0,0.06)',
            }}
        >
            <div
                style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    marginBottom: 16,
                }}
            >
                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                    <div
                        style={{
                            width: 36,
                            height: 36,
                            borderRadius: 10,
                            backgroundColor: '#F3F4F6',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            fontSize: 18,
                        }}
                    >
                        ⚡
                    </div>
                    <div>
                        <div
                            style={{
                                fontSize: 15,
                                fontWeight: 700,
                                color: '#111827',
                            }}
                        >
                            {title}
                        </div>
                        <div
                            style={{
                                fontSize: 12,
                                color: '#9CA3AF',
                                marginTop: 2,
                            }}
                        >
                            {subtitle}
                        </div>
                    </div>
                </div>
            </div>

            {hasData ? (
                <div
                    style={{
                        display: 'flex',
                        gap: 20,
                        marginTop: 12,
                        flexWrap: 'wrap',
                    }}
                >
                    {sidings.map((siding) => (
                        <Gauge key={siding.name} siding={siding} />
                    ))}
                </div>
            ) : (
                <div
                    style={{
                        marginTop: 24,
                        paddingTop: 32,
                        paddingBottom: 32,
                        textAlign: 'center',
                        fontSize: 13,
                        color: '#9CA3AF',
                    }}
                >
                    No stock data.
                </div>
            )}

            <div
                style={{
                    display: 'flex',
                    justifyContent: 'center',
                    gap: 20,
                    marginTop: 24,
                    flexWrap: 'wrap',
                }}
            >
                {[
                    { color: '#EF4444', label: 'Critical (0–30%)' },
                    { color: '#F97316', label: 'Low (30–60%)' },
                    { color: '#F59E0B', label: 'Moderate (60–80%)' },
                    { color: '#10B981', label: 'Sufficient (80–100%)' },
                ].map((item) => (
                    <div
                        key={item.label}
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: 6,
                            fontSize: 11,
                            color: '#6B7280',
                        }}
                    >
                        <div
                            style={{
                                width: 10,
                                height: 10,
                                borderRadius: 2,
                                backgroundColor: item.color,
                            }}
                        />
                        <span>{item.label}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

