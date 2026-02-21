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
import { BarChart3, Bot, Check, Pencil, Scale, X } from 'lucide-react';
import { Fragment, useRef, useState } from 'react';

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
    responsible_party: string | null;
    root_cause: string | null;
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

const RESPONSIBLE_PARTIES = [
    { value: '', label: 'Unassigned' },
    { value: 'railway', label: 'Railway' },
    { value: 'siding', label: 'Siding' },
    { value: 'transporter', label: 'Transporter' },
    { value: 'plant', label: 'Plant' },
    { value: 'other', label: 'Other' },
];

const STATUS_COLORS: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    incurred: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    disputed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    waived: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
};

function InlineResponsibleParty({ penalty }: { penalty: Penalty }) {
    const handleChange = (value: string) => {
        router.patch(
            `/penalties/${penalty.id}`,
            { responsible_party: value || null },
            { preserveScroll: true },
        );
    };

    return (
        <select
            value={penalty.responsible_party ?? ''}
            onChange={(e) => handleChange(e.target.value)}
            className="rounded border border-input bg-background px-2 py-1 text-xs"
            data-pan="penalty-assign-responsibility"
        >
            {RESPONSIBLE_PARTIES.map((rp) => (
                <option key={rp.value} value={rp.value}>
                    {rp.label}
                </option>
            ))}
        </select>
    );
}

