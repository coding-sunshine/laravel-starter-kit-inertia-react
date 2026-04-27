import { router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { FileBarChart, RefreshCw } from 'lucide-react';
import { format } from 'date-fns';
import type { DispatchReport } from './Index';
import type { Filters } from './types';

interface DPRTabProps {
    filters: Filters;
    flashSuccess?: string;
}

interface DprPaginatorPayload {
    data: DispatchReport[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

function formatDecimal(value: number | string | null): string {
    if (value === null || value === undefined) return '—';
    const n = typeof value === 'string' ? parseFloat(value) : value;
    return Number.isNaN(n) ? '—' : n.toLocaleString('en-IN', { maximumFractionDigits: 2 });
}

function formatDveDecimal(value: number | string | null | undefined): string {
    if (value === null || value === undefined) return 'N/A';
    const n = typeof value === 'string' ? parseFloat(value) : value;
    return Number.isNaN(n) ? 'N/A' : n.toLocaleString('en-IN', { maximumFractionDigits: 2 });
}

function formatDveDateTime(value: string | null | undefined): string {
    if (!value) return 'N/A';
    try {
        const d = new Date(value);
        return format(d, 'dd MMM yyyy HH:mm');
    } catch {
        return value;
    }
}

function dveText(value: string | null | undefined): string {
    if (value === null || value === undefined || value === '') return 'N/A';
    return value;
}

function buildDprQueryString(filters: Filters, page: number): string {
    const qs = new URLSearchParams();
    if (filters.date_from) qs.set('date_from', filters.date_from);
    if (filters.date_to) qs.set('date_to', filters.date_to);
    if (filters.date && !filters.date_from && !filters.date_to) qs.set('date', filters.date);
    qs.set('page', String(page));
    return qs.toString();
}

export default function DPRTab({ filters, flashSuccess }: DPRTabProps) {
    const [isGenerating, setIsGenerating] = useState(false);
    const [generationMode, setGenerationMode] = useState<'sync' | 'queue'>('sync');
    const [pagination, setPagination] = useState<DprPaginatorPayload | null>(null);
    const [loading, setLoading] = useState(true);
    const [fetchError, setFetchError] = useState<string | null>(null);

    const loadPage = useCallback(
        async (page: number) => {
            setLoading(true);
            setFetchError(null);
            try {
                const res = await fetch(`/vehicle-dispatch/dpr-data?${buildDprQueryString(filters, page)}`, {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (res.status === 401 || res.status === 403) {
                    setFetchError('You do not have permission to load DPR data.');
                    setPagination(null);
                    return;
                }
                if (!res.ok) {
                    setFetchError('Could not load DPR data. Try again.');
                    setPagination(null);
                    return;
                }
                const json = (await res.json()) as DprPaginatorPayload;
                setPagination(json);
            } catch {
                setFetchError('Could not load DPR data. Try again.');
                setPagination(null);
            } finally {
                setLoading(false);
            }
        },
        [filters],
    );

    useEffect(() => {
        void loadPage(1);
    }, [loadPage, flashSuccess]);

    const handleGenerate = () => {
        setIsGenerating(true);
        const payload: Record<string, unknown> = {
            _filters: filters,
            mode: generationMode,
        };
        router.post('/dispatch-reports/generate', payload, {
            preserveScroll: true,
            onFinish: () => setIsGenerating(false),
        });
    };

    const dateRangeLabel = (() => {
        if (filters.date_from && filters.date_to) {
            const from = format(new Date(filters.date_from), 'dd MMM yyyy');
            const to = format(new Date(filters.date_to), 'dd MMM yyyy');
            return `(${from} - ${to})`;
        }
        if (filters.date) {
            return `(${format(new Date(filters.date), 'dd MMM yyyy')})`;
        }
        return '';
    })();

    const rows = pagination?.data ?? [];
    const total = pagination?.total ?? 0;

    return (
        <Card>
            <CardHeader>
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <CardTitle className="flex flex-wrap items-center gap-2">
                            <FileBarChart className="h-5 w-5" />
                            Dispatch Report (DPR)
                            {dateRangeLabel && (
                                <span className="text-sm font-normal text-muted-foreground">
                                    {dateRangeLabel}
                                </span>
                            )}
                            {!loading && total > 0 && (
                                <span className="text-sm font-normal text-muted-foreground">
                                    · {total} {total === 1 ? 'row' : 'rows'} total (paginated)
                                </span>
                            )}
                        </CardTitle>
                        <CardDescription>
                            Built from mine dispatch; weighbridge fields fill when daily vehicle entries exist,
                            otherwise they show N/A. Data loads when you open this tab. Click Generate DPR to rebuild
                            from dispatches.
                        </CardDescription>
                    </div>
                    <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:flex-wrap">
                        <Button
                            onClick={handleGenerate}
                            disabled={isGenerating}
                            data-pan="vehicle-dispatch-generate-dpr"
                        >
                            <RefreshCw className={`h-4 w-4 mr-2 ${isGenerating ? 'animate-spin' : ''}`} />
                            {isGenerating ? 'Generating...' : 'Generate DPR'}
                        </Button>
                        <div className="w-full sm:w-56">
                            <Select
                                value={generationMode}
                                onValueChange={(value: 'sync' | 'queue') => setGenerationMode(value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Generation mode" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="sync">Generate now (Sync)</SelectItem>
                                    <SelectItem value="queue">Generate in background (Queue)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                {flashSuccess && (
                    <div
                        className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950/30 dark:text-green-200"
                        role="status"
                    >
                        {flashSuccess}
                    </div>
                )}

                {fetchError && (
                    <div
                        className="rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive"
                        role="alert"
                    >
                        {fetchError}
                    </div>
                )}

                {loading && (
                    <div className="flex justify-center py-12 text-sm text-muted-foreground">Loading DPR…</div>
                )}

                {!loading && rows.length === 0 && !fetchError && (
                    <div className="flex flex-col items-center justify-center py-12 text-center text-muted-foreground">
                        <FileBarChart className="h-12 w-12 mb-4 opacity-50" />
                        <p className="text-sm">No DPR records yet.</p>
                        <p className="text-xs mt-2">
                            Click &quot;Generate DPR&quot; to build rows from dispatches (weighbridge data when
                            available).
                        </p>
                    </div>
                )}

                {!loading && rows.length > 0 && (
                    <>
                        <div className="rounded-md border overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12">SL NO</TableHead>
                                        <TableHead>REF</TableHead>
                                        <TableHead>E CHALLAN NO</TableHead>
                                        <TableHead>ISSUED ON</TableHead>
                                        <TableHead>TRUCK NO</TableHead>
                                        <TableHead>SHIFT</TableHead>
                                        <TableHead>DATE</TableHead>
                                        <TableHead>TRIPS</TableHead>
                                        <TableHead>WO.NO</TableHead>
                                        <TableHead>TRANSPORT NAME</TableHead>
                                        <TableHead>MINERAL WT</TableHead>
                                        <TableHead>GROSS WT</TableHead>
                                        <TableHead>TARE WT</TableHead>
                                        <TableHead>NET WT</TableHead>
                                        <TableHead>TYRES</TableHead>
                                        <TableHead>COAL TON VAR</TableHead>
                                        <TableHead>REACHED DATE & TIME</TableHead>
                                        <TableHead>WB</TableHead>
                                        <TableHead>TRIP ID NO</TableHead>
                                        <TableHead>SIDING</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.map((row) => (
                                        <TableRow key={row.id}>
                                            <TableCell className="font-medium">{row.id}</TableCell>
                                            <TableCell>{row.ref_no ?? '—'}</TableCell>
                                            <TableCell>{row.e_challan_no ?? '—'}</TableCell>
                                            <TableCell>
                                                {row.issued_on ? format(new Date(row.issued_on), 'dd MMM yyyy') : '—'}
                                            </TableCell>
                                            <TableCell>{row.truck_no ?? '—'}</TableCell>
                                            <TableCell>{row.shift ?? '—'}</TableCell>
                                            <TableCell>
                                                {row.date ? format(new Date(row.date), 'dd MMM yyyy') : '—'}
                                            </TableCell>
                                            <TableCell>{row.trips ?? '—'}</TableCell>
                                            <TableCell>{row.wo_no ?? '—'}</TableCell>
                                            <TableCell>{dveText(row.transport_name)}</TableCell>
                                            <TableCell>{formatDecimal(row.mineral_wt)}</TableCell>
                                            <TableCell>{formatDveDecimal(row.gross_wt_siding_rec_wt)}</TableCell>
                                            <TableCell>{formatDveDecimal(row.tare_wt)}</TableCell>
                                            <TableCell>{formatDveDecimal(row.net_wt_siding_rec_wt)}</TableCell>
                                            <TableCell>{row.tyres ?? '—'}</TableCell>
                                            <TableCell>{formatDveDecimal(row.coal_ton_variation)}</TableCell>
                                            <TableCell>{formatDveDateTime(row.reached_datetime)}</TableCell>
                                            <TableCell>{dveText(row.wb)}</TableCell>
                                            <TableCell>{dveText(row.trip_id_no)}</TableCell>
                                            <TableCell>
                                                {row.siding
                                                    ? `${row.siding.name} (${row.siding.code})`
                                                    : row.siding_id}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {pagination && pagination.last_page > 1 && (
                            <div className="flex justify-center">
                                <div className="flex flex-wrap gap-2">
                                    {pagination.links.map((link, index) => {
                                        const pageMatch = link.url?.match(/[?&]page=(\d+)/);
                                        const pageNumber = pageMatch ? parseInt(pageMatch[1], 10) : null;

                                        return (
                                            <button
                                                key={index}
                                                type="button"
                                                onClick={() => {
                                                    if (pageNumber !== null) {
                                                        void loadPage(pageNumber);
                                                    }
                                                }}
                                                disabled={!link.url || pageNumber === null}
                                                className={`px-3 py-2 rounded text-sm ${
                                                    link.active
                                                        ? 'bg-primary text-primary-foreground'
                                                        : link.url
                                                          ? 'bg-muted hover:bg-muted/80'
                                                          : 'bg-muted/50 text-muted-foreground cursor-not-allowed'
                                                }`}
                                            >
                                                {link.label
                                                    .replace('&laquo;', '«')
                                                    .replace('&raquo;', '»')}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                    </>
                )}
            </CardContent>
        </Card>
    );
}
