import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Mail } from 'lucide-react';

interface MailJob {
    id: number;
    [key: string]: unknown;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedData {
    data: MailJob[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    jobs: PaginatedData;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Marketing', href: '/marketing' },
    { title: 'Mail Status', href: '/mail-status' },
];

function columnHeaders(data: MailJob[]): string[] {
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

export default function MailStatusIndexPage({ jobs }: Props) {
    const columns = columnHeaders(jobs.data);

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Mail Status" />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="mail-status-page"
            >
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Mail Status
                    </h1>
                    <p className="text-muted-foreground">
                        {jobs.total} results
                    </p>
                </div>

                {jobs.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                        <div className="rounded-full bg-muted p-4">
                            <Mail className="size-8 text-muted-foreground" />
                        </div>
                        <div>
                            <p className="font-medium">No mail jobs</p>
                            <p className="text-sm text-muted-foreground">
                                Mail job statuses will appear here once emails
                                are sent.
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
                                    {jobs.data.map((job) => (
                                        <tr
                                            key={job.id}
                                            className="border-b last:border-0 hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                {job.id}
                                            </td>
                                            {columns.map((col) => (
                                                <td
                                                    key={col}
                                                    className="px-4 py-3 text-muted-foreground"
                                                >
                                                    {formatValue(job[col])}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {jobs.last_page > 1 && (
                            <div className="flex items-center justify-center gap-1">
                                {jobs.links.map((link, index) => (
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
