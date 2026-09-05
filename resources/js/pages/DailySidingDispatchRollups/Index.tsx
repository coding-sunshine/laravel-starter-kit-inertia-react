import AppLayout from '@/layouts/app-layout';
import Heading from '@/components/heading';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { BreadcrumbItem } from '@/types';
import { index as rollupsIndex, recalculate as rollupsRecalculate } from '@/routes/daily-siding-dispatch-rollups';
import { Head, router, usePage } from '@inertiajs/react';
import { Loader2, AlertTriangle } from 'lucide-react';
import { useEffect, useMemo, useState, type ReactElement } from 'react';

interface SidingLite {
    id: number;
    name: string;
    code: string;
}

interface RollupRow {
    id: number;
    issued_on_date: string;
    siding_id: number;
    siding?: SidingLite | null;
    shift_number: number;
    dispatches_count: number;
    qty_mineral_mt: string | number;
    created_at: string | null;
    updated_at: string | null;
}

interface DaySummaryRow {
    date: string;
    bucket_count: number;
    total_dispatches: number;
    total_qty_mineral_mt: string;
    has_dispatch_source: boolean;
}

interface PaginatorPayload<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Filters {
    date_from: string;
    date_to: string;
    detail_date: string | null;
}

interface Props {
    days: PaginatorPayload<DaySummaryRow>;
    detailRollups: PaginatorPayload<RollupRow> | null;
    filters: Filters;
    flash?: {
        success?: string | null;
    };
}

function filterQuery(filters: Filters, overrides: Partial<Filters & { page?: number }> = {}): Record<string, string | number> {
    const q: Record<string, string | number> = {
        date_from: overrides.date_from ?? filters.date_from,
        date_to: overrides.date_to ?? filters.date_to,
    };
    const detail = overrides.detail_date !== undefined ? overrides.detail_date : filters.detail_date;
    if (detail) {
        q.detail_date = detail;
    }
    if (overrides.page !== undefined) {
        q.page = overrides.page;
    }
    return q;
}

