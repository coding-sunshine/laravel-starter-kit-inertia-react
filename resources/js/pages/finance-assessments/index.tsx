import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { FileText } from 'lucide-react';

interface Assessment {
    id: number;
    [key: string]: unknown;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedData {
    data: Assessment[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    assessments: PaginatedData;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/contacts' },
    { title: 'Finance Assessments', href: '/finance-assessments' },
];

function columnHeaders(data: Assessment[]): string[] {
    if (data.length === 0) return [];
    return Object.keys(data[0]).filter((key) => key !== 'id');
}

function formatValue(value: unknown): string {
    if (value === null || value === undefined) return '—';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}/.test(value)) {
        return new Date(value).toLocaleDateString();
    }
    return String(value);
}

function formatHeader(key: string): string {
    return key
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

export default function FinanceAssessmentsIndexPage({
    assessments,
}: Props) {
    const columns = columnHeaders(assessments.data);

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Finance Assessments" />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="finance-assessments-page"
            >
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Finance Assessments
                    </h1>
                    <p className="text-muted-foreground">
                        {assessments.total} results
                    </p>
                </div>

                {assessments.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                        <div className="rounded-full bg-muted p-4">
                            <FileText className="size-8 text-muted-foreground" />
                        </div>
                        <div>
                            <p className="font-medium">
                                No finance assessments
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Assessments will appear here once created.
                            </p>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-lg border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="px-4 py-3 text-left font-medium">
                                            #
                                        </th>
                                        {columns.map((col) => (
                                            <th
                                                key={col}
                                                className="px-4 py-3 text-left font-medium"
                                            >
                                                {formatHeader(col)}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {assessments.data.map((assessment) => (
                                        <tr
                                            key={assessment.id}
                                            className="border-b last:border-0 hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                {assessment.id}
                                            </td>
                                            {columns.map((col) => (
                                                <td
                                                    key={col}
                                                    className="px-4 py-3 text-muted-foreground"
                                                >
                                                    {formatValue(
                                                        assessment[col],
                                                    )}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {assessments.last_page > 1 && (
                            <div className="flex items-center justify-center gap-1">
                                {assessments.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url ?? '#'}
                                        className={`rounded-md px-3 py-1.5 text-sm ${
                                            link.active
                                                ? 'bg-primary text-primary-foreground'
                                                : link.url
                                                  ? 'text-muted-foreground hover:bg-muted'
                                                  : 'cursor-not-allowed text-muted-foreground/50'
                                        }`}
                                        preserveScroll
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppSidebarLayout>
    );
}
