import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { useCan } from '@/hooks/use-can';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { ClipboardList, Plus, Search } from 'lucide-react';

interface SidingRef {
    id: number;
    name: string;
}

interface SidingOption {
    id: number;
    name: string;
}

interface ReportRow {
    id: number;
    siding_id: number | null;
    report_date: string;
    total_indent_raised: number;
    indent_available: number;
    siding?: SidingRef | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedReports {
    data: ReportRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

interface FiltersState {
    siding_id: number | null;
    date_from: string | null;
    date_to: string | null;
}

interface Props {
    reports: PaginatedReports;
    sidings: SidingOption[];
    filters: FiltersState;
}

function formatDate(value: string): string {
    if (!value) return '';
    return value.slice(0, 10);
}

function sidingLabel(row: ReportRow): string {
    return row.siding?.name?.trim() || '—';
}

export default function SidingPreIndentReportsIndex({
    reports,
    sidings,
    filters,
}: Props) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const canCreate = useCan('sections.siding_pre_indent_reports.create');

    const [sidingId, setSidingId] = useState(
        filters.siding_id != null ? String(filters.siding_id) : '',
    );
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    useEffect(() => {
        setSidingId(filters.siding_id != null ? String(filters.siding_id) : '');
        setDateFrom(filters.date_from ?? '');
        setDateTo(filters.date_to ?? '');
    }, [filters.siding_id, filters.date_from, filters.date_to]);

    const applyFilters = () => {
        const q: Record<string, string> = {
            date_from: dateFrom,
            date_to: dateTo,
        };
        if (sidingId) {
            q.siding_id = sidingId;
        }
        router.get('/siding-pre-indent-reports', q, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSidingId('');
        setDateFrom('');
        setDateTo('');
        router.get(
            '/siding-pre-indent-reports',
            { siding_id: '', date_from: '', date_to: '' },
            { replace: true },
        );
    };

    const hasActiveFilters = Boolean(
        filters.siding_id ||
            (filters.date_from && filters.date_from !== '') ||
            (filters.date_to && filters.date_to !== ''),
    );

    const emptyTableMessage = hasActiveFilters
        ? 'No reports match your filters.'
        : 'No reports yet.';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Siding Pre-Indent Report', href: '/siding-pre-indent-reports' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Siding Pre-Indent Report" />
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <ClipboardList className="text-muted-foreground size-7" />
                        <h1 className="text-2xl font-semibold">
                            Siding Pre-Indent Report
                        </h1>
                    </div>
                    {canCreate && (
                        <Button asChild data-pan="siding-pre-indent-report-new">
                            <Link href="/siding-pre-indent-reports/create">
                                <Plus className="mr-2 size-4" />
                                New report
                            </Link>
                        </Button>
                    )}
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <div className="flex flex-col gap-4 md:flex-row md:flex-wrap md:items-end">
                            <div className="grid gap-2 md:min-w-[200px]">
                                <Label htmlFor="filter_siding">Siding</Label>
                                <select
                                    id="filter_siding"
                                    value={sidingId}
                                    onChange={(e) => setSidingId(e.target.value)}
                                    className={cn(
                                        'border-input bg-background h-10 w-full rounded-md border px-3 py-2 text-sm',
                                        'focus-visible:ring-ring focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none',
                                    )}
                                >
                                    <option value="">All sidings</option>
                                    {sidings.map((s) => (
                                        <option key={s.id} value={String(s.id)}>
                                            {s.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors?.siding_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="filter_date_from">From date</Label>
                                <Input
                                    id="filter_date_from"
                                    type="date"
                                    value={dateFrom}
                                    onChange={(e) => setDateFrom(e.target.value)}
                                />
                                <InputError message={errors?.date_from} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="filter_date_to">To date</Label>
                                <Input
                                    id="filter_date_to"
                                    type="date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                />
                                <InputError message={errors?.date_to} />
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    onClick={applyFilters}
                                    data-pan="siding-pre-indent-report-filter-apply"
                                >
                                    <Search className="mr-2 size-4" />
                                    Apply
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={clearFilters}
                                    data-pan="siding-pre-indent-report-filter-clear"
                                >
                                    Clear
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Siding</TableHead>
                                <TableHead>Total indent raised</TableHead>
                                <TableHead>Indent available</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {reports.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        {emptyTableMessage}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                reports.data.map((row) => (
                                    <TableRow
                                        key={row.id}
                                        className="hover:bg-muted/50 cursor-pointer"
                                        onClick={() =>
                                            router.visit(
                                                `/siding-pre-indent-reports/${row.id}`,
                                            )
                                        }
                                        data-pan="siding-pre-indent-report-index-row"
                                    >
                                        <TableCell className="px-4 py-3">
                                            {formatDate(row.report_date)}
                                        </TableCell>
                                        <TableCell className="px-4 py-3">
                                            {sidingLabel(row)}
                                        </TableCell>
                                        <TableCell className="px-4 py-3">
                                            {row.total_indent_raised}
                                        </TableCell>
                                        <TableCell className="px-4 py-3">
                                            {row.indent_available}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {reports.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {reports.links.map((link, index) => {
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
                )}
            </div>
        </AppLayout>
    );
}
