import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { laravelJsonFetch } from '@/lib/laravel-json-fetch';
import { AlertTriangle, ArrowDown, ArrowUp } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
    Bar,
    CartesianGrid,
    Legend,
    BarChart as RechartsBarChart,
    ReferenceLine,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

function stripUnderloadThresholdFromQuery(qs: string): string {
    const u = new URLSearchParams(qs);
    u.delete('underload_threshold');
    return u.toString();
}

function withUnderloadThreshold(qs: string, percent: number): string {
    const u = new URLSearchParams(stripUnderloadThresholdFromQuery(qs));
    u.set('underload_threshold', String(percent));
    return u.toString();
}

function clampUnderloadPercent(n: number): number {
    if (Number.isNaN(n)) {
        return 1;
    }
    return Math.max(0, Math.min(100, n));
}

type OverloadMonthRow = {
    month: string;
    overload: number;
    underload: number;
    total: number;
};

type OverloadDetailSummary = {
    total_wagons: number;
    overloaded_wagons: number;
    underloaded_wagons: number;
    overload_rate: number;
    underload_rate: number;
    overload_trend: number;
    underload_trend: number;
};

type LoaderDetailPayload = {
    loader: { id: number; name: string; siding: string };
    operators: string[];
    monthly: OverloadMonthRow[];
    summary: OverloadDetailSummary;
};

type OperatorDetailPayload = {
    operator: { name: string; siding_id: number; siding: string };
    loaders: Array<{ id: number; name: string }>;
    monthly: OverloadMonthRow[];
    summary: OverloadDetailSummary;
};

type LoaderListRow = { id: number; name: string; siding: string };

type OperatorListRow = { siding_id: number; siding: string; name: string };

type ListMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