export default function DailySidingDispatchRollupsIndex({
    days,
    detailRollups,
    filters,
    flash,
}: Props) {
    const rawDateError = (
        usePage().props.errors as Record<string, string | string[] | undefined>
    ).date;
    const dateError =
        typeof rawDateError === 'string'
            ? rawDateError
            : Array.isArray(rawDateError) && rawDateError.length > 0
              ? rawDateError[0]
              : undefined;

    const [rangeFrom, setRangeFrom] = useState(filters.date_from);
    const [rangeTo, setRangeTo] = useState(filters.date_to);
    const [orphanDate, setOrphanDate] = useState('');
    const [submittingDay, setSubmittingDay] = useState<string | null>(null);
    const [orphanSubmitting, setOrphanSubmitting] = useState(false);

    useEffect(() => {
        setRangeFrom(filters.date_from);
        setRangeTo(filters.date_to);
    }, [filters.date_from, filters.date_to]);

    const listQuery = useMemo(() => filterQuery(filters), [filters]);

    const breadcrumbs: BreadcrumbItem[] = useMemo(
        () => [
            {
                title: 'Daily siding dispatch rollups',
                href: rollupsIndex.url({ query: listQuery }),
            },
        ],
        [listQuery],
    );

    const applyRange = (): void => {
        router.visit(
            rollupsIndex.url({
                query: {
                    date_from: rangeFrom,
                    date_to: rangeTo,
                    page: 1,
                },
            }),
        );
    };

    const openDetailForDay = (date: string): void => {
        router.visit(
            rollupsIndex.url({
                query: filterQuery(filters, { detail_date: date, page: 1 }),
            }),
        );
    };

    const clearDetail = (): void => {
        router.visit(
            rollupsIndex.url({
                query: {
                    date_from: filters.date_from,
                    date_to: filters.date_to,
                    page: 1,
                },
            }),
        );
    };

    const postRecalculate = (
        date: string,
        callbacks: { onStart?: () => void; onFinish?: () => void } = {},
    ): void => {
        router.post(
            rollupsRecalculate.url(),
            {
                date,
                date_from: filters.date_from,
                date_to: filters.date_to,
                ...(filters.detail_date ? { detail_date: filters.detail_date } : {}),
            },
            {
                preserveScroll: true,
                onStart: callbacks.onStart,
                onFinish: callbacks.onFinish,
            },
        );
    };

    const recalculateDayRow = (date: string): void => {
        setSubmittingDay(date);
        postRecalculate(date, {
            onFinish: () => setSubmittingDay(null),
        });
    };

    const recalculateOrphanDate = (): void => {
        if (!orphanDate) {
            return;
        }
        setOrphanSubmitting(true);
        postRecalculate(orphanDate, {
            onFinish: () => setOrphanSubmitting(false),
        });
    };

    const renderPagination = (payload: PaginatorPayload<unknown>, label: string): ReactElement | null => {
        if (payload.last_page <= 1) {
            return null;
        }
        return (
            <div className="flex flex-col gap-2">
                <span className="text-muted-foreground text-xs">{label}</span>
                <div className="flex flex-wrap gap-2">
                    {payload.links.map((link, index) => {
                        if (link.url === null) {
                            return (
                                <span
                                    key={index}
                                    className="text-muted-foreground px-2 py-1 text-sm"
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            );
                        }
                        return (
                            <Button
                                key={index}
                                type="button"
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => router.visit(link.url!)}
                            >
                                <span
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            </Button>
                        );
                    })}
                </div>
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daily siding dispatch rollups" />

            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-6">
                <Heading title="Daily siding dispatch rollups" />

                {dateError ? (
                    <div
                        role="alert"
                        className="flex gap-3 rounded-lg border border-red-300 bg-red-50 p-4 text-red-950 shadow-md dark:border-red-700 dark:bg-red-950 dark:text-red-50"
                    >
                        <AlertTriangle
                            className="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400"
                            aria-hidden
                        />
                        <p className="min-w-0 text-sm leading-relaxed font-semibold">{dateError}</p>
                    </div>
                ) : null}

                <Card>
                    <CardHeader>
                        <CardTitle>Date range</CardTitle>
                        <CardDescription>
                            Lists every calendar day in the range (newest first). Rollup buckets match{' '}
                            <code className="rounded bg-muted px-1 py-0.5 text-xs">(issued_on)::date</code> like the
                            Vehicle Dispatch register. Default range is the last 14 days including today.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4">
                        {flash?.success ? (
                            <Alert>
                                <AlertDescription>{flash.success}</AlertDescription>
                            </Alert>
                        ) : null}

                        <div className="flex flex-wrap items-end gap-4">
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="rollup-from">From</Label>
                                <Input
                                    id="rollup-from"
                                    type="date"
                                    value={rangeFrom}
                                    onChange={(e) => setRangeFrom(e.target.value)}
                                    className="w-auto min-w-[11rem]"
                                />
                            </div>
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="rollup-to">To</Label>
                                <Input
                                    id="rollup-to"
                                    type="date"
                                    value={rangeTo}
                                    onChange={(e) => setRangeTo(e.target.value)}
                                    className="w-auto min-w-[11rem]"
                                />
                            </div>
                            <Button type="button" variant="secondary" onClick={applyRange}>
                                Apply range
                            </Button>
                        </div>

                        <p className="text-muted-foreground text-sm">
                            Page {days.current_page} of {days.last_page}:{' '}
                            {days.total.toLocaleString()} day(s) in range ({days.per_page} days per page).
                        </p>

                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="whitespace-nowrap">Calendar date</TableHead>
                                        <TableHead className="text-right whitespace-nowrap">Buckets</TableHead>
                                        <TableHead className="text-right whitespace-nowrap">Total dispatches</TableHead>
                                        <TableHead className="text-right whitespace-nowrap">Total qty (MT)</TableHead>
                                        <TableHead className="whitespace-nowrap">Source data</TableHead>
                                        <TableHead className="text-right whitespace-nowrap">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {days.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-muted-foreground">
                                                No days in this range.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        days.data.map((row) => (
                                            <TableRow key={row.date}>
                                                <TableCell className="font-medium whitespace-nowrap">{row.date}</TableCell>
                                                <TableCell className="text-right">{row.bucket_count}</TableCell>
                                                <TableCell className="text-right">{row.total_dispatches}</TableCell>
                                                <TableCell className="text-right">{row.total_qty_mineral_mt}</TableCell>
                                                <TableCell className="text-muted-foreground text-sm">
                                                    {row.has_dispatch_source ? (
                                                        <span className="text-foreground">Uploaded</span>
                                                    ) : (
                                                        <span>No dispatch rows</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex flex-wrap justify-end gap-2">
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => openDetailForDay(row.date)}
                                                        >
                                                            View buckets
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            disabled={
                                                                !row.has_dispatch_source || submittingDay === row.date
                                                            }
                                                            title={
                                                                row.has_dispatch_source
                                                                    ? undefined
                                                                    : 'Upload dispatch data for this date first.'
                                                            }
                                                            onClick={() => recalculateDayRow(row.date)}
                                                        >
                                                            {submittingDay === row.date ? (
                                                                <Loader2 className="size-4 animate-spin" />
                                                            ) : (
                                                                'Recalculate'
                                                            )}
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {renderPagination(days, 'Day pages')}
                    </CardContent>
                </Card>

                {filters.detail_date ? (
                    <Card>
                        <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-4">
                            <div>
                                <CardTitle>Bucket detail — {filters.detail_date}</CardTitle>
                                <CardDescription>
                                    Per siding / shift rollup rows stored for this calendar date (
                                    {detailRollups?.total ?? 0} row(s)).
                                </CardDescription>
                            </div>
                            <Button type="button" variant="outline" size="sm" onClick={clearDetail}>
                                Clear detail
                            </Button>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="whitespace-nowrap">ID</TableHead>
                                            <TableHead className="whitespace-nowrap">issued_on_date</TableHead>
                                            <TableHead className="whitespace-nowrap">siding_id</TableHead>
                                            <TableHead>Siding</TableHead>
                                            <TableHead className="whitespace-nowrap">shift_number</TableHead>
                                            <TableHead className="text-right whitespace-nowrap">
                                                dispatches_count
                                            </TableHead>
                                            <TableHead className="text-right whitespace-nowrap">
                                                qty_mineral_mt
                                            </TableHead>
                                            <TableHead className="whitespace-nowrap">created_at</TableHead>
                                            <TableHead className="whitespace-nowrap">updated_at</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {!detailRollups || detailRollups.data.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={9} className="text-muted-foreground">
                                                    No rollup buckets for this date yet — recalculate after uploads land.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            detailRollups.data.map((r) => (
                                                <TableRow key={r.id}>
                                                    <TableCell className="font-mono text-xs">{r.id}</TableCell>
                                                    <TableCell className="whitespace-nowrap">{r.issued_on_date}</TableCell>
                                                    <TableCell>{r.siding_id}</TableCell>
                                                    <TableCell>
                                                        {r.siding ? `${r.siding.name} (${r.siding.code})` : '—'}
                                                    </TableCell>
                                                    <TableCell>{r.shift_number}</TableCell>
                                                    <TableCell className="text-right">{r.dispatches_count}</TableCell>
                                                    <TableCell className="text-right">{r.qty_mineral_mt}</TableCell>
                                                    <TableCell className="text-muted-foreground text-xs whitespace-nowrap">
                                                        {r.created_at ?? '—'}
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground text-xs whitespace-nowrap">
                                                        {r.updated_at ?? '—'}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                            {detailRollups ? renderPagination(detailRollups, 'Bucket detail pages') : null}
                        </CardContent>
                    </Card>
                ) : null}

                <Card>
                    <CardHeader>
                        <CardTitle>Recalculate any calendar date</CardTitle>
                        <CardDescription>
                            Use when operators uploaded dispatches late (previous days or ahead). The day may not appear
                            with totals until rollups exist — this still rebuilds from{' '}
                            <code className="rounded bg-muted px-1 py-0.5 text-xs">siding_vehicle_dispatches</code>. If
                            nothing was uploaded for that date, you will see a validation error.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-wrap items-end gap-4">
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="rollup-orphan-date">Date</Label>
                            <Input
                                id="rollup-orphan-date"
                                type="date"
                                value={orphanDate}
                                onChange={(e) => setOrphanDate(e.target.value)}
                                className="w-auto min-w-[11rem]"
                            />
                        </div>
                        <Button
                            type="button"
                            disabled={orphanSubmitting || orphanDate === ''}
                            onClick={recalculateOrphanDate}
                        >
                            {orphanSubmitting ? (
                                <>
                                    <Loader2 className="mr-2 size-4 animate-spin" />
                                    Recalculating…
                                </>
                            ) : (
                                'Recalculate this date'
                            )}
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
