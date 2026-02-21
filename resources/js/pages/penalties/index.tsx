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
import { Scale } from 'lucide-react';
import { Fragment } from 'react';

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

interface CalculationBreakdown {
    formula?: string;
    demurrage_hours?: number;
    weight_mt?: number;
    rate_per_mt_hour?: number;
    free_hours?: number | null;
    dwell_hours?: number | null;
}

interface Penalty {
    id: number;
    rake_id: number;
    penalty_type: string;
    penalty_amount: string;
    penalty_status: string;
    penalty_date: string;
    description: string | null;
    calculation_breakdown?: CalculationBreakdown | null;
    rake?: Rake;
}

interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    penalties: {
        data: Penalty[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        links: PaginatorLink[];
    };
    sidings: Siding[];
    demurrage_rate_per_mt_hour: number;
}

export default function PenaltiesIndex({
    penalties,
    sidings,
    demurrage_rate_per_mt_hour,
}: Props) {
    const { url } = usePage();
    const q = new URLSearchParams(url.split('?')[1] || '');
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Penalties', href: '/penalties' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Penalties" />
            <div className="space-y-6">
                <Heading
                    title="Penalties"
                    description="Penalty register by rake and siding"
                />
                <RrmcsGuidance
                    title="What this section is for"
                    before="Penalty amounts calculated manually from RR after the fact — often discovered days late."
                    after="Real-time penalty tracking with automated calculation (hours × MT × rate); demurrage alerts warn you BEFORE penalties hit."
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Penalty register</CardTitle>
                        <CardDescription>
                            Filter by siding or status.{' '}
                            <GlossaryTerm term="Demurrage">
                                Demurrage
                            </GlossaryTerm>{' '}
                            formula: hours over free time × weight (
                            <GlossaryTerm term="MT">MT</GlossaryTerm>) × ₹
                            {demurrage_rate_per_mt_hour}/MT/h.
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
                                const status = (
                                    form.querySelector(
                                        '[name=status]',
                                    ) as HTMLSelectElement
                                )?.value;
                                const params = new URLSearchParams();
                                if (siding) params.set('siding_id', siding);
                                if (status) params.set('status', status);
                                router.get(
                                    '/penalties',
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
                            <div className="grid gap-2">
                                <label className="text-sm font-medium">
                                    Status
                                </label>
                                <select
                                    name="status"
                                    defaultValue={q.get('status') ?? ''}
                                    className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                >
                                    <option value="">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="incurred">Incurred</option>
                                    <option value="disputed">Disputed</option>
                                    <option value="waived">Waived</option>
                                </select>
                            </div>
                            <Button type="submit" variant="secondary">
                                Filter
                            </Button>
                        </form>
                        {penalties.data.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                <Scale className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <p>No penalties found.</p>
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto rounded-md border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/50">
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Rake
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Siding
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Type
                                                </th>
                                                <th className="px-5 py-3.5 text-right font-medium">
                                                    Amount
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Status
                                                </th>
                                                <th className="px-5 py-3.5 text-left font-medium">
                                                    Date
                                                </th>
                                                <th className="px-5 py-3.5 text-right font-medium">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {penalties.data.map((p) => (
                                                <Fragment key={p.id}>
                                                    <tr className="border-b last:border-0 hover:bg-muted/30">
                                                        <td className="px-5 py-3.5 font-medium">
                                                            {p.rake
                                                                ?.rake_number ??
                                                                '-'}
                                                        </td>
                                                        <td className="px-5 py-3.5">
                                                            {p.rake?.siding
                                                                ?.name ?? '-'}
                                                        </td>
                                                        <td className="px-5 py-3.5">
                                                            {p.penalty_type}
                                                        </td>
                                                        <td className="px-5 py-3.5 text-right">
                                                            {p.penalty_amount}
                                                        </td>
                                                        <td className="px-5 py-3.5">
                                                            {p.penalty_status}
                                                        </td>
                                                        <td className="px-5 py-3.5">
                                                            {p.penalty_date}
                                                        </td>
                                                        <td className="px-5 py-3.5 text-right">
                                                            <Link
                                                                href={`/rakes/${p.rake_id}`}
                                                            >
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                >
                                                                    View rake
                                                                </Button>
                                                            </Link>
                                                        </td>
                                                    </tr>
                                                    {p.calculation_breakdown && (
                                                        <tr
                                                            key={`${p.id}-breakdown`}
                                                            className="border-b bg-muted/20 last:border-0"
                                                        >
                                                            <td
                                                                colSpan={7}
                                                                className="px-5 py-3 text-sm text-muted-foreground"
                                                            >
                                                                <span className="font-medium text-foreground">
                                                                    How
                                                                    calculated:{' '}
                                                                </span>
                                                                {
                                                                    p
                                                                        .calculation_breakdown
                                                                        .formula
                                                                }
                                                                {p
                                                                    .calculation_breakdown
                                                                    .demurrage_hours !=
                                                                    null && (
                                                                    <>
                                                                        {' '}
                                                                        ={' '}
                                                                        {
                                                                            p
                                                                                .calculation_breakdown
                                                                                .demurrage_hours
                                                                        }{' '}
                                                                        h ×{' '}
                                                                        {
                                                                            p
                                                                                .calculation_breakdown
                                                                                .weight_mt
                                                                        }{' '}
                                                                        MT × ₹
                                                                        {
                                                                            p
                                                                                .calculation_breakdown
                                                                                .rate_per_mt_hour
                                                                        }
                                                                        /MT/h
                                                                    </>
                                                                )}
                                                                {p
                                                                    .calculation_breakdown
                                                                    .free_hours !=
                                                                    null && (
                                                                    <>
                                                                        {' '}
                                                                        (free
                                                                        time:{' '}
                                                                        {
                                                                            p
                                                                                .calculation_breakdown
                                                                                .free_hours
                                                                        }{' '}
                                                                        h
                                                                    </>
                                                                )}
                                                                {p
                                                                    .calculation_breakdown
                                                                    .dwell_hours !=
                                                                    null && (
                                                                    <>
                                                                        , dwell:{' '}
                                                                        {
                                                                            p
                                                                                .calculation_breakdown
                                                                                .dwell_hours
                                                                        }{' '}
                                                                        h
                                                                    </>
                                                                )}
                                                                {p
                                                                    .calculation_breakdown
                                                                    .free_hours !=
                                                                    null && ')'}
                                                            </td>
                                                        </tr>
                                                    )}
                                                </Fragment>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                {penalties.last_page > 1 && (
                                    <nav className="mt-6 flex flex-wrap items-center justify-center gap-4 pt-2">
                                        {penalties.prev_page_url ? (
                                            <Link
                                                href={penalties.prev_page_url}
                                                className="text-sm font-medium text-foreground underline underline-offset-4"
                                            >
                                                Previous
                                            </Link>
                                        ) : null}
                                        <span className="text-sm text-muted-foreground">
                                            Page {penalties.current_page} of{' '}
                                            {penalties.last_page}
                                        </span>
                                        {penalties.next_page_url ? (
                                            <Link
                                                href={penalties.next_page_url}
                                                className="text-sm font-medium text-foreground underline underline-offset-4"
                                            >
                                                Next
                                            </Link>
                                        ) : null}
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
