import { router } from '@inertiajs/react';
import { useState } from 'react';
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
    dispatchReports: DispatchReport[];
    filters: Filters;
    flashSuccess?: string;
}

function formatDecimal(value: number | string | null): string {
    if (value === null || value === undefined) return '—';
    const n = typeof value === 'string' ? parseFloat(value) : value;
    return Number.isNaN(n) ? '—' : n.toLocaleString('en-IN', { maximumFractionDigits: 2 });
}

/** Weighbridge / daily_vehicle_entries sourced columns when missing */
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

export default function DPRTab({ dispatchReports, filters, flashSuccess }: DPRTabProps) {
    const [isGenerating, setIsGenerating] = useState(false);
    const [generationMode, setGenerationMode] = useState<'sync' | 'queue'>('sync');

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

    return (
        <Card>
            <CardHeader>
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <CardTitle className="flex items-center gap-2">
                            <FileBarChart className="h-5 w-5" />
                            Dispatch Report (DPR)
                            {dateRangeLabel && (
                                <span className="text-sm font-normal text-muted-foreground">
                                    {dateRangeLabel}
                                </span>
                            )}
                            {dispatchReports.length > 0 && (
                                <span className="text-sm font-normal text-muted-foreground">
                                    {' '}
                                    · {dispatchReports.length}{' '}
                                    {dispatchReports.length === 1 ? 'row' : 'rows'} (full list, not paginated)
                                </span>
                            )}
                        </CardTitle>
                        <CardDescription>
                            Built from mine dispatch; weighbridge fields fill when daily vehicle entries exist,
                            otherwise they show N/A. The table lists every DPR row returned for your filters—there
                            is no row limit. Click Generate DPR to rebuild from dispatches.
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

                {dispatchReports.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-12 text-center text-muted-foreground">
                        <FileBarChart className="h-12 w-12 mb-4 opacity-50" />
                        <p className="text-sm">No DPR records yet.</p>
                        <p className="text-xs mt-2">
                            Click &quot;Generate DPR&quot; to build rows from dispatches (weighbridge data when
                            available).
                        </p>
                    </div>
                ) : (
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
                                {dispatchReports.map((row) => (
                                    <TableRow key={row.id}>
                                        <TableCell className="font-medium">{row.id}</TableCell>
                                        <TableCell>{row.ref_no ?? '—'}</TableCell>
                                        <TableCell>{row.e_challan_no ?? '—'}</TableCell>
                                        <TableCell>{row.issued_on ? format(new Date(row.issued_on), 'dd MMM yyyy') : '—'}</TableCell>
                                        <TableCell>{row.truck_no ?? '—'}</TableCell>
                                        <TableCell>{row.shift ?? '—'}</TableCell>
                                        <TableCell>{row.date ? format(new Date(row.date), 'dd MMM yyyy') : '—'}</TableCell>
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
                )}
            </CardContent>
        </Card>
    );
}
