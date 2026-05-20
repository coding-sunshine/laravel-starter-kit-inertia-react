import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { JsonFetchError, laravelJsonFetch } from '@/lib/laravel-json-fetch';
import { useCallback, useEffect, useMemo, useState } from 'react';
import {
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

export type RpOverloadTrendsPeriod =
    | 'today'
    | 'yesterday'
    | 'week'
    | 'month'
    | 'last_month';

const PERIOD_OPTIONS: { value: RpOverloadTrendsPeriod; label: string }[] = [
    { value: 'today', label: 'Today' },
    { value: 'yesterday', label: 'Yesterday' },
    { value: 'week', label: 'This week' },
    { value: 'month', label: 'This month' },
    { value: 'last_month', label: 'Last month' },
];

const LINE_COLORS = [
    '#ef4444',
    '#f97316',
    '#eab308',
    '#22c55e',
    '#3b82f6',
    '#a855f7',
    '#ec4899',
    '#14b8a6',
];

export interface RpOverloadTrendsSidingSeries {
    siding_id: number;
    siding_name: string;
    summary: {
        avg_daily_overload_pct: number;
        avg_daily_underload_pct: number;
        days_with_activity: number;
        total_wagons: number;
    };
    daily: Array<{
        date: string;
        label: string;
        overload_pct: number;
        underload_pct: number;
        total_wagons: number;
    }>;
}

interface RpOverloadTrendsPayload {
    from: string;
    to: string;
    underload_threshold: number;
    by_siding: RpOverloadTrendsSidingSeries[];
}

interface Props {
    active: boolean;
    selectedSidingTab: 'all' | number;
    trendsPeriod: RpOverloadTrendsPeriod;
    onTrendsPeriodChange: (period: RpOverloadTrendsPeriod) => void;
    defaultUnderloadThreshold: number;
    buildSearchParams: (args: {
        trendsPeriod: RpOverloadTrendsPeriod;
        sidingId?: number;
        underloadThreshold: number;
    }) => string;
    scopeFilterKey: string;
}

type TrendsMetric = 'overload' | 'underload';

function clampUnderloadPercent(n: number): number {
    if (Number.isNaN(n)) {
        return 1;
    }
    return Math.max(0, Math.min(100, n));
}

function formatPct(v: number | string | undefined): string {
    const n = Number(v ?? 0);
    return `${n.toFixed(1)}%`;
}

function pivotMultiSidingChart(
    bySiding: RpOverloadTrendsSidingSeries[],
    metric: TrendsMetric,
): {
    points: Array<Record<string, string | number>>;
    series: Array<{ key: string; label: string }>;
} {
    const dateMap = new Map<string, Record<string, string | number>>();
    const series: Array<{ key: string; label: string }> = [];

    for (const siding of bySiding) {
        const key = `siding_${siding.siding_id}_${metric}_pct`;
        series.push({ key, label: siding.siding_name });
        for (const day of siding.daily) {
            const pct =
                metric === 'overload' ? day.overload_pct : day.underload_pct;
            let row = dateMap.get(day.date);
            if (!row) {
                row = { label: day.label, date: day.date };
                dateMap.set(day.date, row);
            }
            row[key] = pct;
        }
    }

    const points = [...dateMap.values()].sort((a, b) =>
        String(a.date).localeCompare(String(b.date)),
    );

    return { points, series };
}

export function RakePerformanceOverloadTrends({
    active,
    selectedSidingTab,
    trendsPeriod,
    onTrendsPeriodChange,
    defaultUnderloadThreshold,
    buildSearchParams,
    scopeFilterKey,
}: Props) {
    const [metric, setMetric] = useState<TrendsMetric>('overload');
    const [payload, setPayload] = useState<RpOverloadTrendsPayload | null>(
        null,
    );
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [underloadThreshold, setUnderloadThreshold] = useState(() =>
        clampUnderloadPercent(defaultUnderloadThreshold),
    );
    const [underloadDraft, setUnderloadDraft] = useState(() =>
        String(clampUnderloadPercent(defaultUnderloadThreshold)),
    );

    useEffect(() => {
        const v = clampUnderloadPercent(defaultUnderloadThreshold);
        setUnderloadThreshold(v);
        setUnderloadDraft(String(v));
    }, [defaultUnderloadThreshold]);

    const filterKey = useMemo(
        () =>
            [
                trendsPeriod,
                selectedSidingTab,
                underloadThreshold,
                scopeFilterKey,
            ].join('|'),
        [trendsPeriod, selectedSidingTab, underloadThreshold, scopeFilterKey],
    );

    const commitUnderloadDraft = useCallback(() => {
        const raw = underloadDraft.trim();
        if (raw === '') {
            setUnderloadDraft('1');
            setUnderloadThreshold(1);
            return;
        }
        const v = parseFloat(raw);
        if (Number.isNaN(v)) {
            setUnderloadDraft(String(underloadThreshold));
            return;
        }
        const clamped = clampUnderloadPercent(v);
        setUnderloadDraft(String(clamped));
        setUnderloadThreshold(clamped);
    }, [underloadDraft, underloadThreshold]);

    useEffect(() => {
        if (!active) {
            return;
        }

        let cancelled = false;
        setLoading(true);
        setError(null);

        const qs = buildSearchParams({
            trendsPeriod,
            sidingId:
                selectedSidingTab === 'all' ? undefined : selectedSidingTab,
            underloadThreshold,
        });

        laravelJsonFetch<{ data: RpOverloadTrendsPayload }>(
            `/dashboard/rake-performance/overload-trends?${qs}`,
        )
            .then((res) => {
                if (!cancelled) {
                    setPayload(res.data);
                }
            })
            .catch((e: unknown) => {
                if (!cancelled) {
                    setError(
                        e instanceof JsonFetchError
                            ? e.message
                            : e instanceof Error
                              ? e.message
                              : 'Failed to load trends',
                    );
                    setPayload(null);
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [active, filterKey, buildSearchParams, trendsPeriod, selectedSidingTab]);

    const isAllSidings = selectedSidingTab === 'all';
    const singleSiding = !isAllSidings
        ? payload?.by_siding.find((s) => s.siding_id === selectedSidingTab)
        : null;

    const multiChart = useMemo(() => {
        if (!payload || !isAllSidings) {
            return { points: [], series: [] };
        }
        return pivotMultiSidingChart(payload.by_siding, metric);
    }, [payload, isAllSidings, metric]);

    const hasChartData = isAllSidings
        ? multiChart.points.some((p) =>
              multiChart.series.some((s) => Number(p[s.key] ?? 0) > 0),
          )
        : (singleSiding?.daily.some((d) => d.total_wagons > 0) ?? false);

    return (
        <div className="mt-4 space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-xs text-gray-600">
                    Daily wagon overload / underload rates for rakes in scope
                </p>
                <div className="flex flex-wrap items-center gap-2">
                    {isAllSidings && (
                        <div
                            role="group"
                            aria-label="Trend metric"
                            className="inline-flex items-center rounded-md border border-input bg-background p-0.5 shadow-sm"
                        >
                            <Button
                                type="button"
                                variant={
                                    metric === 'overload' ? 'default' : 'ghost'
                                }
                                size="sm"
                                className="h-8 px-3 text-xs"
                                onClick={() => setMetric('overload')}
                            >
                                Overload
                            </Button>
                            <Button
                                type="button"
                                variant={
                                    metric === 'underload' ? 'default' : 'ghost'
                                }
                                size="sm"
                                className="h-8 px-3 text-xs"
                                onClick={() => setMetric('underload')}
                            >
                                Underload
                            </Button>
                        </div>
                    )}
                    <Select
                        value={trendsPeriod}
                        onValueChange={(v) =>
                            onTrendsPeriodChange(v as RpOverloadTrendsPeriod)
                        }
                    >
                        <SelectTrigger className="min-w-[140px] rounded-lg border border-gray-200 bg-white text-sm">
                            <SelectValue placeholder="Period" />
                        </SelectTrigger>
                        <SelectContent>
                            {PERIOD_OPTIONS.map((o) => (
                                <SelectItem key={o.value} value={o.value}>
                                    {o.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <div className="flex flex-col gap-0.5">
                        <label
                            htmlFor="rp-overload-underload-threshold"
                            className="text-[10px] font-medium text-gray-600"
                        >
                            Underload threshold (% of CC)
                        </label>
                        <Input
                            id="rp-overload-underload-threshold"
                            type="number"
                            inputMode="decimal"
                            min={0}
                            max={100}
                            step={0.1}
                            className="h-8 w-[4.5rem] rounded-md border border-gray-200 bg-white px-2 text-xs tabular-nums"
                            value={underloadDraft}
                            onChange={(e) => {
                                setUnderloadDraft(e.target.value);
                            }}
                            onBlur={commitUnderloadDraft}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    commitUnderloadDraft();
                                }
                            }}
                        />
                    </div>
                </div>
            </div>

            {error != null && (
                <p className="text-sm text-red-600" role="alert">
                    {error}
                </p>
            )}

            {loading ? (
                <p className="py-12 text-center text-sm text-gray-600">
                    Loading trends…
                </p>
            ) : payload == null || payload.by_siding.length === 0 ? (
                <p className="py-12 text-center text-sm text-gray-600">
                    No overload trend data for this period.
                </p>
            ) : (
                <>
                    {isAllSidings ? (
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {payload.by_siding.map((s) => (
                                <div
                                    key={s.siding_id}
                                    className="rounded-lg border border-gray-100 bg-[#fbfbfc] p-3"
                                >
                                    <p className="text-xs font-medium text-gray-800">
                                        {s.siding_name}
                                    </p>
                                    <div className="mt-2 grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <span className="text-red-600">
                                                Avg overload
                                            </span>
                                            <p className="font-semibold tabular-nums text-gray-900">
                                                {formatPct(
                                                    s.summary
                                                        .avg_daily_overload_pct,
                                                )}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-amber-800">
                                                Avg underload
                                            </span>
                                            <p className="font-semibold tabular-nums text-gray-900">
                                                {formatPct(
                                                    s.summary
                                                        .avg_daily_underload_pct,
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : singleSiding != null ? (
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="rounded-lg border border-red-100 bg-red-50/50 p-4">
                                <p className="text-xs font-medium text-red-700">
                                    Avg daily overload
                                </p>
                                <p className="mt-1 text-2xl font-bold tabular-nums text-red-900">
                                    {formatPct(
                                        singleSiding.summary
                                            .avg_daily_overload_pct,
                                    )}
                                </p>
                            </div>
                            <div className="rounded-lg border border-amber-100 bg-amber-50/50 p-4">
                                <p className="text-xs font-medium text-amber-900">
                                    Avg daily underload
                                </p>
                                <p className="mt-1 text-2xl font-bold tabular-nums text-amber-950">
                                    {formatPct(
                                        singleSiding.summary
                                            .avg_daily_underload_pct,
                                    )}
                                </p>
                            </div>
                        </div>
                    ) : null}

                    {!hasChartData ? (
                        <p className="py-8 text-center text-sm text-gray-500">
                            No wagon loads in this period.
                        </p>
                    ) : (
                        <ResponsiveContainer width="100%" height={320}>
                            <LineChart
                                data={
                                    isAllSidings
                                        ? multiChart.points
                                        : singleSiding?.daily
                                }
                                margin={{
                                    top: 8,
                                    right: 16,
                                    left: 8,
                                    bottom: 8,
                                }}
                            >
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    strokeOpacity={0.3}
                                />
                                <XAxis
                                    dataKey="label"
                                    tick={{ fontSize: 11 }}
                                />
                                <YAxis
                                    tick={{ fontSize: 11 }}
                                    tickFormatter={(v) => `${v}%`}
                                    domain={[0, 'auto']}
                                />
                                <Tooltip
                                    formatter={(
                                        v: number | string | undefined,
                                        name: string | undefined,
                                    ) => [formatPct(v), name ?? '']}
                                />
                                <Legend
                                    layout="horizontal"
                                    align="center"
                                    verticalAlign="bottom"
                                    wrapperStyle={{ paddingTop: 16 }}
                                />
                                {isAllSidings
                                    ? multiChart.series.map((s, i) => (
                                          <Line
                                              key={s.key}
                                              type="monotone"
                                              dataKey={s.key}
                                              name={s.label}
                                              stroke={
                                                  LINE_COLORS[
                                                      i % LINE_COLORS.length
                                                  ]
                                              }
                                              strokeWidth={2}
                                              dot={false}
                                              activeDot={{ r: 4 }}
                                              connectNulls
                                          />
                                      ))
                                    : (
                                          <>
                                              <Line
                                                  type="monotone"
                                                  dataKey="overload_pct"
                                                  name="Overload"
                                                  stroke="#ef4444"
                                                  strokeWidth={2}
                                                  dot={false}
                                                  activeDot={{ r: 4 }}
                                              />
                                              <Line
                                                  type="monotone"
                                                  dataKey="underload_pct"
                                                  name="Underload"
                                                  stroke="#d97706"
                                                  strokeWidth={2}
                                                  dot={false}
                                                  activeDot={{ r: 4 }}
                                              />
                                          </>
                                      )}
                            </LineChart>
                        </ResponsiveContainer>
                    )}

                    <p className="text-[11px] text-gray-500">
                        Percent of eligible wagons per day. Underload threshold:{' '}
                        {underloadThreshold}% of CC. Period:{' '}
                        {payload.from} – {payload.to}.
                    </p>
                </>
            )}
        </div>
    );
}