import { GlossaryTerm } from '@/components/glossary-term';
import Heading from '@/components/heading';
import { RrmcsGuidance } from '@/components/rrmcs-guidance';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FileText } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Rake {
    id: number;
    rake_number: string;
    siding?: Siding;
}

interface RrDocument {
    id: number;
    rake_id: number;
    rr_number: string;
    rr_received_date: string;
    rr_weight_mt: string | null;
    document_status: string;
    rake?: Rake;
}

interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    rrDocuments: {
        data: RrDocument[];
        current_page: number;
        last_page: number;
        prev_page_url?: string | null;
        next_page_url?: string | null;
        links: PaginatorLink[];
    };
    sidings: Siding[];
}

export default function RailwayReceiptsIndex({ rrDocuments, sidings }: Props) {
    const { url } = usePage();
    const q = new URLSearchParams(url.split('?')[1] || '');
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Railway Receipts', href: '/railway-receipts' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Railway Receipts" />
            <div className="space-y-6">
                <Heading
                    title="Railway Receipts"
                    description="RR documents and receipts by rake"
                />
                <div className="flex flex-wrap items-center gap-2">
                    <Link href="/railway-receipts/create">
                        <Button>
                            <FileText className="mr-2 size-4" />
                            Add RR document
                        </Button>
                    </Link>
                </div>
                <RrmcsGuidance
                    title="What this section is for"
                    before="RR documents filed in physical folders; FNR, freight, and wagon details copied manually into Excel."
                    after="Upload RR PDF, auto-parsed into structured data — FNR, freight, charges, wagon table all searchable."
                />
                <Card>
                    <CardHeader>
                        <CardTitle>RR documents</CardTitle>
                        <CardDescription>
                            Filter by siding or rake
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            method="get"
                            className="mb-6 flex flex-wrap items-end gap-3"
                            onSubmit={(e) => {
                                e.preventDefault();
                                const form = e.currentTarget;
                                const siding = (
                                    form.querySelector(
                                        '[name=siding_id]',
                                    ) as HTMLSelectElement
                                )?.value;
                                const params = new URLSearchParams();
                                if (siding) params.set('siding_id', siding);
                                router.get(
                                    '/railway-receipts',
                                    Object.fromEntries(params),
                                );
                            }}
                        >
                            <div className="grid gap-2">
                                <label className="text-sm font-medium">
                                    Siding
                                </label>
                                <select
                                    name="siding_id"
                                    defaultValue={q.get('siding_id') ?? ''}
                                    className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                >
                                    <option value="">All</option>
                                    {sidings.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <Button type="submit" variant="secondary">
                                Filter
                            </Button>
                        </form>
                        {rrDocuments.data.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                <FileText className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <p>No RR documents found.</p>
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto rounded-md border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    RR number
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Rake
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Siding
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Received date
                                                </th>
                                                <th className="px-5 py-3.5 text-right font-medium">
                                                    Weight (<GlossaryTerm term="MT">MT</GlossaryTerm>)
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Status
                                                </th>
                                                <th className="px-5 py-3.5 text-right font-medium">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {rrDocuments.data.map((doc) => (
                                                <tr
                                                    key={doc.id}
                                                    className="border-b last:border-0 hover:bg-muted/30"
                                                >
                                                    <td className="px-5 py-3.5 font-medium">
                                                        {doc.rr_number}
                                                    </td>
                                                    <td className="px-5 py-3.5">
                                                        {doc.rake
                                                            ?.rake_number ??
                                                            '-'}
                                                    </td>
                                                    <td className="px-5 py-3.5">
                                                        {doc.rake?.siding
                                                            ?.name ?? '-'}
                                                    </td>
                                                    <td className="px-5 py-3.5">
                                                        {doc.rr_received_date}
                                                    </td>
                                                    <td className="px-5 py-3.5 text-right">
                                                        {doc.rr_weight_mt ??
                                                            '-'}
                                                    </td>
                                                    <td className="px-5 py-3.5">
                                                        {doc.document_status}
                                                    </td>
                                                    <td className="px-5 py-3.5 text-right">
                                                        <Link
                                                            href={`/railway-receipts/${doc.id}`}
                                                        >
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                            >
                                                                View
                                                            </Button>
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                {rrDocuments.last_page > 1 && (
                                    <nav className="mt-6 flex flex-wrap items-center justify-center gap-4 pt-2">
                                        {rrDocuments.links.map((link) => (
                                            <button
                                                key={link.label}
                                                type="button"
                                                disabled={!link.url}
                                                className="rounded-md border border-input px-4 py-2.5 text-sm disabled:opacity-50"
                                                onClick={() =>
                                                    link.url &&
                                                    router.get(link.url)
                                                }
                                            >
                                                {link.label}
                                            </button>
                                        ))}
                                    </nav>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
