import Heading from '@/components/heading';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Scale } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Rake {
    id: number;
    rake_number: string;
    siding?: Siding | null;
}

interface Point {
    point: string;
    value_a: number | null;
    value_b: number | null;
    variance_mt: number | null;
    variance_pct: number | null;
    status: string;
}

interface Row {
    rake: Rake;
    overall_status: string;
    points: Point[];
}

interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    rakes: { data: Rake[]; links: PaginatorLink[]; last_page: number };
    rows: Row[];
    summary: { pending: number; matched: number; mismatched: number };
    sidings: Siding[];
}

export default function ReconciliationIndex({
    rakes,
    rows,
    summary,
    sidings,
}: Props) {
    const { url } = usePage();
    const q = new URLSearchParams(url.split('?')[1] || '');
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Reconciliation', href: '/reconciliation' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reconciliation" />
            <div className="space-y-6">
                <Heading
                    title="Five-point reconciliation"
                    description="Mine vs Siding, Siding vs Rake, Rake vs Weighment, Weighment vs RR, RR vs Power Plant"
                />
                <div className="flex flex-wrap items-center gap-2">
                    <Link href="/reconciliation/power-plant-receipts">
                        <Button variant="outline">Power plant receipts</Button>
                    </Link>
                    <Link href="/reconciliation/power-plant-receipts/create">
                        <Button>Add power plant receipt</Button>
                    </Link>
                </div>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Pending (no weighment)</CardDescription>
                            <CardTitle className="text-lg">{summary.pending}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Matched this page</CardDescription>
                            <CardTitle className="text-lg">{summary.matched}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Mismatched this page</CardDescription>
                            <CardTitle className="text-lg">{summary.mismatched}</CardTitle>
                        </CardHeader>
                    </Card>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Rakes with weighment</CardTitle>
                        <CardDescription>Filter by siding</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            method="get"
                            className="mb-6 flex flex-wrap items-end gap-3"
                            onSubmit={(e) => {
                                e.preventDefault();
                                const form = e.currentTarget;
                                const siding = (form.querySelector('[name=siding_id]') as HTMLSelectElement)?.value;
                                const params = new URLSearchParams();
                                if (siding) params.set('siding_id', siding);
                                router.get('/reconciliation', Object.fromEntries(params));
                            }}
                        >
                            <div className="grid gap-2">
                                <label className="text-sm font-medium">Siding</label>
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
                        {rows.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                <Scale className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <p>No rakes with weighment found.</p>
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto rounded-md border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-5 py-3.5 text-left font-medium">Rake</th>
                                                <th className="px-5 py-3.5 text-left font-medium">Siding</th>
                                                <th className="px-5 py-3.5 text-left font-medium">Status</th>
                                                <th className="px-5 py-3.5 text-right font-medium">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {rows.map((row) => (
                                                <tr key={row.rake.id} className="border-b last:border-0 hover:bg-muted/30">
                                                    <td className="px-5 py-3.5 font-medium">{row.rake.rake_number}</td>
                                                    <td className="px-5 py-3.5">{row.rake.siding?.name ?? '—'}</td>
                                                    <td className="px-5 py-3.5">
                                                        <span
                                                            className={
                                                                row.overall_status === 'MAJOR_DIFF'
                                                                    ? 'text-red-600 dark:text-red-400'
                                                                    : row.overall_status === 'MINOR_DIFF'
                                                                      ? 'text-amber-600 dark:text-amber-400'
                                                                      : ''
                                                            }
                                                        >
                                                            {row.overall_status}
                                                        </span>
                                                    </td>
                                                    <td className="px-5 py-3.5 text-right">
                                                        <Link href={`/reconciliation/${row.rake.id}`}>
                                                            <Button variant="outline" size="sm">
                                                                Detail
                                                            </Button>
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                {rakes.last_page > 1 && (
                                    <nav className="mt-6 flex flex-wrap items-center justify-center gap-4 pt-2">
                                        {rakes.links.map((link) => (
                                            <button
                                                key={link.label}
                                                type="button"
                                                disabled={!link.url}
                                                className="rounded-md border border-input px-4 py-2.5 text-sm disabled:opacity-50"
                                                onClick={() => link.url && router.get(link.url)}
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
