import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { type BreadcrumbItem } from '@/types';
import { cn } from '@/lib/utils';

interface SidingOption {
    id: number;
    name: string;
}

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
    sidings: SidingOption[];
}

const textAreaClassName = cn(
    'placeholder:text-muted-foreground flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none transition-[color,box-shadow] md:text-sm',
    'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
    'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30',
);

export default function SidingPreIndentReportEdit({ report, sidings }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Siding Pre-Indent Report', href: '/siding-pre-indent-reports' },
        {
            title: 'Edit',
            href: `/siding-pre-indent-reports/${report.id}/edit`,
        },
    ];

    const { data, setData, put, processing, errors } = useForm({
        siding_id: report.siding_id,
        report_date: report.report_date,
        total_indent_raised: report.total_indent_raised,
        indent_available: report.indent_available,
        loading_status_text: report.loading_status_text ?? '',
        indent_details_text: report.indent_details_text ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/siding-pre-indent-reports/${report.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Siding Pre-Indent Report" />
            <div className="mx-auto max-w-2xl space-y-6">
                <h1 className="text-2xl font-semibold">Edit report</h1>
                <form onSubmit={submit} className="space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="siding_id">Siding (optional)</Label>
                        <select
                            id="siding_id"
                            value={data.siding_id === null ? '' : String(data.siding_id)}
                            onChange={(e) =>
                                setData(
                                    'siding_id',
                                    e.target.value === ''
                                        ? null
                                        : Number(e.target.value),
                                )
                            }
                            className={cn(
                                'border-input bg-background ring-offset-background',
                                'focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm',
                                'focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none',
                            )}
                        >
                            <option value="">None</option>
                            {sidings.map((s) => (
                                <option key={s.id} value={String(s.id)}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.siding_id} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="report_date">Report date</Label>
                        <Input
                            id="report_date"
                            type="date"
                            value={data.report_date}
                            onChange={(e) => setData('report_date', e.target.value)}
                            required
                        />
                        <InputError message={errors.report_date} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="total_indent_raised">
                            Total indent raised
                        </Label>
                        <Input
                            id="total_indent_raised"
                            type="number"
                            min={0}
                            step={1}
                            value={data.total_indent_raised}
                            onChange={(e) =>
                                setData(
                                    'total_indent_raised',
                                    Number(e.target.value),
                                )
                            }
                            required
                        />
                        <InputError message={errors.total_indent_raised} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="indent_available">Indent available</Label>
                        <Input
                            id="indent_available"
                            type="number"
                            min={0}
                            step={1}
                            value={data.indent_available}
                            onChange={(e) =>
                                setData(
                                    'indent_available',
                                    Number(e.target.value),
                                )
                            }
                            required
                        />
                        <InputError message={errors.indent_available} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="loading_status_text">
                            Loading status
                        </Label>
                        <textarea
                            id="loading_status_text"
                            rows={4}
                            className={cn(textAreaClassName, 'min-h-[100px]')}
                            value={data.loading_status_text}
                            onChange={(e) =>
                                setData('loading_status_text', e.target.value)
                            }
                        />
                        <InputError message={errors.loading_status_text} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="indent_details_text">
                            Indent details
                        </Label>
                        <textarea
                            id="indent_details_text"
                            rows={6}
                            className={cn(textAreaClassName, 'min-h-[120px]')}
                            value={data.indent_details_text}
                            onChange={(e) =>
                                setData('indent_details_text', e.target.value)
                            }
                        />
                        <InputError message={errors.indent_details_text} />
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            data-pan="siding-pre-indent-report-edit-submit"
                        >
                            Update
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={`/siding-pre-indent-reports/${report.id}`}
                            >
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
