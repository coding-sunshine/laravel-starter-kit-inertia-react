import { Head, Link, router } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { useCan } from '@/hooks/use-can';
import { type BreadcrumbItem } from '@/types';
import { Copy, Pencil } from 'lucide-react';

interface ReportPayload {
    id: number;
    siding_id: number | null;
    report_date: string;
    report_date_formatted: string;
    total_indent_raised: number;
    indent_available: number;
    loading_status_text: string | null;
    indent_details_text: string | null;
    heading_line: string;
    siding: { id: number; name: string } | null;
}

interface Props {
    report: ReportPayload;
}

/** Plain-text block for clipboard; matches operational template. */
function buildPreIndentReportCopyText(report: ReportPayload): string {
    const loading = report.loading_status_text ?? '';
    const details = report.indent_details_text ?? '';
    const lines = [
        report.heading_line,
        '',
        `Date- ${report.report_date_formatted}`,
        '',
        `(1) TOTAL INDENT RAISED:- ${report.total_indent_raised}`,
        '',
        '(2) LOADING STATUS:-',
        loading,
        '',
        `(3) INDENT AVAILABLE:- ${report.indent_available}`,
        details,
    ];

    return lines.join('\n');
}

export default function SidingPreIndentReportShow({ report }: Props) {
    const canUpdate = useCan('sections.siding_pre_indent_reports.update');
    const canDelete = useCan('sections.siding_pre_indent_reports.delete');
    const [copyDone, setCopyDone] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Siding Pre-Indent Report', href: '/siding-pre-indent-reports' },
        {
            title: report.report_date_formatted,
            href: `/siding-pre-indent-reports/${report.id}`,
        },
    ];

    const copyToClipboard = useCallback(async () => {
        const text = buildPreIndentReportCopyText(report);
        try {
            await navigator.clipboard.writeText(text);
            setCopyDone(true);
            window.setTimeout(() => setCopyDone(false), 2000);
        } catch {
            setCopyDone(false);
        }
    }, [report]);

    const confirmDelete = () => {
        if (
            !confirm(
                'Delete this report? This cannot be undone.',
            )
        ) {
            return;
        }
        router.delete(`/siding-pre-indent-reports/${report.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Report ${report.report_date_formatted}`} />
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">Report</h1>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            type="button"
                            onClick={(e) => {
                                e.preventDefault();
                                void copyToClipboard();
                            }}
                            data-pan="siding-pre-indent-report-copy"
                        >
                            <Copy className="mr-2 size-4" />
                            {copyDone ? 'Copied' : 'Copy'}
                        </Button>
                        {canUpdate && (
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={`/siding-pre-indent-reports/${report.id}/edit`}
                                >
                                    <Pencil className="mr-2 size-4" />
                                    Edit
                                </Link>
                            </Button>
                        )}
                        {canDelete && (
                            <Button
                                variant="destructive"
                                size="sm"
                                type="button"
                                onClick={confirmDelete}
                                data-pan="siding-pre-indent-report-delete"
                            >
                                Delete
                            </Button>
                        )}
                    </div>
                </div>

                <div className="bg-card text-card-foreground rounded-lg border p-6 font-sans text-base leading-relaxed">
                    <p className="mb-6 whitespace-pre-wrap font-semibold uppercase">
                        {report.heading_line}
                    </p>

                    <p className="mb-6">Date- {report.report_date_formatted}</p>

                    <p className="mb-2">
                        (1) TOTAL INDENT RAISED:- {report.total_indent_raised}
                    </p>

                    <p className="mb-1">(2) LOADING STATUS:-</p>
                    <p className="text-muted-foreground mb-6 whitespace-pre-wrap">
                        {report.loading_status_text ?? ''}
                    </p>

                    <p className="mb-1">
                        (3) INDENT AVAILABLE:- {report.indent_available}
                    </p>
                    <p className="text-muted-foreground whitespace-pre-wrap">
                        {report.indent_details_text ?? ''}
                    </p>
                </div>

                <Button variant="outline" asChild>
                    <Link href="/siding-pre-indent-reports">Back to list</Link>
                </Button>
            </div>
        </AppLayout>
    );
}