function InlineRootCause({ penalty }: { penalty: Penalty }) {
    const [editing, setEditing] = useState(false);
    const [value, setValue] = useState(penalty.root_cause ?? '');
    const inputRef = useRef<HTMLInputElement>(null);

    const save = () => {
        router.patch(
            `/penalties/${penalty.id}`,
            { root_cause: value || null },
            { preserveScroll: true },
        );
        setEditing(false);
    };

    const cancel = () => {
        setValue(penalty.root_cause ?? '');
        setEditing(false);
    };

    if (editing) {
        return (
            <div className="flex items-center gap-1">
                <input
                    ref={inputRef}
                    type="text"
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') save();
                        if (e.key === 'Escape') cancel();
                    }}
                    className="w-full max-w-xs rounded border border-input bg-background px-2 py-1 text-xs"
                    placeholder="Why did this penalty happen?"
                    autoFocus
                    data-pan="penalty-set-root-cause"
                />
                <button onClick={save} className="rounded p-0.5 hover:bg-muted" type="button">
                    <Check className="h-3.5 w-3.5 text-green-600" />
                </button>
                <button onClick={cancel} className="rounded p-0.5 hover:bg-muted" type="button">
                    <X className="h-3.5 w-3.5 text-muted-foreground" />
                </button>
            </div>
        );
    }

    return (
        <button
            onClick={() => setEditing(true)}
            className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
            type="button"
        >
            {penalty.root_cause ? (
                <span className="max-w-xs truncate text-foreground">{penalty.root_cause}</span>
            ) : (
                <span className="italic">Add root cause</span>
            )}
            <Pencil className="h-3 w-3 shrink-0" />
        </button>
    );
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

    const hasActiveFilters = q.has('type') || q.has('from') || q.has('to') || q.has('siding_id') || q.has('status') || q.has('responsible_party');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Penalties" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Penalties"
                        description="Penalty register by rake and siding"
                    />
                    <Link href="/penalties/analytics">
                        <Button variant="outline" size="sm" data-pan="penalty-analytics-tab">
                            <BarChart3 className="mr-1.5 h-4 w-4" />
                            Analytics
                        </Button>
                    </Link>
                </div>
                <RrmcsGuidance
                    title="What this section is for"
                    before="Penalty amounts calculated manually from RR after the fact — often discovered days late."
                    after="Real-time penalty tracking with automated calculation (hours × MT × rate); demurrage alerts warn you BEFORE penalties hit."
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Penalty register</CardTitle>
                        <CardDescription>
                            Filter by siding, status, type, or date.{' '}
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
                                const fd = new FormData(form);
                                const params = new URLSearchParams();
                                for (const [key, val] of fd.entries()) {
                                    if (val) params.set(key, val as string);
                                }
                                router.get('/penalties', Object.fromEntries(params));
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
                            <div className="grid gap-2">
                                <label className="text-sm font-medium">Status</label>
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
                            <div className="grid gap-2">
                                <label className="text-sm font-medium">Type</label>
                                <select
                                    name="type"
                                    defaultValue={q.get('type') ?? ''}
                                    className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                >
                                    <option value="">All</option>
                                    <option value="DEM">DEM</option>
                                    <option value="POL1">POL1</option>
                                    <option value="POLA">POLA</option>
                                    <option value="PLO">PLO</option>
                                    <option value="ULC">ULC</option>
                                    <option value="SPL">SPL</option>
                                    <option value="WMC">WMC</option>
                                    <option value="MCF">MCF</option>
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <label className="text-sm font-medium">Responsible</label>
                                <select
                                    name="responsible_party"
                                    defaultValue={q.get('responsible_party') ?? ''}
                                    className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                >
                                    <option value="">All</option>
                                    <option value="railway">Railway</option>
                                    <option value="siding">Siding</option>
                                    <option value="transporter">Transporter</option>
                                    <option value="plant">Plant</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <label className="text-sm font-medium">From</label>
                                <input
                                    type="date"
                                    name="from"
                                    defaultValue={q.get('from') ?? ''}
                                    className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                />
                            </div>
                            <div className="grid gap-2">
                                <label className="text-sm font-medium">To</label>
                                <input
                                    type="date"
                                    name="to"
                                    defaultValue={q.get('to') ?? ''}
                                    className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                />
                            </div>
                            <Button type="submit" variant="secondary">
                                Filter
                            </Button>
                            {hasActiveFilters && (
                                <Link href="/penalties">
                                    <Button type="button" variant="ghost" size="sm">
                                        Clear
                                    </Button>
                                </Link>
                            )}
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
                                                <th className="px-4 py-3 text-left font-medium">Rake</th>
                                                <th className="px-4 py-3 text-left font-medium">Siding</th>
                                                <th className="px-4 py-3 text-left font-medium">Type</th>
                                                <th className="px-4 py-3 text-right font-medium">Amount</th>
                                                <th className="px-4 py-3 text-left font-medium">Status</th>
                                                <th className="px-4 py-3 text-left font-medium">Date</th>
                                                <th className="px-4 py-3 text-left font-medium">Responsible</th>
                                                <th className="px-4 py-3 text-left font-medium">Root Cause</th>
                                                <th className="px-4 py-3 text-right font-medium">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {penalties.data.map((p) => (
                                                <Fragment key={p.id}>
                                                    <tr className="border-b last:border-0 hover:bg-muted/30">
                                                        <td className="px-4 py-3 font-medium">
                                                            {p.rake?.rake_number ?? '-'}
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            {p.rake?.siding?.name ?? '-'}
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <span className="inline-flex rounded bg-muted px-1.5 py-0.5 text-xs font-medium">
                                                                {p.penalty_type}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-3 text-right font-medium">
                                                            ₹{Number(p.penalty_amount).toLocaleString()}
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_COLORS[p.penalty_status] ?? 'bg-muted text-muted-foreground'}`}>
                                                                {p.penalty_status}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-3 text-muted-foreground">
                                                            {p.penalty_date}
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <InlineResponsibleParty penalty={p} />
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <InlineRootCause penalty={p} />
                                                        </td>
                                                        <td className="px-4 py-3 text-right">
                                                            <div className="flex items-center justify-end gap-1.5">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => {
                                                                        const breakdown = p.calculation_breakdown;
                                                                        const details = [
                                                                            `penalty #${p.id}`,
                                                                            `type: ${p.penalty_type}`,
                                                                            `amount: ₹${Number(p.penalty_amount).toLocaleString()}`,
                                                                            p.rake?.rake_number ? `rake: ${p.rake.rake_number}` : null,
                                                                            p.rake?.siding?.name ? `siding: ${p.rake.siding.name}` : null,
                                                                            breakdown?.formula ? `formula: ${breakdown.formula}` : null,
                                                                        ].filter(Boolean).join(', ');

                                                                        window.dispatchEvent(
                                                                            new CustomEvent('chat:ask', {
                                                                                detail: {
                                                                                    message: `Explain ${details} and suggest how to avoid this in the future.`,
                                                                                },
                                                                            }),
                                                                        );
                                                                    }}
                                                                    data-pan="penalty-ask-ai"
                                                                >
                                                                    <Bot className="mr-1 h-3.5 w-3.5" />
                                                                    Ask AI
                                                                </Button>
                                                                <Link href={`/rakes/${p.rake_id}`}>
                                                                    <Button variant="outline" size="sm">
                                                                        View rake
                                                                    </Button>
                                                                </Link>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    {p.calculation_breakdown && (
                                                        <tr
                                                            key={`${p.id}-breakdown`}
                                                            className="border-b bg-muted/20 last:border-0"
                                                        >
                                                            <td
                                                                colSpan={9}
                                                                className="px-4 py-2.5 text-sm text-muted-foreground"
                                                            >
                                                                <span className="font-medium text-foreground">
                                                                    How calculated:{' '}
                                                                </span>
                                                                {p.calculation_breakdown.formula}
                                                                {p.calculation_breakdown.demurrage_hours != null && (
                                                                    <>
                                                                        {' '}= {p.calculation_breakdown.demurrage_hours} h
                                                                        × {p.calculation_breakdown.weight_mt} MT
                                                                        × ₹{p.calculation_breakdown.rate_per_mt_hour}/MT/h
                                                                    </>
                                                                )}
                                                                {p.calculation_breakdown.free_hours != null && (
                                                                    <>
                                                                        {' '}(free time: {p.calculation_breakdown.free_hours} h
                                                                    </>
                                                                )}
                                                                {p.calculation_breakdown.dwell_hours != null && (
                                                                    <>
                                                                        , dwell: {p.calculation_breakdown.dwell_hours} h
                                                                    </>
                                                                )}
                                                                {p.calculation_breakdown.free_hours != null && ')'}
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
                                            Page {penalties.current_page} of {penalties.last_page}
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
