import { DataTable } from 'laravel-data-table';
import type { DataTableResponse } from 'laravel-data-table';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { rakeLoaderLoadingDateFilterValue } from '@/lib/rake-loader-date-periods';
import AppLayout from '@/layouts/app-layout';
import rakeLoader from '@/routes/rake-loader';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Train } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

type SidingOption = { id: number; name: string; code: string };

type RakeRow = {
    id: number;
    rake_number: string;
    loading_date: string | null;
    siding_label: string | null;
};

interface Props {
    tableData: DataTableResponse<RakeRow>;
    sidings: SidingOption[];
    defaultSidingId: number | null;
    isSuperAdmin: boolean;
    loadError?: string | null;
}

type DateFilterOption = 'today' | 'yesterday' | 'this_week' | 'this_month' | 'financial_year' | 'custom';

const DATE_FILTER_OPTIONS: { id: DateFilterOption; label: string }[] = [
    { id: 'today', label: 'Today' },
    { id: 'yesterday', label: 'Yesterday' },
    { id: 'this_week', label: 'This week' },
    { id: 'this_month', label: 'This month' },
    { id: 'financial_year', label: 'Financial year' },
    { id: 'custom', label: 'Custom range' },
];

function todayIsoDate(): string {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function parseBetweenRange(value: unknown): { from: string; to: string } | null {
    if (typeof value !== 'string' || !value.startsWith('between:')) {
        return null;
    }

    const raw = value.slice('between:'.length);
    const [from, to] = raw.split(',');
    if (!from || !to) {
        return null;
    }

    return { from: from.trim(), to: to.trim() };
}

function detectDateFilterOption(value: unknown): DateFilterOption {
    if (typeof value !== 'string') {
        return 'today';
    }

    if (value === rakeLoaderLoadingDateFilterValue('today')) {
        return 'today';
    }
    if (value === rakeLoaderLoadingDateFilterValue('yesterday')) {
        return 'yesterday';
    }
    if (value === rakeLoaderLoadingDateFilterValue('this_week')) {
        return 'this_week';
    }
    if (value === rakeLoaderLoadingDateFilterValue('this_month')) {
        return 'this_month';
    }
    if (value === rakeLoaderLoadingDateFilterValue('financial_year')) {
        return 'financial_year';
    }

    return 'custom';
}

const ALL_SIDINGS_VALUE = 'all';

export default function RakeLoaderIndex({
    tableData,
    sidings,
    defaultSidingId,
    isSuperAdmin,
    loadError,
}: Props) {
    const page = usePage<{ errors?: Record<string, string> }>();
    const { errors } = page.props;

    const showSidingFilter = sidings.length > 0;
    const nonSuperMultiSidings = !isSuperAdmin && sidings.length > 1;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Rake Loader', href: '/rake-loader' },
    ];

    const searchParams = useMemo(() => {
        try {
            const base = typeof window !== 'undefined' ? window.location.origin : 'http://localhost';
            return new URL(page.url, base).searchParams;
        } catch {
            return new URLSearchParams();
        }
    }, [page.url]);

    const loadingDateFilter = tableData.meta.filters?.loading_date as unknown;
    const [dateFilterOption, setDateFilterOption] = useState<DateFilterOption>(() =>
        detectDateFilterOption(loadingDateFilter),
    );
    const [customFrom, setCustomFrom] = useState<string>(todayIsoDate());
    const [customTo, setCustomTo] = useState<string>(todayIsoDate());

    const [sidingId, setSidingId] = useState<string>(() => {
        try {
            const base = typeof window !== 'undefined' ? window.location.origin : 'http://localhost';
            const sid = new URL(page.url, base).searchParams.get('siding_id');
            if (sid) {
                return sid;
            }
            if (isSuperAdmin) {
                return defaultSidingId ? String(defaultSidingId) : '';
            }
            if (sidings.length === 1 && sidings[0]) {
                return String(sidings[0].id);
            }
            if (sidings.length > 1) {
                return ALL_SIDINGS_VALUE;
            }
            return '';
        } catch {
            if (isSuperAdmin) {
                return defaultSidingId ? String(defaultSidingId) : '';
            }
            if (sidings.length === 1 && sidings[0]) {
                return String(sidings[0].id);
            }
            if (sidings.length > 1) {
                return ALL_SIDINGS_VALUE;
            }
            return '';
        }
    });

    useEffect(() => {
        const sid = searchParams.get('siding_id');
        if (sid) {
            setSidingId(sid);
            return;
        }
        if (!isSuperAdmin && sidings.length > 1) {
            setSidingId(ALL_SIDINGS_VALUE);
        }
    }, [searchParams, isSuperAdmin, sidings.length]);

    useEffect(() => {
        const nextOption = detectDateFilterOption(loadingDateFilter);
        setDateFilterOption(nextOption);

        const customRange = parseBetweenRange(loadingDateFilter);
        if (customRange) {
            setCustomFrom(customRange.from);
            setCustomTo(customRange.to);
        } else {
            const today = todayIsoDate();
            setCustomFrom(today);
            setCustomTo(today);
        }
    }, [loadingDateFilter]);

    useEffect(() => {
        if (!isSuperAdmin || !defaultSidingId) {
            return;
        }
        let u: URL;
        try {
            const base = typeof window !== 'undefined' ? window.location.origin : 'http://localhost';
            u = new URL(page.url, base);
        } catch {
            return;
        }
        if (u.searchParams.has('siding_id')) {
            return;
        }
        u.searchParams.set('siding_id', String(defaultSidingId));
        router.get(u.pathname + u.search, {}, { replace: true, preserveState: true });
    }, [isSuperAdmin, defaultSidingId, page.url]);

    const canShowTable = !isSuperAdmin || Boolean(sidingId);

    useEffect(() => {
        if (!canShowTable) {
            return;
        }
        let u: URL;
        try {
            const base = typeof window !== 'undefined' ? window.location.origin : 'http://localhost';
            u = new URL(page.url, base);
        } catch {
            return;
        }
        if (u.searchParams.has('filter[loading_date]')) {
            return;
        }
        u.searchParams.set('filter[loading_date]', rakeLoaderLoadingDateFilterValue('today'));
        router.get(u.pathname + u.search, {}, { replace: true, preserveState: true });
    }, [canShowTable, page.url]);

    const navigateWithSearch = useCallback((mutate: (u: URL) => void) => {
        const u = new URL(window.location.href);
        mutate(u);
        router.get(u.pathname + u.search, {}, { preserveScroll: true });
    }, []);

    const applyDateFilterOption = useCallback(
        (option: DateFilterOption) => {
            setDateFilterOption(option);

            if (option === 'custom') {
                return;
            }

            const value = rakeLoaderLoadingDateFilterValue(option);
            navigateWithSearch((u) => {
                u.searchParams.set('filter[loading_date]', value);
                u.searchParams.delete('page');
            });
        },
        [navigateWithSearch],
    );

    const applyCustomDateRange = useCallback(() => {
        if (!customFrom || !customTo) {
            return;
        }

        navigateWithSearch((u) => {
            u.searchParams.set('filter[loading_date]', `between:${customFrom},${customTo}`);
            u.searchParams.delete('page');
        });
    }, [customFrom, customTo, navigateWithSearch]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rake Loader" />

            <div className="space-y-4">
                <Heading
                    title="Rake Loader"
                    description="Filter like the Rakes list, then open a rake number to enter loader weighment."
                />

                {loadError && (
                    <Alert variant="destructive" className="py-2">
                        <AlertTitle className="text-sm">Cannot open loader</AlertTitle>
                        <AlertDescription className="text-sm">{loadError}</AlertDescription>
                    </Alert>
                )}

                <Card>
                    <CardHeader className="space-y-1 px-4 pb-2 pt-4">
                        <CardTitle className="flex items-center gap-2 text-base font-semibold">
                            <Train className="h-4 w-4 shrink-0" />
                            Rakes with weighment
                        </CardTitle>
                        <CardDescription className="text-xs">
                            Quick views and filters work like the Railway Rakes page. Click a rake # to open
                            wagon loading.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2 px-4 pb-4 pt-0">
                        <div className="flex flex-wrap items-end gap-2 border-b border-border/60 pb-2">
                            {showSidingFilter ? (
                                <div className="flex flex-col gap-1">
                                    <Label
                                        htmlFor="rake-loader-siding"
                                        className="text-xs font-medium text-muted-foreground"
                                    >
                                        Siding
                                    </Label>
                                    <Select
                                        value={sidingId}
                                        onValueChange={(v) => {
                                            setSidingId(v);
                                            navigateWithSearch((u) => {
                                                if (v === ALL_SIDINGS_VALUE) {
                                                    u.searchParams.delete('siding_id');
                                                } else {
                                                    u.searchParams.set('siding_id', v);
                                                }
                                                u.searchParams.delete('page');
                                            });
                                        }}
                                    >
                                        <SelectTrigger
                                            id="rake-loader-siding"
                                            className="h-8 w-[220px]"
                                            data-pan="rake-loader-siding-select"
                                        >
                                            <SelectValue
                                                placeholder={isSuperAdmin ? 'Select siding' : 'Siding'}
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {nonSuperMultiSidings ? (
                                                <SelectItem value={ALL_SIDINGS_VALUE}>
                                                    All sidings
                                                </SelectItem>
                                            ) : null}
                                            {sidings.map((s) => (
                                                <SelectItem key={s.id} value={String(s.id)}>
                                                    {s.name} ({s.code})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            ) : null}

                            <div className="flex flex-col gap-1">
                                <Label
                                    htmlFor="rake-loader-date-filter"
                                    className="text-xs font-medium text-muted-foreground"
                                >
                                    Loading date
                                </Label>
                                <Select
                                    value={dateFilterOption}
                                    onValueChange={(value) =>
                                        applyDateFilterOption(value as DateFilterOption)
                                    }
                                >
                                    <SelectTrigger
                                        id="rake-loader-date-filter"
                                        className="h-8 w-[220px]"
                                        data-pan="rake-loader-date-filter-select"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {DATE_FILTER_OPTIONS.map((option) => (
                                            <SelectItem key={option.id} value={option.id}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {dateFilterOption === 'custom' ? (
                                <div className="flex flex-wrap items-end gap-2">
                                    <div className="flex flex-col gap-1">
                                        <Label
                                            htmlFor="rake-loader-custom-from"
                                            className="text-xs text-muted-foreground"
                                        >
                                            From
                                        </Label>
                                        <Input
                                            id="rake-loader-custom-from"
                                            type="date"
                                            className="h-8 w-[170px]"
                                            value={customFrom}
                                            onChange={(e) => setCustomFrom(e.target.value)}
                                        />
                                    </div>
                                    <div className="flex flex-col gap-1">
                                        <Label
                                            htmlFor="rake-loader-custom-to"
                                            className="text-xs text-muted-foreground"
                                        >
                                            To
                                        </Label>
                                        <Input
                                            id="rake-loader-custom-to"
                                            type="date"
                                            className="h-8 w-[170px]"
                                            value={customTo}
                                            onChange={(e) => setCustomTo(e.target.value)}
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        size="sm"
                                        className="h-8"
                                        onClick={applyCustomDateRange}
                                        data-pan="rake-loader-date-filter-custom-apply"
                                    >
                                        Apply
                                    </Button>
                                </div>
                            ) : null}

                            <InputError message={errors?.date} />
                        </div>

                        {!canShowTable ? (
                            <p className="py-2 text-sm text-muted-foreground">
                                Select a siding to load rakes.
                            </p>
                        ) : (
                            <DataTable<RakeRow>
                                tableData={tableData}
                                tableName="rake-loader-list"
                                preserveSearchParams={['siding_id']}
                                options={{
                                    quickViews: false,
                                    customQuickViews: false,
                                    exports: false,
                                    filters: false,
                                    columnVisibility: false,
                                    columnOrdering: false,
                                }}
                                toolbarPosition="left"
                                actions={[
                                    {
                                        label: 'View',
                                        onClick: (row) => {
                                            router.visit(rakeLoader.rakes.loading.url(row.id));
                                        },
                                    },
                                ]}
                                renderCell={(columnId, value, row) => {
                                    if (columnId === 'rake_number') {
                                        return <span className="font-medium">{String(value ?? row.rake_number)}</span>;
                                    }
                                    if (columnId === 'siding_label') {
                                        return row.siding_label ?? '—';
                                    }
                                    if (columnId === 'loading_date') {
                                        return row.loading_date
                                            ? new Date(row.loading_date).toLocaleDateString()
                                            : '—';
                                    }
                                    return undefined;
                                }}
                            />
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