export function LoaderOverloadDashboardSection({
    buildApiSearchParams,
    defaultDetailUnderloadPercent,
    mainDateRangeLabel,
    loaderIdFromUrl,
    filterKey,
}: {
    buildApiSearchParams: (args: { page?: number; perPage?: number }) => string;
    /** Seeds the “View data” underload field (charts/KPIs); does not need to match the URL. */
    defaultDetailUnderloadPercent: number;
    mainDateRangeLabel: string | null;
    loaderIdFromUrl: number | null;
    /** Changes when global dashboard filters change (reload lists). */
    filterKey: string;
}) {
    const [subTab, setSubTab] = useState<'loaders' | 'operators'>('loaders');
    const [page, setPage] = useState(1);
    const [listMeta, setListMeta] = useState<ListMeta | null>(null);
    const [loaderRows, setLoaderRows] = useState<LoaderListRow[]>([]);
    const [operatorRows, setOperatorRows] = useState<OperatorListRow[]>([]);
    const [listLoading, setListLoading] = useState(false);
    const [listError, setListError] = useState<string | null>(null);

    const [detailOpen, setDetailOpen] = useState(false);
    const [detailKind, setDetailKind] = useState<'loader' | 'operator' | null>(
        null,
    );
    const [loaderDetail, setLoaderDetail] =
        useState<LoaderDetailPayload | null>(null);
    const [operatorDetail, setOperatorDetail] =
        useState<OperatorDetailPayload | null>(null);
    const [detailLoading, setDetailLoading] = useState(false);
    const [detailError, setDetailError] = useState<string | null>(null);

    /** Prevents duplicate auto-open for the same dashboard filter key + loader id. */
    const lastAutoOpenKeyRef = useRef<string | null>(null);

    const [detailUnderloadDraft, setDetailUnderloadDraft] = useState('1');
    const [debouncedUnderloadPercent, setDebouncedUnderloadPercent] =
        useState(1);
    const [activeDetailLoaderId, setActiveDetailLoaderId] = useState<
        number | null
    >(null);
    const [activeDetailOperator, setActiveDetailOperator] = useState<{
        sidingId: number;
        name: string;
    } | null>(null);

    const seedDetailUnderload = useCallback((): number => {
        return clampUnderloadPercent(
            Number.isFinite(defaultDetailUnderloadPercent)
                ? defaultDetailUnderloadPercent
                : 1,
        );
    }, [defaultDetailUnderloadPercent]);

    useEffect(() => {
        setPage(1);
    }, [filterKey]);

    useEffect(() => {
        if (subTab !== 'loaders') {
            return;
        }
        let cancelled = false;
        setListLoading(true);
        setListError(null);
        const qs = stripUnderloadThresholdFromQuery(
            buildApiSearchParams({ page, perPage: 10 }),
        );
        laravelJsonFetch<{
            data: LoaderListRow[];
            meta: ListMeta;
        }>(`/dashboard/loader-overload/loaders?${qs}`)
            .then((res) => {
                if (!cancelled) {
                    setLoaderRows(res.data);
                    setListMeta(res.meta);
                }
            })
            .catch((e: unknown) => {
                if (!cancelled) {
                    setListError(
                        e instanceof Error
                            ? e.message
                            : 'Failed to load loaders',
                    );
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setListLoading(false);
                }
            });
        return () => {
            cancelled = true;
        };
    }, [subTab, filterKey, page, buildApiSearchParams]);

    useEffect(() => {
        if (subTab !== 'operators') {
            return;
        }
        let cancelled = false;
        setListLoading(true);
        setListError(null);
        const qs = stripUnderloadThresholdFromQuery(
            buildApiSearchParams({ page, perPage: 10 }),
        );
        laravelJsonFetch<{
            data: OperatorListRow[];
            meta: ListMeta;
        }>(`/dashboard/loader-overload/operators?${qs}`)
            .then((res) => {
                if (!cancelled) {
                    setOperatorRows(res.data);
                    setListMeta(res.meta);
                }
            })
            .catch((e: unknown) => {
                if (!cancelled) {
                    setListError(
                        e instanceof Error
                            ? e.message
                            : 'Failed to load operators',
                    );
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setListLoading(false);
                }
            });
        return () => {
            cancelled = true;
        };
    }, [subTab, filterKey, page, buildApiSearchParams]);

    const openLoaderDetail = useCallback(
        (id: number) => {
            const seed = seedDetailUnderload();
            setDetailKind('loader');
            setLoaderDetail(null);
            setOperatorDetail(null);
            setDetailError(null);
            setActiveDetailLoaderId(id);
            setActiveDetailOperator(null);
            setDetailUnderloadDraft(String(seed));
            setDebouncedUnderloadPercent(seed);
            setDetailOpen(true);
        },
        [seedDetailUnderload],
    );

    const openOperatorDetail = useCallback(
        (sidingId: number, name: string) => {
            const seed = seedDetailUnderload();
            setDetailKind('operator');
            setLoaderDetail(null);
            setOperatorDetail(null);
            setDetailError(null);
            setActiveDetailLoaderId(null);
            setActiveDetailOperator({ sidingId, name });
            setDetailUnderloadDraft(String(seed));
            setDebouncedUnderloadPercent(seed);
            setDetailOpen(true);
        },
        [seedDetailUnderload],
    );

    useEffect(() => {
        if (!detailOpen) {
            return;
        }
        const t = window.setTimeout(() => {
            const raw = detailUnderloadDraft.trim();
            if (raw === '') {
                setDebouncedUnderloadPercent(1);
                return;
            }
            const v = parseFloat(raw);
            if (Number.isNaN(v)) {
                return;
            }
            setDebouncedUnderloadPercent(clampUnderloadPercent(v));
        }, 400);
        return () => window.clearTimeout(t);
    }, [detailUnderloadDraft, detailOpen]);

    useEffect(() => {
        if (!detailOpen) {
            return;
        }
        if (detailKind == null) {
            return;
        }
        if (detailKind === 'loader' && activeDetailLoaderId == null) {
            return;
        }
        if (
            detailKind === 'operator' &&
            (activeDetailOperator == null ||
                activeDetailOperator.sidingId <= 0 ||
                activeDetailOperator.name === '')
        ) {
            return;
        }
        let cancelled = false;
        setDetailLoading(true);
        setDetailError(null);
        const baseQs = stripUnderloadThresholdFromQuery(
            buildApiSearchParams({}),
        );
        const qs = withUnderloadThreshold(
            baseQs,
            debouncedUnderloadPercent,
        );
        if (detailKind === 'loader' && activeDetailLoaderId != null) {
            laravelJsonFetch<{ data: LoaderDetailPayload }>(
                `/dashboard/loader-overload/loaders/${activeDetailLoaderId}?${qs}`,
            )
                .then((res) => {
                    if (!cancelled) {
                        setLoaderDetail(res.data);
                    }
                })
                .catch((e: unknown) => {
                    if (!cancelled) {
                        setDetailError(
                            e instanceof Error
                                ? e.message
                                : 'Failed to load',
                        );
                    }
                })
                .finally(() => {
                    if (!cancelled) {
                        setDetailLoading(false);
                    }
                });
        } else if (detailKind === 'operator' && activeDetailOperator) {
            const op = encodeURIComponent(activeDetailOperator.name);
            laravelJsonFetch<{ data: OperatorDetailPayload }>(
                `/dashboard/loader-overload/operators/show?siding_id=${activeDetailOperator.sidingId}&operator=${op}&${qs}`,
            )
                .then((res) => {
                    if (!cancelled) {
                        setOperatorDetail(res.data);
                    }
                })
                .catch((e: unknown) => {
                    if (!cancelled) {
                        setDetailError(
                            e instanceof Error
                                ? e.message
                                : 'Failed to load',
                        );
                    }
                })
                .finally(() => {
                    if (!cancelled) {
                        setDetailLoading(false);
                    }
                });
        } else {
            setDetailLoading(false);
        }
        return () => {
            cancelled = true;
        };
    }, [
        detailOpen,
        detailKind,
        activeDetailLoaderId,
        activeDetailOperator?.sidingId,
        activeDetailOperator?.name,
        debouncedUnderloadPercent,
        buildApiSearchParams,
    ]);

    useEffect(() => {
        if (loaderIdFromUrl == null) {
            return;
        }
        const k = `${filterKey}:${loaderIdFromUrl}`;
        if (lastAutoOpenKeyRef.current === k) {
            return;
        }
        lastAutoOpenKeyRef.current = k;
        openLoaderDetail(loaderIdFromUrl);
    }, [loaderIdFromUrl, filterKey, openLoaderDetail]);

    const trendData = useMemo(() => {
        const m =
            detailKind === 'loader'
                ? loaderDetail?.monthly
                : operatorDetail?.monthly;
        if (!m) {
            return [];
        }
        return m.map((row) => ({
            month: row.month,
            overloaded: row.overload,
            underloaded: row.underload,
            total: row.total,
        }));
    }, [detailKind, loaderDetail?.monthly, operatorDetail?.monthly]);

    const overloadOnlyData = useMemo(() => {
        const m =
            detailKind === 'loader'
                ? loaderDetail?.monthly
                : operatorDetail?.monthly;
        if (!m) {
            return [];
        }
        return m.map((row) => ({
            month: row.month,
            overloaded: row.overload,
            underloaded: row.underload,
        }));
    }, [detailKind, loaderDetail?.monthly, operatorDetail?.monthly]);

    const summary = useMemo(
        () =>
            detailKind === 'loader'
                ? loaderDetail?.summary
                : operatorDetail?.summary,
        [detailKind, loaderDetail?.summary, operatorDetail?.summary],
    );

    const avgOverload = useMemo(() => {
        if (overloadOnlyData.length === 0) {
            return 0;
        }
        return (
            overloadOnlyData.reduce((s, d) => s + d.overloaded, 0) /
            overloadOnlyData.length
        );
    }, [overloadOnlyData]);

    const avgUnderload = useMemo(() => {
        if (overloadOnlyData.length === 0) {
            return 0;
        }
        return (
            overloadOnlyData.reduce((s, d) => s + d.underloaded, 0) /
            overloadOnlyData.length
        );
    }, [overloadOnlyData]);

    const hasChartData = trendData.some(
        (d) => d.total > 0 || d.overloaded > 0 || d.underloaded > 0,
    );

    const title =
        detailKind === 'loader'
            ? loaderDetail?.loader.name
            : detailKind === 'operator'
              ? operatorDetail?.operator.name
              : 'Details';

    return (
        <div className="dashboard-card rounded-xl border-0 p-6">
            <div className="flex flex-wrap items-center gap-3">
                <div className="flex size-9 items-center justify-center rounded-xl bg-primary/10">
                    <AlertTriangle className="size-4.5 text-primary" />
                </div>
                <div>
                    <h3 className="text-base font-semibold">
                        Loader and operator overloading
                    </h3>
                    <p className="text-xs text-gray-600">
                        {mainDateRangeLabel
                            ? `${mainDateRangeLabel} (rake loading date). Open “View data” for charts; underload rule is set inside the window.`
                            : 'Monthly trends by rake loading date. Open View data to load charts. Underload rule is set in the detail window.'}
                    </p>
                </div>
            </div>

            <div className="mt-4 flex flex-wrap gap-2">
                <button
                    type="button"
                    onClick={() => {
                        setSubTab('loaders');
                        setPage(1);
                    }}
                    className={`rounded-full px-3 py-1.5 text-sm font-medium ${
                        subTab === 'loaders'
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-gray-100 text-gray-700'
                    }`}
                >
                    Loaders
                </button>
                <button
                    type="button"
                    onClick={() => {
                        setSubTab('operators');
                        setPage(1);
                    }}
                    className={`rounded-full px-3 py-1.5 text-sm font-medium ${
                        subTab === 'operators'
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-gray-100 text-gray-700'
                    }`}
                >
                    Operators
                </button>
            </div>

            {listError && (
                <p className="mt-3 text-sm text-red-600" role="alert">
                    {listError}
                </p>
            )}

            <div className="mt-4 overflow-x-auto rounded-lg border border-gray-200">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {subTab === 'loaders' ? (
                                <>
                                    <TableHead>Loader</TableHead>
                                    <TableHead>Siding</TableHead>
                                    <TableHead className="w-[120px] text-right">
                                        Actions
                                    </TableHead>
                                </>
                            ) : (
                                <>
                                    <TableHead>Operator</TableHead>
                                    <TableHead>Siding</TableHead>
                                    <TableHead className="w-[120px] text-right">
                                        Actions
                                    </TableHead>
                                </>
                            )}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {listLoading && (
                            <TableRow>
                                <TableCell
                                    colSpan={3}
                                    className="text-center text-sm text-gray-500"
                                >
                                    Loading…
                                </TableCell>
                            </TableRow>
                        )}
                        {!listLoading &&
                            subTab === 'loaders' &&
                            loaderRows.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={3}
                                        className="text-center text-sm text-gray-500"
                                    >
                                        No loaders with activity in this range.
                                    </TableCell>
                                </TableRow>
                            )}
                        {!listLoading &&
                            subTab === 'operators' &&
                            operatorRows.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={3}
                                        className="text-center text-sm text-gray-500"
                                    >
                                        No operators in this range.
                                    </TableCell>
                                </TableRow>
                            )}
                        {!listLoading &&
                            subTab === 'loaders' &&
                            loaderRows.map((r) => (
                                <TableRow key={r.id}>
                                    <TableCell className="font-medium">
                                        {r.name}
                                    </TableCell>
                                    <TableCell>{r.siding}</TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="secondary"
                                            onClick={() =>
                                                openLoaderDetail(r.id)
                                            }
                                        >
                                            View data
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        {!listLoading &&
                            subTab === 'operators' &&
                            operatorRows.map((r) => (
                                <TableRow key={`${r.siding_id}-${r.name}`}>
                                    <TableCell className="font-medium">
                                        {r.name}
                                    </TableCell>
                                    <TableCell>{r.siding}</TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="secondary"
                                            onClick={() =>
                                                openOperatorDetail(
                                                    r.siding_id,
                                                    r.name,
                                                )
                                            }
                                        >
                                            View data
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                    </TableBody>
                </Table>
            </div>

            {listMeta && listMeta.last_page > 1 && (
                <div className="mt-3 flex items-center justify-between gap-2 text-sm text-gray-600">
                    <span>
                        Page {listMeta.current_page} of {listMeta.last_page} (
                        {listMeta.total} total)
                    </span>
                    <div className="flex gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            disabled={listMeta.current_page <= 1}
                            onClick={() => setPage((p) => Math.max(1, p - 1))}
                        >
                            Previous
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            disabled={
                                listMeta.current_page >= listMeta.last_page
                            }
                            onClick={() => setPage((p) => p + 1)}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            )}

            <Dialog
                open={detailOpen}
                onOpenChange={(open) => {
                    setDetailOpen(open);
                    if (!open) {
                        setDetailKind(null);
                        setActiveDetailLoaderId(null);
                        setActiveDetailOperator(null);
                        setLoaderDetail(null);
                        setOperatorDetail(null);
                        setDetailError(null);
                    }
                }}
            >
                <DialogContent className="!w-[min(98vw,1800px)] flex h-[min(94vh,1100px)] !max-w-[min(98vw,1800px)] flex-col gap-0 overflow-hidden p-0 sm:h-[min(92vh,1100px)] sm:!w-[min(98vw,1800px)] sm:!max-w-[min(98vw,1800px)]">
                    <div className="shrink-0 space-y-3 border-b border-border p-4 pb-3 sm:p-6 sm:pb-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <DialogHeader className="space-y-1 text-left sm:mr-4 sm:text-left">
                                <DialogTitle className="text-lg sm:text-xl">
                                    {title}
                                </DialogTitle>
                                <DialogDescription>
                                    {detailKind === 'loader' && loaderDetail
                                        ? `${loaderDetail.loader.siding} — operators: ${loaderDetail.operators.length ? loaderDetail.operators.join(', ') : '—'}`
                                        : detailKind === 'operator' &&
                                            operatorDetail
                                          ? `${operatorDetail.operator.siding} — loaders: ${operatorDetail.loaders.map((l) => l.name).join(', ') || '—'}`
                                          : 'Loading…'}
                                </DialogDescription>
                            </DialogHeader>
                            <div className="flex w-full flex-col gap-0.5 sm:max-w-[14rem] sm:shrink-0">
                                <label
                                    htmlFor="loader-detail-underload-threshold"
                                    className="text-xs text-muted-foreground"
                                >
                                    Underload threshold (% of CC)
                                </label>
                                <Input
                                    id="loader-detail-underload-threshold"
                                    type="number"
                                    inputMode="decimal"
                                    min={0}
                                    max={100}
                                    step={0.1}
                                    className="h-9 w-full max-w-full rounded-md border text-sm tabular-nums"
                                    value={detailUnderloadDraft}
                                    onChange={(e) => {
                                        setDetailUnderloadDraft(
                                            e.target.value,
                                        );
                                    }}
                                    onBlur={() => {
                                        const raw = detailUnderloadDraft.trim();
                                        if (raw === '') {
                                            setDetailUnderloadDraft('1');
                                            setDebouncedUnderloadPercent(1);
                                            return;
                                        }
                                        const v = parseFloat(raw);
                                        if (Number.isNaN(v)) {
                                            setDetailUnderloadDraft(
                                                String(
                                                    debouncedUnderloadPercent,
                                                ),
                                            );
                                            return;
                                        }
                                        const clamped = clampUnderloadPercent(v);
                                        setDetailUnderloadDraft(
                                            String(clamped),
                                        );
                                        setDebouncedUnderloadPercent(clamped);
                                    }}
                                />
                                <p className="text-[11px] text-muted-foreground">
                                    Counts a wagon as underloaded when the
                                    shortfall is at least this % of its loaded
                                    CC. Updates charts after a short delay.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="min-h-0 flex-1 overflow-y-auto px-4 py-3 sm:px-6 sm:py-4">
                    {detailLoading && (
                        <p className="text-sm text-gray-500">Loading detail…</p>
                    )}
                    {detailError && (
                        <p className="text-sm text-red-600">{detailError}</p>
                    )}
                    {summary && !detailLoading && (
                        <>
                            <div className="mt-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                                <div className="rounded-xl border-0 bg-blue-50 p-3">
                                    <p className="text-xs font-medium text-blue-600">
                                        Total wagons loaded
                                    </p>
                                    <p className="mt-1 text-xl font-bold text-blue-900 tabular-nums">
                                        {summary.total_wagons}
                                    </p>
                                </div>
                                <div className="rounded-xl border-0 bg-red-50 p-3">
                                    <p className="text-xs font-medium text-red-600">
                                        Overloaded wagons
                                    </p>
                                    <p className="mt-1 text-xl font-bold text-red-900 tabular-nums">
                                        {summary.overloaded_wagons}
                                    </p>
                                </div>
                                <div className="rounded-xl border-0 bg-amber-50 p-3">
                                    <p className="text-xs font-medium text-amber-800">
                                        Underloaded wagons
                                    </p>
                                    <p className="mt-1 text-xl font-bold text-amber-950 tabular-nums">
                                        {summary.underloaded_wagons}
                                    </p>
                                </div>
                                <div className="rounded-xl border-0 bg-green-50 p-3">
                                    <p className="text-xs text-green-600">
                                        Overload rate
                                    </p>
                                    <div className="mt-1 flex items-center gap-1">
                                        <span className="text-xl font-bold text-green-900">
                                            {summary.overload_rate.toFixed(1)}%
                                        </span>
                                        {summary.overload_trend !== 0 &&
                                            (summary.overload_trend > 0 ? (
                                                <ArrowUp className="size-4 text-red-600" />
                                            ) : (
                                                <ArrowDown className="size-4 text-green-600" />
                                            ))}
                                    </div>
                                </div>
                                <div className="rounded-xl border-0 bg-orange-50 p-3">
                                    <p className="text-xs text-orange-800">
                                        Underload rate
                                    </p>
                                    <div className="mt-1 flex items-center gap-1">
                                        <span className="text-xl font-bold text-orange-950">
                                            {summary.underload_rate.toFixed(1)}%
                                        </span>
                                        {summary.underload_trend !== 0 &&
                                            (summary.underload_trend > 0 ? (
                                                <ArrowUp className="size-4 text-amber-700" />
                                            ) : (
                                                <ArrowDown className="size-4 text-green-600" />
                                            ))}
                                    </div>
                                </div>
                            </div>
                            {!hasChartData ? (
                                <p className="mt-4 text-sm text-gray-500">
                                    No monthly overload data for this selection.
                                </p>
                            ) : (
                                <div className="mt-4 grid gap-4 lg:grid-cols-2">
                                    <div>
                                        <p className="mb-1 text-xs font-medium text-gray-600">
                                            Total vs overloaded / underloaded
                                            (monthly)
                                        </p>
                                        <ResponsiveContainer
                                            width="100%"
                                            height={400}
                                        >
                                            <RechartsBarChart
                                                data={trendData}
                                                margin={{
                                                    top: 8,
                                                    right: 8,
                                                    left: 8,
                                                    bottom: 0,
                                                }}
                                            >
                                                <CartesianGrid
                                                    strokeDasharray="3 3"
                                                    strokeOpacity={0.3}
                                                />
                                                <XAxis
                                                    dataKey="month"
                                                    tick={{ fontSize: 10 }}
                                                />
                                                <YAxis
                                                    tick={{ fontSize: 10 }}
                                                    allowDecimals={false}
                                                />
                                                <Tooltip
                                                    formatter={(
                                                        v: number | undefined,
                                                        name?: string,
                                                    ) => [
                                                        `${v ?? 0} wagons`,
                                                        name ?? '',
                                                    ]}
                                                />
                                                <Legend />
                                                <Bar
                                                    dataKey="total"
                                                    name="Total"
                                                    fill="#3B82F6"
                                                    radius={[2, 2, 0, 0]}
                                                    maxBarSize={20}
                                                />
                                                <Bar
                                                    dataKey="overloaded"
                                                    name="Overloaded"
                                                    fill="#DC2626"
                                                    radius={[2, 2, 0, 0]}
                                                    maxBarSize={20}
                                                />
                                                <Bar
                                                    dataKey="underloaded"
                                                    name="Underloaded"
                                                    fill="#D97706"
                                                    radius={[2, 2, 0, 0]}
                                                    maxBarSize={20}
                                                />
                                            </RechartsBarChart>
                                        </ResponsiveContainer>
                                    </div>
                                    <div>
                                        <p className="mb-1 text-xs font-medium text-gray-600">
                                            Overloaded vs underloaded
                                        </p>
                                        <ResponsiveContainer
                                            width="100%"
                                            height={400}
                                        >
                                            <RechartsBarChart
                                                data={overloadOnlyData}
                                                margin={{
                                                    top: 8,
                                                    right: 8,
                                                    left: 8,
                                                    bottom: 0,
                                                }}
                                            >
                                                <CartesianGrid
                                                    strokeDasharray="3 3"
                                                    strokeOpacity={0.3}
                                                />
                                                <XAxis
                                                    dataKey="month"
                                                    tick={{ fontSize: 10 }}
                                                />
                                                <YAxis
                                                    tick={{ fontSize: 10 }}
                                                    allowDecimals={false}
                                                />
                                                <Tooltip
                                                    formatter={(
                                                        v: number | undefined,
                                                        name?: string,
                                                    ) => [
                                                        `${v ?? 0} wagons`,
                                                        name ?? '',
                                                    ]}
                                                />
                                                <Legend />
                                                <ReferenceLine
                                                    y={avgOverload}
                                                    stroke="#9ca3af"
                                                    strokeDasharray="5 5"
                                                />
                                                <ReferenceLine
                                                    y={avgUnderload}
                                                    stroke="#d6d3d1"
                                                    strokeDasharray="3 3"
                                                />
                                                <Bar
                                                    dataKey="overloaded"
                                                    name="Overloaded"
                                                    fill="#DC2626"
                                                    radius={[2, 2, 0, 0]}
                                                    maxBarSize={20}
                                                />
                                                <Bar
                                                    dataKey="underloaded"
                                                    name="Underloaded"
                                                    fill="#D97706"
                                                    radius={[2, 2, 0, 0]}
                                                    maxBarSize={20}
                                                />
                                            </RechartsBarChart>
                                        </ResponsiveContainer>
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    );
}
