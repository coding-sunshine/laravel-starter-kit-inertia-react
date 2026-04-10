import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { useCan } from '@/hooks/use-can';
import { type BreadcrumbItem } from '@/types';
import { ClipboardList, Plus } from 'lucide-react';

interface SidingRef {
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

interface Props {
    reports: PaginatedReports;
}

function formatDate(value: string): string {
    if (!value) return '';
    return value.slice(0, 10);
}

export default function SidingPreIndentReportsIndex({ reports }: Props) {
    const canCreate = useCan('sections.siding_pre_indent_reports.create');

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

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Total indent raised</TableHead>
                                <TableHead>Indent available</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {reports.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={3}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No reports yet.
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
